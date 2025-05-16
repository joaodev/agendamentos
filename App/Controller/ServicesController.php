<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\Services;

class ServicesController extends ActionController implements CrudInterface
{
    private mixed $model;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Services();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'services'),
            'canCreate' => $this->getAcl('create', 'services'),
            'canUpdate' => $this->getAcl('update', 'services'),
            'canDelete' => $this->getAcl('delete', 'services'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
            $stringFields = 'id, title, price, service_type, status, created_at, updated_at';
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

            $_POST['price'] = $this->moneyToDb($_POST['price']);
            
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);

            if ($transaction) {
                $newId = $transaction;

                $this->toLog("Cadastrou o serviço $newId");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Serviço cadastrado.',
                    'type' => 'success',
                    'pos' => 'top-right',
                    'id' => $newId,
                    'titlesv' => $_POST['title'],
                    'price' => number_format($_POST['price'], 2, ",", ".")

                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O serviço não foi cadastrado.',
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
            $fields = "id, title, price, service_type, status";
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
            $_POST['price'] = $this->moneyToDb($_POST['price']);

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Atualizou o serviço {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Serviço atualizado.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O serviço não foi atualizado.',
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
                $this->toLog("Removeu o serviço {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Serviço removido.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O serviço não foi removido.',
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

    public function searchServiceAction(): bool
    {
        if (!empty($_POST)) {
            $res = [];
            if (!empty($_POST['term']) && strlen($_POST['term']) >= 1) {
                $data = $this->model->searchData($_POST['term']);

                foreach ($data as $entity) {
                    $res[] = [
                        'id' => $entity['id'],
                        'text' => $entity['id']
                        .' - '. $entity['title'] 
                        . ' | Valor: R$ ' . number_format($entity['price'], 2,",",".")
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