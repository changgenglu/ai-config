# AI 配置知識庫

> 本專案是所有 AI 提示詞、技能與代理設定的**單一真相來源（Single Source of Truth）**。
> 任何對 `~/.claude/` 的永久性變更，都應同步更新此 repo。

---

## 目錄結構

```
ai-config/
├── CLAUDE.md               # 全域規則與核心行為約束
├── settings.json           # Claude Code 全域設定（權限、MCP 等）
├── agents/                 # 子代理（SubAgent）定義
├── skills/                 # 技能（Skill）定義
├── commands/               # 自訂斜線指令（Slash Commands）
└── hooks/                  # 掛鉤腳本（Hooks）
```

| 目錄 | 職責 | 載入方式 |
|------|------|---------|
| `agents/` | 定義具有獨立職責、工具權限與系統提示的子代理 | Claude Code 自動掃描 `.claude/agents/` |
| `skills/` | 可按需載入的專業知識模組（含 SKILL.md） | `Skill` 工具呼叫 |
| `commands/` | 使用者可在對話中用 `/xxx` 呼叫的自訂指令 | `/指令名稱` 觸發 |
| `hooks/` | 在特定事件（如 context 滿載）自動執行的 shell 腳本 | Claude Code 事件系統 |

---

## 核心設計原則

- **對話屬於主 agent，執行屬於子代理**：子代理是一次性執行、無法與使用者互動。所有需要來回討論的情境都由主 agent 直接處理，僅在需要資訊時委派子代理調查後回報。
- **自包含委派**：委派提示詞必須自包含——子代理不繼承主 agent 的對話歷史，只提供完成任務所需的最小上下文。
- **Evidence Before Assertions**：所有子代理回報完成前必須實際執行驗證指令並附上具體輸出。

---

## 開發工作流程（完整生命週期）

主 agent 永遠只負責**路由**（判斷委派給誰），不直接執行任何領域工作。

### 流程總覽

```mermaid
flowchart TD
    U[使用者提出需求] --> EXPLORE[2a. 對話式需求探索\n主 agent 直接與使用者對話\n一次一個問題、2-3 方案選項]
    EXPLORE --> GATE{使用者確認\n方向正確？}
    GATE -- 繼續探索 --> EXPLORE
    GATE -- 確認 --> PLAN[2b. 委派 @planner\n帶入完整需求共識\n代碼探索 + 影響分析\n產出規劃報告 v1.0]
    PLAN --> DISCUSS{使用者確認規劃？}
    DISCUSS -- 需要修改 --> REVISE[重新委派 @planner\n帶入修改意見\n遞增版本]
    REVISE --> DISCUSS
    DISCUSS -- 確認 OK --> ARCH{涉及新架構？}
    ARCH -- 是 --> DESIGN[委派 @architect\n產出架構設計]
    DESIGN --> WORKTREE_Q{大型功能？}
    ARCH -- 否 --> WORKTREE_Q
    WORKTREE_Q -- 是 --> WORKTREE[建立 git worktree\n隔離工作區]
    WORKTREE --> TDD_Q{需要 TDD？}
    WORKTREE_Q -- 否 --> TDD_Q
    TDD_Q -- 是 --> TDD[委派 @tdd-guide\n建立測試先行]
    TDD_Q -- 否 --> IMPL_PATH{實作路徑？}
    TDD --> IMPL_PATH

    IMPL_PATH -- 路徑 A\nSubagent-Driven --> SD[每個任務獨立委派\n@implementer\n即時審查]
    IMPL_PATH -- 路徑 B\nWave --> IW1[Wave 1\n@foundation-implementer]
    IMPL_PATH -- 路徑 C\n小型修復 --> IMPL_DIRECT[委派 @implementer\n直接實作]

    SD --> SD_REVIEW[每任務後\nspec-reviewer\nquality-reviewer]
    SD_REVIEW --> SD_FINAL[全部完成後\n@review-lead 最終審查]
    SD_FINAL --> USER_REVIEW

    IW1 --> IW2[Wave 2 平行\n@logic-implementer\n@api-implementer]
    IW2 --> IW3_Q{需要補寫測試？}
    IW3_Q -- 是 --> IW3[Wave 3\n@test-implementer]
    IW3_Q -- 否 --> TEST
    IW3 --> TEST

    IMPL_DIRECT --> TEST

    TEST{測試/建置通過？}
    TEST -- 失敗 --> FIX[委派 @build-error-resolver\nSystematic Debugging 4 階段]
    FIX --> TEST
    TEST -- 通過 --> W1[Wave 1：平行審查\n@style-reviewer\n@security-reviewer\n@perf-test-reviewer]
    W1 --> W2[Wave 2：主審合併\n@review-lead\n讀取 3 份報告 + 交叉比對]
    W2 --> USER_REVIEW

    USER_REVIEW{使用者審查報告}
    USER_REVIEW -- 討論型回饋 --> DISCUSS_FB[主 agent 直接對話\n必要時委派調查]
    DISCUSS_FB --> USER_REVIEW
    USER_REVIEW -- 需要修復 --> HOTFIX[委派 @implementer\n修復指定問題]
    HOTFIX --> RE_REVIEW[review-lead 更新報告]
    RE_REVIEW --> USER_REVIEW
    USER_REVIEW -- 確認通過 --> BRANCH_Q{使用 worktree？}
    BRANCH_Q -- 是 --> BRANCH[分支完成\n合併/PR/保留/捨棄]
    BRANCH_Q -- 否 --> DONE[完成]
    BRANCH --> DONE
```

