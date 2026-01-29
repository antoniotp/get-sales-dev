<?php

namespace App\Services\System;

use App\Contracts\Services\System\TimezoneServiceInterface;
use App\DataTransferObjects\System\TimezoneData;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;

class TimezoneService implements TimezoneServiceInterface
{
    public function getAllFormatted(): array
    {
        return Cache::rememberForever('system.timezones', function () {
            return collect(DateTimeZone::listIdentifiers())
                ->map(function ($tz) {
                    return TimezoneData::fromTimezone($tz);
                })
                ->sortBy('offset')
                ->groupBy('continent')
                ->toArray();
        });
    }
}
