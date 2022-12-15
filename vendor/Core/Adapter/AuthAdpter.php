<?php

namespace Core\Adapter;

use Core\Di\Container;

class AuthAdpter
{
    public function getIdentity()
    {
        return $this->checkUserSession();
    }

    private function checkUserSession()
    {
        @session_start();
        if (!empty($_SESSION['EMAIL']) && !empty($_SESSION['PASS']) && !empty($_SESSION['TOKEN'])) {
            $email = $_SESSION['EMAIL'];
            $pass  = $_SESSION['PASS'];
            $token = $_SESSION['TOKEN'];
        } else {
            $email = "";
            $pass  = "";
            $token = "";
        }

        $collaborator = Container::getClass("User", 'app');
        return $collaborator->authByCrenditials($email, $pass, $token);
    }
}
