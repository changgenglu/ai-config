## 🔧 Postman Pre-request Script 中的 Hash 計算
**日期：** 2026-03-26
**技能：** postman-mcp-integrator
**情境：** 在 Postman 請求前自動計算 MD5 Hash 時，遇到 `CryptoJS` 棄用警告與全域 `crypto` 物件語法不相容的問題。
**解法：**
- **不要使用**全域 `CryptoJS`（會觸發棄用警告）。
- **不要使用**全域 `crypto.createHash`（這是 Node.js 語法，Postman 的全域 `crypto` 遵循 Web Crypto API）。
- **正確做法**：使用 `const CryptoJS = require('crypto-js');` 引入函式庫，維持同步執行且符合現代規範。
**範例腳本：**
```javascript
const CryptoJS = require('crypto-js');
const secretKey = pm.collectionVariables.get("SECRET_KEY") || "";
const params = pm.request.body.urlencoded.all();

// 排除 hash 欄位後按 key 字母排序
const hashParams = {};
params.forEach(p => { if (p.key !== "hash") hashParams[p.key] = p.value; });
const sortedKeys = Object.keys(hashParams).sort();

// 串接字串並計算 MD5
const queryString = sortedKeys.map(key => `${key}=${hashParams[key]}`).join("&");
const rawString = queryString + secretKey;
const hash = CryptoJS.MD5(rawString).toString();

// 設定變數供請求使用
pm.variables.set("hash", hash);
```
**關鍵檔案/路徑：**
- Postman Collection Pre-request Scripts
**keywords：** postman, crypto-js, md5, hash, pre-request-script
