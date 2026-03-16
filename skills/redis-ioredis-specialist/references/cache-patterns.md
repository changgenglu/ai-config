# Redis 快取設計模式

此文件定義專案中 Redis 快取的設計模式與最佳實踐。

---

## 快取鍵命名規範

### 格式

```
{module}:{entity}:{identifier}[:{subKey}]
```

### 範例

```
user:profile:123
user:profile:123:permissions
order:list:user:456:page:1
game:room:789:players
cache:lock:order:create:123
```

### 保留前綴

| 前綴 | 用途 |
|------|------|
| `cache:` | 資料快取 |
| `session:` | Session 儲存 |
| `lock:` | 分散式鎖 |
| `rate:` | 限流計數器 |
| `queue:` | 佇列 |
| `pubsub:` | 發布/訂閱頻道 |

---

## 快取策略

### 1. Cache-Aside (旁路快取) - 最常用

```
┌────────┐     1. Get    ┌───────┐
│ Client │──────────────►│ Cache │
└────────┘               └───────┘
    │                        │
    │ 2. Miss                │
    ▼                        ▼
┌────────┐     3. Query  ┌────────┐
│ App    │──────────────►│   DB   │
└────────┘               └────────┘
    │
    │ 4. Set Cache
    ▼
┌───────┐
│ Cache │
└───────┘
```

**實作**:
```typescript
async getUser(userId: string): Promise<User> {
  const cacheKey = `user:profile:${userId}`;
  
  // 1. 先查快取
  const cached = await this.redis.get(cacheKey);
  if (cached) {
    return JSON.parse(cached);
  }
  
  // 2. 快取未命中，查資料庫
  const user = await this.userRepository.findOne({ where: { id: userId } });
  
  // 3. 寫入快取
  if (user) {
    await this.redis.setex(cacheKey, 3600, JSON.stringify(user));
  }
  
  return user;
}
```

**適用場景**:
- 讀多寫少
- 可接受短暫的資料不一致

---

### 2. Write-Through (穿透寫入)

```
┌────────┐     1. Write  ┌───────┐
│ Client │──────────────►│ Cache │
└────────┘               └───────┘
                             │
                             │ 2. Sync Write
                             ▼
                         ┌────────┐
                         │   DB   │
                         └────────┘
```

**實作**:
```typescript
async updateUser(userId: string, data: UpdateUserDto): Promise<User> {
  const cacheKey = `user:profile:${userId}`;
  
  // 1. 更新資料庫
  const user = await this.userRepository.update(userId, data);
  
  // 2. 同步更新快取
  await this.redis.setex(cacheKey, 3600, JSON.stringify(user));
  
  return user;
}
```

**適用場景**:
- 需要強一致性
- 寫入頻率中等

---

### 3. Cache Invalidation (快取失效)

```typescript
async updateUser(userId: string, data: UpdateUserDto): Promise<User> {
  const cacheKey = `user:profile:${userId}`;
  
  // 1. 更新資料庫
  const user = await this.userRepository.update(userId, data);
  
  // 2. 刪除快取（下次讀取時重建）
  await this.redis.del(cacheKey);
  
  return user;
}
```

**適用場景**:
- 寫入頻繁
- 可接受首次讀取延遲

---

## 快取問題防護

### 快取穿透 (Cache Penetration)

**問題**: 查詢不存在的資料，每次都打到資料庫

**解決方案 1 - 快取空值**:
```typescript
async getUser(userId: string): Promise<User | null> {
  const cacheKey = `user:profile:${userId}`;
  
  const cached = await this.redis.get(cacheKey);
  if (cached === 'NULL_VALUE') {
    return null; // 已知不存在，直接返回
  }
  if (cached) {
    return JSON.parse(cached);
  }
  
  const user = await this.userRepository.findOne({ where: { id: userId } });
  
  if (user) {
    await this.redis.setex(cacheKey, 3600, JSON.stringify(user));
  } else {
    // 快取空值，短 TTL
    await this.redis.setex(cacheKey, 60, 'NULL_VALUE');
  }
  
  return user;
}
```

**解決方案 2 - 布隆過濾器**:
```typescript
// 初始化時將所有存在的 ID 加入布隆過濾器
// 查詢前先檢查布隆過濾器
```

