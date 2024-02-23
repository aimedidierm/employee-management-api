<?php

namespace App\Services;

use App\Models\Employee;

class EmployeeCodeGenerator
{
    public static function generate($check_db = true): string
    {
        $text = "1234567890";
        $code = "EMP" . substr(str_shuffle($text), 1, 4);

        if (!$check_db) {
            return $code;
        }

        while (Employee::code($code)
            ->exists()
        ) {
            $code = "EMP" . substr(str_shuffle($text), 1, 4);
        }

        return $code;
    }
}
