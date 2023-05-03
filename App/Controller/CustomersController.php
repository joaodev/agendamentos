<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;

class CustomersController extends ActionController implements CrudInterface
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Customers", "app");
    }

    public function indexAction(): void
    {
        $data = $this->model->getAll($this->parentUUID);
        $this->view->data = $data;

        $activePlan = self::getActivePlan();
        $totalServices = $this->model->totalData($this->model->getTable(), $this->parentUUID);
        
        $totalFree = ($activePlan['total_customers'] - $totalServices);
        $this->view->total_free = $totalFree;

        if ($totalServices >= $activePlan['total_customers']) {
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
            $totalCustomers = $this->model->totalData($this->model->getTable(), $this->parentUUID);
            if ($totalCustomers >= $activePlan['total_customers']) {
                $data  = [
                    'title' => 'Erro!',
                    'msg' => 'Você atingiu o limite de cadastros disponíveis para este plano.',
                    'type' => 'error',
                    'pos'   => 'top-center'
                ];
            } else {
                $_POST['uuid'] = $this->model->NewUUID();
                $_POST['parent_uuid'] = $this->parentUUID;
                
                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->create($_POST);

                if ($transaction) {
                    $this->toLog("Cadastrou o Cliente {$_POST['uuid']}");
                    $data  = [
                        'title' => 'Sucesso!', 
                        'msg'   => 'Cliente cadastrado.',
                        'type'  => 'success',
                        'pos'   => 'top-right',
                        'uuid'  => $_POST['uuid'],
                        'name'  => $_POST['name']
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!', 
                        'msg' => 'O Cliente não foi cadastrado.',
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

	public function readAction(): void
    {
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid'], $this->parentUUID);
            $this->view->entity = $entity;
            $this->render('read', false);
        }
    }

	public function updateAction(): void
    {
        if (!empty($_POST)) {
            $entity = $this->model->getOne($_POST['uuid'], $this->parentUUID);
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
                $this->toLog("Atualizou o Cliente {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Cliente atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Cliente não foi atualizado.',
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

	public function deleteAction(): bool
    {
        if (!empty($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update(['deleted' => 1], $_POST['uuid'], 'uuid');

            if ($transaction) {
                $this->toLog("Removeu o Cliente {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Cliente removido.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Cliente não foi removido.',
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

            if (!empty($_POST['name'])) $field = 'name';
            if (!empty($_POST['email'])) $field = 'email';
            if (!empty($_POST['cellphone'])) $field = 'cellphone';
            if (!empty($_POST['document_1'])) $field = 'document_1';
            if (!empty($_POST['document_2'])) $field = 'document_2';
            
            $exists = $this->model->fieldExists($field, $_POST[$field], 'uuid', $uuid);
            if ($exists) {
                echo 'false';
            } else {
                echo 'true';
            }
        }
    }

    public function searchAction(): bool
    {
        if (!empty($_POST)) {
            $res  = [];
            if (!empty($_POST['term']) && strlen($_POST['term']) >= 1) {
                $data = $this->model->searchData($_POST['term'], $this->parentUUID);

                foreach ($data as $entity) {
                    $res[] = [
                        'id' => $entity['uuid'],
                        'text' => $entity['name']
                    ];
                }
            }

            echo json_encode($res);
            return true;           
        } else {    
            return false;
        }
    }
}