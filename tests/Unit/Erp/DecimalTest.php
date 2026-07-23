<?php

namespace Tests\Unit\Erp;

use App\Support\Erp\Decimal;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DecimalTest extends TestCase
{
    public function test_add_mul_without_float_drift(): void
    {
        $this->assertSame('1240.0000', Decimal::add(Decimal::mul('10', '100'), Decimal::mul('2', '120')));
    }

    public function test_integer_quantity_detection(): void
    {
        $this->assertTrue(Decimal::isIntegerQuantity('10'));
        $this->assertTrue(Decimal::isIntegerQuantity('10.0000'));
        $this->assertFalse(Decimal::isIntegerQuantity('1.5'));
    }

    public function test_to_int_quantity_rejects_fraction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Decimal::toIntQuantity('1.5');
    }
}
