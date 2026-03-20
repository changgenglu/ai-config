<div class="converter-container">
    <div class="converter-form">
        <!-- 轉換類別選擇 -->
        <div class="form-group">
            <label for="conv-type">轉換類別</label>
            <select id="conv-type" class="conv-select">
                <option value="length">長度 (Length)</option>
                <option value="weight">重量 (Weight)</option>
                <option value="temperature">溫度 (Temperature)</option>
            </select>
        </div>

        <!-- 來源單位與數值 -->
        <div class="form-row">
            <div class="form-group">
                <label for="conv-from">來源單位</label>
                <select id="conv-from" class="conv-select">
                    <option value="m">公尺 (m)</option>
                    <option value="cm">公分 (cm)</option>
                    <option value="mm">公厘 (mm)</option>
                    <option value="km">公里 (km)</option>
                    <option value="inch">英吋 (in)</option>
                    <option value="foot">英呎 (ft)</option>
                    <option value="yard">碼 (yd)</option>
                    <option value="mile">英里 (mi)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="conv-value">數值</label>
                <input type="number" id="conv-value" class="conv-input" placeholder="輸入數值" value="1">
            </div>
        </div>

        <!-- 目標單位 -->
        <div class="form-group">
            <label for="conv-to">目標單位</label>
            <select id="conv-to" class="conv-select">
                <option value="m">公尺 (m)</option>
                <option value="cm">公分 (cm)</option>
                <option value="mm">公厘 (mm)</option>
                <option value="km">公里 (km)</option>
                <option value="inch">英吋 (in)</option>
                <option value="foot">英呎 (ft)</option>
                <option value="yard">碼 (yd)</option>
                <option value="mile">英里 (mi)</option>
            </select>
        </div>

        <!-- 轉換按鈕 -->
        <button id="conv-btn" class="conv-btn">轉換</button>
    </div>

    <!-- 結果顯示 -->
    <div class="result-display">
        <div id="conv-result" class="conv-result"></div>
    </div>
</div>
