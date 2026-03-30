# Claude Code AI 設定架構

> 這是 `~/.claude/` 設定目錄的版本控制專案，定義了一套以主代理為調度核心、由 17 個子代理與 32 個技能構成的 AI 開發工作流程。

---

## 設計概念

本架構的設計理念是**語言無關、流程驅動、自然語言觸發**：

- **不綁定特定後端語言**：核心的調度規則（CLAUDE.md）、子代理定義（agents/）、工作流程不包含任何特定程式語言的假設。語言相關的知識（如 Laravel、NestJS、TypeORM）全部封裝在 skills/ 中，可自由擴充或替換。
- **預設 TDD 但非硬性規定**：流程在需求探索階段會詢問測試策略（TDD / 事後補測 / 不寫），根據使用者選擇走不同路徑。
- **無需記憶指令**：所有流程透過自然語言觸發（如「幫我做一個 X 功能」「這個 bug 怎麼回事」），主代理會自動判斷該走哪條路徑、委派哪個子代理。
- **可擴充的技術棧**：若你的專案使用不同的技術棧（如 Python/Django、Go、React），可以請 AI 讀取你的設定後，針對需要的技術棧建立對應的 skill。

---

## 依賴套件

本設定依賴以下外部套件，使用前請確認已安裝：

### 必要依賴

| 套件 | 用途 | 安裝方式 |
|------|------|---------|
| **Superpowers** | 提供方法論層：brainstorming、systematic-debugging、verification-before-completion、receiving-code-review 等 | `/plugin install superpowers@claude-plugins-official` |

### 選用依賴

