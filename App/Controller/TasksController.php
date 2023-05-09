<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;

class TasksController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $filesModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Tasks", "app");
        $this->filesModel = Container::getClass("Files", "app");
    }

    public function indexAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            if (!empty($_GET['m'])) {
                $month = $_GET['m'];
            } else {
                $month = date('Y-m');
            }

            $this->view->month = self::formatMonth($month);
            $data = $this->model->getAllByMonth('0', $month, $this->parentUUID);
            $this->view->data = $data;

            $activePlan = self::getActivePlan();
            $totalTasks = $this->model->totalMonthlyData(
                $month, $this->model->getTable(), 'task_date', $this->parentUUID
            );

            $totalFree = ($activePlan['total_tasks'] - $totalTasks);
            $this->view->total_free = $totalFree;

            if ($totalTasks >= $activePlan['total_tasks']) {
                $reached_limit = true;
            } else {
                $reached_limit = false;
            }   
            $this->view->reached_limit = $reached_limit;
            $this->view->parentUUID = $this->parentUUID;
            
            $this->render('index', false);
        }
    }

    public function createAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $this->render('create', false);
        }
    }

    public function createProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            $activePlan = self::getActivePlan();
            $month = substr($_POST['task_date'], 0, 7);
            $totalTasks = $this->model->totalMonthlyData(
                $month, $this->model->getTable(), 'task_date', $this->parentUUID
            );

            if ($totalTasks >= $activePlan['total_tasks']) {
                $data  = [
                    'title' => 'Erro!',
                    'msg' => 'Você atingiu o limite de cadastros disponíveis para este plano.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];
            } else {
                $uuid = $this->model->NewUUID();
                $_POST['uuid'] = $uuid;
                $_POST['parent_uuid'] = $this->parentUUID;
    
                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->create($_POST);
    
                if ($transaction) { 
                    if (!empty($_FILES)) {
                        $this->filesModel->uploadFiles($_FILES, "tasks", $uuid);
                    }

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
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $fields = "uuid, title, description, task_date, task_time, status";
            $entity = $this->model->find($_POST['uuid'], $fields, 'uuid');
            $this->view->entity = $entity;

            $files = $this->filesModel->findAllBy('uuid, file', 'parent_uuid', $_POST['uuid']);
            $this->view->files = $files;

            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            $_POST['updated_at'] = date('Y-m-d H:i:s');

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');
          
            if ($transaction) {
                if (!empty($_FILES)) {
                    $this->filesModel->uploadFiles($_FILES, "tasks", $_POST['uuid']);
                }

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
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $fields = "uuid, title, description, task_date, task_time, status, created_at, updated_at";
            $entity = $this->model->find($_POST['uuid'], $fields, 'uuid');
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
}