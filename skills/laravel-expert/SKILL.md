---
name: "laravel-expert"
description: "Activates when user requests Laravel framework guidance, version migration, Eloquent patterns, middleware design, service container usage, or Laravel best practices. Do NOT use for generic PHP questions unrelated to the framework. Examples: 'How to use Service Container?', 'Translate this to Laravel 12'."
---

# Laravel Expert Skill

## 🧠 Expertise

Laravel 框架專家，專精於跨版本開發、框架核心機制與最佳實務。

> **官方文檔參考**：https://laravel.com/docs/

---

## 1. 版本差異對照表

### 1.1 支援政策

| 版本 | PHP 版本 | 發布日期 | Bug 修復結束 | 安全修復結束 |
|-----|---------|---------|-------------|-------------|
| **9.x** | 8.0 - 8.2 | 2022-02 | 2023-08 | 2024-02 |
| **10.x** | 8.1 - 8.3 | 2023-02 | 2024-08 | 2025-02 |
| **11.x** | 8.2 - 8.4 | 2024-03 | 2025-09 | 2026-03 |
| **12.x** | 8.2+ | 2025-02 | 2026-08 | 2027-02 |

> **參考**：https://laravel.com/docs/12.x/releases#support-policy

### 1.2 核心差異對照

| 特性 | Laravel 9 | Laravel 11 | Laravel 12 |
|-----|-----------|------------|------------|
| **目錄結構** | 傳統完整 | 精簡化 | 精簡化 |
| **app/Http/Kernel.php** | ✅ 存在 | ❌ 移除 | ❌ 移除 |
| **Middleware 註冊** | Kernel | bootstrap/app.php | bootstrap/app.php |
| **Exception Handler** | app/Exceptions | bootstrap/app.php | bootstrap/app.php |
| **預設測試框架** | PHPUnit | Pest 可選 | Pest 預設 |
| **Model Casts** | `$casts` 屬性 | `casts()` 方法 | `casts()` 方法 |
| **前端工具** | Mix | Vite | Vite |
| **Starter Kits** | Breeze/Jetstream | Breeze/Jetstream | 新 Starter Kits |

### 1.3 Laravel 9 主要特性

```php
// 新版 Accessor / Mutator 語法
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function name(): Attribute
{
    return Attribute::make(
        get: fn (string $value) => ucfirst($value),
        set: fn (string $value) => strtolower($value),
    );
}

// Enum Casting
protected $casts = [
    'status' => OrderStatus::class,
];

// Controller Route Groups
Route::controller(OrderController::class)->group(function () {
    Route::get('/orders', 'index');
    Route::post('/orders', 'store');
});
```

### 1.4 Laravel 11/12 精簡化結構

```php
// bootstrap/app.php（Laravel 11+）
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            CheckUserStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json(['error' => 'Not found'], 404);
        });
    })
    ->create();
```

---

## 2. Service Container 與依賴注入

### 2.1 基本綁定

```php
// 綁定介面到實作
$this->app->bind(IPaymentGateway::class, StripeGateway::class);

// 單例綁定
$this->app->singleton(IPaymentGateway::class, StripeGateway::class);

// 實例綁定
$this->app->instance(IPaymentGateway::class, $gateway);

// 上下文綁定
$this->app->when(OrderService::class)
    ->needs(IPaymentGateway::class)
    ->give(StripeGateway::class);
```

### 2.2 自動解析

```php
// 自動注入（Constructor Injection）
class OrderController extends Controller
{
    public function __construct(
        private readonly IOrderService $orderService,
        private readonly IPaymentGateway $paymentGateway,
    ) {}
}

// 方法注入
public function store(Request $request, IOrderService $service)
{
    $order = $service->createOrder($request->validated());
}
```

---

## 3. Middleware 設計

### 3.1 Laravel 9 方式（Kernel）

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ...
    ],
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

protected $middlewareAliases = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
];
```

### 3.2 Laravel 11+ 方式（bootstrap/app.php）

```php
->withMiddleware(function (Middleware $middleware) {
    // 新增到 web 群組
    $middleware->web(append: [
        CheckUserStatus::class,
    ]);
    
    // 新增到 api 群組
    $middleware->api(prepend: [
        EnsureTokenIsValid::class,
    ]);
    
    // 別名
    $middleware->alias([
        'admin' => EnsureUserIsAdmin::class,
    ]);
    
    // 全域 Middleware
    $middleware->append(LogRequests::class);
})
```

### 3.3 自訂 Middleware

```php
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status !== 'active') {
            abort(403, 'Your account is not active.');
        }
        
        return $next($request);
    }
}
```

---


## 參考文件 (Progressive Disclosure)
為了保持主要文件簡潔，進階指引已移至 `references/` 目錄。當需要解決特定問題時，請查閱對應文件：
- [Laravel 進階組件 (Eloquent, Events, Form Request, Policy)](references/advanced_components.md)
- [升級遷移與常用命令、檢查表](references/operations_and_upgrades.md)
