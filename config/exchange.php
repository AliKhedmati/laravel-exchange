<?php

return [
    'default-driver'    =>  env('DEFAULT_EXCHANGE', 'nobitex'),
    'drivers' =>  [
        'nobitex'   =>  [
            'base-url'  =>  env('NOBITEX_BASE_URL', 'https://api.nobitex.ir/'),
            'api-key'  =>  env('NOBITEX_API_KEY'),
        ],
        'bitpin'    =>  [
            'base-url'  =>  env('BITPIN_BASE_URL', 'https://api.bitpin.ir/'),
            'api-key'   =>  env('BITPIN_API_KEY'),
            'secret-key'    =>  env('BITPIN_SECRET_KEY'),
        ],
        'wallex'    =>  [
            'base-url'  =>  env('WALLEX_BASE_URL', 'https://api.wallex.ir/'),
            'api-key'   =>  env('WALLEX_API_KEY')
        ]
    ],
];