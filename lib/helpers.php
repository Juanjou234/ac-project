<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function add_flash(string $type, string $message): void
{
    if (!isset($_SESSION['flashes']) || !is_array($_SESSION['flashes'])) {
        $_SESSION['flashes'] = [];
    }
    $_SESSION['flashes'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return is_array($flashes) ? $flashes : [];
}

function page_title(string $page): string
{
    $titles = [
        'dashboard' => 'Dashboard',
        'erp_clientes' => 'ERP - Clientes',
        'crm_contactos' => 'CRM - Contactos',
        'crm_empresas' => 'CRM - Empresas',
        'terceros' => 'Terceros',
        'lote' => 'Lote de Consultas',
        'configuracion' => 'Configuracion API',
    ];

    return $titles[$page] ?? 'Dashboard';
}

function redirect_page(string $page): void
{
    header('Location: index.php?page=' . rawurlencode($page));
    exit;
}

function get_record_by_id(string $listKey, string $recordId): ?array
{
    foreach (get_list_data($listKey) as $row) {
        if ((string)($row['id'] ?? '') === $recordId) {
            return $row;
        }
    }

    return null;
}

function list_label(string $listKey): string
{
    $labels = [
        'erp_clientes' => 'ERP_CLIENTES',
        'crm_contactos' => 'CRM_CONTACTOS',
        'crm_empresas' => 'CRM_EMPRESAS',
        'terceros' => 'TERCEROS',
    ];

    return $labels[$listKey] ?? strtoupper($listKey);
}
