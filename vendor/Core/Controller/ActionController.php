<?php

namespace Core\Controller;

use App\Model\Acl;
use App\Model\BudgetMessages;
use App\Model\BudgetResponsibles;
use App\Model\Budgets;
use App\Model\ChangesHistoric;
use App\Model\Config;
use App\Model\Customers;
use App\Model\Expenses;
use App\Model\Financial;
use App\Model\Os;
use App\Model\OsMessages;
use App\Model\Purchases;
use App\Model\Sales;
use App\Model\Schedules;
use App\Model\SupportMessages;
use App\Model\SystemMessages;
use App\Model\TaskMessages;
use App\Model\TaskResponsibles;
use App\Model\Tasks;
use App\Model\User;
use App\Model\SystemNotifications;
use Client\Model\ClientMessages;
use Client\Model\ClientNotifications;
use Core\Db\Bcrypt;
use Core\Db\SecurityFilter;
use Core\Db\Logs;
use Core\Db\Crud;
use Core\Di\Container;
use stdClass;
use PHPMailer;
use phpmailerException;

class ActionController
{
    protected mixed $action;
    protected mixed $namespace;
    protected mixed $controller;
    protected mixed $view;

    public function __construct()
    {
        @session_start();

        if (!empty($_GET)) {
            self::dataValidation($_GET);
        }

        if (!empty($_POST)) {
            foreach ($_POST as $key => $value) {
                $_POST[$key] = str_replace("'", "`", $_POST[$key]);
            }

            $data = $_POST;
            if (!empty($data['description'])) unset($data['description']);
            if (!empty($data['description_1'])) unset($data['description_1']);
            if (!empty($data['description_2'])) unset($data['description_2']);
            if (!empty($data['mail_password'])) unset($data['mail_password']);
            if (!empty($data['password'])) unset($data['password']);
            if (!empty($data['confirmation'])) unset($data['confirmation']);

            self::dataValidation($data);
        }
        
        $this->view = new stdClass();
        
        $this->view->isAdmin = self::isAdmin();
    }

    public function indexAction(): void
    {
        $this->render('index', false);
    }

    public function render(string $action, bool $layout = true): void
    {
        $this->action = $action;

        $current = get_class($this);
        $exp_current_class = explode("\\", $current, 3);

        $this->namespace = $exp_current_class[0];
        $this->controller = strtolower($exp_current_class[2]);

        if ($layout == true && file_exists("../" . $this->namespace . "/view/layout/layout.phtml")) {
            include_once("../" . $this->namespace . "/view/layout/layout.phtml");
        } else {
            $this->content();
        }
    }

    public function content(): void
    {
        $class_name = str_replace("controller", "", $this->controller);
        include_once('../' . $this->namespace . '/view/' . $class_name . '/' . $this->action . '.phtml');
    }

