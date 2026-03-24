<?php

return [
    'triage' => [
        'url' => env('TRIAGE_SERVICE_URL', 'http://localhost:3001'),
        'secret' => env('TRIAGE_SHARED_SECRET'),
        'timeout' => (int) env('TRIAGE_TIMEOUT_SECONDS', 20),
        'min_confidence' => (float) env('TRIAGE_MIN_CONFIDENCE', 0.6),
    ],
];
