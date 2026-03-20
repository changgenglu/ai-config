<?php

namespace Calculator;

use Calculator\Controllers\FrontController;
use Calculator\Controllers\CalculatorController;
use Calculator\Controllers\ConverterController;
use Calculator\Controllers\ExchangeController;

/**
 * 路由分發器
 *
 * 負責解析請求、對應控制器方法、處理 404
 */
class Router
{
    private string $method;
    private string $uri;
    private array $routes = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        // 移除 query string，只保留路徑
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // 移除開頭 / 並去除尾部 /（除非是根路徑）
        $this->uri = trim($this->uri, '/');
        if ($this->uri === '') {
            $this->uri = '/';
        } else {
            $this->uri = '/' . $this->uri;
        }

        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        // 路由表定義
        $this->routes = [
            'GET' => [
                '/' => [FrontController::class, 'index'],
            ],
            'POST' => [
                '/api/calculate' => [CalculatorController::class, 'calculate'],
                '/api/convert' => [ConverterController::class, 'convert'],
                '/api/exchange' => [ExchangeController::class, 'exchange'],
            ],
        ];
    }

    public function dispatch(): void
    {
        // 查找對應的路由
        if (isset($this->routes[$this->method][$this->uri])) {
            [$controller, $method] = $this->routes[$this->method][$this->uri];
            $controllerInstance = new $controller();
            $controllerInstance->$method();
            return;
        }

        // 404 處理
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not Found']);
    }
}
