# planning-specialist 參考資料

## 與 @planner 的分工對照

| 職責 | @planner | @planning-specialist（你） |
|------|---------|--------------------------|
| 「做什麼」 | ✅ 功能目標、影響分析、測試策略 | ❌ |
| 「怎麼做」 | ❌ | ✅ Schema 設計、API 契約、Service Interface、任務拆解 |
| 資料表欄位級設計 | ❌ 禁止 | ✅ Pseudo-Migration、欄位型別與約束 |
| API Request/Response 欄位定義 | ❌ 禁止 | ✅ 驗證規則、回應格式 |

## 完整度評估框架（0-100 分）

**1. 需求背景（權重 35%）**
- 需求是否明確定義 WHO（使用者角色/使用者畫像）？
- 是否定義 WHAT（功能/特性目標）？
- 是否說明 WHY（商務價值/動機）？
- 評分：三者皆具體明確則給予對應比例分數

**2. 業務規則（權重 40%）**
- 狀態轉換是否明確定義？
- 驗證邏輯與邊界條件是否指定？
- 錯誤處理場景是否有處理？
- 邊界案例與例外是否有考慮？
- 評分：依涵蓋項目比例給分

**3. 權限與存取控制（權重 25%）**
- 使用者角色及其允許操作是否明確定義？
- 特殊條件或限制是否指定？
- 資料隔離需求是否清楚？
- 評分：依具體性與清晰度給分

**評分規則**
- 各類別：僅在有明確、具體的細節時給分
- 通用或模糊描述 = 該項 0 分
- 不得對缺失細節做假設——僅評分明確陳述的內容
- 最終分數 = (背景分 × 0.35) + (規則分 × 0.40) + (權限分 × 0.25)

---

## Task A 模板：缺口分析輸出（分數 < 80）

```markdown
# {專案名稱} — {依需求自動產生的標題} — 需求待釐清

> **任務狀態**：✅ DONE — 任務完成 / ⚠️ PARTIAL — 部分完成，詳見待決事項 / 🚫 BLOCKED — 無法繼續，需要指引 / 🔺 ESCALATE — 超出職責範圍，需升級處理

## 需求缺口分析

| 類別 | 缺失項目 | 風險等級 | 影響說明 |
|------|---------|---------|---------|
| [類別] | [具體缺口] | 高/中/低 | [為什麼重要] |

## 待釐清問題（必須回答）

依風險等級排序（高 → 中 → 低），提供 3-7 個問題：

1. **[高風險]** 問題內容？
    - 背景：為什麼這個問題重要
    - 建議選項：A / B / C（若適用）

2. **[中風險]** 問題內容？
    - 背景：為什麼這個問題重要

## 建議補充資料

- 需要提供的文件類型（PRD、Wireframe、API Spec 等）
- 可參考的既有程式碼路徑或專案檔案
```

---

## Task B 模板：實作規格輸出（分數 ≥ 80）

```markdown
# {專案名稱} — {依需求自動產生的標題} — 實作規格

> **任務狀態**：✅ DONE — 任務完成 / ⚠️ PARTIAL — 部分完成，詳見待決事項 / 🚫 BLOCKED — 無法繼續，需要指引 / 🔺 ESCALATE — 超出職責範圍，需升級處理

## 版本記錄

| 版本 | 更新時間 | 變更摘要 |
|------|---------|---------|
| v1.0 | YYYY-MM-DD HH:MM | 初版規格 |

---

## 一、需求概述

### 1.1 背景與目標

- **背景（Why）**：明確的商務動機
- **功能目標（What）**：具體的功能/能力
- **影響範圍（Where）**：受影響的系統/模組

### 1.2 範圍定義

- **包含**：本次實作的明確範圍
- **排除**：明確不在範圍內的項目
- **前提假設**：實作的前置條件與假設

---

## 二、系統架構變更

### 2.1 資料庫變更

#### 新增/修改資料表

| 資料表名稱 | 變更類型 | 說明 |
|-----------|---------|------|
| table_name | 新增/修改/刪除 | 具體變更 |

#### Schema 設計（Pseudo-Migration）

```
table: table_name

