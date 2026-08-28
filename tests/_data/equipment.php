<?php

return [
    // Never lent out, so BR-8 allows it to be deleted for good.
    'neverLoaned' => [
        'id' => 1,
        'category_id' => 1,
        'inventory_no' => 'LP-0001',
        'name' => 'Dell Latitude 5540',
        'description' => null,
        'status' => 0,
        'purchased_at' => '2026-01-15',
        'deposit' => 30000,
        'created_at' => '2026-08-01 09:10:00',
        'updated_at' => null,
    ],
    // Has one closed loan, so BR-8 must scrap it instead of deleting it.
    'previouslyLoaned' => [
        'id' => 2,
        'category_id' => 1,
        'inventory_no' => 'LP-0002',
        'name' => 'Lenovo ThinkPad T14',
        'description' => null,
        'status' => 0,
        'purchased_at' => '2026-02-20',
        'deposit' => 25000,
        'created_at' => '2026-08-01 09:11:00',
        'updated_at' => null,
    ],
    // Keeps category 2 non-empty, so that category cannot be deleted.
    'inProjectors' => [
        'id' => 3,
        'category_id' => 2,
        'inventory_no' => 'PR-0001',
        'name' => 'Epson EB-2250U',
        'description' => null,
        'status' => 0,
        'purchased_at' => '2026-03-05',
        'deposit' => 40000,
        'created_at' => '2026-08-01 09:12:00',
        'updated_at' => null,
    ],
];
