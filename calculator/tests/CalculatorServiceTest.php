<?php

use Calculator\Services\CalculatorService;
use PHPUnit\Framework\TestCase;

class CalculatorServiceTest extends TestCase
{
    /** @var CalculatorService */
    private $service;

    protected function setUp(): void
    {
        $this->service = new CalculatorService();
    }

    // ---------------------------------------------------------------
    // 正常計算
    // ---------------------------------------------------------------

    public function test_evaluate_operator_precedence_returns_correct_result(): void
    {
        // Given: 運算式 3 + 5 * 2，乘法優先
        $result = $this->service->evaluate('3 + 5 * 2');

        // Then: 應得 13
        $this->assertSame('13', $result['result']);
    }

    public function test_evaluate_parentheses_override_precedence(): void
    {
        // Given: 括號強制先加後乘
        $result = $this->service->evaluate('(3 + 5) * 2');

        // Then: 應得 16
        $this->assertSame('16', $result['result']);
    }

    public function test_evaluate_division_returns_decimal(): void
    {
        // Given: 10 / 4 無法整除
        $result = $this->service->evaluate('10 / 4');

        // Then: 應得 2.5
        $this->assertSame('2.5', $result['result']);
    }

    public function test_evaluate_negative_number(): void
    {
        // Given: 開頭為負數的表達式
        $result = $this->service->evaluate('-5 + 3');

        // Then: 應得 -2
        $this->assertSame('-2', $result['result']);
    }

    public function test_evaluate_nested_parentheses(): void
    {
        // Given: 巢狀括號表達式
        $result = $this->service->evaluate('2 * (3 + (4 - 1))');

        // Then: 應得 12
        $this->assertSame('12', $result['result']);
    }

    // ---------------------------------------------------------------
    // 百分比（手機計算機慣例）
    // ---------------------------------------------------------------

    public function test_evaluate_percent_after_addition_uses_left_operand_as_base(): void
    {
        // Given: a + b% 應展開為 a + (a * b / 100)
        $result = $this->service->evaluate('100 + 10%');

        // Then: 100 + (100 * 10 / 100) = 110
        $this->assertSame('110', $result['result']);
    }

    public function test_evaluate_percent_after_multiplication_divides_by_100(): void
    {
        // Given: a * b% 應展開為 a * (b / 100)
        $result = $this->service->evaluate('200 * 50%');

        // Then: 200 * 0.5 = 100
        $this->assertSame('100', $result['result']);
    }

    public function test_evaluate_standalone_percent_divides_by_100(): void
    {
        // Given: 無前置運算子的百分比
        $result = $this->service->evaluate('200%');

        // Then: 200 / 100 = 2
        $this->assertSame('2', $result['result']);
    }

    public function test_evaluate_standalone_percent_with_decimal_result(): void
    {
        // Given: 結果為小數的單獨百分比
        $result = $this->service->evaluate('50%');

        // Then: 50 / 100 = 0.5
        $this->assertSame('0.5', $result['result']);
    }

    // ---------------------------------------------------------------
    // 錯誤情境
    // ---------------------------------------------------------------

    public function test_evaluate_division_by_zero_returns_error(): void
    {
        // Given: 除以零
        $result = $this->service->evaluate('100 / 0');

        // Then: 應回傳 error 鍵
        $this->assertArrayHasKey('error', $result);
    }

    public function test_evaluate_empty_string_returns_error(): void
    {
        // Given: 空字串輸入
        $result = $this->service->evaluate('');

        // Then: 應回傳 error 鍵
        $this->assertArrayHasKey('error', $result);
    }
}
