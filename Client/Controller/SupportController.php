<?php

namespace Client\Controller;

use App\Model\ChangesHistoric;
use App\Model\Files;
use App\Model\Support;
use App\Model\SupportMessages;
use App\Model\SupportResponsibles;
use App\Model\User;
use Core\Controller\ActionController;
use Core\Db\Crud;

class SupportController extends ActionController
{
    private mixed $supportModel;
    private mixed $filesModel;
    private mixed $userModel;
    private mixed $supportMessagesModel;
    private mixed $supportResponsiblesModel;
    private mixed $changesHistoricModel;

    public function __construct()
    {
        parent::__construct();

        $this->supportModel = new Support();
        $this->filesModel = new Files();
        $this->userModel = new User();
        $this->supportMessagesModel = new SupportMessages();
        $this->supportResponsiblesModel = new SupportResponsibles();
        $this->changesHistoricModel = new ChangesHistoric();
    }

    public function indexAction(): void
    {
        if ($this->validateClientPostParams($_POST)) {
            $data = $this->supportModel->getAll($_SESSION['CLI_COD']);
            $this->view->data = $data;
            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createAction(): void
    {
        if ($this->validateClientPostParams($_POST)) {
            $this->render('create', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createProcessAction(): bool
    {
        if ($this->validateClientPostParams($_POST)) {
            unset($_POST['target']);
            
            $_POST['customer_id'] = $_SESSION['CLI_COD'];

            $crud = new Crud();
            $crud->setTable($this->supportModel->getTable());
            $transaction = $crud->create($_POST);

            if ($transaction) {
                $newId = $transaction;

                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "support", $newId, 'support_id');
                }

                $entity = $this->supportModel->getOne($newId);
                $admins = $this->userModel->getAllAdminActives();

                foreach ($admins as $admin) {
                    $this->sendNotification([
                        'parent' => 'support',
                        'user_id' => $admin['id'],
                        'support_id' => $newId,
                        'description' => "Chamado #{$newId} aberto."
                    ]);

                    $url = baseUrl;
                    $message = "<p>Chamado #{$entity['id']} em aberto; 
                        <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                        
                    $this->sendMail([
                        'title' => 'Chamado #'.$entity['id']. ' - Em Aberto',
                        'message' => $message,
                        'name' => $admin['name'],
                        'toAddress' => $admin['email']
                    ]);
                }

                $this->saveHistoric([
                    'support_id' => $newId, 
                    'status' => 1,
                    'customer_id' => $_SESSION['CLI_COD']
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
        if ($this->validateClientPostParams($_POST)) {
            $entity = $this->supportModel->getOne($_POST['id'], $_SESSION['CLI_COD'], false);
            if ($entity) {
                $this->view->entity = $entity;
       
                $historic = $this->changesHistoricModel->getAllByModule('support_id', $_POST['id']);
                $this->view->historic = $historic;

                $responsibles = $this->supportResponsiblesModel->getAllBySupport($entity['id']);
                $this->view->responsibles = $responsibles;

                $this->render('read', false);
            } else {
                $this->render('../error/not-found', false);
            }
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function messagesAction():void 
    {
        if ($this->validateClientPostParams($_POST)) {
            $messages = $this->supportMessagesModel->getAllByCall($_POST['id']);
            $this->view->messages = $messages;
            $this->render('messages', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function messageProcessAction(): bool
    {
        if ($this->validateClientPostParams($_POST)) {
            $postData = [
                'parent_id' => $_POST['id'],
                'customer_id' => $_SESSION['CLI_COD'],
                'description' => $_POST['message']
            ];
            
            $crud = new Crud();
            $crud->setTable($this->supportMessagesModel->getTable());
            $transaction = $crud->create($postData);
            
            if ($transaction) {
                $responsibles = $this->supportResponsiblesModel->getAllBySupport($_POST['id']);
                if (!empty($responsibles)) {
                    foreach ($responsibles as $responsible) {
                        $this->notifyMessage([
                            'parent' => 'support',
                            'user_id' => $responsible['user_id'],
                            'support_id' => $_POST['id'],
                            'description' => "Mensagem no Chamado #{$_POST['id']}."
                        ]);
                    }
                } else {
                    $admins = $this->userModel->getAllAdminActives();
                    foreach ($admins as $admin) {
                        $this->notifyMessage([
                            'parent' => 'support',
                            'user_id' => $admin['id'],
                            'support_id' => $_POST['id'],
                            'description' => "Mensagem no Chamado #{$_POST['id']}."
                        ]);
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
    
    public function filesAction(): void
    {
        if (!empty($_POST['id']) && $this->validateClientPostParams($_POST)) {
            $files = $this->filesModel->findAllBy('id, file, created_at', 'support_id', $_POST['id']);
            $this->view->files = $files;
            $this->render('files', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }
}