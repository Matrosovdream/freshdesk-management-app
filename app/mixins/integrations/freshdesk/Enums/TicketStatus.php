<?php

namespace App\Mixins\Integrations\Freshdesk\Enums;

enum TicketStatus: int
{
    case Open     = 2;
    case Pending  = 3;
    case Resolved = 4;
    case Closed   = 5;

    public function label(): string
    {
        return match ($this) {
            self::Open     => 'Open',
            self::Pending  => 'Pending',
            self::Resolved => 'Resolved',
            self::Closed   => 'Closed',
        };
    }
}
