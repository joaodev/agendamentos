<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Bcrypt;
use Core\Db\Crud;
use Core\Di\Container;
use PHPMailer;
use phpmailerException;

class DemoController extends ActionController
{
    private mixed $model;
    private mixed $roleModel;
    private mixed $privilegeModel;
    private mixed $aclModel;

    public function __construct()
    {
        parent::__construct();

        $this->model = Container::getClass("User", "app");
        $this->roleModel = Container::getClass("Role", "app");
        $this->privilegeModel = Container::getClass("Privilege", "app");
        $this->aclModel = Container::getClass("Acl", "app");
    }

    public function indexAction(): void
    {
        if (!empty($_POST)) {
            if ($_POST['password'] != $_POST['confirmation']) {
                self::redirect('', 'senhas-incorretas&n=' . $_POST['name'] . '&e=' . $_POST['email']);
            } else {
                $role = '4c139b44-a57f-4c53-0aa3-758080e8c95b';
                if ($this->roleModel->getOne($role)) {
                    unset($_POST['confirmation']);
                    $passwordMd5 = $_POST['password'];
                    $_POST['password'] = Bcrypt::hash($_POST['password']);
                    $_POST['role_uuid'] = $role;

                    $_POST['uuid'] = $this->model->NewUUID();
                    $crud = new Crud();
                    $crud->setTable($this->model->getTable());
                    $transaction = $crud->create($_POST);

                    if ($transaction) {
                        $privileges = $this->privilegeModel->getAllByRoleUuid($_POST['role_uuid']);
                        foreach ($privileges as $privilege) {
                            $aclData = [
                                'uuid' => $this->privilegeModel->NewUUID(),
                                'user_uuid' => $_POST['uuid'],
                                'privilege_uuid' => $privilege['uuid']
                            ];
        
                            $crud->setTable($this->aclModel->getTable());
                            $crud->create($aclData);
                        }

                        $credentials = $this->model
	        					->findByCrenditials($_POST['email'], $passwordMd5); 

                        if ($credentials) {
                            $_SESSION['COD']         = $credentials['uuid'];
                            $_SESSION['NAME']        = $credentials['name'];
                            $_SESSION['EMAIL']       = $credentials['email'];
                            $_SESSION['PASS']        = $credentials['password'];
                            $_SESSION['ROLE_NAME']   = $credentials['role'];
                            $_SESSION['ROLE']        = $credentials['role_uuid'];
                            $_SESSION['ROLE_ADM']    = $credentials['is_admin'];
                            $_SESSION['PHOTO']       = $credentials['file'];

                            $this->toLog("Usuário cadastrado: {$_POST['name']} #{$_POST['uuid']}, aguardando validação do token");
                            self::registerToken($credentials['uuid']);

                            self::redirect('');
                            die;
                        } else {
                            self::redirect('', 'usuario-invalido');
                        }
                    } else {
                        self::redirect('', 'nao-enviado&n=' . $_POST['name'] . '&e=' . $_POST['email']);
                    }
                } else {
                    self::redirect('', 'nao-enviado&n=' . $_POST['name'] . '&e=' . $_POST['email']);
                }
            }
        }
    }

    private function registerToken($uuid)
    {
        $user = $this->model->find($uuid, 'uuid, name, email', 'uuid');
        if ($user) {
            $code = $this->randomString();

            $crud = new Crud();
            $crud->setTable($this->model->getTable());

            $updateCode = $crud->update([
                'code' => md5($code),
                'code_validated' => '0',
                'updated_at' => date('Y-m-d H:i:s')
            ], $uuid, 'uuid');

            if ($updateCode) {
                $config = $this->getSiteConfig();

                $message = '<!DOCTYPE html>
                                <html lang="en" xmlns="http://www.w3.org/1999/xhtml" >
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
                                            <td style=" text-align: center;padding:0;">
                                                <table role="presentation" style="width:602px;border-collapse:collapse;border:1px solid #cccccc;border-spacing:0;text-align:center;">
                                                    <tr>
                                                        <td style=" text-align: center;padding:10px 0 10px 0;background:' .$config['primary_color'].';">
                                                            <h1 style="color: white; text-shadow: black 0.1em 0.1em 0.2em;">'.$config['site_title'].'</h1>
                                                            <h2 style="color: white; text-shadow: black 0.1em 0.1em 0.2em;">Token de Acesso</h2>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:36px 30px 42px 30px;">
                                                            <table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
                                                                <tr>
                                                                    <td style="padding:0 0 10px 0;color:#153643;">
                                                                        <p>Olá, '.$user['name'].', tudo bem? </p>
                                                                        <p>Este é o seu token para validar seu acesso ao sistema:</p>
                                                                        <br>
                                                                        <h1 style="font-size:24px;margin:0 0 20px 0;font-family:Arial,sans-serif;">'.$code.'</h1>
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

                $mail->addAddress($user['email']);

                $message = wordwrap($message, 70);
                $mail->isHTML();
                $mail->Subject = utf8_decode('Token de Acesso | ' . $config['site_title']);
                $mail->Body    = utf8_decode($message);

                try {
                    if ($mail->send()) {
                        $this->toLog("{$user['name']} solicitou um token para acessar o sistema.");
                    } else {
                        $this->toLog("{$user['name']} tentou solicitar um token para acessar o sistema.");
                    }
                } catch (phpmailerException $e) {
                    $this->toLog("Erro ao enviar: $e");
                }
            }
        }
    }
}