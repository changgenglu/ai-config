/**
 * 匯率換算 JavaScript
 *
 * 負責幣別選項、swap 功能、即時 AJAX 呼叫 /api/exchange
 */

(function () {
    'use strict';

    const CURRENCIES = {
        TWD: '台幣 (TWD)',
        USD: '美元 (USD)',
        EUR: '歐元 (EUR)',
        JPY: '日圓 (JPY)',
        GBP: '英鎊 (GBP)',
        CNY: '人民幣 (CNY)'
    };

    const fromSelect   = document.getElementById('exc-from');
    const toSelect     = document.getElementById('exc-to');
    const amountInput  = document.getElementById('exc-amount');
    const swapBtn      = document.getElementById('exc-swap');
    const resultDiv    = document.getElementById('exc-result');
    const resultBox    = resultDiv ? resultDiv.closest('.result-display') : null;

    // 填充幣別選單
    function populateCurrencySelect(sel, selectedValue) {
        // 安全清空
        while (sel.firstChild) {
            sel.removeChild(sel.firstChild);
        }
        Object.keys(CURRENCIES).forEach(function (code) {
            const opt       = document.createElement('option');
            opt.value       = code;
            opt.textContent = CURRENCIES[code];
            if (code === selectedValue) {
                opt.selected = true;
            }
            sel.appendChild(opt);
        });
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
    async function doExchange() {
        const from   = fromSelect.value;
        const to     = toSelect.value;
        const raw    = amountInput.value;
        const amount = parseFloat(raw);

        if (raw === '' || isNaN(amount)) {
            showResult('', false);
            return;
        }

        try {
            const response = await fetch('/api/exchange', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ from: from, to: to, amount: amount })
            });
            const data = await response.json();

            if (data.error) {
                showResult(data.error, true);
                return;
            }

            const fromLabel = CURRENCIES[from] || from;
            const toLabel   = CURRENCIES[to]   || to;
            showResult(
                raw + ' ' + fromLabel + ' = ' + data.result + ' ' + toLabel,
                false
            );
        } catch (e) {
            showResult('連線失敗', true);
        }
    }

    // 初始化
    function init() {
        // 填充幣別選單（保留 HTML 骨架的預設值）
        const defaultFrom = fromSelect ? fromSelect.value || 'TWD' : 'TWD';
        const defaultTo   = toSelect   ? toSelect.value   || 'USD' : 'USD';
        populateCurrencySelect(fromSelect, defaultFrom);
        populateCurrencySelect(toSelect,   defaultTo);

        // 事件綁定
        if (fromSelect)  fromSelect.addEventListener('change', doExchange);
        if (toSelect)    toSelect.addEventListener('change',   doExchange);
        if (amountInput) amountInput.addEventListener('input', doExchange);

        // Swap 按鈕
        if (swapBtn) {
            swapBtn.addEventListener('click', function () {
                const prevFrom = fromSelect.value;
                const prevTo   = toSelect.value;
                fromSelect.value = prevTo;
                toSelect.value   = prevFrom;
                doExchange();
            });
        }

        // 換算按鈕
        const btn = document.getElementById('exc-btn');
        if (btn) btn.addEventListener('click', doExchange);

        // 初始計算
        doExchange();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
