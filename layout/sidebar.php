<?php

declare(strict_types=1);
$active = $page ?? 'dashboard';
?>
<nav class="col-md-2 d-md-block bg-dark sidebar collapse text-white min-vh-100">
  <div class="position-sticky pt-3">
    <h6 class="sidebar-heading px-3 mb-3 text-uppercase text-secondary">ac-project</h6>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link text-white sidebar-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
          <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
      </li>
    </ul>

    <div class="accordion accordion-flush" id="sidebarSections">
      <div class="accordion-item bg-dark border-0">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-dark text-white px-3 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#erpMenu">
            <i class="bi bi-boxes me-2"></i>Gestion Operativa
          </button>
        </h2>
        <div id="erpMenu" class="accordion-collapse collapse <?= $active === 'erp_clientes' ? 'show' : '' ?>" data-bs-parent="#sidebarSections">
          <div class="accordion-body py-1 px-0">
            <a class="nav-link text-white sidebar-link ps-4 <?= $active === 'erp_clientes' ? 'active' : '' ?>" href="index.php?page=erp_clientes">Clientes</a>
          </div>
        </div>
      </div>

      <div class="accordion-item bg-dark border-0">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-dark text-white px-3 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#crmMenu">
            <i class="bi bi-people me-2"></i>Gestion Comercial
          </button>
        </h2>
        <div id="crmMenu" class="accordion-collapse collapse <?= in_array($active, ['crm_contactos', 'crm_empresas', 'terceros'], true) ? 'show' : '' ?>" data-bs-parent="#sidebarSections">
          <div class="accordion-body py-1 px-0">
            <a class="nav-link text-white sidebar-link ps-4 <?= $active === 'crm_contactos' ? 'active' : '' ?>" href="index.php?page=crm_contactos">Contactos</a>
            <a class="nav-link text-white sidebar-link ps-4 <?= $active === 'crm_empresas' ? 'active' : '' ?>" href="index.php?page=crm_empresas">Empresas</a>
            <a class="nav-link text-white sidebar-link ps-4 <?= $active === 'terceros' ? 'active' : '' ?>" href="index.php?page=terceros">Terceros</a>
          </div>
        </div>
      </div>

      <div class="accordion-item bg-dark border-0">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-dark text-white px-3 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#loteMenu">
            <i class="bi bi-layers me-2"></i>Lote
          </button>
        </h2>
        <div id="loteMenu" class="accordion-collapse collapse <?= $active === 'lote' ? 'show' : '' ?>" data-bs-parent="#sidebarSections">
          <div class="accordion-body py-1 px-0">
            <a class="nav-link text-white sidebar-link ps-4 <?= $active === 'lote' ? 'active' : '' ?>" href="index.php?page=lote">Consulta por lote</a>
          </div>
        </div>
      </div>

      <div class="accordion-item bg-dark border-0">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-dark text-white px-3 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#configMenu">
            <i class="bi bi-gear me-2"></i>Configuracion
          </button>
        </h2>
        <div id="configMenu" class="accordion-collapse collapse <?= $active === 'configuracion' ? 'show' : '' ?>" data-bs-parent="#sidebarSections">
          <div class="accordion-body py-1 px-0">
            <a class="nav-link text-white sidebar-link ps-4 <?= $active === 'configuracion' ? 'active' : '' ?>" href="index.php?page=configuracion">API AML Advantage</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>
