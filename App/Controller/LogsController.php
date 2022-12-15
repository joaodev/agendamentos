<?php

namespace App\Controller;

use Core\Controller\ActionController;
use Core\Di\Container;

class LogsController extends ActionController
{
    private mixed $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = Container::getClass("Logs", "app");
    }

    public function indexAction(): void
    {
        $data = $this->model->getAll();
        $this->view->data = $data;
        $this->render('index', false);
    }

    public function readAction(): void
    {
        if (!empty($_POST['uuid'])) {
            $entity = $this->model->getOne($_POST['uuid']);
            $this->view->entity = $entity;
            $this->render('read', false);
        }
    }

}