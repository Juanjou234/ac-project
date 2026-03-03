<?php

declare(strict_types=1);

function handle_post_action(string $fallbackPage): void
{
    $action = trim((string)($_POST['action'] ?? ''));
    $configProfile = trim((string)($_POST['config_profile'] ?? ''));
    $returnPage = trim((string)($_POST['return_page'] ?? $fallbackPage));
    if ($returnPage === '') {
        $returnPage = $fallbackPage;
    }
    $persistApiConfig = static function (array $config): string {
        $mode = (trim((string)($config['usuario'] ?? '')) !== '' || trim((string)($config['clave'] ?? '')) !== '') ? 'query' : 'header';
        if (!isset($_SESSION['api_configs']) || !is_array($_SESSION['api_configs'])) {
            $_SESSION['api_configs'] = [
                'header' => [],
                'query' => [],
                'active' => 'header',
            ];
        }
        $_SESSION['api_configs'][$mode] = $config;
        if (!in_array((string)($_SESSION['api_configs']['active'] ?? ''), ['header', 'query'], true)) {
            $_SESSION['api_configs']['active'] = 'header';
        }
        $_SESSION['api_config'] = $_SESSION['api_configs'][$_SESSION['api_configs']['active']];
        return $mode;
    };
    $flashConnectionResult = static function (array $result): void {
        $rawLower = strtolower((string)($result['response_raw'] ?? ''));
        $json = $result['response_json'] ?? null;
        $errorLower = '';
        if (is_array($json) && isset($json['error']) && is_string($json['error'])) {
            $errorLower = strtolower(trim($json['error']));
        }

        if (
            str_contains($rawLower, 'debe ingresar al menos un dato')
            || str_contains($rawLower, 'debe ingresar al menos uno')
            || str_contains($errorLower, 'debe ingresar al menos un dato')
        ) {
            add_flash('success', 'Conexion exitosa.');
        } elseif (
            str_contains($rawLower, 'usuario o clave incorrecta')
            || str_contains($errorLower, 'usuario o clave incorrecta')
            || str_contains($rawLower, 'api key invalida')
            || $result['error_type'] === 'auth'
        ) {
            add_flash('danger', 'Credenciales invalidas.');
        } elseif ($result['error_type'] === 'timeout' || $result['error_type'] === 'network' || $result['error_type'] === 'parse' || (int)$result['http_code'] === 404) {
            add_flash('danger', 'Conexion fallo (timeout/network/404/no JSON).');
        } elseif ($result['ok']) {
            add_flash('success', 'Conexion exitosa.');
        } else {
            add_flash('warning', 'Conexion respondio con estado no esperado.');
        }
    };
    $prepareConfigInput = static function (array $input, string $profile): array {
        if ($profile === 'header') {
            $input['usuario'] = '';
            $input['clave'] = '';
        } elseif ($profile === 'query') {
            $input['api_key'] = '';
            $input['header_name'] = '';
        }
        return $input;
    };

    switch ($action) {
        case 'reset_demo':
            $savedConfig = $_SESSION['api_config'] ?? [
                'url' => '',
                'api_key' => '',
                'header_name' => '',
                'usuario' => '',
                'clave' => '',
                'timeout_ms' => 5000,
            ];
            $savedConfigs = $_SESSION['api_configs'] ?? null;
            session_unset();
            $defaultConfig = [
                'url' => '',
                'api_key' => '',
                'header_name' => '',
                'usuario' => '',
                'clave' => '',
                'timeout_ms' => 5000,
            ];
            if (is_array($savedConfigs)) {
                $_SESSION['api_configs'] = array_merge(
                    [
                        'header' => $defaultConfig,
                        'query' => $defaultConfig,
                        'active' => 'header',
                    ],
                    $savedConfigs
                );
            } else {
                $savedConfig = is_array($savedConfig) ? array_merge($defaultConfig, $savedConfig) : $defaultConfig;
                $isQuery = trim((string)$savedConfig['usuario']) !== '' || trim((string)$savedConfig['clave']) !== '';
                $_SESSION['api_configs'] = [
                    'header' => $isQuery ? $defaultConfig : $savedConfig,
                    'query' => $isQuery ? $savedConfig : $defaultConfig,
                    'active' => 'header',
                ];
            }
            $_SESSION['api_configs']['active'] = 'header';
            $_SESSION['api_config'] = $_SESSION['api_configs'][$_SESSION['api_configs']['active']];
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
            $input = $prepareConfigInput($_POST, $configProfile);
            $valid = validate_config_input($input);
            if (!$valid['ok']) {
                add_flash('danger', implode(' ', $valid['errors']));
                redirect_page('configuracion');
            }
            $savedMode = in_array($configProfile, ['header', 'query'], true) ? $configProfile : $persistApiConfig($valid['data']);
            if (!isset($_SESSION['api_configs']) || !is_array($_SESSION['api_configs'])) {
                $_SESSION['api_configs'] = ['header' => [], 'query' => [], 'active' => 'header'];
            }
            $_SESSION['api_configs'][$savedMode] = $valid['data'];
            $_SESSION['api_configs']['active'] = $savedMode;
            $_SESSION['api_config'] = $_SESSION['api_configs'][$savedMode];
            add_flash('success', 'Configuracion guardada y establecida como activa.');
            $result = call_consulta_listas($valid['data'], []);
            $flashConnectionResult($result);
            redirect_page('configuracion');
            break;

        case 'verify_connection':
            $input = $prepareConfigInput($_POST, $configProfile);
            $valid = validate_config_input($input);
            if (!$valid['ok']) {
                add_flash('danger', implode(' ', $valid['errors']));
                redirect_page('configuracion');
            }
            $mode = in_array($configProfile, ['header', 'query'], true)
                ? $configProfile
                : ((trim((string)($valid['data']['usuario'] ?? '')) !== '' || trim((string)($valid['data']['clave'] ?? '')) !== '') ? 'query' : 'header');
            $activeMode = (string)($_SESSION['api_configs']['active'] ?? 'header');
            if ($mode !== $activeMode) {
                add_flash('danger', 'Solo puede verificar la configuracion activa.');
                redirect_page('configuracion');
            }
            if (!isset($_SESSION['api_configs']) || !is_array($_SESSION['api_configs'])) {
                $_SESSION['api_configs'] = ['header' => [], 'query' => [], 'active' => 'header'];
            }
            $_SESSION['api_configs'][$mode] = $valid['data'];
            $_SESSION['api_config'] = $_SESSION['api_configs'][$mode];
            $result = call_consulta_listas($valid['data'], []);
            $flashConnectionResult($result);

            redirect_page('configuracion');
            break;
        case 'set_active_config':
            $target = trim((string)($_POST['config_target'] ?? ''));
            if (!in_array($target, ['header', 'query'], true)) {
                add_flash('danger', 'Configuracion objetivo invalida.');
                redirect_page('configuracion');
            }
            $input = $prepareConfigInput($_POST, $target);
            $valid = validate_config_input($input);
            if (!$valid['ok']) {
                add_flash('danger', implode(' ', $valid['errors']));
                redirect_page('configuracion');
            }
            if (!isset($_SESSION['api_configs']) || !is_array($_SESSION['api_configs'])) {
                $_SESSION['api_configs'] = ['header' => [], 'query' => [], 'active' => 'header'];
            }
            $_SESSION['api_configs'][$target] = $valid['data'];
            $_SESSION['api_configs']['active'] = $target;
            $_SESSION['api_config'] = $_SESSION['api_configs'][$target];
            add_flash('success', 'Configuracion guardada y establecida como activa.');
            $result = call_consulta_listas($valid['data'], []);
            $flashConnectionResult($result);
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
                'alias' => $valid['data']['alias'],
                'nacionalidad' => $valid['data']['nacionalidad'],
                'pais_domicilio' => $valid['data']['pais_domicilio'],
                'comentarios' => $valid['data']['comentarios'],
                'representante_legal' => $valid['data']['representante_legal'],
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
                'alias' => $valid['data']['alias'],
                'nacionalidad' => $valid['data']['nacionalidad'],
                'pais_domicilio' => $valid['data']['pais_domicilio'],
                'comentarios' => $valid['data']['comentarios'],
                'representante_legal' => $valid['data']['representante_legal'],
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
            $redirectLote = static function (string $list): void {
                $safeList = valid_list_key($list) ? $list : 'erp_clientes';
                header('Location: index.php?page=lote&list=' . rawurlencode($safeList));
                exit;
            };
            $recordIds = $_POST['record_ids'] ?? [];
            $maxAllowed = (int)($_POST['max_lote'] ?? 10);
            if ($maxAllowed !== 20) {
                $maxAllowed = 10;
            }

            if (!valid_list_key($listKey)) {
                add_flash('danger', 'Lista invalida para lote.');
                $redirectLote('erp_clientes');
            }
            if (!is_array($recordIds) || empty($recordIds)) {
                add_flash('danger', 'Debe seleccionar al menos un registro.');
                $redirectLote($listKey);
            }
            if (count($recordIds) > $maxAllowed) {
                add_flash('danger', 'Supera el limite maximo permitido (' . $maxAllowed . ').');
                $redirectLote($listKey);
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
                add_flash('success', 'Consultas exitosas');
            }
            $redirectLote($listKey);
            break;

        default:
            redirect_page($fallbackPage);
    }
}
