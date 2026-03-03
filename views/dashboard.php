<?php

declare(strict_types=1);

$lists = [
    'erp_clientes' => 'ERP Clientes',
    'crm_contactos' => 'CRM Contactos',
    'crm_empresas' => 'CRM Empresas',
    'terceros' => 'Terceros',
];
$historyListLabels = [
    'erp_clientes' => 'Gestion Operativa - Clientes',
    'crm_contactos' => 'Gestion Comercial - Contactos',
    'crm_empresas' => 'Gestion Comercial - Empresas',
    'terceros' => 'Gestion Comercial - Terceros',
];
?>
<div class="row g-3 mb-4">
  <?php foreach ($lists as $key => $label): ?>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small"><?= e($label) ?></div>
          <div class="display-6"><?= e((string)count(get_list_data($key))) ?></div>
          <a class="stretched-link small" href="index.php?page=<?= e($key) ?>">Ver lista</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-header">Historial reciente</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Registro</th>
                <th>Lista</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $history = $_SESSION['history'] ?? [];
              $history = is_array($history) ? array_reverse($history) : [];
              $history = array_slice($history, 0, 8);
              ?>
              <?php if (empty($history)): ?>
                <tr><td colspan="4" class="text-center text-muted">Sin consultas</td></tr>
              <?php else: ?>
                <?php foreach ($history as $h): ?>
                  <?php $ok = !empty($h['resultado']['ok']); ?>
                  <?php
                  $listKey = (string)($h['lista'] ?? '');
                  $listLabel = $historyListLabels[$listKey] ?? $listKey;
                  $payload = is_array($h['payload'] ?? null) ? $h['payload'] : [];
                  $registro = '';
                  if (trim((string)($payload['nombre_completo'] ?? '')) !== '') {
                      $registro = trim((string)$payload['nombre_completo']);
                  } else {
                      $nombres = trim((string)($payload['nombres'] ?? ''));
                      $apellidos = trim((string)($payload['apellidos'] ?? ''));
                      $registro = trim($nombres . ' ' . $apellidos);
                  }
                  if ($registro === '') {
                      $registro = (string)($h['registro_id'] ?? '-');
                  }
                  $dateRaw = trim((string)($h['timestamp'] ?? ''));
                  $dateOnly = $dateRaw;
                  if ($dateRaw !== '') {
                      $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateRaw);
                      if ($dt instanceof DateTimeImmutable) {
                          $dateOnly = $dt->format('d/m/Y H:i:s');
                      } elseif ((bool)preg_match('/^\d{4}-\d{2}-\d{2}/', $dateRaw)) {
                          $dateOnly = substr($dateRaw, 0, 10);
                      }
                  }
                  ?>
                  <tr>
                    <td><?= e($dateOnly) ?></td>
                    <td><?= e($registro) ?></td>
                    <td><?= e($listLabel) ?></td>
                    <td><span class="badge bg-<?= $ok ? 'success' : 'danger' ?>"><?= $ok ? 'OK' : 'FAIL' ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
