<?php

namespace Calculator\Controllers;

/**
 * 前台控制器
 *
 * 負責渲染主頁面
 */
class FrontController
{
    public function index(): void
    {
        include ROOT_DIR . '/src/Views/layout.php';
    }
}
