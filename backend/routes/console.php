<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('leadlantern:about', function () {
    $this->info('LeadLantern API — LLM-routed CRM task triage');
})->purpose('Show LeadLantern API info');
