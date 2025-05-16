<?php 

namespace Client\Controller;

use App\Model\ChangesHistoric;
use App\Model\Files;
use App\Model\Schedules;
use App\Model\User;
use Core\Controller\ActionController;
use Core\Db\Crud;

class SchedulesController extends ActionController
{
    private mixed $model;
    private mixed $userModel;
    private mixed $changesHistoricModel;
    private mixed $filesModel;

    public function __construct()
    {
        parent::__construct();

        $this->model = new Schedules();
        $this->userModel = new User();
        $this->changesHistoricModel = new ChangesHistoric();
        $this->filesModel = new Files();
    }

    public function indexAction(): void
    {
        if ($this->validateClientPostParams($_POST)) {
            $data = $this->model->getAllByCustomer($_SESSION['CLI_COD']);
            $this->view->data = $data;
            $this->render('index', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function readAction(): void
    {
        if (!empty($_POST['id']) && $this->validateClientPostParams($_POST)) {
            $entity = $this->model->getOne($_POST['id'], $_SESSION['CLI_COD'], false);
            if ($entity) {
                $this->view->entity = $entity;

                $historic = $this->changesHistoricModel->getAllByModule('schedule_id', $_POST['id']);
                $this->view->historic = $historic;

                $this->render('read', false);
            } else {
                $this->render('../error/not-found', false);
            }
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function filesAction(): void
    {
        if (!empty($_POST['id']) && $this->validateClientPostParams($_POST)) {
            $files = $this->filesModel->findAllBy('id, file, created_at', 'schedule_id', $_POST['id']);
            $this->view->files = $files;
            $this->render('files', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }

    public function answerScheduleAction(): void
    {
        if (!empty($_POST['id']) && $this->validateClientPostParams($_POST)) {
      
            $this->render('answer-schedule', false);
        } else {
            $this->render('../error/not-found', false);
        }
    }
    
    public function processAnswerAction(): bool
    {
        if (!empty($_POST) && $this->validateClientPostParams($_POST)) {
            if ($_POST['status'] == 1 && empty($_POST['description'])) {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'Informe uma resposta no campo INFORMAÇÕES ADICIONAIS.',
                    'type' => 'error',
                    'pos' => 'top-center'
                ];

                echo json_encode($data);
                return false;
            }

            $entity = $this->model->getOne($_POST['id'], $_SESSION['CLI_COD']);
            if ($entity) {
           
                $updateData = [
                    'description' => $_POST['description'],
                    'status' => $_POST['status'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
    
                $crud = new Crud();
                $crud->setTable($this->model->getTable());
                $transaction = $crud->update($updateData, $entity['id'], 'id');
      
                if ($transaction) {
                    if (!empty($entity['user_id'])) {
                        $this->sendNotification([
                            'parent' => 'schedules',
                            'user_id' => $entity['user_id'],
                            'schedule_id' => $entity['id'],
                            'description' => "Respondeu ao Agendamento #{$entity['id']}."
                        ]);

                        $url = baseUrl;
                        $message = "<p>Agendamento #{$entity['id']} foi respondido; 
                            <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                            
                        $this->sendMail([
                            'title' => "Respondeu ao Agendamento #{$entity['id']}.",
                            'message' => $message,
                            'name' => $entity['userName'],
                            'toAddress' => $entity['userEmail']
                        ]);
                    } else {
                        $admins = $this->userModel->getAllAdminActives();
                        foreach ($admins as $admin) {
                            $this->sendNotification([
                                'parent' => 'schedules',
                                'user_id' => $admin['id'],
                                'schedule_id' => $entity['id'],
                                'description' => "Respondeu ao Agendamento #{$entity['entity']}."
                            ]);

                            $url = baseUrl;
                            $message = "<p>Agendamento #{$entity['id']} foi respondido; 
                                <a href='$url'>acesse a plataforma</a> para conferir.</p>";
                                
                            $this->sendMail([
                                'title' => "Respondeu ao Agendamento #{$entity['entity']}.",
                                'message' => $message,
                                'name' => $admin['name'],
                                'toAddress' => $admin['email']
                            ]);
                        }
                    }

                    $this->toLog("Respondeu ao Agendamento {$entity['id']}");
                    $data = [
                        'title' => 'Sucesso!',
                        'msg' => 'Agendamento respondido.',
                        'type' => 'success',
                        'pos' => 'top-right',
                        'id' => $entity['id']
                    ];
                } else {
                    $data = [
                        'title' => 'Erro!',
                        'msg' => 'O Agendamento não foi respondido.',
                        'type' => 'error',
                        'pos' => 'top-center'
                    ];
                }
            } else {
                $data = [
                    'title' => 'Erro!',
                    'msg' => 'O Agendamento não foi respondido.',
                    'type' => 'error',
                    'pos' => 'top-center'
                ];
            }

            echo json_encode($data);
            return true;
        } else {
            return false;
        }
    }
}