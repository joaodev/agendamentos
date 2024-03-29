<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;

class PlansController extends ActionController implements CrudInterface
{
    private mixed $model;
    private mixed $userPlansModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Plans", "app");
        $this->userPlansModel = Container::getClass("UserPlans", "app");
    }

    public function indexAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $data = $this->model->getAll();
            $this->view->data = $data;
            $this->render('index', false);
        }
    }

    public function createAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $this->render('create', false);
        }
    }
    
    public function createProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            $uuid = $this->model->NewUUID();
            $_POST['uuid'] = $uuid;
            $_POST['price'] = $this->moneyToDb($_POST['price']);
            $_POST['btn_link'] = base64_encode($_POST['btn_link']);
            
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);
           
            if ($transaction) {
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
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $_POST['price'] = $this->moneyToDb($_POST['price']);
            $_POST['btn_link'] = base64_encode($_POST['btn_link']);
            
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction) {
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
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
            $this->render('read', false);
        }
    }

	public function deleteAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'deleted' => '1',
                'updated_at' => date('Y-m-d H:i:s')
            ],$_POST['uuid'], 'uuid');

            if ($transaction) {
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
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $userPlans = $this->userPlansModel->getAllByUser($_SESSION['COD']);
            $this->view->user_plans = $userPlans;

            $data = $this->model->getAllPlans();
            $this->view->data = $data;

            $this->render('user-plans', false);
        }
    }

    public function selectedPlanAction(): bool
    {
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);

            $postData = [
                'uuid' => $this->userPlansModel->NewUUID(),
                'parent_uuid' => $_SESSION['COD'],
                'plan_uuid' => $_POST['uuid']
            ];

            $crud = new Crud();
            $crud->setTable($this->userPlansModel->getTable());
            $transaction = $crud->create($postData);

            if ($transaction) {
                $entity = $this->model->getOne($_POST['uuid']);
                $config = $this->getSiteConfig();

                $message = "<p>Pedido para alteração de plano:</p>
                                <p>Usuário: {$_SESSION['NAME']} - {$_SESSION['EMAIL']}</p>
                                <p>Plano Selecionado: {$entity['name']}</p>";

                $this->sendMail([
                    'title' => 'Solicitação de Plano',
                    'message' => $message,
                    'name' => "Administrador",
                    'toAddress' => $config['mail_to_address']
                ]);

                $this->toLog("Solicitação a alteração para o Plano {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Solicitação enviada, entraremos em contato assim que possível.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'A solicitação não foi realizada, tente novamente.',
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

    public function fileProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_FILES)) {
            if (!empty($_FILES) && !empty($_FILES["file"])) {
                $image_name = $_FILES["file"]["name"];
                if ($image_name != null) {

                    $dir1 = "../public/uploads/userplans/" . $_POST['order'];
                    if (!is_dir($dir1)) {
                        mkdir($dir1);
                    }

                    $ext_img = explode(".", $image_name, 2);
                    $new_name  = md5($ext_img[0]) . '.' . $ext_img[1];
                    if ($ext_img[1] == 'jpg' || $ext_img[1] == 'jpeg'
                        || $ext_img[1] == 'png' || $ext_img[1] == 'gif') {
                        $tmp_name1  =  $_FILES["file"]["tmp_name"];
                        $new_image_name = md5($new_name . time()).'.png';

                        if (move_uploaded_file($tmp_name1, $dir1 . '/' . $new_image_name)) {
                            $file = $new_image_name;
                        } 
                    }
               
                }
            } else {
                $file = "";
            }

            $crud = new Crud();
            $crud->setTable($this->userPlansModel->getTable());
            $transaction = $crud->update([
                'file' => $file,
                'status' => '3',
                'uploaded_at' => date('Y-m-d H:i:s')
            ],$_POST['order'], 'uuid');

            if ($transaction) {
                $this->toLog("Usuário anexou o comprovante ao Plano {$_POST['order']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Comprovante anexado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Comprovante não foi anexado.',
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

    public function renewProcessAction(): bool
    {
        if (!empty($_POST) && !empty($_FILES)) {
            if (!empty($_FILES) && !empty($_FILES["file_renew"])) {
                $image_name = $_FILES["file_renew"]["name"];
                if ($image_name != null) {

                    $dir1 = "../public/uploads/userplans/" . $_POST['order'];
                    if (!is_dir($dir1)) {
                        mkdir($dir1);
                    }

                    $ext_img = explode(".", $image_name, 2);
                    $new_name  = md5($ext_img[0]) . '.' . $ext_img[1];
                    if ($ext_img[1] == 'jpg' || $ext_img[1] == 'jpeg'
                        || $ext_img[1] == 'png' || $ext_img[1] == 'gif') {
                        $tmp_name1  =  $_FILES["file_renew"]["tmp_name"];
                        $new_image_name = md5($new_name . time()).'.png';

                        if (move_uploaded_file($tmp_name1, $dir1 . '/' . $new_image_name)) {
                            $file = $new_image_name;
                        } 
                    }
               
                }
            } else {
                $file = "";
            }

            $crud = new Crud();
            $crud->setTable($this->userPlansModel->getTable());
            $transaction = $crud->update([
                'file' => $file,
                'status' => '1',
                'uploaded_at' => date('Y-m-d H:i:s')
            ],$_POST['order'], 'uuid');

            if ($transaction) {
                $this->toLog("Usuário anexou o comprovante ao Plano {$_POST['order']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Operação confirmada.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Comprovante não foi anexado, tente novamente.',
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

    public function cancelPlanAction(): bool
    {
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $crud = new Crud();
            $crud->setTable($this->userPlansModel->getTable());
            $transaction = $crud->update([
                'status' => '2',
                'canceled_at' => date('Y-m-d H:i:s')
            ],$_POST['uuid'], 'uuid');

            if ($transaction) {
                $this->toLog("Usuário cancelou o Plano {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Plano cancelado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Plano não foi cancelado.',
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

    public function usersPlansAction(): void
    {
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {   
            $data = $this->userPlansModel->getAllUsersPlans($_POST['uuid']);
            $this->view->data = $data;

            $this->view->plan_uuid = $_POST['uuid'];

            $this->render('users-plans', false);
        }
    }

    public function deleteUserplanAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $crud = new Crud();
            $crud->setTable($this->userPlansModel->getTable());
            $transaction = $crud->delete($_POST['uuid'], 'uuid');

            if ($transaction) {
                $this->toLog("Removeu o Plano de Usuário {$_POST['uuid']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Plano de Usuário removido.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Plano de Usuário não foi removido.',
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

    public function deletePaymentfileAction(): bool
    {
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $crud = new Crud();
            $crud->setTable($this->userPlansModel->getTable());
            $transaction = $crud->update([
                'file' => null,
                'status' => '0',
                'uploaded_at' => null
            ],$_POST['uuid'], 'uuid');
           
            if ($transaction) {
                $this->toLog("Removeu o Comprovante de Pagamento {$_POST['file']}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Comprovante de Pagamento removido.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Comprovante de Pagamento não foi removido.',
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

    public function updateUserplanAction(): void
    {
        if (!empty($_POST['uuid']) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $plans = $this->model->getAll();
            $this->view->plans = $plans;
            
            $entity = $this->userPlansModel->getOne($_POST['uuid']);
            $this->view->entity = $entity;
            $this->render('update-userplan', false);
        }
    }

    public function processUserplanAction(): bool
    {
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            if (!empty($_POST['message'])) {
                $entity = $this->userPlansModel->getOne($_POST['uuid']);
                $url = baseUrl;
                $message = "<p>Você possui novas informações sobre o {$entity['planName']}.
                    <br><br>
                    {$_POST['message']} 
                    <br><br> 
                    <a href='$url'>acesse a plataforma</a> para conferir os detalhes.</p>"; 
                    
                $this->sendMail([
                    'title' => 'Informações - ' . $entity['planName'],
                    'message' => $message,
                    'name' => $entity['userName'],
                    'toAddress' => $entity['userEmail']
                ]);
            }

            $logMessage = $_POST['message'] ? ' com a mensagem: ' . $_POST['message'] : '';

            $updateData = [
                'plan_uuid' => $_POST['plan_uuid'],
                'status' => $_POST['status'],
                'renovation_at' => $_POST['renovation_at']
            ];

            $crud = new Crud();
            $crud->setTable($this->userPlansModel->getTable());
            $transaction = $crud->update($updateData, $_POST['uuid'], 'uuid');

            if ($transaction) {
                $this->toLog("Atualizou o Plano de Usuário {$_POST['uuid']}{$logMessage}");
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Plano de Usuário atualizado.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'O Plano de Usuário não foi atualizado.',
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

    public function requestAlterAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            $plans = $this->model->getAllActives();
            $this->view->plans = $plans;
            $this->render('request-alter', false);
        }
    }

    public function sendRequestAction(): bool
    {   
        if (!empty($_POST) && !empty($_POST['target']) && $this->targetValidated($_POST['target'])) {
            unset($_POST['target']);
            $config = $this->getSiteConfig();

            $message = "<p>Pedido para alteração de plano:</p>
                            <p>Usuário: {$_SESSION['NAME']} - {$_SESSION['EMAIL']}</p>
                            <p>Plano Selecionado: {$_POST['plan_uuid']}</p>
                            <p>Mensagem: {$_POST['description']}</p>";

            $sended = $this->sendMail([
                'title' => 'Solicitação para Alteração de Plano',
                'message' => $message,
                'name' => "Administrador",
                'toAddress' => $config['mail_to_address']
            ]);

            if ($sended) {
                $data  = [
                    'title' => 'Sucesso!', 
                    'msg'   => 'Solicitação enviada.',
                    'type'  => 'success',
                    'pos'   => 'top-right'
                ];
            } else {
                $data  = [
                    'title' => 'Erro!', 
                    'msg' => 'A solicitação não foi enviada.',
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