/**
 * 單位換算 JavaScript
 *
 * 負責填充單位選項、即時 AJAX 呼叫 /api/convert
 */

(function () {
    'use strict';

    const UNITS = {
        length: {
            label: '長度',
            units: {
                km:    '公里 (km)',
                mile:  '英里 (mile)',
                m:     '公尺 (m)',
                cm:    '公分 (cm)',
                mm:    '毫米 (mm)',
                ft:    '英尺 (ft)',
                inch:  '英吋 (in)'
            }
        },
        weight: {
            label: '重量',
            units: {
                kg:        '公斤 (kg)',
                lb:        '磅 (lb)',
                g:         '公克 (g)',
                mg:        '毫克 (mg)',
                ton:       '公噸 (ton)',
                short_ton: '英噸 (short ton)'
            }
        },
        temperature: {
            label: '溫度',
            units: {
                C: '攝氏 (°C)',
                F: '華氏 (°F)',
                K: '克耳文 (K)'
            }
        }
    };

    const typeSelect = document.getElementById('conv-type');
    const fromSelect = document.getElementById('conv-from');
    const toSelect   = document.getElementById('conv-to');
    const valueInput = document.getElementById('conv-value');
    const resultDiv  = document.getElementById('conv-result');
    const resultBox  = resultDiv ? resultDiv.closest('.result-display') : null;

    // 清空並重新填充 select 元素
    function populateSelect(sel, unitMap, defaultIndex) {
        // 安全清空
        while (sel.firstChild) {
            sel.removeChild(sel.firstChild);
        }
        Object.keys(unitMap).forEach(function (key, idx) {
            const opt       = document.createElement('option');
            opt.value       = key;
            opt.textContent = unitMap[key];
            if (idx === defaultIndex) {
                opt.selected = true;
            }
            sel.appendChild(opt);
        });
    }

    // 填充 from/to 選單
    function populateUnitSelects(type) {
        const unitMap = UNITS[type] ? UNITS[type].units : {};
        const keys    = Object.keys(unitMap);

        populateSelect(fromSelect, unitMap, 0);
        // to 預設選第 1 個（避免與 from 相同）
        populateSelect(toSelect,   unitMap, keys.length > 1 ? 1 : 0);
    }

    // 顯示結果
    function showResult(text, isError) {
        resultDiv.textContent = text;
        if (resultBox) {
            resultBox.classList.toggle('error',   !!isError);
            resultBox.classList.toggle('success', !isError && text !== '');
        }
    }

    // 送出換算請求
    async function doConvert() {
        const type  = typeSelect.value;
        const from  = fromSelect.value;
        const to    = toSelect.value;
        const raw   = valueInput.value;

        if (raw === '' || isNaN(parseFloat(raw))) {
            showResult('', false);
            return;
        }

        try {
            const response = await fetch('/api/convert', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    type:  type,
                    from:  from,
                    to:    to,
                    value: parseFloat(raw)
                })
            });
            const data = await response.json();

            if (data.error) {
                showResult(data.error, true);
                return;
            }

            const unitDef   = UNITS[type] ? UNITS[type].units : {};
            const fromLabel = unitDef[from] || from;
            const toLabel   = unitDef[to]   || to;
            showResult(raw + ' ' + fromLabel + ' = ' + data.result + ' ' + toLabel, false);
        } catch (e) {
            showResult('連線失敗', true);
        }
    }

    // 初始化
    function init() {
        // 依當前選中的 type 填充 from/to
        const initialType = typeSelect ? typeSelect.value : 'length';
        populateUnitSelects(initialType);

        // 事件綁定
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                populateUnitSelects(typeSelect.value);
                doConvert();
            });
        }

        if (fromSelect) fromSelect.addEventListener('change', doConvert);
        if (toSelect)   toSelect.addEventListener('change',   doConvert);
        if (valueInput) valueInput.addEventListener('input',  doConvert);

        // 換算按鈕
        const btn = document.getElementById('conv-btn');
        if (btn) btn.addEventListener('click', doConvert);

        // 初始計算
        doConvert();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
