<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;

class SchedulesController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $customersModel;
    private mixed $paymentTypesModel;
    private mixed $servicesModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Schedules", "app");
        $this->customersModel = Container::getClass("Customers", "app");
        $this->paymentTypesModel = Container::getClass("PaymentTypes", "app");
        $this->servicesModel = Container::getClass("Services", "app");
    }

    public function indexAction(): void
    {
        $data = $this->model->getAll();
        $this->view->data = $data;

        $this->render('index', false);
    }

    public function createAction(): void
    {
        $customers = $this->customersModel->findAllActives('uuid, name');
        $this->view->customers = $customers;

        $paymentTypes = $this->paymentTypesModel->getAllActives();
        $this->view->paymentTypes = $paymentTypes;

        $services = $this->servicesModel->findAllActives('uuid, title, description, price');
        $this->view->services = $services;

        $this->render('create', false);
    }
    
    public function createProcessAction(): bool
    {
        if (!empty($_POST)) {
            $uuid = $this->model->NewUUID();
            $_POST['uuid'] = $uuid;

            if (!empty($_FILES)) {
                $image_name  = $_FILES["file"]["name"];
                if ($image_name != null) {
                    $ext_img = explode(".", $image_name, 2);
                    $new_name  = 'Arquivo_Agendamento_'.date('dmY') . '.' . $ext_img[1];
                    $dir1 = "../public/uploads/schedules/" . $new_name;
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
                $this->toLog("Cadastrou o agendamento $uuid");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Agendamento cadastrado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Agendamento não foi cadastrado.',
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

            $services = $this->servicesModel->findAllActives('uuid, title, description, price');
            $this->view->services = $services;
    
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
                    $new_name  = 'Arquivo_Agendamento_'.date('dmY') . '.' . $ext_img[1];
                    $dir1 = "../public/uploads/schedules/" . $new_name;
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
                $this->toLog("Atualizou o agendamento {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Agendamento atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Agendamento não foi atualizado.',
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
                $this->toLog("Removeu o agendamento {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Agendamento removido.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Agendamento não foi removido.',
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