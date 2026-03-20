# 後端開發最佳實踐

此分類記錄後端開發相關的經驗和最佳實踐。

---

## 🔧 ioredis keyPrefix 雙重前綴陷阱
**日期：** 2026-03-16
**情境：** 在使用 ioredis 並設定 `keyPrefix` 的專案中操作 Redis key

**問題：**
`redis.keys()` 回傳的是 Redis 原始 key（含 keyPrefix，如 `puppy:pp:token:xxx`），
直接傳給 `redis.get()` / `redis.del()` 時 ioredis 會**再加一次前綴**，
變成 `puppy:puppy:pp:token:xxx`，查詢永遠 null、刪除永遠無效。

**最佳實踐：**
```typescript
// ✅ 測試中清除所有 key：用 flushdb（確保測試用專屬 DB）
await redis.flushdb();

// ✅ 需要操作特定 key：先去除前綴
function stripKeyPrefix(rawKey: string): string {
    const prefix = `${process.env.PROJECT_NAME}:`;
    return rawKey.startsWith(prefix) ? rawKey.slice(prefix.length) : rawKey;
}
const keys = await redis.keys(`${process.env.PROJECT_NAME}:pp:token:*`);
const data = await redis.get(stripKeyPrefix(keys[0])); // ioredis 自動補回前綴

// ❌ 錯誤：直接把 keys() 結果傳給 get/del
await redis.get(keys[0]); // 查 puppy:puppy:pp:token:xxx → null
```

---

## 🔧 NestJS 整合測試：overrideProvider + 資源清理模式
**日期：** 2026-03-16
**情境：** NestJS 整合測試需要替換外部依賴（TCP、HTTP）並確保資源正確釋放

**最佳實踐：**
```typescript
// 1. 環境變數必須在 import 前設定（避免 ConfigModule 初始化拋錯）
if (!process.env.SOME_KEY) process.env.SOME_KEY = 'test-value';

// 2. Jest 自動設 NODE_ENV='test'，需強制覆蓋為實際環境
if (!process.env.NODE_ENV || process.env.NODE_ENV === 'test')
    process.env.NODE_ENV = 'dev';

// 3. 替換外部依賴
module = await Test.createTestingModule({ imports: [AppModule] })
    .overrideProvider(WalletService)
    .useValue({ getService: () => fakeTcpClient })
    .overrideProvider(HttpClientService)
    .useValue({ fetchData: mockFetch })
    .compile();

// 4. afterAll：優先讓 NestJS 自行關閉，失敗才 fallback 手動關閉
afterAll(async () => {
    try { await module?.close(); } catch (_) {
        // TypeORM onApplicationShutdown 在測試環境可能拋錯
        // fallback：手動關閉命名 DataSource
        try {
            const ds = module.get<DataSource>(getDataSourceToken('platform_pp'));
            if (ds?.isInitialized) await ds.destroy();
        } catch (_) {}
    }
    try { await redis?.quit(); } catch (_) {} // 避免 Jest open handles 警告
});
```

---

## 🔧 語意化異常原則
**日期：** 2026-03-16
**情境：** 後端 API 錯誤處理，需清楚表達錯誤類型與 HTTP 狀態碼

**最佳實踐：**
- 使用具體語意的 Exception 類別，讓框架自動對應 HTTP 狀態碼
- 禁止拋出通用 `Exception`（語意不明、難以統一處理）

```php
// ✅ PHP/Laravel 範例
throw new NotFoundException('Game not found');    // 404
throw new ParameterException('Invalid input');    // 422
throw new AuthException('Unauthorized');          // 401
throw new ForbiddenException('Access denied');    // 403
throw new ExternalException('Provider API error');// 外部錯誤

// ❌ 禁止
throw new \Exception('Error occurred');           // 語意不明
```

```typescript
// ✅ NestJS 範例
throw new NotFoundException('Game not found');
throw new BadRequestException('Invalid parameter');
throw new UnauthorizedException('Token invalid');
throw new InternalServerErrorException('Wallet timeout');

// ❌ 禁止
throw new Error('Something went wrong');
```

---

## 🔧 SQLite in-memory 替換 MySQL 加速測試
**日期：** 2026-03-16
**情境：** PHPUnit 整合測試需要資料庫，但不想依賴真實 MySQL 連線

**最佳實踐：**
- 測試環境將所有 MySQL 連接替換為 SQLite in-memory，速度快 10x+
- 需注意 SQLite 不支援部分 MySQL 特有語法（如 JSON 函數、全文搜尋）
- 真正需要驗證 MySQL 特性（如 unique constraint、外鍵）時才接真實 DB

```php
// Laravel 範例：TestCase 基類設定
protected function setUp(): void
{
    parent::setUp();
    $this->setUpSQLiteForTesting(); // 覆蓋所有 DB 連接為 SQLite
}

// phpunit.xml 環境設定
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## 🔧 Redis Key 集中管理用 Enum
**日期：** 2026-03-16
**情境：** 專案 Redis key 數量多（50+），散落各處難以維護

**最佳實踐：**
- 所有 Redis key 集中定義在單一 Enum 檔案，禁止硬編碼字串
- Key 格式統一：`{業務前綴}:{資源}:{識別碼}`
- Enum 提供 `build(...ids)` 動態組裝含 ID 的 key

```php
// PHP/Laravel 範例
enum RedisKey: string {
    case GAME = 'game';
    case GAME_CODE = 'game:code';

    public function build(string ...$parts): string {
        return implode(':', [$this->value, ...$parts]);
    }
}

// 使用
RedisKey::GAME->build($gameId);              // 'game:abc123'
RedisKey::GAME_CODE->build($platformId, $code); // 'game:code:1:slot'
```

```typescript
// TypeScript/NestJS 範例（Puppy 的做法）
enum PpRedisKey {
    TOKEN = 'pp:token',
    PLAYER = 'pp:player',
    TRANSACTION = 'pp:transaction',
}
// key 組裝集中在 Service，不在各處散落字串拼接
const key = `${PpRedisKey.PLAYER}:${groupId}:${userId}`;
```
