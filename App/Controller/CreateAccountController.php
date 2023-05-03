<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Bcrypt;
use Core\Db\Crud;
use Core\Di\Container;

class CreateAccountController extends ActionController
{
    private mixed $model;
    private mixed $roleModel;
    private mixed $privilegeModel;
    private mixed $aclModel;

    public function __construct()
    {
        parent::__construct();

        $this->model = Container::getClass("User", "app");
        $this->roleModel = Container::getClass("Role", "app");
        $this->privilegeModel = Container::getClass("Privilege", "app");
        $this->aclModel = Container::getClass("Acl", "app");
    }

    public function indexAction(): void
    {
        if (!empty($_POST)) {
            if ($_POST['password'] != $_POST['confirmation']) {
                self::redirect('', 'senhas-incorretas&n=' . $_POST['name'] . '&e=' . $_POST['email']);
            } else {
                $role = 'ebd1a81c-2d47-21a7-4702-d1b26f04f23a';
                if ($this->roleModel->getOne($role)) {
                    unset($_POST['confirmation']);
                    $passwordMd5 = $_POST['password'];
                    $_POST['password'] = Bcrypt::hash($_POST['password']);
                    $_POST['role_uuid'] = $role;

                    $_POST['uuid'] = $this->model->NewUUID();
                    $_POST['parent_uuid'] = $_POST['uuid'];

                    $crud = new Crud();
                    $crud->setTable($this->model->getTable());
                    $transaction = $crud->create($_POST);
                    
                    if ($transaction) {
                        $privileges = $this->privilegeModel->getAllByRoleUuid($_POST['role_uuid']);
                        foreach ($privileges as $privilege) {
                            $aclData = [
                                'uuid' => $this->privilegeModel->NewUUID(),
                                'user_uuid' => $_POST['uuid'],
                                'privilege_uuid' => $privilege['uuid']
                            ];
        
                            $crud->setTable($this->aclModel->getTable());
                            $crud->create($aclData);
                        }

                        $credentials = $this->model
	        					->findByCrenditials($_POST['email'], $passwordMd5); 

                        if ($credentials) {
                            $_SESSION['COD']         = $credentials['uuid'];
                            $_SESSION['NAME']        = $credentials['name'];
                            $_SESSION['EMAIL']       = $credentials['email'];
                            $_SESSION['PASS']        = $credentials['password'];
                            $_SESSION['ROLE_NAME']   = $credentials['role'];
                            $_SESSION['ROLE']        = $credentials['role_uuid'];
                            $_SESSION['ROLE_ADM']    = $credentials['is_admin'];
                            $_SESSION['PHOTO']       = $credentials['file'];

                            $this->toLog("Usuário cadastrado: {$_POST['name']} #{$_POST['uuid']}, aguardando validação do token");
                            self::registerToken($credentials['uuid']);

                            self::redirect('');
                            die;
                        } else {
                            self::redirect('', 'usuario-invalido');
                        }
                    } else {
                        self::redirect('', 'nao-enviado&n=' . $_POST['name'] . '&e=' . $_POST['email']);
                    }
                } else {
                    self::redirect('', 'nao-enviado&n=' . $_POST['name'] . '&e=' . $_POST['email']);
                }
            }
        }
    }

    private function registerToken($uuid)
    {
        $user = $this->model->find($uuid, 'uuid, name, email', 'uuid');
        if ($user) {
            $code = $this->randomString();

            $crud = new Crud();
            $crud->setTable($this->model->getTable());

            $updateCode = $crud->update([
                'code' => md5($code),
                'code_validated' => '0',
                'updated_at' => date('Y-m-d H:i:s')
            ], $uuid, 'uuid');

            if ($updateCode) {
                $message = "<p>Este é o seu token para validar seu acesso ao sistema:</p>
                                <h1>{$code}</h1>";

                $hasSend = $this->sendMail([
                    'title' => 'Token de Acesso',
                    'message' => $message,
                    'name' => $user['name'],
                    'toAddress' => $user['email']
                ]);

                if ($hasSend) {
                    $this->toLog("{$user['name']} solicitou um token para acessar o sistema.");
                } else {
                    $this->toLog("{$user['name']} tentou solicitar um token para acessar o sistema.");
                }
            }
        }
    }
}