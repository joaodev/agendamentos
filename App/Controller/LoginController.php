<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Db\Bcrypt;
use Core\Db\Crud;
use Core\Di\Container;
use PHPMailer;
use phpmailerException;

class LoginController extends ActionController
{
	private mixed $userModel;
    private mixed $devicesModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = Container::getClass("User", "app");
        $this->devicesModel = Container::getClass("UserDevices", "app");

        if (!empty($_POST)) {
            self::dataValidation($_POST);
        }
    }

    public function indexAction(): void
    {
        if (!empty($_SESSION['EMAIL']) && !empty($_SESSION['PASS']) && empty($_SESSION['TOKEN'])) {
            $this->render('token', false);
        } else {
            if (session_id()) {
                session_destroy();
            }
            $this->render('index', false);
        }
    }

    public function authAction()
    {
    	if (!empty($_POST)) {
        	$email = $_POST['email'];
	        $password  = $_POST['password'];

	        $credentials = $this->userModel
	        					->findByCrenditials($email, $password); 

	        if ($credentials) {
                $_SESSION['COD']         = $credentials['uuid'];
	            $_SESSION['NAME']        = $credentials['name'];
	            $_SESSION['EMAIL']       = $credentials['email'];
	            $_SESSION['PASS']        = $credentials['password'];
	            $_SESSION['ROLE_NAME']   = $credentials['role'];
	            $_SESSION['ROLE']        = $credentials['role_uuid'];
	            $_SESSION['ROLE_ADM']    = $credentials['is_admin'];
	            $_SESSION['PHOTO']       = $credentials['file'];

                $currentDevice = $_SERVER["HTTP_USER_AGENT"];
                $checkedDevices = $this->devicesModel->findAllBy('user_device', 'user_uuid', $credentials['uuid']);

                $devices = [];
                foreach ($checkedDevices as $checkedDevice) {
                    $devices[] = $checkedDevice['user_device'];
                }

                if (in_array($currentDevice, $devices)) {
                    $_SESSION['TOKEN'] = $credentials['code'];
                    $this->toLog("Fez login no sistema usando um dispositivo registrado.");
                } else {
                    $this->toLog("Fez login no sistema, aguardando validação do token");
                    self::registerToken($credentials['uuid']);
                }

	            self::redirect('');
                die;
	        } else {
	            self::redirect('', 'usuario-invalido');
	        }
        } else {
        	self::redirect('');
        }
    }

    public function logoutAction()
    {
        $this->toLog("Fez logout do sistema");

        unset($_SESSION['COD']);
        unset($_SESSION['NAME']);
        unset($_SESSION['EMAIL']);
        unset($_SESSION['PASS']);
        unset($_SESSION['ROLE_NAME']);
        unset($_SESSION['ROLE']);
        unset($_SESSION['PHOTO']);
        session_destroy();

        self::redirect('');
    }

    public function forgotPasswordAction()
    {
        if (!empty($_POST)) {
            $userUuid = $this->userModel->getUuidByField('email', $_POST['email'], 'uuid');
            if ($userUuid > 0) {
                $user = $this->userModel->find($userUuid, 'uuid, name, email', 'uuid');
                $code = $this->randomString();

                $crud = new Crud();
                $crud->setTable($this->userModel->getTable());
                
                $updateCode = $crud->update([
                    'code' => md5($code),
                    'updated_at' => date('Y-m-d H:i:s')
                ], $userUuid, 'uuid');

                if ($updateCode) {
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
                                                                        <p>Olá, '.$user['name'].', tudo bem? </p>
                                                                        <p>Este é o seu código para validar sua alteração de senha:</p>
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
                    $mail->Subject = utf8_decode('Código de Verificação - ' . $config['site_title']);
                    $mail->Body    = utf8_decode($message);

                    try {
                        $mail->send();
                    } catch (phpmailerException $e) {
                        $this->toLog("Erro ao enviar: $e");
                    }

                    $this->toLog("{$user['name']} solicitou um código para recuperação de senha.");
                }
            } 
            
            self::redirect('validar-codigo');
        } else {
            $this->render('forgot-password', false);
        }
    }
    
    public function codeValidationAction()
    {
        if (!empty($_POST)) {
            $code = ($_POST['code']);
            $userUuid = $this->userModel->getUuidByField('code', $code, 'uuid');
            if ($userUuid > 0) {
                $crud = new Crud();
                $crud->setTable($this->userModel->getTable());
                $validatedCode = $crud->update([
                    'code_validated' => '1',
                    'updated_at' => date('Y-m-d H:i:s')
                ], $userUuid, 'uuid');
    
                if ($validatedCode) {
                    $this->view->code = $code;
                    $this->render('change-password', false);
                } else {
                    self::redirect('validar-codigo', 'codigo-invalido');
                }
            } else {
                self::redirect('validar-codigo', 'codigo-invalido');
            }
        } else {
            $this->render('code-validation', false);
        }
    }

    public function changePasswordAction()
    {
        if (!empty($_POST)) {
            if (empty($_POST['info'])) {
                self::redirect('validar-codigo', 'codigo-invalido');
            } else {
                if ($_POST['password'] != $_POST['confirmation']) {
                    $this->view->code = $_POST['info'];
                    $this->view->errorPasswords = 'Senhas incorretas!';
                    $this->render('change-password', false);
                } else {
                    $userUuid = $this->userModel->getUuidByField('code', $_POST['info'], 'uuid');
                    if ($userUuid > 0) {
                        $user = $this->userModel->find($userUuid, 'uuid, email, password, code, code_validated', 'uuid');
                        if ($user['code'] != $_POST['info'] || $user['code_validated'] != '1') {
                            self::redirect('validar-codigo', 'codigo-invalido');
                        } else {
                            $crud = new Crud();
                            $crud->setTable($this->userModel->getTable());
                            
                            $newPassword = Bcrypt::hash($_POST['password']);
                            $updatedPassword = $crud->update([
                                'password' => $newPassword,
                                'updated_at' => date('Y-m-d H:i:s')
                            ], $user['uuid'], 'uuid');

                            if ($updatedPassword) {   
                                $credentials = $this->userModel
	        					    ->authByCrenditials($user['email'], $newPassword, $_POST['info']);
                            
                                if ($credentials) {
                                    $_SESSION['COD']         = $credentials['uuid'];
                                    $_SESSION['NAME']        = $credentials['name'];
                                    $_SESSION['EMAIL']       = $credentials['email'];
                                    $_SESSION['PASS']        = $newPassword;
                                    $_SESSION['ROLE_NAME']   = $credentials['role'];
                                    $_SESSION['ROLE']        = $credentials['role_uuid'];
                                    $_SESSION['ROLE_ADM']    = $credentials['is_admin'];
                                    $_SESSION['PHOTO']       = $credentials['file'];
                                    $_SESSION['TOKEN']       = $_POST['info'];
                                    
                                    $this->toLog("Atualizou a senha e fez o login no sistema.");

                                    self::redirect('');
                                } else {
                                    self::redirect('', 'usuario-invalido');
                                }
                            } else {
                                self::redirect('validar-codigo', 'codigo-invalido');
                            }
                        }
                    } else {
                        self::redirect('validar-codigo', 'codigo-invalido');
                    }
                }
            }
        } else {
            self::redirect('validar-codigo', 'codigo-invalido');
        }
    }

    public function tokenAuthAction()
    {
        if (!empty($_POST['token'])) {
            $code = $_POST['token'];
            $userUuid = $this->userModel->getUuidByField('code', $code, 'uuid');
            if ($userUuid > 0) {
                $newData = [
                    'code_validated' => '1',
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if (!empty($_POST['remember']) && $_POST['remember'] == 1) {
                    $crudDevice = new Crud();
                    $crudDevice->setTable($this->devicesModel->getTable());
                    $crudDevice->create([
                        'uuid' => $this->devicesModel->NewUUID(),
                        'user_uuid' => $userUuid,
                        'user_device' => $_SERVER["HTTP_USER_AGENT"]
                    ]);
                }

                $crud = new Crud();
                $crud->setTable($this->userModel->getTable());
                $validatedCode = $crud->update($newData, $userUuid, 'uuid') ;

                if ($validatedCode) {
                    $_SESSION['TOKEN'] = $code;
                    self::redirect('');
                } else {
                    self::redirect('', 'token-invalido');
                }
            } else {
                self::redirect('', 'token-invalido');
            }
        } else {
            self::redirect('');
        }
    }

    private function registerToken($uuid)
    {
        $user = $this->userModel->find($uuid, 'uuid, name, email', 'uuid');
        if ($user) {
            $code = $this->randomString();

            $crud = new Crud();
            $crud->setTable($this->userModel->getTable());

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

    public function tokenCancelAction()
    {
        unset($_SESSION['PASS']);
        unset($_SESSION['EMAIL']);
        unset($_SESSION['TOKEN']);
        session_destroy();
        self::redirect('');
    }
}