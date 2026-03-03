<?php

declare(strict_types=1);

$records = get_list_data($listKey);
$lastResult = $_SESSION['last_api_result'] ?? null;
$tipoLabel = static function (string $tipo): string {
    $t = strtoupper(trim($tipo));
    if ($t === 'PN') {
        return 'Persona Natural';
    }
    if ($t === 'PJ') {
        return 'Persona Juridica';
    }
    return $tipo;
};
$parseCompound = static function (string $value, bool $splitBySlash): array {
    $value = trim($value);
    if ($value === '') {
        return [];
    }

    $rows = [];
    $sections = $splitBySlash ? (preg_split('/\s*\/\s*/u', $value) ?: []) : [$value];
    foreach ($sections as $section) {
        $section = trim($section);
        if ($section === '') {
            continue;
        }

        $field = 'detalle';
        $content = $section;
        if (str_contains($section, ':')) {
            [$possibleField, $possibleContent] = explode(':', $section, 2);
            $field = trim($possibleField) !== '' ? trim($possibleField) : 'detalle';
            $content = trim($possibleContent);
        }

        $chunks = preg_split('/\|+/u', $content) ?: [];
        if (empty($chunks)) {
            $chunks = [$content];
        }

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk, " \t\n\r\0\x0B,;");
            if ($chunk === '') {
                continue;
            }

            $parts = preg_split('/\s*,\s*/u', $chunk) ?: [];
            $foundKeyValue = false;
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                if (str_contains($part, ':')) {
                    [$k, $v] = explode(':', $part, 2);
                    $rows[] = [
                        'campo' => $field,
                        'key' => trim($k),
                        'valor' => trim($v),
                    ];
                    $foundKeyValue = true;
                } else {
                    $rows[] = [
                        'campo' => $field,
                        'key' => 'valor',
                        'valor' => $part,
                    ];
                }
            }

            if (!$foundKeyValue && empty($parts)) {
                $rows[] = [
                    'campo' => $field,
                    'key' => 'valor',
                    'valor' => $chunk,
                ];
            }
        }
    }

    usort(
        $rows,
        static function (array $a, array $b): int {
            $cmpField = strcmp(strtolower((string)$a['campo']), strtolower((string)$b['campo']));
            if ($cmpField !== 0) {
                return $cmpField;
            }
            $cmpKey = strcmp(strtolower((string)$a['key']), strtolower((string)$b['key']));
            if ($cmpKey !== 0) {
                return $cmpKey;
            }
            return strcmp((string)$a['valor'], (string)$b['valor']);
        }
    );

    return $rows;
};

$parseNoDocumento = static function (string $value): array {
    $value = trim($value);
    if ($value === '') {
        return [];
    }

    $rows = [];
    $items = preg_split('/\|+/u', $value) ?: [];
    foreach ($items as $item) {
        $item = trim($item, " \t\n\r\0\x0B,;");
        if ($item === '') {
            continue;
        }

        $doc = [
            'tipo' => '',
            'numero' => '',
            'pais' => '',
            'expiracion' => '',
            'emision' => '',
        ];

        $parts = preg_split('/\s*,\s*/u', $item) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (!str_contains($part, ':')) {
                continue;
            }
            [$k, $v] = explode(':', $part, 2);
            $key = strtolower(trim($k));
            $val = trim($v);

            if ($key === 'idtype' || $key === 'tipo') {
                $doc['tipo'] = $val;
            } elseif ($key === 'idnumber' || $key === 'numero' || $key === 'número') {
                $doc['numero'] = $val;
            } elseif ($key === 'idcountry' || $key === 'pais' || $key === 'país' || $key === 'country') {
                $doc['pais'] = $val;
            } elseif ($key === 'expirationdate' || $key === 'fecha de expiracion' || $key === 'fecha de expiración') {
                $doc['expiracion'] = $val;
            } elseif ($key === 'issuedate' || $key === 'fecha de emision' || $key === 'fecha de emisión') {
                $doc['emision'] = $val;
            }
        }

        $rows[] = $doc;
    }

    usort(
        $rows,
        static function (array $a, array $b): int {
            return strcmp(strtolower((string)$a['tipo']), strtolower((string)$b['tipo']));
        }
    );

    return $rows;
};

$parseInfoRegistroStructured = static function (string $value): array {
    $value = trim($value);
    if ($value === '') {
        return [];
    }

    $rows = [];
    $blocks = preg_split('/\s\/\s/u', $value) ?: [];
    foreach ($blocks as $index => $block) {
        $block = trim($block, " \t\n\r\0\x0B,;");
        if ($block === '') {
            continue;
        }

        $campo = 'detalle';
        $valor = $block;
        if (str_contains($block, ':')) {
            [$left, $right] = explode(':', $block, 2);
            $campo = trim($left) !== '' ? trim($left) : 'detalle';
            $valor = trim($right);
        }
        $valor = rtrim($valor, " |\t\n\r\0\x0B");

        $rows[] = [
            'campo' => $campo,
            'valor' => $valor,
        ];
    }

    return $rows;
};

