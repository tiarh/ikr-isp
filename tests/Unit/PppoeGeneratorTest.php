<?php

namespace Tests\Unit;

use App\Enums\PsbStatus;
use App\Models\PsbOrder;
use PHPUnit\Framework\TestCase;

class PppoeGeneratorTest extends TestCase
{
    public function test_generate_username_format(): void
    {
        $order = new PsbOrder([
            'customer_name' => 'Budi Santoso',
            'rt' => '03',
            'rw' => '05',
            'odp_code' => 'ODP-MLG-001',
        ]);

        $expected = 'BUDISANTOSO_RT03_RW05_ODPMLG001';
        $this->assertEquals($expected, PsbOrder::generatePppoeUser('Budi Santoso', '03', '05', 'ODP-MLG-001'));
    }

    public function test_generate_username_handles_special_chars(): void
    {
        $user = PsbOrder::generatePppoeUser("Ahmad 'Ali & Co.", '1', '2', 'ODP-001');
        $this->assertStringNotContainsString("'", $user);
        $this->assertStringNotContainsString("&", $user);
        $this->assertStringContainsString('AHMADALICO', $user);
    }

    public function test_generate_password_uses_router_name(): void
    {
        $pass = PsbOrder::generatePppoePassword('Mangliawan');
        $this->assertEquals('mangliawan', $pass);
    }

    public function test_generate_password_strips_specials_and_lowercases(): void
    {
        $this->assertEquals('sumbersari', PsbOrder::generatePppoePassword('Sumber Sari'));
        $this->assertEquals('krebet01', PsbOrder::generatePppoePassword('Krebet-01'));
    }
}