### 三個階段與版本化報告

#### 階段一：需求探索與規劃（程式碼不動）

1. 使用者提出需求
2. **主 agent 對話式探索（步驟 2a）**：主 agent 直接與使用者逐步確認需求——一次只問一個問題，遇到多方案時提出 2-3 選項讓使用者選擇。在使用者確認「方向正確」前，絕不委派任何子代理。
3. **委派規劃報告（步驟 2b）**：需求共識確認後，主 agent 將完整需求（功能目標、方案選擇、業務規則、排除範圍等）整理為自包含的任務描述，委派 **@planner** 執行代碼探索、影響分析並產出規劃報告（`/tmp/planning-report-latest.md`）
4. **⏸️ 回報使用者**：主 agent 彙整規劃報告摘要，等待使用者確認或要求修改（每次修改重新委派 @planner 並遞增版本號）
5. **使用者明確說「開始實作」後，才進入下一階段**

> 若涉及架構決策，委派 **@architect** 產出架構設計文件。
> 若需技術規格細化，委派 **@planning-specialist** 產出技術規格。

#### 階段二：實作（依據已確認的規劃）

依任務特性選擇實作路徑：

| 情境 | 實作路徑 | 審查方式 |
|------|---------|---------|
| 使用者已指定精確檔案與內容，≤ 2 tool calls | 主 agent 直接執行 | 無需正式審查 |
| 需要探索才能實作 / 2-3 個子任務 / 同一模組 | **路徑 C**：@implementer | 視需要委派 @review-lead |
| 獨立任務集合（各任務間無嚴格順序依賴） | **路徑 A**：Subagent-Driven | 每任務即時審查 |
| Laravel 分層功能開發（Migration → Model → Service → Controller） | **路徑 B**：Wave 流程 | 全部完成後批次審查 |
| 大規模重構（10+ 檔案） | **路徑 B**：Wave + 分組平行 | 批次審查 |

> **審查時機鐵律**：路徑 A 採每任務即時審查；路徑 B 採完成後批次審查。兩路徑不會同時啟用。

**路徑 A — Subagent-Driven（獨立任務集合）**：

1. 每個任務獨立委派 **@implementer（haiku）**
2. 每個任務完成後立即審查：spec-reviewer → quality-reviewer
3. 全部任務完成後委派 **@review-lead** 做最終整體審查
4. ⏸️ 回報使用者

**路徑 B — Wave（Laravel 分層架構）**：

1. 若採用 TDD，先委派 **@tdd-guide** 建立測試
2. **Wave 1**：委派 **@foundation-implementer**（haiku）建立 Migration、Model、Config、Route
3. **Wave 2**：平行委派 **@logic-implementer**（sonnet）+ **@api-implementer**（sonnet）
4. **Wave 3**（可選）：委派 **@test-implementer**（sonnet）為已完成程式碼補寫測試
5. 全部完成後進入階段三批次審查

