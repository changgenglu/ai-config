---
description: 功能開發導引（已由新的總監工作流程取代）
argument-hint: 功能需求描述（可選）
---

# 功能開發工作流程

> **注意**：此指令已由新的**任務執行總監工作流程**取代。
> 原 feature-dev plugin 的 7 階段流程已整合並強化至現有代理團隊中。

---

## 啟動新的開發流程

$ARGUMENTS 若有提供功能描述，將作為需求直接傳入 @planner。

請依照以下步驟啟動開發：

### 1. 需求規劃（@planner）

委派 **@planner** 進行：
- 需求確認（對應原 Phase 1 Discovery + Phase 3 Clarifying Questions）
- 多角度平行程式碼探索（對應原 Phase 2 Codebase Exploration）
- 產出版本化規劃報告

> ⏸️ **等待使用者確認規劃報告後才繼續**

### 2. 架構設計（@architect，若需要）

委派 **@architect** 進行：
- 提出 ≥2 個方案比較（對應原 Phase 4 Architecture Design）
- 產出架構設計文件與 ADR

> ⏸️ **等待使用者選擇方案後才繼續**

### 3. 實作（Wave 實作團隊）

依任務規模：
- **大型功能**：Wave 1 @foundation-implementer → Wave 2 @logic-implementer + @api-implementer → Wave 3 @test-implementer（可選）
- **小型修復**：直接 @implementer

### 4. 多角度審查（Wave 1 + Wave 2）

- Wave 1 平行：@style-reviewer + @security-reviewer + @perf-test-reviewer
- Wave 2 合併：@review-lead（SOLID + 功能正確性 + 交叉比對）

> ⏸️ **等待使用者決定修復項目**

---

## 與原 feature-dev 的對應關係

| 原 feature-dev Phase | 新流程對應 |
|---------------------|-----------|
| Phase 1: Discovery | @planner 步驟 1 需求確認 |
| Phase 2: Codebase Exploration | @planner 步驟 2 多角度平行探索 |
| Phase 3: Clarifying Questions | @planner 反覆討論循環 |
| Phase 4: Architecture Design | @architect（含 ≥2 方案比較） |
| Phase 5: Implementation | Wave 實作團隊（分層平行） |
| Phase 6: Quality Review | Wave 1+2 審查團隊（4 代理交叉比對） |
| Phase 7: Summary | 主 agent 任務結束回報 |
