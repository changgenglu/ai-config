---
name: "markdown-stylist-tw"
description: "Activates when generating Markdown documents, reports, or technical documentation in Traditional Chinese (Taiwan). Ensures consistent visual hierarchy with proper vertical spacing, dash-only lists, code block isolation, and CJK-Latin spacing rules. Use this skill whenever producing any .md output in zh-TW. Do NOT use for English-only documents or non-Markdown formats. Examples: '幫我寫一份技術報告', 'Generate a zh-TW README'."
---

# Markdown Stylist (TW)

本技能定義了優化 Markdown 文件可讀性的排版規範，適用於所有技術文件與報告。

## 核心規範

### 1. 列表符號與縮排
- **無序列表**：一律使用減號 `-` 作為符號（避免混用 `*` 或 `+`，以維持文件全局一致性）。
- **層次感**：列表項與上下段落之間應保持適當空間。

### 2. 垂直間距（Vertical Spacing）
- **標題與內容**：在標題（`#`, `##`, `###`）與其下方的段落、清單或程式碼區塊之間，必須插入一個完整的空行。
- **段落分隔**：不同段落、不同層級的列表之間，應保持一個空行以增加「呼吸感」。

### 3. 程式碼區塊（Code Blocks）
- **完全隔離**：程式碼區塊前後各保留一個空行（與文字緊貼會降低可讀性，且部分 Markdown 紅染器可能解析失敗）。
- **語言標籤**：必須標註正確的語言類型（如 `bash`, `typescript`, `json`）。

### 4. 臺灣 Typography 優化
- **中英混排**：在中文（CJK 字元）與半形英文字母、數字、半形符號（如 `( )`, `[ ]`）之間，應手動補齊一個空格以提升閱讀體驗。
  - *正例*：`在 Redis (6379) 中存儲資料`
  - *反例*：`在Redis(6379)中存儲資料`
- **標點符號**：中文內容使用全形標點符號（`，`, `。`, `：`, `；`），但在 Markdown 語法定義處除外。

## 執行流程
1. 產生內容後，掃描全文件檢視列表符號是否統一為 `-`。
2. 檢查標題、程式碼區塊前後的空行是否足夠。
3. 執行中英文空格校對。
4. 最終輸出必須符合上述視覺層次。
