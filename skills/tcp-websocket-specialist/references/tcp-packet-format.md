# TCP 封包格式定義

此文件定義專案中 TCP 通訊（例如：錢包通訊）的封包格式規範。

---

## 基礎封包結構

```
+----------------+------------------+
|    Header      |      Body        |
|   (4 bytes)    |   (N bytes)      |
+----------------+------------------+
```

### Header (4 bytes)

| 欄位 | 型態 | 大小 | 說明 |
|------|------|------|------|
| length | uint32 | 4 bytes | Body 長度（Big-Endian） |

### Body (N bytes)

Body 為 JSON 格式的訊息內容，編碼使用 UTF-8。

---

## 訊息格式

### 請求訊息 (Request)

```json
{
  "action": "string",      // 操作類型
  "requestId": "string",   // 請求唯一識別碼 (UUID)
  "timestamp": 1704067200, // Unix 時間戳 (秒)
  "payload": {}            // 操作資料
}
```

### 回應訊息 (Response)

```json
{
  "action": "string",      // 對應的操作類型
  "requestId": "string",   // 對應的請求 ID
  "timestamp": 1704067200, // Unix 時間戳 (秒)
  "code": 0,               // 狀態碼 (0=成功, 其他=錯誤)
  "message": "string",     // 狀態訊息
  "data": {}               // 回應資料
}
```

---

## 操作類型 (Action Types)

### 錢包餘額查詢

**Request**
```json
{
  "action": "wallet:balance:query",
  "requestId": "550e8400-e29b-41d4-a716-446655440000",
  "timestamp": 1704067200,
  "payload": {
    "userId": "user_123",
    "currency": "USD"
  }
}
```

**Response**
```json
{
  "action": "wallet:balance:query",
  "requestId": "550e8400-e29b-41d4-a716-446655440000",
  "timestamp": 1704067201,
  "code": 0,
  "message": "success",
  "data": {
    "balance": 1000.00,
    "currency": "USD",
    "updatedAt": "2024-01-01T00:00:00Z"
  }
}
```

### 錢包餘額異動

**Request**
```json
{
  "action": "wallet:balance:update",
  "requestId": "550e8400-e29b-41d4-a716-446655440001",
  "timestamp": 1704067200,
  "payload": {
    "userId": "user_123",
    "amount": 100.00,
    "operation": "credit",  // credit | debit
    "reference": "order_456",
    "description": "獎金發放"
  }
}
```

**Response**
```json
{
  "action": "wallet:balance:update",
  "requestId": "550e8400-e29b-41d4-a716-446655440001",
  "timestamp": 1704067201,
  "code": 0,
  "message": "success",
  "data": {
    "transactionId": "txn_789",
    "balanceBefore": 1000.00,
    "balanceAfter": 1100.00,
    "currency": "USD"
  }
}
```

---

## 錯誤碼定義

| 錯誤碼 | 說明 |
|--------|------|
| 0 | 成功 |
| 1001 | 無效的請求格式 |
| 1002 | 缺少必要欄位 |
| 2001 | 使用者不存在 |
| 2002 | 錢包餘額不足 |
| 2003 | 交易處理中，請稍後重試 |
| 3001 | 簽章驗證失敗 |
| 9999 | 系統內部錯誤 |

---

## 安全性規範

### 簽章驗證

敏感操作（如餘額異動）需包含簽章欄位：

```json
{
  "action": "wallet:balance:update",
  "requestId": "...",
  "timestamp": 1704067200,
  "payload": { ... },
  "signature": "sha256_hmac_signature_here"
}
```

**簽章計算方式**
```
signature = HMAC-SHA256(
  key = SHARED_SECRET,
  data = action + requestId + timestamp + JSON.stringify(payload)
)
```

### 時間戳驗證

- 請求時間戳必須在伺服器當前時間 ±5 分鐘內
- 超時請求應回傳錯誤碼 1003
