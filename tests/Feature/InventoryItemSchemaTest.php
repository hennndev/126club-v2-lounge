<?php

use Illuminate\Support\Facades\Schema;

test('inventory items table has is_count_portion_possible column', function () {
    expect(Schema::hasColumn('inventory_items', 'is_count_portion_possible'))->toBeTrue();
});
