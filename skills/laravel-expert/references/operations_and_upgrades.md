# 升級遷移與營運

## 8. 升級遷移指南

### 8.1 Laravel 9 → 10

| 變更項目 | 處理方式 |
|---------|---------|
| PHP 8.1 最低版本 | 升級 PHP |
| PHPUnit 10 | 更新測試配置 |
| Pest 2 | 更新 Pest |

### 8.2 Laravel 10 → 11

| 變更項目 | 處理方式 |
|---------|---------|
| 目錄結構精簡化 | 可選擇保留或遷移 |
| Kernel.php 移除 | 遷移到 bootstrap/app.php |
| Exception Handler 移除 | 遷移到 bootstrap/app.php |
| Model casts() 方法 | 可選擇遷移 |

### 8.3 Laravel 11 → 12

> **預計升級時間**：5 分鐘（官方建議）
> **參考**：https://laravel.com/docs/12.x/upgrade

| 影響程度 | 項目 |
|---------|------|
| **高** | 更新依賴 (`laravel/framework` ^12.0) |
| **中** | Model UUIDv7 變更 |
| **低** | Carbon 3、Image 驗證排除 SVG |

```bash
# 升級命令
composer require laravel/framework:^12.0 phpunit/phpunit:^11.0 pestphp/pest:^3.0
```

---

## 9. 常用 Artisan 命令

```bash
# 開發
php artisan serve
php artisan tinker
php artisan route:list

# 快取
php artisan optimize
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 資料庫
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed

# 產生器
php artisan make:model Order -mfsc  # Model + Migration + Factory + Seeder + Controller
php artisan make:request StoreOrderRequest
php artisan make:policy OrderPolicy --model=Order
php artisan make:event OrderPlaced
php artisan make:listener SendOrderNotification --event=OrderPlaced
```

---

## 10. Laravel 檢查清單

### 版本相容
- [ ] PHP 版本是否符合要求？
- [ ] 依賴套件是否支援目標 Laravel 版本？
- [ ] 是否有廢棄 API 使用？

### 架構設計
- [ ] 是否使用 Service 層處理業務邏輯？
- [ ] 是否使用 Repository 模式？
- [ ] 是否正確使用依賴注入？
- [ ] 是否為高風險操作設定 Policy？

### 效能
- [ ] 是否使用 Eager Loading？
- [ ] 是否設定適當快取？
- [ ] 是否使用 Queue 處理耗時任務？
