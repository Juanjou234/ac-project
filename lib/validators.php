<?php

declare(strict_types=1);

const REGEX_NAME = '/^(?=.{1,120}$)[\p{L}\p{N}._"\'\-]+(?:\s+[\p{L}\p{N}._"\'\-]+)*$/u';
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
    $alias = trim((string)($input['alias'] ?? ''));
    $nacionalidad = trim((string)($input['nacionalidad'] ?? ''));
    $paisDomicilio = trim((string)($input['pais_domicilio'] ?? ''));
    $comentarios = trim((string)($input['comentarios'] ?? ''));
    $representanteLegal = trim((string)($input['representante_legal'] ?? ''));

    if (!in_array($tipo, ['PN', 'PJ'], true)) {
        $errors[] = 'Tipo invalido. Debe ser Persona Natural o Persona Juridica.';
    }

    if ($nombres !== '' && !validate_name_field($nombres)) {
        $errors[] = 'Nombres invalido. Use letras, numeros, espacios y .-_ comillas (1-120).';
    }
    if ($apellidos !== '' && !validate_name_field($apellidos)) {
        $errors[] = 'Apellidos invalido. Use letras, numeros, espacios y .-_ comillas (1-120).';
    }
    if ($nombreCompleto !== '' && !validate_name_field($nombreCompleto)) {
        $errors[] = 'Nombre completo invalido. Use letras, numeros, espacios y .-_ comillas (1-120).';
    }
    if (!validate_document_field($documento)) {
        $errors[] = 'Documento invalido. Solo numeros, espacios y guion (max 40).';
    }
    if (mb_strlen($alias) > 120) {
        $errors[] = 'Alias invalido. Maximo 120 caracteres.';
    }
    if (mb_strlen($nacionalidad) > 120) {
        $errors[] = 'Nacionalidad invalida. Maximo 120 caracteres.';
    }
    if (mb_strlen($paisDomicilio) > 120) {
        $errors[] = 'Pais domicilio invalido. Maximo 120 caracteres.';
    }
    if (mb_strlen($comentarios) > 500) {
        $errors[] = 'Comentarios invalido. Maximo 500 caracteres.';
    }
    if (mb_strlen($representanteLegal) > 120) {
        $errors[] = 'Representante legal invalido. Maximo 120 caracteres.';
    }

    if ($tipo === 'PN') {
        if ($nombres === '' || $apellidos === '') {
            $errors[] = 'Persona Natural requiere nombres y apellidos.';
        }
        if ($nombreCompleto !== '') {
            $errors[] = 'Persona Natural no permite nombre_completo.';
        }
        if ($representanteLegal !== '') {
            $errors[] = 'Persona Natural no permite representante_legal.';
        }
    }
    if ($tipo === 'PJ') {
        if ($nombreCompleto === '') {
            $errors[] = 'Persona Juridica requiere nombre_completo.';
        }
        if ($nombres !== '' || $apellidos !== '') {
            $errors[] = 'Persona Juridica no permite nombres ni apellidos.';
        }
        if ($alias !== '' || $comentarios !== '') {
            $errors[] = 'Persona Juridica no permite alias ni comentarios.';
        }
    }

    if ($tipo === 'PN') {
        $nombreCompleto = '';
        $representanteLegal = '';
    }
    if ($tipo === 'PJ') {
        $nombres = '';
        $apellidos = '';
        $alias = '';
        $comentarios = '';
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
            'alias' => $alias,
            'nacionalidad' => $nacionalidad,
            'pais_domicilio' => $paisDomicilio,
            'comentarios' => $comentarios,
            'representante_legal' => $representanteLegal,
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
            $errors[] = 'Nombres invalido para modo A. Use letras, numeros, espacios y .-_ comillas.';
        }
        if ($apellidos !== '' && !validate_name_field($apellidos)) {
            $errors[] = 'Apellidos invalido para modo A. Use letras, numeros, espacios y .-_ comillas.';
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
            $errors[] = 'Nombre completo invalido para modo B. Use letras, numeros, espacios y .-_ comillas.';
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
        $payload['nombres'] = $nombreCompleto;
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
    $usuario = trim((string)($input['usuario'] ?? ''));
    $clave = trim((string)($input['clave'] ?? ''));
    $timeout = (int)($input['timeout_ms'] ?? 5000);

    if ($url !== '' && !preg_match('/^https?:\/\//i', $url)) {
        $url = 'http://' . $url;
    }

    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) === false) {
        $errors[] = 'La URL no es valida. Debe iniciar con http:// o https://';
    }
    if ($timeout < 1000 || $timeout > 60000) {
        $errors[] = 'Timeout debe estar entre 1000 y 60000 ms.';
    }

    $usesQueryCredentials = ($usuario !== '' || $clave !== '');
    if ($usesQueryCredentials) {
        if ($usuario === '' || $clave === '') {
            $errors[] = 'Para autenticacion por query debe ingresar usuario y clave.';
        }
    } else {
        if ($apiKey === '' || $headerName === '') {
            $errors[] = 'Debe ingresar API Key y Header name, o usuario y clave.';
        }
    }

    return [
        'ok' => empty($errors),
        'errors' => $errors,
        'data' => [
            'url' => $url,
            'api_key' => $apiKey,
            'header_name' => $headerName,
            'usuario' => $usuario,
            'clave' => $clave,
            'timeout_ms' => $timeout,
        ],
    ];
}
