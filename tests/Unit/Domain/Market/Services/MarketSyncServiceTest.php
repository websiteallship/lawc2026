<?php

namespace Tests\Unit\Domain\Market\Services;

use App\Domain\Market\Services\MarketSyncService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MarketSyncServiceTest extends TestCase
{
    private function invokePrivateMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function test_calculate_profit_rate_correctly()
    {
        $service = new MarketSyncService();

        // Odd > 1.0 => profit_rate = odd - 1
        $this->assertEquals(0.910, $this->invokePrivateMethod($service, 'calculateProfitRate', [1.91]));
        $this->assertEquals(0.850, $this->invokePrivateMethod($service, 'calculateProfitRate', [1.85]));
        
        // Odd < 1.0 => profit_rate = odd
        $this->assertEquals(0.910, $this->invokePrivateMethod($service, 'calculateProfitRate', [0.91]));
        $this->assertEquals(0.850, $this->invokePrivateMethod($service, 'calculateProfitRate', [0.85]));
    }

    public function test_normalize_line_with_weird_values()
    {
        $service = new MarketSyncService();

        // ASIAN_HANDICAP
        $this->assertEquals(3.0, $this->invokePrivateMethod($service, 'normalizeLine', [3.1, 'ASIAN_HANDICAP']));
        $this->assertEquals(2.25, $this->invokePrivateMethod($service, 'normalizeLine', [2.2, 'ASIAN_HANDICAP']));
        $this->assertEquals(1.75, $this->invokePrivateMethod($service, 'normalizeLine', [1.8, 'ASIAN_HANDICAP']));
        $this->assertEquals(-1.5, $this->invokePrivateMethod($service, 'normalizeLine', [-1.4, 'ASIAN_HANDICAP']));
        
        // Vượt quá giới hạn (±3.0)
        $this->assertNull($this->invokePrivateMethod($service, 'normalizeLine', [3.25, 'ASIAN_HANDICAP']));
        $this->assertNull($this->invokePrivateMethod($service, 'normalizeLine', [-3.25, 'ASIAN_HANDICAP']));

        // OVER_UNDER
        $this->assertEquals(3.0, $this->invokePrivateMethod($service, 'normalizeLine', [3.1, 'OVER_UNDER']));
        $this->assertEquals(2.25, $this->invokePrivateMethod($service, 'normalizeLine', [2.2, 'OVER_UNDER']));
        
        // Nhỏ hơn 0.5
        $this->assertNull($this->invokePrivateMethod($service, 'normalizeLine', [0.25, 'OVER_UNDER']));
    }
}
