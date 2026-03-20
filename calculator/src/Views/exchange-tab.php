<div class="exchange-container">
    <div class="exchange-form">
        <!-- 來源幣別與金額 -->
        <div class="form-row">
            <div class="form-group">
                <label for="exc-from">源幣別</label>
                <select id="exc-from" class="exc-select">
                    <option value="TWD" selected>台幣 (TWD)</option>
                    <option value="USD">美元 (USD)</option>
                    <option value="EUR">歐元 (EUR)</option>
                    <option value="JPY">日圓 (JPY)</option>
                    <option value="GBP">英鎊 (GBP)</option>
                    <option value="CNY">人民幣 (CNY)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="exc-amount">金額</label>
                <input type="number" id="exc-amount" class="exc-input" placeholder="輸入金額" value="1">
            </div>
        </div>

        <!-- 反轉按鈕與目標幣別 -->
        <div class="form-row">
            <button id="exc-swap" class="exc-swap-btn" title="反轉幣別">⇄</button>

            <div class="form-group">
                <label for="exc-to">目標幣別</label>
                <select id="exc-to" class="exc-select">
                    <option value="TWD">台幣 (TWD)</option>
                    <option value="USD" selected>美元 (USD)</option>
                    <option value="EUR">歐元 (EUR)</option>
                    <option value="JPY">日圓 (JPY)</option>
                    <option value="GBP">英鎊 (GBP)</option>
                    <option value="CNY">人民幣 (CNY)</option>
                </select>
            </div>
        </div>

        <!-- 轉換按鈕 -->
        <button id="exc-btn" class="exc-btn">換算</button>
    </div>

    <!-- 結果顯示 -->
    <div class="result-display">
        <div id="exc-result" class="exc-result"></div>
    </div>
</div>
