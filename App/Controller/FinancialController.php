<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;

class FinancialController extends ActionController
{
    private mixed $schedulesModel;
    private mixed $expensesModel;

    public function __construct()
    {
        parent::__construct();
        $this->schedulesModel = Container::getClass("Schedules", "app");
        $this->expensesModel = Container::getClass("Expenses", "app");
    }
    
    public function indexAction(): void
    {
        $total_expenses = $this->expensesModel->getTotalByStatus('2');
        $this->view->total_expenses = $total_expenses;

        $total_schedules = $this->schedulesModel->getTotalAmountByStatus('2');
        $this->view->total_schedules = $total_schedules;

        $schedules = $this->schedulesModel->getAll();
        $this->view->schedules = $schedules;

        $expenses = $this->expensesModel->getAll();
        $this->view->expenses = $expenses;

        $this->render('index', false);
    }
}