<?php

return [
    'blind_index_key' => env('BLIND_INDEX_KEY'),
    'retencao_prontuario_anos' => 20,
    'jitsi' => [
        'domain' => env('JITSI_DOMAIN'),
        'app_id' => env('JITSI_APP_ID'),
        'app_secret' => env('JITSI_APP_SECRET'),
        'antecedencia_min' => 15,
        'tolerancia_apos_fim_min' => 30,
    ]
];
