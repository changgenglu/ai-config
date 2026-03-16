/**
 * k6 負載測試腳本模板
 * 
 * 使用方式:
 *   k6 run k6_load_test_template.js
 *   k6 run --vus 50 --duration 5m k6_load_test_template.js
 *   k6 run --out json=results.json k6_load_test_template.js
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// ============================================
// 自定義指標
// ============================================
const errorRate = new Rate('errors');
const loginDuration = new Trend('login_duration');
const apiDuration = new Trend('api_duration');

// ============================================
// 測試設定
// ============================================
export const options = {
    // 階段式負載測試
    stages: [
        { duration: '1m', target: 10 },   // 暖機: 1 分鐘爬升到 10 用戶
        { duration: '3m', target: 50 },   // 增壓: 3 分鐘爬升到 50 用戶
        { duration: '5m', target: 50 },   // 穩定: 維持 50 用戶 5 分鐘
        { duration: '2m', target: 100 },  // 壓力: 增加到 100 用戶
        { duration: '3m', target: 100 },  // 尖峰: 維持 100 用戶 3 分鐘
        { duration: '2m', target: 0 },    // 降溫: 逐步減少到 0
    ],

    // 效能閾值 (SLO)
    thresholds: {
        // 整體請求
        http_req_duration: ['p(95)<500', 'p(99)<1000'],  // 95% < 500ms, 99% < 1s
        http_req_failed: ['rate<0.01'],                  // 錯誤率 < 1%
        
        // 自定義指標
        errors: ['rate<0.05'],                           // 總錯誤率 < 5%
        login_duration: ['p(95)<1000'],                  // 登入 95% < 1s
        api_duration: ['p(95)<300'],                     // API 95% < 300ms
    },

    // 其他設定
    noConnectionReuse: false,
    userAgent: 'k6-load-test/1.0',
};

// ============================================
// 環境設定
// ============================================
const BASE_URL = __ENV.BASE_URL || 'https://api.example.com';
const API_TOKEN = __ENV.API_TOKEN || '';

const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${API_TOKEN}`,
};

// ============================================
// 測試資料
// ============================================
const testUsers = [
    { email: 'test1@example.com', password: 'password123' },
    { email: 'test2@example.com', password: 'password123' },
    { email: 'test3@example.com', password: 'password123' },
];

// ============================================
// 工具函數
// ============================================
function randomItem(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
}

function checkResponse(res, expectedStatus = 200) {
    const success = check(res, {
        [`status is ${expectedStatus}`]: (r) => r.status === expectedStatus,
        'response time < 500ms': (r) => r.timings.duration < 500,
        'response has body': (r) => r.body && r.body.length > 0,
    });
    
    errorRate.add(!success);
    return success;
}

// ============================================
// 測試場景
// ============================================
export default function () {
    // 場景 1: 健康檢查
    group('Health Check', function () {
        const res = http.get(`${BASE_URL}/health`);
        checkResponse(res);
    });

    sleep(1);

    // 場景 2: 登入流程
    group('Login Flow', function () {
        const user = randomItem(testUsers);
        const startTime = Date.now();
        
        const res = http.post(
            `${BASE_URL}/api/login`,
            JSON.stringify({
                email: user.email,
                password: user.password,
            }),
            { headers }
        );
        
        loginDuration.add(Date.now() - startTime);
        checkResponse(res);
    });

    sleep(2);

    // 場景 3: API 請求
    group('API Requests', function () {
        // 列表查詢
        const listRes = http.get(`${BASE_URL}/api/products?page=1&per_page=20`, { headers });
        apiDuration.add(listRes.timings.duration);
        checkResponse(listRes);

        sleep(0.5);

        // 詳情查詢
        const detailRes = http.get(`${BASE_URL}/api/products?id=1`, { headers });
        apiDuration.add(detailRes.timings.duration);
        checkResponse(detailRes);
    });

    sleep(1);

    // 場景 4: 寫入操作
    group('Write Operations', function () {
        const res = http.post(
            `${BASE_URL}/api/orders`,
            JSON.stringify({
                product_id: Math.floor(Math.random() * 100) + 1,
                quantity: Math.floor(Math.random() * 5) + 1,
            }),
            { headers }
        );
        
        checkResponse(res, 201);
    });

    // 模擬用戶思考時間
    sleep(Math.random() * 3 + 1);
}

// ============================================
// 生命週期 Hooks
// ============================================
export function setup() {
    console.log(`Starting load test against: ${BASE_URL}`);
    
    // 驗證目標可達
    const res = http.get(`${BASE_URL}/health`);
    if (res.status !== 200) {
        throw new Error(`Target not reachable: ${res.status}`);
    }
    
    return { startTime: Date.now() };
}

export function teardown(data) {
    const duration = (Date.now() - data.startTime) / 1000;
    console.log(`Test completed in ${duration.toFixed(2)} seconds`);
}

// ============================================
// 測試報告處理
// ============================================
export function handleSummary(data) {
    return {
        'stdout': textSummary(data, { indent: ' ', enableColors: true }),
        'summary.json': JSON.stringify(data),
        'summary.html': htmlReport(data),
    };
}

function textSummary(data, options) {
    // k6 內建的 textSummary 函數
    return JSON.stringify(data, null, 2);
}

function htmlReport(data) {
    return `
<!DOCTYPE html>
<html>
<head>
    <title>k6 Load Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .pass { color: green; }
        .fail { color: red; }
    </style>
</head>
<body>
    <h1>k6 Load Test Report</h1>
    <h2>Thresholds</h2>
    <table>
        <tr><th>Metric</th><th>Status</th></tr>
        ${Object.entries(data.thresholds || {}).map(([name, threshold]) => 
            `<tr><td>${name}</td><td class="${threshold.ok ? 'pass' : 'fail'}">${threshold.ok ? 'PASS' : 'FAIL'}</td></tr>`
        ).join('')}
    </table>
</body>
</html>
    `;
}
