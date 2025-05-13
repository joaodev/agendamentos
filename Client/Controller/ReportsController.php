<?php

namespace Client\Controller;

use App\Model\Budgets;
use App\Model\Expenses;
use App\Model\Os;
use App\Model\Sales;
use App\Model\Schedules;
use Core\Controller\ActionController;

class ReportsController extends ActionController
{    
    private mixed $osModel;
    private mixed $budgetsModel;
    private mixed $purchasesModel;
    private mixed $expensesModel;
    private mixed $schedulesModel;

    public function __construct()
    {
        parent::__construct();

        $this->osModel = new Os();
        $this->budgetsModel = new Budgets();
        $this->purchasesModel = new Sales();
        $this->expensesModel = new Expenses();
        $this->schedulesModel = new Schedules();
    }

    public function indexAction(): void
    {
        if ($this->validateClientPostParams($_POST)) {
            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    /**
     * @return void
     */
    public function generateAction(): void
    {
        if (!empty($_POST) && $this->validateClientPostParams($_POST)) {
            switch ($_POST['sis_module']) {
                case 1:
                    $module = 'compras';
                    $moduleModel = $this->purchasesModel;
                    $moduleTitle = 'Compras';
                    $modalTitle = 'Compra';
                    break;
                case 2:
                    $module = 'pagamentos';
                    $moduleModel = $this->expensesModel;
                    $moduleTitle = 'Pagamentos';
                    $modalTitle = 'Pagamento';
                    break;
                case 3:
                    $module = 'orcamentos';
                    $moduleModel = $this->budgetsModel;
                    $moduleTitle = 'Orçamentos';
                    $modalTitle = 'Orçamento';
                    break;
                case 4:
                    $module = 'os';
                    $moduleModel = $this->osModel;
                    $moduleTitle = 'Ordens de Serviço';
                    $modalTitle = 'OS';
                    break;
                case 5:
                    $module = 'agendamentos';
                    $moduleModel = $this->schedulesModel;
                    $moduleTitle = 'Agendamentos';
                    $modalTitle = 'Agendamento';
                    break;
                default:
                    $module = null;
                    $moduleModel = null;
                    $moduleTitle = null;
                    $modalTitle = null;
                    break;
            }

            if (
                !empty($module) && !empty($moduleModel) 
                && !empty($moduleTitle) && !empty($modalTitle)
            ) {
                $data = $moduleModel->getDataForReport($_POST, $_SESSION['CLI_COD']);
            } else {
                $data = [];
            }

            $orderType = $_POST['order_type'] == 1 ? 'asc' : 'desc';
            $this->view->orderType = $orderType;

            $this->view->module = $module;
            $this->view->moduleTitle = $moduleTitle;
            $this->view->modalTitle = $modalTitle;
            $this->view->data = $data;
            $this->render('results', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }
}