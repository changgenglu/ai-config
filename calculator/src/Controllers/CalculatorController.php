<?php

namespace Calculator\Controllers;

use Calculator\Services\CalculatorService;

class CalculatorController
{
    public function calculate(): void
    {
        header('Content-Type: application/json');
        $body       = json_decode(file_get_contents('php://input'), true);
        $expression = trim($body['expression'] ?? '');

        if ($expression === '') {
            echo json_encode(['error' => '請輸入運算式']);
            return;
        }

        $service = new CalculatorService();
        echo json_encode($service->evaluate($expression));
    }
}
