(() => {
  const NAME_REGEX = /^(?=.{1,120}$)[\p{L}]+(?:\s+[\p{L}]+)*$/u;
  const DOC_REGEX = /^(?=.{0,40}$)[0-9]+(?:[0-9\s-]*[0-9])?$/;

  const toggleNuevoTipo = () => {
    const tipoEl = document.getElementById("nuevo_tipo");
    if (!tipoEl) {
      return;
    }
    const tipo = tipoEl.value;
    const blockPn = document.getElementById("bloque_nuevo_pn");
    const blockPj = document.getElementById("bloque_nuevo_pj");
    const nombres = document.getElementById("nuevo_nombres");
    const apellidos = document.getElementById("nuevo_apellidos");
    const nombreCompleto = document.getElementById("nuevo_nombre_completo");

    if (!blockPn || !blockPj || !nombres || !apellidos || !nombreCompleto) {
      return;
    }

    if (tipo === "PN") {
      blockPn.classList.remove("d-none");
      blockPj.classList.add("d-none");
      nombres.required = true;
      apellidos.required = true;
      nombreCompleto.required = false;
      nombreCompleto.value = "";
    } else {
      blockPn.classList.add("d-none");
      blockPj.classList.remove("d-none");
      nombres.required = false;
      apellidos.required = false;
      nombres.value = "";
      apellidos.value = "";
      nombreCompleto.required = true;
    }
  };

  const toggleEditTipo = () => {
    const tipoEl = document.getElementById("edit_tipo");
    if (!tipoEl) {
      return;
    }
    const tipo = tipoEl.value;
    const blockPn = document.getElementById("bloque_edit_pn");
    const blockPj = document.getElementById("bloque_edit_pj");
    const nombres = document.getElementById("edit_nombres");
    const apellidos = document.getElementById("edit_apellidos");
    const nombreCompleto = document.getElementById("edit_nombre_completo");

    if (!blockPn || !blockPj || !nombres || !apellidos || !nombreCompleto) {
      return;
    }

    if (tipo === "PN") {
      blockPn.classList.remove("d-none");
      blockPj.classList.add("d-none");
      nombres.required = true;
      apellidos.required = true;
      nombreCompleto.required = false;
      nombreCompleto.value = "";
    } else {
      blockPn.classList.add("d-none");
      blockPj.classList.remove("d-none");
      nombres.required = false;
      apellidos.required = false;
      nombres.value = "";
      apellidos.value = "";
      nombreCompleto.required = true;
    }
  };

  const validateInputSet = () => {
    document.querySelectorAll(".js-validate-name").forEach((el) => {
      el.addEventListener("input", () => {
        const v = el.value.trim();
        if (v === "") {
          el.setCustomValidity("");
          return;
        }
        el.setCustomValidity(NAME_REGEX.test(v) ? "" : "Solo letras y espacios (1-120).");
      });
    });

    document.querySelectorAll(".js-validate-doc").forEach((el) => {
      el.addEventListener("input", () => {
        const v = el.value.trim();
        if (v === "") {
          el.setCustomValidity("");
          return;
        }
        el.setCustomValidity(DOC_REGEX.test(v) ? "" : "Solo numeros, espacios y guion.");
      });
    });
  };

  const bindBatchSelectAll = () => {
    const checkAll = document.getElementById("check_all_batch");
    if (!checkAll) {
      return;
    }
    checkAll.addEventListener("change", () => {
      document.querySelectorAll(".batch-check").forEach((el) => {
        el.checked = checkAll.checked;
      });
    });
  };

  const bindResultadosToggle = () => {
    document.querySelectorAll(".js-toggle-resultados").forEach((btn) => {
      const targetId = btn.getAttribute("data-target-id");
      if (!targetId) {
        return;
      }
      const target = document.getElementById(targetId);
      const label = btn.querySelector("span");
      if (!target || !label) {
        return;
      }

      const setLabel = (isOpen) => {
        label.textContent = isOpen ? "Ocultar resultados" : "Ver resultados";
      };

      setLabel(target.classList.contains("show"));
      target.addEventListener("shown.bs.collapse", () => setLabel(true));
      target.addEventListener("hidden.bs.collapse", () => setLabel(false));
    });
  };

  const bindAutoFlashes = () => {
    document.querySelectorAll(".js-auto-flash").forEach((el) => {
      const ms = Number(el.getAttribute("data-auto-close-ms") || "3000");
      const closeMs = Number.isFinite(ms) && ms > 0 ? ms : 3000;
      const progress = el.querySelector(".js-flash-progress");
      if (progress) {
        progress.style.transition = `transform ${closeMs}ms linear`;
        window.requestAnimationFrame(() => {
          progress.style.transform = "scaleX(0)";
        });
      }
      window.setTimeout(() => {
        el.classList.remove("show");
        window.setTimeout(() => {
          el.remove();
        }, 250);
      }, closeMs);
    });
  };

  const bindEditModal = () => {
    document.querySelectorAll(".js-open-edit-modal").forEach((btn) => {
      btn.addEventListener("click", () => {
        const recordId = btn.getAttribute("data-record-id") || "";
        const tipo = btn.getAttribute("data-tipo") || "PN";
        const nombres = btn.getAttribute("data-nombres") || "";
        const apellidos = btn.getAttribute("data-apellidos") || "";
        const nombreCompleto = btn.getAttribute("data-nombre-completo") || "";
        const documento = btn.getAttribute("data-documento") || "";

        const rid = document.getElementById("edit_record_id");
        const t = document.getElementById("edit_tipo");
        const n = document.getElementById("edit_nombres");
        const a = document.getElementById("edit_apellidos");
        const nc = document.getElementById("edit_nombre_completo");
        const d = document.getElementById("edit_documento");

        if (rid) rid.value = recordId;
        if (t) t.value = tipo;
        if (n) n.value = nombres;
        if (a) a.value = apellidos;
        if (nc) nc.value = nombreCompleto;
        if (d) d.value = documento;
        toggleEditTipo();
      });
    });
  };

  const bindGlobalDetailModal = () => {
    const globalModalEl = document.getElementById("globalDetailModal");
    const globalTitle = document.getElementById("globalDetailModalTitle");
    const globalBody = document.getElementById("globalDetailModalBody");
    if (!globalModalEl || !globalTitle || !globalBody || typeof bootstrap === "undefined") {
      return;
    }
    const globalModal = new bootstrap.Modal(globalModalEl);

    document.querySelectorAll(".js-open-global-detail-modal").forEach((btn) => {
      btn.addEventListener("click", () => {
        const templateId = btn.getAttribute("data-template-id") || "";
        const title = btn.getAttribute("data-title") || "Detalle";
        const tpl = templateId ? document.getElementById(templateId) : null;
        if (!tpl || tpl.tagName !== "TEMPLATE") {
          return;
        }

        globalTitle.textContent = title;
        globalBody.innerHTML = tpl.innerHTML;

        const parentModalEl = btn.closest(".modal");
        if (parentModalEl) {
          const parentModal = bootstrap.Modal.getInstance(parentModalEl);
          if (parentModal) {
            parentModal.hide();
            window.setTimeout(() => globalModal.show(), 180);
            return;
          }
        }
        globalModal.show();
      });
    });
  };

  document.addEventListener("DOMContentLoaded", () => {
    validateInputSet();
    bindBatchSelectAll();
    bindResultadosToggle();
    bindAutoFlashes();
    bindEditModal();
    bindGlobalDetailModal();
    const tipoEl = document.getElementById("nuevo_tipo");
    if (tipoEl) {
      tipoEl.addEventListener("change", toggleNuevoTipo);
      toggleNuevoTipo();
    }
    const editTipoEl = document.getElementById("edit_tipo");
    if (editTipoEl) {
      editTipoEl.addEventListener("change", toggleEditTipo);
      toggleEditTipo();
    }
  });
})();
