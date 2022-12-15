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
        if (!empty($_GET['m'])) {
            $month = $_GET['m'];
        } else {
            $month = date('Y-m');
        }

        $this->view->month = self::formatMonth($month);

        $total_expenses = $this->expensesModel->getTotalAmountByMonth($month);
        $this->view->total_expenses = $total_expenses;

        $total_schedules = $this->schedulesModel->getTotalAmountByMonth($month);
        $this->view->total_schedules = $total_schedules;

        $schedules = $this->schedulesModel->getAllByMonth($month);
        $this->view->schedules = $schedules;

        $expenses = $this->expensesModel->getAllByMonth($month);
        $this->view->expenses = $expenses;

        $this->render('index', false);
    }

    private function formatMonth($month): string
    {
        $exp = explode('-', $month, 2);

        return $exp[1] . '/' . $exp[0];
    }
}