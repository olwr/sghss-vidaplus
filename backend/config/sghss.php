<?php

return [
    'blind_index_key' => env('BLIND_INDEX_KEY'),
    // Prazo de guarda do prontuário (anos) — base da anonimização.
    'retencao_prontuario_anos' => (int) env('SGHSS_RETENCAO_PRONTUARIO_ANOS', 20),
    'jitsi' => [
        'domain' => env('JITSI_DOMAIN'),
        'app_id' => env('JITSI_APP_ID'),
        'app_secret' => env('JITSI_APP_SECRET'),
        'antecedencia_min' => (int) env('SGHSS_JITSI_ANTECEDENCIA_MIN', 15),         // pode entrar X min antes
        'tolerancia_apos_fim_min' => (int) env('SGHSS_JITSI_TOLERANCIA_MIN', 30),    // e até X min depois do fim
    ],
    'demo_password' => env('DEMO_PASSWORD'),
    // Fuso em que as horas das agendas (08:00-12:00) são interpretadas; o banco guarda tudo em UTC.
    'fuso_agenda' => env('SGHSS_FUSO_AGENDA', 'America/Sao_Paulo'),
];
