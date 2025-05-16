<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\PaymentTypes;

class PaymentTypesController extends ActionController implements CrudInterface
{
    private mixed $model;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PaymentTypes();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'payment-types'),
            'canCreate' => $this->getAcl('create', 'payment-types'),
            'canUpdate' => $this->getAcl('update', 'payment-types'),
            'canDelete' => $this->getAcl('delete', 'payment-types'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
            $data = $this->model->getAll();
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
            
            if ($this->model->fieldExists('name', $_POST['name'], 'id')) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Nome já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);

            if ($transaction) {
                $newId = $transaction;

                $this->toLog("Cadastrou a Forma de Pagamento $newId");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Forma de Pagamento cadastrada.',
                    'type' => 'success',
                    'pos' => 'top-right',
                    'id' => $newId
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Forma de Pagamento não foi cadastrada.',
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
            $entity = $this->model->getOne($_POST['id']);
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

            if ($this->model->fieldExists('name', $_POST['name'], 'id', $_POST['id'])) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Nome já cadastrado, informe outro.',
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
                $this->toLog("Atualizou a Forma de Pagamento {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Forma de Pagamento atualizada.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Forma de Pagamento não foi atualizada.',
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
                $this->toLog("Removeu a Forma de Pagamento {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Forma de Pagamento removida.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Forma de Pagamento não foi removida.',
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
