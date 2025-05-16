<?php

namespace App\Controller;

use App\Model\Budgets;
use App\Model\Expenses;
use App\Model\Os;
use App\Model\Prospects;
use App\Model\Purchases;
use App\Model\Sales;
use App\Model\Schedules;
use App\Model\Tasks;
use Core\Controller\ActionController;

class IndexController extends ActionController
{    
    private mixed $expensesModel;
    private mixed $schedulesModel;

    public function __construct()
    {
        parent::__construct();
        $this->expensesModel = new Expenses();
        $this->schedulesModel = new Schedules();
    }
    
    public function indexAction(): void
    {
        $total_pending_expenses = $this->expensesModel->totalPendingExpenses('1', date('m/Y'));
        $this->view->total_pending_expenses = $total_pending_expenses;
        
        $total_pending_schedules = $this->schedulesModel->getTotalByStatus('1');
        $this->view->total_pending_schedules = $total_pending_schedules;

        $this->render('index');
    }
}