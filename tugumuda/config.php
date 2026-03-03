<?php

return [
    'base_url' => getenv('SERVER10_BASE_URL') ?: 'http://192.168.88.10',
    'token' => getenv('SERVER10_TOKEN') ?: '',
    'timeout' => 15,
    'allowed_paths' => [
        '/lumpiapasar/api/monitor',
        '/lumpiapasar/api/data',
        '/api/data',
    ],
];
