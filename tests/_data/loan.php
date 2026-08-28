<?php

return [
    // Already returned. BR-8 asks whether the item was EVER lent out, not
    // whether it is out right now - this row proves the difference.
    'closed' => [
        'id' => 1,
        'equipment_id' => 2,
        'borrower_id' => 1,
        'loaned_at' => '2026-08-01',
        'due_at' => '2026-08-08',
        'returned_at' => '2026-08-07',
        'note' => 'Lezárt kölcsönzés a BR-8 teszteléséhez.',
        'created_at' => '2026-08-01 10:00:00',
    ],
];
