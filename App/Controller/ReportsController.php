<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;

class ReportsController extends ActionController
{    
    private mixed $schedulesModel;
    private mixed $customersModel;
    private mixed $expensesModel;
    private mixed $revenuesModel;
    private mixed $servicesModel;
    private mixed $tasksModel;

    public function __construct()
    {
        parent::__construct();

        $this->schedulesModel = Container::getClass("Schedules", "app");
        $this->customersModel = Container::getClass("Customers", "app");
        $this->expensesModel = Container::getClass("Expenses", "app");
        $this->revenuesModel = Container::getClass("Revenues", "app");
        $this->servicesModel = Container::getClass("Services", "app");
        $this->tasksModel = Container::getClass("Tasks", "app");
    }

    public function indexAction(): void
    {
        $this->render('index', false);
    }

    /**
     * @return void
     */
    public function generateAction(): void
    {
        if (!empty($_POST)) {
            switch ($_POST['sis_module']) {
                case 1:
                    $module = 'agendamentos';
                    $moduleModel = $this->schedulesModel;
                    $moduleTitle = 'Agendamentos';
                    $modalTitle = 'Agendamento';
                    break;
                case 2:
                    $module = 'clientes';
                    $moduleModel = $this->customersModel;
                    $moduleTitle = 'Clientes';
                    $modalTitle = 'Cliente';
                    break;
                case 3:
                    $module = 'despesas';
                    $moduleModel = $this->expensesModel;
                    $moduleTitle = 'Despesas';
                    $modalTitle = 'Despesa';
                    break;
                case 4:
                    $module = 'receitas';
                    $moduleModel = $this->revenuesModel;
                    $moduleTitle = 'Receitas';
                    $modalTitle = 'Receita';
                    break;
                case 5:
                    $module = 'servicos';
                    $moduleModel = $this->servicesModel;
                    $moduleTitle = 'Serviços';
                    $modalTitle = 'Serviço';
                    break;
                case 6:
                    $module = 'tarefas';
                    $moduleModel = $this->tasksModel;
                    $moduleTitle = 'Tarefas';
                    $modalTitle = 'Tarefa';
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
                $data = $moduleModel->getDataForReport($_POST, $this->parentUUID);
            } else {
                $data = [];
            }

            $orderType = $_POST['order_type'] == 1 ? 'asc' : 'desc';
            $this->view->orderType = $orderType;

            $this->view->module = $module;
            $this->view->moduleTitle = $moduleTitle;
            $this->view->modalTitle = $modalTitle;
            $this->view->data = $data;
        }

        $this->render('results', false);
    }
}