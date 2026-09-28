<?php

namespace Modules\ReportsOther\Services;

class AmountWordsService
{
    private array $ones = [
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
    ];

    private array $tens = [
        20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
        60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety',
    ];

    public function convert(float|int|string $amount, int $precision = 2): string
    {
        $precision = max(0, min(8, $precision));
        $formatted = number_format((float) $amount, $precision, '.', '');
        [$whole, $fraction] = array_pad(explode('.', $formatted, 2), 2, '');
        $wholeNumber = (int) $whole;
        $words = $this->integerToWords($wholeNumber);

        if ($precision > 0 && (int) $fraction > 0) {
            $minorLabel = trim((string) config('reportsother.amount_words.minor_unit_label', 'Cents')) ?: 'Cents';
            $words .= ' and '.$this->integerToWords((int) $fraction).' '.$minorLabel;
        }

        return trim($words).' Only';
    }

    private function integerToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }
        if ($number < 0) {
            return 'Minus '.$this->integerToWords(abs($number));
        }

        $scales = [
            1000000000000 => 'Trillion',
            1000000000 => 'Billion',
            1000000 => 'Million',
            1000 => 'Thousand',
        ];

        $parts = [];
        foreach ($scales as $value => $label) {
            if ($number >= $value) {
                $count = intdiv($number, $value);
                $parts[] = $this->integerToWords($count).' '.$label;
                $number %= $value;
            }
        }

        if ($number >= 100) {
            $parts[] = $this->ones[intdiv($number, 100)].' Hundred';
            $number %= 100;
        }

        if ($number >= 20) {
            $ten = intdiv($number, 10) * 10;
            $word = $this->tens[$ten];
            $number %= 10;
            if ($number > 0) {
                $word .= '-'.$this->ones[$number];
            }
            $parts[] = $word;
        } elseif ($number > 0) {
            $parts[] = $this->ones[$number];
        }

        return implode(' ', $parts);
    }
}
