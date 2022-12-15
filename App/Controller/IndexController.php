<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;

class IndexController extends ActionController
{    
    private mixed $customersModel;
    private mixed $servicesModel;
    private mixed $schedulesModel;
    private mixed $expensesModel;

    public function __construct()
    {
        parent::__construct();
        $this->customersModel = Container::getClass("Customers", "app");
        $this->servicesModel = Container::getClass("Services", "app");
        $this->schedulesModel = Container::getClass("Schedules", "app");
        $this->expensesModel = Container::getClass("Expenses", "app");
    }
    
    public function indexAction(): void
    {
        $total_customers = $this->customersModel->totalCustomers();
        $this->view->total_customers = $total_customers;

        $total_services = $this->servicesModel->totalData($this->servicesModel->getTable());
        $this->view->total_services = $total_services;

        $total_pending_schedules = $this->schedulesModel->getTotalByStatus('1');
        $this->view->total_pending_schedules = $total_pending_schedules;

        $total_finished_schedules = $this->schedulesModel->getTotalByStatus('2');
        $this->view->total_finished_schedules = $total_finished_schedules;

        $total_expenses = $this->expensesModel->getTotalByStatus('1') + $this->expensesModel->getTotalByStatus('2');
        $this->view->total_expenses = $total_expenses;

        $total_schedules = $this->schedulesModel->getTotalAmountByStatus('2');
        $this->view->total_schedules = $total_schedules;

        $this->render('index');
    }
}