<?php

namespace App\Mixins\Integrations\Freshdesk\Enums;

enum TicketSource: int
{
    case Email          = 1;
    case Portal         = 2;
    case Phone          = 3;
    case Chat           = 7;
    case Feedback       = 9;
    case OutboundEmail  = 10;
}
