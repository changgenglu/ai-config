# 子代理提示詞優化規劃 v1.0

> 基於 `/home/dev/doc/changgenglu-blog/public/markdownFiles/提示詞工程/子代理/提示詞建議.md` 設計規範，對當前 16 個子代理進行差距分析與優化規劃。

**日期**：2026-03-30

## 執行記錄

| Phase | 完成日期 | 狀態 |
|-------|---------|------|
| Phase 1 (P0) | 2026-03-30 | ✅ Communication Protocol + Thinking Budget（16/16） |
| Phase 2 (P1) | 2026-03-30 | ✅ IDENTITY 重寫 + Few-Shot (6) + Escalation（16/16） |
| Phase 3 (P2) | 2026-03-30 | ✅ XML 試點 (3) + Guardrails 統一 + Input Schema |
| Phase 4 (P3) | 2026-03-30 | ✅ planning-specialist 瘦身（14.1→6.7KB） |
| 追加：技能合併 | 2026-03-30 | ✅ critical-analysis 合併入 critical-thinking，刪除舊技能 |
| 追加：新代理 | 2026-03-30 | ✅ 新增 @gemini-researcher，更新 CLAUDE.md 調度規則 |
**分析來源**：Claude 主代理分析 + Gemini 無頭模式交叉驗證

---

## 1. 差距分析總覽

### 1.1 四區塊完整性（IDENTITY / SCOPE / PROTOCOL / OUTPUT）

**現狀**：所有代理都具備四區塊的「概念」，但以 Markdown 標題散落呈現，未以結構化標籤封裝。

| 區塊 | 現有對應 | 完整度 | 差距 |
|------|---------|--------|------|
| IDENTITY | 「你是...專家」開頭段 | 16/16 | 缺少「思考框架」與「見過的失敗模式」 |
| SCOPE | 「你負責的範圍」+「你不做的事」 | 16/16 | 缺少 `escalation` 升級條件 |
| PROTOCOL | 「執行流程」步驟 | 16/16 | 步驟判斷標準不夠具體（部分代理） |
| OUTPUT | 「輸出規範」或模板 | 14/16 | `style-reviewer`、`perf-test-reviewer` 模板偏簡略 |

### 1.2 XML Tags 使用

**現狀**：**16/16 代理完全使用 Markdown**，0 個使用 XML tags。

**影響評估**：規範指出 Claude 對 XML tags 有更高的注意力權重，在長 context 中解析可靠性優於 Markdown。但需權衡：
- 當前代理在實際使用中表現尚可，XML 遷移的 ROI 需評估
- XML 會降低人類可讀性，維護成本增加
- **建議：漸進式遷移**，先在關鍵區塊（SCOPE、GUARDRAILS）使用 XML，保留 Markdown 做內容格式化

### 1.3 精確性 vs 激勵性

**現狀**：14/16 代理使用「專家」「深厚經驗」等激勵性措辭。

**問題行（已驗證）**：

| ���案 | 問題措辭 | 建議替換 |
|------|---------|---------|
| `review-lead.md:9` | 「擁有 SOLID 原則...的深厚經驗」 | 改為列出具體思考框架與常見失敗模式 |
| `architect.md:9` | 「資深系統架構師...深厚經驗」 | 改為「使用 CAP 定理、故障域分析、運維複雜度三個維度評估」 |
| `security-reviewer.md:9` | 「資安審查專家，專精...」 | 改為列出具體檢查維度與攻擊模式 |
| `planning-specialist.md:10` | 「資深首席軟體架構師（10+ 年經驗）」 | 改為具體的評估框架 |
| 其餘 10 個代理 | 「{角色}專家。你的唯一職責是...」 | 改為角色 + 思考框架 + 失敗經驗 |

### 1.4 Thinking Budget

**現狀**：無任何代理有 `<thinking_budget>` 控制。

**差距**：
- **分析型**（architect, review-lead, planner）：未要求「列出 3 個替代方案」式深度推理
- **執行型**（foundation-implementer, api-implementer, logic-implementer）：未明確「禁止推論，嚴格執行」

