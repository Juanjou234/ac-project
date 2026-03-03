<?php

declare(strict_types=1);

$lastBatch = $_SESSION['batch_last'] ?? [];
$defaultList = (is_array($lastBatch) && valid_list_key((string)($lastBatch['list'] ?? '')))
    ? (string)$lastBatch['list']
    : 'erp_clientes';
$selectedList = (string)($_GET['list'] ?? $defaultList);
if (!valid_list_key($selectedList)) {
    $selectedList = $defaultList;
}
$records = get_list_data($selectedList);
$batch = $_SESSION['batch_last'] ?? [];
?>
<div class="card shadow-sm mb-4">
  <div class="card-header">Consulta por lote</div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="action" value="run_batch">
      <input type="hidden" name="list_key" value="<?= e($selectedList) ?>">
      <input type="hidden" name="max_lote" value="20">
      <div class="row g-3 align-items-end mb-3">
        <div class="col-md-5">
          <label class="form-label">Lista</label>
          <select
            class="form-select"
            onchange="window.location='index.php?page=lote&list=' + encodeURIComponent(this.value)"
          >
            <option value="erp_clientes" <?= $selectedList === 'erp_clientes' ? 'selected' : '' ?>>Gestion Operativa - Clientes</option>
            <option value="crm_contactos" <?= $selectedList === 'crm_contactos' ? 'selected' : '' ?>>Gestion Comercial - Contactos</option>
            <option value="crm_empresas" <?= $selectedList === 'crm_empresas' ? 'selected' : '' ?>>Gestion Comercial - Empresas</option>
            <option value="terceros" <?= $selectedList === 'terceros' ? 'selected' : '' ?>>Gestion Comercial - Terceros</option>
          </select>
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary w-100" type="submit"><i class="bi bi-play-circle me-1"></i>Ejecutar lote</button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th style="width:50px"><input type="checkbox" id="check_all_batch"></th>
              <th>Nombre</th>
              <th>Documento</th>
              <th>Orden</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $row): ?>
              <?php $name = trim(((string)$row['nombre_completo']) !== '' ? (string)$row['nombre_completo'] : ((string)$row['nombres'] . ' ' . (string)$row['apellidos'])); ?>
              <tr>
                <td><input type="checkbox" name="record_ids[]" value="<?= e((string)$row['id']) ?>" class="batch-check"></td>
                <td><?= e($name) ?></td>
                <td><?= e((string)($row['documento'] ?? '')) ?></td>
                <td><?= e((string)$row['orden']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($batch) && is_array($batch)): ?>
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between">
      <span>Último resultado</span>
      <span>Tiempo total: <strong><?= e((string)($batch['total_time_ms'] ?? 0)) ?> ms</strong></span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Nombres</th>
              <th>Apellidos</th>
              <th>Nombre de Empresa</th>
              <th>Documento</th>
              <th>Coincidencias</th>
              <th>Resultados</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($batch['rows'] ?? []) as $index => $row): ?>
              <?php
              $modalId = 'batchMatches_' . $index;
              $respJson = $row['response_json'] ?? null;
              $datos = (is_array($respJson) && isset($respJson['datos']) && is_array($respJson['datos'])) ? $respJson['datos'] : [];
              $num = (is_array($respJson) && array_key_exists('num_registros', $respJson)) ? (int)$respJson['num_registros'] : count($datos);
              $nombreEmpresa = (string)(($row['mode'] ?? '') === 'B' ? ($row['nombre_completo'] ?? '') : '');
              ?>
              <tr>
                <td><?= e((string)($row['nombres'] ?? '')) ?></td>
                <td><?= e((string)($row['apellidos'] ?? '')) ?></td>
                <td><?= e($nombreEmpresa) ?></td>
                <td><?= e((string)($row['documento'] ?? '')) ?></td>
                <td><?= e((string)$num) ?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= e($modalId) ?>">
                    Ver Coincidencias
                  </button>
                  <div class="modal fade" id="<?= e($modalId) ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Coincidencias (<?= e((string)$num) ?>)</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <?php if (!empty($datos)): ?>
                            <?php $apiDatos = $datos; ?>
                            <?php $resultContextPrefix = 'batch_' . $modalId . '_'; ?>
                            <?php $matchesDetailMode = 'inline'; ?>
                            <?php require __DIR__ . '/partials/api_matches_table.php'; ?>
                          <?php else: ?>
                            <div class="alert alert-secondary mb-0">Sin coincidencias para esta consulta.</div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="modal fade" id="globalDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="globalDetailModalTitle">Detalle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="globalDetailModalBody"></div>
    </div>
  </div>
</div>
