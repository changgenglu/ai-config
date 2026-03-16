# HTTP 狀態碼參考

## 1xx - 資訊性回應

| 狀態碼 | 名稱 | 說明 |
|-------|------|------|
| 100 | Continue | 伺服器已收到請求標頭，客戶端應繼續發送請求主體 |
| 101 | Switching Protocols | 伺服器同意切換協議 |

---

## 2xx - 成功

| 狀態碼 | 名稱 | 說明 | API 使用場景 |
|-------|------|------|-------------|
| **200** | OK | 請求成功 | GET 查詢成功、PUT/PATCH 更新成功 |
| **201** | Created | 新資源已創建 | POST 新增資源成功 |
| 202 | Accepted | 請求已接受但尚未處理 | 非同步任務已排入佇列 |
| **204** | No Content | 成功但無回應內容 | DELETE 刪除成功 |

---

## 3xx - 重新導向

| 狀態碼 | 名稱 | 說明 |
|-------|------|------|
| 301 | Moved Permanently | 資源已永久移動到新位置 |
| 302 | Found | 資源臨時移動 |
| 304 | Not Modified | 資源未修改，可使用快取 |
| 307 | Temporary Redirect | 臨時重新導向，保持原請求方法 |
| 308 | Permanent Redirect | 永久重新導向，保持原請求方法 |

---

## 4xx - 客戶端錯誤

| 狀態碼 | 名稱 | 說明 | API 使用場景 |
|-------|------|------|-------------|
| **400** | Bad Request | 請求格式錯誤 | JSON 格式錯誤、缺少必要欄位 |
| **401** | Unauthorized | 未認證 | 未提供 Token、Token 過期 |
| **403** | Forbidden | 已認證但無權限 | 存取被拒絕的資源 |
| **404** | Not Found | 資源不存在 | 查詢不存在的記錄 |
| 405 | Method Not Allowed | 請求方法不支援 | 對只讀資源使用 DELETE |
| 408 | Request Timeout | 請求超時 | 客戶端等待過久 |
| 409 | Conflict | 資源衝突 | 重複建立、樂觀鎖衝突 |
| 413 | Payload Too Large | 請求主體過大 | 上傳檔案超過限制 |
| **422** | Unprocessable Entity | 驗證失敗 | 欄位驗證錯誤 |
| 429 | Too Many Requests | 請求頻率過高 | 觸發速率限制 |

---

## 5xx - 伺服器錯誤

| 狀態碼 | 名稱 | 說明 | 處理建議 |
|-------|------|------|---------|
| **500** | Internal Server Error | 伺服器內部錯誤 | 記錄詳細錯誤、通知開發團隊 |
| 502 | Bad Gateway | 上游伺服器回應無效 | 檢查反向代理設定 |
| 503 | Service Unavailable | 服務暫時不可用 | 回傳 Retry-After 標頭 |
| 504 | Gateway Timeout | 上游伺服器回應超時 | 增加超時設定、優化上游服務 |

---

## 速率限制標頭

```http
HTTP/1.1 429 Too Many Requests
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1609459200
Retry-After: 60
```

| 標頭 | 說明 |
|-----|------|
| `X-RateLimit-Limit` | 時間窗口內的請求上限 |
| `X-RateLimit-Remaining` | 剩餘可用請求數 |
| `X-RateLimit-Reset` | 限制重置的 Unix 時間戳 |
| `Retry-After` | 建議等待秒數 |

---

## 快速參考

### 成功回應選擇
- 查詢資料 → `200 OK`
- 新增資源 → `201 Created`
- 更新資源 → `200 OK`
- 刪除資源 → `204 No Content`

### 錯誤回應選擇
- 請求格式錯誤 → `400 Bad Request`
- 未登入 → `401 Unauthorized`
- 無權限 → `403 Forbidden`
- 資源不存在 → `404 Not Found`
- 驗證失敗 → `422 Unprocessable Entity`
- 頻率限制 → `429 Too Many Requests`
- 伺服器錯誤 → `500 Internal Server Error`
