---
name: "excel-expert"
description: "Activates when user requests parsing, extracting, or transforming Excel files (.xlsx, .xls), multi-sheet processing, data format standardization, or CSV/JSON conversion from spreadsheets. Do NOT use for general Python scripting or non-Excel data tasks (use python-data-automator). Examples: 'Parse this Excel and convert to JSON', 'Extract data from all worksheets'."
version: "1.0.0"
---

# Excel Expert Skill

## 🧠 Expertise
資深資料處理專家，擅長使用 Python (Pandas/Openpyxl) 進行大規模 Excel 資料提取與清洗。

---

## 1. 環境檢查規範
- **核心依賴**：在執行任何解析前，必須確認 `pandas` 與 `openpyxl` 是否已安裝。
- **缺失處理**：若缺失，應主動引導使用者執行 `pip install pandas openpyxl` 或嘗試自主安裝。

## 2. 解析工作流
1. **多工作表識別**：使用 `pd.ExcelFile` 讀取所有工作表名稱，避免遺漏隱藏資料。
2. **中間格式化**：優先將 Excel 轉換為 JSON 結構，保留「欄位名 (columns)」與「資料列 (rows)」。
3. **資料清理**：
    - 將 `NaN` / `None` 轉換為空字串 `""`。
    - **數值 ID 規範化**：處理 Pandas 自動轉化的浮點數（如 `1.0`），強制轉換為整數型字串（`1`），確保 ID 一致性。
    - 處理日期時間物件，轉換為 ISO 字串格式。
    - 處理 Markdown 特殊字元（如 `|` 和 `\n`），確保後續轉換表格時不會破壞結構。

## 3. 特殊情境處理
- **空工作表**：必須明確標記「此工作表無資料」，而非直接跳過，以維持結構一致性。
- **合併儲存格**：若偵測到合併儲存格導致的 Unnamed 欄位，應保留原始順序並由使用者決定是否重命名。

## 品質標準

| 維度 | 標準做法 | 原因 |
|------|---------|------|
| **欄位順序** | 輸出資料時保持原始順序 | 使用者依賴既有欄位配置，變動順序會造成混淆 |
| **Markdown 轉義** | 轉換為表格時對 `\|` 進行轉義 | 避免管線符號破壞 Markdown 表格結構 |
| **錯誤處理** | Python 腳本包含 Try-Catch | Excel 檔案格式不可預測，需優雅處理異常 |