### 1.5 Few-Shot Examples

**現狀**：僅 3/16 代理有程式碼範例片段，0/16 有規範定義的正例/反例對。

**高優先補充對象**：

| 代理 | 需要的範例類型 | 原因 |
|------|-------------|------|
| `review-lead` | Good Finding + Over-flagging 反例 | 防止過度標記瑣碎問題 |
| `security-reviewer` | 真實漏洞 + 誤報反例 | 防止將正常模式標記為安全問題 |
| `style-reviewer` | 合格風格 + 過度吹毛求疵反例 | 防止產生大量低價值建議 |
| `planner` | 優良規劃報告片段 + 過度規劃反例 | 控制報告深度 |
| `implementer` | 正確實作 + 過度工程反例 | 防止添加規劃外功能 |

### 1.6 Escalation 機制

**現狀**：多數代理在「你不做的事」或「後續可能需要的代理」中隱含升級，但缺乏**結構化的觸發條件**。

**差距**：代理遇到邊界情況時，不知道何時應該停止並報告 vs 硬做。

### 1.7 Communication Protocol（DONE/BLOCKED/ESCALATE）

**現狀**：**0/16 代理有統一的狀態回報格式**。各代理用「輸出摘要」（Markdown 模板）代替。

**影響**：
- 主代理需人工解讀每個子代理的不同格式輸出
- 無法自動判斷「任務成功」「任務卡住」「需要升級」

**���️ Claude vs Gemini 觀點分歧**：

Gemini 建議使用 JSON 狀態信號（`{"status": "DONE", ...}`）。但 Claude Code 的子代理回傳機制是**自然語言文字**，不是結構化 API。JSON 信號在此架構下：
- 增加提示詞複雜度但無法被主代理自動解析
- 主代理仍然需要閱讀自然語言理解子代理的產出

**建議替代方案**：不用 JSON，而是在輸出模板頂部加入**固定格式的狀態行**：
```
## 任務狀態：✅ DONE / ⚠��� PARTIAL / 🚫 BLOCKED / 🔺 ESCALATE
```
主代理可透過 Grep 快速識別狀態。

### 1.8 Guardrails（護欄）

**現狀**：所有代理有「禁止事項」列表，`implementer` 系列有「防循環協議」引用。但：
- 不一致：部分有密鑰保護，部分沒有
- 缺失：「工具重複失敗 2 次即停止」的硬限制未在所有代理中出現

### 1.9 Context Injection（`{{PLACEHOLDER}}`）

**現狀**：0/16 ���理使用 `{{PLACEHOLDER}}` 標記動態注入點。主代理透過委派提示詞傳入上下文，子代理 prompt 本身是靜態的。

**評估**：在 Claude Code 架構中，動態上下文由主代理在委派時注入，不寫死在子代理定義中。現行做法合理，但可在子代理 prompt 中加入 `<input_schema>` ��明「預期接收哪些上下文」，���主代理委派時更有結構。

### 1.10 Token Budget

**現狀**：基本符合。

| 代理 | 檔案大小 | 約 Token 數 | 建議上限 | 狀態 |
|------|---------|------------|---------|------|
| `planning-specialist` | 14.1KB | ~4500 | ~2000 | ⚠️ 超出 |
| `architect` | 6.7KB | ~2100 | ~3000 | ✅ |
| `review-lead` | 6.1KB | ~1900 | ~3000 | ✅ |
| `security-reviewer` | 6.3KB | ~2000 | ~2000 | ✅ 邊界 |
| `planner` | 5.9KB | ~1800 | ~2000 | ✅ |
| `implementer` | 5.0KB | ~1600 | ~2000 | ✅ |
| `foundation-implementer` | 4.0KB | ~1200 | ~1500 | ✅ |
| 其餘 9 個 | 3-5KB | ~1000-1600 | ~2000 | ✅ |

---

## 2. 優先級排序

