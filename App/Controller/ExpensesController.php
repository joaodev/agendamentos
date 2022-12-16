<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;

class ExpensesController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $customersModel;
    private mixed $paymentTypesModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Expenses", "app");
        $this->customersModel = Container::getClass("Customers", "app");
        $this->paymentTypesModel = Container::getClass("PaymentTypes", "app");
    }

    public function indexAction(): void
    {
        if (!empty($_GET['m'])) {
            $month = $_GET['m'];
        } else {
            $month = date('Y-m');
        }

        $this->view->month = self::formatMonth($month);

        $data = $this->model->getAllByMonth('0', $month);
        $this->view->data = $data;

        $this->render('index', false);
    }

    public function createAction(): void
    {
        $customers = $this->customersModel->findAllActives('uuid, name');
        $this->view->customers = $customers;

        $paymentTypes = $this->paymentTypesModel->getAllActives();
        $this->view->paymentTypes = $paymentTypes;

        $this->render('create', false);
    }
    
    public function createProcessAction(): bool
    {
        if (!empty($_POST)) {
            $uuid = $this->model->NewUUID();
            $_POST['uuid'] = $uuid;
            $_POST['user_uuid'] = $_SESSION['COD'];

            if (!empty($_FILES)) {
                $image_name  = $_FILES["file"]["name"];
                if ($image_name != null) {
                    $ext_img = explode(".", $image_name, 2);
                    $new_name  = 'Arquivo_Despesa_'.date('dmY') . '.' . $ext_img[1];
                    $dir1 = "../public/uploads/expenses/" . $new_name;
                    $tmp_name1  =  $_FILES["file"]["tmp_name"];
                    if (move_uploaded_file($tmp_name1, $dir1)) {
                        $_POST['file'] = $new_name;
                    }
                }
            } else {
                unset($_POST['file']);
            }

            $_POST['amount'] = $this->moneyToDb($_POST['amount']);
 
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);
 
            if ($transaction){
                $this->toLog("Cadastrou a despesa $uuid");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Despesa cadastrada.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'A despesa não foi cadastrada.',
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
    
    public function updateAction(): void
    {
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
    
            $customers = $this->customersModel->findAllActives('uuid, name');
            $this->view->customers = $customers;

            $paymentTypes = $this->paymentTypesModel->getAllActives();
            $this->view->paymentTypes = $paymentTypes;
    
            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST)) {
            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $_POST['amount']  = $this->moneyToDb($_POST['amount']);

            if (!empty($_FILES)) {
                $image_name  = $_FILES["file"]["name"];
                if ($image_name != null) {
                    $ext_img = explode(".", $image_name, 2);
                    $new_name  = 'Arquivo_Despesa_'.date('dmY') . '.' . $ext_img[1];
                    $dir1 = "../public/uploads/expenses/" . $new_name;
                    $tmp_name1  =  $_FILES["file"]["tmp_name"];
                    if (move_uploaded_file($tmp_name1, $dir1)) {
                        $_POST['file'] = $new_name;
                    }
                }
            } else {
                unset($_POST['file']);
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction){
                $this->toLog("Atualizou a despesa {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Despesa atualizada.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'A despesa não foi atualizada.',
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

    public function readAction(): void
    {
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
            $this->render('read', false);
        }
    }

	public function deleteAction(): bool
    {
        if (!empty($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'deleted' => '1',
                'updated_at' => date('Y-m-d H:i:s')
            ],$_POST['uuid'], 'uuid');

            if ($transaction){
                $this->toLog("Removeu a despesa {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Despesa removida.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'A despesa não foi removida.',
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