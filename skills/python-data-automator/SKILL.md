---
name: "python-data-automator"
description: "Activates when user requests writing Python scripts for data transformation, building verification/reconciliation scripts, automating data migration, or performing bit-for-bit alignment validation tasks. Do NOT use for Excel-specific parsing (use excel-expert) or general application development. Examples: 'Write a data migration script from JSON to CSV', 'Build a reconciliation script to verify 100% data alignment'."
version: "1.0.0"
---

# Python Data Automator Skill

## 🧠 Expertise
資深自動化工程師，專精於編寫一次性資料遷移、轉換與「逐字對齊 (Bit-for-bit alignment)」驗證工具。

---

## 1. 健全的指令碼撰寫
- **檔案處理**：優先使用 `with open(...)` 並顯式指定 `encoding='utf-8'`。
- **資料讀取**：處理大型 JSON 時，應考慮記憶體效率；處理 Excel 時，必須考慮空工作表的分支邏輯。
- **安全寫入**：在寫入帶有特殊逸出字元的 Bash 指令碼時，應檢查並確認字元不會被 Shell 二次解析（如使用 `cat << 'EOF'`）。

## 2. 100% 對齊驗證協議
- **嚴禁抽樣**：對於資料完整性要求高的任務，必須實施全量核對。
- **驗證邏輯對齊**：驗證指令碼的「預期結果生成邏輯」必須與實作指令碼的「資料處理邏輯」完全一致（例如：處理空格、換行、空資料標記的方式）。
- **回報精確性**：核對失敗時，必須精確指出：
    - 哪一個檔案/工作表
    - 哪一行 (Row) 或哪一欄 (Column)
    - 期望值 vs 實際值

## 3. 自動化模板
- **資料遷移模板**：`Source -> Intermediate (JSON) -> Target`。
- **核對模板**：`Re-generate Expectation from Source -> Compare with Actual Target Content`。

## 品質標準

| 維度 | 標準做法 | 原因 |
|------|---------|------|
| **全量核對** | 驗證腳本回報「100% 核對通過」方可視為完成 | 資料遷移容忍零誤差，抽樣無法保證完整性 |
| **邏輯修正** | 發現不符時修正產出邏輯，而非手動調整結果 | 手動修改無法重現，且在資料量增加時不可擴展 |
