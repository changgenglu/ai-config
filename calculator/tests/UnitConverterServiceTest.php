<?php

use Calculator\Services\UnitConverterService;
use PHPUnit\Framework\TestCase;

class UnitConverterServiceTest extends TestCase
{
    /** @var UnitConverterService */
    private $service;

    protected function setUp(): void
    {
        $this->service = new UnitConverterService();
    }

    // ---------------------------------------------------------------
    // 長度換算
    // ---------------------------------------------------------------

    public function test_convert_km_to_mile(): void
    {
        // Given: 1 公里換算為英里
        $result = $this->service->convert('length', 'km', 'mile', 1.0);

        // Then: 約 0.621371（1000m / 1609.344m）
        $this->assertArrayHasKey('result', $result);
        $this->assertEqualsWithDelta(0.621371, (float) $result['result'], 0.000001);
    }

    public function test_convert_mile_to_km(): void
    {
        // Given: 1 英里換算為公里
        $result = $this->service->convert('length', 'mile', 'km', 1.0);

        // Then: 約 1.609344
        $this->assertArrayHasKey('result', $result);
        $this->assertEqualsWithDelta(1.609344, (float) $result['result'], 0.000001);
    }

    public function test_convert_m_to_cm(): void
    {
        // Given: 1 公尺換算為公分
        $result = $this->service->convert('length', 'm', 'cm', 1.0);

        // Then: 100 公分
        $this->assertSame('100', $result['result']);
    }

    // ---------------------------------------------------------------
    // 重量換算
    // ---------------------------------------------------------------

    public function test_convert_kg_to_lb(): void
    {
        // Given: 1 公斤換算為磅
        $result = $this->service->convert('weight', 'kg', 'lb', 1.0);

        // Then: 約 2.204624（1000g / 453.592g）
        $this->assertArrayHasKey('result', $result);
        $this->assertEqualsWithDelta(2.204624, (float) $result['result'], 0.000001);
    }

    public function test_convert_lb_to_g(): void
    {
        // Given: 1 磅換算為公克
        $result = $this->service->convert('weight', 'lb', 'g', 1.0);

        // Then: 453.592 公克
        $this->assertSame('453.592', $result['result']);
    }

    // ---------------------------------------------------------------
    // 溫度換算
    // ---------------------------------------------------------------

    public function test_convert_celsius_to_fahrenheit(): void
    {
        // Given: 100 攝氏度換算為華氏
        $result = $this->service->convert('temperature', 'C', 'F', 100.0);

        // Then: 212 華氏度（100 * 9/5 + 32）
        $this->assertSame('212', $result['result']);
    }

    public function test_convert_celsius_to_kelvin(): void
    {
        // Given: 0 攝氏度換算為克耳文
        $result = $this->service->convert('temperature', 'C', 'K', 0.0);

        // Then: 273.15 克耳文
        $this->assertSame('273.15', $result['result']);
    }

    public function test_convert_fahrenheit_to_celsius(): void
    {
        // Given: 32 華氏度換算為攝氏
        $result = $this->service->convert('temperature', 'F', 'C', 32.0);

        // Then: 0 攝氏度（(32 - 32) * 5/9）
        $this->assertSame('0', $result['result']);
    }

    // ---------------------------------------------------------------
    // 相同單位
    // ---------------------------------------------------------------

    public function test_convert_same_unit_returns_original_value(): void
    {
        // Given: 來源與目標單位相同
        $result = $this->service->convert('length', 'km', 'km', 5.0);

        // Then: 直接回傳原值
        $this->assertSame('5', $result['result']);
    }

    // ---------------------------------------------------------------
    // 錯誤情境
    // ---------------------------------------------------------------

    public function test_convert_unsupported_type_returns_error(): void
    {
        // Given: 不支援的換算類型
        $result = $this->service->convert('volume', 'L', 'mL', 1.0);

        // Then: 應回傳 error 鍵
        $this->assertArrayHasKey('error', $result);
    }

    public function test_convert_unsupported_from_unit_returns_error(): void
    {
        // Given: 不支援的來源單位
        $result = $this->service->convert('length', 'furlong', 'km', 1.0);

        // Then: 應回傳 error 鍵
        $this->assertArrayHasKey('error', $result);
    }

    public function test_convert_unsupported_to_unit_returns_error(): void
    {
        // Given: 不支援的目標單位
        $result = $this->service->convert('length', 'km', 'furlong', 1.0);

        // Then: 應回傳 error 鍵
        $this->assertArrayHasKey('error', $result);
    }
}
