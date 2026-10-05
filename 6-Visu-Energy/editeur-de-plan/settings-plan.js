'use strict';

window.UISettingsParts = window.UISettingsParts || {};

// Onglet Plan de la page Réglages — édition du plan du parc (zones,
// équipements, connexions), déplacée depuis l'ancienne route standalone
// #plan-editor (voir issue "L'unification" du plan, sur le même principe
// que le déplacement du Calendrier depuis l'onglet Projet vers Réglages).
// Le plan n'est désormais modifiable QUE depuis cette page — la vue en
// lecture seule du dashboard (onglet "Plan") reste inchangée et pointe
// ici pour toute modification.
//
// Le blocage mobile (édition drag&drop impraticable au tactile) est géré
// au niveau de settings.page.js, comme pour l'onglet Administration —
// pas ici, pour rester cohérent avec le reste des onglets Réglages.
window.UISettingsParts.buildPlanContent = function buildPlanContent(state, renderHelpers) {
  const { escHtml, button } = renderHelpers;

  const editor = state.planEditor || {};
  const selectedPaletteType = editor.selectedPaletteType || 'visu-box';
  const selectedTool        = editor.selectedTool        || 'select';

  const user = state.user || {};
  const isAdmin = user.role === 'admin';
  const planEditorParcs = Array.isArray(state.planEditorParcs) ? state.planEditorParcs : [];
  const activeParcId = state.planEditorSelectedParcId || (state.parc && state.parc.id) || '';

  // =================================================================
  // PALETTE — IMAGES DES ELEMENTS
  // Chaque entrée : { type, label, fallback, file }
  //   file = chemin relatif depuis `assets/plan-icons/`
  //          ex. 'Armoire_Distribution.png'  ou  'cablage/RAL VISU 32.png'
  // Pour changer une image : modifiez le champ `file` de l'entrée.
  // =================================================================
  const iconFolder = 'assets/plan-icons';
  const encodeIconSrc = (folder, file) => file ? encodeURI(`${folder}/${file}`) : '';

  const paletteGroups = [
    {
      label: 'Equipements',
      items: [
        { type: 'visu-box',       label: 'VISU-BOX',             fallback: 'VB',  file: 'Armoire_Distribution.png'   },
        { type: 'groupe',         label: 'Groupe electrogene',   fallback: 'GR',  file: 'GroupeElectrogene.jpg'      },
        { type: 'moteur',         label: 'Moteur',               fallback: 'MO',  file: 'motor.svg'                  },
        { type: 'armoire_double', label: 'Armoire elec. double', fallback: 'AD',  file: 'TGBT 1.png'                 },
        { type: 'armoire_simple', label: 'Armoire elec. simple', fallback: 'AS',  file: 'TGBT 2.png'                 },
        { type: 'coffret',        label: 'Coffret distribution', fallback: 'CD',  file: 'Coffret_Distribution.png'   },
        { type: 'foodtruck',      label: 'Foodtruck',            fallback: 'FT',  file: 'Food_Truck.png'             },
        { type: 'autre',          label: 'Autre appareil',       fallback: 'AP',  file: 'tank.svg'                   },
      ],
    },
    {
      label: 'Cablage',
      items: [
        { type: 'cablage-32',  label: 'Cable 32A',  fallback: '32',  file: 'cablage/RAL VISU 32.png'  },
        { type: 'cablage-63',  label: 'Cable 63A',  fallback: '63',  file: 'cablage/RAL VISU 63.png'  },
        { type: 'cablage-125', label: 'Cable 125A', fallback: '125', file: 'cablage/RAL VISU 125.png' },
      ],
    },
    {
      label: 'Capteurs',
      items: [
        { type: 'capteur1', label: 'Capteur type 1', fallback: 'C1', file: 'capteur/capteur1.png' },
        { type: 'capteur2', label: 'Capteur type 2', fallback: 'C2', file: 'capteur/capteur2.png' },
      ],
    },
    {
      label: 'Note',
      items: [
        { type: 'commentaire', label: 'Bloc commentaire', fallback: '📝', file: null },
      ],
    },
  ];

  // --- Rendu du plan (mode édition) ---
  const planCanvasHtml = window.UIComponents && typeof window.UIComponents.planCanvas === 'function'
    ? window.UIComponents.planCanvas(state, renderHelpers, { editMode: true })
    : '';

  // ── Palette (groupée par section) ──────────────────────────────────
  const paletteMarkup = paletteGroups.map((group) => {
    const itemsHtml = group.items.map((p) => {
      const active  = p.type === selectedPaletteType;
      const iconSrc = encodeIconSrc(iconFolder, p.file);
      const isTallGroup = group.label === 'Capteurs' || group.label === 'Equipements' || group.label === 'Cablage';
      const isCapteur = p.type.startsWith('capteur');
      const isCablageGroup = group.label === 'Cablage';
      const hasBelowCode = isCapteur || isCablageGroup;
      return `<div class="plan-palette-item-wrapper${isTallGroup ? ' plan-palette-tall' : ''}">
        <button
          type="button"
          class="plan-palette-item${active ? ' is-active' : ''}${hasBelowCode ? ' has-below-code' : ''}"
          data-action="plan-select-palette"
          data-palette-type="${escHtml(p.type)}">
          <span class="plan-palette-icon${hasBelowCode ? ' is-below-code' : ''}">
            ${iconSrc ? `<img src="${escHtml(iconSrc)}" alt="" draggable="false" onerror="this.style.display='none'">` : ''}
            <span class="plan-palette-code">${escHtml(p.fallback)}</span>
          </span>
          <span class="plan-palette-label">${escHtml(p.label)}</span>
        </button>
        <button
          type="button"
          class="plan-palette-add-btn"
          data-action="plan-add-node-from-palette"
          data-palette-type="${escHtml(p.type)}"
          title="Ajouter au plan">+</button>
      </div>`;
    }).join('');
    return `<div class="plan-palette-group">
      <p class="plan-palette-group-label">${escHtml(group.label)}</p>
      ${itemsHtml}
    </div>`;
  }).join('');

  const toolHint = selectedTool === 'connect'
    ? "Clique sur l'element 1 puis sur l'element 2 pour creer une connexion."
    : 'Mode Deplacer Et Modifier — Modifier les positions des elements et des zones. Glissez elements et zones pour les repositionner.';

  return `
    <section class="card plan-editor-shell">
      <header class="plan-editor-head">
        <div>
          <h3 style="display: inline-flex; align-items: center; gap: 0.5rem;">
            Modification du plan
            <span id="plan-save-status" class="plan-save-status" style="font-size: 0.78rem; font-weight: normal; color: var(--fg-muted); transition: opacity 0.3s; opacity: 0; white-space: nowrap;"></span>
          </h3>
          <p class="plan-tool-hint">${escHtml(toolHint)}</p>
        </div>
        <div class="plan-editor-head-actions" style="display: flex; align-items: center; gap: 0.75rem;">
          ${isAdmin ? `<select id="plan-parc-select" class="select" style="max-width: 340px;">
            ${planEditorParcs.length === 0 ? '<option value="">Chargement des parcs…</option>' : ''}
            ${planEditorParcs.map((p) => `<option value="${escHtml(String(p.id))}"${String(p.id) === String(activeParcId) ? ' selected' : ''}>${escHtml(p.nom_parc || `Parc #${p.id}`)}</option>`).join('')}
          </select>` : ''}
          ${isAdmin ? button({ label: 'Supprimer le plan', variant: 'outline', size: 'sm', attrs: 'data-action="plan-delete"' }) : ''}
          ${button({ label: 'Sauvegarder', variant: 'default', size: 'sm', attrs: 'data-action="plan-save"' })}
        </div>
      </header>

      <div class="plan-editor-main">
        <section class="plan-editor-board-wrap">

          <div class="plan-editor-toolbar">
            <div class="plan-tool-switch" role="group" aria-label="Mode edition">
              <button type="button" class="metric-variant-btn ${selectedTool === 'select'  ? 'is-active' : ''}" data-action="plan-select-tool" data-tool="select">Deplacer et modifier</button>
              <button type="button" class="metric-variant-btn ${selectedTool === 'connect' ? 'is-active' : ''}" data-action="plan-select-tool" data-tool="connect">Connecter</button>
            </div>
            <div class="plan-toolbar-right">
              ${button({ label: '+ Zone', variant: 'outline', size: 'sm', attrs: 'data-action="plan-add-zone"' })}
              ${button({ label: 'Vider connexions', variant: 'outline', size: 'sm', attrs: 'data-action="plan-clear-links"' })}
            </div>
          </div>

          ${planCanvasHtml}

        </section>

        <aside class="plan-editor-aside">
          <div class="card plan-side-card">
            <h4>Palette</h4>
            <div class="plan-palette-list">${paletteMarkup}</div>
          </div>
        </aside>
      </div>
    </section>`;
};
