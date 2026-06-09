<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NeutralSaasNavigationTest extends TestCase
{
    /** @var list<string> */
    private array $domainNouns = [
        'coach',
        'course',
        'class',
        'equipment',
        'level',
        'schedule',
        'booking',
        'timetable',
        'member-package',
        'member_package',
        'credit',
        'product',
        'store',
        'order',
        'payment',
        'voucher',
        'customer',
        'material',
        'addon',
        'brand',
        'color',
        'satuan',
        'pilates',
        'studio',
        'gym',
    ];

    public function test_navigation_config_is_domain_neutral(): void
    {
        $navigation = require __DIR__ . '/../../config/navigation.php';

        $payload = strtolower(json_encode($navigation, JSON_THROW_ON_ERROR));

        foreach ($this->domainNouns as $noun) {
            $this->assertStringNotContainsString($noun, $payload, "Navigation contains domain noun [{$noun}].");
        }
    }

    public function test_navigation_keeps_core_saas_sections(): void
    {
        $navigation = require __DIR__ . '/../../config/navigation.php';
        $payload = strtolower(json_encode($navigation['admin'], JSON_THROW_ON_ERROR));

        foreach (['dashboard', 'organizations', 'branches', 'users', 'roles', 'permissions', 'subscriptions', 'account settings'] as $expected) {
            $this->assertStringContainsString($expected, $payload);
        }
    }
}
