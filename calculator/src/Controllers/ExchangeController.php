<?php

namespace Calculator\Controllers;

use Calculator\Services\ExchangeService;

class ExchangeController
{
    public function exchange(): void
    {
        header('Content-Type: application/json');
        $body   = json_decode(file_get_contents('php://input'), true);
        $from   = $body['from']   ?? '';
        $to     = $body['to']     ?? '';
        $amount = (float) ($body['amount'] ?? 0);

        if ($from === '' || $to === '') {
            echo json_encode(['error' => '參數不完整']);
            return;
        }

        $service = new ExchangeService();
        echo json_encode($service->convert($from, $to, $amount));
    }
}