**路徑 C — 小型修復**：直接委派 **@implementer**（haiku）

**測試策略決策**（主 agent 依規劃報告判斷）：

| 條件 | 委派 |
|------|------|
| 規劃指定 TDD | @tdd-guide（先）→ 實作團隊（後） |
| 規劃指定需要測試但非 TDD | 實作團隊（先）→ @test-implementer（後） |
| 規劃未要求測試 | 僅實作團隊 |

#### 階段三：多角度審查與修復（程式碼凍結）

審查採用 **兩波次（Wave 1 + Wave 2）** 架構，多角度交叉比對：

**Wave 1（平行，3 個專項審查員）**：
1. 委派 **@style-reviewer**（haiku）— 程式碼品質 + 編碼規範
2. 委派 **@security-reviewer**（sonnet）— OWASP Top 10 + 安全漏洞
3. 委派 **@perf-test-reviewer**（haiku）— 效能 + 可測試性

**Wave 2（接續，1 個主審）**：
4. 委派 **@review-lead**（opus）— SOLID + 功能正確性 + 讀取 3 份報告交叉比對 → 產出最終合併報告（`/tmp/code-review-latest.md`）

**使用者決策（通用回饋處理）**：

使用者在任何 ⏸️ 回報點的回饋分為兩類：

| 回饋類型 | 處理方式 |
|---------|---------|
| **指令型**（確認/修改/否決/指定項目） | 走對應行動表格 |
| **討論型**（為什麼？有沒有其他方案？） | 主 agent 直接與使用者對話，必要時委派子代理調查 |

5. 使用者閱讀合併報告，**判斷哪些問題需要修復**
6. 委派 **@implementer** 修復 → **@review-lead** 更新報告版本
7. 反覆至使用者確認通過

### 版本記錄規範

所有規劃報告與審查報告都必須包含版本記錄表：

```markdown
## 版本記錄

| 版本 | 更新時間 | 變更摘要 |
|------|---------|---------|
| v1.0 | 2026-03-16 14:00 | 初版規劃 |
| v1.1 | 2026-03-16 15:30 | 根據討論調整：移除 X 功能、新增 Y 欄位 |
| v2.0 | 2026-03-17 10:00 | 重大修訂：改採方案 B 架構 |
```

**版本號規則**：
- `v1.0` → `v1.1`：小幅調整（措辭、補充、微調）
- `v1.x` → `v2.0`：重大變更（架構改動、功能增刪、方案替換）

---

## 主 Agent 委派限制

- 不得執行任何屬於 subAgent 職責範圍的工作
- 同時啟動的 subAgent **不超過 4 個**（避免 context 爆炸）
- 未收到 subAgent 完整輸出前，不啟動下一個委派
- **規劃未經使用者確認前，禁止異動程式碼**
- **審查報告中的修復項目，由使用者決定哪些需要修復**

---

## SubAgent 一覽表

### 開發流程團隊

| Agent | 職責 | Model | 觸發時機 |
|-------|------|-------|---------|
| `planner` | 接收需求共識、代碼探索、影響分析、規劃報告產出 | sonnet | 主 agent 完成需求探索後委派 |
| `architect` | 架構設計、資料模型、分層結構、ADR | opus | 涉及新模組、跨服務整合 |
| `tdd-guide` | TDD 引導、測試案例先行、驗收標準 | sonnet | 規劃確認後、實作前（TDD 流程） |
| `build-error-resolver` | Systematic Debugging 4 階段：根因調查→模式分析→假設驗證→修復 | sonnet | CI 失敗、測試紅燈 |
| `e2e-runner` | E2E 測試腳本生成、覆蓋矩陣 | sonnet | 功能完成、驗收前 |

### 實作團隊（Wave 分層）

| Agent | 職責 | Model | 波次 |
|-------|------|-------|------|
| `implementer` | 小型修復/審查後修復（路徑 C 直接實作） | **sonnet** | — |
| `foundation-implementer` | Migration、Model/Entity、Config、Route | **haiku** | Wave 1 |
| `logic-implementer` | Service、Repository、Action、Event、Job | **sonnet** | Wave 2（平行） |
| `api-implementer` | Controller、Request、Resource、Middleware | **sonnet** | Wave 2（平行） |
| `test-implementer` | 實作後補寫 Unit/Feature Test（可選） | **sonnet** | Wave 3（可選） |

