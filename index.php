<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/session_bootstrap.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/validators.php';
require_once __DIR__ . '/lib/api_client.php';
require_once __DIR__ . '/lib/actions.php';

$allowlist = [
    'dashboard',
    'erp_clientes',
    'crm_contactos',
    'crm_empresas',
    'terceros',
    'lote',
    'configuracion',
];

$page = isset($_GET['page']) ? (string) $_GET['page'] : 'dashboard';
if (!in_array($page, $allowlist, true)) {
    $page = 'dashboard';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_post_action($page);
}

$viewFile = __DIR__ . '/views/' . $page . '.php';
if (!is_file($viewFile)) {
    $viewFile = __DIR__ . '/views/dashboard.php';
    $page = 'dashboard';
}

$flashes = pull_flashes();

require __DIR__ . '/layout/header.php';
?>
<div class="container-fluid">
  <div class="row">
    <?php require __DIR__ . '/layout/sidebar.php'; ?>
    <main class="col-md-10 ms-sm-auto px-md-4 bg-light min-vh-100">
      <?php if (!empty($flashes)): ?>
        <div class="flash-overlay">
          <?php foreach ($flashes as $flash): ?>
            <div
              class="alert alert-<?= e($flash['type']) ?> fade show flash-toast js-auto-flash"
              data-auto-close-ms="3000"
              role="alert"
            >
              <div class="flash-toast-message"><?= e($flash['message']) ?></div>
              <div class="flash-toast-progress js-flash-progress"></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h4 mb-0"><?= e(page_title($page)) ?></h1>
        <form method="post" class="m-0">
          <input type="hidden" name="action" value="reset_demo">
          <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-arrow-counterclockwise"></i>
            Reset demo
          </button>
        </form>
      </div>

      <?php require $viewFile; ?>
    </main>
  </div>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
