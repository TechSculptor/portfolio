'use strict';

window.UIActionsParts = window.UIActionsParts || {};

window.UIActionsParts.plan = (() => {
  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function getDefaultPlanEditor() {
    return {
      zones: [],
      nodes: [],
      links: [],
      selectedZoneId: null,
      selectedPaletteType: 'visu-box',
      selectedTool: 'select',
      selectedNodeId: null,
      connectFromId: null,
      nextNodeId: 1,
    };
  }

  function ensurePlanEditorState(S) {
    if (!S.planEditor || typeof S.planEditor !== 'object') {
      S.planEditor = getDefaultPlanEditor();
      return;
    }

    if (!Array.isArray(S.planEditor.zones)) S.planEditor.zones = [];
    if (!Array.isArray(S.planEditor.nodes)) S.planEditor.nodes = [];
    if (!Array.isArray(S.planEditor.links)) S.planEditor.links = [];

    if (!Number.isInteger(S.planEditor.nextNodeId) || S.planEditor.nextNodeId < 1) {
      S.planEditor.nextNodeId = S.planEditor.nodes.length + 1;
    }
    if (!S.planEditor.selectedZoneId && S.planEditor.zones.length > 0) {
      S.planEditor.selectedZoneId = S.planEditor.zones[0].id;
    }
    if (!S.planEditor.selectedPaletteType) S.planEditor.selectedPaletteType = 'visu-box';
    if (!S.planEditor.selectedTool) S.planEditor.selectedTool = 'select';
  }

  function autoSavePlan(ctx) {
    if (typeof ctx.savePlanConfiguration === 'function') {
      ctx.savePlanConfiguration({ silent: true });
    }
  }

  function getSettingsPart(name) {
    return window.UISettingsParts?.[name];
  }

  function openSettingsPanel(html) {
    const overlay = document.getElementById('settings-panel-overlay');
    const container = document.getElementById('settings-panel-container');
    if (!overlay || !container) return;
    container.innerHTML = html;
    overlay.hidden = false;
    setTimeout(() => {
      const first = container.querySelector('input:not([type="hidden"]), select, textarea, button');
      if (first) first.focus();
    }, 60);
  }

  function closeSettingsPanel() {
    const overlay = document.getElementById('settings-panel-overlay');
    if (overlay) overlay.hidden = true;
  }

  function handleAction(ctx, target, action) {
    const { S } = ctx;

    if (action === 'plan-add-zone') {
      ensurePlanEditorState(S);
      const zoneId = `zone-${S.planEditor.zones.length + 1}`;
      S.planEditor.zones.push({
        id: zoneId,
        label: `NOUVELLE ZONE ${S.planEditor.zones.length + 1}`,
        subtitle: '',
        x: clamp(8 + (S.planEditor.zones.length * 9) % 64, 0, 85),
        y: clamp(8 + (S.planEditor.zones.length * 7) % 70, 0, 85),
        w: 28,
        h: 18,
      });
      S.planEditor.selectedZoneId = zoneId;
      ctx.render();
      autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-delete-zone') {
      ensurePlanEditorState(S);
      const zoneId = target.dataset.zoneId;
      if (!zoneId) return true;
      S.planEditor.zones = S.planEditor.zones.filter((z) => z.id !== zoneId);
      S.planEditor.nodes = S.planEditor.nodes.map((node) => (node.zoneId === zoneId ? { ...node, zoneId: null } : node));
      if (S.planEditor.selectedZoneId === zoneId) {
        S.planEditor.selectedZoneId = S.planEditor.zones[0]?.id || null;
      }
      ctx.render();
      autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-add-node-from-palette') {
      ensurePlanEditorState(S);
      const paletteType = target.dataset.paletteType;
      if (!paletteType) return true;
      const nodeId = `n${S.planEditor.nextNodeId}`;
      const zoneId = S.planEditor.selectedZoneId;
      const labelMap = {
        'visu-box': 'VISU-BOX',
        groupe: 'Groupe',
        moteur: 'Moteur',
        armoire_double: 'Armoire elec. double',
        armoire_simple: 'Armoire elec. simple',
        foodtruck: 'Foodtruck',
        autre: 'Autre appareil',
        'cablage-32': 'Cable 32A',
        'cablage-63': 'Cable 63A',
        'cablage-125': 'Cable 125A',
        capteur1: 'Capteur 1',
        capteur2: 'Capteur 2',
        commentaire: 'Commentaire',
      };
      S.planEditor.nodes.push({
        id: nodeId,
        type: paletteType,
        label: labelMap[paletteType] || 'Element',
        zoneId,
        x: clamp(16 + (S.planEditor.nodes.length * 7) % 60, 6, 94),
        y: clamp(18 + (S.planEditor.nodes.length * 9) % 62, 7, 93),
      });
      S.planEditor.nextNodeId += 1;
      S.planEditor.selectedNodeId = nodeId;
      ctx.render();
      autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-select-tool') {
      ensurePlanEditorState(S);
      const tool = target.dataset.tool;
      if (tool !== 'select' && tool !== 'connect') return true;
      S.planEditor.selectedTool = tool;
      if (tool !== 'connect') S.planEditor.connectFromId = null;
      ctx.render();
      autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-select-zone') {
      ensurePlanEditorState(S);
      const zoneId = target.dataset.zoneId;
      if (!zoneId) return true;
      S.planEditor.selectedZoneId = zoneId;
      ctx.render();
      return true;
    }

    if (action === 'plan-select-palette') {
      ensurePlanEditorState(S);
      const type = target.dataset.paletteType;
      if (!type) return true;
      S.planEditor.selectedPaletteType = type;
      ctx.render();
      return true;
    }

    if (action === 'plan-add-node') {
      ensurePlanEditorState(S);
      const nodeId = `n${S.planEditor.nextNodeId}`;
      const zoneId = S.planEditor.selectedZoneId;
      const paletteType = S.planEditor.selectedPaletteType;
      const labelMap = {
        'visu-box': 'VISU-BOX',
        groupe: 'Groupe',
        moteur: 'Moteur',
        armoire_double: 'Armoire elec. double',
        armoire_simple: 'Armoire elec. simple',
        foodtruck: 'Foodtruck',
        autre: 'Autre appareil',
      };
      S.planEditor.nodes.push({
        id: nodeId,
        type: paletteType,
        label: labelMap[paletteType] || 'Equipement',
        zoneId,
        x: clamp(16 + (S.planEditor.nodes.length * 7) % 60, 6, 94),
        y: clamp(18 + (S.planEditor.nodes.length * 9) % 62, 7, 93),
      });
      S.planEditor.nextNodeId += 1;
      S.planEditor.selectedNodeId = nodeId;
      ctx.render();
      return true;
    }

    if (action === 'plan-select-node') {
      ensurePlanEditorState(S);
      const nodeId = target.dataset.nodeId;
      if (!nodeId) return true;
      const node = S.planEditor.nodes.find((n) => n.id === nodeId);
      if (!node) return true;

      if (S.planEditor.selectedTool === 'connect') {
        if (!S.planEditor.connectFromId) {
          S.planEditor.connectFromId = nodeId;
        } else if (S.planEditor.connectFromId !== nodeId) {
          const exists = S.planEditor.links.some((l) =>
            (l.from === S.planEditor.connectFromId && l.to === nodeId)
            || (l.from === nodeId && l.to === S.planEditor.connectFromId)
          );
          if (!exists) {
            S.planEditor.links.push({
              id: `l${Date.now()}`,
              from: S.planEditor.connectFromId,
              to: nodeId,
              kind: 'cablage-32',
            });
            autoSavePlan(ctx);
          }
          S.planEditor.connectFromId = null;
        }
      }

      S.planEditor.selectedNodeId = nodeId;
      S.planEditor.selectedZoneId = node.zoneId;
      ctx.render();
      if (S.planEditor.selectedTool !== 'connect') autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-delete-selected') {
      ensurePlanEditorState(S);
      const nodeId = S.planEditor.selectedNodeId;
      if (!nodeId) return true;
      S.planEditor.nodes = S.planEditor.nodes.filter((n) => n.id !== nodeId);
      S.planEditor.links = S.planEditor.links.filter((l) => l.from !== nodeId && l.to !== nodeId);
      S.planEditor.selectedNodeId = null;
      if (S.planEditor.connectFromId === nodeId) S.planEditor.connectFromId = null;
      ctx.render();
      autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-delete-node') {
      ensurePlanEditorState(S);
      const nodeId = target.dataset.nodeId;
      if (!nodeId) return true;
      S.planEditor.nodes = S.planEditor.nodes.filter((n) => n.id !== nodeId);
      S.planEditor.links = S.planEditor.links.filter((l) => l.from !== nodeId && l.to !== nodeId);
      if (S.planEditor.selectedNodeId === nodeId) S.planEditor.selectedNodeId = null;
      if (S.planEditor.connectFromId  === nodeId) S.planEditor.connectFromId  = null;
      ctx.render();
      autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-nudge-selected') {
      ensurePlanEditorState(S);
      const nodeId = S.planEditor.selectedNodeId;
      if (!nodeId) return true;
      const dir = target.dataset.dir;
      const node = S.planEditor.nodes.find((n) => n.id === nodeId);
      if (!node) return true;
      const step = 2;
      if (dir === 'up') node.y = clamp(node.y - step, 2, 98);
      if (dir === 'down') node.y = clamp(node.y + step, 2, 98);
      if (dir === 'left') node.x = clamp(node.x - step, 2, 98);
      if (dir === 'right') node.x = clamp(node.x + step, 2, 98);
      ctx.render();
      autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-clear-links') {
      ensurePlanEditorState(S);
      S.planEditor.links = [];
      S.planEditor.connectFromId = null;
      ctx.render();
      autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-remove-link') {
      ensurePlanEditorState(S);
      const linkId = target.dataset.linkId;
      if (!linkId) return true;
      S.planEditor.links = S.planEditor.links.filter((l) => l.id !== linkId);
      ctx.render();
      autoSavePlan(ctx);
      return true;
    }

    if (action === 'plan-save') {
      ensurePlanEditorState(S);
      const parcId = typeof ctx.getActivePlanParcId === 'function' ? ctx.getActivePlanParcId() : (S.parc && S.parc.id);
      if (!parcId) {
        window.alert('Aucun parc. Impossible de sauvegarder.');
        return true;
      }
      if (typeof ctx.savePlanConfiguration === 'function') {
        ctx.savePlanConfiguration({ silent: false });
      }
      return true;
    }

    if (action === 'plan-delete') {
      ensurePlanEditorState(S);
      const parcId = typeof ctx.getActivePlanParcId === 'function' ? ctx.getActivePlanParcId() : (S.parc && S.parc.id);
      if (!parcId) return true;
      const activeParc = (S.user?.role === 'admin' && S.planEditorSelectedParcId)
        ? (S.planEditorParcs || []).find((p) => String(p.id) === String(parcId))
        : S.parc;
      const parcNom = (activeParc && (activeParc.nom_parc || activeParc.nom)) || `Parc #${parcId}`;
      const buildConfirmModal = getSettingsPart('buildConfirmModal');
      if (buildConfirmModal) {
        openSettingsPanel(buildConfirmModal({
          title: 'Supprimer définitivement le plan de ce parc ?',
          sub: 'Cette action est irréversible : le plan (zones, éléments, connexions) sera perdu.',
          confirmAction: 'sp-confirm-plan-delete',
          confirmText: parcNom,
          confirmLabel: 'Supprimer le plan',
        }));
      }
      return true;
    }

    if (action === 'sp-confirm-plan-delete') {
      closeSettingsPanel();
      if (typeof ctx.deletePlanConfiguration === 'function') {
        ctx.deletePlanConfiguration();
      }
      return true;
    }

    if (action === 'plan-reset-demo') {
      S.planEditor = getDefaultPlanEditor();
      ctx.render();
      return true;
    }

    return false;
  }

  function handleInput(ctx, target) {
    const { S } = ctx;

    if (target.id === 'plan-parc-select') {
      if (typeof ctx.selectPlanEditorParc === 'function') {
        ctx.selectPlanEditorParc(target.value);
      }
      return true;
    }

    if (target.classList && target.classList.contains('plan-zone-rename')) {
      ensurePlanEditorState(S);
      const zoneId = target.dataset.zoneId;
      const zone = S.planEditor.zones.find((item) => item.id === zoneId);
      if (!zone) return true;
      zone.label = target.value || zone.label;
      if (typeof ctx.savePlanConfiguration === 'function') {
        ctx.savePlanConfiguration({ silent: true });
      }
      return true;
    }

    if (target.id === 'plan-node-label') {
      ensurePlanEditorState(S);
      const node = S.planEditor.nodes.find((n) => n.id === S.planEditor.selectedNodeId);
      if (!node) return true;
      node.label = target.value || node.label;
      if (typeof ctx.savePlanConfiguration === 'function') {
        ctx.savePlanConfiguration({ silent: true });
      }
      return true;
    }

    if (target.id === 'plan-link-kind') {
      ensurePlanEditorState(S);
      const link = S.planEditor.links[0];
      if (link) link.kind = target.value || 'cablage-32';
      if (typeof ctx.savePlanConfiguration === 'function') {
        ctx.savePlanConfiguration({ silent: true });
      }
      return true;
    }

    return false;
  }

  // ── État unifié du drag ────────────────────────────────────────────
  const drag = {
    active: false,
    kind: null,
    id: null,
    el: null,
    boardRect: null,
    offsetX: 0,
    offsetY: 0,
    startX: 0,
    startY: 0,
    startW: 0,
    startH: 0,
    previewSvg: null,
    previewLine: null,
    wasDrag: false,
  };

  function getPlanBoard() {
    return document.querySelector('.plan-board');
  }

  function getCurrentPlanTool() {
    const btn = document.querySelector('[data-action="plan-select-tool"].is-active');
    return (btn && btn.dataset.tool) || 'select';
  }

  function toggleNodeLinkButtonsVisibility(nodeId, hide) {
    if (!nodeId) return;
    const board = getPlanBoard();
    if (!board) return;
    board.querySelectorAll('.plan-link-remove').forEach((el) => {
      if (el.dataset.linkFrom === nodeId || el.dataset.linkTo === nodeId) {
        el.classList.toggle('is-hidden', hide);
      }
    });
  }

  function updateNodeLinksPosition(nodeId, xPct, yPct) {
    if (!nodeId) return;
    const board = getPlanBoard();
    if (!board) return;
    board.querySelectorAll('.plan-link-line').forEach((line) => {
      let x1 = parseFloat(line.getAttribute('x1'));
      let y1 = parseFloat(line.getAttribute('y1'));
      let x2 = parseFloat(line.getAttribute('x2'));
      let y2 = parseFloat(line.getAttribute('y2'));

      if (line.dataset.linkFrom === nodeId) {
        x1 = xPct; y1 = yPct;
        line.setAttribute('x1', `${xPct}%`);
        line.setAttribute('y1', `${yPct}%`);
      } else if (line.dataset.linkTo === nodeId) {
        x2 = xPct; y2 = yPct;
        line.setAttribute('x2', `${xPct}%`);
        line.setAttribute('y2', `${yPct}%`);
      } else {
        return;
      }

      const btn = board.querySelector(
        `.plan-link-remove[data-link-from="${line.dataset.linkFrom}"][data-link-to="${line.dataset.linkTo}"]`
      );
      if (btn) {
        btn.style.left = `${(x1 + x2) / 2}%`;
        btn.style.top  = `${(y1 + y2) / 2}%`;
      }
    });
  }

  function onMouseDown(e) {
    if (e.button !== 0) return;
    const board = getPlanBoard();
    if (!board) return;
    if (!board.contains(e.target)) return;
    if (e.target.closest('.plan-comment-edit-ta')) return;

    const rect = board.getBoundingClientRect();
    const tool = getCurrentPlanTool();

    const nodeEl = e.target.closest('.plan-node');
    if (nodeEl && nodeEl.dataset.nodeId) {
      if (nodeEl.querySelector('.plan-comment-edit-ta')) return;
      e.preventDefault();
      if (tool !== 'select') return;
      const wrapEl   = nodeEl.closest('.plan-node-wrap') || nodeEl;
      const nodeRect = nodeEl.getBoundingClientRect();
      drag.active   = true;
      drag.wasDrag  = false;
      drag.id       = nodeEl.dataset.nodeId;
      drag.el       = wrapEl;
      drag.boardRect = rect;
      drag.startX   = e.clientX;
      drag.startY   = e.clientY;
      drag.offsetX  = e.clientX - (nodeRect.left + nodeRect.width  / 2);
      drag.offsetY  = e.clientY - (nodeRect.top  + nodeRect.height / 2);
      drag.previewSvg  = null;
      drag.previewLine = null;
      drag.kind = 'node-move';
      toggleNodeLinkButtonsVisibility(drag.id, true);
      return;
    }

    if (tool !== 'select') return;
    const resizeHandle = e.target.closest('.plan-zone-resize-handle');
    if (resizeHandle) {
      const zoneEl = resizeHandle.closest('.plan-zone-rect');
      if (!zoneEl || !zoneEl.dataset.zoneId) return;
      const zoneRect = zoneEl.getBoundingClientRect();
      drag.active   = true;
      drag.wasDrag  = false;
      drag.kind     = 'zone-resize';
      drag.id       = zoneEl.dataset.zoneId;
      drag.el       = zoneEl;
      drag.boardRect = rect;
      drag.startX   = e.clientX;
      drag.startY   = e.clientY;
      drag.startW   = zoneRect.width;
      drag.startH   = zoneRect.height;
      drag.previewSvg  = null;
      drag.previewLine = null;
      return;
    }
    const zoneEl = e.target.closest('.plan-zone-rect');
    if (!zoneEl || !zoneEl.dataset.zoneId) return;
    if (e.target.closest('.plan-zone-delete')) return;

    const zoneRect = zoneEl.getBoundingClientRect();
    drag.active   = true;
    drag.wasDrag  = false;
    drag.kind     = 'zone';
    drag.id       = zoneEl.dataset.zoneId;
    drag.el       = zoneEl;
    drag.boardRect = rect;
    drag.startX   = e.clientX;
    drag.startY   = e.clientY;
    drag.offsetX  = e.clientX - zoneRect.left;
    drag.offsetY  = e.clientY - zoneRect.top;
    drag.previewSvg  = null;
    drag.previewLine = null;
  }

  function onMouseMove(e) {
    if (!drag.active) return;

    const ddx = e.clientX - drag.startX;
    const ddy = e.clientY - drag.startY;
    if (!drag.wasDrag) {
      if (Math.sqrt(ddx * ddx + ddy * ddy) < 5) return;
      drag.wasDrag = true;
    }

    const rect = drag.boardRect;

    if (drag.kind === 'node-move') {
      const cx = e.clientX - drag.offsetX;
      const cy = e.clientY - drag.offsetY;
      const xPct = Math.max(0, Math.min(100, Math.round(((cx - rect.left) / rect.width) * 100)));
      const yPct = Math.max(0, Math.min(100, Math.round(((cy - rect.top) / rect.height) * 100)));
      drag.el.style.left = `${xPct}%`;
      drag.el.style.top  = `${yPct}%`;
      updateNodeLinksPosition(drag.id, xPct, yPct);

    } else if (drag.kind === 'zone') {
      const newLeft = e.clientX - drag.offsetX - rect.left;
      const newTop  = e.clientY - drag.offsetY - rect.top;
      const xPct = Math.max(0, Math.min(100, Math.round((newLeft / rect.width) * 100)));
      const yPct = Math.max(0, Math.min(100, Math.round((newTop / rect.height) * 100)));
      drag.el.style.left = `${xPct}%`;
      drag.el.style.top  = `${yPct}%`;
    } else if (drag.kind === 'zone-resize') {
      const newW = drag.startW + (e.clientX - drag.startX);
      const newH = drag.startH + (e.clientY - drag.startY);
      const minW = rect.width * 0.1;
      const minH = rect.height * 0.1;
      const safeW = Math.max(minW, newW);
      const safeH = Math.max(minH, newH);
      const wPct = Math.max(1, Math.min(100, Math.round((safeW / rect.width) * 100)));
      const hPct = Math.max(1, Math.min(100, Math.round((safeH / rect.height) * 100)));
      drag.el.style.width  = `${wPct}%`;
      drag.el.style.height = `${hPct}%`;
    }
  }

  function onMouseUp(e) {
    if (!drag.active) return;
    drag.active = false;

    if (drag.previewSvg && drag.previewSvg.parentNode) {
      drag.previewSvg.parentNode.removeChild(drag.previewSvg);
      drag.previewSvg  = null;
      drag.previewLine = null;
    }

    if (drag.kind === 'node-move') {
      toggleNodeLinkButtonsVisibility(drag.id, false);
    }

    if (!drag.wasDrag) return;

    const rect = drag.boardRect;

    if (drag.kind === 'node-move') {
      const cx = e.clientX - drag.offsetX;
      const cy = e.clientY - drag.offsetY;
      window.ui.movePlanNode(drag.id,
        Math.max(0, Math.min(100, Math.round(((cx - rect.left) / rect.width) * 100))),
        Math.max(0, Math.min(100, Math.round(((cy - rect.top) / rect.height) * 100)))
      );

    } else if (drag.kind === 'zone') {
      const newLeft = e.clientX - drag.offsetX - rect.left;
      const newTop  = e.clientY - drag.offsetY - rect.top;
      window.ui.movePlanZone(drag.id,
        Math.max(0, Math.min(100, Math.round((newLeft / rect.width) * 100))),
        Math.max(0, Math.min(100, Math.round((newTop / rect.height) * 100)))
      );
    } else if (drag.kind === 'zone-resize') {
      const newW = drag.startW + (e.clientX - drag.startX);
      const newH = drag.startH + (e.clientY - drag.startY);
      const minW = rect.width * 0.1;
      const minH = rect.height * 0.1;
      const safeW = Math.max(minW, newW);
      const safeH = Math.max(minH, newH);
      window.ui.resizePlanZone(drag.id,
        Math.max(1, Math.min(100, Math.round((safeW / rect.width) * 100))),
        Math.max(1, Math.min(100, Math.round((safeH / rect.height) * 100)))
      );
    }
  }

  function onDblClick(e) {
    const commentNode = e.target.closest('.plan-comment-node');
    if (commentNode) {
      e.preventDefault();
      startCommentEdit(commentNode);
      return;
    }

    const labelEl = e.target.closest('.plan-node-label');
    if (labelEl && labelEl.dataset.nodeId) {
      e.preventDefault();
      startNodeRename(labelEl, labelEl.dataset.nodeId);
      return;
    }

    if (e.target.classList.contains('plan-zone-rename-trigger') && e.target.dataset.zoneId) {
      e.preventDefault();
      startZoneRename(e.target, e.target.dataset.zoneId);
      return;
    }
  }

  function startNodeRename(labelEl, nodeId) {
    if (!labelEl || labelEl.querySelector('input')) return;
    const currentText = labelEl.textContent.trim();

    const input = document.createElement('input');
    input.className = 'plan-node-rename-input';
    input.value = currentText;
    labelEl.replaceWith(input);
    input.focus();
    input.select();

    const commit = () => window.ui.updateNodeLabel(nodeId, input.value);
    input.addEventListener('blur', commit, { once: true });
    input.addEventListener('keydown', (ev) => {
      ev.stopPropagation();
      if (ev.key === 'Enter')  { ev.preventDefault(); input.blur(); }
      if (ev.key === 'Escape') { ev.preventDefault(); input.removeEventListener('blur', commit); window.ui.render(); }
    });
    input.addEventListener('click',     (ev) => ev.stopPropagation());
    input.addEventListener('mousedown', (ev) => ev.stopPropagation());
  }

  // Renomme une zone avec la bonne structure
  function startZoneRename(h4El, zoneId) {
    if (!h4El || h4El.querySelector('input')) return;
    const currentText = h4El.textContent.trim();

    const input = document.createElement('input');
    input.className = 'plan-zone-rename-input';
    input.value = currentText;
    h4El.textContent = '';
    h4El.appendChild(input);
    input.focus();
    input.select();

    const commit = () => window.ui.updateZoneLabel(zoneId, input.value);
    input.addEventListener('blur', commit, { once: true });
    input.addEventListener('keydown', (ev) => {
      ev.stopPropagation();
      if (ev.key === 'Enter')  { ev.preventDefault(); input.blur(); }
      if (ev.key === 'Escape') { ev.preventDefault(); input.removeEventListener('blur', commit); window.ui.render(); }
    });
    input.addEventListener('click',     (ev) => ev.stopPropagation());
    input.addEventListener('mousedown', (ev) => ev.stopPropagation());
  }

  function startCommentEdit(nodeEl) {
    if (!nodeEl) return;
    const commentDiv = nodeEl.querySelector('.plan-comment-text');
    if (!commentDiv || nodeEl.querySelector('.plan-comment-edit-ta')) return;

    const currentText = Array.from(commentDiv.querySelectorAll('span'))
      .map((s) => s.textContent)
      .join('\n');

    const ta = document.createElement('textarea');
    ta.className = 'plan-comment-edit-ta';
    ta.value = currentText;
    ta.rows  = Math.max(3, currentText.split('\n').length + 1);
    commentDiv.replaceWith(ta);
    ta.focus();
    ta.select();

    const nodeId = nodeEl.dataset.nodeId;

    ta.addEventListener('blur', () => {
      window.ui.updateCommentText(nodeId, ta.value);
    }, { once: true });

    ta.addEventListener('keydown',  (ev) => { ev.stopPropagation(); if (ev.key === 'Escape') { ev.preventDefault(); ta.blur(); } });
    ta.addEventListener('keyup',    (ev) => ev.stopPropagation());
    ta.addEventListener('click',    (ev) => ev.stopPropagation());
    ta.addEventListener('mousedown',(ev) => ev.stopPropagation());
  }

  function checkAndConsumeDragClick(e) {
    if (drag.wasDrag) {
      drag.wasDrag = false;
      return true;
    }
    if (e.target.closest('.plan-comment-edit-ta')) return true;
    const commentNode = e.target.closest('.plan-comment-node');
    if (commentNode && commentNode.querySelector('.plan-comment-edit-ta')) return true;
    if (commentNode && e.detail >= 2) {
      startCommentEdit(commentNode);
      return true;
    }
    return false;
  }

  function initEvents() {
    document.addEventListener('mousedown', onMouseDown);
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup',   onMouseUp);
    document.addEventListener('dblclick',  onDblClick);
  }

  return {
    handleAction,
    handleInput,
    getDefaultPlanEditor,
    ensurePlanEditorState,
    checkAndConsumeDragClick,
    initEvents
  };
})();
