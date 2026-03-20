<?php

namespace Calculator\Services;

/**
 * 單位換算服務
 *
 * 以中間基準單位進行兩步換算：來源 → 基準 → 目標。
 * 溫度因非線性需特殊處理。
 */
class UnitConverterService
{
    /** @var array<string, array<string, float>> 各類別單位對應基準的換算係數 */
    private const FACTORS = [
        'length' => [
            'km'   => 1000.0,
            'mile' => 1609.344,
            'm'    => 1.0,
            'cm'   => 0.01,
            'mm'   => 0.001,
            'ft'   => 0.3048,
            'in'   => 0.0254,
        ],
        'weight' => [
            'kg'        => 1000.0,
            'lb'        => 453.592,
            'g'         => 1.0,
            'mg'        => 0.001,
            'ton'       => 1000000.0,
            'short_ton' => 907185.0,
        ],
    ];

    /**
     * 轉換單位
     *
     * @param string $type  轉換類別 (length, weight, temperature)
     * @param string $from  源單位
     * @param string $to    目標單位
     * @param float  $value 數值
     * @return array{result: string}|array{error: string}
     */
    public function convert(string $type, string $from, string $to, float $value): array
    {
        if ($from === $to) {
            return ['result' => (string) round($value, 6)];
        }

        if ($type === 'temperature') {
            return $this->convertTemperature($from, $to, $value);
        }

        if (!isset(self::FACTORS[$type])) {
            return ['error' => '不支援的換算類型'];
        }

        $factors = self::FACTORS[$type];

        if (!isset($factors[$from])) {
            return ['error' => '不支援的來源單位：' . $from];
        }

        if (!isset($factors[$to])) {
            return ['error' => '不支援的目標單位：' . $to];
        }

        // 來源 → 基準 → 目標
        $baseValue = $value * $factors[$from];
        $result    = $baseValue / $factors[$to];

        return ['result' => (string) round($result, 6)];
    }

    /**
     * 溫度換算（非線性，需特殊處理）
     *
     * @return array{result: string}|array{error: string}
     */
    private function convertTemperature(string $from, string $to, float $value): array
    {
        $key = $from . '_' . $to;

        switch ($key) {
            case 'C_F': $result = $value * 9 / 5 + 32; break;
            case 'C_K': $result = $value + 273.15; break;
            case 'F_C': $result = ($value - 32) * 5 / 9; break;
            case 'F_K': $result = ($value - 32) * 5 / 9 + 273.15; break;
            case 'K_C': $result = $value - 273.15; break;
            case 'K_F': $result = ($value - 273.15) * 9 / 5 + 32; break;
            default:    $result = null;
        }

        if ($result === null) {
            return ['error' => '不支援的溫度換算：' . $from . ' → ' . $to];
        }

        return ['result' => (string) round($result, 6)];
    }
}
