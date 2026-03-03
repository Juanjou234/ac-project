<?php

declare(strict_types=1);

$cfgDefault = [
    'url' => '',
    'api_key' => '',
    'header_name' => '',
    'usuario' => '',
    'clave' => '',
    'timeout_ms' => 5000,
];
$cfgs = $_SESSION['api_configs'] ?? [];
$cfgHeader = array_merge($cfgDefault, is_array($cfgs['header'] ?? null) ? $cfgs['header'] : []);
$cfgQuery = array_merge($cfgDefault, is_array($cfgs['query'] ?? null) ? $cfgs['query'] : []);
$activeConfig = in_array((string)($cfgs['active'] ?? ''), ['header', 'query'], true) ? (string)$cfgs['active'] : 'header';
$isHeaderActive = $activeConfig === 'header';
$isQueryActive = $activeConfig === 'query';
$amlConsulta2Url = 'http://desa.aml-advantage.com/aml/api/amlconsulta_2';
?>
<div class="card shadow-sm mb-4">
  <div class="card-header">Configuracion API AML Advantage</div>
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">Formulario 1: API con API Key</h6>
      <?php if ($isHeaderActive): ?>
        <span class="badge bg-success">Activa</span>
      <?php endif; ?>
    </div>
    <form method="post" class="row g-3 mb-2">
      <input type="hidden" name="config_profile" value="header">
      <input type="hidden" name="timeout_ms" value="5000">
      <input type="hidden" name="usuario" value="">
      <input type="hidden" name="clave" value="">

      <div class="col-12">
        <label class="form-label">URL completa</label>
        <input class="form-control" type="url" name="url" required value="<?= e((string)$cfgHeader['url']) ?>" placeholder="https://api.ejemplo.com/consulta">
      </div>
      <div class="col-md-6">
        <label class="form-label">API Key</label>
        <input class="form-control" name="api_key" required value="<?= e((string)$cfgHeader['api_key']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Header name</label>
        <input class="form-control" name="header_name" required value="<?= e((string)$cfgHeader['header_name']) ?>" placeholder="x-api-key">
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit" name="action" value="save_config">
          <i class="bi bi-save me-1"></i>Guardar configuracion
        </button>
        <button class="btn btn-outline-secondary" type="submit" name="action" value="verify_connection" <?= $isHeaderActive ? '' : 'disabled' ?> title="<?= $isHeaderActive ? '' : 'Primero establezca esta configuracion como activa' ?>">
          <i class="bi bi-plug me-1"></i>Verificar conexion
        </button>
        <?php if (!$isHeaderActive): ?>
          <button class="btn btn-outline-success" type="submit" name="action" value="set_active_config">
            <i class="bi bi-check2-circle me-1"></i>Establecer como activa
          </button>
          <input type="hidden" name="config_target" value="header">
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">Formulario 2: API con usuario y clave (amlconsulta_2)</h6>
      <?php if ($isQueryActive): ?>
        <span class="badge bg-success">Activa</span>
      <?php endif; ?>
    </div>
    <form method="post" class="row g-3">
      <input type="hidden" name="config_profile" value="query">
      <input type="hidden" name="timeout_ms" value="5000">
      <input type="hidden" name="api_key" value="">
      <input type="hidden" name="header_name" value="">

      <div class="col-12">
        <label class="form-label">URL amlconsulta_2</label>
        <input class="form-control" type="url" name="url" required value="<?= e((string)($cfgQuery['url'] !== '' ? $cfgQuery['url'] : $amlConsulta2Url)) ?>" placeholder="<?= e($amlConsulta2Url) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Usuario</label>
        <input class="form-control" name="usuario" required value="<?= e((string)$cfgQuery['usuario']) ?>" placeholder="user_1">
      </div>
      <div class="col-md-6">
        <label class="form-label">Clave</label>
        <input class="form-control" type="password" name="clave" required value="<?= e((string)$cfgQuery['clave']) ?>" placeholder="Abc123">
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit" name="action" value="save_config">
          <i class="bi bi-save me-1"></i>Guardar configuracion
        </button>
        <button class="btn btn-outline-secondary" type="submit" name="action" value="verify_connection" <?= $isQueryActive ? '' : 'disabled' ?> title="<?= $isQueryActive ? '' : 'Primero establezca esta configuracion como activa' ?>">
          <i class="bi bi-plug me-1"></i>Verificar conexion
        </button>
        <?php if (!$isQueryActive): ?>
          <button class="btn btn-outline-success" type="submit" name="action" value="set_active_config">
            <i class="bi bi-check2-circle me-1"></i>Establecer como activa
          </button>
          <input type="hidden" name="config_target" value="query">
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
