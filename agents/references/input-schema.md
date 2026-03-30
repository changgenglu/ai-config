# 子代理 Input Schema 參考

> 定義每類子代理預期從主代理接收的動態上下文，供主代理委派時參考。

## 分析型代理

### planner
| 欄位 | 必要性 | 說明 |
|------|--------|------|
| 需求共識 | 必須 | 功能目標、方案選擇、業務規則、排除範圍 |
| 測試策略 | 必須 | TDD / 事後補測 / 不寫（含原因） |
| 已確認決策 | 選填 | 使用者在探索階段已選定的項目 |
| 版本指示 | 選填 | 若為修改：修改意見 + 現有報告路徑 |

### architect
| 欄位 | 必要性 | 說明 |
|------|--------|------|
| 設計目標 | 必須 | 要解決的架構問題 |
| 約束條件 | 必須 | 技術棧限制、效能需求、相容性要求 |
| 規劃報告路徑 | 選填 | `.claude/reports/planning-report.md` |

### planning-specialist
| 欄位 | 必要性 | 說明 |
|------|--------|------|
| planner 報告路徑 | 必須 | `.claude/reports/planning-report.md` |
| 細化重點 | 選填 | 指定需要特別細化的模組 |

## 執行型代理

### implementer / foundation / api / logic-implementer
| 欄位 | 必要性 | 說明 |
|------|--------|------|
| 規劃報告路徑 | 必須 | `.claude/reports/planning-report.md` |
| 架構設計路徑 | 選填 | `.claude/reports/architecture-design.md` |
| 任務範圍 | 必須 | 要實作的具體步驟或修復項目 |
| Wave 1 摘要 | 選填 | foundation-implementer 的產出（Wave 2 代理需要） |
| Gemini 輔助指示 | 選填 | 觸發條件符合時由主代理注入 |

### build-error-resolver
| 欄位 | 必要性 | 說明 |
|------|--------|------|
| 錯誤訊息 | 必須 | 完整的錯誤日誌或截圖 |
| 觸發指令 | 必須 | 造成錯誤的指令（如 `npm test`） |
| 最近變更 | 選填 | 最近的 git diff 或變更摘要 |

## 審查型代理

### security-reviewer / style-reviewer / perf-test-reviewer
| 欄位 | 必要性 | 說明 |
|------|--------|------|
| 變更範圍 | 必須 | git diff 指令或變更檔案清單 |
| 規劃報告路徑 | 選填 | 供對照需求規格 |
| 審查重點 | 選填 | 主代理指定的風險點或關注區域 |

### review-lead
| 欄位 | 必要性 | 說明 |
|------|--------|------|
| Wave 1 報告路徑 | 必須 | 三份報告的路徑 |
| 變更範圍 | 必須 | git diff 指令或變更檔案清單 |
| 規劃風險點 | 選填 | 規劃標記的風險點與參數流向表 |

## 測試型代理

### tdd-guide / test-implementer / e2e-runner
| 欄位 | 必要性 | 說明 |
|------|--------|------|
| 規劃報告路徑 | 必須 | `.claude/reports/planning-report.md` |
| 測試範圍 | 必須 | 要測試的模組/方法清單 |
| 測試類型 | 必須 | 單元 / 整合 / E2E |
