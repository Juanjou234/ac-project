<?php

declare(strict_types=1);

$resultContextPrefix = isset($resultContextPrefix) ? (string)$resultContextPrefix : 'api_';
$matchesDetailMode = isset($matchesDetailMode) ? (string)$matchesDetailMode : 'modal';
$inlineDetails = $matchesDetailMode === 'inline';

$parseNoDocumento = $parseNoDocumento ?? static function (string $value): array {
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
            if ($part === '' || !str_contains($part, ':')) {
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
    return $rows;
};

$parseInfoRegistroStructured = $parseInfoRegistroStructured ?? static function (string $value): array {
    $value = trim($value);
    if ($value === '') {
        return [];
    }
    $rows = [];
    $blocks = preg_split('/\s\/\s/u', $value) ?: [];
    foreach ($blocks as $block) {
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
        $rows[] = ['campo' => $campo, 'valor' => $valor];
    }
    return $rows;
};

$parseInfoValorTablaInterna = $parseInfoValorTablaInterna ?? static function (string $value): array {
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
        $row = ['city' => '', 'stateOrProvince' => '', 'country' => '', 'postalCode' => '', 'address' => ''];
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

$normalizeDisplayValue = $normalizeDisplayValue ?? static function (string $value): string {
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
            return strtolower((string)$m[1]) === 'male' ? 'Masculino' : 'Femenino';
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
<div class="table-responsive">
  <table class="table table-sm align-middle table-hover api-results-table">
    <thead>
      <tr>
        <th>Nombres</th>
        <th>Apellidos</th>
        <th>Nombre empresa</th>
        <th>Tipo</th>
        <th>No documento</th>
        <th>Nombre lista</th>
        <th>% Coincidencia</th>
        <th>Informacion</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($apiDatos as $i => $dato): ?>
        <?php
        if (!is_array($dato)) {
            continue;
        }
        $modalId = $resultContextPrefix . 'infoRegistro_' . (string)$i;
        $docModalId = $resultContextPrefix . 'docInfo_' . (string)$i;
        $docInlineId = $resultContextPrefix . 'docInline_' . (string)$i;
        $docRawCollapseId = $resultContextPrefix . 'docRaw_' . (string)$i;
        $infoRawCollapseId = $resultContextPrefix . 'infoRaw_' . (string)$i;
        $infoInlineId = $resultContextPrefix . 'infoInline_' . (string)$i;
        $infoRegistro = trim((string)($dato['informacion_registro'] ?? ''));
        $parsedInfoRows = $parseInfoRegistroStructured($infoRegistro);
        $noDocumento = trim((string)($dato['no_documento'] ?? ''));
        $parsedDocRows = $parseNoDocumento($noDocumento);
        $isSimpleDocNumber = $noDocumento !== '' && (bool)preg_match('/^[0-9][0-9\s-]*$/', $noDocumento);
        ?>
        <tr>
          <td><?= e((string)($dato['nombres'] ?? '')) ?></td>
          <td><?= e((string)($dato['apellidos'] ?? '')) ?></td>
          <td><?= e((string)($dato['nombre_empresa'] ?? '')) ?></td>
          <td><?= e((string)($dato['tipo'] ?? '')) ?></td>
          <td>
            <?php if ($noDocumento !== ''): ?>
              <?php if ($isSimpleDocNumber): ?>
                <span><?= e($normalizeDisplayValue($noDocumento)) ?></span>
              <?php else: ?>
                <?php if ($inlineDetails): ?>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary js-open-global-detail-modal"
                    data-template-id="<?= e($docInlineId) ?>"
                    data-title="Detalle de documento"
                  >Ver documento</button>
                  <template id="<?= e($docInlineId) ?>">
                    <div class="table-responsive mb-2">
                      <table class="table table-sm align-middle table-hover api-results-table">
                        <thead>
                          <tr>
                            <th>Tipo (idType)</th>
                            <th>Numero</th>
                            <th>Pais</th>
                            <th>Fecha de expiracion</th>
                            <th>Fecha de emision</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($parsedDocRows)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Sin datos parseables</td></tr>
                          <?php else: ?>
                            <?php foreach ($parsedDocRows as $dr): ?>
                              <tr>
                                <td><?= e((string)($dr['tipo'] !== '' ? $normalizeDisplayValue((string)$dr['tipo']) : '-')) ?></td>
                                <td><?= e((string)($dr['numero'] !== '' ? $normalizeDisplayValue((string)$dr['numero']) : '-')) ?></td>
                                <td><?= e((string)($dr['pais'] !== '' ? $normalizeDisplayValue((string)$dr['pais']) : '-')) ?></td>
                                <td><?= e((string)($dr['expiracion'] !== '' ? $normalizeDisplayValue((string)$dr['expiracion']) : '-')) ?></td>
                                <td><?= e((string)($dr['emision'] !== '' ? $normalizeDisplayValue((string)$dr['emision']) : '-')) ?></td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                    <div>
                      <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($docRawCollapseId) ?>"><i class="bi bi-eye me-1"></i>Ver Texto original</button>
                      <div class="collapse" id="<?= e($docRawCollapseId) ?>">
                        <pre class="bg-dark text-white p-2 small overflow-auto mb-0"><?= e($noDocumento) ?></pre>
                      </div>
                    </div>
                  </template>
                <?php else: ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#<?= e($docModalId) ?>">Ver documento</button>
                  <div class="modal fade" id="<?= e($docModalId) ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Detalle de documento</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered">
                              <thead class="table-light">
                                <tr>
                                  <th>Tipo (idType)</th>
                                  <th>Numero</th>
                                  <th>Pais</th>
                                  <th>Fecha de expiracion</th>
                                  <th>Fecha de emision</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php if (empty($parsedDocRows)): ?>
                                  <tr><td colspan="5" class="text-center text-muted">Sin datos parseables</td></tr>
                                <?php else: ?>
                                  <?php foreach ($parsedDocRows as $dr): ?>
                                    <tr>
                                      <td><?= e((string)($dr['tipo'] !== '' ? $normalizeDisplayValue((string)$dr['tipo']) : '-')) ?></td>
                                      <td><?= e((string)($dr['numero'] !== '' ? $normalizeDisplayValue((string)$dr['numero']) : '-')) ?></td>
                                      <td><?= e((string)($dr['pais'] !== '' ? $normalizeDisplayValue((string)$dr['pais']) : '-')) ?></td>
                                      <td><?= e((string)($dr['expiracion'] !== '' ? $normalizeDisplayValue((string)$dr['expiracion']) : '-')) ?></td>
                                      <td><?= e((string)($dr['emision'] !== '' ? $normalizeDisplayValue((string)$dr['emision']) : '-')) ?></td>
                                    </tr>
                                  <?php endforeach; ?>
                                <?php endif; ?>
                              </tbody>
                            </table>
                          </div>
                          <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($docRawCollapseId) ?>"><i class="bi bi-eye me-1"></i>Ver Texto original</button>
                          <div class="collapse" id="<?= e($docRawCollapseId) ?>">
                            <pre class="bg-dark text-white p-2 small overflow-auto mb-0"><?= e($noDocumento) ?></pre>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-muted small">Sin info</span>
            <?php endif; ?>
          </td>
          <td><?= e((string)($dato['nombre_lista'] ?? '')) ?></td>
          <td><?= e((string)($dato['porcentaje_coincidencia'] ?? '')) ?></td>
          <td>
            <?php if ($infoRegistro !== ''): ?>
              <?php if ($inlineDetails): ?>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary js-open-global-detail-modal"
                  data-template-id="<?= e($infoInlineId) ?>"
                  data-title="Detalle informacion_registro"
                >Ver detalle</button>
                <template id="<?= e($infoInlineId) ?>">
                  <div class="table-responsive mb-2">
                    <table class="table table-sm align-middle table-hover api-results-table">
                      <thead><tr><th>Campo</th><th>Valor</th></tr></thead>
                      <tbody>
                        <?php if (empty($parsedInfoRows)): ?>
                          <tr><td colspan="2" class="text-center text-muted">Sin datos parseables</td></tr>
                        <?php else: ?>
                          <?php foreach ($parsedInfoRows as $pr): ?>
                            <?php $innerRows = $parseInfoValorTablaInterna((string)($pr['valor'] ?? '')); ?>
                            <tr>
                              <td><?= e((string)($pr['campo'] ?? '')) ?></td>
                              <td class="text-break">
                                <?php if (!empty($innerRows)): ?>
                                  <div class="table-responsive">
                                    <table class="table table-sm align-middle table-hover api-results-table mb-0">
                                      <thead><tr><th>Ciudad</th><th>Estado/Provincia</th><th>Pais</th><th>Codigo postal</th><th>Direccion</th></tr></thead>
                                      <tbody>
                                        <?php foreach ($innerRows as $ir): ?>
                                          <tr>
                                            <td><?= e($normalizeDisplayValue((string)($ir['city'] !== '' ? $ir['city'] : '-'))) ?></td>
                                            <td><?= e($normalizeDisplayValue((string)($ir['stateOrProvince'] !== '' ? $ir['stateOrProvince'] : '-'))) ?></td>
                                            <td><?= e($normalizeDisplayValue((string)($ir['country'] !== '' ? $ir['country'] : '-'))) ?></td>
                                            <td><?= e($normalizeDisplayValue((string)($ir['postalCode'] !== '' ? $ir['postalCode'] : '-'))) ?></td>
                                            <td><?= e($normalizeDisplayValue((string)($ir['address'] !== '' ? $ir['address'] : '-'))) ?></td>
                                          </tr>
                                        <?php endforeach; ?>
                                      </tbody>
                                    </table>
                                  </div>
                                <?php else: ?>
                                  <?= e($normalizeDisplayValue((string)($pr['valor'] ?? ''))) ?>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                  <div>
                    <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($infoRawCollapseId) ?>"><i class="bi bi-eye me-1"></i>Ver Texto original</button>
                    <div class="collapse" id="<?= e($infoRawCollapseId) ?>">
                      <pre class="bg-dark text-white p-2 small overflow-auto mb-0"><?= e($infoRegistro) ?></pre>
                    </div>
                  </div>
                </template>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= e($modalId) ?>">Ver detalle</button>
                <div class="modal fade" id="<?= e($modalId) ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Detalle informacion_registro</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="table-responsive mb-3">
                          <table class="table table-sm align-middle table-hover api-results-table">
                            <thead><tr><th>Campo</th><th>Valor</th></tr></thead>
                            <tbody>
                              <?php if (empty($parsedInfoRows)): ?>
                                <tr><td colspan="2" class="text-center text-muted">Sin datos parseables</td></tr>
                              <?php else: ?>
                                <?php foreach ($parsedInfoRows as $pr): ?>
                                  <?php $innerRows = $parseInfoValorTablaInterna((string)($pr['valor'] ?? '')); ?>
                                  <tr>
                                    <td><?= e((string)($pr['campo'] ?? '')) ?></td>
                                    <td class="text-break">
                                      <?php if (!empty($innerRows)): ?>
                                        <div class="table-responsive">
                                          <table class="table table-sm align-middle table-hover api-results-table mb-0">
                                            <thead><tr><th>Ciudad</th><th>Estado/Provincia</th><th>Pais</th><th>Codigo postal</th><th>Direccion</th></tr></thead>
                                            <tbody>
                                              <?php foreach ($innerRows as $ir): ?>
                                                <tr>
                                                  <td><?= e($normalizeDisplayValue((string)($ir['city'] !== '' ? $ir['city'] : '-'))) ?></td>
                                                  <td><?= e($normalizeDisplayValue((string)($ir['stateOrProvince'] !== '' ? $ir['stateOrProvince'] : '-'))) ?></td>
                                                  <td><?= e($normalizeDisplayValue((string)($ir['country'] !== '' ? $ir['country'] : '-'))) ?></td>
                                                  <td><?= e($normalizeDisplayValue((string)($ir['postalCode'] !== '' ? $ir['postalCode'] : '-'))) ?></td>
                                                  <td><?= e($normalizeDisplayValue((string)($ir['address'] !== '' ? $ir['address'] : '-'))) ?></td>
                                                </tr>
                                              <?php endforeach; ?>
                                            </tbody>
                                          </table>
                                        </div>
                                      <?php else: ?>
                                        <?= e($normalizeDisplayValue((string)($pr['valor'] ?? ''))) ?>
                                      <?php endif; ?>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              <?php endif; ?>
                            </tbody>
                          </table>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($infoRawCollapseId) ?>"><i class="bi bi-eye me-1"></i>Ver Texto original</button>
                        <div class="collapse" id="<?= e($infoRawCollapseId) ?>">
                          <pre class="bg-dark text-white p-2 small overflow-auto mb-0"><?= e($infoRegistro) ?></pre>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-muted small">Sin info</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
