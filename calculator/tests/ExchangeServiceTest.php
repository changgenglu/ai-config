<?php

use Calculator\Services\ExchangeService;
use PHPUnit\Framework\TestCase;

class ExchangeServiceTest extends TestCase
{
    /** @var ExchangeService */
    private $service;

    protected function setUp(): void
    {
        $this->service = new ExchangeService();
    }

    // ---------------------------------------------------------------
    // 正常換算
    // ---------------------------------------------------------------

    public function test_convert_usd_to_twd(): void
    {
        // Given: 1 美元換算為新台幣（靜態匯率 32.5）
        $result = $this->service->convert('USD', 'TWD', 1.0);

        // Then: 32.5 新台幣
        $this->assertSame('32.5', $result['result']);
    }

    public function test_convert_twd_to_usd(): void
    {
        // Given: 1 新台幣換算回美元
        $result = $this->service->convert('TWD', 'USD', 1.0);

        // Then: 1 / 32.5 ≈ 0.0308（四捨五入 4 位）
        $this->assertSame('0.0308', $result['result']);
    }

    public function test_convert_same_currency_returns_original_amount(): void
    {
        // Given: 來源與目標幣別相同
        $result = $this->service->convert('USD', 'USD', 1.0);

        // Then: 直接回傳 1
        $this->assertSame('1', $result['result']);
    }

    public function test_convert_twd_to_jpy_via_usd(): void
    {
        // Given: 100 新台幣換算為日圓（經 USD 中間匯率）
        $result = $this->service->convert('TWD', 'JPY', 100.0);

        // Then: 100 / 32.5 * 149.5 = 460.0（四捨五入 4 位）
        $this->assertSame('460', $result['result']);
    }

    // ---------------------------------------------------------------
    // 大小寫容錯
    // ---------------------------------------------------------------

    public function test_convert_accepts_lowercase_currency_codes(): void
    {
        // Given: 小寫幣別代碼（Service 內部 strtoupper 轉換）
        $result = $this->service->convert('usd', 'twd', 1.0);

        // Then: 仍能正確換算
        $this->assertSame('32.5', $result['result']);
    }

    // ---------------------------------------------------------------
    // 錯誤情境
    // ---------------------------------------------------------------

    public function test_convert_unsupported_from_currency_returns_error(): void
    {
        // Given: 不支援的來源幣別
        $result = $this->service->convert('XYZ', 'USD', 1.0);

        // Then: 應回傳 error 鍵
        $this->assertArrayHasKey('error', $result);
    }

    public function test_convert_unsupported_to_currency_returns_error(): void
    {
        // Given: 不支援的目標幣別
        $result = $this->service->convert('USD', 'XYZ', 1.0);

        // Then: 應回傳 error 鍵
        $this->assertArrayHasKey('error', $result);
    }
}
