<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\Acl;
use App\Model\Privilege;
use App\Model\Role;
use App\Model\TimeSheets;
use App\Model\User;
use Core\Db\Bcrypt;

class UserController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $roleModel;
    private mixed $privilegeModel;
    private mixed $aclModel;
    private mixed $timeSheetsModel;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();

        $this->model = new User();
        $this->roleModel = new Role();
        $this->privilegeModel = new Privilege();
        $this->aclModel = new Acl();
        $this->timeSheetsModel = new TimeSheets();
        
        $this->aclData = [
            'canView' => $this->getAcl('view', 'user'),
            'canCreate' => $this->getAcl('create', 'user'),
            'canUpdate' => $this->getAcl('update', 'user'),
            'canDelete' => $this->getAcl('delete', 'user'),
            'canViewAcl' => $this->getAcl('view', 'privileges'),
            'canUpdateAcl' => $this->getAcl('update', 'privileges'),
            'canViewTimeSheets' => $this->getAcl('view', 'time-sheets'),
            'canCreateTimeSheets' => $this->getAcl('create', 'time-sheets'),
            'canUpdateTimeSheets' => $this->getAcl('update', 'time-sheets'),
            'canDeleteTimeSheets' => $this->getAcl('delete', 'time-sheets'),
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
            $roles = $this->roleModel->getAll();
            $this->view->roles = $roles;
            
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

            $passwordToken = $this->randomString(8);
            $_POST['password'] = Bcrypt::hash(md5($passwordToken));

            if ($_POST['salary']) {
                $_POST['salary'] = $this->moneyToDb($_POST['salary']);
            } else {
                unset($_POST['salary']);
            }

            if (empty($_POST['birthdate'])) {
                unset($_POST['birthdate']);
            }
            if (empty($_POST['end_date'])) {
                unset($_POST['end_date']);
            }

            $_POST['code'] = md5($this->randomString(10));
            $_POST['code_validated'] = 1;

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);

            if ($transaction) {
                $newId = $transaction;
                $privileges = $this->privilegeModel->getAllByRoleId($_POST['role_id']);

                foreach ($privileges as $privilege) {
                    $aclData = [
                        'user_id' => $newId,
                        'privilege_id' => $privilege['id']
                    ];

                    $crud->setTable($this->aclModel->getTable());
                    $crud->create($aclData);
                }

                $message = "<p>Você foi adicionado ao sistema, veja as informações de como acessar:</p>
                            <br>
                            <p>Email: <b>{$_POST['email']}</b></p>
                            <p>Senha: <b>$passwordToken</b></p>";

                $this->sendMail([
                    'title' => 'Senha de Acesso',
                    'message' => $message,
                    'name' => $_POST['name'],
                    'toAddress' => $_POST['email']
                ]);

                $this->toLog("Cadastrou o usuário: {$_POST['name']} #{$newId}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Usuário cadastrado.',
                    'type' => 'success',
                    'pos' => 'top-right',
                    'id' => $newId
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Usuário não foi cadastrado.',
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
            $roles = $this->roleModel->getAll();
            $this->view->roles = $roles;

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
            
            $entity = $this->model->getOne($_POST['id']);

            if (!empty($_POST['password'])) {
                unset($_POST['password']);
            }

            $_POST['updated_at'] = date('Y-m-d H:i:s');

            if (!empty($_POST['salary']) && $_POST['salary'] != '0,00') {
                $_POST['salary'] = $this->moneyToDb($_POST['salary']);
            } else {
                $_POST['salary'] = null;
            }

            if (empty($_POST['birthdate'])) {
                $_POST['birthdate'] = null;
            }

            if (empty($_POST['end_date'])) {
                $_POST['end_date'] = null;
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['id'], 'id');

            if ($transaction) {
                if (!empty($_POST['role_id']) && $entity['role_id'] != $_POST['role_id']) {
                    $this->reorganizeAcl($_POST['role_id'], $_POST['id']);
                }

                $this->toLog("Atualizou o usuário: {$_POST['name']} #{$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Usuário atualizado.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Usuário não foi atualizado.',
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
            if (($_POST['id'] != $_SESSION['COD'])) {
                $updateData = [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'deleted' => '1'
                ];

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update($updateData, $_POST['id'], 'id');

                if ($transaction) {
                    $this->toLog("Removeu o usuário: #{$_POST['id']}");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'Usuário removido.',
                        'type' => 'success',
                        'pos' => 'top-right'
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'O Usuário não foi removido.',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];
                }
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Usuário está logado.',
                    'type' => 'error',
                    'pos' => 'top-center'
                ];
            }
        } else {
            return false;
        }

        echo json_encode($data);
        return true;
    }

    public function aclAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canViewAcl']) {
            $user = $this->model->getOne($_POST['id']);
            $this->view->user = $user;

            $data = $this->aclModel->getUserPrivileges($_POST['id']);
            $this->view->data = $data;
            $this->render('acl', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function alterPrivilegeAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdateAcl']) {
            unset($_POST['target']);
            
            $crud = new Crud();
            $crud->setTable($this->aclModel->getTable());
            $transaction = $crud->update($_POST, $_POST['id'], 'id');

            if ($transaction) {
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Privilégio atualizado.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Privilégio não foi atualizado.',
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

    public function timeSheetsAction(): void
    {
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && $this->aclData['canViewTimeSheets']) {
            if (!empty($_POST['month'])) {
                $month = $_POST['month'];
            } else {
                $month = date('Y-m');
            }

            $this->view->month = self::formatMonth($month);
            
            $user = $this->model->getOne($_POST['id'], false);
            $this->view->user = $user;

            $data = $this->timeSheetsModel->getAllByUser($_POST['id'], $month);
            $this->view->data = $data;
            
            $this->render('time-sheet', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function timesheetDetailsAction(): void
    {
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && $this->aclData['canViewTimeSheets']) {
            $user = $this->model->getOne($_POST['user_id'], false);
            $this->view->user = $user;

            $entity = $this->timeSheetsModel->getOne($_POST['id']);
            $this->view->entity = $entity;

            $this->render('timesheet-details', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function timesheetCreateAction(): void
    {
        if (!empty($_POST['user_id']) && $this->validatePostParams($_POST) && $this->aclData['canCreateTimeSheets']) {
            $user = $this->model->getOne($_POST['user_id']);
            $this->view->user = $user;

            $this->render('timesheet-create', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function timesheetUpdateAction(): void
    {
        if (!empty($_POST['id']) && !empty($_POST['user_id']) && $this->validatePostParams($_POST) && $this->aclData['canUpdateTimeSheets']) {
            $user = $this->model->getOne($_POST['user_id']);
            $this->view->user = $user;

            $entity = $this->timeSheetsModel->getOne($_POST['id']);
            $this->view->entity = $entity;
            
            $this->render('timesheet-update', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function searchResponsibleAction(): bool
    {
        if (!empty($_POST)) {
            $res = [];
            if (!empty($_POST['term']) && strlen($_POST['term']) >= 1) {
                $data = $this->model->searchData($_POST['term']);

                foreach ($data as $entity) {
                    $res[] = [
                        'id' => $entity['id'],
                        'text' => $entity['id']
                        .' - '. $entity['name'] 
                        .' - '. $entity['job_role'] . ' (' . $entity['email'] . ')'
                    ];
                }
            }

            echo json_encode($res);
            return true;
        } else {
            return false;
        }
    }

    private function reorganizeAcl($role, $user): void
    {
        if (!empty($role) && !empty($user)) {
            if ($this->aclModel->cleanUserAcl($user)) {
                if ($this->privilegeModel->cleanUserPrivileges($user)) {
                    $crud = new Crud();
                    $privileges = $this->privilegeModel->getAllByRoleId($role);
                    foreach ($privileges as $privilege) {
                        $aclData = [
                            'user_id' => $user,
                            'privilege_id' => $privilege['id']
                        ];
                        
                        $crud->setTable($this->aclModel->getTable());
                        $crud->create($aclData);
                    }
                }
            }
        }
    }



    public function restoreProcessAction(): bool
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canUpdate']) {
            
            $entity = $this->model->getOne($_POST['id'], false);
            if ($entity) {

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update([
                    'deleted' => '0',
                    'status' => '1',
                    'updated_at' => date('Y-m-d H:i:s')
                ], $_POST['id'], 'id');

                if ($transaction) {
                    if (!empty($_POST['send_mail']) && $_POST['send_mail'] == 1) {
                        $url = baseUrl;
                        $message = "<h3>Acesso  restaurado:</h3>";
                        $message .= "<p><b>{$entity['name']}</b></p>";
                        $message .= "<p><a href='$url'>acesse a plataforma</a> para conferir.</p>";

                        $this->sendMail([
                            'title' => "Acesso restaurado",
                            'message' => $message,
                            'name' => $entity['name'],
                            'toAddress' => $entity['email']
                        ]);
                    }

                    $this->toLog("Restaurou o usuário {$_POST['id']}");
                    $data  = [
                        'title' => 'Sucesso!',
                        'msg'   => 'Usuário restaurado.',
                        'type'  => 'success',
                        'pos'   => 'top-right'
                    ];
                } else {
                    $data  = [
                        'title' => 'Erro!',
                        'msg' => 'O Usuário não foi restaurado.',
                        'type' => 'error',
                        'pos'   => 'top-center'
                    ];
                }

                echo json_encode($data);
                return true;
            } else {
                return false;
            }
        } else {
            return false;   
        }
    }
}