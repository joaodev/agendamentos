<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;
use Core\Db\Crud;
use App\Interfaces\CrudInterface;
use PHPMailer;
use phpmailerException;

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
        $data = $this->model->getAll();
        $this->view->data = $data;
        $this->render('index', false);
    }

    public function createAction(): void
    {
        $this->render('create', false);
    }
    
    public function createProcessAction(): bool
    {
        if (!empty($_POST)) {
            $uuid = $this->model->NewUUID();
            $_POST['uuid'] = $uuid;
            $_POST['price'] = $this->moneyToDb($_POST['price']);
            $_POST['btn_link'] = base64_encode($_POST['btn_link']);
            
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->create($_POST);
           
            if ($transaction){
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
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
            $this->render('update', false);
        }
    }

    public function updateProcessAction(): bool
    {
        if (!empty($_POST)) {
            $_POST['updated_at'] = date('Y-m-d H:i:s');
            $_POST['price'] = $this->moneyToDb($_POST['price']);
            $_POST['btn_link'] = base64_encode($_POST['btn_link']);
            
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update($_POST, $_POST['uuid'], 'uuid');

            if ($transaction){
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
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
            $this->render('read', false);
        }
    }

	public function deleteAction(): bool
    {
        if (!empty($_POST)) {
            $crud = new Crud();
            $crud->setTable($this->model->getTable());
            $transaction = $crud->update([
                'deleted' => '1',
                'updated_at' => date('Y-m-d H:i:s')
            ],$_POST['uuid'], 'uuid');

            if ($transaction){
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
        $userPlans = $this->userPlansModel->getAllByUser($_SESSION['COD']);
        $this->view->user_plans = $userPlans;

        $data = $this->model->getAllPlans();
        $this->view->data = $data;

        $this->render('user-plans', false);
    }

    public function selectedPlanAction(): bool
    {
        if (!empty($_POST['uuid'])) {
            $postData = [
                'uuid' => $this->userPlansModel->NewUUID(),
                'user_uuid' => $_SESSION['COD'],
                'plan_uuid' => $_POST['uuid']
            ];

            $crud = new Crud();
            $crud->setTable($this->userPlansModel->getTable());
            $transaction = $crud->create($postData);

            if ($transaction) {
                $entity = $this->model->getOne($_POST['uuid']);
                $config = $this->getSiteConfig();

                $message = '<!DOCTYPE html>
                            <html lang="en" xmlns="http://www.w3.org/1999/xhtml">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width,initial-scale=1">
                                <meta name="x-apple-disable-message-reformatting">
                                <title></title>
                                <!--[if mso]>
                                <noscript>
                                    <xml>
                                        <o:OfficeDocumentSettings>
                                            <o:PixelsPerInch>96</o:PixelsPerInch>
                                        </o:OfficeDocumentSettings>
                                    </xml>
                                </noscript>
                                <![endif]-->
                                <style>
                                    table, td, div, h1, p {font-family: Arial, sans-serif;}
                                </style>
                            </head>
                            <body style="margin:0;padding:0;">
                                <table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;background:#ffffff;">
                                    <tr>
                                        <td style="padding:0; text-align: center;">
                                            <table role="presentation" style="width:602px;border-collapse:collapse;border:1px solid #cccccc;border-spacing:0;text-align:left;">
                                                <tr>
                                                    <td style="text-align: center; padding:10px 0 10px 0;background:'.$config['primary_color'].';">
                                                        <h1 style="color: white; text-shadow: black 0.1em 0.1em 0.2em;">'.$config['site_title'].'</h1>
                                                        <h2 style="color: white; text-shadow: black 0.1em 0.1em 0.2em;">Código de Verificação</h2>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:36px 30px 42px 30px;">
                                                        <table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
                                                            <tr>
                                                                <td style="padding:0 0 10px 0;color:#153643;">
                                                                    <p>Pedido para alteração de plano:</p>
                                                                    <p>Usuário: '.$_SESSION['NAME'].' - '.$_SESSION['EMAIL'].'</p>
                                                                    <p>Plano Selecionado: '.$entity['name'].'</p>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding:0;">
                                                                    <table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
                                                                        <tr>
                                                                            <td style="width:260px;padding:0;vertical-align:top;color:#153643;">
                                                                                <p style="margin:0 0 12px 0;font-size:11px;line-height:15px;font-family:Arial,sans-serif;">*Esta é uma mensagem automática, não responda este email.</p>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:30px;background:'.$config['primary_color'].';">
                                                        <table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;font-size:9px;font-family:Arial,sans-serif;">
                                                            <tr>
                                                                <td style="padding:0;width:100%; text-align: center;">
                                                                    <p style="margin:0;font-size:14px;line-height:16px;font-family:Arial,sans-serif;color:#ffffff;">
                                                                        &copy; ' . $config['site_title'] . '  | ' . date('Y') . '<br/>
                                                                    </p>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </body>
                            </html>';   

                $mail = new PHPMailer();
                $mail->isSMTP();                                            
                $mail->Host       = $config['mail_host'];
                $mail->SMTPAuth   = true;                                 
                $mail->Username   = $config['mail_username'];
                $mail->Password   = $config['mail_password'];
                $mail->Port       = $config['mail_port'];

                try {
                    $mail->setFrom($config['mail_from_address'], $config['site_title']);
                } catch (phpmailerException $e) {
                    $this->toLog("Erro ao definir destinatário: $e");
                }

                $mail->addAddress($config['mail_to_address']);
    
                $message = wordwrap($message, 70);
                $mail->isHTML();
                $mail->Subject = utf8_decode('Solicitação de Plano - ' . $config['site_title']);
                $mail->Body    = utf8_decode($message);

                try {
                    $mail->send();
                } catch (phpmailerException $e) {
                    $this->toLog("Erro ao enviar: $e");
                }

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

            if ($transaction){
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

    public function cancelPlanAction(): bool
    {
        if (!empty($_POST['uuid'])) {
            $crud = new Crud();
            $crud->setTable($this->userPlansModel->getTable());
            $transaction = $crud->update([
                'status' => '2',
                'canceled_at' => date('Y-m-d H:i:s')
            ],$_POST['uuid'], 'uuid');

            if ($transaction){
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
        if (!empty($_POST['uuid'])) {   
            $data = $this->userPlansModel->getAllUsersPlans($_POST['uuid']);
            $this->view->data = $data;
            $this->render('users-plans', false);
        }
    }
}