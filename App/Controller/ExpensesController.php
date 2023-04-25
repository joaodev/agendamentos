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

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Expenses", "app");
        $this->customersModel = Container::getClass("Customers", "app");
        $this->paymentTypesModel = Container::getClass("PaymentTypes", "app");
        $this->filesModel = Container::getClass("Files", "app");
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

        $activePlan = self::getActivePlan();
        $totalData = $this->model->totalMonthlyData(
            $month, $this->model->getTable(), 'expense_date', $_SESSION['COD']
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
            $activePlan = self::getActivePlan();
            $month = substr($_POST['expense_date'], 0, 7);
            $totalExpenses = $this->model->totalMonthlyData(
                $month, $this->model->getTable(), 'expense_date', $_SESSION['COD']
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
                $_POST['user_uuid'] = $_SESSION['COD'];
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
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
    
            $customers = $this->customersModel->findAllActives('uuid, name');
            $this->view->customers = $customers;

            $paymentTypes = $this->paymentTypesModel->getAllActives();
            $this->view->paymentTypes = $paymentTypes;

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
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;

            $files = $this->filesModel->findAllBy('uuid, file', 'parent_uuid', $_POST['uuid']);
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
        if (!empty($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->filesModel->getTable());
            return $crud->update(['deleted' => '1'], $_POST['uuid'], 'uuid');
        } else {
            return false;
        }
    }
}