<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\Categories;

class CategoriesController extends ActionController implements CrudInterface
{
    private mixed $model;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Categories();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'categories'),
            'canCreate' => $this->getAcl('create', 'categories'),
            'canUpdate' => $this->getAcl('update', 'categories'),
            'canDelete' => $this->getAcl('delete', 'categories'),
            'canViewSubcategories' => $this->getAcl('view', 'subcategories'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
            $stringFields = 'id, title, status, created_at, updated_at';
            $data = $this->model->findAll($stringFields);
            $this->view->data = $data;

            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canCreate']) {
            $this->render('create', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createProcessAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canCreate']) {
            unset($_POST['target']);
            
            if ($this->model->fieldExists('title', $_POST['title'], 'id')) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Título já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);
            
            if ($transaction) {
               
                $this->toLog("Cadastrou a Categoria $transaction");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Categoria cadastrada.',
                    'type' => 'success',
                    'pos' => 'top-right',
                    'id' => $transaction,
                    'titlecat' => $_POST['title'],
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Categoria não foi cadastrada.',
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

    public function updateAction(): void
    {
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            $fields = "id, title, status";
            $entity = $this->model->find($_POST['id'], $fields, 'id');
            $this->view->entity = $entity;
            $this->render('update', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            unset($_POST['target']);

            if ($this->model->fieldExists('title', $_POST['title'], 'id', $_POST['id'])) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Título já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            $_POST['updated_at'] = date('Y-m-d H:i:s');

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Atualizou a Categoria {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Categoria atualizada.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Categoria não foi atualizada.',
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
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && $this->aclData['canView']) {
            $entity = $this->model->getOne($_POST['id'], false);
            if ($entity) {
                $this->view->entity = $entity;
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
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'deleted' => '1',
                'updated_at' => date('Y-m-d H:i:s')
            ], $_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Removeu a Categoria {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Categoria removida.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Categoria não foi removida.',
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
}           