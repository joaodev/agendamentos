<?php

//Config SANDBOX or PRODUCTION environment
$SANDBOX_ENVIRONMENT = true;

if ($SANDBOX_ENVIRONMENT) {
    $PAGSEGURO_API_URL = 'https://ws.sandbox.pagseguro.uol.com.br/v2';
    $JS_FILE_URL = "https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js";
} else {
    $JS_FILE_URL = "https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js";
    $PAGSEGURO_API_URL = 'https://ws.pagseguro.uol.com.br/v2';
}