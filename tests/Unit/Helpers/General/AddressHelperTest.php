<?php

namespace Tests\Unit\Helpers\General;

use App\Helpers\General\AddressHelper;
use PHPUnit\Framework\TestCase;

class AddressHelperTest extends TestCase
{
    public function test_joins_street_then_picked_parts_skipping_empty_ones(): void
    {
        $address = AddressHelper::join('House 6D, Road 7B', ['Uttara', null, '', 'Dhaka', '1230', 'Bangladesh']);

        $this->assertSame('House 6D, Road 7B, Uttara, Dhaka, 1230, Bangladesh', $address);
    }

    public function test_skips_picked_parts_the_typed_street_already_mentions(): void
    {
        $address = AddressHelper::join('Shop C-39/B, Mirpur 1, dhaka-1216', ['Mirpur 1', 'Dhaka', '1216']);

        $this->assertSame('Shop C-39/B, Mirpur 1, dhaka-1216', $address);
    }

    public function test_keeps_a_picked_part_that_only_appears_inside_a_longer_word_of_the_street(): void
    {
        $address = AddressHelper::join('Rampura Bridge', ['Ram']);

        $this->assertSame('Rampura Bridge, Ram', $address);
    }

    public function test_keeps_a_district_even_when_the_area_name_contains_it(): void
    {
        $address = AddressHelper::join(null, ['Gazipur Sadar', 'Joydebpur', 'Gazipur', '1703']);

        $this->assertSame('Gazipur Sadar, Joydebpur, Gazipur, 1703', $address);
    }

    public function test_skips_a_picked_part_identical_to_one_already_added(): void
    {
        $address = AddressHelper::join(null, ['Savar', 'savar', 'Dhaka']);

        $this->assertSame('Savar, Dhaka', $address);
    }
}
