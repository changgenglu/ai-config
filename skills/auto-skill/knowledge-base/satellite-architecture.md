# Satellite 專案架構知識庫

## 🔧 Service Factory 模式
**日期：** 2026-03-16
**情境：** 呼叫業務邏輯 Service 的標準方式
**最佳實踐：**
- 標準呼叫：`app('Service')::init('ServiceName')::method()`
- 範例：`app('Service')::init('User')::isAgent($operator)`
- 範例：`app('Service')::init('Feature')::create($data)`
- ServiceFactory 實作單例模式，同一 Request 內只建立一次實例
- 靜態方法也可直接呼叫：`\App\Services\AnomalyDetector::checkFailedLoginAttempts()`
- 取得當前登入使用者：`app('Backend')::user()`

## 🔧 認證 Middleware 架構
**日期：** 2026-03-16
**情境：** 理解路由保護層級
**最佳實踐：**
- 三個作用域：`auth:provider`（站台）、`auth:backend`（管端/控端）、`auth:company`（公司內部 IP）
- 路由必須套 `auth:provider` 外層，再套 `auth:backend` 內層
- 取得當前使用者：`app('Backend')::user()`（backend 作用域）
- IP 白名單：特定 IP + Server Token 可繞過認證
- 判斷使用者類型：`app('Service')::init('User')::isCtl($operator)`（控端）、`isAgent()`（代理）

## 🔧 Model 命名慣例
**日期：** 2026-03-16
**情境：** 建立或查詢 Model 時
**最佳實踐：**
- 資料表名稱用**單數**（`user` 非 `users`，`feature` 非 `features`）
- 連線名稱：`$connection = 'satellite'`
- 大多數 Model `$timestamps = false`（自行管理時間戳）
- 部分 Model `$incrementing = false`（非自增 ID）
- 普遍使用 `SoftDeletes`
- 資料庫連線設定在 `config/database.php`，連線名為 `satellite`

## 🔧 多租戶隔離設計
**日期：** 2026-03-16
**情境：** 查詢資料需要 provider 隔離時
**最佳實踐：**
- 核心表均有 `provider_id` 欄位（user、history、user_sign_in_record、msg_board）
- 查詢時務必帶入 `provider_id` 做資料隔離
- Provider（站台）= 系統租戶；Platform（遊戲供應商）= 提供遊戲的廠商（兩者勿混用）

## 🔧 快取架構
**日期：** 2026-03-16
**情境：** 實作快取讀寫
**最佳實踐：**
- 雙層快取：Redis（高頻 KV）+ Memcached（分散式快取）
- Redis 連線：`Redis::connection('satellite')`
- Cache Store：`Cache::store('memcached')`
- 所有 Redis Key 定義集中在 `app/Enums/RedisKey.php`，新增前先確認是否已有對應 key
- 快取失效端點模式：`POST /xxx/clear_cache` 或 `POST /xxx/removeCache`

## 🔧 權限系統
**日期：** 2026-03-16
**情境：** 實作功能/選單權限控制
**最佳實踐：**
- Feature（選單/功能）= 權限控制單位，支援 `parent_id` 階層結構
- Role（角色）= 樹狀結構，有 `depth` 和 `path` 欄位
- 關聯表：`UserFeature`（使用者-功能）、`RoleFeature`（角色-功能）、`RoleDefaultFeature`
- 建立 Feature 後會自動執行 `addAdminPermission` 綁定管理員權限
- `is_admin` 欄位區分控端（Admin）與管端（Provider User）可見功能

## 🔧 Controller 設計模式
**日期：** 2026-03-16
**情境：** 撰寫新 Controller 方法時
**最佳實踐：**
- 標準流程：`$this->validate($request, [...])` → 取得 operator → 呼叫 Service → 回傳結果
- 自訂例外層：`AuthException`、`PermissionException`、`NotFoundException`、`ForbiddenException`
- Controller 直接在 `app/Http/Controllers/` 下，無子目錄分組
- 命名：`{Entity}Controller`，多實體關聯：`{Parent}{Child}Controller`

## 🔧 業務術語對照
**日期：** 2026-03-16
**情境：** 閱讀或撰寫程式碼時，確保術語正確
**最佳實踐：**
- Provider = 站台（租戶/客戶端），對應 `provider_id`
- Platform = 遊戲供應商（如 PG, TP）
- Admin = 控端（最高權限管理者）
- Provider User = 管端（站台管理者）
- Feature = 選單/功能（權限控制單位）
- Captain = 隊長（`is_captain` 欄位，站台特定功能）
- 以上術語**嚴格區分，禁止混用**
