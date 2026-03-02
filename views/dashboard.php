<?php

declare(strict_types=1);

$lists = [
    'erp_clientes' => 'ERP Clientes',
    'crm_contactos' => 'CRM Contactos',
    'crm_empresas' => 'CRM Empresas',
    'terceros' => 'Terceros',
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
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header">Configuracion actual API</div>
      <div class="card-body">
        <?php $cfg = $_SESSION['api_config'] ?? []; ?>
        <p class="mb-1"><strong>URL:</strong> <?= e((string)($cfg['url'] ?? '')) ?></p>
        <p class="mb-1"><strong>Header:</strong> <?= e((string)($cfg['header_name'] ?? '')) ?></p>
        <!-- <p class="mb-0"><strong>Timeout:</strong> <?php //echo e((string)($cfg['timeout_ms'] ?? 0)) ?> ms</p> -->
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header">Historial reciente</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Lista</th>
                <th>ID</th>
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
                  <tr>
                    <td><?= e((string)($h['timestamp'] ?? '')) ?></td>
                    <td><?= e((string)($h['lista'] ?? '')) ?></td>
                    <td><?= e((string)($h['registro_id'] ?? '')) ?></td>
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