### 審查團隊（Wave 1 + Wave 2）

| Agent | 審查維度 | 權重 | Model | 波次 |
|-------|---------|------|-------|------|
| `style-reviewer` | 程式碼品質 + 編碼規範 | 20% + 15% = 35% | **haiku** | Wave 1（平行） |
| `security-reviewer` | 安全性（OWASP Top 10） | 15% | **sonnet** | Wave 1（平行） |
| `perf-test-reviewer` | 效能 + 可測試性 | 5% + 5% = 10% | **haiku** | Wave 1（平行） |
| `review-lead` | SOLID + 功能正確性 + **交叉比對合併** | 25% + 15% = 40% | **opus** | Wave 2（接續） |

**交叉比對**：review-lead 在 Wave 2 讀取 3 份 Wave 1 報告，發現跨維度複合問題時升級嚴重度（如：style 發現方法過長 + perf-test 發現無測試 → 🔴 升級）。

### 輔助 agents

| Agent | 職責 | Model | 對應指令 |
|-------|------|-------|---------|
| `critical-analyst` | 多維度批判分析技術提案、架構決策 | inherit | `/critique` |
| `planning-specialist` | 需求→技術規格（Gap Analysis / Implementation Plan） | inherit | `/plan` |
| `prompt-optimizer` | 提示詞結構化、專案上下文注入 | inherit | `/prompt-optimize` |

### agents 分工說明

| 場景 | 使用 | 不使用 | 原因 |
|------|------|--------|------|
| 需求探索（與使用者對話） | **主 agent** | `planner` | 子代理無法多輪對話，對話由主 agent 處理 |
| 需求共識→規劃報告 | `planner` | `planning-specialist` | planner 做代碼探索 + 影響分析 + 規劃報告 |
| 技術規格細化 | `planning-specialist` | `planner` | planning-specialist 專注技術規格產出 |
| 架構方案設計 | `architect` | `critical-analyst` | architect 做前期設計；critical-analyst 做事後批判 |
| 架構方案評審 | `critical-analyst` | `architect` | critical-analyst 批判已有方案的邏輯健全性 |
| 程式碼審查 | 審查團隊 4 人 | 單一 reviewer | 多角度 + 交叉比對，品質更高、成本更低 |
| 大型功能實作（路徑 B） | 實作團隊 4 人（Wave 1→2→3） | `implementer` 單獨 | 分層平行實作，效率更高 |
| 獨立任務集合（路徑 A） | 多個 `implementer` 平行 | Wave 流程 | 任務間無依賴，不需分層 |
| 小型修復（路徑 C） | `implementer` | 實作團隊 | 不需要拆分的小範圍修改 |
| 實作後補測試（非 TDD） | `test-implementer` | `tdd-guide` | tdd-guide 是測試先行，test-implementer 是實作後補寫 |
| 回報點的討論型回饋 | **主 agent** | 任何子代理 | 子代理無法對話，主 agent 必要時委派調查再彙報 |

---

## Model 選擇策略

| 情境 | 模型 | 理由 |
|------|------|------|
| 邏輯層/接口層實作、測試補寫、錯誤修復、E2E 生成、TDD 引導、安全審查 | `sonnet` | 效能與成本的最佳平衡點 |
| 架構設計、審查主審（SOLID + 交叉比對）、批判分析 | `opus` | 需要深度推理能力 |
| 基礎層實作、程式碼風格審查、效能/測試覆蓋審查、瑣碎資訊整理 | `haiku` | 最低成本，適合機械性檢查 |

### SubAgent 統一輸出規範

所有子代理的報告統一寫入 `/tmp/`，命名規則如下：

