<?php

namespace App\Modules\Identity\Enums;

enum AccountType: string
{
    case Customer = 'customer';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Staff => 'Company Staff',
        };
    }
}
