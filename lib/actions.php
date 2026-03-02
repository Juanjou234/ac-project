<?php

declare(strict_types=1);

function handle_post_action(string $fallbackPage): void
{
    $action = trim((string)($_POST['action'] ?? ''));
    $returnPage = trim((string)($_POST['return_page'] ?? $fallbackPage));
    if ($returnPage === '') {
        $returnPage = $fallbackPage;
    }

    switch ($action) {
        case 'reset_demo':
            $savedConfig = $_SESSION['api_config'] ?? [
                'url' => '',
                'api_key' => '',
                'header_name' => '',
                'timeout_ms' => 5000,
            ];
            session_unset();
            $_SESSION['api_config'] = is_array($savedConfig) ? $savedConfig : [
                'url' => '',
                'api_key' => '',
                'header_name' => '',
                'timeout_ms' => 5000,
            ];
            $_SESSION['data_custom'] = [
                'erp_clientes' => [],
                'crm_contactos' => [],
                'crm_empresas' => [],
                'terceros' => [],
            ];
            $_SESSION['data_overrides'] = [
                'erp_clientes' => [],
                'crm_contactos' => [],
                'crm_empresas' => [],
                'terceros' => [],
            ];
            $_SESSION['data_deleted'] = [
                'erp_clientes' => [],
                'crm_contactos' => [],
                'crm_empresas' => [],
                'terceros' => [],
            ];
            $_SESSION['history'] = [];
            $_SESSION['batch_last'] = [];
            unset($_SESSION['last_api_result'], $_SESSION['flashes']);
            add_flash('info', 'Demo reseteada. Configuracion API conservada.');
            header('Location: index.php?page=dashboard');
            exit;

        case 'save_config':
            $valid = validate_config_input($_POST);
            if (!$valid['ok']) {
                add_flash('danger', implode(' ', $valid['errors']));
                redirect_page('configuracion');
            }
            $_SESSION['api_config'] = $valid['data'];
            add_flash('success', 'Configuracion guardada.');
            redirect_page('configuracion');
            break;

        case 'verify_connection':
            $valid = validate_config_input($_POST);
            if (!$valid['ok']) {
                add_flash('danger', implode(' ', $valid['errors']));
                redirect_page('configuracion');
            }
            $_SESSION['api_config'] = $valid['data'];
            $result = call_consulta_listas($_SESSION['api_config'], []);
            $rawLower = strtolower((string)($result['response_raw'] ?? ''));

            if (str_contains($rawLower, 'debe ingresar al menos uno')) {
                add_flash('success', '¡Conexion exitosa!');
            } elseif (str_contains($rawLower, 'api key invalida') || str_contains($rawLower, 'api key inválida') || $result['error_type'] === 'auth') {
                add_flash('danger', 'Credenciales invalidas.');
            } elseif ($result['error_type'] === 'timeout' || $result['error_type'] === 'network' || $result['error_type'] === 'parse' || (int)$result['http_code'] === 404) {
                add_flash('danger', 'Conexion fallo (timeout/network/404/no JSON).');
            } elseif ($result['ok']) {
                add_flash('success', 'Conexion exitosa.');
            } else {
                add_flash('warning', 'Conexion respondio con estado no esperado.');
            }

            redirect_page('configuracion');
            break;

        case 'create_record':
            $listKey = trim((string)($_POST['list_key'] ?? ''));
            if (!valid_list_key($listKey)) {
                add_flash('danger', 'Lista invalida.');
                redirect_page($returnPage);
            }

            $valid = validate_record_input($_POST);
            if (!$valid['ok']) {
                add_flash('danger', implode(' ', $valid['errors']));
                redirect_page($returnPage);
            }

            $listData = get_list_data($listKey);
            $maxOrder = 0;
            foreach ($listData as $row) {
                $maxOrder = max($maxOrder, (int)($row['orden'] ?? 0));
            }

            $newId = 'CUST-' . uniqid();
            $_SESSION['data_custom'][$listKey][] = [
                'id' => $newId,
                'origen_lista' => list_label($listKey),
                'tipo' => $valid['data']['tipo'],
                'nombres' => $valid['data']['nombres'],
                'apellidos' => $valid['data']['apellidos'],
                'nombre_completo' => $valid['data']['nombre_completo'],
                'documento' => $valid['data']['documento'],
                'orden' => $maxOrder + 1,
                'meta' => [],
            ];

            add_flash('success', 'Registro creado correctamente.');

            $tipo = strtoupper((string)$valid['data']['tipo']);
            $doc = (string)($valid['data']['documento'] ?? '');
            $modo = $tipo === 'PJ' ? 'B' : 'A';
            if ($modo === 'A') {
                $postData = [
                    'modo' => 'A',
                    'nombres' => (string)$valid['data']['nombres'],
                    'apellidos' => (string)$valid['data']['apellidos'],
                    'nombre_completo' => '',
                    'documento' => $doc,
                ];
            } else {
                $postData = [
                    'modo' => 'B',
                    'nombres' => '',
                    'apellidos' => '',
                    'nombre_completo' => (string)$valid['data']['nombre_completo'],
                    'documento' => $doc,
                ];
            }

            $autoValid = validate_consulta_input($postData);
            if (!$autoValid['ok']) {
                add_flash('warning', 'Registro guardado, pero la consulta automatica no paso validacion: ' . implode(' ', $autoValid['errors']));
                redirect_page($returnPage);
            }

            $autoResult = call_consulta_listas($_SESSION['api_config'], $autoValid['payload']);
            $_SESSION['history'][] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'lista' => $listKey,
                'registro_id' => $newId,
                'modo' => $autoValid['mode'],
                'payload' => $autoValid['payload'],
                'url' => (string)($_SESSION['api_config']['url'] ?? ''),
                'resultado' => [
                    'ok' => $autoResult['ok'],
                    'http_code' => $autoResult['http_code'],
                    'error_type' => $autoResult['error_type'],
                    'error_message' => $autoResult['error_message'],
                ],
                'tiempo' => $autoResult['elapsed_ms'],
            ];

            $_SESSION['last_api_result'] = [
                'list' => $listKey,
                'record_id' => $newId,
                'result' => $autoResult,
            ];

            if ($autoResult['ok']) {
                // add_flash('success', 'Consulta automatica OK (HTTP ' . $autoResult['http_code'] . ').');
            } else {
                add_flash('warning', 'Registro guardado. Consulta automatica FAIL: ' . $autoResult['error_type'] . ' - ' . $autoResult['error_message']);
            }
            redirect_page($returnPage);
            break;

        case 'update_record':
            $listKey = trim((string)($_POST['list_key'] ?? ''));
            $recordId = trim((string)($_POST['record_id'] ?? ''));
            if (!valid_list_key($listKey) || $recordId === '') {
                add_flash('danger', 'Datos invalidos para editar.');
                redirect_page($returnPage);
            }

            $existing = get_record_by_id($listKey, $recordId);
            if ($existing === null) {
                add_flash('danger', 'No se encontro el registro a editar.');
                redirect_page($returnPage);
            }

            $valid = validate_record_input($_POST);
            if (!$valid['ok']) {
                add_flash('danger', implode(' ', $valid['errors']));
                redirect_page($returnPage);
            }

            if (!isset($_SESSION['data_overrides'][$listKey]) || !is_array($_SESSION['data_overrides'][$listKey])) {
                $_SESSION['data_overrides'][$listKey] = [];
            }

            $_SESSION['data_overrides'][$listKey][$recordId] = [
                'tipo' => $valid['data']['tipo'],
                'nombres' => $valid['data']['nombres'],
                'apellidos' => $valid['data']['apellidos'],
                'nombre_completo' => $valid['data']['nombre_completo'],
                'documento' => $valid['data']['documento'],
            ];

            add_flash('success', 'Registro actualizado correctamente.');

            $tipo = strtoupper((string)$valid['data']['tipo']);
            $doc = (string)($valid['data']['documento'] ?? '');
            $modo = $tipo === 'PJ' ? 'B' : 'A';
            if ($modo === 'A') {
                $postData = [
                    'modo' => 'A',
                    'nombres' => (string)$valid['data']['nombres'],
                    'apellidos' => (string)$valid['data']['apellidos'],
                    'nombre_completo' => '',
                    'documento' => $doc,
                ];
            } else {
                $postData = [
                    'modo' => 'B',
                    'nombres' => '',
                    'apellidos' => '',
                    'nombre_completo' => (string)$valid['data']['nombre_completo'],
                    'documento' => $doc,
                ];
            }

            $autoValid = validate_consulta_input($postData);
            if (!$autoValid['ok']) {
                add_flash('warning', 'Registro actualizado, pero la consulta automatica no paso validacion: ' . implode(' ', $autoValid['errors']));
                redirect_page($returnPage);
            }

            $autoResult = call_consulta_listas($_SESSION['api_config'], $autoValid['payload']);
            $_SESSION['history'][] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'lista' => $listKey,
                'registro_id' => $recordId,
                'modo' => $autoValid['mode'],
                'payload' => $autoValid['payload'],
                'url' => (string)($_SESSION['api_config']['url'] ?? ''),
                'resultado' => [
                    'ok' => $autoResult['ok'],
                    'http_code' => $autoResult['http_code'],
                    'error_type' => $autoResult['error_type'],
                    'error_message' => $autoResult['error_message'],
                ],
                'tiempo' => $autoResult['elapsed_ms'],
            ];

            $_SESSION['last_api_result'] = [
                'list' => $listKey,
                'record_id' => $recordId,
                'result' => $autoResult,
            ];

            if (!$autoResult['ok']) {
                add_flash('warning', 'Registro actualizado. Consulta automatica FAIL: ' . $autoResult['error_type'] . ' - ' . $autoResult['error_message']);
            }
            redirect_page($returnPage);
            break;

        case 'delete_record':
            $listKey = trim((string)($_POST['list_key'] ?? ''));
            $recordId = trim((string)($_POST['record_id'] ?? ''));
            if (!valid_list_key($listKey) || $recordId === '') {
                add_flash('danger', 'Datos invalidos para eliminar.');
                redirect_page($returnPage);
            }

            $existing = get_record_by_id($listKey, $recordId);
            if ($existing === null) {
                add_flash('warning', 'El registro ya no existe.');
                redirect_page($returnPage);
            }

            if (!isset($_SESSION['data_deleted'][$listKey]) || !is_array($_SESSION['data_deleted'][$listKey])) {
                $_SESSION['data_deleted'][$listKey] = [];
            }
            if (!in_array($recordId, $_SESSION['data_deleted'][$listKey], true)) {
                $_SESSION['data_deleted'][$listKey][] = $recordId;
            }

            if (isset($_SESSION['data_overrides'][$listKey][$recordId])) {
                unset($_SESSION['data_overrides'][$listKey][$recordId]);
            }

            if (isset($_SESSION['last_api_result']['list'], $_SESSION['last_api_result']['record_id'])
                && (string)$_SESSION['last_api_result']['list'] === $listKey
                && (string)$_SESSION['last_api_result']['record_id'] === $recordId) {
                unset($_SESSION['last_api_result']);
            }

            add_flash('success', 'Registro eliminado correctamente.');
            redirect_page($returnPage);
            break;

        case 'consulta_api':
            $listKey = trim((string)($_POST['list_key'] ?? ''));
            $recordId = trim((string)($_POST['record_id'] ?? ''));
            if (!valid_list_key($listKey) || $recordId === '') {
                add_flash('danger', 'Datos de consulta invalidos.');
                redirect_page($returnPage);
            }

            $valid = validate_consulta_input($_POST);
            if (!$valid['ok']) {
                add_flash('danger', implode(' ', $valid['errors']));
                redirect_page($returnPage);
            }

            $record = get_record_by_id($listKey, $recordId);
            if ($record === null) {
                add_flash('danger', 'No se encontro el registro seleccionado.');
                redirect_page($returnPage);
            }

            $result = call_consulta_listas($_SESSION['api_config'], $valid['payload']);
            $_SESSION['history'][] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'lista' => $listKey,
                'registro_id' => $recordId,
                'modo' => $valid['mode'],
                'payload' => $valid['payload'],
                'url' => (string)($_SESSION['api_config']['url'] ?? ''),
                'resultado' => [
                    'ok' => $result['ok'],
                    'http_code' => $result['http_code'],
                    'error_type' => $result['error_type'],
                    'error_message' => $result['error_message'],
                ],
                'tiempo' => $result['elapsed_ms'],
            ];

            $_SESSION['last_api_result'] = [
                'list' => $listKey,
                'record_id' => $recordId,
                'result' => $result,
            ];

            if ($result['ok']) {
                add_flash('success', 'Consulta realizada exitosamente.');
            } else {
                add_flash('warning', 'Consulta API FAIL: ' . $result['error_type'] . ' - ' . $result['error_message']);
            }
            redirect_page($returnPage);
            break;

        case 'run_batch':
            $listKey = trim((string)($_POST['list_key'] ?? ''));
            $recordIds = $_POST['record_ids'] ?? [];
            $maxAllowed = (int)($_POST['max_lote'] ?? 10);
            if ($maxAllowed !== 20) {
                $maxAllowed = 10;
            }

            if (!valid_list_key($listKey)) {
                add_flash('danger', 'Lista invalida para lote.');
                redirect_page('lote');
            }
            if (!is_array($recordIds) || empty($recordIds)) {
                add_flash('danger', 'Debe seleccionar al menos un registro.');
                redirect_page('lote');
            }
            if (count($recordIds) > $maxAllowed) {
                add_flash('danger', 'Supera el limite maximo permitido (' . $maxAllowed . ').');
                redirect_page('lote');
            }

            $rows = [];
            $ok = 0;
            $fail = 0;
            $start = microtime(true);
            foreach ($recordIds as $idValue) {
                $recordId = trim((string)$idValue);
                if ($recordId === '') {
                    continue;
                }
                $record = get_record_by_id($listKey, $recordId);
                if ($record === null) {
                    $rows[] = [
                        'id' => $recordId,
                        'mode' => '',
                        'status' => 'FAIL',
                        'http_code' => 0,
                        'elapsed_ms' => 0,
                        'error' => 'Registro no encontrado',
                        'response_json' => null,
                        'response_raw' => '',
                        'nombres' => '',
                        'apellidos' => '',
                        'nombre_completo' => '',
                        'documento' => '',
                    ];
                    $fail++;
                    continue;
                }

                $doc = trim((string)($record['documento'] ?? ''));
                $nombresRecord = trim((string)($record['nombres'] ?? ''));
                $apellidosRecord = trim((string)($record['apellidos'] ?? ''));
                $nombreCompletoRecord = trim((string)($record['nombre_completo'] ?? ''));
                if ($nombreCompletoRecord === '') {
                    $nombreCompletoRecord = trim($nombresRecord . ' ' . $apellidosRecord);
                }
                $recordType = strtoupper(trim((string)($record['tipo'] ?? 'PN')));
                $mode = $recordType === 'PJ' ? 'B' : 'A';
                if ($mode === 'A') {
                    $postData = [
                        'modo' => 'A',
                        'nombres' => (string)($record['nombres'] ?? ''),
                        'apellidos' => (string)($record['apellidos'] ?? ''),
                        'nombre_completo' => '',
                        'documento' => $doc,
                    ];
                } else {
                    $fullName = trim((string)($record['nombre_completo'] ?? ''));
                    if ($fullName === '') {
                        $fullName = trim(((string)($record['nombres'] ?? '')) . ' ' . ((string)($record['apellidos'] ?? '')));
                    }
                    $postData = [
                        'modo' => 'B',
                        'nombres' => '',
                        'apellidos' => '',
                        'nombre_completo' => $fullName,
                        'documento' => $doc,
                    ];
                }

                $valid = validate_consulta_input($postData);
                if (!$valid['ok']) {
                    $rows[] = [
                        'id' => $recordId,
                        'mode' => $mode,
                        'status' => 'FAIL',
                        'http_code' => 0,
                        'elapsed_ms' => 0,
                        'error' => implode(' ', $valid['errors']),
                        'response_json' => null,
                        'response_raw' => '',
                        'nombres' => $nombresRecord,
                        'apellidos' => $apellidosRecord,
                        'nombre_completo' => $nombreCompletoRecord,
                        'documento' => $doc,
                    ];
                    $fail++;
                    continue;
                }

                $result = call_consulta_listas($_SESSION['api_config'], $valid['payload']);
                $status = $result['ok'] ? 'OK' : 'FAIL';
                if ($status === 'OK') {
                    $ok++;
                } else {
                    $fail++;
                }

                $rows[] = [
                    'id' => $recordId,
                    'mode' => $valid['mode'],
                    'status' => $status,
                    'http_code' => $result['http_code'],
                    'elapsed_ms' => $result['elapsed_ms'],
                    'error' => $result['ok'] ? '' : ($result['error_type'] . ': ' . $result['error_message']),
                    'response_json' => $result['response_json'] ?? null,
                    'response_raw' => $result['response_raw'] ?? '',
                    'nombres' => $nombresRecord,
                    'apellidos' => $apellidosRecord,
                    'nombre_completo' => $nombreCompletoRecord,
                    'documento' => $doc,
                ];

                $_SESSION['history'][] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'lista' => $listKey,
                    'registro_id' => $recordId,
                    'modo' => $valid['mode'],
                    'payload' => $valid['payload'],
                    'url' => (string)($_SESSION['api_config']['url'] ?? ''),
                    'resultado' => [
                        'ok' => $result['ok'],
                        'http_code' => $result['http_code'],
                        'error_type' => $result['error_type'],
                        'error_message' => $result['error_message'],
                    ],
                    'tiempo' => $result['elapsed_ms'],
                ];
            }

            $_SESSION['batch_last'] = [
                'list' => $listKey,
                'mode' => 'AUTO',
                'max' => $maxAllowed,
                'rows' => $rows,
                'ok' => $ok,
                'fail' => $fail,
                'total_time_ms' => (int)round((microtime(true) - $start) * 1000),
                'ran_at' => date('Y-m-d H:i:s'),
            ];
            if ($fail > 0) {
                add_flash('info', 'Lote ejecutado.');
            } else {
                add_flash('success', 'Consultas exitosas : ' . $ok);
            }
            redirect_page('lote');
            break;

        default:
            redirect_page($fallbackPage);
    }
}