---

### 快取雪崩 (Cache Avalanche)

**問題**: 大量快取同時過期，請求湧入資料庫

**解決方案 - 隨機 TTL**:
```typescript
function getRandomTtl(baseTtl: number): number {
  // 在基礎 TTL 上添加 0-10% 的隨機偏移
  const offset = Math.floor(Math.random() * baseTtl * 0.1);
  return baseTtl + offset;
}

await this.redis.setex(cacheKey, getRandomTtl(3600), data);
```

---

### 快取擊穿 (Cache Breakdown)

**問題**: 熱點資料過期，大量請求同時查詢

**解決方案 - 分散式鎖**:
```typescript
async getHotData(key: string): Promise<any> {
  const cacheKey = `cache:${key}`;
  const lockKey = `lock:${key}`;
  
  let data = await this.redis.get(cacheKey);
  if (data) {
    return JSON.parse(data);
  }
  
  // 嘗試獲取鎖
  const acquired = await this.redis.set(lockKey, '1', 'EX', 5, 'NX');
  
  if (acquired) {
    try {
      // 再次檢查（雙重檢查）
      data = await this.redis.get(cacheKey);
      if (data) {
        return JSON.parse(data);
      }
      
      // 查詢資料庫
      const result = await this.loadFromDb(key);
      await this.redis.setex(cacheKey, 3600, JSON.stringify(result));
      return result;
    } finally {
      await this.redis.del(lockKey);
    }
  } else {
    // 未獲得鎖，等待後重試
    await this.sleep(100);
    return this.getHotData(key);
  }
}
```

---

## 資料結構選擇指南

### String

**適用**: 簡單 KV、計數器、分散式鎖

```typescript
// 簡單快取
await redis.setex('user:123', 3600, JSON.stringify(userData));

// 計數器
await redis.incr('page:views:home');

// 分散式鎖
await redis.set('lock:order:create', '1', 'EX', 5, 'NX');
```

### Hash

**適用**: 物件屬性、可部分更新的資料

```typescript
// 儲存使用者資料（可單獨更新欄位）
await redis.hset('user:123', {
  name: 'John',
  email: 'john@example.com',
  age: '30',
});

// 讀取單一欄位
const email = await redis.hget('user:123', 'email');

// 更新單一欄位
await redis.hset('user:123', 'age', '31');
```

### List

**適用**: 佇列、最近記錄

```typescript
// 加入佇列
await redis.lpush('queue:notifications', JSON.stringify(notification));

// 取出處理
const item = await redis.brpop('queue:notifications', 0);

// 最近 N 筆記錄
await redis.lpush('user:123:recent', JSON.stringify(activity));
await redis.ltrim('user:123:recent', 0, 99); // 保留最近 100 筆
```

### Set

**適用**: 唯一值集合、標籤

```typescript
// 使用者標籤
await redis.sadd('user:123:tags', 'vip', 'active', 'male');

// 檢查成員
const isVip = await redis.sismember('user:123:tags', 'vip');

// 集合運算（共同好友）
const mutualFriends = await redis.sinter('user:123:friends', 'user:456:friends');
```

### Sorted Set

**適用**: 排行榜、延遲佇列

```typescript
// 排行榜
await redis.zadd('leaderboard:daily', score, `user:${userId}`);

// 取得前 10 名
const top10 = await redis.zrevrange('leaderboard:daily', 0, 9, 'WITHSCORES');

// 延遲佇列（以時間戳為分數）
await redis.zadd('queue:delayed', Date.now() + 60000, taskId);
```

---

## 監控指標

### 關鍵指標

| 指標 | 說明 | 告警閾值 |
|------|------|----------|
| 快取命中率 | hits / (hits + misses) | < 80% |
| 記憶體使用率 | used_memory / maxmemory | > 80% |
| 連線數 | connected_clients | > 1000 |
| 慢查詢 | slowlog | > 10ms |

### 監控指令

```bash
# 查看記憶體使用
redis-cli INFO memory

# 查看慢查詢
redis-cli SLOWLOG GET 10

# 查看連線數
redis-cli CLIENT LIST
```
