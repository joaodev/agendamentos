<?php

namespace Client\Controller;

use App\Model\Budgets;
use App\Model\Expenses;
use App\Model\Os;
use App\Model\Sales;
use Core\Controller\ActionController;

class IndexController extends ActionController
{
    private mixed $osModel;
    private mixed $budgetsModel;
    private mixed $purchasesModel;
    private mixed $expensesModel;

    public function __construct()
    {
        parent::__construct();
        $this->osModel = new Os();
        $this->budgetsModel = new Budgets();
        $this->purchasesModel = new Sales();
        $this->expensesModel = new Expenses();
    }

    public function indexAction(): void 
    {
        $total_pending_os = $this->osModel->getTotal('1', $_SESSION['CLI_COD']);
        $this->view->total_pending_os = $total_pending_os;
        
        $total_pending_budgets = $this->budgetsModel->getTotal('1', $_SESSION['CLI_COD']);
        $this->view->total_pending_budgets = $total_pending_budgets;

        $total_purchases = $this->purchasesModel->getTotalByStatusAndMonth('2', date('m/Y'), $_SESSION['CLI_COD']);
        $this->view->total_purchases = $total_purchases;
        
        $total_expenses = $this->expensesModel->getTotalExpensesByMonthAndCustomer('2', '2', date('m/Y'), $_SESSION['CLI_COD']);
        $this->view->total_expenses = $total_expenses;
        
        $this->render('index');
    }
}