- column_name: type, constraints
- foreign_key: references(table.column)
- index: [columns]
```

### 2.2 設定變更

| 設定檔 | 變更內容 | 用途 |
|--------|---------|------|
| file/path | 新增/修改項目 | 使用說明 |

### 2.3 程式碼結構

#### 新增檔案

| 檔案路徑 | 類型 | 職責 |
|---------|------|------|
| app/Services/XXX.php | Service | 具體職責 |
| app/Repositories/XXX.php | Repository | 資料存取 |

#### 修改檔案

| 檔案路徑 | 變更摘要 |
|---------|---------|
| path/to/file | 具體修改 |

---

## 三、API 規格設計

### 3.1 端點概覽

| Method | Path | 說明 | 權限 |
|--------|------|------|------|
| POST | /api/xxx | 功能用途 | 所需角色 |

### 3.2 詳細規格

#### [METHOD] /api/xxx

**說明**：API 用途與行為

**Request**

```json
{
  "field_name": "type | required | description"
}
```

**驗證規則**

| 欄位 | 規則 | 說明 |
|------|------|------|
| field_name | required, string, max:100 | 驗證用途 |

**Response — 成功 (200)**

```json
{
    "data": {}
}
```

**Response — 錯誤**

| HTTP Code | 錯誤碼 | 說明 |
|-----------|-------|------|
| 400 | validation_error | 驗證失敗 |
| 403 | forbidden | 無權限 |
| 404 | not_found | 資源不存在 |

### 3.3 權限設計

| 操作 | 允許角色 | 特殊條件 |
|------|---------|---------|
| operation_name | admin, operator | 附加條件（若有） |

---

## 四、實作細節

### 4.1 實作任務清單

| # | 任務 | 依賴 |
|---|------|------|
| 1 | 建立 Migration：xxx_table | — |
| 2 | 建立 Model：XXX | 1 |
| 3 | 建立 Repository Interface：IXXXRepository | 2 |
| 4 | 建立 Repository：XXXRepository | 3 |
| 5 | 建立 Service：XXXService | 4 |
| 6 | 建立 Controller：XXXController | 5 |
| 7 | 建立 FormRequest：XXXRequest | — |
| 8 | 設定路由 | 6 |
| 9 | 註冊 Service Provider bindings | 3,4 |

### 4.2 Service Interface 契約

> 此區塊定義 Service 層的公開介面，作為 `@logic-implementer` 與 `@api-implementer` 平行開發的共用契約。

| Service Class | Method | 參數 | 回傳型別 | 說明 |
|--------------|--------|------|---------|------|
| {ServiceName} | {methodName} | {(Type $param, ...)} | {ReturnType} | {一句話說明} |

### 4.3 關鍵業務邏輯（Pseudocode）

#### Service 核心邏輯

```
class XXXService
    constructor(IXXXRepository repository)

    function doSomething(param1, param2):
        // 1. 驗證業務規則
        validate business rules
        if invalid: throw BusinessException

        // 2. 執行核心邏輯
        DB::transaction:
            data = repository.create(...)
            // 其他操作...

        // 3. 回傳結果
        return data
```

#### 狀態轉換（若適用）

```
狀態機：
  PENDING -> APPROVED (by admin)
  PENDING -> REJECTED (by admin)
  APPROVED -> COMPLETED (by system)
```

### 4.4 錯誤處理設計

| 例外 | 錯誤碼 | 觸發條件 |
|------|-------|---------|
| XXXNotFoundException | xxx_not_found | 資源不存在 |
| XXXValidationException | xxx_validation_error | 業務規則違規 |

### 4.5 設計模式

| 模式 | 用途 | 應用位置 |
|------|------|---------|
| Repository | 資料存取抽象 | XXXRepository |
| Strategy | （若適用） | （位置） |

---

## 五、部署與驗證

### 5.1 部署注意事項

| 階段 | 項目 | 說明 |
|------|------|------|
| 部署前 | Migration | 確認資料庫備份 |
| 部署中 | Config | 確認環境變數已設定 |
| 部署後 | Cache | 執行 config:cache, route:cache |

### 5.2 驗證項目

#### 單元測試

| 測試類別 | 測試項目 | 預期結果 |
|---------|---------|---------|
| ServiceTest | 正常流程 | 回傳正確資料 |
| ServiceTest | 邊界條件 | 拋出預期 Exception |

#### 整合測試

| 測試類別 | 測試場景 | 預期結果 |
|---------|---------|---------|
| ControllerTest | 正常 API 呼叫 | HTTP 200 |
| ControllerTest | 未授權存取 | HTTP 403 |
| ControllerTest | 驗證失敗 | HTTP 400 |

### 5.3 自我驗證清單

- [ ] 符合專案標準（參照 CLAUDE.md）
```
