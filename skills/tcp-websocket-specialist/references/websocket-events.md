# WebSocket 事件規範

此文件定義專案中 WebSocket 通訊的事件命名與資料格式規範。

---

## 命名規範

### 事件名稱格式

```
{domain}:{action}

或

{domain}:{entity}:{action}
```

**範例**
- `game:start`
- `game:round:complete`
- `notification:new`
- `player:joined`
- `chat:message:send`

---

## 系統事件

### 連線建立

**事件**: `connection` (內建)

**驗證流程**:
1. 客戶端在握手時透過 Query 或 Header 傳遞 Token
2. 服務端在 `handleConnection` 驗證 Token
3. 驗證失敗則斷開連線

```typescript
handleConnection(client: Socket) {
  const token = client.handshake.auth.token 
    || client.handshake.query.token;
  
  if (!this.validateToken(token)) {
    client.disconnect(true);
    return;
  }
  
  // 儲存使用者資訊
  client.data.userId = extractUserId(token);
}
```

### 連線中斷

**事件**: `disconnect` (內建)

**處理邏輯**:
- 清理房間資訊
- 通知其他玩家
- 釋放資源

---

## 遊戲事件

### 加入房間

**事件**: `room:join`

**Payload (Client → Server)**
```json
{
  "roomId": "room_123"
}
```

**Response (Server → Client)**
```json
{
  "event": "room:joined",
  "data": {
    "roomId": "room_123",
    "players": [
      { "userId": "user_1", "nickname": "Player1" },
      { "userId": "user_2", "nickname": "Player2" }
    ]
  }
}
```

### 離開房間

**事件**: `room:leave`

**Payload (Client → Server)**
```json
{
  "roomId": "room_123"
}
```

### 遊戲開始

**事件**: `game:start` (Server → Client, Broadcast)

**Payload**
```json
{
  "gameId": "game_456",
  "roomId": "room_123",
  "startTime": "2024-01-01T00:00:00Z",
  "config": {
    "duration": 300,
    "maxPlayers": 4
  }
}
```

### 遊戲動作

**事件**: `game:action`

**Payload (Client → Server)**
```json
{
  "gameId": "game_456",
  "actionType": "bet",
  "data": {
    "amount": 100,
    "position": "player"
  }
}
```

### 遊戲結算

**事件**: `game:settle` (Server → Client)

**Payload**
```json
{
  "gameId": "game_456",
  "result": {
    "winner": "banker",
    "players": [
      {
        "userId": "user_1",
        "bet": 100,
        "payout": 0,
        "balanceAfter": 900
      }
    ]
  }
}
```

---

## 通知事件

### 系統通知

**事件**: `notification:system` (Server → Client)

**Payload**
```json
{
  "id": "notif_001",
  "type": "maintenance",
  "title": "系統維護通知",
  "content": "系統將於 2024-01-01 02:00 進行維護",
  "priority": "high",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

### 個人通知

**事件**: `notification:personal` (Server → Specific Client)

**Payload**
```json
{
  "id": "notif_002",
  "type": "balance_update",
  "title": "餘額變動通知",
  "content": "您的帳戶已入帳 $100",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

---

## 錯誤處理

### 錯誤回應格式

**事件**: `error`

**Payload**
```json
{
  "code": 4001,
  "message": "房間不存在",
  "originalEvent": "room:join",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

### 錯誤碼定義

| 錯誤碼 | 說明 |
|--------|------|
| 4001 | 房間不存在 |
| 4002 | 房間已滿 |
| 4003 | 遊戲進行中，無法加入 |
| 4004 | 餘額不足 |
| 4005 | 操作過於頻繁 |
| 4100 | 認證失敗 |
| 4101 | Token 過期 |
| 5000 | 伺服器內部錯誤 |

---

## 心跳機制

### Ping/Pong

Socket.IO 內建心跳機制，預設配置：

```typescript
@WebSocketGateway({
  pingTimeout: 60000,    // 60 秒無回應視為斷線
  pingInterval: 25000,   // 每 25 秒發送 ping
})
```

### 自訂心跳

若需自訂心跳邏輯：

**事件**: `heartbeat`

**Payload (Client → Server)**
```json
{
  "timestamp": 1704067200
}
```

**Response (Server → Client)**
```json
{
  "event": "heartbeat:ack",
  "timestamp": 1704067200,
  "serverTime": 1704067201
}
```
