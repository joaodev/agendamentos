<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;

class ConfigController extends ActionController
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Config", "app");
    }

    public function indexAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $entity = $this->model->getEntity();
            $this->view->entity = $entity;
            $this->render('index', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            if (!empty($_FILES["logo"])) {
                $image_name_f1 = $_FILES["logo"]["name"];
                if ($image_name_f1 != null) {
                    $ext_img_f1 = explode(".", $image_name_f1, 2);
                    $new_name_f1 = md5($ext_img_f1[0]) . '.' . $ext_img_f1[1];
                    if (
                        $ext_img_f1[1] == 'jpg' || $ext_img_f1[1] == 'jpeg'
                        || $ext_img_f1[1] == 'png' || $ext_img_f1[1] == 'gif'
                    ) {
                        $tmp_name1_f1 = $_FILES["logo"]["tmp_name"];
                        $new_image_name_f1 = md5($new_name_f1 . time()) . '.png';
                        $dir1_f1 = "../public/uploads/logo/" . $new_image_name_f1;

                        if (move_uploaded_file($tmp_name1_f1, $dir1_f1)) {
                            $_POST['logo'] = $new_image_name_f1;
                        }
                    }
                }
            } else {
                if (!empty($_POST['remove_image_logo'])) {
                    $_POST['logo'] = null;
                    unset($_POST['remove_image_logo']);
                } else {
                    unset($_POST['logo']);
                }
            }

            if (!empty($_FILES["logo_icon"])) {
                $image_name_f2 = $_FILES["logo_icon"]["name"];
                if ($image_name_f2 != null) {
                    $ext_img_f2 = explode(".", $image_name_f2, 2);
                    $new_name_f2 = md5($ext_img_f2[0]) . '.' . $ext_img_f2[1];
                    if (
                        $ext_img_f2[1] == 'jpg' || $ext_img_f2[1] == 'jpeg'
                        || $ext_img_f2[1] == 'png' || $ext_img_f2[1] == 'gif'
                    ) {
                        $tmp_name1_f2 = $_FILES["logo_icon"]["tmp_name"];
                        $new_image_name_f2 = md5($new_name_f2 . time()) . '.png';
                        $dir1_f2 = "../public/uploads/logo/" . $new_image_name_f2;

                        if (move_uploaded_file($tmp_name1_f2, $dir1_f2)) {
                            $_POST['logo_icon'] = $new_image_name_f2;
                        }
                    }
                }
            } else {
                if (!empty($_POST['remove_image_icon'])) {
                    $_POST['logo_icon'] = null;
                    unset($_POST['remove_image_icon']);
                } else {
                    unset($_POST['logo_icon']);
                }
            }

            if (!empty($_FILES["file_menu"])) {
                $image_name_ft = $_FILES["file_menu"]["name"];
                if ($image_name_ft != null) {
                    $ext_img_ft = explode(".", $image_name_ft, 2);
                    $new_name_ft = md5($ext_img_ft[0]) . '.' . $ext_img_ft[1];
                    if (
                        $ext_img_ft[1] == 'jpg' || $ext_img_ft[1] == 'jpeg'
                        || $ext_img_ft[1] == 'png' || $ext_img_ft[1] == 'gif'
                    ) {
                        $tmp_name1_ft = $_FILES["file_menu"]["tmp_name"];
                        $new_image_name_ft = md5($new_name_ft . time()) . '.png';
                        $dir1_ft = "../public/uploads/menu/" . $new_image_name_ft;

                        if (move_uploaded_file($tmp_name1_ft, $dir1_ft)) {
                            $_POST['file_menu'] = $new_image_name_ft;
                        }
                    }
                }
            } else {
                if (!empty($_POST['remove_image_menu'])) {
                    $_POST['file_menu'] = null;
                    unset($_POST['remove_image_menu']);
                } else {
                    unset($_POST['file_menu']);
                }
            }

            if (empty($_POST['mail_password'])) {
                unset($_POST['mail_password']);
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction) {
                $this->toLog("Atualizou as configurações do sistema");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Configurações atualizadas.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'AS Configurações não foi atualizadas.',
                    'type' => 'error',
                    'pos' => 'top-center'
                ];
            }

            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }
}