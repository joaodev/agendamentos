<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\Customers;

class CustomersController extends ActionController implements CrudInterface
{
    private mixed $model;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Customers();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'customers'),
            'canCreate' => $this->getAcl('create', 'customers'),
            'canUpdate' => $this->getAcl('update', 'customers'),
            'canDelete' => $this->getAcl('delete', 'customers'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
            $stringFields = 'id, name, document_1, document_2, email, phone, cellphone, 
                                postal_code, address, number, 
                                complement, neighborhood, city, state,
                                status, created_at, updated_at';

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

            if ($this->model->fieldExists('name', $_POST['name'], 'id')) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Nome já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            if ($this->model->fieldExists('email', $_POST['email'], 'id')) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Email já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            if ($this->model->fieldExists('cellphone', $_POST['cellphone'], 'id')) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Celular já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            if ($this->model->fieldExists('document_1', $_POST['document_1'], 'id')) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'CPF já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            if ($this->model->fieldExists('document_2', $_POST['document_2'], 'id')) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'CNPJ já cadastrado, informe outro.',
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

                $fields = "id, name";
                $entity = $this->model->find($newId, $fields, 'id');
                $this->toLog("Cadastrou o Cliente {$newId}");

                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Cliente cadastrado.',
                    'type' => 'success',
                    'pos' => 'top-right',
                    'id' => $entity['id'],
                    'name' => $entity['name']
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Cliente não foi cadastrado.',
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

    public function updateAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            $fields = "id, name, document_1, document_2, email, phone, cellphone, 
                        postal_code, address, number, 
                        complement, neighborhood, city, state, 
                        status, created_at, updated_at";

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

            if ($this->model->fieldExists('name', $_POST['name'], 'id', $_POST['id'])) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Nome já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            if ($this->model->fieldExists('email', $_POST['email'], 'id', $_POST['id'])) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Email já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            if ($this->model->fieldExists('cellphone', $_POST['cellphone'], 'id', $_POST['id'])) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Celular já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            if ($this->model->fieldExists('document_1', $_POST['document_1'], 'id', $_POST['id'])) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'CPF já cadastrado, informe outro.',
                    'type' => 'error', 'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            if ($this->model->fieldExists('document_2', $_POST['document_2'], 'id', $_POST['id'])) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'CNPJ já cadastrado, informe outro.',
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
                $this->toLog("Atualizou o Cliente {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Cliente atualizado.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Cliente não foi atualizado.',
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
        if ($this->validatePostParams($_POST) && $this->aclData['canDelete']) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update(['deleted' => 1], $_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Removeu o Cliente {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Cliente removido.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Cliente não foi removido.',
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

    public function searchAction(): bool
    {
        if (!empty($_POST)) {
            $res = [];
            if (!empty($_POST['term']) && strlen($_POST['term']) >= 1) {
                $data = $this->model->searchData($_POST['term']);

                foreach ($data as $entity) {
                    $res[] = [
                        'id' => $entity['id'],
                        'text' => $entity['id'] . ' - ' . $entity['name'] 
                            . ' ( Email:' . $entity['email'] . ' '
                            . ($entity['document_1'] ? ' / CPF: ' . $entity['document_1'] : '') 
                            . ($entity['document_2'] ? ' / CNPJ: ' . $entity['document_1'] : '') 
                            . ' )'
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