<?php

namespace App\Controller;

use App\Model\ChangesHistoric;
use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\Files;
use App\Model\Schedules;
use App\Model\User;

class SchedulesController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $filesModel;
    private mixed $userModel;
    private mixed $changesHistoricModel;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Schedules();
        $this->filesModel = new Files();
        $this->userModel = new User();
        $this->changesHistoricModel = new ChangesHistoric();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'schedules'),
            'canCreate' => $this->getAcl('create', 'schedules'),
            'canUpdate' => $this->getAcl('update', 'schedules'),
            'canDelete' => $this->getAcl('delete', 'schedules'),
            'canCreateCustomer' => $this->getAcl('create', 'customers'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
            $data = $this->model->getAll();
            $this->view->data = $data;
            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canCreate']) {
            $users = $this->userModel->getAllActives();
            $this->view->users = $users;
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

            if (!empty($_POST['send_email_user']) && !empty($_POST['user_id'])) {
                $sendEmailUser = $_POST['send_email_user'];
                unset($_POST['send_email_user']);
            } else {
                $sendEmailUser = false;
            }

            if (empty($_POST['user_id'])) {
                unset($_POST['user_id']);
            }

            if (empty($_POST['customer_id'])) {
                unset($_POST['customer_id']);
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);

            if ($transaction) { 
                $newId = $transaction;

                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "schedules", $newId, 'schedule_id');
                }

                $scheduleStatus = "Pendente";
                $entity = $this->model->getOne($newId);
         
                if (!empty($entity['user_id']) && $sendEmailUser == 1) {
                    $this->sendNotification([
                        'parent' => 'schedules',
                        'user_id' => $entity['user_id'],
                        'schedule_id' => $entity['id'],
                        'description' => "Agendamento #{$entity['id']} $scheduleStatus."
                    ]);

                    $url = baseUrl;
                    $message = "<h3>Informações do agendamento emitido:</h3>";
                    $message .= "<p><b>{$_POST['title']}</b></p>
                                <p>Situação: <b>$scheduleStatus</b></p>";
                    $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                    $this->sendMail([
                        'title' => 'Novo Agendamento - ' . $scheduleStatus,
                        'message' => $message,
                        'name' => $entity['userName'],
                        'toAddress' => $entity['userEmail']
                    ]);
                }

                if (!empty($entity['customer_id']) && $sendEmailCustomer == 1) {
                    $this->sendNotification([
                        'parent' => 'schedules',
                        'customer_id' => $entity['customer_id'],
                        'schedule_id' => $entity['id'],
                        'description' => "Agendamento #{$entity['id']} $scheduleStatus."
                    ]);

                    $url = baseUrl . 'cliente';
                    $message = "<h3>Informações do agendamento emitido:</h3>";
                    $message .= "<p><b>{$_POST['title']}</b></p>
                                <p>Situação: <b>$scheduleStatus</b></p>";
                    $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                    $this->sendMail([
                        'title' => 'Novo Agendamento - ' . $scheduleStatus,
                        'message' => $message,
                        'name' => $entity['customerName'],
                        'toAddress' => $entity['customerEmail']
                    ]);
                }

                $this->saveHistoric([
                    'schedule_id' => $newId, 
                    'status' => 1,
                    'user_id' => $_SESSION['COD']
                ]);

                $this->toLog("Cadastrou o agendamento $newId");
                $data  = [
                    'title' => 'Sucesso!',
                    'msg'   => 'Agendamento cadastrado.',
                    'type'  => 'success',
                    'pos'   => 'top-right',
                    'id'  => $newId

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
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            $entity = $this->model->getOne($_POST['id']);
            $this->view->entity = $entity;

            $files = $this->filesModel->findAllBy('id, file, created_at', 'schedule_id', $_POST['id']);
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

            $_POST['updated_at'] = date('Y-m-d H:i:s');

            if (!empty($_POST['send_email_customer']) && !empty($_POST['customer_id'])) {
                $sendEmailCustomer = $_POST['send_email_customer'];
                unset($_POST['send_email_customer']);
            } else {
                $sendEmailCustomer = false;
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

            if (empty($_POST['customer_id'])) {
                unset($_POST['customer_id']);
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['id'], 'id');
          
            if ($transaction) {
                $entity = $this->model->getOne($_POST['id']);
         
                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "schedules", $_POST['id'], 'schedule_id');
                }

                $scheduleStatus = "Pendente";
                if ($_POST['status'] == 1) $scheduleStatus = "Pendente";
                if ($_POST['status'] == 2) $scheduleStatus = "Confirmado";
                if ($_POST['status'] == 3) $scheduleStatus = "Concluído";
                if ($_POST['status'] == 4) $scheduleStatus = "Cancelado";

                if (!empty($entity['user_id']) && $sendEmailUser == 1) {
                    $url = baseUrl;
                    $message = "<h3>Atualização do agendamento emitido:</h3>";
                    $message .= "<p><b>{$_POST['title']}</b></p>
                                <p>Situação: <b>$scheduleStatus</b></p>";
                    $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                    $this->sendMail([
                        'title' => 'Agendamento - ' . $scheduleStatus,
                        'message' => $message,
                        'name' => $entity['userName'],
                        'toAddress' => $entity['userEmail']
                    ]);

                    $this->sendNotification([
                        'parent' => 'schedules',
                        'user_id' => $entity['user_id'],
                        'schedule_id' => $entity['id'],
                        'description' => "Atualização no Agendamento #{$entity['id']}."
                    ]);
                }

                if (!empty($entity['customer_id']) && $sendEmailCustomer == 1) {
                    $this->sendNotification([
                        'parent' => 'schedules',
                        'customer_id' => $entity['customer_id'],
                        'schedule_id' => $entity['id'],
                        'description' => "Atualização no Agendamento #{$entity['id']}."
                    ]);

                    $url = baseUrl . 'cliente';
                    $message = "<h3>Atualização do agendamento emitido:</h3>";
                    $message .= "<p><b>{$_POST['title']}</b></p>
                                <p>Situação: <b>$scheduleStatus</b></p>";
                    $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                    $this->sendMail([
                        'title' => 'Agendamento - ' . $scheduleStatus,
                        'message' => $message,
                        'name' => $entity['customerName'],
                        'toAddress' => $entity['customerEmail']
                    ]);
                }
               
                if ($entity['status'] != $oldEntity['status']) {
                    $this->saveHistoric([
                        'schedule_id' => $entity['id'], 
                        'status' => $_POST['status'],
                        'user_id' => $_SESSION['COD']
                    ]);
                }

                $this->toLog("Atualizou o agendamento {$_POST['id']}");
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
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && $this->aclData['canView']) {
            $entity = $this->model->getOne($_POST['id'], null, false);
            if ($entity) {
                $this->view->entity = $entity;

                $historic = $this->changesHistoricModel->getAllByModule('schedule_id', $_POST['id']);
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
                    if (!empty($_POST['send_mail']) && $_POST['send_mail'] == 1) {
                        if (!empty($entity['user_id'])) {
                            $this->sendNotification([
                                'parent' => 'schedules',
                                'user_id' => $entity['user_id'],
                                'schedule_id' => $entity['id'],
                                'description' => "Agendamento #{$entity['id']} removido."
                            ]);

                            $url = baseUrl;
                            $message = "<h3>Agendamento #{$entity['id']} removido:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";
                            $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                            $this->sendMail([
                                'title' => "Agendamento #{$entity['id']} removido",
                                'message' => $message,
                                'name' => $entity['userName'],
                                'toAddress' => $entity['userEmail']
                            ]);
                        }

                        if (!empty($entity['customer_id'])) {
                            $this->sendNotification([
                                'parent' => 'schedules',
                                'customer_id' => $entity['customer_id'],
                                'schedule_id' => $entity['id'],
                                'description' => "Agendamento #{$entity['id']} removido."
                            ]);

                            $url = baseUrl . 'cliente';
                            $message = "<h3>Agendamento #{$entity['id']} removido:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";
                            $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                            $this->sendMail([
                                'title' => "Agendamento #{$entity['id']} removido",
                                'message' => $message,
                                'name' => $entity['customerName'],
                                'toAddress' => $entity['customerEmail']
                            ]);
                        }
                    }

                    $this->toLog("Removeu o agendamento {$_POST['id']}");
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
                if (!empty($_POST['send_notification'])) {
                    $entity = $this->model->getOne($_POST['schedule_id']);
                    
                    if (!empty($entity) && !empty($entity['customerEmail'])) {
                        $this->sendNotification([
                            'parent' => 'schedules',
                            'customer_id' => $entity['customer_id'],
                            'schedule_id' => $entity['id'],
                            'description' => "Arquivo removido no Agendamento #{$entity['id']}.",
                            'for_files' => 1
                        ]);

                        $url = baseUrl . 'cliente';
                        $message = "<p>Arquivo removido no Agendamento #{$entity['id']}, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => 'Informações do Agendamento #'.$entity['id'],
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }
                    
                    if (!empty($entity) && !empty($entity['userEmail'])) {
                        $this->sendNotification([
                            'parent' => 'schedules',
                            'user_id' => $entity['user_id'],
                            'schedule_id' => $entity['id'],
                            'description' => "Arquivo removido no Agendamento #{$entity['id']}.",
                            'for_files' => 1
                        ]);

                        $url = baseUrl;
                        $message = "<p>Arquivo removido no Agendamento #{$entity['id']},  
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => 'Informações do Agendamento #'.$entity['id'],
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

    public function changeStatusAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {

            $entity = $this->model->getOne($_POST['id']);
            $this->view->entity = $entity;

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
                if (!empty($_POST['schedule_notification'])) {
                    $sendEmailNotification = $_POST['schedule_notification'];
                    unset($_POST['schedule_notification']);
                } else {
                    $sendEmailNotification = false;
                }

                $updateData = [
                    'status' => $_POST['status'],
                    'description' => $_POST['description'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update($updateData, $entity['id'], 'id');

                if ($transaction) {
                    $scheduleStatus = "Pendente";
                    if ($_POST['status'] == 1) $scheduleStatus = "Pendente";
                    if ($_POST['status'] == 2) $scheduleStatus = "Confirmado";
                    if ($_POST['status'] == 3) $scheduleStatus = "Concluído";
                    if ($_POST['status'] == 4) $scheduleStatus = "Cancelado";
                    
                    if (!empty($entity) && !empty($entity['customerEmail']) && $sendEmailNotification) {
                        $this->sendNotification([
                            'parent' => 'schedules',
                            'customer_id' => $entity['customer_id'],
                            'schedule_id' => $entity['id'],
                            'description' => "Agendamento #{$entity['id']} $scheduleStatus."
                        ]);

                        $url = baseUrl . 'cliente';
                        $message = "<p>O Agendamento #{$entity['id']} acaba de ser atualizado, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => 'Informações do Agendamento #'.$entity['id'],
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }
                    
                    if (!empty($entity) && !empty($entity['userEmail']) && $sendEmailNotification) {
                        $this->sendNotification([
                            'parent' => 'schedules',
                            'user_id' => $entity['user_id'],
                            'schedule_id' => $entity['id'],
                            'description' => "Agendamento #{$entity['id']} $scheduleStatus."
                        ]);

                        $url = baseUrl;
                        $message = "<p>O Agendamento #{$entity['id']} acaba de ser atualizado pelo cliente, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => 'Informações do Agendamento #'.$entity['id'],
                            'message' => $message,
                            'name' => $entity['userName'],
                            'toAddress' => $entity['userEmail']
                        ]);
                    }

                    if (!empty($_POST['status']) && $_POST['status'] != $entity['status']) {
                        $this->saveHistoric([
                            'schedule_id' => $entity['id'], 
                            'status' => $_POST['status'],
                            'user_id' => $_SESSION['COD']
                        ]);
                    }

                    $this->toLog("Atualizou a situação do Agendamento {$entity['id']}");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'O Agendamento foi atualizado.',
                        'type' => 'success',
                        'pos' => 'top-right',
                        'id' => $entity['id']
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'O Agendamento não foi atualizado.',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];
                }
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Agendamento é inválido.',
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
            $files = $this->filesModel->findAllBy('id, file, created_at', 'schedule_id', $_POST['id']);
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
                $this->filesModel->uploadFiles($_FILES, "schedules", $_POST['id'], 'schedule_id');

                $entity = $this->model->getOne($_POST['id']);
                if (!empty($_POST['schedule_files_notification'])) {
                    if (!empty($entity['customer_id'])) {
                        $this->sendNotification([
                            'parent' => 'schedules',
                            'customer_id' => $entity['customer_id'],
                            'schedule_id' => $entity['id'],
                            'description' => "Novos arquivos no Agendamento #{$entity['id']}.",
                            'for_files' => 1
                        ]);

                        $url = baseUrl . 'cliente';
                        $message = "<p>O Agendamento #{$entity['id']} acaba de ser atualizado, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => "Novos arquivos no Agendamento #{$entity['id']}.",
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }

                    if (!empty($entity['user_id'])) {
                        $this->sendNotification([
                            'parent' => 'schedules',
                            'user_id' => $entity['user_id'],
                            'schedule_id' => $entity['id'],
                            'description' => "Novos arquivos no Agendamento #{$entity['id']}.",
                            'for_files' => 1
                        ]);

                        $url = baseUrl;
                        $message = "<p>O Agendamento #{$entity['id']} acaba de ser atualizado pelo cliente, 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                        $this->sendMail([
                            'title' => "Novos arquivos no Agendamento #{$entity['id']}.",
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
                                'parent' => 'schedules',
                                'user_id' => $entity['user_id'],
                                'schedule_id' => $entity['id'],
                                'description' => "Agendamento #{$entity['id']} restaurado."
                            ]);

                            $url = baseUrl;
                            $message = "<h3>Agendamento #{$entity['id']} restaurado:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";
                            $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                            $this->sendMail([
                                'title' => "Agendamento #{$entity['id']} restaurado",
                                'message' => $message,
                                'name' => $entity['userName'],
                                'toAddress' => $entity['userEmail']
                            ]);
                        }

                        if (!empty($entity['customer_id'])) {
                            $this->sendNotification([
                                'parent' => 'schedules',
                                'customer_id' => $entity['customer_id'],
                                'schedule_id' => $entity['id'],
                                'description' => "Agendamento #{$entity['id']} restaurado."
                            ]);

                            $url = baseUrl . 'cliente';
                            $message = "<h3>Agendamento #{$entity['id']} restaurado:</h3>";
                            $message .= "<p><b>{$entity['title']}</b></p>";
                            $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                            $this->sendMail([
                                'title' => "Agendamento #{$entity['id']} restaurado",
                                'message' => $message,
                                'name' => $entity['customerName'],
                                'toAddress' => $entity['customerEmail']
                            ]);
                        }
                    }

                    $this->toLog("Restaurou o agendamento {$_POST['id']}");
                    $data  = [
                        'title' => 'Sucesso!',
                        'msg'   => 'Agendamento restaurado.',
                        'type'  => 'success',
                        'pos'   => 'top-right'
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!',
                        'msg' => 'O Agendamento não foi restaurado.',
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