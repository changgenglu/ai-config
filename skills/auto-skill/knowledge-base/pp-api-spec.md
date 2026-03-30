---
name: PP API Spec
description: Pragmatic Play 無縫錢包整合 API 規格：所有端點參數、Hash 計算、錯誤碼、Data Feed，供 Puppy 專案快速查詢（排除 XVI 真人娛樂場與 XVIII 賓果）
type: reference
---

## PP (Pragmatic Play) 無縫錢包 API 規格

**來源**：Integration API Specification ZH.pdf（207 頁）
**更新日期**：2026-03-25
**排除**：XVI 真人娛樂場遊戲接入、XVIII 賓果集成 API

---

## 目錄

- [III 無縫錢包 API](#section-iii)
  - [3.1 StartGame（我方呼叫 PP）](#31-startgame)
  - [3.2 Hash 計算](#32-hash-計算)
  - [3.3 資料型別](#33-資料型別)
  - [3.4 Authenticate](#34-authenticate)
  - [3.5 Balance](#35-balance)
  - [3.6 Bet](#36-bet)
  - [3.7 Result](#37-result)
  - [3.8 BonusWin](#38-bonuswin)
  - [3.9 JackpotWin](#39-jackpotwin)
  - [3.10 EndRound](#310-endround)
  - [3.11 Refund](#311-refund)
  - [3.12 GetBalancePerGame](#312-getbalancepergame)
  - [3.13 PromoWin](#313-promowin)
  - [3.14 SessionExpired](#314-sessionexpired)
  - [3.15 Adjustment](#315-adjustment)
  - [3.16 RoundDetails 端點](#316-rounddetails-端點)
  - [3.17 錯誤碼](#317-錯誤碼)
- [VIII Data Feed API](#section-viii)

---

## Section III：無縫錢包 API {#section-iii}

所有端點為 `POST`，`Content-Type: application/x-www-form-urlencoded`。
PP 呼叫我方端點：`https://{operator-domain}/{endpoint}.html`

### 3.1 StartGame（我方呼叫 PP）{#31-startgame}

**我方呼叫 PP 取得遊戲連結**

```
POST https://{PLATFORM_PP_API_DOMAIN}/game/url/
```

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| secureLogin | API 身份驗證用戶名 | 必需 |
| externalPlayerId | 運營商系統玩家 ID | 必需 |
| symbol | PP 遊戲 ID（如 vs50aladdin）⚠️ HTTP 參數名為 `symbol`，非 `gameId` | 必需 |
| language | ISO 639-1 語言代碼 | 必需 |
| country | ISO 3166-1 alpha-2 國家代碼 | 必需 |
| hash | Hash 代碼 | 必需 |
| platform | MOBILE / WEB | 可選 |
| cashierUrl | 收銀台 URL | 可選 |
| lobbyUrl | 大廳 URL | 可選 |
| playMode | REAL / DEMO | 可選 |
| token | 玩家令牌 | 可選 |

**響應參數**

| 名稱 | 說明 |
|------|------|
| gameURL | 遊戲啟動連結 |
| error | 錯誤碼 |
| description | 錯誤說明 |

**注意**：遊戲連結格式為：
`https://{game_server_domain}/gs2c/openGame.do?tc={ticket}&stylename={secureLogin}`

---

### 3.2 Hash 計算 {#32-hash-計算}

**無縫錢包 API（III）Hash 計算方式**：

1. 從請求 POST 參數取得所有參數（排除 hash 本身）
2. 按字母表順序排序所有參數
3. 以 `key1=value1&key2=value2` 格式串接
4. 附加密鑰：`key1=value1&key2=value2{SECRET_KEY}`
5. 使用 **MD5** 計算哈希
6. 與 hash 參數比對，失敗應返回錯誤碼 5

**注意**：Hash 驗證失敗應返回 error code **5**。

---

### 3.3 資料型別 {#33-資料型別}

| 名稱 | 類型 | 說明 |
|------|------|------|
| userId | String(100) | 運營商系統玩家唯一 ID。**區分大小寫**。playerABC 和 playerAbc 是兩個不同帳戶 |
| currency | String(3) | ISO 4217 貨幣代碼，如 USD、EUR、TWD |
| roundId | **Long**（以字串傳輸） | 遊戲回合 ID。PDF 原廠定義為 Long 型別 |
| gameId | **String(32)** | PP 遊戲唯一符號 ID，如 vs50aladdin、cs5triple8gold |
| transactionId | **Varchar(32)** | PP 系統內交易唯一 ID（我方不生成，PP 生成後由我方記錄） |
| reference | **String(32)** | 此交易在 PP 系統內的唯一參考號（PP 生成，每次交易不同） |
| amount | Decimal(10,2) | 最小 0.00 |
| cash | Decimal(10,2) | 玩家真錢餘額 |
| bonus | Decimal(10,2) | 玩家獎勵餘額 |
| usedPromo | Decimal(10,2) | 從獎勵餘額使用的金額 |
| error | Integer | 0=成功 |
| description | String(100) | 錯誤說明 |
| roundDetails | String(4000) | **逗號分隔**的交易附加信息（見下方詳細說明） |
| campaignId | Long | 活動 ID |
| campaignType | String(3) | 活動類型 |
| sessionId | String(100) | PP 遊戲會話 ID |

#### roundDetails 格式詳解

格式：逗號分隔的鍵值對，例如：
`spin,totalBet:200.0,freeSpinCount:18,totalWin:284.0,baseWin:4.0,freeSpinWin:280.0`

**Bet 端點中的 roundDetails 值**（描述下注類型）：

| 值 | 說明 |
|----|------|
| `spin` | 默認下注 |
| `spin,bonusBuy` | 用購買的紅利下注 |
| `spin,anteBet` | 前注 |
| `spin,superSpin` | 超級旋轉 |
| `type:main,desc:Tie` | 真人娛樂場示例 1 |

**Result 端點中的 roundDetails 鍵值欄位**（描述回合結果）：

| 鍵 | 說明 | 備註 |
|----|------|------|
| `totalBet` | 回合總投注金額 | **只對免費旋轉回合**，核帳關鍵 |
| `totalWin` | 回合總贏額 | **只對免費旋轉回合**，核帳關鍵 |
| `baseWin` | 基礎旋轉獲勝金額 | 可選，需 PP 配置才發送 |
| `freeSpinCount` | 免費旋轉總數 | 可選，需 PP 配置才發送。計算：freeSpinCount = freeSpinCount + reSpinCount + 1（若玩過獎勵遊戲）|
| `freeSpinWin` | 免費旋轉總贏 | 可選，需 PP 配置才發送。計算：freeSpinWin = freeSpinWin + reSpinWin + gamblingWin + bonusGamesWins |

---

### 3.4 Authenticate {#34-authenticate}

**PP → 我方**，玩家打開遊戲時 PP 驗證玩家身份並獲取餘額。

```
POST /authenticate.html
```

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| token | 玩家令牌（我方生成並傳給 PP 的一次性令牌） | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| gameId | 遊戲標識符 | 可選 |
| ipAddress | 玩家 IP 地址 | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| userId | 我方系統用戶 ID | 必需 |
| currency | 玩家貨幣 | 必需 |
| cash | 玩家真錢餘額 | 必需 |
| bonus | 玩家獎勵餘額 | 必需 |
| token | 玩家令牌/會話（可配置在後續所有 API 調用中返回） | 可選 |
| betLimits | 下注限制對象 | 可選 |
| extraInfo | 附加參數（如 promoAvailable） | 可選 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

**betLimits 結構**：
```json
{
  "defaultBet": 0.10,
  "minBet": 0.02,
  "maxBet": 10.00,
  "minTotalBet": 0.50,
  "maxTotalBet": 250.00
}
```

**HTTP 請求示例**：
```
POST /authenticate.html HTTP/1.1
providerId=pragmaticplay&hash=e1467eb30743fb0a180ed141a26c58f7&token=5v93mto7jr
```

**JSON 響應示例**：
```json
{
  "userId": "421",
  "currency": "USD",
  "cash": 99999.99,
  "bonus": 99.99,
  "error": 0,
  "description": "Success"
}
```

---

### 3.5 Balance {#35-balance}

**PP → 我方**，取得玩家當前餘額。

```
POST /balance.html
```

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| token | 玩家令牌 | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| currency | 玩家貨幣 | 必需 |
| cash | 玩家真錢餘額 | 必需 |
| bonus | 玩家獎勵餘額 | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.6 Bet {#36-bet}

**PP → 我方**，下注扣款。

```
POST /bet.html
```

**重要**：冪等操作，以相同的 reference 再次發送只會創建一個交易。對於重試，應返回實際玩家的餘額。

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| gameId | 遊戲 ID | 必需 |
| roundId | 回合 ID | 必需 |
| amount | 賭注金額，最小 0.00 | 必需 |
| reference | 此交易的唯一參考 | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| timestamp | Unix 毫秒時間戳 | 必需 |
| roundDetails | 當前遊戲回合附加信息（逗號分隔） | 必需 |
| bonusCode | 獎勵 ID（FRB 時必需） | 可選 |
| platform | MOBILE / WEB | 可選 |
| language | 語言代碼 | 可選 |
| jackpotContribution | 累積獎金貢獻金額 | 可選 |
| jackpotId | 累積獎金 ID | 可選 |
| jackpotDetails | 多級累積獎金貢獻詳情 | 可選 |
| token | 玩家令牌 | 可選 |
| ipAddress | 玩家 IP 地址 | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| transactionId | 錢包中的交易 ID（我方生成） | 必需 |
| currency | 玩家貨幣 | 必需 |
| cash | 玩家真錢餘額 | 必需 |
| bonus | 玩家獎勵餘額 | 必需 |
| usedPromo | 從獎勵餘額使用的金額 | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.7 Result {#37-result}

**PP → 我方**，派彩加款。

```
POST /result.html
```

**重要**：冪等操作，以相同的 reference 再次發送結果只會創建一個交易。

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| gameId | 遊戲 ID | 必需 |
| roundId | 回合 ID | 必需 |
| amount | 贏獎金額 | 必需 |
| reference | 此交易的唯一參考 | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| timestamp | Unix 毫秒時間戳 | 必需 |
| roundDetails | 當前遊戲回合附加信息（逗號分隔） | 必需 |
| bonusCode | 獎勵 ID | 可選 |
| platform | MOBILE / WEB | 可選 |
| token | 玩家令牌 | 可選 |
| promoWinAmount | 玩家在推廣活動期間獲得的獎勵金額（天降獎勵必需加到 cash） | 可選 |
| promoWinReference | 此交易的唯一參考 | 可選 |
| promoCampaignID | 推廣活動 ID | 可選 |
| promoCampaignType | 推廣活動類型（R=天降現金推廣） | 可選 |
| specPrizes[#].specPrizeAmount | 宾果遊戲獲得的 FRB 數量 | 可選（宾果） |
| specPrizes[#].specPrizeCode | PP 系統唯一 FRB 獎金代碼 | 可選（宾果） |
| specPrizes[#].specPrizeType | 免費特類獎品類型（FRB） | 可選（宾果） |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| transactionId | 錢包中的交易 ID | 必需 |
| currency | 玩家貨幣 | 必需 |
| cash | 玩家真錢餘額 | 必需 |
| bonus | 玩家獎勵餘額 | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.8 BonusWin {#38-bonuswin}

**PP → 我方**，免費旋轉所有回合完成後發送獎勵。

```
POST /bonusWin.html
```

**重要**：
- 冪等操作
- **異步發送**，與遊戲回合的結束無關
- 最小金額 0.00（零金額會被視為輸）

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| amount | 贏獎金額（最小 0.00） | 必需 |
| reference | 此交易的唯一參考 | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| timestamp | Unix 毫秒時間戳 | 必需 |
| bonusCode | 獎勵 ID（FRB 時必需） | 必需 |
| roundId | 免費旋轉獎勵最後一局的遊戲交易 ID | 可選 |
| gameId | 免費旋轉獎勵最後一局的遊戲 ID | 可選 |
| token | 玩家令牌 | 可選 |
| requestId | FRB 信用請求的唯一標識符 | 可選 |
| remainAmount | 剩餘 FRB 數量 | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| transactionId | 錢包中的交易 ID | 必需 |
| currency | 玩家貨幣 | 必需 |
| cash | 玩家真錢餘額 | 必需 |
| bonus | 玩家獎勵餘額 | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.9 JackpotWin {#39-jackpotwin}

**PP → 我方**，通知運營商玩家中累積獎金。

```
POST /jackpotWin.html
```

**重要**：冪等操作。在老虎機累積獎金中，累積獎金和非累積獎金會在 amount 字段裡一起發送，PP 只向運營商支付累積獎金部分。

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| timestamp | Unix 毫秒時間戳 | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| gameId | 遊戲 ID | 必需 |
| roundId | 遊戲回合 ID | 必需 |
| jackpotId | 累積獎金 ID | 必需 |
| amount | 累積獎金贏獎金額 | 必需 |
| reference | PP 系統內唯一參考 | 必需 |
| platform | MOBILE / WEB | 可選 |
| token | 玩家令牌 | 可選 |
| jackpotDetails | 多級累積獎金詳情（progressive:XX,non-progressive:YY） | 可選 |
| balanceBeforeWin | 獲勝前餘額 | 可選 |
| balanceAfterWin | 獲勝後餘額 | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| transactionId | 錢包中的交易 ID | 必需 |
| currency | 玩家貨幣 | 必需 |
| cash | 玩家真錢餘額 | 必需 |
| bonus | 玩家獎勵餘額 | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.10 EndRound {#310-endround}

**PP → 我方**，每次遊戲回合結束時調用，**不改變餘額**，僅標記回合結束。

```
POST /endRound.html
```

**重要**：
- EndRound 請求可以**多次發送**
- 如果遊戲回合已經被結束，應忽略 EndRound 請求並**返回成功響應**
- 如不需要實時結束交易，強烈建議在 PP 側禁用 EndRound 功能，改用 Data Feed API

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| gameId | 遊戲 ID | 必需 |
| roundId | 回合 ID | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| bonusCode | 獎勵 ID | 可選 |
| platform | MOBILE / WEB | 可選 |
| token | 玩家令牌 | 可選 |
| roundDetails | 當前遊戲回合附加信息（逗號分隔，同 Result 格式） | 可選 |
| win | 回合金額，通知運營商回合的獎金金額（通知參數，不應用於回合的交易） | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| cash | 玩家真錢餘額 | 必需 |
| bonus | 玩家獎勵餘額 | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.11 Refund {#311-refund}

**PP → 我方**，退款（退回之前的下注金額）。

```
POST /refund.html
```

**重要**：
- 冪等操作
- **如果下注單號未找到，不應有任何操作，應返回成功(0)或特定錯誤代碼**
- reference 是**退款的原始下注 reference**

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| reference | 賭注交易的參考（原始 bet reference） | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| platform | MOBILE / WEB | 可選 |
| amount | 退款金額 | 可選 |
| gameId | 遊戲 ID | 可選 |
| roundId | 回合 ID | 可選 |
| timestamp | Unix 毫秒時間戳 | 可選 |
| roundDetails | 當前遊戲回合附加信息 | 可選 |
| bonusCode | 獎勵 ID（FRB 時必需） | 可選 |
| token | 玩家令牌 | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| transactionId | 運營商系統中的退款交易 ID | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

**注意**：Refund 響應**不含** currency/cash/bonus 欄位（與其他端點不同）。

---

### 3.12 GetBalancePerGame {#312-getbalancepergame}

**PP → 我方**，取得特定遊戲的可用餘額（適用於根據遊戲類型使用不同金額的運營商）。

```
POST /GetBalancePerGame.html
```

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| gameIdList | 以逗號分隔的遊戲 ID 列表 | 必需 |
| token | 玩家令牌 | 可選 |
| platform | MOBILE / WEB | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| gamesBalances | 每個遊戲的餘額列表 [{gameID, cash, bonus}] | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.13 PromoWin {#313-promowin}

**PP → 我方**，通知運營商活動結束時玩家最終獲得的獎勵。

```
POST /promoWin.html
```

**重要**：
- 冪等操作
- **異步發送**，活動結束後可能延遲才收到
- 此端點也用於通知有關 FRB 作為獎品掉落或錦標賽的獎金
- 也用於通知社區大獎中獎情況

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| timestamp | Unix 毫秒時間戳 | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| campaignId | 活動 ID | 必需 |
| campaignType | 活動類型：**T**=錦標賽, **CJP**=社區大獎, **CB**=現金返還（如多值用逗號分隔） | 必需 |
| amount | 玩家獲得的獎勵金額（**必須加到玩家 cash 餘額中**） | 必需 |
| currency | 玩家貨幣 | 必需 |
| reference | PP 系統內唯一參考 | 必需 |
| roundId | 本輪 ID（錦標賽成就的最後一輪導致改變分數） | 可選 |
| gameId | 遊戲唯一標識符 | 可選 |
| dataType | 促銷活動的投資組合類型 | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| transactionId | 錢包中的交易 ID | 必需 |
| currency | 玩家貨幣 | 必需 |
| cash | 玩家真錢餘額 | 必需 |
| bonus | 玩家獎勵餘額 | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.14 SessionExpired {#314-sessionexpired}

**PP → 我方**，通知玩家會話過期（玩家長時間非活動或遊戲關閉）。

```
POST /session/expired
```

**重要**：
- **可選端點**，不默認發送
- 需要 PP 技術支援進行額外配置才會啟用

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必選 |
| providerId | 遊戲供應商 ID | 必選 |
| sessionId | PP 遊戲會話 ID | 必選 |
| playerId | 運營商系統玩家 ID | 必選 |
| token | 玩家令牌 | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.15 Adjustment {#315-adjustment}

**PP → 我方**，調整玩家餘額（增加或減少）。

```
POST /adjustment.html
```

**重要**：
- 冪等操作
- **只在真人遊戲使用**
- 可以離線發送（不要求玩家一定要在線）
- 如果金額為負數且玩家餘額不足，應返回 error code **1** 和描述 `"Insufficient balance"`

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必選 |
| userId | 運營商系統用戶 ID | 必選 |
| gameId | 遊戲 ID | 必選 |
| roundId | 回合 ID | 必選 |
| amount | 調整金額（可正可負） | 必選 |
| reference | PP 系統內唯一參考 | 必選 |
| providerId | 遊戲供應商 ID | 必選 |
| validBetAmount | 有效投注金額 | 必選 |
| timestamp | Unix 毫秒時間戳 | 必選 |
| token | 玩家令牌 | 可選 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| transactionId | 錢包中的交易 ID | 必需 |
| currency | 玩家貨幣 | 必需 |
| cash | 玩家真錢餘額 | 必需 |
| bonus | 玩家獎勵餘額 | 必需 |
| error | 響應狀態 | 必選 |
| description | 響應狀態簡短說明 | 必選 |

---

### 3.16 RoundDetails 端點 {#316-rounddetails-端點}

**PP → 我方**，EndRound 調用之後發送，包含老虎機或 RNG 桌面遊戲詳細結果。

```
POST /roundDetails.html
```

**重要**：
- **必須在 EndRound 之後才發送**，因此必須啟用和支持它
- 與請求中的 `roundDetails` 欄位不同，這是一個**獨立的 API 端點**
- smResult 包含 URL-encoded 的遊戲詳細結果（可轉發給監管者）

**請求參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| hash | Hash 代碼 | 必需 |
| userId | 運營商系統用戶 ID | 必需 |
| roundId | 回合 ID | 必需 |
| providerId | 遊戲供應商 ID | 必需 |
| smResult | URL-encoded 的老虎機或 RNG 桌面遊戲結果詳細信息 | 必需 |
| gameCategory | 遊戲類別 | 必需 |
| betMultiplier | 投注乘數 | 必需 |

**響應參數**

| 名稱 | 說明 | 狀態 |
|------|------|------|
| error | 響應狀態 | 必需 |
| description | 響應狀態簡短描述 | 必需 |

---

### 3.17 錯誤碼 {#317-錯誤碼}

運營商響應無縫錢包 API 調用時返回的錯誤碼：

| 代碼 | 描述 | Bet 送 Reconciliation | Result/Refund 送 Reconciliation |
|------|------|-----------------------|----------------------------------|
| 0 | Success 成功 | 否 | 否 |
| 1 | Insufficient balance 餘額不足（Bet 端點響應返回） | 否 | 是（重試） |
| 2 | Player not found or logged out 未找到玩家或已登出 | 是 | 是 |
| 3 | Bet is not allowed 不允許下注（如特殊獎勵限制） | 否 | 是 |
| 4 | Player authentication failed 玩家身份驗證失敗（令牌無效/未找到/過期） | 是 | 是 |
| 5 | Invalid hash code 無效 Hash 代碼 | 是 | 是 |
| 6 | Player is frozen 玩家帳戶被禁止或凍結 | 是 | 是 |
| 7 | Bad parameters 請求參數錯誤，請檢查 POST 參數 | 是 | 是 |
| 8 | Game not found or disabled 遊戲未找到或已禁用（即使禁用，包含贏取金額的投注結果請求也應按預期處理） | 是 | 是 |
| 50 | Bet limit has been reached 已達到投注限額（受監管市場相關） | 否 | 是 |
| 100 | Internal server error, retry required 內部服務器錯誤，運營商邏輯**需要重試** | 是 | 是 |
| 120 | Internal server error, retry NOT required 內部服務器錯誤，運營商邏輯**不需要重試** | 否 | 否 |
| 130 | EndRound processing error 處理 EndRound 時內部錯誤，需要重試（**僅用於 EndRound，不適用於其他方法**） | - | - |
| 210 | Reality check warning 現實檢查警告 | 是 | 是 |
| 310 | Player's bet is out of bet limits 玩家賭注超出限額（與 Authenticate 響應中的 betLimits 相關） | 否 | 否 |

---

## Section VIII：Data Feed API {#section-viii}

Data Feed API 用於**核帳（Reconciliation）**，採 CSV 格式，時間點（timepoint）輪詢機制。

**基本機制**：
- 每次請求傳入 `timepoint`（Unix 毫秒時間戳），返回從該時間點開始的最新數據
- 響應第一行為新的 `timepoint` 值，供下次請求使用
- 若 timepoint 為空，返回最新的 timepoint

### 8.2 Game Rounds（遊戲回合）

**用途**：記錄每個遊戲回合的下注和結果。

**CSV 標頭（共 16 欄，依序）**：

```
playerID, extPlayerID, gameID, playSessionID, parentSessionID, startDate, endDate, status, type, bet, win, currency, jackpot, bonusCode, bonusBet, bonusWin
```

| 欄位 | 說明 |
|------|------|
| playerID | 運營商系統玩家 ID |
| extPlayerID | PP 系統玩家 ID |
| gameID | 遊戲 ID（對應 symbol） |
| playSessionID | 本局遊戲 Session ID（對應 roundId） |
| parentSessionID | 父 Session ID（Free Round 使用） |
| startDate | 回合開始時間 |
| endDate | 回合結束時間 |
| status | 回合狀態 |
| type | 回合類型 |
| bet | 投注金額 |
| win | 贏獎金額 |
| currency | 貨幣 |
| jackpot | 累積獎金金額 |
| bonusCode | 獎勵代碼 |
| bonusBet | 獎勵投注金額 |
| bonusWin | 獎勵贏獎金額 |

> ⚠️ **重要**：欄位名稱與無縫錢包 API（§ III）不同。`playSessionID` ≠ `roundId`（字串），核帳時需注意映射關係。

### 8.3 Transactions（交易記錄）

**用途**：記錄所有交易（下注、派彩、退款等）。

**CSV 標頭（基礎 9 欄，依序）**：

```
playerID, extPlayerID, gameID, playSessionID, timestamp, referenceID, type, amount, currency
```

| 欄位 | 說明 | 狀態 |
|------|------|------|
| playerID | 運營商系統玩家 ID | 必需 |
| extPlayerID | PP 系統玩家 ID | 必需 |
| gameID | 遊戲 ID | 必需 |
| playSessionID | 本局遊戲 Session ID（對應 roundId） | 必需 |
| timestamp | 交易處理時間 (Unix ms) | 必需 |
| referenceID | PP 系統交易參考號（對應 reference） | 必需 |
| type | 交易類型碼（見下方列表） | 必需 |
| amount | 交易金額 | 必需 |
| currency | 貨幣 | 必需 |
| contributionAmount | 累積獎金貢獻金額（需 options 包含 addJPContributionAmount） | 可選 |
| status | 交易狀態（S=成功, L=取消, R=退款，需 options 包含 addTransactionStatus） | 可選 |

**交易類型碼**：

| 代碼 | 說明 |
|------|------|
| B | 下注（Bet） |
| W | 獲勝（Win） |
| V | 部分獲勝 |
| L | 取消賭注 |
| R | 退款（Refund） |
| J | 累積獎金（Jackpot） |
| P | 促銷活動獲勝（PromoWin） |

### 8.4 Failed Transactions（失敗的交易）

**用途**：記錄因運營商系統錯誤（error code 100）導致的失敗交易，供後續 Reconciliation。

CSV 欄位示例：`roundId,gameId,playerId,transactionType,reference,betId,amount,currency`
示例行：`...,"spin,bonus",...` （roundDetails 為逗號分隔字符串，整體包在引號內）

### 8.5-8.7 Jackpots（累積獎金）

記錄累積獎金中獎信息。

### 8.8 Daily Totals（每日統計）

記錄每日匯總統計數據（投注總額、贏獎總額等）。

---

## 附錄：端點對照表

| 端點路徑 | 方向 | 說明 |
|----------|------|------|
| `POST /game/url/`（PP 域名） | 我→PP | 取得遊戲啟動 URL |
| `POST /authenticate.html` | PP→我 | 身份驗證並取得餘額 |
| `POST /balance.html` | PP→我 | 取得玩家餘額 |
| `POST /bet.html` | PP→我 | 下注扣款 |
| `POST /result.html` | PP→我 | 派彩加款 |
| `POST /bonusWin.html` | PP→我 | 免費旋轉總獎勵 |
| `POST /jackpotWin.html` | PP→我 | 累積獎金中獎 |
| `POST /endRound.html` | PP→我 | 回合結束通知（不改變餘額） |
| `POST /refund.html` | PP→我 | 退款 |
| `POST /GetBalancePerGame.html` | PP→我 | 取得特定遊戲餘額 |
| `POST /promoWin.html` | PP→我 | 促銷活動獎勵 |
| `POST /session/expired` | PP→我 | 會話過期通知（需配置） |
| `POST /adjustment.html` | PP→我 | 餘額調整（真人遊戲） |
| `POST /roundDetails.html` | PP→我 | 遊戲詳細結果（EndRound 後） |

---

## 附錄：Puppy 實作注意事項

### 回應格式規範（我方回應 PP）

成功回應必須包含：
- `transactionId`（字符串）- 我方生成的交易 ID
- `currency`（字符串）- 玩家貨幣
- `cash`（數字）- 真錢餘額
- `bonus`（數字）- 獎勵餘額
- `error: 0`
- `description: "Success"`

**例外**：Refund 響應不含 currency/cash/bonus；EndRound 響應不含 transactionId。

### 冪等性設計

所有含 `reference` 的端點均為冪等：
- 相同 `reference` 第二次請求應返回原始交易結果（不重複執行），並返回當前餘額
- 建議用 Redis（190 分鐘 TTL）+ DB 雙重保護
- 190 分鐘 < PP 的 24 小時重試窗口，因此需要 DB 作為最終保障

### Refund 特殊處理

- Refund 的 reference 是原始 Bet 的 reference
- 呼叫 Stars Protocol executeSpin 時：`bet=0, payoff=退款金額`（退錢給玩家）
- 建議在 Stars transactionId 加 `refund_` 前綴，避免 Stars err:6（TID 重複）

### IP 白名單

PP 的 IP 設定在環境變數 `PLATFORM_PP_ALLOWED_IPS`（逗號分隔）
