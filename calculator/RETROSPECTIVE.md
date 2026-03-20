# 計算機專案 — 工作流程回顧紀錄

> 日期：2026-03-17
> 用途：記錄本次 Wave 分層實作流程中遇到的問題與使用者回饋，供後續流程優化參考。

---

## 問題一：子代理 Bash 工具不可用

**現象**：Wave 2a（@logic-implementer）與 Wave 2b（@api-implementer）回報「Bash 工具目前未被允許執行」，無法自行執行語法檢查與功能驗證，最後由主 agent 補跑。

**根本原因**：`settings.json` 的 `permissions.allow` 只有 `Bash(date +%Y-%m-%d %H:%M:*)` 白名單。背景子代理沒有互動介面，確認請求被系統自動拒絕。

**使用者回饋**：需要研究如何在 `settings.json` 中開放較安全的 Bash 白名單（如 `php -l *`、`php -r *` 等唯讀操作），以讓子代理可以自行驗證，減少使用者盯著視窗確認的負擔。

---

## 問題二 + 四：Wave 1 未探測環境（PHP 版本 / Port 衝突）

**現象**：
- Wave 2a 使用 PHP 8.0 的 `match` 語法，但環境為 PHP 7.4，導致 `UnitConverterService.php` 語法錯誤，需手動修復。
- 規劃報告指定 Port 8080，但該 Port 已被 Apache 佔用，測試時需改用 8888。

**使用者回饋**：這類問題只會出現在尚未定義 `CLAUDE.md` 的全新專案、或 `CLAUDE.md` 未記錄技術棧的情況。此情境發生機率低，特地為此寫規則不划算，暫不處理。

---

## 問題三：`index.php` 兼作 Autoloader 導致 CLI 測試需要 workaround

**使用者回饋**：這不是流程問題，跳過。

---

## 問題五：規劃報告未同步使用者確認的待確認項目

**現象**：@planner 報告中百分比業務規則寫的是「選項 A（÷100）」，但使用者事後確認選的是「選項 B（手機計算機慣例）」。Wave 2 委派時主 agent 帶入了正確規則，實作無誤，但規劃報告版本未同步。

**使用者回饋**：確認需要改善。應在使用者回答待確認項目後，提示是否更新規劃報告至新版本以同步確認結果。

---

---

## 問題六：Wave 1 審查員無法寫入審查報告

**現象**：三個 Wave 1 審查員（@style-reviewer、@security-reviewer、@perf-test-reviewer）完成分析後，無法自行將報告寫入 `/tmp/review-*.md`：
- @style-reviewer 與 @perf-test-reviewer 嘗試用 `cat > /tmp/...`（Bash），但 `cat >` 不在白名單，被拒絕。
- @security-reviewer 嘗試 `Skill("write")`，該 skill 不存在，代理卡住。
- 最終由主 agent 讀取每個審查員的任務輸出檔（`.output`），提取報告內容，再用 `Write` 工具手動寫入三份報告。

**補充現象**：@perf-test-reviewer 的輸出檔達 316KB，需用 offset/limit 分段讀取才能找到報告內容，增加額外的 token 成本。

**根本原因**：三個審查員的代理定義（`tools:` 欄位）均未包含 `Write` 工具：
- style-reviewer：`Read, Glob, Grep, Bash, Skill`
- security-reviewer：`Read, Glob, Grep, WebSearch, Bash, Skill`
- perf-test-reviewer：`Read, Glob, Grep, Bash, Skill`

**建議修復**：在三個代理定義的 `tools:` 欄位加入 `Write`，並在 prompt 中加一行說明：「完成審查後，使用 **Write 工具**（非 Bash）將完整報告寫入指定路徑。」

**使用者決定**：記錄問題，暫不修改代理定義。

---

## 問題七：Context Window 滿載需跨 Session

**現象**：上一 session 在 Wave 3 測試完成 + Wave 1 審查啟動後 context 滿載，需開新 session 從摘要重建狀態，有額外的 token 成本。

**建議修復**：在完成大型階段（如 Wave 實作完成、所有審查員啟動後）主動執行 `/compact`，不等到 context 滿才被迫換 session。

---

## 待處理行動項目

- [ ] 研究並設定 `settings.json` 的 Bash 白名單，讓子代理可執行語法檢查等安全操作（問題一）
- [ ] 在 CLAUDE.md 或 @planner 流程中加入：待確認項目回答後，提示同步更新規劃報告（問題五）
- [ ] 在三個 Wave 1 審查員代理定義加入 `Write` 工具，並在 prompt 補充寫入說明（問題六）
