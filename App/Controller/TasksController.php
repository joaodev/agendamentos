<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;

class TasksController extends ActionController implements CrudInterface
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Tasks", "app");
    }

    public function indexAction(): void
    {
        $stringFields = 'uuid, title, description, status, created_at, updated_at';
        $data = $this->model->findAllBy($stringFields, 'user_uuid', $_SESSION['COD']);
        $this->view->data = $data;

        $activePlan = self::getActivePlan();
        $totalTasks = $this->model->totalData($this->model->getTable(), $_SESSION['COD']);

        $totalFree = ($activePlan['total_tasks'] - $totalTasks);
        $this->view->total_free = $totalFree;

        if ($totalTasks >= $activePlan['total_tasks']) {
            $reached_limit = true;
        } else {
            $reached_limit = false;
        }   
        $this->view->reached_limit = $reached_limit;
        
        $this->render('index', false);
    }

    public function createAction(): void
    {
        $this->render('create', false);
    }

    public function createProcessAction(): bool
    {
        if (!empty($_POST)) {
            $activePlan = self::getActivePlan();
            $totalTasks = $this->model->totalData($this->model->getTable(), $_SESSION['COD']);
            if ($totalTasks >= $activePlan['total_tasks']) {
                $data  = [
                    'title' => 'Erro!',
                    'msg' => 'Você atingiu o limite de cadastros disponíveis para este plana.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];
            } else {
                $uuid = $this->model->NewUUID();
                $_POST['uuid'] = $uuid;
                $_POST['user_uuid'] = $_SESSION['COD'];
    
                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->create($_POST);
    
                if ($transaction) { 
                    $this->toLog("Cadastrou a tarefa $uuid");
                    $data  = [
                        'title' => 'Sucesso!',
                        'msg'   => 'Tarefa cadastrada.',
                        'type'  => 'success',
                        'pos'   => 'top-right'
    
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!',
                        'msg' => 'A Tarefa não foi cadastrada.',
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
            $fields = "uuid, title, description, task_date, task_time, status";
            $entity = $this->model->find($_POST['uuid'], $fields, 'uuid');
            $this->view->entity = $entity;

            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST)) {
            $_POST['updated_at'] = date('Y-m-d H:i:s');

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction) {
                $this->toLog("Atualizou a tarefa {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!',
                    'msg'   => 'Tarefa atualizada.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!',
                    'msg' => 'A Tarefa não foi atualizada.',
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
            $fields = "uuid, title, description, task_date, task_time, status, created_at, updated_at";
            $entity = $this->model->find($_POST['uuid'], $fields, 'uuid');
            $this->view->entity = $entity;
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
                $this->toLog("Removeu a tarefa {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!',
                    'msg'   => 'Tarefa removida.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!',
                    'msg' => 'A Tarefa não foi removida.',
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
}