<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Bcrypt;
use Core\Db\Crud;
use Core\Di\Container;

class LoginController extends ActionController
{
	private mixed $userModel;
    private mixed $devicesModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = Container::getClass("User", "app");
        $this->devicesModel = Container::getClass("UserDevices", "app");

        if (!empty($_POST)) {
            self::dataValidation($_POST);
        }
    }

    public function indexAction(): void
    {
        if (!empty($_SESSION['EMAIL']) && !empty($_SESSION['PASS']) && empty($_SESSION['TOKEN'])) {
            $this->render('token', false);
        } else {
            if (session_id()) {
                session_destroy();
            }
            $this->render('index', false);
        }
    }

    public function authAction()
    {
    	if (!empty($_POST)) {
        	$email = $_POST['email'];
	        $password  = $_POST['password'];

	        $credentials = $this->userModel
	        					->findByCrenditials($email, $password); 

            if ($credentials) {
                $_SESSION['COD']         = $credentials['uuid'];
	            $_SESSION['NAME']        = $credentials['name'];
	            $_SESSION['EMAIL']       = $credentials['email'];
	            $_SESSION['PASS']        = $credentials['password'];
	            $_SESSION['ROLE_NAME']   = $credentials['role'];
	            $_SESSION['ROLE']        = $credentials['role_uuid'];
	            $_SESSION['ROLE_ADM']    = $credentials['is_admin'];
	            $_SESSION['PHOTO']       = $credentials['file'];

                $userPlan = $this->getActiveUserPlan($credentials['uuid']);
                if (!empty($userPlan)) {
                    $_SESSION['PLAN_NAME'] = $userPlan['planName'];
                    $_SESSION['PLAN'] = $userPlan['plan_uuid'];
                } else {
                    $_SESSION['PLAN_NAME'] = null;
                    $_SESSION['PLAN'] = null;
                }

                if ($credentials['auth2factor'] == 1) {
                    $currentDevice = $_SERVER["HTTP_USER_AGENT"];
                    $checkedDevices = $this->devicesModel->findAllBy('user_device', 'user_uuid', $credentials['uuid']);

                    $devices = [];
                    foreach ($checkedDevices as $checkedDevice) {
                        $devices[] = $checkedDevice['user_device'];
                    }

                    if (in_array($currentDevice, $devices)) {
                        $_SESSION['TOKEN'] = $credentials['code'];
                        $this->toLog("Fez login no sistema usando um dispositivo registrado.");
                    } else {
                        $this->toLog("Fez login no sistema, aguardando validação do token");
                        self::registerToken($credentials['uuid']);
                    }
                } else {
                    $_SESSION['TOKEN'] = $credentials['code'];
                    $this->toLog("Fez login no sistema no modo padrão.");
                }

	            self::redirect('');
                die;
	        } else {
	            self::redirect('', 'usuario-invalido');
	        }
        } else {
        	self::redirect('');
        }
    }

    public function logoutAction()
    {
        $this->toLog("Fez logout do sistema");

        unset($_SESSION['COD']);
        unset($_SESSION['NAME']);
        unset($_SESSION['EMAIL']);
        unset($_SESSION['PASS']);
        unset($_SESSION['ROLE_NAME']);
        unset($_SESSION['ROLE']);
        unset($_SESSION['PHOTO']);
        session_destroy();

        self::redirect('');
    }

    public function forgotPasswordAction()
    {
        if (!empty($_POST)) {
            $userUuid = $this->userModel->getUuidByField('email', $_POST['email'], 'uuid');
            if ($userUuid > 0) {
                $user = $this->userModel->find($userUuid, 'uuid, name, email', 'uuid');
                $code = $this->randomString();

                $crud = new Crud();
                $crud->setTable($this->userModel->getTable());
                
                $updateCode = $crud->update([
                    'code' => md5($code),
                    'updated_at' => date('Y-m-d H:i:s')
                ], $userUuid, 'uuid');

                if ($updateCode) {
                    $message = "<p>Este é o seu código para validar sua alteração de senha:</p>
                                <h1>{$code}</h1>";

                    $this->sendMail([
                        'title' => 'Código de Verificação',
                        'message' => $message,
                        'name' => $user['name'],
                        'toAddress' => $user['email']
                    ]);

                    $this->toLog("{$user['name']} solicitou um código para recuperação de senha.");
                }
            } 
            
            self::redirect('validar-codigo');
        } else {
            $this->render('forgot-password', false);
        }
    }
    
    public function codeValidationAction()
    {
        if (!empty($_POST)) {
            $code = ($_POST['code']);
            $userUuid = $this->userModel->getUuidByField('code', $code, 'uuid');
            if ($userUuid > 0) {
                $crud = new Crud();
                $crud->setTable($this->userModel->getTable());
                $validatedCode = $crud->update([
                    'code_validated' => '1',
                    'updated_at' => date('Y-m-d H:i:s')
                ], $userUuid, 'uuid');
    
                if ($validatedCode) {
                    $this->view->code = $code;
                    $this->render('change-password', false);
                } else {
                    self::redirect('validar-codigo', 'codigo-invalido');
                }
            } else {
                self::redirect('validar-codigo', 'codigo-invalido');
            }
        } else {
            $this->render('code-validation', false);
        }
    }

    public function changePasswordAction()
    {
        if (!empty($_POST)) {
            if (empty($_POST['info'])) {
                self::redirect('validar-codigo', 'codigo-invalido');
            } else {
                if ($_POST['password'] != $_POST['confirmation']) {
                    $this->view->code = $_POST['info'];
                    $this->view->errorPasswords = 'Senhas incorretas!';
                    $this->render('change-password', false);
                } else {
                    $userUuid = $this->userModel->getUuidByField('code', $_POST['info'], 'uuid');
                    if ($userUuid > 0) {
                        $user = $this->userModel->find($userUuid, 'uuid, email, password, code, code_validated', 'uuid');
                        if ($user['code'] != $_POST['info'] || $user['code_validated'] != '1') {
                            self::redirect('validar-codigo', 'codigo-invalido');
                        } else {
                            $crud = new Crud();
                            $crud->setTable($this->userModel->getTable());
                            
                            $newPassword = Bcrypt::hash($_POST['password']);
                            $updatedPassword = $crud->update([
                                'password' => $newPassword,
                                'updated_at' => date('Y-m-d H:i:s')
                            ], $user['uuid'], 'uuid');

                            if ($updatedPassword) {   
                                $credentials = $this->userModel
	        					    ->authByCrenditials($user['email'], $newPassword, $_POST['info']);
                            
                                if ($credentials) {
                                    $_SESSION['COD']         = $credentials['uuid'];
                                    $_SESSION['NAME']        = $credentials['name'];
                                    $_SESSION['EMAIL']       = $credentials['email'];
                                    $_SESSION['PASS']        = $newPassword;
                                    $_SESSION['ROLE_NAME']   = $credentials['role'];
                                    $_SESSION['ROLE']        = $credentials['role_uuid'];
                                    $_SESSION['ROLE_ADM']    = $credentials['is_admin'];
                                    $_SESSION['PHOTO']       = $credentials['file'];
                                    $_SESSION['TOKEN']       = $_POST['info'];
                                    
                                    $this->toLog("Atualizou a senha e fez o login no sistema.");

                                    self::redirect('');
                                } else {
                                    self::redirect('', 'usuario-invalido');
                                }
                            } else {
                                self::redirect('validar-codigo', 'codigo-invalido');
                            }
                        }
                    } else {
                        self::redirect('validar-codigo', 'codigo-invalido');
                    }
                }
            }
        } else {
            self::redirect('validar-codigo', 'codigo-invalido');
        }
    }

    public function tokenAuthAction()
    {
        if (!empty($_POST['token'])) {
            $code = $_POST['token'];
            $userUuid = $this->userModel->getUuidByField('code', $code, 'uuid');
            if ($userUuid > 0) {
                $newData = [
                    'code_validated' => '1',
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if (!empty($_POST['remember']) && $_POST['remember'] == 1) {
                    $crudDevice = new Crud();
                    $crudDevice->setTable($this->devicesModel->getTable());
                    $crudDevice->create([
                        'uuid' => $this->devicesModel->NewUUID(),
                        'user_uuid' => $userUuid,
                        'user_device' => $_SERVER["HTTP_USER_AGENT"]
                    ]);
                }

                $crud = new Crud();
                $crud->setTable($this->userModel->getTable());
                $validatedCode = $crud->update($newData, $userUuid, 'uuid') ;

                if ($validatedCode) {
                    $_SESSION['TOKEN'] = $code;
                    self::redirect('');
                } else {
                    self::redirect('', 'token-invalido');
                }
            } else {
                self::redirect('', 'token-invalido');
            }
        } else {
            self::redirect('');
        }
    }

    private function registerToken($uuid)
    {
        $user = $this->userModel->find($uuid, 'uuid, name, email', 'uuid');
        if ($user) {
            $code = $this->randomString();

            $crud = new Crud();
            $crud->setTable($this->userModel->getTable());

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

    public function tokenCancelAction()
    {
        unset($_SESSION['PASS']);
        unset($_SESSION['EMAIL']);
        unset($_SESSION['TOKEN']);
        session_destroy();
        self::redirect('');
    }
}