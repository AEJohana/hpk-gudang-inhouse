<?php

namespace Tests\Unit;

use App\Models\Component;
use PHPUnit\Framework\TestCase;

class ComponentQuickStoreTest extends TestCase
{
    /**
     * Test that new components default to valid attributes and dimension formatted works.
     */
    public function test_component_attributes_and_dimensions(): void
    {
        $comp = new Component([
            'part_number' => 'NEW-20261026-TEST',
            'name' => 'Plangkon Bumper Belakang ADR 25',
            'category' => 'subframe',
            'uom' => 'PCS',
            'length_mm' => '250',
            'width_or_diameter_mm' => '80',
            'thickness_or_height_mm' => '6',
        ]);

        $this->assertEquals('NEW-20261026-TEST', $comp->part_number);
        $this->assertEquals('subframe', $comp->category);
        $this->assertEquals('PCS', $comp->uom);
        $this->assertEquals('P: 250mm x L/D: 80mm x T: 6mm', $comp->dimension_formatted);
    }

    /**
     * Test component category fallback.
     */
    public function test_component_category_fallback(): void
    {
        $comp = new Component([
            'part_number' => 'NEW-20261026-ACC',
            'name' => 'Handle Bodi Karoseri',
            'category' => 'accessories',
        ]);

        $this->assertEquals('accessories', $comp->category);
    }

    /**
     * Test component source attributes and master isolation.
     */
    public function test_component_source_and_isolation_accessors(): void
    {
        $masterComp = new Component([
            'part_number' => 'CSFP10300250082',
            'name' => 'Bracket Mounting Hino',
            'source' => 'master',
        ]);

        $this->assertTrue($masterComp->is_master);
        $this->assertFalse($masterComp->is_wr_adhoc);

        $wrComp = new Component([
            'part_number' => 'NEW-20261123-ABCD',
            'name' => 'Flange Custom WR 50mm',
            'source' => 'wr_adhoc',
        ]);

        $this->assertFalse($wrComp->is_master);
        $this->assertTrue($wrComp->is_wr_adhoc);
    }
}
