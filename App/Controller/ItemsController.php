<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use App\Model\Categories;
use App\Model\Items;
use App\Model\Subcategories;

class ItemsController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $categoriesModel;
    private mixed $subcategoriesModel;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Items();
        $this->categoriesModel = new Categories();
        $this->subcategoriesModel = new Subcategories();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'items'),
            'canCreate' => $this->getAcl('create', 'items'),
            'canUpdate' => $this->getAcl('update', 'items'),
            'canDelete' => $this->getAcl('delete', 'items'),
            'canCreateCategory' => $this->getAcl('create', 'categories'),
            'canCreateSubcategory' => $this->getAcl('create', 'subcategories'),
            'canCreateProvider' => $this->getAcl('create', 'providers'),
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
            $categories = $this->categoriesModel->findAllActives('id, title');
            $this->view->categories = $categories;

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
            
            $_POST['name'] = str_replace("'", "`", $_POST['name']);
            $_POST['description'] = str_replace("'", "`", $_POST['description']);
            $_POST['price'] = $this->moneyToDb($_POST['price']);

            if (!$_POST['subcategory_id']) {
                unset($_POST['subcategory_id']);
            }

            if (empty($_POST['provider_id'])) {
                unset($_POST['provider_id']);
            }

            if (!empty($_FILES) && !empty( $_FILES["file"])) {
                $image_name = $_FILES["file"]["name"];
                if ($image_name != null) {
                    $ext_img = explode(".", $image_name, 2);
                    $new_name  = md5($ext_img[0]) . '.' . $ext_img[1];
                    if ($ext_img[1] == 'jpg' || $ext_img[1] == 'jpeg'
                        || $ext_img[1] == 'png' || $ext_img[1] == 'gif') {
                        $tmp_name1  =  $_FILES["file"]["tmp_name"];
                        $new_image_name = md5($new_name . time()).'.png';
                        $dir1 = "../public/uploads/items/" . $new_image_name;

                        if (move_uploaded_file($tmp_name1, $dir1)) {
                            $_POST['file'] = $new_image_name;
                        } 

                    }
                }
            } else {
                unset($_POST['file']);
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);
            
            if ($transaction) {
                $newId = $transaction;

                $fields = "id, name, price";
                $entity = $this->model->find($newId, $fields, 'id');

                $this->toLog("Cadastrou o Produto {$newId}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Produto cadastrado.',
                    'type' => 'success',
                    'pos' => 'top-right',
                    'id' => $entity['id'],
                    'name' => $entity['name'] . ' | Valor: R$: ' . number_format($entity['price'], 2, ",",".")
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Produto não foi cadastrado.',
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
            $entity = $this->model->getOne($_POST['id']);
            $this->view->entity = $entity;

            $categories = $this->categoriesModel->findAllActives('id, title');
            $this->view->categories = $categories;

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
                        
            $_POST['name'] = str_replace("'", "`", $_POST['name']);
            $_POST['description'] = str_replace("'", "`", $_POST['description']);
            $_POST['price'] = $this->moneyToDb($_POST['price']);
            $_POST['updated_at'] = date('Y-m-d H:i:s');

            if (!$_POST['subcategory_id']) {
                unset($_POST['subcategory_id']);
            }

            if (empty($_POST['provider_id'])) {
                unset($_POST['provider_id']);
            }

            if (!empty($_FILES) && !empty( $_FILES["file"])) {
                $image_name = $_FILES["file"]["name"];
                if ($image_name != null) {
                    $ext_img = explode(".", $image_name, 2);
                    $new_name  = md5($ext_img[0]) . '.' . $ext_img[1];
                    if ($ext_img[1] == 'jpg' || $ext_img[1] == 'jpeg'
                        || $ext_img[1] == 'png' || $ext_img[1] == 'gif') {
                        $tmp_name1  =  $_FILES["file"]["tmp_name"];
                        $new_image_name = md5($new_name . time()).'.png';
                        $dir1 = "../public/uploads/items/" . $new_image_name;

                        if (move_uploaded_file($tmp_name1, $dir1)) {
                            $_POST['file'] = $new_image_name;
                        } 
                    }
                }
            } else {
                if (!empty($_POST['remove_image'])) {
                    $_POST['file'] = null;
                    unset($_POST['remove_image']);
                } else {
                    unset($_POST['file']);
                }
            }

            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['id'], 'id');

            if ($transaction) {
                $this->toLog("Atualizou o Produto {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Produto atualizado.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Produto não foi atualizado.',
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
                $this->toLog("Removeu o Produto {$_POST['id']}");
                $data = [
                    'title' => 'Sucesso!',
                    'msg' => 'Produto removido.',
                    'type' => 'success',
                    'pos' => 'top-right'
                ];
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Produto não foi removido.',
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
                        'text' => $entity['id']
                        .' - '. $entity['name'] 
                        . ' | Qtd.: ' . $entity['quantity'] 
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

    public function createCategoryAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canCreateCategory']) {
            $this->render('create-category', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createSubcategoryAction(): void
    {
        if (!empty($_POST['category_id']) && $this->validatePostParams($_POST) && $this->aclData['canCreateSubcategory']) {
            $category = $this->categoriesModel->getOneActiveById($_POST['category_id']);
            if ($category) {
                $this->view->category = $category;
                $this->render('create-subcategory', false);
            } else {
                $this->render('../error/not-found', false);
            }
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function createProviderAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canCreateProvider']) {
            $this->render('create-provider', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function subcategoriesAction(): void
    {
        if (!empty($_POST['category_id']) && $this->validatePostParams($_POST)) {
            $subcateogories = $this->subcategoriesModel->getAllActivesByCategory($_POST['category_id']);
            $this->view->subcategories = $subcateogories;
            $this->render('subcategories', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }
}