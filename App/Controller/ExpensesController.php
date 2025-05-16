<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\ChangesHistoric;
use App\Model\Expenses;
use App\Model\Files;
use App\Model\Financial;
use App\Model\PaymentTypes;

class ExpensesController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $paymentTypesModel;
    private mixed $filesModel;
    private mixed $financialModel;
    private mixed $changesHistoricModel;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Expenses();
        $this->paymentTypesModel = new PaymentTypes();
        $this->financialModel = new Financial();
        $this->filesModel = new Files();
        $this->changesHistoricModel = new ChangesHistoric();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'expenses'),
            'canCreate' => $this->getAcl('create', 'expenses'),
            'canUpdate' => $this->getAcl('update', 'expenses'),
            'canDelete' => $this->getAcl('delete', 'expenses'),
            'canCreateCustomer' => $this->getAcl('create', 'customers'),
            'canCreateProvider' => $this->getAcl('create', 'providers'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
            if (!empty($_GET['m'])) {
                $month = $_GET['m'];
            } else {
                $month = date('Y-m');
            }

            $this->view->month = self::formatMonth($month);
            $this->view->month_not_formatted = $month;

            $data = $this->model->getAllByMonth('0', $month);
            $this->view->data = $data;

            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canCreate']) {
            $paymentTypes = $this->paymentTypesModel->getAllActives();
            $this->view->paymentTypes = $paymentTypes;

            $this->render('create', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createProcessAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canCreate']) {
            unset($_POST['target']);

            if (!empty($_POST['send_email_customer']) && !empty($_POST['customer_id'])) {
                $sendEmailCustomer = $_POST['send_email_customer'];
                unset($_POST['send_email_customer']);
            } else {
                $sendEmailCustomer = false;
            }

            if (!empty($_POST['send_email_provider']) && !empty($_POST['provider_id'])) {
                $sendEmailProvider = $_POST['send_email_provider'];
                unset($_POST['send_email_provider']);
            } else {
                $sendEmailProvider = false;
            }

            if (!empty($_POST['send_email_user']) && !empty($_POST['user_id'])) {
                $sendEmailUser = $_POST['send_email_user'];
                unset($_POST['send_email_user']);
            } else {
                $sendEmailUser = false;
            }

            if (empty($_POST['user_id'])) {
                unset($_POST['user_id']);
            }

            if (empty($_POST['provider_id'])) {
                unset($_POST['provider_id']);
            }

            if (empty($_POST['customer_id'])) {
                unset($_POST['customer_id']);
            }

            if (empty($_POST['payment_type_id'])) {
                unset($_POST['payment_type_id']);
            }

            $_POST['amount'] = $this->moneyToDb($_POST['amount']);

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);

            if ($transaction) {
                $newId = $transaction;
                $expenseStatus = "Pendente";

                if ($_POST['expense_type'] == '1') {
                    $title = "Conta a Pagar";
                    $type = "expenses-down";
                    $movType = '2';
                }

                if ($_POST['expense_type'] == '2') {
                    $title = "Conta a Receber";
                    $type = "expenses-up";
                    $movType = '1';
                }

                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "expenses", $newId, 'expense_id');
                }
                
                $entity = $this->model->getOne($newId);
        
                if (!empty($entity['customer_id']) && $sendEmailCustomer == 1) {
                    $this->sendNotification([
                        'parent' => 'expenses',
                        'customer_id' => $entity['customer_id'],
                        'expense_id' => $entity['id'],
                        'description' => "$title #{$entity['id']} $expenseStatus."
                    ]);

                    $url = baseUrl . 'cliente';
                    $message = "<h3>Informações da conta emitida:</h3>";
                    $message .= "<p>Uma conta para pagamento acaba de ser disponibilizada, 
                        <a href='$url'>acesse a plataforma</a> para conferir.</p>";

                    $this->sendMail([
                        'title' => $title . ' #' . $entity['id'],
                        'message' => $message,
                        'name' => $entity['customerName'],
                        'toAddress' => $entity['customerEmail']
                    ]);
                }

                if (!empty($entity['provider_id']) && $sendEmailProvider == 1) {
                    $message = "<h3>Informações da conta emitida:</h3>";
                    $this->sendMail([
                        'title' => $title . ' #' . $entity['id'],
                        'message' => $message,
                        'name' => $entity['providerName'],
                        'toAddress' => $entity['providerEmail']
                    ]);
                }

                if (!empty($entity['user_id']) && $sendEmailUser == 1) {
                    $this->sendNotification([
                        'parent' => 'expenses',
                        'user_id' => $entity['user_id'],
                        'expense_id' => $entity['id'],
                        'description' => "$title #{$entity['id']} $expenseStatus."
                    ]);

                    $message = "<h3>Informações da conta emitida:</h3>";
                    $this->sendMail([
                        'title' => $title . ' #' . $entity['id'],
                        'message' => $message,
                        'name' => $entity['userName'],
                        'toAddress' => $entity['userEmail']
                    ]);
                }

                $this->saveHistoric([
                    'expense_id' => $newId, 
                    'status' => 1,
                    'user_id' => $_SESSION['COD']
                ]);

                $this->toLog("Cadastrou a Conta $newId");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Conta cadastrada.',
                    'type' => 'success',
                    'pos' => 'top-right',
                    'id' => $newId
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Conta não foi cadastrada.',
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

    public function updateAction(): void
    {
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            $entity = $this->model->getOne($_POST['id']);
            $this->view->entity = $entity;

            $paymentTypes = $this->paymentTypesModel->getAllActives();
            $this->view->paymentTypes = $paymentTypes;

            $files = $this->filesModel->findAllBy('id, file, created_at', 'expense_id', $_POST['id']);
            $this->view->files = $files;

            $this->render('update', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            unset($_POST['target']);
            $oldEntity = $this->model->getOne($_POST['id']);

            if ($_POST['status'] == 2 || $_POST['status'] == 4) {
                if ($_POST['amount'] <= 0) {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'A Conta deve possuir valor.',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];

                    echo json_encode($data);
                    return true;
                }
            }

            if (!empty($_POST['send_email_customer']) && !empty($_POST['customer_id'])) {
                $sendEmailCustomer = $_POST['send_email_customer'];
                unset($_POST['send_email_customer']);
            } else {
                $sendEmailCustomer = false;
            }

            if (!empty($_POST['send_email_provider']) && !empty($_POST['user_id'])) {
                $sendEmailProvider = $_POST['send_email_provider'];
                unset($_POST['send_email_provider']);
            } else {
                $sendEmailProvider = false;
            }

            if (!empty($_POST['send_email_user']) && !empty($_POST['user_id'])) {
                $sendEmailUser = $_POST['send_email_user'];
                unset($_POST['send_email_user']);
            } else {
                $sendEmailUser = false;
            }

            if (empty($_POST['user_id'])) {
                unset($_POST['user_id']);
            }

            if (empty($_POST['provider_id'])) {
                unset($_POST['provider_id']);
            }

            if (empty($_POST['customer_id'])) {
                unset($_POST['customer_id']);
            }

            if (empty($_POST['payment_type_id'])) {
                unset($_POST['payment_type_id']);
            }

            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $_POST['amount'] = $this->moneyToDb($_POST['amount']);
  
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['id'], 'id');
            
            if ($transaction) {
                $expenseStatus = "Pendente";
                if ($_POST['status'] == 1) $expenseStatus = "Pendente";
                if ($_POST['status'] == 2) $expenseStatus = "Finalizada";
                if ($_POST['status'] == 3) $expenseStatus = "Cancelada";
                if ($_POST['status'] == 4) $expenseStatus = "Em Análise";

                if ($_POST['expense_type'] == '1') {
                    $title = "Conta a Pagar";
                    $type = "expenses-down";
                    $movType = '2';
                }

                if ($_POST['expense_type'] == '2') {
                    $title = "Conta a Receber";
                    $type = "expenses-up";
                    $movType = '1';
                }

                if ($_POST['status'] == '2' && !$this->financialModel->dataInFinancial($_POST['id'], 'expense_id')) {
                    $this->financialLog([
                        'expense_id' => $_POST['id'],
                        'parent_type' => $type,
                        'title' => $title . ' - ' . $_POST['title'],
                        'description' => $_POST['description'],
                        'amount' => $_POST['amount'],
                        'mov_type' => $movType,
                        'status' => '2'
                    ]);
                }
                
                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "expenses", $_POST['id'], 'expense_id');
                }

                $entity = $this->model->getOne($_POST['id']);
                
                if (!empty($entity['customer_id']) && $sendEmailCustomer == 1) {
                    $this->sendNotification([
                        'parent' => 'expenses',
                        'customer_id' => $entity['customer_id'],
                        'expense_id' => $entity['id'],
                        'description' => "Atualização da Conta #{$entity['id']}."
                    ]);

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

                if (!empty($entity['provider_id']) && $sendEmailProvider == 1) {
                    $message = "<h3>Informações da conta emitida:</h3>";
                    $this->sendMail([
                        'title' => $title . ' #' . $entity['id'],
                        'message' => $message,
                        'name' => $entity['providerName'],
                        'toAddress' => $entity['providerEmail']
                    ]);
                }

                if (!empty($entity['user_id']) && $sendEmailUser == 1) {
                    $this->sendNotification([
                        'parent' => 'expenses',
                        'user_id' => $entity['user_id'],
                        'expense_id' => $entity['id'],
                        'description' => "Atualização da Conta #{$entity['id']}."
                    ]);

                    $message = "<h3>Informações da conta emitida:</h3>";
                    $this->sendMail([
                        'title' => $title . ' #' . $entity['id'],
                        'message' => $message,
                        'name' => $entity['userName'],
                        'toAddress' => $entity['userEmail']
                    ]);
                }
               
                if ($entity['status'] != $oldEntity['status']) {
                    $this->saveHistoric([
                        'expense_id' => $entity['id'], 
                        'status' => $_POST['status'],
                        'user_id' => $_SESSION['COD']
                    ]);
                }
                
                $this->toLog("Atualizou a Conta {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Conta atualizada.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
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

    public function readAction(): void
    {
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && $this->aclData['canView']) {
            $id = $_POST['id'];
            $entity = $this->model->getOne($id, null, false);
            if ($entity) {
                $this->view->entity = $entity;

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

    public function deleteAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canDelete']) {
            
            $entity = $this->model->getOne($_POST['id']);
            if ($entity) {
                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update([
                    'deleted' => '1',
                    'updated_at' => date('Y-m-d H:i:s')
                ], $_POST['id'], 'id');

                if ($transaction) {
                    $financialEntity = $this->financialModel->getByParent($_POST['id'], 'expense_id');
                    if ($financialEntity) {
                        $crud->setTable($this->financialModel->getTable());
                        $crud->update([
                            'deleted' => '1',
                            'updated_at' => date('Y-m-d H:i:s')
                        ], $financialEntity['id'], 'id');
                    }
                    
                    if (!empty($_POST['send_mail']) && $_POST['send_mail'] == 1) {
                        if (!empty($entity['user_id'])) {
                            $this->sendNotification([
                                'parent' => 'expenses',
                                'user_id' => $entity['user_id'],
                                'expense_id' => $entity['id'],
                                'description' => "Conta #{$entity['id']} removida."
                            ]);

                            $url = baseUrl;
                            $message = "<h3>Conta #{$entity['id']} removida:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";
                            $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";
    
                            $this->sendMail([
                                'title' => "Conta #{$entity['id']} removida",
                                'message' => $message,
                                'name' => $entity['userName'],
                                'toAddress' => $entity['userEmail']
                            ]);
                        }

                        if (!empty($entity['customer_id'])) {
                            $this->sendNotification([
                                'parent' => 'expenses',
                                'customer_id' => $entity['customer_id'],
                                'expense_id' => $entity['id'],
                                'description' => "Pagamento #{$entity['id']} removido."
                            ]);

                            $url = baseUrl . 'cliente';
                            $message = "<h3>Pagamento #{$entity['id']} removido:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";
                            $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                            $this->sendMail([
                                'title' => "Pagamento #{$entity['id']} removido",
                                'message' => $message,
                                'name' => $entity['customerName'],
                                'toAddress' => $entity['customerEmail']
                            ]);
                        }
                
                        if (!empty($entity['provider_id'])) {
                            $message = "<h3>Pagamento #{$entity['id']} removido:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";

                            $this->sendMail([
                                'title' => "Pagamento #{$entity['id']} removido",
                                'message' => $message,
                                'name' => $entity['providerName'],
                                'toAddress' => $entity['providerEmail']
                            ]);
                        }
                    }


                    $this->toLog("Removeu a Conta {$_POST['id']}");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'Conta removida.',
                        'type' => 'success',
                        'pos' => 'top-right'
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'A Conta não foi removida.',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];
                }

                echo json_encode($data);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function deleteFileAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
 
            $crud = new Crud();
            $crud->setTable($this->filesModel->getTable());
            $deleted = $crud->update(['deleted' => '1'], $_POST['id'], 'id');

            if ($deleted) {

                if (!empty($_POST['send_notification']) && $_POST['send_notification'] == 1) {
                    $entity = $this->model->getOne($_POST['expense_id']);
                    if (!empty($entity['customer_id'])) {
                        $this->sendNotification([
                            'parent' => 'expenses',
                            'customer_id' => $entity['customer_id'],
                            'expense_id' => $entity['id'],
                            'description' => "Arquivo removido na Conta #{$entity['id']}.",
                            'for_files' => 1
                        ]);
                        
                        $url = baseUrl . 'cliente';
                        $message = "<h3>Conta #{$entity['id']} removida:</h3>";
                        $message .= "<p><b>{$entity['title']}</b></p>";
                        $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                        $this->sendMail([
                            'title' => "Arquivo removido na Conta #{$entity['id']}.",
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }
    
                    if (!empty($entity['user_id'])) {
                        $this->sendNotification([
                            'parent' => 'expenses',
                            'user_id' => $entity['user_id'],
                            'expense_id' => $entity['id'],
                            'description' => "Arquivo removido na Conta #{$entity['id']}.",
                            'for_files' => 1
                        ]);

                        $url = baseUrl;
                        $message = "<h3>Conta #{$entity['id']} removida:</h3>";
                        $message .= "<p><b>{$entity['title']}</b></p>";
                        $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                        $this->sendMail([
                            'title' => "Arquivo removido na Conta #{$entity['id']}.",
                            'message' => $message,
                            'name' => $entity['userName'],
                            'toAddress' => $entity['userEmail']
                        ]);
                    }
                }

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function createCustomerAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canCreateCustomer']) {
            $this->render('create-customer', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createProviderAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canCreateProvider']) {
            $this->render('create-provider', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function changeStatusAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {

            $entity = $this->model->getOne($_POST['id']);
            $this->view->entity = $entity;

            $paymentTypes = $this->paymentTypesModel->getAllActives();
            $this->view->paymentTypes = $paymentTypes;

            $this->render('change-status', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }
    
    public function processStatusAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            $entity = $this->model->getOne($_POST['id']);

            if ($entity) {
                if (!empty($_POST['send_email_status'])) {
                    $sendEmailNotification = $_POST['send_email_status'];
                    unset($_POST['send_email_status']);
                } else {
                    $sendEmailNotification = false;
                }
    
                if ($_POST['status'] == 2 && empty($_POST['payment_type_id'])) {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'Informe a Forma de Pagamento',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];
    
                    echo json_encode($data);
                    return false;
                }

                $updateData = [
                    'status' => $_POST['status'],
                    'description' => $_POST['description'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if (!empty($_POST['payment_type_id'])) {
                    $updateData['payment_type_id'] = $_POST['payment_type_id'];
                }

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update($updateData, $entity['id'], 'id');

                if ($transaction) {
                    $expenseStatus = "Pendente";
                    if ($_POST['status'] == 1) $expenseStatus = "Pendente";
                    if ($_POST['status'] == 2) $expenseStatus = "Finalizada";
                    if ($_POST['status'] == 3) $expenseStatus = "Cancelada";
                    if ($_POST['status'] == 4) $expenseStatus = "Em Análise";

                    if ($entity['expense_type'] == '1') {
                        $title = "Conta a Pagar";
                        $type = "expenses-down";
                        $movType = '2';
                    }
    
                    if ($entity['expense_type'] == '2') {
                        $title = "Conta a Receber";
                        $type = "expenses-up";
                        $movType = '1';
                    }
    
                    if ($_POST['status'] == '2' && !$this->financialModel->dataInFinancial($_POST['id'], 'expense_id')) {
                        $this->financialLog([
                            'expense_id' => $_POST['id'],
                            'parent_type' => $type,
                            'title' => $title . ' - ' . $entity['title'],
                            'description' => $_POST['description'],
                            'amount' => $entity['amount'],
                            'mov_type' => $movType,
                            'status' => '2'
                        ]);
                    }

                    if (!empty($entity) && !empty($entity['userEmail']) && $sendEmailNotification) {
                        $this->sendNotification([
                            'parent' => 'expenses',
                            'user_id' => $entity['user_id'],
                            'expense_id' => $entity['id'],
                            'description' => "$title #{$entity['id']} $expenseStatus."
                        ]);

                        $url = baseUrl;
                        $message = "<p>A Conta #{$entity['id']} acaba de ser atualizado pelo cliente, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => 'Informações da Conta #'.$entity['id'],
                            'message' => $message,
                            'name' => $entity['userName'],
                            'toAddress' => $entity['userEmail']
                        ]);
                    }

                    if (!empty($entity) && !empty($entity['customerEmail']) && $sendEmailNotification) {
                        $this->sendNotification([
                            'parent' => 'expenses',
                            'customer_id' => $entity['customer_id'],
                            'expense_id' => $entity['id'],
                            'description' => "$title #{$entity['id']} $expenseStatus."
                        ]);

                        $url = baseUrl . 'cliente/';
                        $message = "<p>A Conta #{$entity['id']} acaba de ser atualizado pelo cliente, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => 'Informações da Conta #'.$entity['id'],
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }

                    if (!empty($entity) && !empty($entity['providerEmail']) && $sendEmailNotification) {
                        $url = baseUrl;
                        $message = "<p>A Conta #{$entity['id']} acaba de ser atualizado pelo cliente.</p>";
                        
                        $this->sendMail([
                            'title' => 'Informações da Conta #'.$entity['id'],
                            'message' => $message,
                            'name' => $entity['providerName'],
                            'toAddress' => $entity['providerEmail']
                        ]);
                    }

                    if (!empty($_POST['status']) && $_POST['status'] != $entity['status']) {
                        $this->saveHistoric([
                            'expense_id' => $entity['id'], 
                            'status' => $_POST['status'],
                            'user_id' => $_SESSION['COD']
                        ]);
                    }

                    $this->toLog("Atualizou a situação da Conta {$entity['id']}");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'A Conta foi atualizada.',
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
                    'msg' => 'A Conta é inválida.',
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
    
    public function filesAction(): void
    {
        if (!empty($_POST['id']) && $this->validatePostParams($_POST)) {
            $files = $this->filesModel->findAllBy('id, file, created_at', 'expense_id', $_POST['id']);
            $this->view->files = $files;
            $this->render('files', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function uploadFilesAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            
            $this->render('upload-files', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function uploadProcessAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            
            if (!empty($_FILES)) {
                $this->filesModel->uploadFiles($_FILES, "expenses", $_POST['id'], 'expense_id');
                
                if (!empty($_POST['expense_files_notification']) && $_POST['expense_files_notification'] == 1) {
                    $entity = $this->model->getOne($_POST['id']);
                    if (!empty($entity['customer_id'])) {

                        $this->sendNotification([
                            'parent' => 'expenses',
                            'customer_id' => $entity['customer_id'],
                            'expense_id' => $entity['id'],
                            'description' => "Arquivos enviados na Conta #{$entity['id']}.",
                            'for_files' => 1
                        ]);

                        $url = baseUrl . 'cliente/';
                        $message = "<p>Arquivos enviados na Conta #{$entity['id']}, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => "Arquivos enviados na Conta #{$entity['id']}.",
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }

                    if (!empty($entity['user_id'])) {
                        $this->sendNotification([
                            'parent' => 'expenses',
                            'user_id' => $entity['user_id'],
                            'expense_id' => $entity['id'],
                            'description' => "Arquivos enviados na Conta #{$entity['id']}.",
                            'for_files' => 1
                        ]);
                  
                        $url = baseUrl;
                        $message = "<p>Arquivos enviados na Conta #{$entity['id']}, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => "Arquivos enviados na Conta #{$entity['id']}.",
                            'message' => $message,
                            'name' => $entity['userName'],
                            'toAddress' => $entity['userEmail']
                        ]);
                    }
                }

                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Os arquivos foram enviados.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Os arquivos não foram enviados.',
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

    public function restoreProcessAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            
            $entity = $this->model->getOne($_POST['id'], null, false);
            if ($entity) {

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update([
                    'deleted' => '0',
                    'status' => '1',
                    'updated_at' => date('Y-m-d H:i:s')
                ], $_POST['id'], 'id');

                if ($transaction) {
                    if (!empty($_POST['send_mail']) && $_POST['send_mail'] == 1) {
                        if (!empty($entity['user_id'])) {
                            $this->sendNotification([
                                'parent' => 'expenses',
                                'user_id' => $entity['user_id'],
                                'expense_id' => $entity['id'],
                                'description' => "Conta #{$entity['id']} restaurada."
                            ]);

                            $url = baseUrl;
                            $message = "<h3>Conta #{$entity['id']} restaurada:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";
                            $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";
    
                            $this->sendMail([
                                'title' => "Conta #{$entity['id']} restaurada",
                                'message' => $message,
                                'name' => $entity['userName'],
                                'toAddress' => $entity['userEmail']
                            ]);
                        }

                        if (!empty($entity['customer_id'])) {
                            $this->sendNotification([
                                'parent' => 'expenses',
                                'customer_id' => $entity['customer_id'],
                                'expense_id' => $entity['id'],
                                'description' => "Pagamento #{$entity['id']} restaurado."
                            ]);

                            $url = baseUrl . 'cliente';
                            $message = "<h3>Pagamento #{$entity['id']} restaurado:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";
                            $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                            $this->sendMail([
                                'title' => "Pagamento #{$entity['id']} restaurado",
                                'message' => $message,
                                'name' => $entity['customerName'],
                                'toAddress' => $entity['customerEmail']
                            ]);
                        }
                
                        if (!empty($entity['provider_id'])) {
                            $message = "<h3>Pagamento #{$entity['id']} restaurado:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";

                            $this->sendMail([
                                'title' => "Pagamento #{$entity['id']} restaurado",
                                'message' => $message,
                                'name' => $entity['providerName'],
                                'toAddress' => $entity['providerEmail']
                            ]);
                        }
                    }

                    $this->toLog("Restaurou a Conta {$_POST['id']}");
                    $data  = [
                        'title' => 'Sucesso!',
                        'msg'   => 'Conta restaurada.',
                        'type'  => 'success',
                        'pos'   => 'top-right'
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!',
                        'msg' => 'A Conta não foi restaurada.',
                        'type' => 'error',
                        'pos'   => 'top-center'
                    ];
                }

                echo json_encode($data);
                return true;
            } else {
                return false;
            }
        } else {
            return false;   
        }
    }
}