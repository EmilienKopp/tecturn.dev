<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Generation Buffer
    |--------------------------------------------------------------------------
    |
    | A small delay (in seconds) applied inside the deck generation job before
    | broadcasting the "ready" event. It stops a fast AI response from producing
    | a jarring, instant toast. Tests set this to 0.
    |
    */

    'buffer_seconds' => (int) env('DECK_BUFFER_SECONDS', 1),

];
