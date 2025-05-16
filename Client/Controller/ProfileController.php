<?php

namespace Client\Controller;

use Core\Controller\ActionController;
use Client\Model\Customer;
use Core\Db\Crud;

class ProfileController extends ActionController
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Customer();
    }

    public function indexAction(): void
    {
        if ($this->validateClientPostParams($_POST)) {
            $entity = $this->model->getOne($_SESSION['CLI_COD']);
            $this->view->entity = $entity;

            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if ($this->validateClientPostParams($_POST)) {
            unset($_POST['target']);
            
            $exists = $this->model->fieldExists('email', $_POST['email'], 'id', $_SESSION['CLI_COD']);
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

            if (!empty($_FILES) && !empty( $_FILES["file"])) {
                $image_name = $_FILES["file"]["name"];
                if ($image_name != null) {
                    $ext_img = explode(".", $image_name, 2);
                    $new_name  = md5($ext_img[0]) . '.' . $ext_img[1];
                    if ($ext_img[1] == 'jpg' || $ext_img[1] == 'jpeg'
                        || $ext_img[1] == 'png' || $ext_img[1] == 'gif') {
                        $tmp_name1  =  $_FILES["file"]["tmp_name"];
                        $new_image_name = md5($new_name . time()).'.png';
                        $dir1 = "../public/uploads/customers/" . $new_image_name;

                        if (move_uploaded_file($tmp_name1, $dir1)) {
                            $_POST['file'] = $new_image_name;
                        } 
                    }
                }
            } else {
                if (!empty($_POST['remove_image'])) {
                    $_SESSION['CLI_PHOTO'] = null;
                    $_POST['file'] = null;
                    unset($_POST['remove_image']);
                } else {
                    unset($_POST['file']);
                }
            }

            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_SESSION['CLI_COD'], 'id');
            
            if ($transaction){
                $_SESSION['CLI_NAME']  = $_POST['name'];
                $_SESSION['CLI_EMAIL'] = $_POST['email'];
                
                if (!empty($_POST['file'])) {
                    $_SESSION['CLI_PHOTO'] = $_POST['file'];
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