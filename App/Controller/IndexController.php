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
    private mixed $revenuesModel;
    private mixed $tasksModel;
    private mixed $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->customersModel = Container::getClass("Customers", "app");
        $this->servicesModel = Container::getClass("Services", "app");
        $this->schedulesModel = Container::getClass("Schedules", "app");
        $this->expensesModel = Container::getClass("Expenses", "app");
        $this->revenuesModel = Container::getClass("Revenues", "app");
        $this->tasksModel = Container::getClass("Tasks", "app");
        $this->userModel = Container::getClass("User", "app");
    }
    
    public function indexAction(): void
    {
        $parentUUID = $this->parentUUID;
        $total_customers = $this->customersModel->totalCustomers($parentUUID);
        $this->view->total_customers = $total_customers;

        $total_services = $this->servicesModel->totalData($this->servicesModel->getTable(), $parentUUID);
        $this->view->total_services = $total_services;

        $total_users = $this->userModel->totalData($this->userModel->getTable(), $parentUUID);
        $this->view->total_users = $total_users;

        $total_pending_schedules = $this->schedulesModel->getTotalByStatus('1', $parentUUID);
        $this->view->total_pending_schedules = $total_pending_schedules;
        
        $total_revenues = $this->revenuesModel->getTotalByStatus('2', $parentUUID);
        $this->view->total_revenues = $total_revenues;
        
        $totalExpenses1 = $this->expensesModel->getTotalByStatus('1', $parentUUID);
        $totalExpenses2 = $this->expensesModel->getTotalByStatus('2', $parentUUID);
        
        $total_expenses =  ($totalExpenses1 + $totalExpenses2);
        $this->view->total_expenses = $total_expenses;
        
        $total_schedules = $this->schedulesModel->getTotalAmountByStatus('2', $parentUUID);
        $this->view->total_schedules = $total_schedules;
        
        $total_pending_tasks = $this->tasksModel->getTotalByStatus('1', $parentUUID);
        $this->view->total_pending_tasks = $total_pending_tasks;
        
        $schedulesForToday = $this->schedulesModel->getTotalForToday(
            1, $parentUUID, 'schedule_date', $this->schedulesModel->getTable()
        );
        $this->view->schedulesForToday = $schedulesForToday;

        $tasksForToday = $this->tasksModel->getTotalForToday(
            1, $parentUUID, 'task_date', $this->tasksModel->getTable()
        );
        $this->view->tasksForToday = $tasksForToday;

        $expensesForToday = $this->expensesModel->getTotalForToday(
            1, $parentUUID, 'expense_date', $this->expensesModel->getTable()
        );
        $this->view->expensesForToday = $expensesForToday;

        $revenuesForToday = $this->revenuesModel->getTotalForToday(
            1, $parentUUID, 'revenue_date', $this->revenuesModel->getTable()
        );
        $this->view->revenuesForToday = $revenuesForToday;

        $schedulesDelayed = $this->schedulesModel->getTotalDelayed(
            1, $parentUUID, 'schedule_date', $this->schedulesModel->getTable()
        );
        $this->view->schedulesDelayed = $schedulesDelayed;

        $tasksDelayed = $this->tasksModel->getTotalDelayed(
            1, $parentUUID, 'task_date', $this->tasksModel->getTable()
        );
        $this->view->tasksDelayed = $tasksDelayed;

        $expensesDelayed = $this->expensesModel->getTotalDelayed(
            1, $parentUUID, 'expense_date', $this->expensesModel->getTable()
        );
        $this->view->expensesDelayed = $expensesDelayed;

        $revenuesDelayed = $this->revenuesModel->getTotalDelayed(
            1, $parentUUID, 'revenue_date', $this->revenuesModel->getTable()
        );
        $this->view->revenuesDelayed = $revenuesDelayed;

        $this->render('index');
    }
}