/**
 * 計算機 JavaScript
 *
 * 負責組裝表達式、鍵盤事件、AJAX 呼叫 /api/calculate
 */

(function () {
    'use strict';

    // 目前累積的表達式字串（使用標準運算子，如 * /）
    let expression = '';
    // 是否剛顯示計算結果（下一次輸入數字時清空表達式）
    let justCalculated = false;

    const displayExpr   = document.getElementById('calc-expression');
    const displayResult = document.getElementById('calc-result');

    // 將顯示用字串轉為後端可解析的標準表達式
    function toApiExpression(display) {
        return display
            .replace(/×/g, '*')
            .replace(/÷/g, '/')
            .replace(/−/g, '-');
    }

    // 更新主顯示區
    // #calc-expression：上方小字，顯示歷史表達式（計算後才有內容）
    // #calc-result：下方大字，顯示當前輸入或計算結果
    function updateDisplay() {
        displayResult.textContent = expression === '' ? '0' : expression;
        displayResult.classList.remove('error');
    }

    // 顯示錯誤
    function showError(msg) {
        displayResult.textContent = msg;
        displayResult.classList.add('error');
        displayExpr.textContent = '';
    }

    // 清除全部
    function clearAll() {
        expression      = '';
        justCalculated  = false;
        displayExpr.textContent  = '';
        updateDisplay();
    }

    // 刪除最後一個字元
    function backspace() {
        if (justCalculated) {
            clearAll();
            return;
        }
        expression = expression.slice(0, -1);
        updateDisplay();
    }

    // 取得表達式中最後一個完整數字的起始位置
    // 用於實作 +/- 功能
    function getLastNumberStart() {
        // 從尾端往前找最後一段連續的 數字/小數點 字元（可帶負號）
        const match = expression.match(/([+\-×÷*(]|^)(-?\d*\.?\d*)$/);
        if (!match) return -1;
        // match.index + match[1].length 即最後數字段的起始
        return match.index + match[1].length;
    }

    // 對最後一個數字取負
    function toggleSign() {
        const start = getLastNumberStart();
        if (start < 0) return;

        const numStr = expression.slice(start);
        if (numStr === '' || numStr === '0') return;

        if (numStr.startsWith('-')) {
            expression = expression.slice(0, start) + numStr.slice(1);
        } else {
            expression = expression.slice(0, start) + '-' + numStr;
        }
        updateDisplay();
    }

    // 送出計算請求
    async function calculate() {
        if (expression === '') return;

        const apiExpr = toApiExpression(expression);

        try {
            const response = await fetch('/api/calculate', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ expression: apiExpr })
            });
            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            // 將表達式移到上方小字歷史區，大字顯示計算結果
            displayExpr.textContent = expression + ' =';
            expression = String(data.result);
            justCalculated = true;
            displayResult.classList.remove('error');
            displayResult.textContent = expression;
        } catch (e) {
            showError('連線失敗');
        }
    }

    // 按鍵對應表（display 字元 → 實際 append 的字元）
    const OPERATOR_MAP = {
        '×': '×',
        '÷': '÷',
        '−': '−',
        '+': '+',
        '-': '−',
        '*': '×',
        '/': '÷',
    };

    // 處理按鍵 value
    function handleValue(value) {
        switch (value) {
            case 'AC':
                clearAll();
                return;

            case 'backspace':
                backspace();
                return;

            case '+/-':
                toggleSign();
                return;

            case '=':
                calculate();
                return;

            case '%':
                if (justCalculated) justCalculated = false;
                expression += '%';
                updateDisplay();
                return;

            case '(':
            case ')':
                if (justCalculated) justCalculated = false;
                expression += value;
                updateDisplay();
                return;

            case '.':
                if (justCalculated) { expression = '0'; justCalculated = false; }
                expression += '.';
                updateDisplay();
                return;

            case '×':
            case '÷':
            case '−':
            case '+':
                if (justCalculated) justCalculated = false;
                expression += value;
                updateDisplay();
                return;

            default:
                // 數字 0-9
                if (justCalculated) {
                    // 計算結果後輸入數字，清空重新開始
                    expression     = '';
                    justCalculated = false;
                }
                expression += value;
                updateDisplay();
        }
    }

    // 綁定按鍵點擊
    document.querySelectorAll('.calc-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            handleValue(btn.dataset.value);
        });
    });

    // 鍵盤事件
    document.addEventListener('keydown', function (e) {
        // 防止在 input 元素上觸發（換算頁籤有 input）
        if (document.activeElement && document.activeElement.tagName === 'INPUT') return;

        const key = e.key;

        if (key >= '0' && key <= '9') {
            e.preventDefault();
            handleValue(key);
        } else if (key === '.') {
            e.preventDefault();
            handleValue('.');
        } else if (key === '+') {
            e.preventDefault();
            handleValue('+');
        } else if (key === '-') {
            e.preventDefault();
            handleValue('−');
        } else if (key === '*') {
            e.preventDefault();
            handleValue('×');
        } else if (key === '/') {
            e.preventDefault();
            handleValue('÷');
        } else if (key === '(') {
            e.preventDefault();
            handleValue('(');
        } else if (key === ')') {
            e.preventDefault();
            handleValue(')');
        } else if (key === '%') {
            e.preventDefault();
            handleValue('%');
        } else if (key === 'Enter' || key === '=') {
            e.preventDefault();
            handleValue('=');
        } else if (key === 'Backspace') {
            e.preventDefault();
            handleValue('backspace');
        } else if (key === 'Escape') {
            e.preventDefault();
            handleValue('AC');
        }
    });
}());
