<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Services\WhatsApp\WhatsAppWebService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppWebServiceTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $service = new WhatsAppWebService;
        $this->assertInstanceOf(WhatsAppWebService::class, $service);
    }
}
