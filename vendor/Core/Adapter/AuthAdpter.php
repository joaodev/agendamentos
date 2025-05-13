<?php

namespace Core\Adapter;

use App\Model\User;

class AuthAdpter
{
    public function getIdentity(): array|bool
    {
        return $this->checkUserSession();
    }

    private function checkUserSession(): array|bool
    {
        @session_start();
        if (!empty($_SESSION['EMAIL']) && !empty($_SESSION['PASS']) && !empty($_SESSION['TOKEN'])) {
            $email = $_SESSION['EMAIL'];
            $pass = $_SESSION['PASS'];
            $token = $_SESSION['TOKEN'];
        } else {
            $email = "";
            $pass = "";
            $token = "";
        }

        $collaborator = new User();
        return $collaborator->authByCrenditials($email, $pass, $token);
    }
}