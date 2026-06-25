<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use Carbon\CarbonImmutable;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property DeviceType $type
 * @property string $ip
 * @property string|null $mac
 * @property string|null $vlan
 * @property DeviceStatus $status
 * @property CarbonImmutable|null $last_seen
 * @property int|null $monitor_port
 * @property int $pjlink_port
 * @property string|null $pjlink_password
 * @property string|null $wol_broadcast
 * @property int $wol_port
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'name', 'type', 'ip', 'mac', 'vlan', 'status', 'last_seen',
    'monitor_port', 'pjlink_port', 'pjlink_password', 'wol_broadcast', 'wol_port',
])]
class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DeviceType::class,
            'status' => DeviceStatus::class,
            'last_seen' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PocActionLog, $this>
     */
    public function actionLogs(): HasMany
    {
        return $this->hasMany(PocActionLog::class);
    }

    public function supportsPjlink(): bool
    {
        return $this->type->supportsPjlink();
    }
}
