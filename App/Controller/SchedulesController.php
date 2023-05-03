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
    private mixed $filesModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Schedules", "app");
        $this->customersModel = Container::getClass("Customers", "app");
        $this->paymentTypesModel = Container::getClass("PaymentTypes", "app");
        $this->servicesModel = Container::getClass("Services", "app");
        $this->filesModel = Container::getClass("Files", "app");
    }

    public function indexAction(): void
    {
        $parentUUID = $this->parentUUID;

        if (!empty($_GET['m'])) {
            $month = $_GET['m'];
        } else {
            $month = date('Y-m');
        }

        $this->view->month = self::formatMonth($month);

        $data = $this->model->getAllByMonth('0', $month, $parentUUID);
        $this->view->data = $data;

        $activePlan = self::getActivePlan();
        $totalSchedules = $this->model->totalMonthlyData(
            $month, $this->model->getTable(), 'schedule_date', $parentUUID
        );
        
        $totalFree = ($activePlan['total_schedules'] - $totalSchedules);
        $this->view->total_free = $totalFree;

        if ($activePlan['total_schedules'] == 0) {
            $reached_limit = false;
        } else {
            if ($totalSchedules >= $activePlan['total_schedules']) {
                $reached_limit = true;
            } else {
                $reached_limit = false;
            }   
        }

        $this->view->reached_limit = $reached_limit;

        $this->render('index', false);
    }

    public function createAction(): void
    {
        $parentUUID = $this->parentUUID;

        $customers = $this->customersModel->findAllActivesBy('uuid, name', 'parent_uuid', $parentUUID);
        $this->view->customers = $customers;

        $stringFields = 'uuid, title, description, price';
        $services = $this->servicesModel->findAllActivesBy($stringFields, 'parent_uuid', $parentUUID);
        $this->view->services = $services;

        $paymentTypes = $this->paymentTypesModel->getAllActives();
        $this->view->paymentTypes = $paymentTypes;

        $this->render('create', false);
    }
    
    public function createProcessAction(): bool
    {
        if (!empty($_POST)) {
            $parentUUID = $this->parentUUID;

            $activePlan = self::getActivePlan();
            $month = substr($_POST['schedule_date'], 0, 7);
            $totalSchedules = $this->model->totalMonthlyData(
                $month, $this->model->getTable(), 'schedule_date', $parentUUID
            );
            
            if ($totalSchedules >= $activePlan['total_schedules']) {
                $data  = [
                    'title' => 'Erro!',
                    'msg' => 'Você atingiu o limite de cadastros disponíveis para este plano.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];
            } else {
                $uuid = $this->model->NewUUID();
                $_POST['uuid'] = $uuid;
                $_POST['parent_uuid'] = $parentUUID;
                $_POST['amount'] = $this->moneyToDb($_POST['amount']);
    
                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->create($_POST);
    
                if ($transaction) {
                    if (!empty($_FILES)) {
                        $this->filesModel->uploadFiles($_FILES, "schedules", $uuid);
                    }

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
            $parentUUID = $this->parentUUID;
            $entity = $this->model->getOne($_POST['uuid'], $parentUUID);
            $this->view->entity = $entity;
    
            $paymentTypes = $this->paymentTypesModel->getAllActives();
            $this->view->paymentTypes = $paymentTypes;

            $customers = $this->customersModel->findAllActivesBy('uuid, name', 'parent_uuid', $parentUUID);
            $this->view->customers = $customers;

            $stringFields = 'uuid, title, description, price';
            $services = $this->servicesModel->findAllActivesBy($stringFields, 'parent_uuid', $parentUUID);
            $this->view->services = $services;
            
            $files = $this->filesModel->findAllBy('uuid, file', 'parent_uuid', $_POST['uuid']);
            $this->view->files = $files;

            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST)) {
            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $_POST['amount']  = $this->moneyToDb($_POST['amount']);

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction) {
                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "schedules", $_POST['uuid']);
                }

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
            $entity = $this->model->getOne($_POST['uuid'], $this->parentUUID);
            $this->view->entity = $entity;

            $files = $this->filesModel->findAllBy('file', 'parent_uuid', $_POST['uuid']);
            $this->view->files = $files;

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

            if ($transaction) {
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

    public function serviceDetailsAction(): bool
    {
        if (!empty($_POST)) {
            $fields = "description, price";
            $entity = $this->servicesModel->find($_POST['uuid'], $fields, 'uuid');

            if ($entity){
                $data =  number_format($entity['price'], 2, ",", ".");
            } else {
                $data  = [
                    'Erro ao consultar valor'
                ];
            }

            echo $data;
            return true;
        } else {
            echo 'Erro ao consultar valor';
            return false;
        }
    }

    public function createServiceAction(): void
    {
        $this->render('create-service', false);
    }

    public function createCustomerAction(): void
    {
        $this->render('create-customer', false);
    }

    public function deleteFileAction(): bool
    {
        if (!empty($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->filesModel->getTable());
            return $crud->update(['deleted' => '1'], $_POST['uuid'], 'uuid');
        } else {
            return false;
        }
    }
}