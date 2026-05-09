<?php

namespace App\Enums;

enum ProcurementMode: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case BOTH = 'both';
}
