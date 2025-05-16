<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\Categories;
use App\Model\Subcategories;

class SubcategoriesController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $categoryModel;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Subcategories();
        $this->categoryModel = new Categories();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'subcategories'),
            'canCreate' => $this->getAcl('create', 'subcategories'),
            'canUpdate' => $this->getAcl('update', 'subcategories'),
            'canDelete' => $this->getAcl('delete', 'subcategories'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if (!empty($_POST['id']) && $this->validatePostParams($_POST) && $this->aclData['canView']) {
            $data = $this->model->getAllData($_POST['id']);
            $this->view->data = $data;

            $category = $this->categoryModel->getOne($_POST['id'], false);
            $this->view->category = $category;

            $this->view->category_id = $_POST['id'];

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
            
            $category = $this->categoryModel->getOne($_POST['category_id']);
            if ($category) {
                
                if ($this->model->subcategoryExists('title', $_POST['title'], 'id', null, $category['id'])) {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'Título já cadastrado, informe outro.',
                        'type' => 'error', 'pos' => 'top-center'
                    ];
    
                    echo json_encode($data);
                    return false;
                }
                
                $_POST['title'] = str_replace("'", "`", $_POST['title']);

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->create($_POST);

                if ($transaction) {
                    $this->toLog("Cadastrou a Subcategoria $transaction");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'Subcategoria cadastrada.',
                        'type' => 'success',
                        'pos' => 'top-right',
                        'id' => $transaction,
                        'name' => $_POST['title']
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'A Subcategoria não foi cadastrada.',
                        'type' => 'error',
                        'pos' => 'top-center'
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
            
            $category = $this->categoryModel->getOne($_POST['category_id']);
            if ($category) {

                if ($this->model->subcategoryExists('title', $_POST['title'], 'id', $_POST['id'], $category['id'])) {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'Título já cadastrado, informe outro.',
                        'type' => 'error', 'pos' => 'top-center'
                    ];
    
                    echo json_encode($data);
                    return false;
                }

                $_POST['title'] = str_replace("'", "`", $_POST['title']);
                $_POST['updated_at'] = date('Y-m-d H:i:s');

                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update($_POST, $_POST['id'], 'id');

                if ($transaction) {
                    $this->toLog("Atualizou a Subcategoria {$_POST['id']}");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'Subcategoria atualizada.',
                        'type' => 'success',
                        'pos' => 'top-right'
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'A Subcategoria não foi atualizada.',
                        'type' => 'error',
                        'pos' => 'top-center'
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
                $this->toLog("Removeu a Subcategoria {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Subcategoria removida.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'A Subcategoria não foi removida.',
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