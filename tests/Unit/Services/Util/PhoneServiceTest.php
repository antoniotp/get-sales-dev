<?php

namespace Tests\Unit\Services\Util;

use App\Contracts\Services\Util\PhoneServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneServiceTest extends TestCase
{
    private PhoneServiceInterface $phoneService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phoneService = $this->app->make(PhoneServiceInterface::class);
    }

    #[DataProvider('phoneNumberProvider')]
    public function test_get_country_from_phone_number(string $phoneNumber, string $expectedCountry): void
    {
        $country = $this->phoneService->getCountryFromPhoneNumber($phoneNumber);
        $this->assertEquals($expectedCountry, $country);
    }

    public static function phoneNumberProvider(): array
    {
        return [
            'USA number' => ['+1-202-555-0104', 'US'],
            'USA number without plus' => ['1-202-555-0104', 'US'],
            'UK number' => ['+44 20 7946 0958', 'GB'],
            'Argentinian number' => ['+54 9 11 1234-5678', 'AR'],
            'Argentinian number without plus' => ['54 9 11 1234-5678', 'AR'],
            'Brazilian number' => ['+55 11 98765-4321', 'BR'],
            'Spanish number' => ['+34 911 23 45 67', 'ES'],
            'Mexican number' => ['+52 55 1234 5678', 'MX'],
            'Mexican number with extra digit' => ['+521 55 1234 5678', 'MX'],
            'Colombian number' => ['+57 300 1234567', 'CO'],
            'Invalid number' => ['not a number', ''],
            'Empty string' => ['', ''],
        ];
    }
}
