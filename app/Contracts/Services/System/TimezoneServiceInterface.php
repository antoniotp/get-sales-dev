<?php

namespace App\Contracts\Services\System;

interface TimezoneServiceInterface
{
    public function getAllFormatted(): array;
}
