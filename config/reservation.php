<?php

return [
    'max_reservation_time' => 100,  // in minutes
    'max_reservations_per_user' => 2, // per day
    'daily_maintenance_start' => [
        'hours' => 4,
        'minutes' => 0,
        'cron' => '4:02', // add some threshold
    ],
    'daily_maintenance_end' => [
        'hours' => 4,
        'minutes' => 15
    ],
];
