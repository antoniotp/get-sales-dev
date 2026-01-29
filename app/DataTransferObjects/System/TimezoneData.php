<?php

namespace App\DataTransferObjects\System;

use Carbon\Carbon;

readonly class TimezoneData
{
    public function __construct(
        public string $continent,
        public string $value,
        public string $label,
        public string $offset
    ) {}

    public static function fromTimezone(string $timezone): self
    {
        $now = Carbon::now($timezone);

        if (str_contains($timezone, '/')) {
            $parts = explode('/', $timezone);
            $continent = $parts[0];
            $timezoneLabel = str_replace($continent.'/', '', $timezone);
        } else {
            $continent = 'Others';
            $timezoneLabel = $timezone;
        }

        return new self(
            continent: $continent,
            value: $timezone,
            label: str_replace('_', ' ', $timezoneLabel)." (UTC {$now->format('P')})",
            offset: $now->offset
        );
    }
}
