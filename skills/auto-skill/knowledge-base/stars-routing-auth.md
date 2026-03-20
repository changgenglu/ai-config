# Stars 路由與認證

## 🔧 路由分組與中介層對照
**日期：** 2026-03-16
**情境：** routes/api.php（616 行）分為多個認證群組，開發時需確認使用正確的認證

| 路由前綴 | 中介層 | 說明 |
|---------|--------|------|
| (無) | 無 | 公開端點：getTime, setOnlineUsersCount |
| (無) | `auth:company` | 內部公司：平台列表、RTP 更新 |
| `/backend` | `auth:back` | 後台控端：全面管理（200+ 路由） |
| `/backend/agent` | `auth:back` | 後台館端：所屬站台開關與報表 |
| `/client` | `auth:provider:maintain` | 客端：遊戲列表、連結、RTP |
| `/platform` | `auth:platform_user` | 廠商 QC：遊戲列表、RTP |
| `/qc` | `auth:provider` | QC 內部：分類列表、遊戲排序 |

## 🔧 認證中介層常數定義
**日期：** 2026-03-16
**情境：** Authenticate.php 定義認證類型，測試時需使用正確的認證標頭

```php
const MAINTAIN = 'maintain'           // 維護模式檢查
const PROVIDER = 'provider'           // 供應商認證
const CUSTOMER = 'customer'           // 客戶認證
const COMPANY = 'company'             // 公司內部認證
const PLATFORM_USER = 'platform_user' // 廠商 QC 認證
const BACK = 'back'                   // 後台認證（控端/館端）
```

## 🔧 後台控端 vs 館端區別
**日期：** 2026-03-16
**情境：** 兩者使用相同 `auth:back` 但路由前綴不同，權限範圍不同

- **控端**（`/backend`）：可操作所有站台，全域管理員
- **館端**（`/backend/agent`）：僅操作所屬單一站台