$parseInfoValorTablaInterna = static function (string $value): array {
    $value = trim($value);
    if ($value === '' || !str_contains($value, '|') || !str_contains($value, ':')) {
        return [];
    }

    $rows = [];
    $blocks = preg_split('/\|+/u', $value) ?: [];
    foreach ($blocks as $block) {
        $block = trim($block, " \t\n\r\0\x0B,;");
        if ($block === '') {
            continue;
        }

        $row = [
            'city' => '',
            'stateOrProvince' => '',
            'country' => '',
            'postalCode' => '',
            'address' => '',
        ];
        $found = false;

        $parts = preg_split('/\s*,\s*/u', $block) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || !str_contains($part, ':')) {
                continue;
            }

            [$k, $v] = explode(':', $part, 2);
            $key = strtolower(trim($k));
            $val = trim($v);

            if ($key === 'city') {
                $row['city'] = $val;
                $found = true;
            } elseif ($key === 'stateorprovince') {
                $row['stateOrProvince'] = $val;
                $found = true;
            } elseif ($key === 'country') {
                $row['country'] = $val;
                $found = true;
            } elseif ($key === 'postalcode') {
                $row['postalCode'] = $val;
                $found = true;
            } elseif ($key === 'address') {
                $row['address'] = $val;
                $found = true;
            }
        }

        if ($found) {
            $rows[] = $row;
        }
    }

    return $rows;
};

$normalizeDisplayValue = static function (string $value): string {
    $formatDateToken = static function (string $token): string {
        $token = trim($token);
        if ($token === '') {
            return $token;
        }
        if ($token === '0000-00-00' || $token === '0000/00/00') {
            return $token;
        }

        if ((bool)preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}$/', $token)) {
            $normalized = str_replace('/', '-', $token);
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $normalized);
            if ($date !== false) {
                return $date->format('d/m/Y');
            }
            return $token;
        }

        if ((bool)preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}$/', $token)) {
            $normalized = str_replace('-', '/', $token);
            $date = DateTimeImmutable::createFromFormat('d/m/Y', $normalized);
            if ($date !== false) {
                return $date->format('d/m/Y');
            }
            return $token;
        }

        if ((bool)preg_match('/^\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}/', $token)) {
            $datePart = substr($token, 0, 10);
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $datePart);
            if ($date !== false) {
                return $date->format('d/m/Y');
            }
        }

        return $token;
    };

    $value = preg_replace('/\s*,\s*/u', ', ', $value) ?? $value;
    $value = preg_replace_callback(
        '/\b(male|female)\b/ui',
        static function (array $m): string {
            $word = strtolower((string)$m[1]);
            return $word === 'male' ? 'Masculino' : 'Femenino';
        },
        $value
    ) ?? $value;
    $value = preg_replace_callback(
        '/\b\d{4}[-\/]\d{2}[-\/]\d{2}(?:[T\s]\d{2}:\d{2}(?::\d{2})?(?:\.\d+)?)?(?:Z|[+\-]\d{2}:?\d{2})?\b|\b\d{2}[-\/]\d{2}[-\/]\d{4}\b/u',
        static function (array $m) use ($formatDateToken): string {
            return $formatDateToken((string)$m[0]);
        },
        $value
    ) ?? $value;

    return $value;
};
?>
<div class="card shadow-sm mb-4">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <h2 class="h5 mb-1"><?= e($title) ?></h2>
      <p class="text-muted mb-0">Gestione registros internos y ejecute consulta AML Advantage.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoRegistro">
      <i class="bi bi-plus-circle me-1"></i>Nuevo Registro
    </button>
  </div>
</div>

