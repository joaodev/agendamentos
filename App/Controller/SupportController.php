<?php

namespace App\Controller;

use App\Interfaces\CrudInterface;
use App\Model\ChangesHistoric;
use App\Model\Files;
use App\Model\Support;
use App\Model\SupportMessages;
use App\Model\SupportResponsibles;
use App\Model\User;
use Core\Controller\ActionController;
use Core\Db\Crud;

class SupportController extends ActionController implements CrudInterface
{

    private mixed $model;
    private mixed $filesModel;
    private mixed $userModel;
    private mixed $supportMessagesModel;
    private mixed $supportResponsiblesModel;
    private mixed $changesHistoricModel;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();    
        
        $this->model = new Support();
        $this->filesModel = new Files();
        $this->userModel = new User();
        $this->supportMessagesModel = new SupportMessages();
        $this->supportResponsiblesModel = new SupportResponsibles();
        $this->changesHistoricModel = new ChangesHistoric();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'support'),
            'canCreate' => $this->getAcl('create', 'support'),
            'canUpdate' => $this->getAcl('update', 'support'),
            'canDelete' => $this->getAcl('delete', 'support'),
            'canViewMessages' => $this->getAcl('view', 'support-messages'),
            'canCreateMessages' => $this->getAcl('create', 'support-messages'),
            'canDeleteMessages' => $this->getAcl('delete', 'support-messages'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST)) {
            if ($this->aclData['canView']) {
                $data = $this->model->getAll();
            } else {
                $data = $this->model->getAllByUser($_SESSION['COD']);
            }

            $this->view->data = $data;
            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createAction(): void
    {
        if ($this->validatePostParams($_POST)) {
            $this->render('create', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createProcessAction(): bool
    {
        if ($this->validatePostParams($_POST)) {
            unset($_POST['target']);
            
            if (!empty($_POST['send_email_customer']) && !empty($_POST['customer_id'])) {
                $sendEmailCustomer = $_POST['send_email_customer'];
                unset($_POST['send_email_customer']);
            } else {
                $sendEmailCustomer = false;
            }

            if (empty($_POST['customer_id'])) {
                unset($_POST['customer_id']);
            }

            $_POST['user_id'] = $_SESSION['COD'];   

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);

            if ($transaction) {
                $newId = $transaction;
                $supportStatus = "Pendente";

                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "support", $newId, 'support_id');
                }

                $entity = $this->model->getOne($newId);

                if (!empty($entity['customer_id']) && $sendEmailCustomer == 1) {
                    $this->sendNotification([
                        'parent' => 'support',
                        'customer_id' => $entity['customer_id'],
                        'support_id' => $entity['id'],
                        'description' => "Chamado #{$entity['id']} $supportStatus."
                    ]);

                    $url = baseUrl . 'cliente';
                    $message = "<p>Chamado #{$entity['id']} em aberto; 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                    $this->sendMail([
                        'title' => 'Chamado #'.$entity['id']. ' - Novas Informações:',
                        'message' => $message,
                        'name' => $entity['customerName'],
                        'toAddress' => $entity['customerEmail']
                    ]);
                }

                $admins = $this->userModel->getAllAdminActives();
                foreach ($admins as $admin) {
                    if ($_SESSION['COD'] != $admin['id']) {
                        $this->sendNotification([
                            'parent' => 'support',
                            'user_id' => $admin['id'],
                            'support_id' => $entity['id'],
                            'description' => "Chamado #{$entity['id']} $supportStatus."
                        ]);
                    }
                }

                $this->saveHistoric([
                    'support_id' => $newId, 
                    'status' => 1,
                    'user_id' => $_SESSION['COD']
                ]);

                $this->toLog("Abriu o Chamado $newId");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Chamado aberto.',
                    'type' => 'success',
                    'pos' => 'top-right',
                    'id' => $newId
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Chamado não foi aberto.',
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
        if ($this->validatePostParams($_POST)) {
            $entity = $this->model->getOne($_POST['id'], null, false);
            if ($entity) {
                $this->view->entity = $entity;

                $historic = $this->changesHistoricModel->getAllByModule('support_id', $_POST['id']);
                $this->view->historic = $historic;

                $responsibles = $this->supportResponsiblesModel->getAllBySupport($_POST['id']);
                $this->view->responsibles = $responsibles;

                $this->render('read', false);
            }
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function updateAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            $entity = $this->model->getOne($_POST['id']);
            $this->view->entity = $entity;

            $files = $this->filesModel->findAllBy('id, file, created_at', 'support_id', $entity['id']);
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
        
            if (empty($_POST['customer_id'])) {
                unset($_POST['customer_id']);
            }

            $_POST['updated_at'] = date('Y-m-d H:i:s');

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['id'], 'id');

            if ($transaction) {
                $supportStatus = "Pendente";
                if ($_POST['status'] == 1) $supportStatus = "Pendente";
                if ($_POST['status'] == 2) $supportStatus = "Em Atendimento";
                if ($_POST['status'] == 3) $supportStatus = "Finalizado";
                if ($_POST['status'] == 4) $supportStatus = "Fechado";

                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "support", $_POST['id'], 'support_id');
                }

                $entity = $this->model->getOne($_POST['id']);
                if (!empty($_POST['send_email_user']) && $_POST['send_email_user'] == 1) {
                    if ($entity['customer_id']) {
                        $this->sendNotification([
                            'parent' => 'support',
                            'customer_id' => $entity['customer_id'],
                            'support_id' => $entity['id'],
                            'description' => "Chamado #{$entity['id']} $supportStatus."
                        ]);

                        $url = baseUrl . 'cliente';
                        $message = "<p>Chamado #{$entity['id']}, informações atualizadas; 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                            
                        $this->sendMail([
                            'title' => 'Chamado #'.$entity['id']. ' - Novas Informações:',
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }

                    $responsibles = $this->supportResponsiblesModel->getAllBySupport($entity['id']);
                    foreach ($responsibles as $responsible) {
                        $this->sendNotification([
                            'parent' => 'support',
                            'user_id' => $responsible['user_id'],
                            'support_id' => $entity['id'],
                            'description' => "Chamado #{$entity['id']} $supportStatus."
                        ]);
                        
                        $url = baseUrl;
                        $message = "<p>Chamado #{$entity['id']}, informações atualizadas; 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                            
                        $this->sendMail([
                            'title' => 'Chamado #'.$entity['id']. ' - Novas Informações:',
                            'message' => $message,
                            'name' => $responsible['userName'],
                            'toAddress' => $responsible['userEmail']
                        ]);
                    }
                }

                if ($entity['status'] != $oldEntity['status']) {
                    $this->saveHistoric([
                        'support_id' => $entity['id'], 
                        'status' => $_POST['status'],
                        'user_id' => $_SESSION['COD']
                    ]);
                }

                $this->toLog("Atualizou o Chamado {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Chamado atualizado.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Chamado não foi atualizado.',
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

    public function deleteAction(): bool
    {   
        if ($this->validatePostParams($_POST) && $this->aclData['canDelete']) {
            $entity = $this->model->getOne($_POST['id']);
    
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'deleted' => '1',
                'updated_at' => date('Y-m-d H:i:s')
            ], $_POST['id'], 'id');

            if ($transaction) {
                if (!empty($_POST['send_mail']) && $_POST['send_mail'] == 1) {
                    if ($entity['customer_id']) {
                        $this->sendNotification([
                            'parent' => 'support',
                            'customer_id' => $entity['customer_id'],
                            'support_id' => $entity['id'],
                            'description' => 'Chamado #'.$entity['id']. ' removido'
                        ]);

                        $url = baseUrl . 'cliente';
                        $message = "<p>Chamado #{$entity['id']} removido com sucesso; 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                            
                        $this->sendMail([
                            'title' => 'Chamado #'.$entity['id']. ' removido',
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }

                    $responsibles = $this->supportResponsiblesModel->getAllBySupport($entity['id']);
                    foreach ($responsibles as $responsible) {
                        $this->sendNotification([
                            'parent' => 'support',
                            'user_id' => $responsible['user_id'],
                            'support_id' => $entity['id'],
                            'description' => 'Chamado #'.$entity['id']. ' removido'
                        ]);
                        
                        $url = baseUrl;
                        $message = "<p>Chamado #{$entity['id']}, informações atualizadas; 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                            
                        $this->sendMail([
                            'title' => 'Chamado #'.$entity['id']. ' removido',
                            'message' => $message,
                            'name' => $responsible['userName'],
                            'toAddress' => $responsible['userEmail']
                        ]);
                    }
                }

                $this->toLog("Removeu o Chamado {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Chamado removido.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Chamado não foi removido.',
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

    public function messagesAction(): void
    {
        if ($this->validatePostParams($_POST)) {
            $messages = $this->supportMessagesModel->getAllByCall($_POST['id']);
            $this->view->messages = $messages;
            $this->render('messages', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function messageProcessAction(): bool
    {
        if ($this->validatePostParams($_POST)) {
            $postData = [
                'parent_id' => $_POST['id'],
                'user_id' => $_SESSION['COD'],
                'description' => $_POST['message']
            ];
            
            $crud = new Crud();
            $crud->setTable($this->supportMessagesModel->getTable());
            $transaction = $crud->create($postData);
            
            if ($transaction) {
                $entity = $this->model->getOne($_POST['id']);

                if (!empty($entity['customer_id'])) {
                    $this->notifyMessage([
                        'parent' => 'support',
                        'customer_id' => $entity['customer_id'],
                        'support_id' => $entity['id'],
                        'description' => "Mensagem no Chamado #{$entity['id']}"
                    ]);
                }

                $responsibles = $this->supportResponsiblesModel->getAllBySupport($entity['id']);
                if (!empty($responsibles)) {
                    foreach ($responsibles as $responsible) {
                        $this->notifyMessage([
                            'parent' => 'support',
                            'user_id' => $responsible['user_id'],
                            'support_id' => $entity['id'],
                            'description' => "Mensagem no Chamado #{$entity['id']}."
                        ]);
                    }
                } else {
                    $admins = $this->userModel->getAllAdminActives();
                    foreach ($admins as $admin) {
                        if ($admin['id'] != $_SESSION['COD']) {
                            $this->notifyMessage([
                                'parent' => 'support',
                                'user_id' => $admin['id'],
                                'support_id' => $entity['id'],
                                'description' => "Mensagem no Chamado #{$entity['id']}."
                            ]);
                        }
                    }
                }

                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Mensagem enviada.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'A Mensagem não foi enviada.',
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
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
         
            $crud = new Crud();
            $crud->setTable($this->filesModel->getTable());
            $deleted = $crud->update(['deleted' => '1'], $_POST['id'], 'id');

            if ($deleted) {
                if (!empty($_POST['send_mail']) && $_POST['send_mail'] == 1) {
                    $entity = $this->model->getOne($_POST['support_id']);

                    if ($entity['customer_id']) {
                        $this->sendNotification([
                            'parent' => 'support',
                            'customer_id' => $entity['customer_id'],
                            'support_id' => $entity['id'],
                            'description' => 'Arquivo no Chamado #'.$entity['id']. ' removido',
                            'for_files' => 1
                        ]);

                        $url = baseUrl . 'cliente';
                        $message = "<p>Arquivo no Chamado #{$entity['id']} removido com sucesso,
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                            
                        $this->sendMail([
                            'title' => 'Arquivo no Chamado #'.$entity['id']. ' removido',
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }

                    $responsibles = $this->supportResponsiblesModel->getAllBySupport($entity['id']);
                    foreach ($responsibles as $responsible) {
                        $this->sendNotification([
                            'parent' => 'support',
                            'user_id' => $responsible['user_id'],
                            'support_id' => $entity['id'],
                            'description' => 'Arquivo no Chamado #'.$entity['id']. ' removido',
                            'for_files' => 1
                        ]);
                        
                        $url = baseUrl;
                        $message = "<p>Arquivo no Chamado #{$entity['id']} removido com sucesso,
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                            
                        $this->sendMail([
                            'title' => 'Arquivo no Chamado #'.$entity['id']. ' removido',
                            'message' => $message,
                            'name' => $responsible['userName'],
                            'toAddress' => $responsible['userEmail']
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
                if (!empty($_POST['send_email_status'])) {
                    $sendEmailNotification = $_POST['send_email_status'];
                    unset($_POST['send_email_status']);
                } else {
                    $sendEmailNotification = false;
                }

                $updateData = [
                    'status' => $_POST['status'],
                    'call_answer' => $_POST['call_answer'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update($updateData, $entity['id'], 'id');

                if ($transaction) {
                    $supportStatus = "Pendente";
                    if ($_POST['status'] == 1) $supportStatus = "Pendente";
                    if ($_POST['status'] == 2) $supportStatus = "Em Atendimento";
                    if ($_POST['status'] == 3) $supportStatus = "Finalizado";
                    if ($_POST['status'] == 4) $supportStatus = "Fechado";
                    
                    if (!empty($_FILES)) {
                        $this->filesModel->uploadFiles($_FILES, "support", $entity['id'], 'support_id');
                    }

                    if ($sendEmailNotification) {
                        $entity = $this->model->getOne($_POST['id']);

                        if ($entity['customer_id']) {
                            $this->sendNotification([
                                'parent' => 'support',
                                'customer_id' => $entity['customer_id'],
                                'support_id' => $entity['id'],
                                'description' => "Chamado #{$entity['id']} $supportStatus."
                            ]);

                            $url = baseUrl . 'cliente';
                            $message = "<p>Chamado #{$entity['id']}, informações atualizadas; 
                                <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                                
                            $this->sendMail([
                                'title' => 'Chamado #'.$entity['id']. ' - Novas Informações:',
                                'message' => $message,
                                'name' => $entity['customerName'],
                                'toAddress' => $entity['customerEmail']
                            ]);
                        }

                        $responsibles = $this->supportResponsiblesModel->getAllBySupport($entity['id']);
                        if (!empty($responsibles)) {
                            foreach ($responsibles as $responsible) {
                                $this->sendNotification([
                                    'parent' => 'support',
                                    'user_id' => $responsible['user_id'],
                                    'support_id' => $entity['id'],
                                    'description' => "Chamado #{$entity['id']} $supportStatus."
                                ]);

                                $url = baseUrl;
                                $message = "<p>Chamado #{$entity['id']}, informações atualizadas; 
                                    <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                                    
                                $this->sendMail([
                                    'title' => 'Chamado #'.$entity['id']. ' - Novas Informações:',
                                    'message' => $message,
                                    'name' => $responsible['userName'],
                                    'toAddress' => $responsible['userEmail']
                                ]);
                                
                            }
                        } else {
                            $admins = $this->userModel->getAllAdminActives();
                            foreach ($admins as $admin) {
                                $this->sendNotification([
                                    'parent' => 'support',
                                    'user_id' => $admin['id'],
                                    'support_id' => $entity['id'],
                                    'description' => "Mensagem no Chamado #{$entity['id']}."
                                ]);

                                $url = baseUrl;
                                $message = "<p>Chamado #{$entity['id']}, informações atualizadas; 
                                    <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                                    
                                $this->sendMail([
                                    'title' => 'Chamado #'.$entity['id']. ' - Novas Informações:',
                                    'message' => $message,
                                    'name' => $admin['name'],
                                    'toAddress' => $admin['email']
                                ]);
                            }
                        }
                    }

                    if (!empty($_POST['status']) && $_POST['status'] != $entity['status']) {
                        $this->saveHistoric([
                            'support_id' => $entity['id'], 
                            'status' => $_POST['status'],
                            'user_id' => $_SESSION['COD']
                        ]);
                    }

                    $this->toLog("Atualizou a situação do Chamado {$entity['id']}");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'A situação do Chamado foi atualizada.',
                        'type' => 'success',
                        'pos' => 'top-right',
                        'id' => $entity['id']
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'A situação do Chamado não foi atualizada.',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];
                }
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Chamado é inválido.',
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

    public function modulesSearchAction(): void
    {
        if (!empty($_POST)) {
            $this->view->support_id = $_POST['support_id'];
            $this->view->title = $_POST['title'];
            $this->view->target = $_POST['target'];

            $this->render('modules-search', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function responsiblesListAction(): void
    {
        if (!empty($_POST['support_id']) && $this->validatePostParams($_POST)) {
            $this->view->support_id = $_POST['support_id'];
            $data = $this->supportResponsiblesModel->getAllBySupport($_POST['support_id']);
            $this->view->data = $data;
            $this->render('responsibles-list', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function responsibleProcessAction(): bool
    {
        if (!empty($_POST['support_id']) 
            && !empty($_POST['keyword'])
            && $this->validatePostParams($_POST)
            && $this->aclData['canUpdate']  
        ) {
            if (!$this->supportResponsiblesModel->getResponsibleExists($_POST['support_id'], $_POST['keyword'])) {
                $crud = new Crud();
                $crud->setTable($this->supportResponsiblesModel->getTable());

                $transaction = $crud->create([
                    'support_id' => $_POST['support_id'],
                    'user_id' => $_POST['keyword']
                ]);

                if ($transaction) {
                    $this->toLog("Atribuiu o responsável {$_POST['keyword']} no Chamado {$_POST['support_id']}");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'Responsável atribuido no Chamado.',
                        'type' => 'success',
                        'pos' => 'top-right'
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'Responsável não atribuido no Chamado.',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];
                }
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Responsável já atribuido no Chamado.',
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

    public function deleteResponsibleAction(): bool
    {
        if (!empty($_POST) && $this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            $crud = new Crud();
            $crud->setTable($this->supportResponsiblesModel->getTable());
            $transaction = $crud->delete($_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Removeu o Responsável {$_POST['id']} de um chamado");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Responsável removido do Chamado.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Responsável não foi removido do Chamado.',
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
            $files = $this->filesModel->findAllBy('id, file, created_at', 'support_id', $_POST['id']);
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
                $this->filesModel->uploadFiles($_FILES, "support", $_POST['id'], 'support_id');

                if (!empty($_POST['support_files_notification']) && $_POST['support_files_notification'] == 1) {
                    $entity = $this->model->getOne($_POST['id']);

                    if (!empty($entity['customer_id'])) {
                        $this->sendNotification([
                            'parent' => 'support',
                            'customer_id' => $entity['customer_id'],
                            'support_id' => $entity['id'],
                            'description' => "Novos arquivos no Chamado #{$entity['id']}.",
                            'for_files' => 1
                        ]);

                        $url = baseUrl;
                        $message = "<p>Chamado #{$entity['id']}, informações atualizadas; 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                            
                        $this->sendMail([
                            'title' => "Arquivos enviados no Chamado #{$_POST['id']}",
                            'message' => $message,
                            'name' => $entity['customerName'],
                            'toAddress' => $entity['customerEmail']
                        ]);
                    }
                    
                    $responsibles = $this->supportResponsiblesModel->getAllBySupport($_POST['id']);
                    foreach ($responsibles as $responsible) {
                        $this->sendNotification([
                            'parent' => 'support',
                            'user_id' => $responsible['user_id'],
                            'support_id' => $_POST['id'],
                            'description' => "Arquivos enviados no Chamado #{$_POST['id']}",
                            'for_files' => 1
                        ]);


                        $url = baseUrl;
                        $message = "<p>Chamado #{$entity['id']}, informações atualizadas; 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                            
                        $this->sendMail([
                            'title' => "Arquivos enviados no Chamado #{$_POST['id']}",
                            'message' => $message,
                            'name' => $responsible['userName'],
                            'toAddress' => $responsible['userEmail']
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

    public function deleteMessageAction(): bool
    {
        if ($this->validatePostParams($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->supportMessagesModel->getTable());
            $transaction = $crud->delete($_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Removeu a mensagem #{$_POST['id']} do Chamado");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Mensagem removida.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Mensagem não foi removida.',
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
                        if ($entity['customer_id']) {
                            $this->sendNotification([
                                'parent' => 'support',
                                'customer_id' => $entity['customer_id'],
                                'support_id' => $entity['id'],
                                'description' => 'Chamado #'.$entity['id']. ' restaurado'
                            ]);

                            $url = baseUrl . 'cliente';
                            $message = "<p>Chamado #{$entity['id']} restaurado com sucesso; 
                                <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                                
                            $this->sendMail([
                                'title' => 'Chamado #'.$entity['id']. ' restaurado',
                                'message' => $message,
                                'name' => $entity['customerName'],
                                'toAddress' => $entity['customerEmail']
                            ]);
                        }

                        $responsibles = $this->supportResponsiblesModel->getAllBySupport($entity['id']);
                        foreach ($responsibles as $responsible) {
                            $this->sendNotification([
                                'parent' => 'support',
                                'user_id' => $responsible['user_id'],
                                'support_id' => $entity['id'],
                                'description' => 'Chamado #'.$entity['id']. ' restaurado'
                            ]);
                            
                            $url = baseUrl;
                            $message = "<p>Chamado #{$entity['id']}, informações atualizadas; 
                                <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                                
                            $this->sendMail([
                                'title' => 'Chamado #'.$entity['id']. ' restaurado',
                                'message' => $message,
                                'name' => $responsible['userName'],
                                'toAddress' => $responsible['userEmail']
                            ]);
                        }
                    }

                    $this->toLog("Restaurou o Chamado {$_POST['id']}");
                    $data  = [
                        'title' => 'Sucesso!',
                        'msg'   => 'Chamado restaurado.',
                        'type'  => 'success',
                        'pos'   => 'top-right'
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!',
                        'msg' => 'O Chamado não foi restaurado.',
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