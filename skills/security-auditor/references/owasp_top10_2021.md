# OWASP Top 10 2021 完整參考

> 來源: [OWASP Top 10:2021](https://owasp.org/Top10/)

---

## A01:2021 – Broken Access Control (存取控制失效)

### 說明
存取控制負責實施策略，確保使用者無法在其預期權限之外執行操作。

### 常見漏洞
- 繞過存取控制檢查（修改 URL、API 請求）
- 允許檢視或編輯他人帳戶（IDOR）
- 權限提升（以訪客身份存取管理員功能）
- CORS 配置錯誤

### 防護措施
- 預設拒絕存取（除公開資源外）
- 實施一致的存取控制機制
- 記錄存取控制失敗並適時告警
- 停用目錄列表
- 對 Token 設置合理的失效時間

---

## A02:2021 – Cryptographic Failures (加密機制失效)

### 說明
與密碼學相關的失效，通常導致敏感資料暴露。

### 常見漏洞
- 以明文傳輸或儲存敏感資料
- 使用過時或弱加密演算法（MD5、SHA1）
- 未強制使用 HTTPS
- 使用硬編碼的加密金鑰

### 防護措施
- 識別並分類敏感資料
- 傳輸時使用 TLS 1.2+
- 使用強雜湊函數（bcrypt、Argon2）儲存密碼
- 不要在程式碼中硬編碼金鑰
- 禁用快取敏感資料

### 安全雜湊函數建議

| 用途 | 建議演算法 | 避免使用 |
|-----|----------|---------|
| 密碼雜湊 | bcrypt, Argon2 | MD5, SHA1, SHA256 (無鹽) |
| 資料完整性 | SHA-256, SHA-3 | MD5, SHA1 |
| 訊息認證 | HMAC-SHA256 | 自製 MAC |

---

## A03:2021 – Injection (注入攻擊)

### 說明
當不受信任的資料作為命令或查詢的一部分發送到解譯器時。

### 常見漏洞
- SQL Injection
- NoSQL Injection
- OS Command Injection
- LDAP Injection
- XPath Injection

### 防護措施
- 使用參數化查詢（Prepared Statements）
- 使用 ORM 而非原始 SQL
- 對所有輸入進行驗證與過濾
- 使用 LIMIT 防止大規模資料洩漏

### SQL Injection 範例

```php
// ❌ 易受攻擊
$query = "SELECT * FROM users WHERE id = " . $_GET['id'];

// ✅ 參數化查詢 (PDO)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);

// ✅ Eloquent ORM
User::where('id', request('id'))->first();
```

---

## A04:2021 – Insecure Design (不安全設計)

### 說明
與設計缺陷相關的風險，強調在開發前進行威脅建模的重要性。

### 常見問題
- 缺少業務邏輯驗證
- 無速率限制
- 過度信任客戶端輸入
- 缺少深度防禦

### 防護措施
- 建立安全開發生命週期（SDLC）
- 進行威脅建模
- 編寫安全單元測試
- 使用經過審查的設計模式

---

## A05:2021 – Security Misconfiguration (安全配置錯誤)

### 說明
預設配置不安全、不完整或臨時配置導致的安全問題。

### 常見問題
- 預設帳號密碼未更改
- 生產環境開啟 Debug 模式
- 錯誤訊息暴露敏感資訊
- 不必要的功能或服務啟用
- 安全標頭未設置

### 防護措施
- 自動化部署配置
- 移除或不安裝未使用的功能
- 定期審查配置
- 使用安全標頭

### 建議安全標頭

```http
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Security-Policy: default-src 'self'
X-XSS-Protection: 1; mode=block
```

---

## A06:2021 – Vulnerable and Outdated Components (易受攻擊元件)

### 說明
使用含有已知漏洞的軟體元件。

### 常見問題
- 使用過時的框架或函式庫
- 不知道所有使用中元件的版本
- 未及時套用安全補丁

### 防護措施
- 定期掃描相依套件漏洞
- 只從官方來源取得元件
- 監控元件的安全聲明
- 移除未使用的相依

### 掃描工具

| 語言/平台 | 工具 |
|----------|------|
| PHP | `composer audit` |
| Node.js | `npm audit`, `yarn audit` |
| Python | `pip-audit`, `safety` |
| 通用 | Snyk, Dependabot, OWASP Dependency-Check |

---

## A07:2021 – Identification and Authentication Failures (身份驗證失效)

### 說明
與身份確認、認證和 Session 管理相關的缺陷。

### 常見問題
- 允許弱密碼
- 無登入失敗次數限制
- Session ID 暴露於 URL
- Session 未正確失效

### 防護措施
- 實施多因素認證（MFA）
- 強制密碼複雜度政策
- 限制登入失敗次數
- 登出時使 Session 失效
- 使用安全的 Session 管理

### 密碼政策建議

| 項目 | 建議值 |
|-----|-------|
| 最短長度 | 8 字元 |
| 複雜度 | 大小寫 + 數字 |
| 歷史記錄 | 不可重複最近 5 組 |
| 失敗鎖定 | 5 次失敗鎖定 15 分鐘 |

---

## A08:2021 – Software and Data Integrity Failures (軟體與資料完整性失效)

### 說明
與軟體更新、關鍵資料和 CI/CD 管道相關的完整性問題。

### 常見問題
- 不安全的反序列化
- 未驗證更新來源
- CI/CD 管道缺乏完整性檢查

### 防護措施
- 使用數位簽章驗證軟體來源
- 確保 CI/CD 有適當的權限控制
- 避免反序列化不受信任的資料

---

## A09:2021 – Security Logging and Monitoring Failures (日誌與監控失效)

### 說明
缺乏足夠的日誌記錄和監控，無法偵測和回應攻擊。

### 應記錄的事件
- 登入成功與失敗
- 權限變更
- 敏感資料存取
- 重要交易

### 防護措施
- 確保日誌包含足夠的上下文資訊
- 建立有效的監控和告警機制
- 制定事件回應計劃
- 保護日誌不被竄改

### 日誌最佳實務

```php
// ❌ 錯誤：記錄敏感資訊
Log::info('Login attempt', ['password' => $password]);

// ✅ 正確：只記錄必要資訊
Log::info('Login attempt', [
    'user_id' => $user->id,
    'ip' => $request->ip(),
    'result' => 'success',
]);
```

---

## A10:2021 – Server-Side Request Forgery (SSRF)

### 說明
應用程式從伺服器端發起請求到攻擊者控制的位置。

### 常見問題
- 允許使用者指定任意 URL
- 可存取內部網路或元資料端點

### 防護措施
- 驗證並清理所有使用者輸入的 URL
- 使用白名單限制允許的域名/協議
- 禁止存取內部網路 IP 範圍
- 限制 HTTP 重新導向

### 危險 IP 範圍

```
禁止存取:
- 127.0.0.0/8 (localhost)
- 10.0.0.0/8 (內部網路)
- 172.16.0.0/12 (內部網路)
- 192.168.0.0/16 (內部網路)
- 169.254.169.254 (雲端元資料)
```

---

## 快速參照表

| 排名 | 風險 | 關鍵防護 |
|-----|------|---------|
| A01 | 存取控制失效 | 預設拒絕、資源所有權驗證 |
| A02 | 加密失效 | TLS、bcrypt、金鑰管理 |
| A03 | 注入 | 參數化查詢、輸入驗證 |
| A04 | 不安全設計 | 威脅建模、安全 SDLC |
| A05 | 配置錯誤 | 自動化部署、安全標頭 |
| A06 | 易受攻擊元件 | 定期掃描、及時更新 |
| A07 | 身份驗證失效 | MFA、密碼政策、Session 管理 |
| A08 | 完整性失效 | 數位簽章、CI/CD 安全 |
| A09 | 日誌監控失效 | 集中日誌、告警機制 |
| A10 | SSRF | URL 白名單、阻擋內部 IP |
