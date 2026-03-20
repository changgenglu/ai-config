# Stars 專案總覽

## 🔧 專案基本資訊
**日期：** 2026-03-16
**情境：** Laravel 遊戲平台後端中間層系統，整合多個遊戲廠商供多站台使用

**技術棧：**
- 後端：Laravel 9.x (PHP 8.0+)
- 資料庫：MySQL（多資料庫架構，7 個連接）
- 快取：Redis（8 個連接分層）
- 佇列：Laravel Horizon
- API 文檔：HG ApiDoc
- 容器：Podman（容器名 `stars`，端口 8082）

**專案規模統計：**
- Controllers：52 個
- Services：109 個
- Models：61 個
- Jobs：24 個
- Console Commands：35 個
- Interfaces：34 個
- Redis Keys：175+
- Middleware：11 個
- Integration Tests：47 個目錄

## 🔧 關鍵術語定義
**情境：** Provider 與 Platform 易混淆，專案中有嚴格定義

- **Provider（站台）**：營運平台、管端，即下游客戶
- **Platform（供應商）**：遊戲廠商（如 MG, AB, FTG, BW, NW）

## 🔧 關鍵檔案路徑
**情境：** 快速定位核心檔案

| 檔案 | 路徑 |
|------|------|
| 主路由 | `routes/api.php`（616 行） |
| Redis Key 管理 | `app/Enums/RedisKey.php`（175+ keys） |
| 服務工廠 | `app/Services/ServiceFactory.php` |
| 認證中介層 | `app/Http/Middleware/Authenticate.php` |
| 異常處理 | `app/Exceptions/Handler.php` |
| 測試基類 | `tests/TestCase.php` |
| SQLite 設定 | `tests/RefreshDatabaseUsingSQLite.php` |
| 廠商抽象基類 | `app/Platforms/Abs.php` |

## 🔧 目錄結構概要
**情境：** 快速理解各目錄職責

```
app/
├── Console/Commands/   # 35 個指令（含 BW/, FTG/, YGR/, FC/, GameSort/ 子目錄）
├── Enums/              # RedisKey.php
├── Exceptions/         # 10 個異常類（繼承 AbstractException）
├── Http/
│   ├── Controllers/    # 52 個 Controller
│   └── Middleware/     # 11 個中介層
├── Interfaces/         # 34 個介面（多為常數定義，非方法簽名）
├── Jobs/               # 24 個佇列任務（含 BW/, FTG/, Games/, GameSort/, Reports/ 子目錄）
├── Library/            # 6 個外部整合庫
├── Models/             # 61 個 Eloquent Model
├── Platforms/          # 廠商整合層（Seamless/ 使用中，Wallet/ 已棄用）
├── Providers/          # AppServiceProvider
└── Services/           # 109 個 Service（含多個子目錄）
```
