<?php

namespace Core\Adapter;

use Client\Model\Customer;

class AuthClientAdpter
{
    public function getIdentity(): array|bool
    {
        $clientSession = $this->checkClientSession();
        return $clientSession;
    }

    private function checkClientSession(): array|bool
    {
        @session_start();
        if (!empty($_SESSION['CLI_EMAIL']) && !empty($_SESSION['CLI_TOKEN'])) {
            $email = $_SESSION['CLI_EMAIL'];
            $token = $_SESSION['CLI_TOKEN'];
        } else {
            $email = "";
            $token = "";
        }
        
        $customer  = new Customer(); 
        $validated = $customer->authByCrenditials($email, $token);

        return $validated;
    }
}