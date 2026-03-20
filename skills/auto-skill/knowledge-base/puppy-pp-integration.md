# Puppy × PP (Pragmatic Play) 串接知識

## 🔧 PP 無縫錢包架構
**日期：** 2026-03-16
**情境：** NestJS 代理層串接 PP 遊戲供應商的無縫錢包 (Seamless Wallet) 整合

**架構概覽：**
- 星城 → Puppy (HTTP/JWT)：取得遊戲連結、報表、踢人
- PP → Puppy (HTTP/Hash)：authenticate、balance、bet、result、refund、bonusWin、jackpotWin、promoWin、adjustment
- Puppy → 總部錢包 (TCP Stars Protocol)：api_login、api_spin、api_show

**關鍵交易欄位：**
- `roundId`：一局遊戲的 ID，同局共用
- `reference`：單筆資金異動 ID，**冪等性以此為基準**
- `tid` (Stars)：送給錢包的交易 ID，退款需加 `refund_` 前綴避免衝突（否則 err:6）

---

## 🔧 PP 回應欄位規範（必填）
**日期：** 2026-03-16
**情境：** PP Wallet API 所有端點回應都必須包含特定欄位，否則 PP 可能觸發 Reconciliation

**各端點必填欄位：**
- `authenticate`：currency, cash, bonus(0.00), error(0), description("Success")
- `balance`：currency, cash, bonus(0.00), error(0), description("Success")
- `bet`：transactionId, currency, cash, bonus(0.00), usedPromo(0.00), error(0), description("Success")
- `result`：transactionId, currency, cash, bonus(0.00), error(0), description("Success")
- `refund`：transactionId, error(0), description("Success") — **不含** cash/bonus/currency
- `bonusWin/jackpotWin/promoWin/adjustment`：transactionId, currency, cash, bonus(0.00), error(0), description("Success")

**注意：**
- cash/bonus 必須是 Float（不加引號），transactionId 建議 String（加引號）
- refund 回應刻意不含餘額資訊，與其他端點格式不同

---

## 🔧 PP 錯誤碼對照（Puppy 回傳給 PP）
**日期：** 2026-03-16
**情境：** 星城 err code 需映射至 PP error code，選錯錯誤碼會影響 PP 是否重試

**關鍵映射：**
- 星城 err:0 → PP 0（成功）
- 星城 err:1（查無 XinKey）→ PP 4（INVALID_TOKEN）→ 觸發 Reconciliation
- 星城 err:3（查無 User）→ PP 2（PLAYER_NOT_FOUND）→ 觸發 Reconciliation
- 星城 err:5（金幣不足）→ PP 1（INSUFFICIENT_BALANCE）→ Bet 不重試，Result 會重試
- 星城 err:6（TID 重覆）→ PP 0（視為成功，冪等）
- 星城 err:7（離線不得押注）→ PP 3（BET_NOT_ALLOWED）
- 星城 err:16（離線且TID重複）→ PP 0（視為成功）
- 逾時/502 → PP 100（INTERNAL_ERROR_RETRY，觸發 Reconciliation）
- 永久失敗 → PP 120（INTERNAL_ERROR_NO_RETRY，PP 不重試）

**重要差異：** 100 有重試，120 無重試；通常逾時用 100，永久失敗才用 120

---

## 🔧 PP Reconciliation 重試機制
**日期：** 2026-03-16
**情境：** PP 交易失敗後的自動重試行為，影響系統設計

**重試策略：**
- `bet` 失敗：每 5 秒 × 2 次，仍失敗 → **PP 自動觸發 refund**
- `result/bonusWin/jackpotWin/adjustment` 失敗：每 5 秒 × 2 次，後進交易隊列最長 24 小時
- `refund` 失敗：直接進交易隊列
- `promoWin` 失敗：進交易隊列（通常錦標賽結束後 30 分鐘才發送）

**冪等性設計：** Redis TTL 190 分鐘 < PP 24 小時重試窗口 → 必須實作 Redis + DB 雙重保護

---

## 🔧 refund 實作陷阱
**日期：** 2026-03-16
**情境：** refund 的 executeSpin 參數容易寫錯

**正確邏輯：** refund 是純加款，`bet=0, payoff=refundAmount`
- 錯誤寫法：`executeSpin(xinkey, ref, amount, amount, roundId)` → net=0，沒有真正退款
- 正確寫法：`executeSpin(xinkey, ref, 0, amount, roundId)`

**冪等性：** refund 的 `reference` 是**原始 bet 的 reference**，不是退款本身的 ID

**tid 前綴：** 退款 tid 必須加 `refund_` 前綴（如 `refund_${betRef}`），避免與原始 bet 的 tid 衝突導致 err:6

---

## 🔧 已知缺陷清單（PP 串接）
**日期：** 2026-03-16
**情境：** 目前 PP 串接仍有大量缺陷待修復，修復前應優先了解現況

**P0 優先（功能完全失效）：**
- 端點尾端 `/game/url` 應改為 `/game/link`（星城根本呼叫不到）
- DTO 欄位名不符：`gameId` 應改 `game_code`，`language` 應改 `lang`
- 回應欄位：`gameURL` 應包裝為 `url`
- refund executeSpin 參數錯誤（net=0 無退款效果）
- 退款失敗仍快取且回傳成功（玩家永遠無法取回退款）
- 所有成功回應缺少 `currency`、`description`

**P1 優先（功能有缺陷）：**
- `mobile` 欄位未實作（手機板永遠進桌面版）
- 冪等性無 DB 層保護（TTL 過期後重複交易）
- `bet_record_sub.status` 永遠 PENDING
- 三個星城端點未實作：`demo/link`、`report/list`、`user/kick`
