<?php

return [
    'ami' => [
        'host' => env('ASTERISK_AMI_HOST', '192.168.18.66'), // IP laptop PBX
        'port' => env('ASTERISK_AMI_PORT', 5038),
        'username' => env('ASTERISK_AMI_USERNAME', 'admin'),
        'secret' => env('ASTERISK_AMI_SECRET', 'amp111'),
        'timeout' => env('ASTERISK_AMI_TIMEOUT', 10),
    ],
    
    'contexts' => [
        'outbound' => env('ASTERISK_OUTBOUND_CONTEXT', 'from-internal'),
        'predictive' => env('ASTERISK_PREDICTIVE_CONTEXT', 'predictive-dialer'),
    ],
    
    'channels' => [
        'sip_prefix' => env('ASTERISK_SIP_PREFIX', 'SIP/'),
        'trunk_prefix' => env('ASTERISK_TRUNK_PREFIX', 'SIP/trunk/'),
    ],
];