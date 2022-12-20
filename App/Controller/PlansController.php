<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;

class PlansController extends ActionController implements CrudInterface
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Plans", "app");
    }

    public function indexAction(): void
    {
        $data = $this->model->getAll();
        $this->view->data = $data;
        $this->render('index', false);
    }

    public function createAction(): void
    {
        $this->render('create', false);
    }
    
    public function createProcessAction(): bool
    {
        if (!empty($_POST)) {
            $uuid = $this->model->NewUUID();
            $_POST['uuid'] = $uuid;
            $_POST['price'] = $this->moneyToDb($_POST['price']);
            
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);
           
            if ($transaction){
                $this->toLog("Cadastrou o Plano $uuid");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Plano cadastrado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Plano não foi cadastrado.',
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
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST)) {
            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $_POST['price'] = $this->moneyToDb($_POST['price']);

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction){
                $this->toLog("Atualizou o Plano {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Plano atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Plano não foi atualizado.',
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
            $entity = $this->model->getOne($_POST['uuid']);
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

            if ($transaction){
                $this->toLog("Removeu o Plano {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Plano removido.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Plano não foi removido.',
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
            $uuid     = (!empty($_POST['uuid']) ? $_POST['uuid'] : null);
            $field = "";

            if (!empty($_POST['name'])) $field = 'name';
            
            $exists = $this->model->fieldExists($field, $_POST[$field], 'uuid', $uuid);
            if ($exists) {
                echo 'false';
            } else {
                echo 'true';
            }
        }
    }

    public function userPlansAction(): void
    {
        $data = $this->model->getAllPlans();
        $this->view->data = $data;
        $this->render('user-plans', false);
    }
}