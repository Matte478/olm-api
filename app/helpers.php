<?php

use Carbon\Carbon;

if (!function_exists('checkDatesOverlap')) {

    function checkDatesOverlap(Carbon $start1, Carbon $end1, Carbon $start2, Carbon $end2): bool
    {
        return
            $start1->lt($start2) && $end1->gt($start2) || // left
            $start1->lt($end2) && $end1->gt($end2) || // right
            $start1->gte($start2) && $end1->lte($end2) || // inner
            $start1->lt($start2) && $end1->gt($end2); // outer
    }
}