| Agent | 輸出路徑 | 說明 |
|-------|---------|------|
| `planner` | `/tmp/planning-report-latest.md` | 規劃報告（含版本記錄） |
| `architect` | `/tmp/architecture-design-latest.md` | 架構設計文件 |
| `implementer` | `/tmp/implementation-latest.md` | 實作摘要（路徑 C） |
| `foundation-implementer` | `/tmp/impl-foundation-latest.md` | Wave 1 基礎層摘要 |
| `logic-implementer` | `/tmp/impl-logic-latest.md` | Wave 2 邏輯層摘要 |
| `api-implementer` | `/tmp/impl-api-latest.md` | Wave 2 接口層摘要 |
| `test-implementer` | `/tmp/impl-test-latest.md` | Wave 3 測試摘要 |
| `tdd-guide` | 直接寫入專案 `tests/` 目錄 | 測試檔案 |
| `style-reviewer` | `/tmp/review-style-latest.md` | Wave 1：品質+風格報告 |
| `security-reviewer` | `/tmp/review-security-latest.md` | Wave 1：安全報告 |
| `perf-test-reviewer` | `/tmp/review-perf-test-latest.md` | Wave 1：效能+測試報告 |
| `review-lead` | `/tmp/code-review-latest.md` | Wave 2：最終合併報告（含版本記錄） |
| `build-error-resolver` | 對話內直接回報 | 修復報告 |
| `e2e-runner` | 直接寫入專案 `tests/` 目錄 | E2E 測試檔案 |
| `critical-analyst` | `/tmp/critical-analysis-latest.md` | 批判分析報告 |
| `planning-specialist` | `/tmp/planning-latest.md` | 技術規格文件 |

每個子代理在完成工作後，必須在輸出末尾附上「**後續可能需要的代理**」段落，列出建議的下一步（不指揮主 agent，僅供參考）。

### `/compact` 使用規則（強制）

- 只在**功能完整實作並通過驗證後**才執行 `/compact`
- **禁止**在任務進行中、測試紅燈時、代理尚未輸出結果時使用
- 主 agent 在委派子代理前若 context 已滿，先完成當前委派後再清理

---

## 新增 SubAgent 指南

### frontmatter 格式規範

```yaml
---
name: {kebab-case 名稱}
description: "{一句話說明觸發時機}\n\n**觸發範例**：\n\n<example>\nContext: {情境說明}\n\nuser: \"{使用者輸入}\"\n\nassistant: \"{助理回應}\"\n\n<commentary>\n{為什麼這個 agent 適合此情境}\n</commentary>\n</example>"
tools: {逗號分隔的工具清單}
model: haiku|sonnet|opus
color: {顏色名稱}
---
```

### 系統提示範本

```markdown
你是 {專案/領域} 的 {角色名稱} 專家。你的唯一職責是：{一句話職責描述}。

## 核心職責

1. **{主要工作}**：{具體說明}
2. **{次要工作}**：{具體說明}

## 你不做的事

- 不做 {職責邊界 1}（交給 @{其他 agent}）
- 不做 {職責邊界 2}

## 執行流程

### 步驟 1：{初始化}
{具體步驟}

### 步驟 2：{主要工作}
{具體步驟}

### 步驟 3：{輸出}
{輸出規格}

## 輸出規格

{說明輸出格式、必要欄位、命名規則}

## 禁止事項

- 禁止 {行為 1}
- 禁止 {行為 2}
```

### 可用工具清單（按最小權限原則選擇）

| 類型 | 工具 | 用途 |
|------|------|------|
| 讀取 | `Read, Glob, Grep` | 讀取程式碼與檔案 |
| 寫入 | `Write, Edit` | 建立或修改檔案 |
| 執行 | `Bash` | 執行 shell 指令 |
| 網路 | `WebSearch, WebFetch` | 查詢外部資源 |
| IDE | `mcp__ide__getDiagnostics` | 取得 IDE 診斷資訊 |
| Notion | `mcp__notion__*` | 讀寫 Notion 頁面 |
| 技能 | `Skill` | 載入其他 Skills |
| 任務 | `TaskCreate, TaskGet, TaskUpdate, TaskList` | 管理任務清單 |

---

## 同步說明

此 repo 是 `~/.claude/` 的**版本化快照**。當你修改 `~/.claude/` 下的任何設定後，請同步更新此 repo 並建立 commit：

```bash
cd ~/doc/ai-config
# 複製更新的檔案
cp ~/.claude/CLAUDE.md ./CLAUDE.md
cp ~/.claude/settings.json ./settings.json
# ... 依需要複製其他檔案

git add .
git commit -m "chore: sync ~/.claude changes — {簡述變更}"
```
