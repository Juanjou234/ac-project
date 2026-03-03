<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!date_default_timezone_set('America/El_Salvador')) {
    date_default_timezone_set('UTC');
}

$apiConfigDefault = [
    'url' => '',
    'api_key' => '',
    'header_name' => '',
    'usuario' => '',
    'clave' => '',
    'timeout_ms' => 5000,
];

if (!isset($_SESSION['api_configs']) || !is_array($_SESSION['api_configs'])) {
    $legacy = $_SESSION['api_config'] ?? [];
    if (!is_array($legacy)) {
        $legacy = [];
    }
    $legacy = array_merge($apiConfigDefault, $legacy);
    $legacyIsQuery = trim((string)$legacy['usuario']) !== '' || trim((string)$legacy['clave']) !== '';

    $_SESSION['api_configs'] = [
        'header' => $apiConfigDefault,
        'query' => $apiConfigDefault,
        'active' => 'header',
    ];
    if ($legacyIsQuery) {
        $_SESSION['api_configs']['query'] = $legacy;
    } else {
        $_SESSION['api_configs']['header'] = $legacy;
    }
}

$_SESSION['api_configs'] = array_merge(
    [
        'header' => $apiConfigDefault,
        'query' => $apiConfigDefault,
        'active' => 'header',
    ],
    $_SESSION['api_configs']
);
$_SESSION['api_configs']['header'] = array_merge($apiConfigDefault, (array)($_SESSION['api_configs']['header'] ?? []));
$_SESSION['api_configs']['query'] = array_merge($apiConfigDefault, (array)($_SESSION['api_configs']['query'] ?? []));
if (!in_array((string)($_SESSION['api_configs']['active'] ?? ''), ['header', 'query'], true)) {
    $_SESSION['api_configs']['active'] = 'header';
}
$_SESSION['api_config'] = $_SESSION['api_configs'][$_SESSION['api_configs']['active']];

if (!isset($_SESSION['data_custom']) || !is_array($_SESSION['data_custom'])) {
    $_SESSION['data_custom'] = [
        'erp_clientes' => [],
        'crm_contactos' => [],
        'crm_empresas' => [],
        'terceros' => [],
    ];
}

if (!isset($_SESSION['data_overrides']) || !is_array($_SESSION['data_overrides'])) {
    $_SESSION['data_overrides'] = [
        'erp_clientes' => [],
        'crm_contactos' => [],
        'crm_empresas' => [],
        'terceros' => [],
    ];
}

if (!isset($_SESSION['data_deleted']) || !is_array($_SESSION['data_deleted'])) {
    $_SESSION['data_deleted'] = [
        'erp_clientes' => [],
        'crm_contactos' => [],
        'crm_empresas' => [],
        'terceros' => [],
    ];
}

if (!isset($_SESSION['history']) || !is_array($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

if (!isset($_SESSION['batch_last']) || !is_array($_SESSION['batch_last'])) {
    $_SESSION['batch_last'] = [];
}

function get_list_data(string $listKey): array
{
    $validLists = ['erp_clientes', 'crm_contactos', 'crm_empresas', 'terceros'];
    if (!in_array($listKey, $validLists, true)) {
        return [];
    }

    $dataPath = __DIR__ . '/../data/' . $listKey . '.php';
    $baseData = [];
    if (is_file($dataPath)) {
        $loaded = require $dataPath;
        if (is_array($loaded)) {
            $baseData = $loaded;
        }
    }

    $custom = $_SESSION['data_custom'][$listKey] ?? [];
    if (!is_array($custom)) {
        $custom = [];
    }

    $overrides = $_SESSION['data_overrides'][$listKey] ?? [];
    if (!is_array($overrides)) {
        $overrides = [];
    }
    $deleted = $_SESSION['data_deleted'][$listKey] ?? [];
    if (!is_array($deleted)) {
        $deleted = [];
    }
    $deletedMap = [];
    foreach ($deleted as $dId) {
        $deletedMap[(string)$dId] = true;
    }

    $merged = array_merge($baseData, $custom);
    foreach ($merged as $idx => $row) {
        $id = (string)($row['id'] ?? '');
        if ($id !== '' && isset($deletedMap[$id])) {
            unset($merged[$idx]);
            continue;
        }
        if ($id !== '' && isset($overrides[$id]) && is_array($overrides[$id])) {
            $merged[$idx] = array_merge($row, $overrides[$id]);
        }
    }
    $merged = array_values($merged);
    usort(
        $merged,
        static function (array $a, array $b): int {
            $orderA = (int)($a['orden'] ?? 0);
            $orderB = (int)($b['orden'] ?? 0);
            if ($orderA === $orderB) {
                return strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
            }

            return $orderA <=> $orderB;
        }
    );

    return $merged;
}
