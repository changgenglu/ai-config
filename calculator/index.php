<?php
/**
 * PHP 計算機 - 主入點
 *
 * 職責：
 * - 自動載入 src/ 下的類別
 * - 靜態資源直通
 * - 路由分發
 */

// 定義根目錄常數
define('ROOT_DIR', __DIR__);

// 自動載入機制：類別 namespace 對應目錄結構
spl_autoload_register(function ($class) {
    // 確保命名空間以 Calculator 開頭
    if (strpos($class, 'Calculator\\') === 0) {
        // 移除 Calculator\ 前綴
        $relative = substr($class, 11);
        // 將命名空間轉換為檔案路徑
        $file = ROOT_DIR . '/src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 靜態資源直通機制
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 移除開頭 / 用於檔案查找
$uri_path = ltrim($uri, '/');
$staticFile = ROOT_DIR . '/public/' . $uri_path;

// 檢查是否為靜態資源請求且檔案存在
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|webp|woff|woff2|ttf)$/', $uri) && is_file($staticFile)) {
    // 設定對應的 Content-Type
    $mimeTypes = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];

    $ext = pathinfo($staticFile, PATHINFO_EXTENSION);
    $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=3600');
    readfile($staticFile);
    exit;
}

// 所有非靜態資源請求走路由
$router = new \Calculator\Router();
$router->dispatch();
