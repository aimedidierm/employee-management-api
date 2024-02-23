<?php

namespace App\Enums;

enum UserType: string
{
    case MANAGER = 'manager';
    case DEVELOPER = 'developer';
    case CLEANER = 'cleaner';
}
