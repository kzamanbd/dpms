<?php

namespace App\Enums;

enum DeviceType: string
{
    case Projector = 'projector';
    case Pc = 'pc';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Projector => 'Projector',
            self::Pc => 'PC',
            self::Other => 'Other',
        };
    }

    /**
     * Whether this device type is controllable via PJLink.
     */
    public function supportsPjlink(): bool
    {
        return $this === self::Projector;
    }
}
