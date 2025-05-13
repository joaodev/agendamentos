<?php

namespace App\Controller;

use App\Model\Financial;
use Core\Controller\ActionController;

class FinancialController extends ActionController
{
    private mixed $model;
    private array $aclData;


    public function __construct()
    {
        parent::__construct();
        $this->model = new Financial();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'financial'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if ($this->validatePostParams($_POST) && $this->aclData['canView']) {
            if (!empty($_GET['m'])) {
                $month = $_GET['m'];
            } else {
                $month = date('Y-m');
            }

            $this->view->month = self::formatMonth($month);
            $this->view->month_not_formatted = $month;
            
            $data = $this->model->getAllByMonth($month);
            $this->view->data = $data;  

            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }
}