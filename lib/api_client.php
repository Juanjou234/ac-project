<?php

declare(strict_types=1);

function call_consulta_listas(array $config, array $payload): array
{
    $url = trim((string)($config['url'] ?? ''));
    $apiKey = trim((string)($config['api_key'] ?? ''));
    $headerName = trim((string)($config['header_name'] ?? ''));
    $usuario = trim((string)($config['usuario'] ?? ''));
    $clave = trim((string)($config['clave'] ?? ''));
    $timeoutMs = (int)($config['timeout_ms'] ?? 5000);

    if ($url === '') {
        return [
            'ok' => false,
            'http_code' => 0,
            'elapsed_ms' => 0,
            'error_type' => 'validation',
            'error_message' => 'URL vacia.',
            'response_raw' => '',
            'response_json' => null,
        ];
    }
    $usesQueryCredentials = ($usuario !== '' || $clave !== '');
    if ($usesQueryCredentials && ($usuario === '' || $clave === '')) {
        return [
            'ok' => false,
            'http_code' => 0,
            'elapsed_ms' => 0,
            'error_type' => 'validation',
            'error_message' => 'Usuario o clave vacio para autenticacion por query.',
            'response_raw' => '',
            'response_json' => null,
        ];
    }
    if (!$usesQueryCredentials && ($apiKey === '' || $headerName === '')) {
        return [
            'ok' => false,
            'http_code' => 0,
            'elapsed_ms' => 0,
            'error_type' => 'validation',
            'error_message' => 'API Key o Header name vacio.',
            'response_raw' => '',
            'response_json' => null,
        ];
    }
    if ($timeoutMs < 1000) {
        $timeoutMs = 1000;
    }

    $requestUrl = $url;
    if ($usesQueryCredentials) {
        $query = [
            'usuario' => $usuario,
            'clave' => $clave,
        ];
        foreach ($payload as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            if ($v === null || $v === '') {
                continue;
            }
            $query[$k] = (string)$v;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        $requestUrl .= $separator . http_build_query($query);
    }

    $ch = curl_init($requestUrl);
    if ($ch === false) {
        return [
            'ok' => false,
            'http_code' => 0,
            'elapsed_ms' => 0,
            'error_type' => 'network',
            'error_message' => 'No se pudo inicializar cURL.',
            'response_raw' => '',
            'response_json' => null,
        ];
    }

    if ($usesQueryCredentials) {
        curl_setopt_array(
            $ch,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPGET => true,
                CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
                CURLOPT_TIMEOUT_MS => $timeoutMs,
            ]
        );
    } else {
        $headers = [
            'Content-Type: application/json',
            $headerName . ': ' . $apiKey,
        ];
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($jsonPayload === false) {
            return [
                'ok' => false,
                'http_code' => 0,
                'elapsed_ms' => 0,
                'error_type' => 'validation',
                'error_message' => 'No se pudo serializar payload.',
                'response_raw' => '',
                'response_json' => null,
            ];
        }

        curl_setopt_array(
            $ch,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
                CURLOPT_TIMEOUT_MS => $timeoutMs,
            ]
        );
    }

    $start = microtime(true);
    $responseRaw = curl_exec($ch);
    $elapsedMs = (int)round((microtime(true) - $start) * 1000);

    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseRaw === false) {
        $errorType = 'network';
        if ($curlErrno === CURLE_OPERATION_TIMEDOUT) {
            $errorType = 'timeout';
        }

        return [
            'ok' => false,
            'http_code' => $httpCode,
            'elapsed_ms' => $elapsedMs,
            'error_type' => $errorType,
            'error_message' => $curlError !== '' ? $curlError : 'Error de red.',
            'response_raw' => '',
            'response_json' => null,
        ];
    }

    $decoded = json_decode((string)$responseRaw, true);
    $jsonError = json_last_error();

    if ($httpCode === 401 || $httpCode === 403) {
        return [
            'ok' => false,
            'http_code' => $httpCode,
            'elapsed_ms' => $elapsedMs,
            'error_type' => 'auth',
            'error_message' => 'Credenciales invalidas.',
            'response_raw' => (string)$responseRaw,
            'response_json' => is_array($decoded) ? $decoded : null,
        ];
    }

    if ($jsonError !== JSON_ERROR_NONE) {
        return [
            'ok' => false,
            'http_code' => $httpCode,
            'elapsed_ms' => $elapsedMs,
            'error_type' => 'parse',
            'error_message' => 'Respuesta no JSON.',
            'response_raw' => (string)$responseRaw,
            'response_json' => null,
        ];
    }

    if ($httpCode < 200 || $httpCode > 299) {
        $errorMessage = 'Error API';
        if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])) {
            $errorMessage = $decoded['message'];
        }

        return [
            'ok' => false,
            'http_code' => $httpCode,
            'elapsed_ms' => $elapsedMs,
            'error_type' => 'api',
            'error_message' => $errorMessage,
            'response_raw' => (string)$responseRaw,
            'response_json' => $decoded,
        ];
    }

    return [
        'ok' => true,
        'http_code' => $httpCode,
        'elapsed_ms' => $elapsedMs,
        'error_type' => '',
        'error_message' => '',
        'response_raw' => (string)$responseRaw,
        'response_json' => $decoded,
    ];
}
