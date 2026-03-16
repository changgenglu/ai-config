# Laravel Pint 格式化

對與 master 分支有差異的 PHP 檔案執行 Laravel Pint 格式化。

## 變更檔案清單

以下是與 master 分支有差異的 PHP 檔案：

```bash
git diff --name-only --diff-filter=ACMR $(git merge-base master HEAD) HEAD -- '*.php'
```

## 執行步驟

1. **解析參數**：從使用者輸入取得容器名稱與專案名稱
2. **確認變更檔案**：檢視上方的檔案清單
3. **執行 Pint**：在指定容器內執行 pint 指令

## 執行指令格式

```bash
podman exec <容器名稱> sh -c "cd /var/www/html/<專案名稱> && ./vendor/bin/pint --config=pint.json <檔案列表>"
```

**範例**（容器 `backend838`，專案 `eagle`，有 2 個檔案）：

```bash
podman exec backend838 sh -c "cd /var/www/html/eagle && ./vendor/bin/pint --config=pint.json app/Services/UserService.php app/Http/Controllers/UserController.php"
```

## 輸出要求

1. 確認容器名稱與專案名稱
2. 列出所有要格式化的 PHP 檔案
3. 執行 pint 指令
4. 回報執行結果

## 錯誤處理

- 若使用者未提供容器名稱或專案名稱，回覆：「請提供容器名稱與專案名稱，例如：`/pint backend838 eagle`」
- 若無任何 PHP 檔案變更，回覆：「✅ 無 PHP 檔案變更，無需執行 Pint。」
