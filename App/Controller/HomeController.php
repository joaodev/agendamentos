<?php 

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;

class HomeController extends ActionController
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Home", "app");
    }

    public function indexAction(): void
    {
        $entity = $this->model->getOne();
        $this->view->entity = $entity;
        $this->render('index', false);
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST)) {
            $postData = [
                'title' => $_POST['title'],                
                'description_1' => $_POST['description_1'],                
                'footer_title' => $_POST['footer_title'],       
                'footer_description' => $_POST['footer_description'], 
                'updated_at' => date('Y-m-d H:i:s')            
            ];

            if (!empty($_FILES)) {
                $image_name  = $_FILES["file"]["name"];
                if ($image_name != null) {
                    $ext_img = explode(".", $image_name, 2);
                    $new_name  = md5($ext_img[0]) . '.' . $ext_img[1];
                    if ($ext_img[1] == 'jpg' || $ext_img[1] == 'jpeg'
                        || $ext_img[1] == 'png' || $ext_img[1] == 'gif') {
                        $tmp_name1  =  $_FILES["file"]["tmp_name"];
                        $new_image_name = md5($new_name . time()).'.png';
                        $dir1 = "../public/uploads/about/" . $new_image_name;

                        if (move_uploaded_file($tmp_name1, $dir1)) {
                            $postData['file'] = $new_image_name;
                        } 
                    }
                }
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($postData, 1, 'id');

            if ($transaction){
                $this->toLog("Atualizou os dados da home");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Artigo atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'Os dados não foram atualizados.',
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