<?php

namespace App\Services;

class Validation
{
    public static function isRwandaPhoneNumber(string $number): bool
    {
        $number = preg_replace('/[^0-9]/', '', $number); // Remove non-numeric characters
        if (strlen($number) !== 12) {
            return false;
        }
        if (substr($number, 0, 3) !== '250') {
            return false;
        }
        return true;
    }
}
