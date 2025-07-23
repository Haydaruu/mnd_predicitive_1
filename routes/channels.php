<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('agent.', function ($agent) {
    return auth()->check();
});

