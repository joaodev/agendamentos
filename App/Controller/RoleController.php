<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\Module;
use App\Model\Privilege;
use App\Model\Role;
use App\Model\User;

class RoleController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $modulesModel;
    private mixed $privilegesModel;
    private mixed $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Role();
        $this->modulesModel = new Module();
        $this->privilegesModel = new Privilege();
        $this->userModel = new User();
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && self::isAdmin()) {
            $data = $this->model->getAll();
            $this->view->data = $data;
            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createAction(): void
    {
        if ($this->validatePostParams($_POST) && self::isAdmin()) {
            $this->render('create', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    private function savePrivilege(string $role, string $resource, string $module): void
    {
        $data = [
            'role_id' => $role,
            'resource_id' => $resource,
            'module_id' => $module
        ];

        $crud = new Crud();
        $crud->setTable($this->privilegesModel->getTable());
        $crud->create($data);
    }

    public function createProcessAction(): bool
    {
        if ($this->validatePostParams($_POST) && self::isAdmin()) {
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
                $modules = $this->modulesModel->getAll();
                
                foreach ($modules as $module) {
                    if ($module['view_id'] != 0) {
                        $this->savePrivilege($newId, $module['view_id'], $module['id']);
                    }

                    if ($module['create_id'] != 0) {
                        $this->savePrivilege($newId, $module['create_id'], $module['id']);
                    }

                    if ($module['update_id'] != 0) {
                        $this->savePrivilege($newId, $module['update_id'], $module['id']);
                    }

                    if ($module['delete_id'] != 0) {
                        $this->savePrivilege($newId, $module['delete_id'], $module['id']);
                    }
                }

                $this->toLog("Cadastrou o perfil de acesso: {$_POST['name']} #{$newId}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Perfil cadastrado.',
                    'type' => 'success',
                    'pos' => 'top-right',
                    'id' => $newId
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Perfil não foi cadastrado.',
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
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && self::isAdmin()) {
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
        if ($this->validatePostParams($_POST) && self::isAdmin()) {
            $entity = $this->model->getOne($_POST['id']);
            $this->view->entity = $entity;

            $this->render('update', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if ($this->validatePostParams($_POST) && self::isAdmin()) {
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
                $this->toLog("Atualizou o perfil de acesso: {$_POST['name']} #{$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Perfil atualizado.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Perfil não foi atualizado.',
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
        if ($this->validatePostParams($_POST) && self::isAdmin()) {
            $checkDeletePermission = $this->userModel->checkDeletePermission($_POST['id']);
            if ($checkDeletePermission) {
                $updateData = [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'deleted' => '1'
                ];

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update($updateData, $_POST['id'], 'id');

                if ($transaction) {
                    $this->toLog("Removeu o perfil de acesso: #{$_POST['id']}");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'Perfil removido.',
                        'type' => 'success',
                        'pos' => 'top-right'
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'O Perfil não foi removido.',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];
                }
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Há usuários com este nível vinculado, desvincule para excluir.',
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