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
    private mixed $filesModel;
    private mixed $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Expenses", "app");
        $this->customersModel = Container::getClass("Customers", "app");
        $this->paymentTypesModel = Container::getClass("PaymentTypes", "app");
        $this->filesModel = Container::getClass("Files", "app");
        $this->userModel = Container::getClass("User", "app");
    }

    public function indexAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
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
            $totalData = $this->model->totalMonthlyData(
                $month, $this->model->getTable(), 'expense_date', $parentUUID
            );

            $totalFree = ($activePlan['total_expenses'] - $totalData);
            $this->view->total_free = $totalFree;

            if ($totalData >= $activePlan['total_expenses']) {
                $reached_limit = true;
            } else {
                $reached_limit = false;
            }   
            $this->view->reached_limit = $reached_limit;

            $this->render('index', false);
        }
    }

    public function createAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $customers = $this->customersModel->findAllActivesBy('uuid, name', 'parent_uuid', $this->parentUUID);
            $this->view->customers = $customers;

            $paymentTypes = $this->paymentTypesModel->getAllActives();
            $this->view->paymentTypes = $paymentTypes;

            $users = $this->userModel->getAllActives($this->parentUUID);
            $this->view->users = $users;    

            $this->render('create', false);
        }
    }
    
    public function createProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            
            $parentUUID = $this->parentUUID;
        
            $activePlan = self::getActivePlan();
            $month = substr($_POST['expense_date'], 0, 7);
            $totalExpenses = $this->model->totalMonthlyData(
                $month, $this->model->getTable(), 'expense_date', $parentUUID
            );

            if ($totalExpenses >= $activePlan['total_expenses']) {
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
                        $this->filesModel->uploadFiles($_FILES, "expenses", $uuid);
                    }
                
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
            }
                
            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }
    
    public function updateAction(): void
    {
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $parentUUID = $this->parentUUID;

            $entity = $this->model->getOne($_POST['uuid'], $parentUUID);
            $this->view->entity = $entity;
    
            $customers = $this->customersModel->findAllActivesBy('uuid, name', 'parent_uuid', $parentUUID);
            $this->view->customers = $customers;

            $paymentTypes = $this->paymentTypesModel->getAllActives();
            $this->view->paymentTypes = $paymentTypes;

            $files = $this->filesModel->findAllBy('uuid, file', 'parent_uuid', $_POST['uuid']);
            $this->view->files = $files;

            $users = $this->userModel->getAllActives($this->parentUUID);
            $this->view->users = $users;    
    
            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            
            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $_POST['amount']  = $this->moneyToDb($_POST['amount']);

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction) {
                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "expenses", $_POST['uuid']);
                }

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
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $entity = $this->model->getOne($_POST['uuid'], $this->parentUUID);
            $this->view->entity = $entity;

            $files = $this->filesModel->findAllBy('uuid, file', 'parent_uuid', $_POST['uuid']);
            $this->view->files = $files;
            
            $this->render('read', false);
        }
    }

	public function deleteAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'deleted' => '1',
                'updated_at' => date('Y-m-d H:i:s')
            ],$_POST['uuid'], 'uuid');

            if ($transaction) {
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

    public function deleteFileAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $crud = new Crud();
            $crud->setTable($this->filesModel->getTable());
            return $crud->update(['deleted' => '1'], $_POST['uuid'], 'uuid');
        } else {
            return false;
        }
    }
    
    public function createCustomerAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $this->render('create-customer', false);
        }
    }
}