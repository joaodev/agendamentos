<?php

namespace Client\Controller;

use Client\Model\ClientMessages;
use Core\Controller\ActionController;
use Core\Db\Crud;

class MessagesController extends ActionController
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = New ClientMessages();
    }

    public function indexAction(): void
    {
        if ($this->validateClientPostParams($_POST)) {
            $data = $this->model->getAllByCustomer($_SESSION['CLI_COD']);
            $this->view->data = $data;
            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function checkMessagesAction(): void
    {
        if ($this->validateClientPostParams($_POST)) {
            $customerMessages = $this->model->getAllUnreadsByCustomer($_SESSION['CLI_COD']);
            $this->view->customer_messages = $customerMessages;
            $this->render('messages', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }
    
    public function readAction(): bool
    {
        if ($this->validateClientPostParams($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'has_read' => '1'
            ], $_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Marcou como lida a Mensagem {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Mensagem marcada como lida.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Mensagem não foi marcada como lida.',
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
        if ($this->validateClientPostParams($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'deleted' => '1',
                'deleted_at' => date('Y-m-d H:i:s')
            ], $_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Removeu a Mensagem {$_POST['id']}");
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
    
    public function readAllAction(): bool
    {
        if ($this->validateClientPostParams($_POST)) {
            $data = $this->model->getAllByCustomer($_SESSION['CLI_COD']);
            
            $crud = new Crud();
            $crud->setTable($this->model->getTable());

            foreach ($data as $entity) {
                $crud->update(['has_read' => '1'], $entity['id'], 'id');
            }

            $data = [
                'title' => 'Sucesso!',
                'msg' => 'Mensagens marcadas como lida.',
                'type' => 'success',
                'pos' => 'top-right'
            ];

            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }

    public function deleteAllAction(): bool
    {
        if ($this->validateClientPostParams($_POST)) {
            $data = $this->model->getAllByCustomer($_SESSION['CLI_COD']);
            
            $crud = new Crud();
            $crud->setTable($this->model->getTable());

            foreach ($data as $entity) {
                $crud->update([
                    'deleted' => '1',
                    'deleted_at' => date('Y-m-d H:i:s')
                ], $entity['id'], 'id');
            }

            $data = [
                'title' => 'Sucesso!',
                'msg' => 'Mensagens removidas.',
                'type' => 'success',
                'pos' => 'top-right'
            ];

            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }

    public function moduleValidationAction(): bool
    {
        if ($this->validateClientPostParams($_POST)) {
            $entity = $this->model->getOneByCustomer($_POST['id'], $_SESSION['CLI_COD']);
            
            switch ($entity['parent']) {
                case 'budgets':
                    $mod_id = $entity['budget_id'];
                    $mod_name = 'orcamentos/detalhes';
                    break;
                case 'os':
                    $mod_id = $entity['os_id'];
                    $mod_name = 'os/detalhes';
                    break;
                case 'support':
                    $mod_id = $entity['support_id'];
                    $mod_name = 'chamados/detalhes';
                    break;
                default:
                    $mod_id = null;
                    $mod_name = null;
                    break;
            }

            $data = [
                'mod_id' => $mod_id,
                'mod_name' => $mod_name
            ];

            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }

    public function markReadsAction(): void
    {
        if ($this->validateClientPostParams($_POST)) {
            $entity = $this->model->getOneByCustomer($_POST['id'], $_SESSION['CLI_COD']);

            if ($entity['parent'] == 'os') {
                $params = [
                    'os', 'os_id', $entity['os_id'], $_SESSION['CLI_COD']
                ];
            } elseif ($entity['parent'] == 'budgets') {
                $params = [
                    'budgets', 'budget_id', $entity['budget_id'], $_SESSION['CLI_COD']
                ];
            } elseif ($entity['parent'] == 'support') {
                $params = [
                    'support', 'support_id', $entity['support_id'], $_SESSION['CLI_COD']
                ];
            }
            
            $unreads = $this->model->getParentUnreadMessages(
                $params[0], $params[1], $params[2], $params[3]
            );

            foreach ($unreads as $unread) {
                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $crud->update(['has_read' => '1'], $unread['id'], 'id');
            }
        }
    }
}