# Stars 架構模式

## 🔧 ServiceFactory 單例模式
**日期：** 2026-03-16
**情境：** 所有 Service 必須透過 ServiceFactory 初始化，確保同類別單例

**最佳實踐：**
- 使用 `app('Service')->init('ClassName')` 取得 Service 實例
- 相同 ClassName 多次呼叫回傳同一實例（節省資源）
- ServiceFactory 位於 `app/Services/ServiceFactory.php`

```php
// ✅ 正確
$gamesService = app('Service')->init('Games');
$providersService = app('Service')->init('Providers');

// ❌ 錯誤：不應直接 new
$gamesService = new \App\Services\Games();
```

## 🔧 RedisKey Enum 統一管理
**日期：** 2026-03-16
**情境：** 所有 Redis Key 必須透過 RedisKey Enum 定義，禁止硬編碼

**最佳實踐：**
- 所有 Key 定義於 `app/Enums/RedisKey.php`（175+ keys）
- Key 格式：`{業務前綴}:{資源}:{識別碼}`（系統自動加 `stars:` 前綴）
- 使用 `.build()` 動態組裝、`.prefix()` 取得前綴

```php
// ✅ 正確
RedisKey::GAME->build($gameId)              // 'game:{gameId}'
RedisKey::GAME_CODE->build($platformId, $code)  // 'game:code:{platformId}:{code}'

// ❌ 錯誤
$redis->get("game_code:{$platformId}:{$code}")  // 禁止硬編碼
```

## 🔧 Redis 8 層連接分層
**日期：** 2026-03-16
**情境：** 不同業務使用不同 Redis 連接，避免 Key 衝突

| 連接 | 用途 | 代表 Key 前綴 |
|------|------|-------------|
| `game` | 遊戲快取、排序、RTP | `game:*`, `game:rtp:*`, `game:sort:*` |
| `platform` | 平台快取、設定 | `platform:*`, `provider:platform:*` |
| `provider` | 供應商快取 | `provider:*` |
| `user` | 用戶資料、別名、喜好 | `user:*`, `customer:*`, `favorite:*` |
| `cache` | 維護、廣宣快取 | `maintain:*`, `banner:*`, `announce:*` |
| `session` | 會話管理 | `session:*` |
| `report` | 報表統計 | `report:*`, `rank:*` |
| `command_lock` | 指令鎖定 | 指令專用鎖 |

```php
// 指定 Redis 連接
Redis::connection('game')->set($key, $value);
Redis::connection('platform')->get($key);
```

## 🔧 多資料庫架構
**日期：** 2026-03-16
**情境：** 專案使用 7 個 MySQL 連接，測試時轉為 SQLite

| 連接名稱 | 用途 |
|---------|------|
| `management` | 主要業務資料（預設連接） |
| `record` | 操作紀錄 |
| `report` | 統計報表 |
| `entry` | 錢包交易流水 |
| `stars` | 星城系統資料 |
| `platform_1` | FTG 廠商專用 |
| `platform_2` | BW 廠商專用 |
| `platform_mg` | MG 廠商專用 |

```php
// Model 指定連接
class Games extends Model {
    protected $connection = 'management';
}

// 動態切換
DB::connection('report')->table('daily_report')->get();
```

## 🔧 分層職責說明
**日期：** 2026-03-16
**情境：** 標準 MVC + Service 層強化，各層嚴格分工

| 層級 | 職責 | 注意事項 |
|------|------|----------|
| Controller | 請求驗證、回應格式化 | 複雜邏輯委派 Service |
| Service | 核心業務邏輯 | 透過 ServiceFactory 初始化 |
| Model | 資料存取與關聯 | 需指定 connection |
| Interface | 常數定義（多數非方法簽名） | |
| Enum | 列舉定義（RedisKey 等） | |
| Platform | 廠商介接層 | Seamless/ 使用中，Wallet/ 棄用 |
| Job | 佇列任務 | 報表生成、遊戲排序等 |
| Command | 排程與手動指令 | 含廠商子目錄 |
| Library | 外部整合庫 | HttpClient、VertexAI 等 |

## 🔧 異常處理體系
**日期：** 2026-03-16
**情境：** 使用語意化異常，禁止通用 Exception

```php
// ✅ 使用具體語意異常
throw new NotFoundException('Game not found');      // 404
throw new ParameterException('Invalid game ID');    // 422
throw new AuthException('Unauthorized');            // 401
throw new ForbiddenException('Access denied');      // 403
throw new ExternalException('Platform API error');  // 外部錯誤
throw new RuntimeException('Unexpected error');     // 500

// ❌ 禁止
throw new \Exception('Error occurred');
```

**可用異常類別（繼承 AbstractException）：**
NotFoundException, ParameterException, RuntimeException, AuthException,
ForbiddenException, PermissionException, ExternalException, MaintainException
