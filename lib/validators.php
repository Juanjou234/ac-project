<?php

declare(strict_types=1);

const REGEX_NAME = '/^(?=.{1,120}$)[\p{L}]+(?:\s+[\p{L}]+)*$/u';
const REGEX_DOC = '/^(?=.{0,40}$)[0-9]+(?:[0-9\s-]*[0-9])?$/';

function valid_list_key(string $listKey): bool
{
    return in_array($listKey, ['erp_clientes', 'crm_contactos', 'crm_empresas', 'terceros'], true);
}

function validate_name_field(string $value): bool
{
    return (bool)preg_match(REGEX_NAME, $value);
}

function validate_document_field(string $value): bool
{
    if ($value === '') {
        return true;
    }

    return (bool)preg_match(REGEX_DOC, $value);
}

function validate_record_input(array $input): array
{
    $errors = [];
    $tipo = strtoupper(trim((string)($input['tipo'] ?? '')));
    $nombres = trim((string)($input['nombres'] ?? ''));
    $apellidos = trim((string)($input['apellidos'] ?? ''));
    $nombreCompleto = trim((string)($input['nombre_completo'] ?? ''));
    $documento = trim((string)($input['documento'] ?? ''));

    if (!in_array($tipo, ['PN', 'PJ'], true)) {
        $errors[] = 'Tipo invalido. Debe ser Persona Natural o Persona Juridica.';
    }

    if ($nombres !== '' && !validate_name_field($nombres)) {
        $errors[] = 'Nombres invalido. Solo letras y espacios (1-120).';
    }
    if ($apellidos !== '' && !validate_name_field($apellidos)) {
        $errors[] = 'Apellidos invalido. Solo letras y espacios (1-120).';
    }
    if ($nombreCompleto !== '' && !validate_name_field($nombreCompleto)) {
        $errors[] = 'Nombre completo invalido. Solo letras y espacios (1-120).';
    }
    if (!validate_document_field($documento)) {
        $errors[] = 'Documento invalido. Solo numeros, espacios y guion (max 40).';
    }
    if ($tipo === 'PN') {
        if ($nombres === '' || $apellidos === '') {
            $errors[] = 'Persona Natural requiere nombres y apellidos.';
        }
        if ($nombreCompleto !== '') {
            $errors[] = 'Persona Natural no permite nombre_completo.';
        }
    }
    if ($tipo === 'PJ') {
        if ($nombreCompleto === '') {
            $errors[] = 'Persona Juridica requiere nombre_completo.';
        }
        if ($nombres !== '' || $apellidos !== '') {
            $errors[] = 'Persona Juridica no permite nombres ni apellidos.';
        }
    }

    if ($tipo === 'PN') {
        $nombreCompleto = '';
    }
    if ($tipo === 'PJ') {
        $nombres = '';
        $apellidos = '';
    }

    return [
        'ok' => empty($errors),
        'errors' => $errors,
        'data' => [
            'tipo' => $tipo,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'nombre_completo' => $nombreCompleto,
            'documento' => $documento !== '' ? $documento : null,
        ],
    ];
}

function validate_consulta_input(array $input): array
{
    $errors = [];
    $mode = strtoupper(trim((string)($input['modo'] ?? '')));
    $nombres = trim((string)($input['nombres'] ?? ''));
    $apellidos = trim((string)($input['apellidos'] ?? ''));
    $nombreCompleto = trim((string)($input['nombre_completo'] ?? ''));
    $documento = trim((string)($input['documento'] ?? ''));

    if (!in_array($mode, ['A', 'B'], true)) {
        $errors[] = 'Modo invalido. Debe ser A o B.';
    }
    if (!validate_document_field($documento)) {
        $errors[] = 'Documento invalido para consulta API.';
    }

    if ($mode === 'A') {
        if ($nombres === '' || $apellidos === '') {
            $errors[] = 'Modo A requiere nombres y apellidos.';
        }
        if ($nombres !== '' && !validate_name_field($nombres)) {
            $errors[] = 'Nombres invalido para modo A.';
        }
        if ($apellidos !== '' && !validate_name_field($apellidos)) {
            $errors[] = 'Apellidos invalido para modo A.';
        }
        if ($nombreCompleto !== '') {
            $errors[] = 'Modo A no permite nombre_completo.';
        }
    }

    if ($mode === 'B') {
        if ($nombreCompleto === '') {
            $errors[] = 'Modo B requiere nombre_completo.';
        }
        if ($nombreCompleto !== '' && !validate_name_field($nombreCompleto)) {
            $errors[] = 'Nombre completo invalido para modo B.';
        }
        if ($nombres !== '' || $apellidos !== '') {
            $errors[] = 'Modo B no permite nombres ni apellidos.';
        }
    }

    $payload = [];
    if ($mode === 'A') {
        $payload['nombres'] = $nombres;
        $payload['apellidos'] = $apellidos;
        if ($documento !== '') {
            $payload['documento'] = $documento;
        }
    }
    if ($mode === 'B') {
        $payload['nombre_completo'] = $nombreCompleto;
        if ($documento !== '') {
            $payload['documento'] = $documento;
        }
    }

    return [
        'ok' => empty($errors),
        'errors' => $errors,
        'mode' => $mode,
        'payload' => $payload,
    ];
}

function validate_config_input(array $input): array
{
    $errors = [];
    $url = trim((string)($input['url'] ?? ''));
    $apiKey = trim((string)($input['api_key'] ?? ''));
    $headerName = trim((string)($input['header_name'] ?? ''));
    $timeout = (int)($input['timeout_ms'] ?? 5000);

    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) === false) {
        $errors[] = 'La URL no es valida.';
    }
    if ($timeout < 1000 || $timeout > 60000) {
        $errors[] = 'Timeout debe estar entre 1000 y 60000 ms.';
    }

    return [
        'ok' => empty($errors),
        'errors' => $errors,
        'data' => [
            'url' => $url,
            'api_key' => $apiKey,
            'header_name' => $headerName,
            'timeout_ms' => $timeout,
        ],
    ];
}
