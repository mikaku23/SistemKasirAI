<?php

namespace Tests\Unit;

use App\Models\StockBatches;
use PHPUnit\Framework\TestCase;

class StockBatchesModelTest extends TestCase
{
    public function testLegacyStockBatchesClassAliasExists()
    {
        $this->assertTrue(class_exists('App\\Models\\Stock_batches'));
        $this->assertTrue(is_subclass_of('App\\Models\\Stock_batches', StockBatches::class));
    }
}
