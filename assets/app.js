(() => {
  const NAME_REGEX = /^(?=.{1,120}$)[\p{L}\p{N}._"'\-]+(?:\s+[\p{L}\p{N}._"'\-]+)*$/u;
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
    const alias = document.getElementById("nuevo_alias");
    const comentarios = document.getElementById("nuevo_comentarios");
    const representanteLegal = document.getElementById("nuevo_representante_legal");

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
      if (representanteLegal) {
        representanteLegal.value = "";
      }
    } else {
      blockPn.classList.add("d-none");
      blockPj.classList.remove("d-none");
      nombres.required = false;
      apellidos.required = false;
      nombres.value = "";
      apellidos.value = "";
      nombreCompleto.required = true;
      if (alias) {
        alias.value = "";
      }
      if (comentarios) {
        comentarios.value = "";
      }
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
    const alias = document.getElementById("edit_alias");
    const comentarios = document.getElementById("edit_comentarios");
    const representanteLegal = document.getElementById("edit_representante_legal");

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
      if (representanteLegal) {
        representanteLegal.value = "";
      }
    } else {
      blockPn.classList.add("d-none");
      blockPj.classList.remove("d-none");
      nombres.required = false;
      apellidos.required = false;
      nombres.value = "";
      apellidos.value = "";
      nombreCompleto.required = true;
      if (alias) {
        alias.value = "";
      }
      if (comentarios) {
        comentarios.value = "";
      }
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
        el.setCustomValidity(NAME_REGEX.test(v) ? "" : "Use letras, numeros, espacios y .-_ comillas (1-120).");
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
        const alias = btn.getAttribute("data-alias") || "";
        const nacionalidad = btn.getAttribute("data-nacionalidad") || "";
        const paisDomicilio = btn.getAttribute("data-pais-domicilio") || "";
        const comentarios = btn.getAttribute("data-comentarios") || "";
        const representanteLegal = btn.getAttribute("data-representante-legal") || "";

        const rid = document.getElementById("edit_record_id");
        const t = document.getElementById("edit_tipo");
        const n = document.getElementById("edit_nombres");
        const a = document.getElementById("edit_apellidos");
        const nc = document.getElementById("edit_nombre_completo");
        const d = document.getElementById("edit_documento");
        const al = document.getElementById("edit_alias");
        const nac = document.getElementById("edit_nacionalidad");
        const pais = document.getElementById("edit_pais_domicilio");
        const com = document.getElementById("edit_comentarios");
        const rep = document.getElementById("edit_representante_legal");

        if (rid) rid.value = recordId;
        if (t) t.value = tipo;
        if (n) n.value = nombres;
        if (a) a.value = apellidos;
        if (nc) nc.value = nombreCompleto;
        if (d) d.value = documento;
        if (al) al.value = alias;
        if (nac) nac.value = nacionalidad;
        if (pais) pais.value = paisDomicilio;
        if (com) com.value = comentarios;
        if (rep) rep.value = representanteLegal;
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

  const bindConfigModeToggle = () => {
    const toggleBtn = document.getElementById("config_mode_toggle");
    const modeLabel = document.getElementById("config_mode_label");
    const modeHelp = document.getElementById("config_mode_help");
    const modeInput = document.getElementById("config_mode_input");
    const urlHidden = document.getElementById("config_url_hidden");
    const urlTraditional = document.querySelector("input[name='url_traditional']");
    const urlQuery = document.querySelector("input[name='url_query']");
    const apiKey = document.querySelector("input[name='api_key']");
    const headerName = document.querySelector("input[name='header_name']");
    const usuario = document.querySelector("input[name='usuario']");
    const clave = document.querySelector("input[name='clave']");
    const traditionalGroups = document.querySelectorAll(".js-config-group-traditional");
    const queryGroups = document.querySelectorAll(".js-config-group-query");

    if (!toggleBtn || !modeInput || !urlHidden || !urlTraditional || !urlQuery) {
      return;
    }

    const queryUrl = toggleBtn.getAttribute("data-query-url") || "";
    const applyMode = (mode) => {
      const queryMode = mode === "query";
      modeInput.value = queryMode ? "query" : "traditional";
      toggleBtn.setAttribute("data-mode", modeInput.value);

      traditionalGroups.forEach((el) => el.classList.toggle("d-none", queryMode));
      queryGroups.forEach((el) => el.classList.toggle("d-none", !queryMode));

      if (queryMode) {
        if (queryUrl && urlQuery.value.trim() === "") {
          urlQuery.value = queryUrl;
        }
        urlHidden.value = urlQuery.value.trim() !== "" ? urlQuery.value.trim() : queryUrl;
      } else {
        urlHidden.value = urlTraditional.value.trim();
      }

      if (usuario) usuario.required = queryMode;
      if (clave) clave.required = queryMode;
      if (apiKey) apiKey.required = false;
      if (headerName) headerName.required = false;

      if (modeLabel) {
        modeLabel.textContent = queryMode
          ? "amlconsulta_2 (usuario/clave)"
          : "tradicional (header/api key)";
      }
      if (modeHelp) {
        modeHelp.textContent = queryMode
          ? "Modo amlconsulta_2: use usuario y clave. API Key/Header no se usan."
          : "Modo tradicional: use API Key + Header name. Usuario/clave no se usan.";
      }
    };

    toggleBtn.addEventListener("click", () => {
      const currentMode = toggleBtn.getAttribute("data-mode") === "query" ? "query" : "traditional";
      applyMode(currentMode === "query" ? "traditional" : "query");
    });

    urlTraditional.addEventListener("input", () => {
      if (modeInput.value === "traditional") {
        urlHidden.value = urlTraditional.value.trim();
      }
    });
    urlQuery.addEventListener("input", () => {
      if (modeInput.value === "query") {
        urlHidden.value = urlQuery.value.trim();
      }
    });

    applyMode(modeInput.value === "query" ? "query" : "traditional");
  };

  document.addEventListener("DOMContentLoaded", () => {
    validateInputSet();
    bindBatchSelectAll();
    bindResultadosToggle();
    bindAutoFlashes();
    bindEditModal();
    bindGlobalDetailModal();
    bindConfigModeToggle();
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
