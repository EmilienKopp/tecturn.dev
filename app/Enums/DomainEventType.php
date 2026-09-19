<?php

namespace App\Enums;

enum DomainEventType
{
    case CREATED;
    case UPDATED;
    case DELETED;
    case RESTORED;
}