| 套件 | 用途 | 安裝方式 | 備註 |
|------|------|---------|------|
| **Gemini CLI** | `@gemini-researcher` 代理的底層執行引擎，提供超長上下文的大規模分析能力 | `npm install -g @anthropic-ai/gemini-cli` | 若不使用，需請 AI 將 Gemini 相關流程從 CLAUDE.md 中移除，否則 `@gemini-researcher` 會反覆報錯 |
| **RTK (Rust Token Killer)** | Token 優化 CLI 代理，透過 hook 自動攔截 git/npm 等指令，過濾冗餘輸出以節省 60-90% token | [rtk-ai/rtk](https://github.com/rtk-ai/rtk) | 強烈建議安裝，可顯著降低 token 成本 |

### 關於 Gemini 依賴

`gemini/` 目錄包含 Gemini CLI 的全域提示詞（`~/.gemini/GEMINI.md` 和 `~/.gemini/CLAUDE.md`）。如果你暫時不打算使用 Gemini：

1. **不要複製** `gemini/` 目錄到 `~/.gemini/`
2. **請 AI 修改** CLAUDE.md，移除以下內容：
   - 4.1 節「Gemini 研究代理（主代理平行派出）」段落
   - 所有 `@gemini-researcher` 觸發條件
   - 結果合併規則
3. **刪除** `agents/gemini-researcher.md` 和 `skills/gemini-headless/`
4. 否則主代理會在符合觸發條件時不斷嘗試呼叫 Gemini 並失敗

---

## 專案層級設定

**建議將專案層級的 CLAUDE.md 放在專案目錄的 `.claude/` 下**（而非專案根目錄）：

```
your-project/
├── .claude/
│   ├── CLAUDE.md          # 專案層級規則（覆寫全域設定）
│   ├── reports/           # 流程產出的規劃報告、審查報告
│   │   ├── planning-report.md
│   │   ├── code-review.md
│   │   └── implementation.md
│   └── docs/              # 版本化的正式報告
│       ├── xxx-plan-v1.0.md
│       └── xxx-code-review-v1.0.md
├── src/
└── ...
```

原因：本工作流程中，@planner 的規劃報告、@review-lead 的審查報告、@implementer 的實作摘要等，都會寫入 `.claude/reports/`。將專案 CLAUDE.md 也放在 `.claude/` 下，可以讓所有 AI 相關的設定與產出集中管理，不污染專案根目錄。

---

## 目錄結構

```
ai-config/
├── CLAUDE.md                    # 全域規則：角色定位、工作流程、行為約束
├── settings.json                # Claude Code 全域設定（工具權限、MCP 整合）
├── agents/                      # 子代理定義（17 個）
│   ├── planner.md               # 需求拆解與規劃報告
│   ├── architect.md             # 系統架構設計
│   ├── planning-specialist.md   # 技術規格細化
│   ├── implementer.md           # 小型修復 / 路徑 C
│   ├── foundation-implementer.md# Wave 1 基礎層
│   ├── logic-implementer.md     # Wave 2 商業邏輯層
│   ├── api-implementer.md       # Wave 2 接口層
│   ├── tdd-guide.md             # TDD 測試先行
│   ├── test-implementer.md      # Wave 3 測試補寫
│   ├── e2e-runner.md            # E2E 測試生成
│   ├── build-error-resolver.md  # 建置錯誤修復
│   ├── security-reviewer.md     # 資安審查（Wave 1）
│   ├── style-reviewer.md        # 風格審查（Wave 1）
│   ├── perf-test-reviewer.md    # 效能審查（Wave 1）
│   ├── review-lead.md           # 主審合併（Wave 2）
│   ├── prompt-optimizer.md      # 提示詞精煉
│   ├── gemini-researcher.md     # Gemini 研究代理（新增）
│   └── references/              # 共用參考文件
│       ├── common-guardrails.md # 所有子代理的共用護欄
│       ├── input-schema.md      # 子代理預期接收的輸入規格
│       ├── planner-template.md  # 規劃報告範本
│       └── review-lead-template.md # 主審報告範本
├── skills/                      # 技能模組（32 個目錄）
│   ├── auto-skill/              # 自動知識載入（任務啟動協議）
│   ├── critical-thinking/       # 批判性思考（已合併 critical-analysis）
│   ├── gemini-headless/         # Gemini CLI 操作手冊（供 @gemini-researcher 專用）
│   ├── architecture-reviewer/   # 架構審查與 SOLID 原則
│   ├── laravel-expert/          # Laravel 框架最佳實踐
│   ├── laravel-coding-standard/ # Laravel 編碼規範
│   ├── implementation-engineer/ # 實作工程師方法論
│   └── ...（其餘 25 個領域技能）
├── gemini/                      # Gemini CLI 全域設定（Claude 依賴）
│   ├── GEMINI.md                # Gemini 角色定位、輸出原則、安全規則
│   └── CLAUDE.md                # Gemini 的知識庫（SOLID 速查、工具對照、呼叫鏈方法論）
├── commands/                    # 自訂斜線指令
├── hooks/                       # 事件掛鉤腳本
└── optimization-plans/          # 優化規劃文件
    └── subagent-prompt-optimization-v1.0.md
```

---

## 1. 架構概覽

### 1.1 角色分層

```
┌─────────────────────────────────────────────────────┐
│                    使用者                            │
│   提出需求 / 確認規劃 / 決定修復項目 / 審查報告       │
└────────────────────┬────────────────────────────────┘
                     │ 對話 / 指令
┌────────────────────▼────────────────────────────────┐
│              主代理（任務執行總監）                   │
│  - 需求探索（對話屬於主代理，子代理無法多輪對話）      │
│  - 工作流程調度（判斷路徑 A/B/C、Wave 次序）          │
│  - 品質把關（審閱子代理摘要，確認符合規劃）            │
│  - Token 管控（選最低成本的適任 model）               │
│  - 5 個強制回報點（⏸️ 暫停等待使用者決策）            │
└──────────────┬──────────────────────┬───────────────┘
               │ 委派任務              │ 委派任務
┌──────────────▼──────────────────────▼───────────────┐
│                    子代理團隊（17 個）                 │
│                                                      │
│  分析型          執行型              審查型           │
│  @planner        @foundation         @security        │
│  @architect      @logic              @style           │
│  @planning-spec  @api                @perf-test       │
│  @gemini-res     @implementer        @review-lead     │
│                  @tdd-guide                           │
│                  @test-implementer                    │
│                  @e2e-runner                          │
│                  @build-error-resolver                │
│                  @prompt-optimizer                    │
└──────────────────────────────────────────────────────┘
```

### 1.2 代理清單

| 名稱 | model | 類型 | 職責 |
|------|-------|------|------|
| `@planner` | sonnet | 分析 | 接收需求共識，執行程式碼探索與影響分析，產出規劃報告 |
| `@architect` | opus | 分析 | 系統架構設計、ERD、分層圖、ADR |
| `@planning-specialist` | sonnet | 分析 | 將規劃報告細化為可執行的技術規格文件 |
| `@gemini-researcher` | sonnet | 分析/Meta | Gemini CLI 無頭模式研究代理，執行大範圍掃描與批判性審查，所有輸出經三級驗證後回傳 |
| `@implementer` | sonnet | 執行 | 小型修復、探索性實作（路徑 C）、審查後程式碼修復 |
| `@foundation-implementer` | haiku | 執行 | Wave 1：Migration、Model、Config、Route 基礎結構 |
| `@logic-implementer` | sonnet | 執行 | Wave 2：Service、Repository、Action、Event、Job 商業邏輯 |
| `@api-implementer` | sonnet | 執行 | Wave 2：Controller、Request、Resource、Middleware 接口層 |
| `@tdd-guide` | sonnet | 執行 | TDD 測試先行，遵循 Red-Green-Refactor 循環 |
| `@test-implementer` | sonnet | 執行 | Wave 3（可選）：實作後補寫 Unit/Feature Test |
| `@e2e-runner` | sonnet | 執行 | 功能完成後生成 E2E 整合測試腳本 |
| `@build-error-resolver` | sonnet | 執行 | CI 失敗、型別錯誤、測試紅燈的根因定位與最小修復 |
| `@prompt-optimizer` | inherit | Meta | 提示詞結構化、專案上下文注入 |
| `@security-reviewer` | sonnet | 審查 | Wave 1：OWASP Top 10 漏洞掃描，產出 `review-security.md` |
| `@style-reviewer` | haiku | 審查 | Wave 1：命名規則、複雜度、編碼風格，產出 `review-style.md` |
| `@perf-test-reviewer` | haiku | 審查 | Wave 1：N+1 查詢、快取策略、測試覆蓋率，產出 `review-perf-test.md` |
| `@review-lead` | sonnet | 審查 | Wave 2：讀取三份 Wave 1 報告，SOLID + 功能正確性 + 交叉比對，產出最終合併報告 |

### 1.3 技能清單

**核心方法論技能（主代理與子代理均引用）**

| 技能名稱 | 觸發方式 | 用途 |
|---------|---------|------|
| `auto-skill` | 任務啟動時強制觸發 | 載入使用者過往知識庫，避免重複踩坑 |
| `critical-thinking` | 使用者提到「批判/挑戰/驗證/找盲點」，或子代理完成主要產出後自我審查 | 批判性思考與多維度驗證（已合併 critical-analysis） |
| `architecture-reviewer` | 請求架構分析、SOLID 評估、Clean Architecture 審查時 | 架構審查方法論，含 SOLID 原則與設計模式 |
| `implementation-engineer` | 生成、編輯或建立任何程式碼檔案時 | 資深後端工程師實作方法論 |
| `gemini-headless` | 僅供 `@gemini-researcher` 內部調用 | Gemini CLI 指令模板、防幻覺提示詞規範、三級驗證協議 |

**框架與領域技能（按需載入）**

| 技能名稱 | 觸發方式 | 用途 |
|---------|---------|------|
| `laravel-expert` | 涉及 Laravel 框架指引、Eloquent、Service Container | Laravel 框架最佳實踐 |
| `laravel-coding-standard` | 架構審查、編碼規範檢查 | Laravel 編碼規範（SOLID、DDD、Clean Architecture） |
| `nestjs-expert` | 涉及 NestJS 架構、模組設計、依賴注入 | NestJS 架構指引 |
| `api-designer` | 涉及 API 設計、RESTful、OpenAPI | API 設計規範 |
| `database-architect` | 涉及資料庫設計、Schema、索引優化 | 資料庫架構設計 |
| `redis-architect` | 涉及 Redis 快取、分散式鎖、Pub/Sub | Redis 架構設計 |
| `security-auditor` | 安全審查、OWASP | 安全稽核方法論 |
| `performance-analyst` | 效能分析、查詢優化 | 效能分析框架 |
| `mermaid-diagram` | 使用者明確要求 Mermaid 圖表 | Mermaid 8.9.x 相容語法規範 |
| `ascii-diagram-artist` | 需要 ASCII 圖表 | ASCII 圖表繪製 |

**其他專業技能（17 個）**

`business-analyst`、`devops-engineer`、`qa-tester`、`ux-designer`、`code-mentor`、`doc-coauthoring`、`excel-expert`、`python-data-automator`、`markdown-stylist-tw`、`nestjs-module-generator`、`redis-ioredis-specialist`、`tcp-websocket-specialist`、`typeorm-mysql-architect`、`pdf`、`prompt-engineer`、`postman-mcp-integrator`、`internal-comms`

---

## 2. 工作流程

### 2.1 標準工作流程（12 步驟）

```
步驟 1   理解需求
         Glob/Grep 建立上下文，確認 .claude/reports/ 目錄存在

步驟 2a  對話式需求探索（主代理直接處理，子代理無法多輪對話）
         依序確認：需求背景 → 功能目標 → 方案選擇 → 業務規則
         → 權限控制 → 驗收標準 → 測試策略（必問）→ 排除範圍

⏸️ Hard Gate：項目 1-3 清晰後才進入 2b

步驟 2b  委派 @planner 產出規劃報告
         → ⏸️ 回報使用者，等待確認（回報點 1）

步驟 3   委派 @architect 產出架構設計（若需要）
         → ⏸️ 回報使用者，等待確認（回報點 2）

步驟 4   建立 git worktree 隔離工作區（大型功能建議）

步驟 5   委派測試代理（依規劃報告測試策略）
         TDD → @tdd-guide（先）
         事後補測 → 進入步驟 6，實作後補
         不寫測試 → 直接進入步驟 6

步驟 6   委派實作（依路徑選擇）
         路徑 A — Subagent-Driven
         路徑 B — Wave（foundation → logic+api → test）
         路徑 C — @implementer 直接實作

         ⏸️ 實作中遇規劃未涵蓋問題 → 暫停回報（回報點 3）

步驟 7   委派 @build-error-resolver（測試/建置失敗時）
         ⏸️ 無法自動修復 → 回報使用者（回報點 4）

步驟 8   審查分級（依變更規模判斷：微型/小型/中型以上）

步驟 9   委派審查 Wave 1（中型以上：三審平行）
         @style-reviewer + @security-reviewer + @perf-test-reviewer

步驟 10  委派審查 Wave 2
         @review-lead 讀取三份報告 + 交叉比對
         → ⏸️ 回報使用者，等待決定修復項目（回報點 5）

步驟 11  委派 @e2e-runner（若需要）

步驟 12  分支完成（若使用 worktree）
         ⏸️ 提示使用者選擇：合併/PR/保留/捨棄
```

### 2.2 實作路徑選擇

| 情境 | 路徑 | 審查方式 |
|------|------|---------|
| 使用者已指定精確檔案與內容，≤ 2 tool calls（非審查修復、非規劃文件） | 主代理直接執行 | 無需正式審查 |
| 需要探索才能實作 / 2-3 個子任務 / 同一模組 | 路徑 C：`@implementer` | 視需要委派 `@review-lead` |
| 獨立任務集合（各任務間無嚴格順序依賴） | 路徑 A：Subagent-Driven | 每任務後立即：spec-reviewer → quality-reviewer |
| Laravel 分層功能開發（Migration → Model → Service → Controller） | 路徑 B：Wave 流程 | 全部完成後批次：Wave 1（三審）→ Wave 2（合併） |
| 大規模重構（10+ 檔案） | 路徑 B：Wave + 分組平行 | Wave 1+2 批次審查 |

審查時機鐵律：路徑 A 採每任務即時審查；路徑 B 採完成後批次審查，兩路徑不會同時啟用。

### 2.3 審查流程（Wave 1 + Wave 2）

```
Wave 1（平行，依風險選擇 1-3 個審查員）
├── @style-reviewer（haiku）    — 命名/複雜度/編碼規範
├── @security-reviewer（sonnet）— OWASP Top 10/攻擊場景
└── @perf-test-reviewer（haiku）— N+1/快取/測試覆蓋率

         ↓ 三份獨立報告

Wave 2（接續）
└── @review-lead（sonnet）      — 讀取 Wave 1 三份報告
                                  SOLID 原則 + 功能正確性
                                  交叉比對發現複合問題
                                  產出最終合併審查報告

         ↓ ⏸️ 回報使用者
```

審查分級：
- **微型**（≤ 2 檔案、≤ 20 行、無邏輯變更）：免審查，主代理自行確認
- **小型**（≤ 3 檔案、≤ 50 行、輕量邏輯）：輕量審查，僅委派 `@review-lead`
- **中型以上**（> 3 檔案或 > 50 行或涉及安全/資料）：完整審查 Wave 1+2

### 2.4 Gemini 研究代理

`@gemini-researcher` 是 2026-03-30 新增的研究型代理，利用 Gemini CLI 的超長 context 能力執行 Claude 子代理不擅長的大規模分析任務。

**跨平台依賴架構**：

```
Claude Code（主系統）
├── @gemini-researcher（子代理，model: sonnet）
│   ├── 呼叫 gemini-headless 技能取得操作指南
│   └── 執行 `gemini -p "..." --approval-mode yolo -o text 2>/dev/null`
│       ↓
Gemini CLI（外部依賴）
├── ~/.gemini/GEMINI.md — 定義 Gemini 的角色為「Claude Code 研究分析子代理」
└── ~/.gemini/CLAUDE.md — 提供 SOLID 速查、工具對照、呼叫鏈方法論
```

**依賴關係說明**：

| 元件 | 位置 | 說明 |
|------|------|------|
| `@gemini-researcher` | `~/.claude/agents/gemini-researcher.md` | Claude 側的子代理定義，負責撰寫防幻覺提示詞、執行 Gemini、驗證結果 |
| `gemini-headless` 技能 | `~/.claude/skills/gemini-headless/SKILL.md` | Claude 側的操作手冊，僅供 `@gemini-researcher` 內部調用 |
| `GEMINI.md` | `~/.gemini/GEMINI.md` | Gemini 側的全域規則，定義角色、輸出原則、安全規則、防循環協議 |
| `CLAUDE.md`（Gemini 版） | `~/.gemini/CLAUDE.md` | Gemini 側的知識庫，含任務類型對照、信心標註、SOLID/多層架構速查 |

**技能同步**：Claude 的 `~/.claude/skills/` 與 Gemini 的 skills 共用同一套技能目錄。Gemini CLI 啟動時會自動載入 Superpowers 及自訂技能。當 Claude 側修改技能（如合併 `critical-analysis` → `critical-thinking`），Gemini 側也會同步生效。

**維護注意**：修改 Gemini 相關設定時，必須同步檢查兩側（見 `memory/feedback_gemini_sync.md`）：
1. Claude 側：`@gemini-researcher`、`gemini-headless` 技能、CLAUDE.md Gemini 調度段落
2. Gemini 側：`~/.gemini/GEMINI.md`（角色定位、安全規則）、`~/.gemini/CLAUDE.md`（知識庫）

**觸發條件（由主代理判斷是否平行派出）**：

| 條件 | 平行搭配的子代理 | 研究任務 |
|------|----------------|---------|
| 日誌/錯誤訊息 > 100 行 | `@build-error-resolver` | 日誌模式分析、錯誤時序追蹤 |
| 影響分析涉及 10+ 檔案 | `@planner` | 大範圍程式碼掃描，建立依賴圖 |
| 審查變更 > 5 檔案 | `@review-lead` | 交叉驗證審查發現 |
| 安全審查涉及認證/金流 | `@security-reviewer` | 擴大掃描範圍，找遺漏的攻擊面 |
| 規劃方案有重大架構決策 | `@planner`、`@architect` | 批判性思考，挑戰假設 |
| 需讀取外部 PDF/大型文檔 | 任何子代理 | 長文件萃取重點 |

**使用原則**：
- 最常見模式為「平行派出」：主代理同時委派 `@gemini-researcher`（研究）+ 其他子代理（主要任務）
- 禁止用 Gemini 撰寫實作程式碼（品質不穩定，修正成本反而更高）
- 矛盾時 Claude 子代理結論優先，Gemini 結果僅作為補充參考
- `@gemini-researcher` 已內建三級驗證協議（L1 抽樣/L2 全量/L3 邏輯），主代理不需額外驗證

---

## 3. 子代理觸發方式

### 3.1 主代理調度規則

| 觸發條件 | 委派對象 | model |
|---------|---------|-------|
| 需求共識確認完成 | `@planner` | sonnet |
| 涉及新模組、跨服務整合、schema 設計 | `@architect` | opus |
| @planner 報告需要技術規格細化 | `@planning-specialist` | sonnet |
| TDD 流程、規劃確認後實作前 | `@tdd-guide` | sonnet |
| 路徑 C 小型修復、或審查後修復項目 | `@implementer` | sonnet |
| Wave 1：Migration/Model/Config/Route | `@foundation-implementer` | haiku |
| Wave 2 平行：Service/Repository/Action | `@logic-implementer` | sonnet |
| Wave 2 平行：Controller/Request/Resource | `@api-implementer` | sonnet |
| Wave 3（事後補測）：Unit/Feature Test | `@test-implementer` | sonnet |
| 功能完成、驗收前 E2E | `@e2e-runner` | sonnet |
| CI 失敗、測試紅燈 | `@build-error-resolver` | sonnet |
| 中型以上審查：安全/認證/金流 | `@security-reviewer` | sonnet |
| 中型以上審查：命名/重構/DRY | `@style-reviewer` | haiku |
| 中型以上審查：N+1/效能/測試覆蓋 | `@perf-test-reviewer` | haiku |
| Wave 2 主審、小型輕量審查 | `@review-lead` | sonnet |
| 大範圍掃描/日誌分析/批判性審查（觸發條件符合時） | `@gemini-researcher` | sonnet |
| 提示詞優化 | `@prompt-optimizer` | inherit |

### 3.2 子代理間依賴

```
Wave 1（@foundation-implementer）
  ↓ 產出：基礎層摘要（Migration、Model 已建立）
  ↓ Wave 1.5（若 @architect 已定義 Service Interface 契約）
  ↓ 確認契約寫入 Wave 1 摘要，供 Wave 2 參照

Wave 2 平行（@logic-implementer + @api-implementer）
  依賴：Wave 1 的 Model 定義、Service Interface 契約
  ↓ 各自獨立執行，不互相等待

Wave 3 可選（@test-implementer）
  依賴：Wave 2 的完成產出（Service、Controller 已存在）

審查 Wave 1 平行（@security + @style + @perf-test）
  依賴：實作完成
  ↓ 各自獨立執行

審查 Wave 2（@review-lead）
  依賴：Wave 1 三份報告（review-security.md、review-style.md、review-perf-test.md）
  必須等 Wave 1 全部完成才執行
```

---

## 4. 技能依賴模式

### 4.1 子代理 × 技能矩陣

| 子代理 | 核心技能 | 按需技能 |
|--------|---------|---------|
| `@planner` | — | `critical-thinking`（方案評估） |
| `@architect` | `architecture-reviewer` | `database-architect`、`api-designer`、`critical-thinking` |
| `@planning-specialist` | — | `api-designer`、`database-architect` |
| `@implementer` | `implementation-engineer` | `laravel-expert`、`laravel-coding-standard`、`nestjs-expert` |
| `@foundation-implementer` | `implementation-engineer` | `laravel-expert`、`database-architect` |
| `@logic-implementer` | `implementation-engineer` | `laravel-expert`、`redis-architect` |
| `@api-implementer` | `implementation-engineer` | `laravel-expert`、`api-designer` |
| `@security-reviewer` | `security-auditor` | — |
| `@style-reviewer` | `laravel-coding-standard` | `architecture-reviewer` |
| `@perf-test-reviewer` | `performance-analyst` | `database-architect` |
| `@review-lead` | `architecture-reviewer`、`laravel-coding-standard` | `critical-thinking` |
| `@gemini-researcher` | `gemini-headless`（內部專用） | — |
| 主代理 | `auto-skill`（強制）、`critical-thinking` | 按需動態載入 |

### 4.2 Superpowers 整合（批次 1，已完成）

CLAUDE.md 中引用了三個 Superpowers 方法論：

| Superpowers 方法論 | 引用位置 | 用途 |
|-------------------|---------|------|
| `superpowers:verification-before-completion` | 步驟 3.2 完成驗證 | 子代理回報 DONE 前的五步驗證 Gate：IDENTIFY → RUN → READ → VERIFY → CLAIM |
| `superpowers:systematic-debugging` | 步驟 7 錯誤修復 | `@build-error-resolver` 的 4 Phase 方法論：Root Cause → Pattern Analysis → Hypothesis Testing → Implementation |
| `superpowers:receiving-code-review` | 審查回饋處理 | 主代理處理審查回饋前的技術評估（YAGNI 檢查、衝突確認、防表演性同意） |
| `superpowers:using-git-worktrees` | 步驟 4 隔離工作區 | 大型功能建立獨立分支工作區 |

**批次 2（待驗證）**：整合 `brainstorming`、`writing-plans` 方法論
**批次 3（待驗證）**：整合 SDD（Specification Driven Development）方法論，需 3 個以上路徑 A 任務驗證

---

## 5. 共用機制

### 5.1 共用護欄（common-guardrails.md）

所有子代理在執行時載入 `/home/dev/.claude/agents/references/common-guardrails.md`，包含：

- **安全護欄**：禁止輸出敏感憑證（`_PASSWORD`、`_SECRET`、`_TOKEN`、`_KEY` 等後綴），禁止執行破壞性操作
- **防循環護欄**：同一工具相同參數連續失敗 2 次立即停止；同一問題最多 3 個角度；Edit 報錯後禁止立即重試
- **範圍護欄**：禁止修改任務範圍外的檔案；修改前必須先 Read 確認（Read-Before-Write）
- **輸出護欄**：程式碼引用必須含檔案路徑與行號；禁止模糊描述

### 5.2 輸入規格（input-schema.md）

`/home/dev/.claude/agents/references/input-schema.md` 定義每類子代理預期從主代理接收的動態上下文：

- **分析型**（planner、architect、planning-specialist）：需求共識、測試策略、已確認決策
- **執行型**（implementer 系列）：規劃報告路徑、任務範圍、Wave 1 摘要（Wave 2 代理需要）
- **審查型**（security/style/perf-test reviewer、review-lead）：變更範圍、規劃報告路徑、審查重點
- **測試型**（tdd-guide、test-implementer、e2e-runner）：規劃報告路徑、測試範圍、測試類型

### 5.3 狀態回報協議

所有子代理輸出報告頂部必須有統一格式的狀態行，供主代理快速識別：

| 狀態 | 含義 |
|------|------|
| `✅ DONE` | 任務完成，附驗證指令的實際輸出 |
| `⚠️ PARTIAL` | 部分完成，詳見待決事項 |
| `🚫 BLOCKED` | 無法繼續，需要主代理或使用者指引 |
| `🔺 ESCALATE` | 超出職責範圍，需升級處理 |

---

## 6. 待辦事項與流程驗證

### 6.1 當前驗證進度

**已完成**：0/5（批次 1 修改自 2026-03-30 起驗證）

工作流程驗證目標：追蹤批次 1 修改（systematic-debugging、verification-before-completion、receiving-code-review 引用）在真實任務中的表現。

### 6.2 驗證指標（10 項）

| # | 指標 | 期望值 |
|---|------|--------|
| V1 | 審查無效標記率（🔵建議數 / 總發現數） | < 20% |
| V2 | 子代理歧義回報率（BLOCKED/ESCALATE 次數） | > 0（代表 Escalation 生效） |
| V3 | 狀態行覆蓋率（有狀態行的報告 / 總報告數） | 100% |
| V4 | 完成驗證遵循率（附驗證輸出的 DONE / 總 DONE 數） | 100% |
| V5 | systematic-debugging 觸發（Bug 修復任務是否遵循 4 Phase） | 是 |
| V6 | receiving-code-review 觸發（審查回饋處理是否有 YAGNI 檢查） | 是 |
| V7 | Gemini 研究準確率（驗證後可信度百分比） | ≥ 80% |
| V8 | 整體品質評分（使用者主觀評分） | ≥ 70 |
| V9 | Token 效率（本次 token 總量） | 無基準，記錄趨勢 |
| V10 | 流程阻塞事件（兩套系統衝突、路徑找不到等） | 0 |

### 6.3 批次升級路線圖

**批次 1（當前，已完成）**：
- 方法論引用整合（systematic-debugging、verification-before-completion、receiving-code-review、using-git-worktrees）
- 所有 16 個代理完成提示詞優化（Communication Protocol + Thinking Budget + IDENTITY 重寫 + Few-Shot + Escalation）
- XML tags 試點（3 個代理）+ Guardrails 統一 + Input Schema 建立
- `critical-analysis` 合併入 `critical-thinking`，刪除舊技能
- 新增 `@gemini-researcher` 代理，更新 CLAUDE.md 調度規則

**批次 2（就緒條件：5 個任務驗證完成 + 品質 ≥ 70 + 無重大阻塞）**：
- 整合 Superpowers `brainstorming` 方法論
- 整合 Superpowers `writing-plans` 方法論
- 評估 XML tags 是否擴展到所有代理（目前僅 3 個試點）

**批次 3（就緒條件：批次 2 完成 + 3 個以上路徑 A 任務驗證）**：
- 整合 SDD（Specification Driven Development）方法論
- 驗證路徑 A（Subagent-Driven）的 SDD 相容性

### 6.4 待辦 TODO

**流程驗證（5 個任務）**：
- [ ] 任務 1：第一個具規模任務（涉及 3+ 檔案、需走規劃→實作→審查流程）
- [ ] 任務 2-5：依序填寫，每任務完成後更新 `workflow-validation.md`

**批次 2 整合**：
- [ ] `brainstorming` 方法論引用整合進 CLAUDE.md 或相關代理
- [ ] `writing-plans` 方法論引用整合

**批次 3 整合**：
- [ ] SDD 方法論評估（需路徑 A 任務為主要觸發情境）

**XML 試點評估**：
- [ ] 評估現有 3 個 XML 試點代理的效果（是否提升輸出品質）
- [ ] 依評估結果決定是否擴展到所有 17 個代理

**CLAUDE.md 瘦身評估**：
- [ ] 當前 CLAUDE.md 約 26.4KB，評估是否過長影響主代理 context 效率
- [ ] 考慮將「詳細規則」移至 references/，CLAUDE.md 僅保留摘要與引用

**common-guardrails 有效性驗證**：
- [ ] 確認各代理是否確實在執行時讀取並遵循 `common-guardrails.md`
- [ ] V2 指標（歧義回報率 > 0）為主要驗證方式

### 6.5 追蹤項目現況總覽

> 來源：前次重構追蹤報告 + 本次重構新增項目。每個項目標註當前狀態，供快速掌握全貌。

**已解決（不再追蹤）**：

| 原 ID | 項目 | 解決方式 | 日期 |
|--------|------|---------|------|
| P3-001 | `critical-analysis` skill 名稱不匹配 | 合併 critical-analysis → critical-thinking，更新 4 個代理引用 | 2026-03-30 |
| 3.5 | `/tmp/` 清理可靠性 | 改用 `.claude/reports/` 取代 `/tmp/`，無固定檔名衝突 | 2026-03-30 |

**仍需追蹤（納入 5 個任務驗證）**：

| ID | 項目 | 類型 | 觸發條件 | 判定標準 |
|----|------|------|---------|---------|
| H1 | `@foundation-implementer`（haiku）Migration 品質 | 品質 | 路徑 B 任務 | Migration 無語法錯誤 |
| H2 | 審查分級閾值合理性（微型 ≤ 2 檔案 20 行） | 流程 | 路徑 B/C | 免審變更未在後續引發 bug |
| H3 | Service Interface 契約平行開發效果 | 流程 | 路徑 B | Wave 2 後無介面呼叫不匹配 |
| H4 | 探索收斂機制（2 輪追問是否足夠） | 流程 | 所有規劃 | 收斂後需求被打回 < 20% |
| H5 | 技術合理性自檢觸發頻率 | 體驗 | 所有需求探索 | 使用者回「不用管」< 2 次/5 任務 |
| H6 | 規劃迭代上限（3 版是否太嚴） | 流程 | 所有規劃 | 達上限頻率 < 20% |
| H7 | `@planning-specialist` 繁中產出品質 | 品質 | 技術規格任務 | 使用者無術語翻譯抱怨 |
| H8 | `@prompt-optimizer` 工作流程定位（孤島） | 流程 | 所有任務 | 有自然觸發場景 → 納入；無 → 標記按需工具 |
| H9 | CLAUDE.md 大小是否影響主代理表現 | 效能 | 所有任務 | 主代理未遺忘步驟 8+ 規則 |
| H10 | Wave 2 平行衝突（logic 修改 Service 介面） | 流程 | 路徑 B | logic-implementer 正確回報 ESCALATE |

**持續監控（非驗證項目，長期關注）**：

| 項目 | 當前狀態 | 預警門檻 | 緩解策略 |
|------|---------|---------|---------|
| 代理數量膨脹 | 17 個（含 gemini-researcher） | ≤ 20 軟上限 | 新增前評估合併可能性 |
| CLAUDE.md 大小 | ~26KB | 500 行觸發拆分 | 拆出 §5 Edit 安全規範 + §6 防循環協議到 references/ |
| 多 session 衝突 | worktree 部分緩解 | — | 長期引入 session ID 命名空間 |
| 代理定義版本控制 | 無自動化 lint | — | 建立引用一致性檢查腳本 |

---

## 7. 版本記錄

### 2026-03-30

| 類型 | 變更內容 |
|------|---------|
| 新增代理 | `@gemini-researcher`：Gemini 無頭模式研究代理，含三級驗證協議（L1 抽樣/L2 全量/L3 邏輯） |
| 新增技能 | `gemini-headless`：`@gemini-researcher` 的內部操作手冊，含防幻覺提示詞模板與指令規範 |
| 技能合併 | `critical-analysis` 合併入 `critical-thinking`，刪除舊技能；critical-thinking 現涵蓋三種觸發場景 |
| 更新 CLAUDE.md | 新增 Gemini 研究代理調度規則（觸發條件表、使用模式、結果合併規則、限制） |
| 更新 CLAUDE.md | 新增 `superpowers:receiving-code-review` 引用至審查回饋處理段落 |
| 更新 CLAUDE.md | 新增 `superpowers:systematic-debugging` 引用至步驟 7 錯誤修復段落 |
| 更新 CLAUDE.md | 新增 `superpowers:verification-before-completion` gate function 引用至完成驗證規範 |
| 新增文件 | `agents/references/common-guardrails.md`：所有子代理共用護欄 |
| 新增文件 | `agents/references/input-schema.md`：子代理輸入規格參考 |
| 新增文件 | `.claude/reports/workflow-validation.md`：流程驗證追蹤（批次 1，0/5） |
| 新增文件 | `.claude/optimization-plans/subagent-prompt-optimization-v1.0.md`：子代理提示詞優化規劃（Phase 1-4 均已完成） |
| 提示詞優化 | 所有 16 個代理完成：Communication Protocol（DONE/PARTIAL/BLOCKED/ESCALATE）+ Thinking Budget + IDENTITY 重寫 + Few-Shot + Escalation 條件 |
| XML 試點 | 3 個代理完成 XML tags 結構化（`<agent_identity>`、`<task_scope>`、`<protocol>`、`<output_schema>`、`<guardrails>`） |
| 工作流程驗證 | 啟用 4.5 節：5 個任務驗證機制，含 10 項指標與批次升級決策表 |
| planning-specialist 瘦身 | 從 14.1KB 縮減至 6.7KB（超出 token 上限問題修復） |
| 文件規範 | 新增 3.3 節：版本化報告需額外儲存一份至 `.claude/docs/` |
| 回報點強化 | 所有 ⏸️ 回報點新增「⚠️ 使用者應重點確認」清單機制 |

---

## 同步說明

此 repo 是 `~/.claude/` 的**版本化快照**。修改 `~/.claude/` 下的任何設定後，請同步更新此 repo：

```bash
cd ~/doc/ai-config
cp ~/.claude/CLAUDE.md ./CLAUDE.md
cp ~/.claude/settings.json ./settings.json
# 依需要複製 agents/ 或 skills/ 下的變更檔案
git add .
git commit -m "chore: sync ~/.claude changes — {簡述變更}"
```
