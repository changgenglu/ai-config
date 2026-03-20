<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP 計算機</title>
    <link rel="stylesheet" href="/public/css/app.css">
</head>
<body>
    <div class="container">
        <h1>PHP 計算機</h1>

        <!-- 頁籤導航 -->
        <div class="tab-nav">
            <button class="tab-btn active" data-tab="calculator">計算機</button>
            <button class="tab-btn" data-tab="converter">單位換算</button>
            <button class="tab-btn" data-tab="exchange">匯率換算</button>
        </div>

        <!-- 計算機頁籤 -->
        <div class="tab-content active" id="calculator">
            <?php include ROOT_DIR . '/src/Views/calculator-tab.php'; ?>
        </div>

        <!-- 單位換算頁籤 -->
        <div class="tab-content" id="converter">
            <?php include ROOT_DIR . '/src/Views/converter-tab.php'; ?>
        </div>

        <!-- 匯率換算頁籤 -->
        <div class="tab-content" id="exchange">
            <?php include ROOT_DIR . '/src/Views/exchange-tab.php'; ?>
        </div>
    </div>

    <!-- JavaScript 檔案 -->
    <script src="/public/js/calculator.js"></script>
    <script src="/public/js/converter.js"></script>
    <script src="/public/js/exchange.js"></script>

    <script>
        document.querySelectorAll('.tab-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.tab-btn').forEach(function (b) {
                    b.classList.remove('active');
                });
                document.querySelectorAll('.tab-content').forEach(function (p) {
                    p.classList.remove('active');
                });
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });
    </script>
</body>
</html>
