'use strict';

// EXPLICATION : Étend le namespace global window.UIComponents avec planCanvas (rendu du plan
// projet : zones, équipements/nodes, connexions/links) sans recréer l'IIFE de components.js.
// SÉCURITÉ/LOGIQUE : `window.UIComponents = window.UIComponents || {}` rend ce fichier robuste à
// l'ordre de chargement (peu importe si components.js ou components-plan.js est chargé en premier).
// Tout label/id affiché ici (zone, node, commentaire) passe par escHtml() reçu via `renderHelpers` — donnée
// éditable par l'utilisateur dans l'éditeur de plan, donc vecteur XSS potentiel si non échappée.
window.UIComponents = window.UIComponents || {};

Object.assign(window.UIComponents, {
  planCanvas(state, renderHelpers, options = { editMode: false }) {
    const { escHtml } = renderHelpers;
    const isEdit = !!options.editMode;

    const editor = state.planEditor || {};
    const zones = Array.isArray(editor.zones) ? editor.zones : [];
    const nodes = Array.isArray(editor.nodes) ? editor.nodes : [];
    const links = Array.isArray(editor.links) ? editor.links : [];

    const selectedNodeId = editor.selectedNodeId || null;
    const connectFromId = editor.connectFromId || null;

    const iconFolder = 'assets/plan-icons';
    const encodeIconSrc = (folder, file) => file ? encodeURI(`${folder}/${file}`) : '';

    const iconFiles = {
      'visu-box': 'Armoire_Distribution.png',
      'groupe': 'GroupeElectrogene.jpg',
      'moteur': 'motor.svg',
      'armoire_double': 'TGBT 1.png',
      'armoire_simple': 'TGBT 2.png',
      'coffret': 'Coffret_Distribution.png',
      'foodtruck': 'Food_Truck.png',
      'autre': 'tank.svg',
      'cablage-32': 'cablage/RAL VISU 32.png',
      'cablage-63': 'cablage/RAL VISU 63.png',
      'cablage-125': 'cablage/RAL VISU 125.png',
      'capteur1': 'capteur/capteur1.png',
      'capteur2': 'capteur/capteur2.png',
    };

    const fallbacks = {
      'visu-box': 'VB', 'groupe': 'GR', 'moteur': 'MO', 'armoire_double': 'AD',
      'armoire_simple': 'AS', 'coffret': 'CD', 'foodtruck': 'FT', 'autre': 'AP',
      'cablage-32': '32', 'cablage-63': '63', 'cablage-125': '125',
      'capteur1': 'C1', 'capteur2': 'C2', 'commentaire': '📝'
    };

    const nodeById = new Map(nodes.map((n) => [n.id, n]));

    // ── SVG connections lines ──
    const svgLines = links.map((l) => {
      const from = nodeById.get(l.from);
      const to = nodeById.get(l.to);
      if (!from || !to) return '';
      if (isEdit) {
        return `<line class="plan-link-line"
          data-link-id="${escHtml(l.id)}"
          data-link-from="${escHtml(l.from)}"
          data-link-to="${escHtml(l.to)}"
          x1="${from.x}%" y1="${from.y}%" x2="${to.x}%" y2="${to.y}%" />`;
      } else {
        return `<line class="plan-link-line" x1="${from.x}%" y1="${from.y}%" x2="${to.x}%" y2="${to.y}%"/>`;
      }
    }).join('');

    // ── Link remove buttons (edit mode only) ──
    const linkButtons = isEdit ? links.map((l) => {
      const from = nodeById.get(l.from);
      const to = nodeById.get(l.to);
      if (!from || !to) return '';
      const midX = (from.x + to.x) / 2;
      const midY = (from.y + to.y) / 2;
      return `<button class="plan-link-remove"
        style="left:${midX}%;top:${midY}%"
        data-action="plan-remove-link"
        data-link-from="${escHtml(l.from)}"
        data-link-to="${escHtml(l.to)}"
        data-link-id="${escHtml(l.id)}"
        title="Supprimer cette connexion">×</button>`;
    }).join('') : '';

    // ── Zones Rects ──
    const zoneRects = zones.map((z) => {
      if (isEdit) {
        return `<div
          class="plan-zone-rect"
          data-zone-id="${escHtml(z.id)}"
          style="left:${z.x}%;top:${z.y}%;width:${z.w}%;height:${z.h}%">
          <div class="plan-zone-header">
            <h4 class="plan-zone-rename-trigger" data-zone-id="${escHtml(z.id)}" title="Double-clic pour renommer">${escHtml(z.label)}</h4>
            <button class="plan-zone-delete" data-action="plan-delete-zone" data-zone-id="${escHtml(z.id)}" title="Supprimer la zone">×</button>
          </div>
          ${z.subtitle ? `<p>${escHtml(z.subtitle)}</p>` : ''}
          <div class="plan-zone-resize-handle" data-zone-id="${escHtml(z.id)}" title="Redimensionner la zone"></div>
        </div>`;
      } else {
        return `<div class="plan-zone-rect plan-zone-rect--readonly" style="left:${z.x}%;top:${z.y}%;width:${z.w}%;height:${z.h}%">
          <div class="plan-zone-header"><h4>${escHtml(z.label)}</h4></div>
        </div>`;
      }
    }).join('');

    // ── Nodes markup ──
    const nodeMarkup = nodes.map((n) => {
      const fb = fallbacks[n.type] || 'EQ';
      const file = iconFiles[n.type];
      const iconSrc = encodeIconSrc(iconFolder, file);

      if (isEdit) {
        const active = n.id === selectedNodeId;
        const connecting = connectFromId === n.id;
        const deleteBtn = `<button class="plan-node-delete" data-action="plan-delete-node" data-node-id="${escHtml(n.id)}" title="Supprimer">×</button>`;

        if (n.type === 'commentaire') {
          const lines = n.label.split('\n').map((line) => `<span>${escHtml(line)}</span>`).join('');
          return `<div class="plan-node-wrap" style="left:${n.x}%;top:${n.y}%" data-node-id="${escHtml(n.id)}">
            <button
              class="plan-node plan-comment-node${active ? ' is-active' : ''}"
              data-action="plan-select-node"
              data-node-id="${escHtml(n.id)}"
              title="Double-clic pour modifier le texte">
              <div class="plan-comment-text">${lines}</div>
            </button>
            ${deleteBtn}
          </div>`;
        }

        return `<div class="plan-node-wrap" style="left:${n.x}%;top:${n.y}%" data-node-id="${escHtml(n.id)}">
          <button
            class="plan-node${active ? ' is-active' : ''}${connecting ? ' is-connecting' : ''}"
            data-action="plan-select-node"
            data-node-id="${escHtml(n.id)}"
            title="${escHtml(n.label)}">
            <span class="plan-node-icon">
              <span class="plan-node-icon-spacer plan-node-icon-spacer--left"></span>
              ${iconSrc ? `<img src="${escHtml(iconSrc)}" alt="" draggable="false" onerror="this.style.display='none'">` : ''}
              <span class="plan-node-icon-spacer plan-node-icon-spacer--right"></span>
            </span>
            <span class="plan-node-label" data-node-id="${escHtml(n.id)}" title="Double-clic pour renommer">${escHtml(n.label)}</span>
          </button>
          ${deleteBtn}
        </div>`;
      } else {
        // Readonly mode
        return `<div class="plan-node plan-node--readonly" style="left:${n.x}%;top:${n.y}%" title="${escHtml(n.label)}">
          <span class="plan-node-icon">
            ${iconSrc ? `<img src="${escHtml(iconSrc)}" alt="" draggable="false" onerror="this.style.display='none'">` : ''}
            <span class="plan-node-fallback">${escHtml(fb)}</span>
          </span>
          <span class="plan-node-label">${escHtml(n.label)}</span>
        </div>`;
      }
    }).join('');

    const canvasAttrs = isEdit ? 'class="plan-board" aria-label="Plan projet editable"' : 'class="floor-plan-canvas plan-canvas--readonly"';

    return `
      <div ${canvasAttrs}>
        ${zoneRects}
        <svg class="plan-links-svg" xmlns="http://www.w3.org/2000/svg">${svgLines}</svg>
        ${linkButtons}
        ${nodeMarkup}
      </div>`;
  }
});
