<?php

namespace App\Services\Payments;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class CommissionCalculator
{
    public function quote(string $amount): array
    {
        $minor = self::minor($amount);
        $rate = (string) config('frame.commission_percent');
        abort_unless(preg_match('/^\d{1,2}(?:\.\d{1,4})?$/D', $rate) === 1, 503);
        $fee = BigDecimal::of($minor)->multipliedBy($rate)->dividedBy(100, 0, RoundingMode::HALF_UP)->toInt();

        return ['amount' => self::decimal($minor), 'platform_fee' => self::decimal($fee), 'editor_net_amount' => self::decimal($minor - $fee)];
    }

    public static function minor(string $amount): int
    {
        abort_unless(preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $amount) === 1, 409);
        $minor = BigDecimal::of($amount)->multipliedBy(100)->toInt();
        abort_unless($minor >= 50 && $minor <= 99999999, 409);

        return $minor;
    }

    private static function decimal(int $minor): string
    {
        return (string) BigDecimal::of($minor)->dividedBy(100, 2);
    }
}
