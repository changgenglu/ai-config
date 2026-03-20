<?php

namespace Calculator\Services;

/**
 * 匯率換算服務
 *
 * 以 USD 為基準進行靜態匯率換算：
 * 先將來源幣別換回 USD，再換算至目標幣別。
 */
class ExchangeService
{
    /** @var array<string, float> 各幣別對 USD 的匯率 */
    private const RATES = [
        'USD' => 1.0,
        'TWD' => 32.5,
        'EUR' => 0.92,
        'JPY' => 149.5,
        'GBP' => 0.79,
        'CNY' => 7.24,
    ];

    /**
     * 轉換匯率
     *
     * @param string $from   源幣別
     * @param string $to     目標幣別
     * @param float  $amount 金額
     * @return array{result: string}|array{error: string}
     */
    public function convert(string $from, string $to, float $amount): array
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);

        if (!isset(self::RATES[$from])) {
            return ['error' => '不支援的幣別：' . $from];
        }

        if (!isset(self::RATES[$to])) {
            return ['error' => '不支援的幣別：' . $to];
        }

        // 來源 → USD → 目標
        $usd    = $amount / self::RATES[$from];
        $result = $usd * self::RATES[$to];

        return ['result' => (string) round($result, 4)];
    }
}
