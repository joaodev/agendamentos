<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;

class ServicesController extends ActionController implements CrudInterface
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Services", "app");
    }

    public function indexAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $stringFields = 'uuid, title, description, price, status, created_at, updated_at';
            $data = $this->model->findAllBy($stringFields, 'parent_uuid', $this->parentUUID);
            $this->view->data = $data;

            $activePlan = self::getActivePlan();
            $totalServices = $this->model->totalData($this->model->getTable(), $this->parentUUID);

            $totalFree = ($activePlan['total_services'] - $totalServices);
            $this->view->total_free = $totalFree;

            if ($totalServices >= $activePlan['total_services']) {
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
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $this->render('create', false);
        }
    }

    public function createProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            $activePlan = self::getActivePlan();
            $totalServices = $this->model->totalData($this->model->getTable(), $this->parentUUID);
            if ($totalServices >= $activePlan['total_services']) {
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
                $_POST['price'] = $this->moneyToDb($_POST['price']);
    
                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->create($_POST);
    
                if ($transaction) { 
                    $this->toLog("Cadastrou o serviço $uuid");
                    $data  = [
                        'title' => 'Sucesso!',
                        'msg'   => 'Serviço cadastrado.',
                        'type'  => 'success',
                        'pos'   => 'top-right',
                        'uuid'  => $uuid,
                        'titlesv' => $_POST['title'],
                        'price' => number_format($_POST['price'], 2, ",",".")
    
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!',
                        'msg' => 'O serviço não foi cadastrado.',
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
            $fields = "uuid, title, description, price, status";
            $entity = $this->model->find($_POST['uuid'], $fields, 'uuid');
            $this->view->entity = $entity;

            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $_POST['price']  = $this->moneyToDb($_POST['price']);

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction) {
                $this->toLog("Atualizou o serviço {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!',
                    'msg'   => 'Serviço atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!',
                    'msg' => 'O serviço não foi atualizado.',
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
            $fields = "uuid, title, price, description, status, created_at, updated_at";
            $entity = $this->model->find($_POST['uuid'], $fields, 'uuid');
            $this->view->entity = $entity;
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
                $this->toLog("Removeu o serviço {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!',
                    'msg'   => 'Serviço removido.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!',
                    'msg' => 'O serviço não foi removido.',
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

    public function fieldExistsAction()
    {
        if (!empty($_POST)) {
            $uuid  = (!empty($_POST['uuid']) ? $_POST['uuid'] : null);
            $field = "";

            if (!empty($_POST['title'])) $field = 'title';

            $exists = $this->model->fieldExists($field, $_POST[$field], 'uuid', $uuid);
            if ($exists) {
                echo 'false';
            } else {
                echo 'true';
            }
        }
    }
}