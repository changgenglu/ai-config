# Puppy NestJS 整合測試最佳實踐

## 🔧 ioredis keyPrefix 雙重前綴陷阱
**日期：** 2026-03-16
**情境：** 在使用 ioredis 並設定 keyPrefix 的整合測試中清除 Redis 狀態

**問題：** `redis.keys()` 回傳含 keyPrefix 的原始 key（如 `puppy:pp:token:xxx`），
直接傳給 `redis.del()` 時 ioredis 會再加一次前綴 → 嘗試刪除 `puppy:puppy:pp:token:xxx`，實際上什麼都沒清除，測試互相污染。

**最佳實踐：**
```typescript
// ✅ 正確：整個 db 清除（測試環境專屬 DB）
await redis.flushdb();  // Redis DB=1 為測試專屬，安全

// ✅ 若要操作特定 key：先去除前綴
function stripKeyPrefix(rawKey: string): string {
    const prefix = `${process.env.PROJECT_NAME}:`;
    return rawKey.startsWith(prefix) ? rawKey.slice(prefix.length) : rawKey;
}
const keys = await redis.keys(`${process.env.PROJECT_NAME}:pp:token:*`);
const cleanKey = stripKeyPrefix(keys[0]); // 'pp:token:abc'
const data = await redis.get(cleanKey);    // ioredis 自動加前綴 → 'puppy:pp:token:abc'

// ❌ 錯誤：直接把 keys() 結果傳給 del/get
await redis.get(keys[0]);  // 查 puppy:puppy:pp:token:xxx → null
```

---

## 🔧 FakeTcpClientService 模擬 Stars Protocol
**日期：** 2026-03-16
**情境：** 整合測試中模擬總部錢包 TCP 回應，避免真實 TCP 連線

**關鍵設計：**
- 必須模擬 `RequestLogin`、`RequestGetBalance`、`RequestSpin` 三種封包類型
- 使用 `setImmediate` 確保非同步行為與真實 TCP 一致（waitEvent 先註冊 observer）
- 透過 `Receiver.receive(msg)` 觸發觀察者模式回應
- `reset()` 方法在 `afterEach` 重置所有狀態

```typescript
class FakeTcpClientService {
    public readonly Receiver = new Receiver();
    spinErr: string = XinStarsError.NoError;
    spinBalance = 5000;

    SendDataToServer(form: any): void {
        setImmediate(() => {
            if (form instanceof RequestSpin) {
                const msg = new SpinMessage();
                msg.xinkey = form.xinkey;
                msg.transactionId = form.transactionId;
                msg.err = this.spinErr;
                msg.balance = this.spinBalance;
                this.Receiver.receive(msg);
            }
        });
    }
}
```

---

## 🔧 NestJS 整合測試模組設置模式
**日期：** 2026-03-16
**情境：** 使用真實 MySQL + Redis，但 mock TCP 與外部 HTTP 呼叫

**標準設置：**
```typescript
// 環境變數必須在 import 前設定
if (!process.env.PLATFORM_PP_API_DOMAIN)
    process.env.PLATFORM_PP_API_DOMAIN = 'http://localhost:3099';
// ...其他必要 env vars

// Jest 自動設 NODE_ENV='test'，必須強制覆蓋
if (!process.env.NODE_ENV || process.env.NODE_ENV === 'test')
    process.env.NODE_ENV = 'dev';

module = await Test.createTestingModule({
    imports: [loadEnvConfig(), PpModule],
})
    .overrideProvider(WalletService)
    .useValue({ getService: () => fakeTcp })
    .overrideProvider(HttpClientService)
    .useValue({ fetchData: mockHttpFetch })
    .compile();
```

**清理注意：**
```typescript
afterAll(async () => {
    try { await module?.close(); } catch (_) {
        // TypeORM onApplicationShutdown 在測試環境可能拋 "Nest could not find DataSource"
        // fallback：手動關閉命名 DataSource
        try {
            const dataSource = module.get<DataSource>(getDataSourceToken('platform_pp'));
            if (dataSource?.isInitialized) await dataSource.destroy();
        } catch (_) {}
    }
    try { await redis?.quit(); } catch (_) {}  // 避免 Jest open handles 警告
});
```

---

## 🔧 PP 整合測試直接呼叫 Controller 的限制
**日期：** 2026-03-16
**情境：** 整合測試直接呼叫 Controller 方法而非發送 HTTP 請求

**已知限制：**
- **繞過 Middleware**：IP 白名單、Hash 驗證均被跳過，測試傳 `hash: 'test-bypassed'` 不會驗證
- **繞過路由**：無法測試 `@Controller/@Post` 的路徑設定，端點路徑問題（如 `/game/url` vs `/game/link`）測試不會報錯
- **修復方式**：補充 supertest E2E 測試，發送真實 HTTP 請求才能捕捉路徑問題

**測試格式（PP 反向呼叫）：**
```typescript
// 直接呼叫 Controller，hash 傳任意字串（Middleware 已被跳過）
const result = await controller.bet('tw', {
    userId: 'user-001',
    reference: 'ref-001',
    roundId: 'round-001',
    gameId: 'vs20olympgate',
    amount: 500,
    timestamp: String(Date.now()),
    hash: 'test-bypassed',
    providerId: 'pragmaticplay',
} as any);
```
