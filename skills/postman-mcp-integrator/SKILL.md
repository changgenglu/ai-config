---
name: postman-mcp-integrator
description: 提供使用 Postman MCP Server 進行 Collection、Request 管理的操作指南與故障排除。當需要透過代理人自動化維護 Postman 集合時觸發。
---

# Postman MCP Integrator Skill

本技能旨在優化透過 MCP 工具操作 Postman API 的效率，並解決常見的參數結構與權限問題。

## 核心操作規範

### 1. 工作區 (Workspace) 權限處理
- **優先順序**：在建立 Collection 前，務必先呼叫 `getWorkspaces` 獲取列表。
- **權限避讓**：若團隊工作區 (Team Workspace) 報出 `403 Forbidden`，應自動回退 (Fallback) 至個人工作區 (Personal Workspace) 執行，並告知使用者。

### 2. Request 建立的正確資料結構 (⚠️ 重要)
`createCollectionRequest` 工具的參數高度敏感，必須遵循以下模式：

- **Urlencoded 模式**：
  - **錯誤**：直接在根層級傳入 `urlencoded: [...]`。
  - **正確**：必須包在 `data` 陣列中。
  ```json
  {
    "collectionId": "...",
    "dataMode": "urlencoded",
    "data": [
      { "key": "userId", "value": "{{user_id}}" },
      { "key": "hash", "value": "{{md5_hash}}" }
    ],
    "url": "..."
  }
  ```

- **Raw (JSON) 模式**：
  - 必須設定 `dataMode: "raw"` 並提供 `rawModeData` 字串。
  - 建議在 `headerData` 中加入 `Content-Type: application/json`。

### 3. 變數管理
- 建議在 URL 與 Body 中廣泛使用 `{{variable_name}}` 語法，以利使用者在 Postman 介面中切換 Environment。

## 故障排除 (Troubleshooting)
- **Error: additional properties**：通常是因為誤用了 `urlencoded` 或 `queryParams` 等非根層級屬性。請檢查並將資料移至 `data` 屬性。
- **404 Collection Not Found**：確認 `collectionId` 格式正確，且該 ID 存在於當前選定的 Workspace 中。

## 執行流程
1. 獲取當前使用者與工作區資訊。
2. 建立/定位 Collection。
3. 根據 `dataMode` (raw/urlencoded) 建構正確的 `data` 物件。
4. 逐一新增請求，並在回應中確認 `model_id` 以驗證成功。
