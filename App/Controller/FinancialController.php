<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;

class FinancialController extends ActionController
{
    private mixed $schedulesModel;
    private mixed $expensesModel;
    private mixed $revenuesModel;

    public function __construct()
    {
        parent::__construct();
        $this->schedulesModel = Container::getClass("Schedules", "app");
        $this->expensesModel = Container::getClass("Expenses", "app");
        $this->revenuesModel = Container::getClass("Revenues", "app");
    }
    
    public function indexAction(): void
    {
        $parentUUID = $this->parentUUID;

        if (!empty($_GET['m'])) {
            $month = $_GET['m'];
        } else {
            $month = date('Y-m');
        }

        $this->view->month = self::formatMonth($month);

        $total_expenses = $this->expensesModel->getTotalAmountByMonth($month, $parentUUID);
        $this->view->total_expenses = $total_expenses;

        $total_revenues = $this->revenuesModel->getTotalAmountByMonth($month, $parentUUID);
        $this->view->total_revenues = $total_revenues;

        $total_schedules = $this->schedulesModel->getTotalAmountByMonth($month, $parentUUID);
        $this->view->total_schedules = $total_schedules;

        $schedules = $this->schedulesModel->getAllByMonth('2', $month, $parentUUID);
        $this->view->schedules = $schedules;

        $expenses = $this->expensesModel->getAllByMonth('2', $month, $parentUUID);
        $this->view->expenses = $expenses;

        $revenues = $this->revenuesModel->getAllByMonth('2', $month, $parentUUID);
        $this->view->revenues = $revenues;

        $this->render('index', false);
    }
}