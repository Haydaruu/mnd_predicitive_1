<?php

return [
    'ami' => [
        'host' => env('ASTERISK_AMI_HOST', '172.23.158.139'), // WSL localhost
        'port' => env('ASTERISK_AMI_PORT', 5038),
        'username' => env('ASTERISK_AMI_USERNAME', 'admin'),
        'secret' => env('ASTERISK_AMI_SECRET', 'amp111'),
        'timeout' => env('ASTERISK_AMI_TIMEOUT', 10),
        'connect_timeout' => env('ASTERISK_AMI_CONNECT_TIMEOUT', 5),
        'read_timeout' => env('ASTERISK_AMI_READ_TIMEOUT', 10),
    ],
    
    'contexts' => [
        'outbound' => env('ASTERISK_OUTBOUND_CONTEXT', 'from-internal'),
        'predictive' => env('ASTERISK_PREDICTIVE_CONTEXT', 'predictive-dialer'),
        'agents' => env('ASTERISK_AGENTS_CONTEXT', 'agents'),
    ],
    
    'channels' => [
        'sip_prefix' => env('ASTERISK_SIP_PREFIX', 'PJSIP/'),
        'trunk_prefix' => env('ASTERISK_TRUNK_PREFIX', 'PJSIP/trunk/'),
        'agent_prefix' => env('ASTERISK_AGENT_PREFIX', 'PJSIP/'),
    ],
    
    'dialer' => [
        'max_concurrent_calls' => env('PREDICTIVE_MAX_CONCURRENT', 10),
        'answer_timeout' => env('PREDICTIVE_ANSWER_TIMEOUT', 30),
        'retry_attempts' => env('PREDICTIVE_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('PREDICTIVE_RETRY_DELAY', 300), // 5 minutes
        'predictive_ratio' => env('PREDICTIVE_RATIO', 2.5), // calls per agent
        'abandon_rate_threshold' => env('ABANDON_RATE_THRESHOLD', 5), // 5%
    ],
    
    'call_dispositions' => [
        'answered' => 'ANSWERED',
        'busy' => 'BUSY',
        'no_answer' => 'NOANSWER',
        'failed' => 'FAILED',
        'congestion' => 'CONGESTION',
        'cancelled' => 'CANCELLED',
        'unknown' => 'UNKNOWN',
    ],
];