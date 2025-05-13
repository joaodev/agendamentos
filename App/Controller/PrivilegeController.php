<?php

namespace App\Controller;

use App\Model\Privilege;
use App\Model\Role;
use Core\Controller\ActionController;
use Core\Db\Crud;

class PrivilegeController extends ActionController
{
    private mixed $model;
    private mixed $roleModel;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model     = new Privilege();
        $this->roleModel = new Role();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'privileges'),
            'canUpdate' => $this->getAcl('update', 'privileges'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
            $role = $this->roleModel->getOne($_POST['id']);
            $this->view->role = $role;
            
            $data = $this->model->getAllByRoleId($_POST['id']);
            $this->view->data = $data;

            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function changePrivilegeAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            $update = [
                'status' => $_POST['status'],
            ];

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($update, $_POST['id'], 'id');

            if ($transaction){
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Ação realizada.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'A ação não foi realizada.',
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