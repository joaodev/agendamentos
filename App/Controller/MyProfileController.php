<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Bcrypt;
use Core\Di\Container;
use Core\Db\Crud;

class MyProfileController extends ActionController
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("User", "app");
    }

    public function indexAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $entity = $this->model->getOne($_SESSION['COD'], $this->parentUUID);
            $this->view->entity = $entity;
            $this->render('index', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            
            $exists = $this->model->fieldExists('email', $_POST['email'], 'uuid', $_SESSION['COD']);
            if ($exists) {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'Não é possível utilizar este email.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];

                echo json_encode($data);
                return true;
            }

            if (!empty($_POST['password']) && !empty($_POST['confirmation'])) {
                if (($_POST['password'] != $_POST['confirmation'])) {
                    $data  = [
                        'title' => 'Erro!', 
                        'msg' => 'Senhas incorretas.',
                        'type' => 'error',
                        'pos'   => 'top-center'
                    ];

                    echo json_encode($data);
                    return true;
                } else {
                    unset($_POST['confirmation']);
                    $_POST['password'] = Bcrypt::hash($_POST['password']);
                }
            } else {
                unset($_POST['password']);
                unset($_POST['confirmation']);
            }

            if (!empty($_FILES) && !empty( $_FILES["file"])) {
                $image_name = $_FILES["file"]["name"];
                $image_name = str_replace(" ", "_", $image_name);
                if ($image_name != null) {
                    $tmp_name1  =  $_FILES["file"]["tmp_name"];
                    $dir1 = "../public/uploads/users/" . $image_name;
                    if (move_uploaded_file($tmp_name1, $dir1)) {
                        $_POST['file'] = $image_name;
                    } 
                }
            } else {
                if (!empty($_POST['remove_image'])) {
                    $_SESSION['PHOTO'] = null;
                    $_POST['file'] = null;
                    unset($_POST['remove_image']);
                } else {
                    unset($_POST['file']);
                }
            }

            if (empty($_POST['auth2factor'])) {
                $_POST['auth2factor'] = 0;
            }

            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_SESSION['COD'], 'uuid');

            if ($transaction){
                $_SESSION['NAME']  = $_POST['name'];
                $_SESSION['EMAIL'] = $_POST['email'];
                
                if (!empty($_POST['password'])) {
                    $_SESSION['PASS'] = $_POST['password'];
                }

                if (!empty($_POST['file'])) {
                    $_SESSION['PHOTO'] = $_POST['file'];
                }

                $this->toLog("Atualizou os dados através da página meu perfil");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Perfil atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Perfil não foi atualizado.',
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
}