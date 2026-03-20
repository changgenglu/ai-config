<?php

namespace Calculator\Services;

/**
 * 計算機服務
 *
 * 使用 Shunting-yard 演算法（中綴→後綴）計算數學表達式。
 * 百分比遵循手機計算機慣例：二元運算後的 % 取前運算數的百分比。
 */
class CalculatorService
{
    /** @var array<string, int> 運算子優先順序 */
    private const PRECEDENCE = [
        '+' => 1,
        '-' => 1,
        '*' => 2,
        '/' => 2,
    ];

    /**
     * 計算表達式
     *
     * @param string $expression 數學表達式
     * @return array{result: string}|array{error: string}
     */
    public function evaluate(string $expression): array
    {
        $expression = trim($expression);
        if ($expression === '') {
            return ['error' => '無效的表達式'];
        }

        try {
            $tokens = $this->tokenize($expression);
            $tokens = $this->resolvePercent($tokens);
            $postfix = $this->toPostfix($tokens);
            $result  = $this->evalPostfix($postfix);
            return ['result' => (string) round($result, 10)];
        } catch (\DivisionByZeroError $e) {
            return ['error' => '除以零錯誤'];
        } catch (\RuntimeException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 將表達式拆分為 token 陣列
     *
     * @return list<string>
     */
    private function tokenize(string $expression): array
    {
        // 匹配數字（含小數）、運算子、括號、百分比符號
        preg_match_all('/(\d+\.?\d*|\.\d+|[+\-*\/\(\)%])/', $expression, $matches);
        $tokens = $matches[0];

        if (empty($tokens)) {
            throw new \RuntimeException('無效的表達式');
        }

        // 處理一元負號：將 [-] 緊接著左括號、另一個運算子或出現在開頭時，轉為 [0, -]
        $result = [];
        foreach ($tokens as $i => $token) {
            if ($token === '-') {
                $prev = $result[count($result) - 1] ?? null;
                $isUnary = ($prev === null || $prev === '(' || isset(self::PRECEDENCE[$prev]));
                if ($isUnary) {
                    // 以 (0 - ...) 展開
                    $result[] = '0';
                }
            }
            $result[] = $token;
        }

        return $result;
    }

    /**
     * 將百分比 token 展開為對應的數值
     *
     * 手機計算機慣例：
     * - `a + b%` → `a + (a * b / 100)`
     * - `a - b%` → `a - (a * b / 100)`
     * - `a * b%` → `a * (b / 100)`
     * - `a / b%` → `a / (b / 100)`
     * - 單獨 `b%`（無前置二元運算）→ `b / 100`
     *
     * @param list<string> $tokens
     * @return list<string>
     */
    private function resolvePercent(array $tokens): array
    {
        $result = [];
        $n = count($tokens);

        for ($i = 0; $i < $n; $i++) {
            if ($tokens[$i] !== '%') {
                $result[] = $tokens[$i];
                continue;
            }

            // 找出緊接在 % 前的數字 token
            if (empty($result) || !is_numeric($result[count($result) - 1])) {
                throw new \RuntimeException('無效的百分比用法');
            }

            $b = array_pop($result);

            // 向左找最近的二元運算子（跳過括號與數字）
            // 取 $result 中最後一個在頂層的運算子
            $op = $this->findLastBinaryOp($result);

            if ($op === '+' || $op === '-') {
                // 找出 % 前的「左運算數」：$result 中 $op 左側的完整表達式
                // 為了簡化，將整個 $result 重新計算其值作為基準
                // 實際上只需知道緊鄰運算子左側的數字
                $leftValue = $this->extractLeftOperand($result);
                // 展開為 (leftValue * b / 100)
                $result[] = '(';
                $result[] = (string) $leftValue;
                $result[] = '*';
                $result[] = $b;
                $result[] = '/';
                $result[] = '100';
                $result[] = ')';
            } elseif ($op === '*' || $op === '/') {
                // a * b% → a * (b / 100)
                $result[] = '(';
                $result[] = $b;
                $result[] = '/';
                $result[] = '100';
                $result[] = ')';
            } else {
                // 無前置運算子，直接 ÷ 100
                $result[] = '(';
                $result[] = $b;
                $result[] = '/';
                $result[] = '100';
                $result[] = ')';
            }
        }

        return $result;
    }

    /**
     * 在 token 陣列中找最後一個頂層二元運算子
     *
     * @param list<string> $tokens
     */
    private function findLastBinaryOp(array $tokens): ?string
    {
        $depth = 0;
        $lastOp = null;
        foreach ($tokens as $token) {
            if ($token === '(') {
                $depth++;
            } elseif ($token === ')') {
                $depth--;
            } elseif ($depth === 0 && isset(self::PRECEDENCE[$token])) {
                $lastOp = $token;
            }
        }
        return $lastOp;
    }

    /**
     * 擷取 token 陣列中最後一個頂層二元運算子左側的數值
     *
     * 若找不到運算子則直接計算整個 token 陣列的值。
     *
     * @param list<string> $tokens
     */
    private function extractLeftOperand(array $tokens): float
    {
        // 找到最後一個頂層二元運算子的索引
        $depth = 0;
        $lastOpIndex = -1;
        foreach ($tokens as $i => $token) {
            if ($token === '(') {
                $depth++;
            } elseif ($token === ')') {
                $depth--;
            } elseif ($depth === 0 && isset(self::PRECEDENCE[$token])) {
                $lastOpIndex = $i;
            }
        }

        if ($lastOpIndex === -1) {
            // 無運算子，整個 tokens 即為左值
            $sub = $tokens;
        } else {
            // 運算子左側的 tokens
            $sub = array_slice($tokens, 0, $lastOpIndex);
        }

        if (empty($sub)) {
            return 0.0;
        }

        // 遞迴計算左側子表達式的值
        $subPostfix = $this->toPostfix($sub);
        return $this->evalPostfix($subPostfix);
    }

    /**
     * Shunting-yard：將中綴 token 陣列轉為後綴（RPN）
     *
     * @param list<string> $tokens
     * @return list<string>
     */
    private function toPostfix(array $tokens): array
    {
        $output = [];
        $opStack = [];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = $token;
            } elseif ($token === '(') {
                $opStack[] = $token;
            } elseif ($token === ')') {
                while (!empty($opStack) && end($opStack) !== '(') {
                    $output[] = array_pop($opStack);
                }
                if (empty($opStack)) {
                    throw new \RuntimeException('括號不匹配');
                }
                array_pop($opStack); // 彈出 '('
            } elseif (isset(self::PRECEDENCE[$token])) {
                while (
                    !empty($opStack) &&
                    end($opStack) !== '(' &&
                    isset(self::PRECEDENCE[end($opStack)]) &&
                    self::PRECEDENCE[end($opStack)] >= self::PRECEDENCE[$token]
                ) {
                    $output[] = array_pop($opStack);
                }
                $opStack[] = $token;
            } else {
                throw new \RuntimeException('未知的 token：' . $token);
            }
        }

        while (!empty($opStack)) {
            $op = array_pop($opStack);
            if ($op === '(' || $op === ')') {
                throw new \RuntimeException('括號不匹配');
            }
            $output[] = $op;
        }

        return $output;
    }

    /**
     * 計算後綴表達式（RPN）的值
     *
     * @param list<string> $postfix
     */
    private function evalPostfix(array $postfix): float
    {
        $stack = [];

        foreach ($postfix as $token) {
            if (is_numeric($token)) {
                $stack[] = (float) $token;
            } else {
                if (count($stack) < 2) {
                    throw new \RuntimeException('無效的表達式');
                }
                $b = array_pop($stack);
                $a = array_pop($stack);

                switch ($token) {
                    case '+':
                        $stack[] = $a + $b;
                        break;
                    case '-':
                        $stack[] = $a - $b;
                        break;
                    case '*':
                        $stack[] = $a * $b;
                        break;
                    case '/':
                        if ($b == 0) {
                            throw new \DivisionByZeroError('除以零');
                        }
                        $stack[] = $a / $b;
                        break;
                }
            }
        }

        if (count($stack) !== 1) {
            throw new \RuntimeException('無效的表達式');
        }

        return $stack[0];
    }
}
