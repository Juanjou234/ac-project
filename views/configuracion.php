<?php

declare(strict_types=1);

$cfg = $_SESSION['api_config'] ?? [
    'url' => '',
    'api_key' => '',
    'header_name' => '',
    'timeout_ms' => 5000,
];
?>
<div class="card shadow-sm">
  <div class="card-header">Configuracion API AML Advantage</div>
  <div class="card-body">
    <form method="post" class="row g-3 mb-2">
      <input type="hidden" name="action" value="save_config">
      <input type="hidden" name="timeout_ms" value="5000">
      <div class="col-12">
        <label class="form-label">URL completa</label>
        <input class="form-control" type="url" name="url" required value="<?= e((string)$cfg['url']) ?>" placeholder="https://api.ejemplo.com/consulta">
      </div>
      <div class="col-md-6">
        <label class="form-label">API Key</label>
        <input class="form-control" name="api_key" required value="<?= e((string)$cfg['api_key']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Header name</label>
        <input class="form-control" name="header_name" required value="<?= e((string)$cfg['header_name']) ?>" placeholder="x-api-key">
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Guardar configuracion</button>
      </div>
    </form>
    <hr>
    <form method="post" class="row g-3">
      <input type="hidden" name="action" value="verify_connection">
      <input type="hidden" name="url" value="<?= e((string)$cfg['url']) ?>">
      <input type="hidden" name="api_key" value="<?= e((string)$cfg['api_key']) ?>">
      <input type="hidden" name="header_name" value="<?= e((string)$cfg['header_name']) ?>">
      <input type="hidden" name="timeout_ms" value="5000">
      <div class="col-12">
        <button class="btn btn-outline-secondary" type="submit">
          <i class="bi bi-plug me-1"></i>Verificar conexion
        </button>
      </div>
    </form>
  </div>
</div>
