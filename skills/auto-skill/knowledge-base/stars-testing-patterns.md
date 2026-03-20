# Stars 測試模式

## 🔧 SQLite 記憶體資料庫測試
**日期：** 2026-03-16
**情境：** 所有 8 個 MySQL 連接在測試中自動轉為 SQLite in-memory，加快測試速度

**最佳實踐：**
- 整合測試用 `TestCase` + `RefreshDatabaseUsingSQLite` trait
- 單元測試用 `TestCase` + `SQLiteUnitTest`
- 必須在 `setUp()` 呼叫 `$this->setUpSQLiteForTesting()`

```php
// 整合測試範例
use Tests\RefreshDatabaseUsingSQLite;

class GameControllerTest extends TestCase
{
    use RefreshDatabaseUsingSQLite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSQLiteForTesting();
    }
}
```

**SQLite 自動覆蓋的 8 個連接：**
`stars`, `management`, `record`, `report`, `entry`, `platform_1`, `platform_2`, `platform_mg`

## 🔧 測試執行指令
**日期：** 2026-03-16
**情境：** 需在容器內執行（podman exec -it stars sh）

```bash
php artisan test                          # 全部測試
php artisan test --testsuite=Unit         # 單元測試
php artisan test --testsuite=Integration  # 整合測試
php artisan test --filter=GameController  # 指定測試類別
```

## 🔧 測試目錄結構
**日期：** 2026-03-16
**情境：** 按 Controller 分目錄，47 個整合測試目錄

```
tests/
├── Integration/          # 整合測試（按 Controller 分目錄）
├── Unit/Services/        # 單元測試
├── Feature/              # 功能測試
├── Support/              # 測試輔助 Trait
└── Mocks/Services/       # Mock 物件
```

**核心測試基類：**
- `tests/TestCase.php` — 基礎測試類別，初始化 SQLite 與 Redis
- `tests/RefreshDatabaseUsingSQLite.php` — SQLite 轉換 Trait（8 個連接）
- `tests/SQLiteUnitTest.php` — 單元測試基類
- `tests/OutpostIntegrationTestCase.php` — 外部整合測試基類
