<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $device_id
 * @property string $action
 * @property string $result
 * @property string|null $detail
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['device_id', 'action', 'result', 'detail'])]
class PocActionLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