<?php if (is_array($lastResult) && ($lastResult['list'] ?? '') === $listKey): ?>
  <?php $apiR = $lastResult['result'] ?? []; ?>
  <?php $resultCollapseId = 'ultimoResultadoApi_' . $listKey; ?>
  <?php
  $responseJson = $apiR['response_json'] ?? null;
  $numRegistros = (is_array($responseJson) && array_key_exists('num_registros', $responseJson))
      ? (int)$responseJson['num_registros']
      : null;
  $apiDatos = (is_array($responseJson) && isset($responseJson['datos']) && is_array($responseJson['datos'])) ? $responseJson['datos'] : [];
  ?>
  <div class="card mb-4 border-<?= !empty($apiR['ok']) ? 'success' : 'warning' ?>">
    <div class="card-body">
      <div class="d-flex justify-content-end align-items-center gap-2 mb-2">
        <h3 class="h6 mb-0">Resultados de la última consulta (<?= e((string)($numRegistros ?? count($apiDatos))) ?>) &nbsp; &nbsp; </h3>
        <button
          class="btn btn-sm btn-outline-secondary js-toggle-resultados"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#<?= e($resultCollapseId) ?>"
          data-target-id="<?= e($resultCollapseId) ?>"
          aria-expanded="false"
        >
          <i class="bi bi-eye me-1"></i><span>Ver resultados</span>
        </button>
      </div>
      <div class="collapse" id="<?= e($resultCollapseId) ?>">
        <?php if (!empty($apiDatos)): ?>
          <?php $resultContextPrefix = 'single_' . $listKey . '_'; ?>
          <?php require __DIR__ . '/partials/api_matches_table.php'; ?>
        <?php else: ?>
          <?php if ($numRegistros === 0): ?>
            <div class="alert alert-secondary mb-0">Sin resultados para este registro.</div>
          <?php else: ?>
            <pre class="bg-dark text-white p-2 small overflow-auto mb-0"><?= e((string)($apiR['response_raw'] ?? '')) ?></pre>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Nombres</th>
          <th>Apellidos</th>
          <th>Nombre de Empresa</th>
          <th>Documento</th>
          <th class="text-end" style="min-width: 340px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr><td colspan="6" class="text-center text-muted">Sin registros</td></tr>
        <?php else: ?>
          <?php foreach ($records as $row): ?>
            <tr>
              <td><?= e($tipoLabel((string)$row['tipo'])) ?></td>
              <td><?= e((string)$row['nombres']) ?></td>
              <td><?= e((string)$row['apellidos']) ?></td>
              <td><?= e((string)$row['nombre_completo']) ?></td>
              <td><?= e((string)($row['documento'] ?? '')) ?></td>
              <td class="text-end" style="white-space: nowrap;">
                <?php
                $tipo = strtoupper((string)($row['tipo'] ?? 'PN'));
                $modo = $tipo === 'PJ' ? 'B' : 'A';
                $doc = (string)($row['documento'] ?? '');
                $nombres = (string)($row['nombres'] ?? '');
                $apellidos = (string)($row['apellidos'] ?? '');
                $nombreCompleto = (string)($row['nombre_completo'] ?? '');
                $alias = (string)($row['alias'] ?? '');
                $nacionalidad = (string)($row['nacionalidad'] ?? '');
                $paisDomicilio = (string)($row['pais_domicilio'] ?? '');
                $comentarios = (string)($row['comentarios'] ?? '');
                $representanteLegal = (string)($row['representante_legal'] ?? '');
                if ($modo === 'B' && trim($nombreCompleto) === '') {
                    $nombreCompleto = trim($nombres . ' ' . $apellidos);
                }
                ?>
                <button
                  type="button"
                  class="btn btn-outline-primary btn-sm js-open-edit-modal"
                  data-bs-toggle="modal"
                  data-bs-target="#modalEditarRegistro"
                  data-record-id="<?= e((string)$row['id']) ?>"
                  data-tipo="<?= e($tipo) ?>"
                  data-nombres="<?= e($nombres) ?>"
                  data-apellidos="<?= e($apellidos) ?>"
                  data-nombre-completo="<?= e($nombreCompleto) ?>"
                  data-documento="<?= e($doc) ?>"
                  data-alias="<?= e($alias) ?>"
                  data-nacionalidad="<?= e($nacionalidad) ?>"
                  data-pais-domicilio="<?= e($paisDomicilio) ?>"
                  data-comentarios="<?= e($comentarios) ?>"
                  data-representante-legal="<?= e($representanteLegal) ?>"
                >
                  <i class="bi bi-pencil-square me-1"></i>Editar
                </button>
                <form method="post" class="d-inline ms-1">
                  <input type="hidden" name="action" value="delete_record">
                  <input type="hidden" name="return_page" value="<?= e($listKey) ?>">
                  <input type="hidden" name="list_key" value="<?= e($listKey) ?>">
                  <input type="hidden" name="record_id" value="<?= e((string)$row['id']) ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Eliminar
                  </button>
                </form>
                <form method="post" class="d-inline ms-1">
                  <input type="hidden" name="action" value="consulta_api">
                  <input type="hidden" name="return_page" value="<?= e($listKey) ?>">
                  <input type="hidden" name="list_key" value="<?= e($listKey) ?>">
                  <input type="hidden" name="record_id" value="<?= e((string)$row['id']) ?>">
                  <input type="hidden" name="modo" value="<?= e($modo) ?>">
                  <input type="hidden" name="nombres" value="<?= e($modo === 'A' ? $nombres : '') ?>">
                  <input type="hidden" name="apellidos" value="<?= e($modo === 'A' ? $apellidos : '') ?>">
                  <input type="hidden" name="nombre_completo" value="<?= e($modo === 'B' ? $nombreCompleto : '') ?>">
                  <input type="hidden" name="documento" value="<?= e($doc) ?>">
                  <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-search me-1"></i>Consultar API
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalEditarRegistro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="update_record">
      <input type="hidden" name="return_page" value="<?= e($listKey) ?>">
      <input type="hidden" name="list_key" value="<?= e($listKey) ?>">
      <input type="hidden" name="record_id" id="edit_record_id" value="">
      <div class="modal-header">
        <h5 class="modal-title">Editar Registro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Tipo</label>
          <select class="form-select" name="tipo" id="edit_tipo" required>
            <option value="PN">Persona Natural</option>
            <option value="PJ">Persona Juridica</option>
          </select>
        </div>

        <div id="bloque_edit_pn">
          <div class="mb-3">
            <label class="form-label">Nombres</label>
            <input class="form-control js-validate-name" name="nombres" id="edit_nombres" maxlength="120">
          </div>
          <div class="mb-3">
            <label class="form-label">Apellidos</label>
            <input class="form-control js-validate-name" name="apellidos" id="edit_apellidos" maxlength="120">
          </div>
          <div class="mb-3">
            <label class="form-label">Alias</label>
            <input class="form-control" name="alias" id="edit_alias" maxlength="120">
          </div>
          <div class="mb-3">
            <label class="form-label">Comentarios</label>
            <textarea class="form-control" name="comentarios" id="edit_comentarios" rows="2" maxlength="500"></textarea>
          </div>
        </div>

        <div id="bloque_edit_pj" class="d-none">
          <div class="mb-3">
            <label class="form-label">Nombre completo</label>
            <input class="form-control js-validate-name" name="nombre_completo" id="edit_nombre_completo" maxlength="120">
          </div>
          <div class="mb-3">
            <label class="form-label">Representante Legal</label>
            <input class="form-control" name="representante_legal" id="edit_representante_legal" maxlength="120">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Nacionalidad</label>
          <input class="form-control" name="nacionalidad" id="edit_nacionalidad" maxlength="120">
        </div>

        <div class="mb-3">
          <label class="form-label">Pais domicilio</label>
          <input class="form-control" name="pais_domicilio" id="edit_pais_domicilio" maxlength="120">
        </div>

        <div class="mb-0">
          <label class="form-label">Documento (opcional)</label>
          <input class="form-control js-validate-doc" name="documento" id="edit_documento" maxlength="40">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalNuevoRegistro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="create_record">
      <input type="hidden" name="return_page" value="<?= e($listKey) ?>">
      <input type="hidden" name="list_key" value="<?= e($listKey) ?>">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Registro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Tipo</label>
          <select class="form-select" name="tipo" id="nuevo_tipo" required>
            <option value="PN">Persona Natural</option>
            <option value="PJ">Persona Juridica</option>
          </select>
        </div>

        <div id="bloque_nuevo_pn">
          <div class="mb-3">
            <label class="form-label">Nombres</label>
            <input class="form-control js-validate-name" name="nombres" id="nuevo_nombres" maxlength="120">
          </div>
          <div class="mb-3">
            <label class="form-label">Apellidos</label>
            <input class="form-control js-validate-name" name="apellidos" id="nuevo_apellidos" maxlength="120">
          </div>
          <div class="mb-3">
            <label class="form-label">Alias</label>
            <input class="form-control" name="alias" id="nuevo_alias" maxlength="120">
          </div>
          <div class="mb-3">
            <label class="form-label">Comentarios</label>
            <textarea class="form-control" name="comentarios" id="nuevo_comentarios" rows="2" maxlength="500"></textarea>
          </div>
        </div>

        <div id="bloque_nuevo_pj" class="d-none">
          <div class="mb-3">
            <label class="form-label">Nombre completo</label>
            <input class="form-control js-validate-name" name="nombre_completo" id="nuevo_nombre_completo" maxlength="120">
          </div>
          <div class="mb-3">
            <label class="form-label">Representante Legal</label>
            <input class="form-control" name="representante_legal" id="nuevo_representante_legal" maxlength="120">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Nacionalidad</label>
          <input class="form-control" name="nacionalidad" id="nuevo_nacionalidad" maxlength="120">
        </div>

        <div class="mb-3">
          <label class="form-label">Pais domicilio</label>
          <input class="form-control" name="pais_domicilio" id="nuevo_pais_domicilio" maxlength="120">
        </div>

        <div class="mb-0">
          <label class="form-label">Documento (opcional)</label>
          <input class="form-control js-validate-doc" name="documento" maxlength="40">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
