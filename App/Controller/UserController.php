<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use Core\Db\Bcrypt;

class UserController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $roleModel;
    private mixed $privilegeModel;
    private mixed $aclModel;
    private mixed $plansModel;

    public function __construct()
    {
        parent::__construct();
        $this->model          = Container::getClass("User", "app");
        $this->roleModel      = Container::getClass("Role", "app");
        $this->privilegeModel = Container::getClass("Privilege", "app");
        $this->aclModel       = Container::getClass("Acl", "app");
        $this->plansModel     = Container::getClass("Plans", "app");
    }

    public function indexAction(): void
    {
        $data = $this->model->getAll();
        $this->view->data = $data;
        $this->render('index', false);
    }

    public function createAction(): void
    {
        $roles = $this->roleModel->getAll(null);
        $this->view->roles = $roles;
      
        $this->render('create', false);
    }

    public function createProcessAction(): bool
    {
        if (!empty($_POST)) {
            if (!empty($_POST['role_uuid'])) {
                $role = $this->roleModel->getOne($_POST['role_uuid']);
                if (!$role) {
                    $data  = [
                        'title' => 'Erro!', 
                        'msg' => 'Nível de acesso inválido.',
                        'type' => 'error',
                        'pos'   => 'top-center'
                    ];
                    
                    echo json_encode($data);
                    return true;
                }
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'Nível de acesso inválido.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];
                
                echo json_encode($data);
                return true;
            }

            if ($_POST['password'] != $_POST['confirmation']) {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'Senhas incorretas.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];
            } else {
                unset($_POST['confirmation']);
                $_POST['password'] = Bcrypt::hash($_POST['password']);

                $_POST['salary'] = $this->moneyToDb($_POST['salary']);

                if (empty($_POST['birthdate'])) {
                    unset($_POST['birthdate']);
                }
                if (empty($_POST['end_date'])) {
                    unset($_POST['end_date']);
                }

                $_POST['code'] = md5($this->randomString());
                $_POST['code_validated'] = 1;
    
                $_POST['uuid'] = $this->model->NewUUID();
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
    
                    $this->toLog("Cadastrou o usuário: {$_POST['name']} #{$_POST['uuid']}");
                    $data  = [
                        'title' => 'Sucesso!', 
                        'msg'   => 'Usuário cadastrado.',
                        'type'  => 'success',
                        'pos'   => 'top-right'
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!', 
                        'msg' => 'O Usuário não foi cadastrado.',
                        'type' => 'error',
                        'pos'   => 'top-center'
                    ];
                }
            }

            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }

	public function readAction(): void
    {
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
            $this->render('read', false);
        }
    }

	public function updateAction(): void
    {
        if (!empty($_POST)) {
            $roles = $this->roleModel->getAll(null);
            $this->view->roles = $roles;

            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;

            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST)) {
            $entity = $this->model->getOne($_POST['uuid']);
            if (!empty($_POST['password'])) {
                unset($_POST['password']);
            }
            
            if (!empty($_POST['salary']) && $_POST['salary'] != '0,00') {
                $_POST['salary'] = $this->moneyToDb($_POST['salary']);
            } else {
                $_POST['salary'] = null;
            }

            if (empty($_POST['birthdate'])) {
                $_POST['birthdate'] = null;
            }

            if (empty($_POST['end_date'])) {
                $_POST['end_date'] = null;
            }
            
            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction) {  
                if ($entity['role_uuid'] != $_POST['role_uuid']) {
                    $this->reorganizeAcl($_POST['role_uuid'], $_POST['uuid']);
                }

                $this->toLog("Atualizou o usuário: {$_POST['name']} #{$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Usuário atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Usuário não foi atualizado.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];
            }

            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }

	public function deleteAction(): bool
    {
        if (!empty($_POST)) {
            if (($_POST['uuid'] != $_SESSION['COD'])) {
                $updateData = [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'deleted' => '1'
                ];

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update($updateData, $_POST['uuid'], 'uuid');

                if ($transaction) {
                    $this->toLog("Removeu o usuário: #{$_POST['uuid']}");
                    $data  = [
                        'title' => 'Sucesso!', 
                        'msg'   => 'Usuário removido.',
                        'type'  => 'success',
                        'pos'   => 'top-right'
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!', 
                        'msg' => 'O Usuário não foi removido.',
                        'type' => 'error',
                        'pos'   => 'top-center'
                    ];
                }
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Usuário está logado.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];
            }
        } else {
            return false;
        }

        echo json_encode($data);
        return true;
    }

    public function fieldExistsAction()
    {
        if (!empty($_POST)) {
            $uuid  = (!empty($_POST['uuid']) ? $_POST['uuid'] : null);
            $field = "";

            if (!empty($_POST['name'])) $field      = 'name';
            if (!empty($_POST['email'])) $field     = 'email';
            if (!empty($_POST['cellphone'])) $field = 'cellphone';
            
            $exists = $this->model->fieldExists($field, $_POST[$field], 'uuid', $uuid);
            if ($exists) {
                echo 'false';
            } else {
                echo 'true';
            }
        }
    }

    public function aclAction()
    {
        if (!empty($_POST)) {
            $user = $this->model->getOne($_POST['uuid']);
            $this->view->user = $user;
            
            $data = $this->aclModel->getUserPrivileges($_POST['uuid']);
            $this->view->data = $data;
            $this->render('acl', false);
        }
    }

    public function alterPrivilegeAction(): bool
    {
        if (!empty($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->aclModel->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction) {
                $this->toLog("Permissões de usuário atualizadas: #{$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Privilégio atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Privilégio não foi atualizado.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];
            }

            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }

    private function reorganizeAcl($role, $user): void
    {
        if (!empty($role) && !empty($user)) {
            //limpa a acl de usuario
            if ($this->aclModel->cleanUserAcl($user)) {
                //limpa os privilegios de usuarios
                if ($this->privilegeModel->cleanUserPrivileges($user)) {
                    //configura os novos privilegios
                    $crud = new Crud();
                    $privileges = $this->privilegeModel->getAllByRoleUuid($role);
                    foreach ($privileges as $privilege) {
                        $aclData = [
                            'uuid' => $this->privilegeModel->NewUUID(),
                            'user_uuid' => $user,
                            'privilege_uuid' => $privilege['uuid']
                        ];
                        
                        $crud->setTable($this->aclModel->getTable());
                        $crud->create($aclData);
                    }
                }
            }
        }
    }
}