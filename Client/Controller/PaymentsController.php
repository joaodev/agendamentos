<?php

namespace Client\Controller;

use App\Model\ChangesHistoric;
use App\Model\Expenses;
use App\Model\Files;
use App\Model\PaymentTypes;
use Core\Controller\ActionController;
use Core\Db\Crud;

class PaymentsController extends ActionController
{
    private mixed $expensesModel;
    private mixed $filesModel;
    private mixed $paymentTypesModel;
    private mixed $changesHistoricModel;

    public function __construct()
    {
        parent::__construct();
        $this->expensesModel = new Expenses();
        $this->filesModel = new Files();
        $this->paymentTypesModel = new PaymentTypes();
        $this->changesHistoricModel = new ChangesHistoric();
    }

    public function indexAction(): void 
    {
        if ($this->validateClientPostParams($_POST)) {
            if (!empty($_GET['m'])) {
                $month = $_GET['m'];
            } else {
                $month = date('Y-m');
            }

            $this->view->month = self::formatMonth($month);
            $this->view->month_not_formatted = $month;
            
            $data = $this->expensesModel->getAllByMonth('0', $month, $_SESSION['CLI_COD']);
            $this->view->data = $data;

            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function readAction(): void
    {
        if (!empty($_POST['id']) && $this->validateClientPostParams($_POST)) {
            $entity = $this->expensesModel->getOne($_POST['id'], $_SESSION['CLI_COD'], false);
            if ($entity) {
                $this->view->entity = $entity;

                $files = $this->filesModel->findAllBy('id, file, created_at', 'expense_id', $_POST['id']);
                $this->view->files = $files;

                $historic = $this->changesHistoricModel->getAllByModule('expense_id', $_POST['id']);
                $this->view->historic = $historic;
                
                $this->render('read', false);
            } else {
                $this->render('../error/not-found', false);
            }
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function updatePaymentAction(): void
    {
        if ($this->validateClientPostParams($_POST)) {
            $paymentTypes = $this->paymentTypesModel->getAllActives();
            $this->view->paymentTypes = $paymentTypes;           
            $this->render('update-payment', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function processPaymentAction(): bool
    {
        if (!empty($_POST) && $this->validateClientPostParams($_POST)) {
            unset($_POST['target']);

            $entity = $this->expensesModel->getOne($_POST['id'], $_SESSION['CLI_COD']);
            if ($entity) {
                $status = 4;

                $updateData = [
                    'payment_type_id' => $_POST['payment_type_id'],
                    'description' => $_POST['description'],
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $crud = new Crud();
                $crud->setTable($this->expensesModel->getTable());
                $transaction = $crud->update($updateData, $entity['id'], 'id');
                
                if ($transaction) {
                    if (!empty($_FILES)) {
                        $this->filesModel->uploadFiles($_FILES, "expenses", $entity['id'], 'expense_id');
                    }

                    if ($entity['expense_type'] == '1') {
                        $title = "Conta a Pagar";
                        $type = "expenses-down";
                    }

                    if ($entity['expense_type'] == '2') {
                        $title = "Conta a Receber";
                        $type = "expenses-up";
                    }

                    if (!empty($entity['customer_id']) && $entity['customerEmail']) {
                        $url = baseUrl . 'cliente';
                        $message = "<h3>Informações da conta emitida:</h3>";
                        $message .= "<p>Uma conta para pagamento acaba de ser atualizada, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
    
                        $this->sendMail([
                            'title' => $title . ' #' . $entity['id'],
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }
    
                    if (!empty($entity['provider_id']) && $entity['providerEmail']) {
                        $message = "<h3>Informações da conta emitida:</h3>";
                        $this->sendMail([
                            'title' => $title . ' #' . $entity['id'],
                            'message' => $message,
                            'name' => $entity['providerName'],
                            'toAddress' => $entity['providerEmail']
                        ]);
                    }
    
                    if (!empty($entity['user_id'])) {
                        $message = "<h3>Informações da conta emitida:</h3>";
                        $this->sendMail([
                            'title' => $title . ' #' . $entity['id'],
                            'message' => $message,
                            'name' => $entity['userName'],
                            'toAddress' => $entity['userEmail']
                        ]);
                  
                        $this->sendNotification([
                            'parent' => 'expenses',
                            'user_id' => $entity['user_id'],
                            'expense_id' => $entity['id'],
                            'description' => "Conta #{$entity['id']} enviada para análise."
                        ]);
                    }
    
                    $this->toLog("Atualizou a Conta {$entity['id']}");

                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'Conta atualizada.',
                        'type' => 'success',
                        'pos' => 'top-right',
                        'id' => $entity['id']
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'A Conta não foi atualizada.',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];
                }
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Conta não foi atualizada.',
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