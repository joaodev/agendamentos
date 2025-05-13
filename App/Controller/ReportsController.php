<?php

namespace App\Controller;

use App\Model\Budgets;
use App\Model\Expenses;
use App\Model\Financial;
use App\Model\ItemsControl;
use App\Model\Logs;
use App\Model\Os;
use App\Model\Prospects;
use App\Model\Purchases;
use App\Model\Sales;
use App\Model\Schedules;
use App\Model\Support;
use App\Model\Tasks;
use App\Model\TimeSheets;
use App\Model\User;
use Core\Controller\ActionController;

class ReportsController extends ActionController
{    
    private mixed $osModel;
    private mixed $budgetsModel;
    private mixed $purchasesModel;
    private mixed $expensesModel;
    private mixed $salesModel;
    private mixed $schedulesModel;
    private mixed $itemsControlModel;
    private mixed $timeSheetsModel;
    private mixed $prospectsModel;
    private mixed $tasksModel;
    private mixed $financialModel;
    private mixed $userModel;
    private mixed $logsModel;
    private mixed $supportModel;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();

        $this->osModel = new Os();
        $this->budgetsModel = new Budgets();
        $this->purchasesModel = new Purchases();
        $this->expensesModel = new Expenses();
        $this->salesModel = new Sales();
        $this->schedulesModel = new Schedules();
        $this->itemsControlModel = new ItemsControl();
        $this->timeSheetsModel = new TimeSheets();
        $this->prospectsModel = new Prospects();
        $this->tasksModel = new Tasks();
        $this->financialModel = new Financial();
        $this->logsModel = new Logs();
        $this->supportModel = new Support();
        $this->userModel = new User();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'reports')
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
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
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
            switch ($_POST['sis_module']) {
                case 1:
                    $module = 'vendas';
                    $moduleModel = $this->salesModel;
                    $moduleTitle = 'Vendas';
                    $modalTitle = 'Venda';
                    break;
                case 2:
                    $module = 'contas';
                    $moduleModel = $this->expensesModel;
                    $moduleTitle = 'Contas';
                    $modalTitle = 'Conta';
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
                case 6:
                    $module = 'controle-estoque';
                    $moduleModel = $this->itemsControlModel;
                    $moduleTitle = 'Controle de Estoque';
                    $modalTitle = 'Movimentação de Estoque';
                    break;
                case 7:
                    $module = 'folha-ponto';
                    $moduleModel = $this->timeSheetsModel;
                    $moduleTitle = 'Folha de Ponto';
                    $modalTitle = 'Folha de Ponto';
                    break;
                case 8:
                    $module = 'prospeccoes';
                    $moduleModel = $this->prospectsModel;
                    $moduleTitle = 'Prospecções';
                    $modalTitle = 'Prospecção';
                    break;
                case 9:
                    $module = 'tarefas';
                    $moduleModel = $this->tasksModel;
                    $moduleTitle = 'Tarefas';
                    $modalTitle = 'Tarefa';
                    break;
                case 10:
                    $module = 'compras';
                    $moduleModel = $this->purchasesModel;
                    $moduleTitle = 'Compras';
                    $modalTitle = 'Compra';
                    break;
                case 11:
                    $module = 'financeiro';
                    $moduleModel = $this->financialModel;
                    $moduleTitle = 'Fluxo de Caixa';
                    $modalTitle = 'Movimentação de Caixa';
                    break;
                case 12:
                    $module = 'usuarios';
                    $moduleModel = $this->userModel;
                    $moduleTitle = 'Usuários';
                    $modalTitle = 'Usuário';
                    break;
                case 13:
                    $module = 'logs';
                    $moduleModel = $this->logsModel;
                    $moduleTitle = 'Log';
                    $modalTitle = 'Informação do Log';
                    break;
                case 14:
                    $module = 'chamados';
                    $moduleModel = $this->supportModel;
                    $moduleTitle = 'Chamados';
                    $modalTitle = 'Chamado';
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
                $data = $moduleModel->getAdminDataForReport($_POST);
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