<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;

class RevenuesController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $customersModel;
    private mixed $paymentTypesModel;
    private mixed $filesModel;
    private mixed $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Revenues", "app");
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
                $month, $this->model->getTable(), 'revenue_date', $parentUUID
            );

            $totalFree = ($activePlan['total_revenues'] - $totalData);
            $this->view->total_free = $totalFree;

            if ($totalData >= $activePlan['total_revenues']) {
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
        $customers = $this->customersModel->findAllActivesBy('uuid, name', 'parent_uuid', $this->parentUUID);
        $this->view->customers = $customers;

        $paymentTypes = $this->paymentTypesModel->getAllActives();
        $this->view->paymentTypes = $paymentTypes;

        $users = $this->userModel->getAllActives($this->parentUUID);
        $this->view->users = $users;

        $this->render('create', false);
    }
    
    public function createProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);

            if (!empty($_POST['send_email_customer'])) {
                $sendEmailCustomer = $_POST['send_email_customer'];
                unset($_POST['send_email_customer']);
            } else {
                $sendEmailCustomer = false;
            }

            if (!empty($_POST['send_email_user'])) {
                $sendEmailUser = $_POST['send_email_user'];
                unset($_POST['send_email_user']);
            } else {
                $sendEmailUser = false;
            }

            $parentUUID = $this->parentUUID;
            $activePlan = self::getActivePlan();
            $month = substr($_POST['revenue_date'], 0, 7);
            $totalRevenues = $this->model->totalMonthlyData(
                $month, $this->model->getTable(), 'revenue_date', $parentUUID
            );

            if ($totalRevenues >= $activePlan['total_revenues']) {
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
                        $this->filesModel->uploadFiles($_FILES, "revenues", $uuid);
                    }

                    $revenueTo = $this->formatDate($_POST['expense_date']);
                    $revenueStatus = "Pagamento pendente";
                    if ($_POST['status'] == 1) $revenueStatus = "Pagamento pendente";
                    if ($_POST['status'] == 2) $revenueStatus = "Pagamento concluído";
                    if ($_POST['status'] == 3) $revenueStatus = "Pagamento cancelado";

                    if (!empty($_POST['customer_uuid']) && $sendEmailCustomer == 1) {
                        $customer = $this->customersModel->getOne($_POST['customer_uuid'], $this->parentUUID);
                        $message = "<p>Novo recebimento para: $revenueTo.</p>
                                    <p>Situação: <b>$revenueStatus</b></p>";

                        $this->sendMail([
                            'title' => 'Recebimento - ' . $revenueStatus,
                            'message' => $message,
                            'name' => $customer['name'],
                            'toAddress' => $customer['email']
                        ]);
                    }

                    if (!empty($_POST['user_uuid']) && $sendEmailUser == 1) {
                        $user = $this->userModel->getOne($_POST['user_uuid'], $this->parentUUID);
                        $message = "<p>Novo recebimento para: $revenueTo.</p>
                                    <p>Situação: <b>$revenueStatus</b></p>";

                        $this->sendMail([
                            'title' => 'Recebimento - ' . $revenueStatus,
                            'message' => $message,
                            'name' => $user['name'],
                            'toAddress' => $user['email']
                        ]);
                    }
                    
                    $this->toLog("Cadastrou o recebimento $uuid");
                    $data  = [
                        'title' => 'Sucesso!', 
                        'msg'   => 'Recebimento cadastrado.',
                        'type'  => 'success',
                        'pos'   => 'top-right'
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!', 
                        'msg' => 'O recebimento não foi cadastrado.',
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

            if (!empty($_POST['send_email_customer'])) {
                $sendEmailCustomer = $_POST['send_email_customer'];
                unset($_POST['send_email_customer']);
            } else {
                $sendEmailCustomer = false;
            }

            if (!empty($_POST['send_email_user'])) {
                $sendEmailUser = $_POST['send_email_user'];
                unset($_POST['send_email_user']);
            } else {
                $sendEmailUser = false;
            }

            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $_POST['amount']  = $this->moneyToDb($_POST['amount']);

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction) {
                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "revenues", $_POST['uuid']);
                }

                $revenueTo = $this->formatDate($_POST['revenue_date']);
                $revenueStatus = "Pagamento pendente";
                if ($_POST['status'] == 1) $revenueStatus = "Pagamento pendente";
                if ($_POST['status'] == 2) $revenueStatus = "Pagamento concluído";
                if ($_POST['status'] == 3) $revenueStatus = "Pagamento cancelado";
                
                if (!empty($_POST['customer_uuid']) && $sendEmailCustomer == 1) {
                    $customer = $this->customersModel->getOne($_POST['customer_uuid'], $this->parentUUID);
                    $message = "<p>Novo recebimento para: $revenueTo.</p>
                                <p>Situação: <b>$revenueStatus</b></p>";

                    $this->sendMail([
                        'title' => 'Recebimento - ' . $revenueStatus,
                        'message' => $message,
                        'name' => $customer['name'],
                        'toAddress' => $customer['email']
                    ]);
                }

                if (!empty($_POST['user_uuid']) && $sendEmailUser == 1) {
                    $user = $this->userModel->getOne($_POST['user_uuid'], $this->parentUUID);
                    $message = "<p>Novo recebimento para: $revenueTo.</p>
                                <p>Situação: <b>$revenueStatus</b></p>";

                    $this->sendMail([
                        'title' => 'Recebimento - ' . $revenueStatus,
                        'message' => $message,
                        'name' => $user['name'],
                        'toAddress' => $user['email']
                    ]);
                }

                $this->toLog("Atualizou o recebimento {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Recebimento atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O recebimento não foi atualizado.',
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
                $this->toLog("Removeu o recebimento {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Recebimento removido.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O recebimento não foi removido.',
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