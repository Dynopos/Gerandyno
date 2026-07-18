<?php

namespace App\Support;

final class Money
{
    public static function format(float|int|string|null $amount): string
    {
        return 'RM '.number_format((float) $amount, 2);
    }
}
