<?php

namespace App\Controller;

use App\Model\SystemNotifications;
use Core\Controller\ActionController;
use Core\Db\Crud;

class NotificationsController extends ActionController
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();    

        $this->model = new SystemNotifications();
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST)) {
            $data = $this->model->getAllByUser($_SESSION['COD']);
            $this->view->data = $data;
            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function contentAction(): void
    {
        if ($this->validatePostParams($_POST)) {
            $userNotifications = $this->model->getAllUnreadsByUser($_SESSION['COD']);
            $this->view->user_notifications = $userNotifications;
            $this->render('content', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function readAction(): bool
    {
        if ($this->validatePostParams($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'has_read' => '1'
            ], $_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Marcou como lida a Notificação {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Notificação marcada como lida.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Notificação não foi marcada como lida.',
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
        if ($this->validatePostParams($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'deleted' => '1',
                'deleted_at' => date('Y-m-d H:i:s')
            ], $_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Removeu a Notificação {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Notificação removida.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Notificação não foi removida.',
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

    public function moduleValidationAction(): bool
    {
        if ($this->validatePostParams($_POST)) {
            $entity = $this->model->getOneByUser($_POST['id'], $_SESSION['COD']);
           
            switch ($entity['parent']) {
                case 'schedules':
                    $mod_id = $entity['schedule_id'];
                    $mod_name = 'agendamentos/detalhes';
                    break;
                case 'prospects':
                    $mod_id = $entity['prospect_id'];
                    $mod_name = 'prospeccoes/detalhes';
                    break;
                case 'budgets':
                    $mod_id = $entity['budget_id'];
                    $mod_name = 'orcamentos/detalhes';
                    break;
                case 'os':
                    $mod_id = $entity['os_id'];
                    $mod_name = 'os/detalhes';
                    break;
                case 'os_follow_ups':
                    $mod_id = $entity['os_id'];
                    $mod_name = 'os/andamento-os';
                    break;
                case 'os_files':
                    $mod_id = $entity['os_id'];
                    $mod_name = 'os/arquivos-os';
                    break;
                case 'tasks':
                    $mod_id = $entity['task_id'];
                    $mod_name = 'tarefas/detalhes';
                    break;
                case 'expenses':
                    $mod_id = $entity['expense_id'];
                    $mod_name = 'contas/detalhes';
                    break;
                case 'purchases':
                    $mod_id = $entity['purchase_id'];
                    $mod_name = 'compras/detalhes';
                    break;
                case 'support':
                    $mod_id = $entity['support_id'];
                    $mod_name = 'chamados/detalhes';
                    break;
                case 'sales':
                    $mod_id = $entity['sale_id'];
                    $mod_name = 'vendas/detalhes';
                    break;
                case 'time_sheets':
                    $mod_id = $entity['time_sheet_id'];
                    $mod_name = 'folha-ponto/detalhes';
                    break;
                case 'item_control':
                    $mod_id = $entity['item_control_id'];
                    $mod_name = 'controle-estoque/detalhes';
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

    public function markReadAction(): void
    {
        if ($this->validatePostParams($_POST)) {
            $entity = $this->model->getOneByUser($_POST['id'], $_SESSION['COD']);
            if ($entity) {
                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $crud->update(['has_read' => '1'], $entity['id'], 'id');
            }
        }
    }

    public function markReadsAction(): bool
    {
        if ($this->validatePostParams($_POST)) {
            $data = $this->model->getAllByUser($_SESSION['COD']);
            
            $crud = new Crud();
            $crud->setTable($this->model->getTable());

            foreach ($data as $entity) {
                $crud->update(['has_read' => '1'], $entity['id'], 'id');
            }

            $data = [
                'title' => 'Sucesso!',
                'msg' => 'Notificações marcadas como lida.',
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
        if ($this->validatePostParams($_POST)) {
            $data = $this->model->getAllByUser($_SESSION['COD']);
            
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
                'msg' => 'Notificações removidas.',
                'type' => 'success',
                'pos' => 'top-right'
            ];

            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }
}