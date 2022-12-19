<?php

namespace App\Controller;

use Core\Controller\ActionController;
use PHPMailer;

class DemoController extends ActionController
{
    public function indexAction(): void
    {
        $config = [
            'primary_color' => '#004aad',
            'secondary_color' => '#000000'
        ];

        $seo = [
            'title' => 'SISTEMA DE AGENDAMENTOS - JA CONSULTORIATI'
        ];

        if (!empty($_POST)) {
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
                                    <td style="padding:0; text-align: center;">
                                        <table role="presentation" style="width:602px;border-collapse:collapse;border:1px solid #cccccc;border-spacing:0;text-align:center;">
                                            <tr>
                                                <td style=" text-align: center;padding:10px 0 10px 0;background:' .$config['primary_color'].';">
                                                    <h1 style="color: white; text-shadow: black 0.1em 0.1em 0.2em;">'.$seo['title'].'</h1>
                                                    <h2 style="color: white; text-shadow: black 0.1em 0.1em 0.2em;">Demonstração Solicitada</h2>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:36px 30px 42px 30px;">
                                                    <table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
                                                        <tr>
                                                            <td style="padding:0 0 10px 0;color:#153643;">
                                                                <h1>Uma pessoa solicitou a demonstração do sistema! </h1>
                                                                <br>
                                                                <hr>
                                                                <p><b>Nome:</b> ' . $_POST['name'] . '</p>
                                                                <p><b>Email:</b> ' . $_POST['email'] . '</p>
                                                                <hr>
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
                                                                    &reg; '.$seo['title'].'  | '.date('Y').'<br/>
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
            $mail->Host       = 'smtp.titan.email';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'contato@jaconsultoriati.com.br';
            $mail->Password   = 'MET&D9WKH6y<%8*U';
            $mail->Port       = 587;

            $mail->setFrom('contato@jaconsultoriati.com.br', $seo['title']);
            $mail->addAddress('contato@jaconsultoriati.com.br');

            $message = wordwrap($message, 70);

            $mail->isHTML();
            $mail->Subject = utf8_decode('AGENDAMENTOS - DEMONSTRAÇÃO SOLICITADA - ' . $seo['title']);
            $mail->Body    = utf8_decode($message);

            if ($mail->send()) {
                self::redirect('', 'enviado');
            } else {
                self::redirect('', 'nao-enviado');
            }
        } else {
            self::redirect('');
        }
    }
}