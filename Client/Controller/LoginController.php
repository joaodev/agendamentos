<?php

namespace Client\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use Client\Model\Customer;

class LoginController extends ActionController
{
	private mixed $model;

    public function __construct() {
        parent::__construct();
        $this->model = new Customer();

        if (!empty($_POST)) {
            self::dataValidation($_POST);
        }

        $config = $this->getSiteConfig();
        if ($config['customer_area'] == 0) {
            self::redirect('');    
        }
    }

    public function indexAction(): void
    {
        $this->render('index', false);
    }

    public function authAction(): void
    {
    	if (!empty($_POST)) {
            if (!empty($_SESSION['TOKEN_EMAIL'])) {
                unset($_SESSION['TOKEN_EMAIL']);
            } 
            
            $customerId = $this->model->getIdByField('email', $_POST['email'], 'id');
            if ($customerId > 0) {
                $customer = $this->model->find($customerId, 'id, name, email', 'id');
                $code = $this->randomString(10);

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                
                $updateCode = $crud->update([
                    'code' => md5($code),
                    'code_validated' => '0',
                    'updated_at' => date('Y-m-d H:i:s')
                ], $customerId, 'id');
                
                if ($updateCode) {
                    $message = "<p>Este é o seu token para validar seu acesso ao sistema:</p>
                            <h1>{$code}</h1>";

                    $hasSend = $this->sendMail([
                        'title' => 'Token de Acesso',
                        'message' => $message,
                        'name' => $customer['name'],
                        'toAddress' => $customer['email']
                    ]);

                    if ($hasSend) {
                        $_SESSION['TOKEN_EMAIL'] = $_POST['email'];
                        $this->toLog("{$customer['name']} solicitou um token para acessar o sistema.");
                    } else {
                        $this->toLog("{$customer['name']} tentou solicitar um token para acessar o sistema.");
                    }
                } 
            }

            self::redirect('cliente/token');
            die;
        } else {
        	self::redirect('cliente');
        }
    }

    public function tokenAction(): void
    {
        if (!empty($_POST)) {
            $auth = $this->model->authByCrenditials($_POST['email'], $_POST['token']);
            if ($auth) {
                $_SESSION['CLI_COD']   = $auth['id'];
	            $_SESSION['CLI_NAME']  = $auth['name'];
	            $_SESSION['CLI_EMAIL'] = $auth['email'];
	            $_SESSION['CLI_TOKEN'] = $auth['code'];
                $_SESSION['CLI_PHOTO'] = $auth['file'];

	            self::redirect('cliente');
            } else {
                $_SESSION['TOKEN_EMAIL'] = $_POST['email'];
                self::redirect('cliente/token', 'token-invalido');
            }
        } else {
            if (!empty($_SESSION['TOKEN_EMAIL'])) {
                $this->view->email = $_SESSION['TOKEN_EMAIL'];
                unset($_SESSION['TOKEN_EMAIL']);
            }
    
            $this->render('auth-token', false);
        }
    }

    public function logoutAction(): void
    {
        unset($_SESSION['CLI_COD']);
        unset($_SESSION['CLI_NAME']);
        unset($_SESSION['CLI_EMAIL']);
        unset($_SESSION['CLI_TOKEN']);
        session_destroy();

        self::redirect('cliente');
    }

    private function registerToken(string $id): void
    {
        $customer = $this->model->find($id, 'id, name, email', 'id');
        if ($customer > 0) {
            $code = $this->randomString(10);

            $crud = new Crud();
            $crud->setTable($this->model->getTable());

            $updateCode = $crud->update([
                'code' => md5($code),
                'code_validated' => '0',
                'updated_at' => date('Y-m-d H:i:s')
            ], $id, 'id');

            if ($updateCode) {
                $message = "<p>Este é o seu token para validar seu acesso ao sistema:</p>
                            <h1>{$code}</h1>";

                $hasSend = $this->sendMail([
                    'title' => 'Token de Acesso',
                    'message' => $message,
                    'name' => $customer['name'],
                    'toAddress' => $customer['email']
                ]);

                if ($hasSend) {
                    $this->toLog("{$customer['name']} solicitou um token para acessar o sistema.");
                } else {
                    $this->toLog("{$customer['name']} tentou solicitar um token para acessar o sistema.");
                }
            }
        }
    }
}