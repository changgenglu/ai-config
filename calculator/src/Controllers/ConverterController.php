<?php

namespace Calculator\Controllers;

use Calculator\Services\UnitConverterService;

class ConverterController
{
    public function convert(): void
    {
        header('Content-Type: application/json');
        $body  = json_decode(file_get_contents('php://input'), true);
        $type  = $body['type']  ?? '';
        $from  = $body['from']  ?? '';
        $to    = $body['to']    ?? '';
        $value = (float) ($body['value'] ?? 0);

        if ($type === '' || $from === '' || $to === '') {
            echo json_encode(['error' => '參數不完整']);
            return;
        }

        $service = new UnitConverterService();
        echo json_encode($service->convert($type, $from, $to, $value));
    }
}