| 優先級 | 項目 | 影響 | 工作量 | 理由 |
|--------|------|------|--------|------|
| **P0** | Communication Protocol（狀態行） | 高 | 小 | 所有代理加一行，主代理調度效率大幅提升 |
| **P0** | Thinking Budget（分析/執行分化） | 高 | 小 | 每個代理加 2-3 行，直接影響輸出品質與 token 效率 |
| **P1** | IDENTITY 重寫（精確化） | 中 | 中 | 移除激勵性措辭，改為思考框架+失敗模式 |
| **P1** | Few-Shot Examples（5 個核心代理） | 中 | 中 | 審查類代理最需要正例/反例來校準判斷尺度 |
| **P1** | Escalation 結構化 | 中 | 小 | 每個代理加入明確的升級觸發條件 |
| **P2** | XML Tags 漸進遷移 | 低-中 | 大 | 長期收益高但短期 ROI 不明確，建議先在 2-3 個代理試點 |
| **P2** | Guardrails 統一化 | 低 | 小 | 抽出共用護欄模板 |
| **P2** | Input Schema 定義 | 低 | 小 | 文件性質，改善主代理委派體驗 |
| **P3** | `planning-specialist` 瘦身 | 低 | 中 | 14KB 過大，拆分靜態知識到 references/ |

---

## 3. 執行方案

### Phase 1（P0，預估 1 session）

**目標**：全部 16 個代理加入 Communication Protocol + Thinking Budget

1. 定義共用模板片段（`~/.claude/agents/references/common-protocol.md`）
2. 每個代理的輸出模板頂部加入狀態行
3. 分析型代理加入深度推理指令
4. 執行型代理加入精確執行指令

### Phase 2（P1，預估 2-3 sessions）

**目標**：IDENTITY 重�� + Few-Shot + Escalation

1. 依代理類型重寫 IDENTITY（分析型 / 執行型 / 審查型三套模板）
2. 5 個核心代理補充正例/反例
3. 所有代理加入結構化 Escalation 條件

### Phase 3（P2，預估 2 sessions）

**目標**：XML 試點 + Guardrails 統一

1. 選擇 `review-lead`、`security-reviewer`、`implementer` 做 XML 遷移試點
2. 抽出共用 Guardrails 模板
3. 加入 Input Schema 定義

### Phase 4（P3，視需要）

**目標**：`planning-specialist` 瘦身，長期維護優化

---

## 4. Claude vs Gemini 觀點對照

| 項目 | Gemini 建議 | Claude 評估 | 最終建議 |
|------|-----------|------------|---------|
| XML Tags | P0 全面遷移 | P2 漸進試點 | **P2 漸進** — 現行 Markdown 在實務中表現尚可，全面遷移成本高 |
| JSON 狀態信號 | P0 加入 JSON | 不適用 Claude Code 架構 | **替代方案** — 固定格式狀態行 |
| 「專家」措辭 | P2 移除 | P1 重寫為思考框架 | **P1** — 同意移除但優先級略提升 |
| Few-Shot | P1 全部補齊 | P1 僅 5 個核心代理 | **P1 核心 5 個** — 其餘視效果再擴展 |
| Thinking Budget | P1 | P0 | **P0** — 工作量小，影響直接 |
| Communication Protocol | P0 | P0 | **P0** — 雙方一致 |

---

## 5. 風險與注意事項

1. **回歸風險**：大規模修改提示詞可能導致現有穩定行為改變。建議每個 Phase 完成後做一次真實任務測試，確認無回歸。
2. **XML 可讀性**：XML 遷移會降低人類直接閱讀的友好度。保留 Markdown 做內容格式化，XML 僅用於區塊級封裝。
3. **Token 膨脹**：加入 Examples、Thinking Budget 等內容會���加 token 數。需監控每個代理修改後的大小，確保不超出建議上限。
4. **漸進驗證**：每個 Phase 完成後，選一個代表性任務走完整流程（規劃→實作→審查），驗證優化效果。
