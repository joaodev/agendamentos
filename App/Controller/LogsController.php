<?php

namespace App\Controller;

use App\Model\Logs;
use Core\Controller\ActionController;

class LogsController extends ActionController
{
    private mixed $model;
    private array $aclData;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Logs();

        $this->aclData = [
            'canView' => $this->getAcl('view', 'logs'),
        ];

        $this->view->acl = $this->aclData;
    }

    public function indexAction(): void
    {
        if (!empty($_POST['target']) && $this->targetValidated($_POST['target']) && $this->aclData['canView']) {
            
            if (!empty($_GET['m'])) {
                $month = $_GET['m'];
            } else {
                $month = date('Y-m');
            }

            $this->view->month = self::formatMonth($month);
            $this->view->month_not_formatted = $month;

            $data = $this->model->getLogsByMonth($month);
            $this->view->data = $data;
                        
            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function readAction(): void
    {
        if (!empty($_POST['id']) && !empty($_POST['target']) && $this->targetValidated($_POST['target']) && $this->aclData['canView']) {
            $entity = $this->model->getOne($_POST['id']);
            $this->view->entity = $entity;
            $this->render('read', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }
}