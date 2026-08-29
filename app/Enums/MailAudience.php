<?php

namespace App\Enums;

enum MailAudience: string
{
    case Staff = 'staff';
    case Customer = 'customer';

    public function layout(): string
    {
        return match ($this) {
            self::Staff => 'emails.layouts.staff',
            self::Customer => 'emails.layouts.customer',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'พนักงาน',
            self::Customer => 'ลูกค้า / นักเรียน',
        };
    }
}
