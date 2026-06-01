<?php

namespace App\Helpers;

class NumberToWords
{
    protected static $ones = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen'
    ];

    protected static $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    ];

    public static function convert($number): string
    {
        $number = (int) $number;

        if ($number === 0) {
            return 'Zero';
        }

        if ($number < 0) {
            return 'Minus ' . self::convert(abs($number));
        }

        $words = [];

        if ($number >= 10000000) {
            $crore = (int) ($number / 10000000);
            $words[] = self::convertBelow1000($crore) . ' Crore';
            $number %= 10000000;
        }

        if ($number >= 100000) {
            $lakh = (int) ($number / 100000);
            $words[] = self::convertBelow1000($lakh) . ' Lakh';
            $number %= 100000;
        }

        if ($number >= 1000) {
            $thousand = (int) ($number / 1000);
            $words[] = self::convertBelow1000($thousand) . ' Thousand';
            $number %= 1000;
        }

        if ($number > 0) {
            $words[] = self::convertBelow1000($number);
        }

        return implode(' ', array_filter($words));
    }

    protected static function convertBelow1000($number): string
    {
        $words = [];

        if ($number >= 100) {
            $words[] = self::$ones[(int) ($number / 100)] . ' Hundred';
            $number %= 100;
        }

        if ($number >= 20) {
            $words[] = self::$tens[(int) ($number / 10)];
            $number %= 10;
        }

        if ($number > 0 && $number < 20) {
            $words[] = self::$ones[$number];
        }

        return implode(' ', array_filter($words));
    }
}