    public static function redirect(string $module, string $msgCallback = null): void
    {
        $callback = "";

        if (!empty($msgCallback)) {
            $callback .= "?msg=" . $msgCallback;
        }

        header("Location: " . baseUrl . $module . $callback);
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function randomString($size = null): string
    {
        if ($size) {
            $hashLength = $size;
        } else {
            $hashLength = 6;
        }

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randstring = '';
        for ($i = 0; $i < $hashLength; $i++) {
            $randstring .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randstring;
    }

    public function formatDate(string $input_date = null): string
    {
        if (!empty($input_date)) {
            $exp = explode("-", $input_date, 3);
            $date = $exp[2] . '/' . $exp[1] . '/' . $exp[0];
            return $date;
        } else {
            return "";
        }
    }

    public function formatDateTime(string $input_date_time = null, bool $time = true): string
    {
        if (empty($input_date_time)) {
            return '';
        } else {
            $exp_dt = explode(" ", $input_date_time, 2);
            $exp_date = explode("-", $exp_dt[0], 3);
            $date = $exp_date[2] . '/' . $exp_date[1] . '/' . $exp_date[0];

            if ($time == true) {
                $exp_time = explode(":", $exp_dt[1], 3);
                return $date . ' ' . $exp_time[0] . ':' . $exp_time[1];
            } else {
                return $date;
            }
        }
    }

    public function formatDateTimeWithLabels(string $input_date_time, bool $time = true): string
    {
        if (empty($input_date_time)) {
            return $input_date_time;
        } else {
            $exp_dt = explode(" ", $input_date_time, 2);
            $exp_date = explode("-", $exp_dt[0], 3);
            $date = $exp_date[2] . '/' . $exp_date[1] . '/' . $exp_date[0];

            if ($time == true) {
                $exp_time = explode(":", $exp_dt[1], 3);
                return $date . ' às ' . $exp_time[0] . ':' . $exp_time[1];
            } else {
                return $date;
            }
        }
    }

    public function moneyToDb(string $value): array|string
    {
        $value = trim($value);
        $value = str_replace(".", "", $value);
        $value = str_replace(",", ".", $value);
        return $value;
    }

    public static function dataValidation(array $data): void
    {
        $securityFilter = new SecurityFilter();
        foreach ($data as $item) {
            if (!$securityFilter->secureData($item)) {
                session_destroy();
                self::redirect('');
            }
        }
    }

    public function toLog(string $msg): bool|string|null
    {
        $log = new Logs();
        return $log->toLog($msg);
    }

    public function inputMasked(string $val, string $mask): string
    {
        $maskared = '';
        if (!empty($val)) {
            $k = 0;
            for ($i = 0; $i <= strlen($mask) - 1; $i++) {
                if ($mask[$i] == '#') {
                    if (isset($val[$k])) {
                        $maskared .= $val[$k++];
                    }
                } else {
                    if (isset($mask[$i])) {
                        $maskared .= $mask[$i];
                    }
                }
            }
        }

        return $maskared;
    }

    public function dateToTimestamp(string $data): bool|int
    {
        $ano = substr($data, 6, 4);
        $mes = substr($data, 3, 2);
        $dia = substr($data, 0, 2);
        return mktime(0, 0, 0, $mes, $dia, $ano);
    }

    public function acl(string $roleId, string $resourceId, string $moduleId)
    {
        $aclModel = new Acl();
        return $aclModel->getGrantedPrivilege($_SESSION['COD'], $roleId, $resourceId, $moduleId);
    }

    public function getSiteConfig()
    {
        $configModel = new Config();
        $configData = $configModel->getEntity();

        return $configData;
    }

    public function getUser(string $id)
    {
        $userModel = new User();
        $userData = $userModel->getOne($id);
        return $userData;
    }

    public function getCustomer(string $id)
    {
        $customerModel = new Customers();
        $customerData = $customerModel->getOne($id);
        return $customerData;
    }

    public function slugGenerator(string $title): array|string|null
    {
        $slug = preg_replace('/[\@\.\;\"]+/', '', $title);
        $slug = str_replace(" ", "-", $slug);
        $slug = str_replace("&", "", $slug);
        $slug = str_replace("ç", "c", $slug);
        $slug = str_replace("Ç", "c", $slug);
        $slug = str_replace("/", "", $slug);
        $slug = str_replace("'", "", $slug);
        $slug = str_replace(". ", "", $slug);
        $slug = strtolower($slug);

        return preg_replace(
            array(
                "/(á|à|ã|â|ä)/",
                "/(Á|À|Ã|Â|Ä)/",
                "/(é|è|ê|ë)/",
                "/(É|È|Ê|Ë)/",
                "/(í|ì|î|ï)/",
                "/(Í|Ì|Î|Ï)/",
                "/(ó|ò|õ|ô|ö)/",
                "/(Ó|Ò|Õ|Ô|Ö)/",
                "/(ú|ù|û|ü)/",
                "/(Ú|Ù|Û|Ü)/",
                "/(ñ)/",
                "/(Ñ)/"
            ),
            explode(" ", "a A e E i I o O u U n N"),
            $slug
        );
    }

    public function removeSpecialChars(string $string): string
    {
        $slug = ($string);
        $slug = str_replace("ç", "c", $slug);
        $slug = str_replace("Ç", "c", $slug);

        $slug = preg_replace(
            array(
                "/(á|à|ã|â|ä)/",
                "/(Á|À|Ã|Â|Ä)/",
                "/(é|è|ê|ë)/",
                "/(É|È|Ê|Ë)/",
                "/(í|ì|î|ï)/",
                "/(Í|Ì|Î|Ï)/",
                "/(ó|ò|õ|ô|ö)/",
                "/(Ó|Ò|Õ|Ô|Ö)/",
                "/(ú|ù|û|ü)/",
                "/(Ú|Ù|Û|Ü)/",
                "/(ñ)/",
                "/(Ñ)/"
            ),
            explode(" ", "a A e E i I o O u U n N"),
            $slug
        );

        return strtoupper($slug);
    }

    public function formatCellphone(string $cellphone): ?string
    {
        if (!empty($cellphone)) {

            $cellphone = str_replace('(', '', $cellphone);
            $cellphone = str_replace(')', '', $cellphone);
            $cellphone = str_replace('-', '', $cellphone);

            return '55' . trim($cellphone);
        } else {
            return null;
        }
    }

    public function resourceCodes(string $key): string
    {
        $codes = [
            'view' => '1',
            'create' => '2',
            'update' => '3',
            'delete' => '4'
        ];

        return $codes[$key];
    }

    public function moduleCodes(string $key): string
    {
        $codes = [
            'user' => '1',
            'privileges' => '2',
            'configs' => '3',
            'logs' => '4',
            'customers' => '5',
            'payment-types' => '6',
            'smtp' => '7',
            'services' => '8',
            'items' => '9',
            'categories' => '12',
            'subcategories' => '13',
            'expenses' => '14',
            'financial' => '18',
            'reports' => '24',
            'purchases' => '25',
            'schedules' => '26',
            'support' => '28',
            'support-messages' => '31'
        ];

        return $codes[$key];
    }

    public static function isAdmin(): bool
    {
        if (!$_SESSION) {
            return false;
        }
        
        if (!empty($_SESSION['COD'])) {
            $userModel = new User();
            $getUser = $userModel->getOne($_SESSION['COD']);
            
            if (!$getUser) {
                return false;
            }

            
            if ($_SESSION['ROLE_ADM'] == 0) {
                return false;
            } else {
                if ($_SESSION['ROLE_ADM'] === $getUser['is_admin']) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    public function getAcl($resource, $module)
    {
        return self::isAdmin() || $this->acl(
            $_SESSION['ROLE'], 
            $this->resourceCodes($resource), 
            $this->moduleCodes($module)
        );
    }

    public function getTotalExpenses(): array
    {
        $expensesModel = new Expenses();
        return [
            'totalByStatus_1' => $expensesModel->getTotalByStatus(1),
            'totalByStatus_2' => $expensesModel->getTotalByStatus(2),
            'totalDelayed' => $expensesModel->getDataDelayed(
                1, 'expense_date', 'expenses'
            )
        ];
    }

    public function getTotalExpensesByMonth(string $status, string $month): string
    {
        $expensesModel = new Expenses();
        return $expensesModel->getTotalByStatusByMonth($status, $month);
    }

    public function getTotalExpensesByMonthAndCustomer(string $month, string $customerId): array
    {
        $expensesModel = new Expenses();
        return [
            'total_1' => $expensesModel->getTotalExpensesByMonthAndCustomer(2, 1, $month, $customerId),
            'total_2' => $expensesModel->getTotalExpensesByMonthAndCustomer(2, 2, $month, $customerId),
            'totalDelayed' => $expensesModel->getExpensesDelayedByCustomer(
                2, 1, 'expense_date', 'expenses', $customerId
            )
        ];
    }

    public function getTotalExpensesByCustomer(string $type, string $status, string $customerId): array
    {
        $expensesModel = new Expenses();
        return [
            'totalByStatus' => $expensesModel->getTotalExpensesByCustomer($type, $status, $customerId),
            'totalDelayed' => $expensesModel->getExpensesDelayedByCustomer(
                $type, $status, 'expense_date', 'expenses', $customerId
            )
        ];
    }

    public function formatMonth(string $month): string
    {
        $exp = explode('-', $month, 2);
        return $exp[1] . '/' . $exp[0];
    }

    public function formatTime(string $time): string|bool
    {
        $exp = explode(':', $time, 3);
        return $exp[0] . ':' . $exp[1];
    }

    private function sumHours($firstHour, $secondHour): string
    {
        $baseDate = date('Y-m-d');
        $baseTime = strtotime($baseDate . ' 00:00:00');

        $firstTime = strtotime($baseDate . ' ' . $firstHour) - $baseTime;
        $secondTime = strtotime($baseDate . ' ' . $secondHour) - $baseTime;

        $resultTime = $firstTime + $secondTime;
        return date('H:i:s', $baseTime + $resultTime);
    }

    private function reduceHours($firstHour, $secondHour): string
    {
        $baseDate = date('Y-m-d');
        $baseTime = strtotime($baseDate . ' 00:00:00');

        $firstTime = strtotime($baseDate . ' ' . $firstHour) - $baseTime;
        $secondTime = strtotime($baseDate . ' ' . $secondHour) - $baseTime;

        $resultTime = $firstTime - $secondTime;
        return date('H:i:s', $baseTime - $resultTime);
    }

    public function getHours($baseDate, $start_time, $lunch_start_time, $lunch_end_time, $end_time): string
    {
        $morning = null;
        $afternoon = null;
        $fullTime = null;

        if (!empty($start_time) && !empty($lunch_start_time)):
            $morning = $this->reduceHours($start_time, $lunch_start_time);
        endif;

        if (!empty($lunch_end_time) && !empty($end_time)):
            $afternoon = $this->reduceHours($lunch_end_time, $end_time);
        endif;

        if (
            !empty($start_time)
            && empty($lunch_start_time)
            && empty($lunch_end_time)
            && !empty($end_time)
        ):
            $fullTime = $this->reduceHours($start_time, $end_time);
        endif;

        if (!empty($fullTime)):
            return $fullTime;
        else:
            if (!empty($morning) && !empty($afternoon) && $morning != '00:00:00' && $afternoon != '00:00:00'):
                return $this->formatTime($this->sumHours($morning, $afternoon));
            endif;
        endif;

        return 'Pendente';
    }

    public function expenseLog(array $params): bool
    {
        $expensesModel = new Expenses();
        return $expensesModel->saveExpense($params);
    }

    public function financialLog(array $params): bool
    {
        $financialModel = new Financial();
        return $financialModel->saveFinancialLog($params);
    }

    public function getTotalFinancialByMonth(string $status, string $month): float|string
    {
        $financialModel = new Financial();
        return $financialModel->getTotalFinancialByMonth($status, $month);
    }

    public function sendNotification(array $params): bool
    {
        if ($params 
            && (!empty($params['schedule_id']) || !empty($params['task_id'])
                || !empty($params['prospect_id']) || !empty($params['budget_id'])
                || !empty($params['purchase_id']) || !empty($params['expense_id'])
                || !empty($params['os_id']) || !empty($params['support_id']))
                || !empty($params['sale_id']) || !empty($params['time_sheet_id'])
                || !empty($params['item_control_id'])
            && !empty($params['description'])
            && (!empty($params['user_id']) || !empty($params['customer_id']))
        ) {
            $notificationsModel = new SystemNotifications();

            $crud = new Crud();
            $crud->setTable($notificationsModel->getTable());
            $crud->create($params);
            
            return true;
        } else {
            return false;
        }
    }

    public function notifyMessage(array $params): bool
    {
        if ($params 
            && (!empty($params['task_id'])
                || !empty($params['prospect_id']) || !empty($params['budget_id'])
                || !empty($params['os_id']) || !empty($params['support_id']))
            && !empty($params['description'])
            && (!empty($params['user_id']) || !empty($params['customer_id']))
        ) {
            $messagesModel = new SystemMessages();

            $crud = new Crud();
            $crud->setTable($messagesModel->getTable());
            $t = $crud->create($params);

            return true;
        } else {
            return false;
        }
    }

    public function saveHistoric(array $params): bool
    {
        if ($params 
            && (!empty($params['schedule_id']) || !empty($params['task_id']) 
                || !empty($params['expense_id']) || !empty($params['os_id']) 
                || !empty($params['purchase_id']) || !empty($params['support_id']) 
                || !empty($params['prospect_id']) || !empty($params['budget_id']))
            && !empty($params['status'])
            && (!empty($params['user_id']) || !empty($params['customer_id']))
        ) {
            $historicModel = new ChangesHistoric();
            $postData = ['status' => $params['status']];

            if (!empty($params['schedule_id'])) $postData['schedule_id'] = $params['schedule_id'];
            elseif (!empty($params['task_id'])) $postData['task_id'] = $params['task_id'];
            elseif (!empty($params['expense_id'])) $postData['expense_id'] = $params['expense_id'];
            elseif (!empty($params['os_id'])) $postData['os_id'] = $params['os_id'];
            elseif (!empty($params['os_follow_up_id'])) $postData['os_follow_up_id'] = $params['os_follow_up_id'];
            elseif (!empty($params['os_file_id'])) $postData['os_file_id'] = $params['os_file_id'];
            elseif (!empty($params['purchase_id'])) $postData['purchase_id'] = $params['purchase_id'];
            elseif (!empty($params['support_id'])) $postData['support_id'] = $params['support_id'];
            elseif (!empty($params['prospect_id'])) $postData['prospect_id'] = $params['prospect_id'];
            elseif (!empty($params['budget_id'])) $postData['budget_id'] = $params['budget_id'];

            if (!empty($params['user_id'])) $postData['user_id'] = $params['user_id'];
            elseif (!empty($params['customer_id'])) $postData['customer_id'] = $params['customer_id'];

            $crud = new Crud();
            $crud->setTable($historicModel->getTable());
            $crud->create($postData);
            
            return true;
        } else {
            return false;
        }
    }

    public function sendMail(array $data): bool
    {
        if (ENV_PRODUCTION === false) {
            return true;
        }

        if (
            !empty($data['title']) && !empty($data['message'])
            && !empty($data['name']) && !empty($data['toAddress'])
        ) {
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
                            <td style="text-align: center; padding:0;">
                                <table role="presentation" style="width:602px;border-collapse:collapse;border:1px solid #cccccc;border-spacing:0;text-align:left;">
                                    <tr>
                                        <td style="text-align: center; padding:10px 0 10px 0;background:' . $config['primary_color'] . ';">
                                            <h1 style="color: white; text-shadow: black 0.1em 0.1em 0.2em;">' . $config['site_title'] . '</h1>
                                            <h2 style="color: white; text-shadow: black 0.1em 0.1em 0.2em;">' . $data['title'] . '</h2>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:36px 30px 42px 30px;">
                                            <table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
                                                <tr>
                                                    <td style="padding:0 0 10px 0;color:#153643;">
                                                        <p>' . $data['name'] . ', tudo bem? </p>
                                                        ' . $data['message'] . '
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
                                        <td style="padding:30px;background:' . $config['primary_color'] . ';">
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
            $mail->Host = SMTP_MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_MAIL_USERNAME;
            $mail->Password = SMTP_MAIL_PASSWORD;
            $mail->Port = SMTP_MAIL_PORT;

            $mail->setFrom($config['mail_from_address'], ($config['site_title']));
            $mail->addAddress($data['toAddress']);

            $message = wordwrap($message, 70);
            $mail->isHTML();
            $mail->Subject = ($data['title']);
            $mail->Body = ($message);

            try {
                $mail->send();
                return true;
            } catch (phpmailerException $e) {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function getTarget(): string
    {
        if (empty($_SESSION['TARGET'])) {
            $hashMd5 = md5($_SESSION['COD'] 
                .  $_SERVER["HTTP_USER_AGENT"]);

            $_SESSION['TARGET'] = Bcrypt::hash($hashMd5);
        }
        
        return $_SESSION['TARGET'];
    }

    public function targetValidated($target): bool
    {
        if (!empty($target)) {
            $hashMd5 = md5($_SESSION['COD'] 
                .  $_SERVER["HTTP_USER_AGENT"]);

            if (Bcrypt::check($hashMd5, $target)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getClientTarget(): string
    {
        return md5($_SESSION['CLI_COD'] . $_SERVER["HTTP_USER_AGENT"]);
    }

    public function targetClientValidated($target): bool
    {
        if (!empty($target)) {
            $currentTarget = md5($_SESSION['CLI_COD'] . $_SERVER["HTTP_USER_AGENT"]);
            if ($currentTarget && md5($target)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getTotalPurchases()
    {
        $purchasesModel = new Purchases();
        return [
            'totalByStatus_1' => $purchasesModel->getTotalByStatus(1),
            'totalByStatus_2' => $purchasesModel->getTotalByStatus(2),
            'totalDelayed' => $purchasesModel->getDataDelayed(
                1, 'purchase_date', 'purchases'
            )
        ];
    }

    public function getTotalPurchasesByMonth($status, $month)
    {
        $purchasesModel = new Purchases();
        return $purchasesModel->getTotalByStatusByMonth($status, $month);
    }

    public function getTotalTasks()
    {
        $tasksModel = new Tasks();
        return [
            'totalByStatus_1' => $tasksModel->getTotalByStatus(1),
            'totalByStatus_2' => $tasksModel->getTotalByStatus(2),
            'totalByStatus_3' => $tasksModel->getTotalByStatus(3),
            'totalDelayed' => $tasksModel->getDataDelayed(
                1, 'task_date', 'tasks'
            )
        ];
    }    

    public function getTotalSchedules()
    {
        $schedulesModel = new Schedules();

        $totalDelayed_1 = $schedulesModel->getDataDelayed(
            1, 'schedule_date', 'schedules'
        );

        $totalDelayed_2 = $schedulesModel->getDataDelayed(
            2, 'schedule_date', 'schedules'
        );

        return [
            'totalByStatus_1' => $schedulesModel->getTotalByStatus(1),
            'totalByStatus_2' => $schedulesModel->getTotalByStatus(2),
            'totalByStatus_3' => $schedulesModel->getTotalByStatus(3),
            'totalDelayed' => ($totalDelayed_1 + $totalDelayed_2)
        ];
    }    

    public function getTotalSchedulesByCustomer($customerId)
    {
        $schedulesModel = new Schedules();

        $totalDelayed_1 = $schedulesModel->getDataDelayedByCustomer(
            1, 'schedule_date', 'schedules', $_SESSION['CLI_COD']
        );

        $totalDelayed_2 = $schedulesModel->getDataDelayedByCustomer(
            2, 'schedule_date', 'schedules', $_SESSION['CLI_COD']
        );
        
        return [
            'totalByStatus_1' => $schedulesModel->getTotalByStatusAndCustomer(1, $customerId),
            'totalByStatus_2' => $schedulesModel->getTotalByStatusAndCustomer(2, $customerId),
            'totalByStatus_3' => $schedulesModel->getTotalByStatusAndCustomer(3, $customerId),
            'totalDelayed' => ($totalDelayed_1 + $totalDelayed_2)
        ];
    }    

    public function isImage(string $image, string $path = null): bool
    {
        if (!empty($image)) {
            $imageExt = pathinfo($image);
            $imageExt = $imageExt['extension'];
            
            if (in_array($imageExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                
                if (!empty($path)) {
                    if (file_exists('../public/uploads/'.$path.'/'.$image)) {
                        return true;
                    } else {
                        return false;
                    }
                }

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function isFile(string $file, string $path): bool
    {
        if (!empty($file) && !empty($path)) {
            if (file_exists('../public/uploads/'.$path.'/'.$file)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getTotalSalesByMonth(string $status, string $month): string
    {
        $salesModel = new Sales();
        return $salesModel->getTotalByStatusAndMonth($status, $month);
    }

    public function validatePostParams(array $post): bool
    {
        if (!empty($post) && !empty($post['target']) && $this->targetValidated($post['target'])) {
            return true;
        } else {
            return false;
        }
    }
    
    public function validateClientPostParams(array $post): bool
    {
        if (!empty($post) && !empty($post['target']) && $this->targetClientValidated($post['target'])) {
            return true;
        } else {
            return false;
        }
    }

    public function userUnreadNotifications(): int
    {
        if (!empty($_SESSION['COD'])) {
            $notificationsModel = new SystemNotifications();
            $total = $notificationsModel->getTotalUnreadsByUser($_SESSION['COD']);
            return $total;
        } else {
            return 0;
        }
    }

    public function customerUnreadNotifications(): int
    {
        if (!empty($_SESSION['CLI_COD'])) {
            $notificationsModel = new ClientNotifications();
            $total = $notificationsModel->getTotalUnreadsByCustomer($_SESSION['CLI_COD']);
            return $total;
        } else {
            return 0;
        }
    }

    public function userUnreadMessages(): int
    {
        if (!empty($_SESSION['COD'])) {
            $messagesModel = new SystemMessages();
            $total = $messagesModel->getTotalUnreadsByUser($_SESSION['COD']);
            return $total;
        } else {
            return 0;
        }
    }

    public function customerUnreadMessages(): int
    {
        if (!empty($_SESSION['CLI_COD'])) {
            $messagesModel = new ClientMessages();
            $total = $messagesModel->getTotalUnreadsByCustomer($_SESSION['CLI_COD']);
            return $total;
        } else {
            return 0;
        }
    }

    public function getUserDataByMsg(array $params): array|bool
    {
        if (!empty($params)) {
            $data = [];
          
            if ($params['parent'] == 'os') {
                $osMessagesModel = new OsMessages();
                $data = $osMessagesModel->getLastByOs($params['os_id']);
            }
            
            if ($params['parent'] == 'tasks') {
                $taskMessagesModel = new TaskMessages();
                $data = $taskMessagesModel->getLastByTask($params['task_id']);
            }

            if ($params['parent'] == 'budgets') {
                $budgetMessagesModel = new BudgetMessages();
                $data = $budgetMessagesModel->getLastByBudget($params['budget_id']);
            }

            if ($params['parent'] == 'support') {
                $supportMessagesModel = new SupportMessages();
                $data = $supportMessagesModel->getLastByCall($params['support_id']);
            }
            
            return $data;
        } else {
            return [];
        }
    }
}