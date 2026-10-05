'use strict';

window.UISettingsParts = window.UISettingsParts || {};

(() => {
  const iconEdit = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
  const iconTrash = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>`;
  const iconMove = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="5" y1="8" x2="19" y2="8"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="5" y1="16" x2="19" y2="16"/></svg>`;
  const iconChevronDown = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>`;
  const iconArchive = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8"/><line x1="10" y1="12" x2="14" y2="12"/></svg>`;
  const iconUnarchive = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><polyline points="3 4 3 8 7 8"/></svg>`;


  /** Compose le nom complet d'une séquence : "Séquence {ordre}" + le texte saisi par l'utilisateur. */
  function composeSequenceName(ordre, suffix) {
    const n = parseInt(ordre, 10) || 1;
    const s = (suffix || '').trim();
    return s ? `Séquence ${n} : ${s}` : `Séquence ${n}`;
  }

  /** Retire le préfixe "Séquence {n} : " d'un nom complet pour ne garder que le texte saisi (édition). */
  function splitSequenceName(fullName) {
    return (fullName || '').replace(/^Séquence\s+\d+\s*:?\s*/i, '').trim();
  }

  /**
   * Statut calculé d'une séquence (non stocké en base) :
   * - 'archivee' si is_archived
   * - 'cloturee' si date_fin renseignée et passée
   * - 'en_cours' sinon (pas de date de fin, ou date de fin future)
   */
  function computeSequenceStatus(seq) {
    if (seq?.is_archived) return 'archivee';
    if (seq?.date_fin && new Date(seq.date_fin) < new Date()) return 'cloturee';
    return 'en_cours';
  }

  const SEQUENCE_STATUS_LABELS = { en_cours: 'En cours', cloturee: 'Clôturée', archivee: 'Archivée' };

  function buildSequenceStatusBadge(seq) {
    const status = computeSequenceStatus(seq);
    return `<span class="sp-status-badge sp-status-${status}">${SEQUENCE_STATUS_LABELS[status]}</span>`;
  }

  /**
   * Attache Flatpickr (déjà chargé globalement, cf ui-actions-export.js) aux champs
   * de date début/fin d'un formulaire de séquence, en désactivant et coloriant en
   * rouge (classe sp-day-occupied, cf CSS) les jours déjà couverts par une autre
   * séquence du même projet — empêche la création/édition dans une plage occupée.
   */
  function initSequenceDatePickers(startId, endId, existingSequences, excludeId) {
    if (typeof flatpickr === 'undefined') return;
    const disable = (existingSequences || [])
      .filter((s) => String(s.id) !== String(excludeId) && s.date_debut && s.date_fin)
      .map((s) => ({ from: s.date_debut.slice(0, 10), to: s.date_fin.slice(0, 10) }));
    const onDayCreate = (dObj, dStr, fp, dayElem) => {
      if (dayElem.classList.contains('flatpickr-disabled')) dayElem.classList.add('sp-day-occupied');
    };
    [startId, endId].forEach((id) => {
      const input = document.getElementById(id);
      if (!input || input._flatpickr) return;
      flatpickr(input, { dateFormat: 'Y-m-d', locale: 'fr', disable, onDayCreate });
    });
  }

  /** Liste en surbrillance des périodes déjà planifiées pour ce projet, pour aider à choisir des dates libres. */
  function buildPlannedPeriodsHint(existingSequences, excludeId) {
    const planned = (existingSequences || [])
      .filter((s) => String(s.id) !== String(excludeId) && s.date_debut && s.date_fin)
      .sort((a, b) => new Date(a.date_debut) - new Date(b.date_debut));
    if (!planned.length) return '';
    return `
      <div class="sp-planned-hint">
        <span class="sp-planned-hint-label">Périodes déjà prises :</span>
        ${planned.map((s) => `<span class="sp-planned-chip">${escHtml(s.nom_sequence || '—')} : ${escHtml(s.date_debut.slice(0, 10))} → ${escHtml(s.date_fin.slice(0, 10))}</span>`).join('')}
      </div>`;
  }

  function validateSequencePayload(payload, existingSequences = [], excludeId = null) {
    const errors = [];
    const nomSeq = String(payload.nom_sequence || '').trim();
    if (!nomSeq) {
      errors.push('Le nom de la séquence est obligatoire.');
    } else {
      // L'unicité porte sur le texte saisi (sans le préfixe "Séquence N :"),
      // et uniquement au sein du même projet (existingSequences est déjà filtré par projet).
      const suffix = splitSequenceName(nomSeq).toLowerCase();
      if (suffix) {
        const nameConflict = existingSequences.find(
          (s) => String(s.id) !== String(excludeId) && splitSequenceName(s.nom_sequence || '').toLowerCase() === suffix
        );
        if (nameConflict) {
          errors.push(`Une séquence nommée « ${splitSequenceName(nomSeq)} » existe déjà pour ce projet. Choisissez un autre nom.`);
        }
      }
    }
    const ordre = parseInt(payload.ordre, 10);
    if (isNaN(ordre) || ordre < 1) {
      errors.push('L\'ordre doit être un entier ≥ 1.');
    } else {
      const conflict = existingSequences.find(
        (s) => String(s.id) !== String(excludeId) && parseInt(s.ordre, 10) === ordre
      );
      if (conflict) {
        errors.push(`La position ${ordre} est déjà occupée par « ${conflict.nom_sequence || '—'} ». Choisissez un autre ordre.`);
      }
    }
    const hasStart = !!(payload.date_debut || '').trim();
    const hasEnd   = !!(payload.date_fin   || '').trim();
    if (hasStart !== hasEnd) {
      errors.push('Les deux dates de planification doivent être renseignées ensemble (début et fin).');
    } else if (hasStart && hasEnd && payload.date_debut > payload.date_fin) {
      errors.push('La date de fin doit être égale ou postérieure à la date de début.');
    } else if (hasStart && hasEnd) {
      const overlap = existingSequences.find((s) => {
        if (String(s.id) === String(excludeId)) return false;
        const sStart = (s.date_debut || '').trim();
        const sEnd   = (s.date_fin   || '').trim();
        if (!sStart || !sEnd) return false;
        return payload.date_debut <= sEnd && sStart <= payload.date_fin;
      });
      if (overlap) {
        errors.push(`Les dates chevauchent la séquence « ${overlap.nom_sequence || '—'} » (${overlap.date_debut} → ${overlap.date_fin}).`);
      }
    }
    return errors;
  }

  /**
   * Sélecteur visuel de position (ORDRE), inspiré du style "Progression du
   * projet" : une rangée de cercles numérotés au lieu d'un simple input.
   * Cliquer un cercle pose sa valeur dans l'input caché #${hiddenId}.
   */
  function buildOrderPicker(existingSequences, selectedOrdre, hiddenId, excludeId) {
    const steps = (existingSequences || [])
      .filter((s) => String(s.id) !== String(excludeId))
      .slice()
      .sort((a, b) => (parseInt(a.ordre, 10) || 0) - (parseInt(b.ordre, 10) || 0));
    const maxOrdre = steps.reduce((m, s) => Math.max(m, parseInt(s.ordre, 10) || 0), 0);
    const newOrdre = maxOrdre + 1;
    const sel = selectedOrdre != null ? parseInt(selectedOrdre, 10) : newOrdre;

    const stepHtml = (ordre, label, isNew) => `
      <div class="sp-order-step ${ordre === sel ? 'is-selected' : ''} ${isNew ? 'is-new' : ''}"
           data-action="pick-ordre" data-ordre="${ordre}" data-target="${hiddenId}">
        <div class="sp-order-circle">${ordre}</div>
        <span class="sp-order-label">${escHtml(label || '')}</span>
      </div>`;

    return `
      <div class="sp-order-picker" id="${hiddenId}-picker">
        ${steps.map((s) => stepHtml(parseInt(s.ordre, 10) || 0, s.nom_sequence, false)).join('')}
        ${stepHtml(newOrdre, 'Nouvelle position', true)}
      </div>
      <input type="hidden" id="${hiddenId}" value="${escHtml(String(sel))}">`;
  }

  function buildSequenceMoveForm(seq, projets) {
    const opts = (projets || []).map((p) =>
      `<option value="${escHtml(String(p.id))}" ${String(p.id) === String(seq?.projet_id) ? 'selected' : ''}>${escHtml(p.nom_projet || p.name || '')}</option>`
    ).join('') || '<option value="">—</option>';
    return `
      <div class="sp-panel is-form-sm">
        <div class="sp-head"><p class="sp-head-title">Déplacer la séquence</p></div>
        <div class="sp-body">
          <div class="sp-field">
            <label class="sp-label">SÉQUENCE</label>
            <p class="sp-value-static">${escHtml(seq?.nom_sequence || '—')}</p>
            <input type="hidden" id="sp-seq-move-id" value="${escHtml(String(seq?.id || ''))}">
          </div>
          <div class="sp-field">
            <label class="sp-label" for="sp-seq-move-projet">PROJET DE DESTINATION</label>
            <select class="input" id="sp-seq-move-projet">${opts}</select>
          </div>
        </div>
        <div class="sp-footer">
          <button class="btn btn-gold" data-action="sp-save-sequence-move">Déplacer</button>
          <button class="btn btn-outline" data-action="sp-cancel">Annuler</button>
        </div>
      </div>`;
  }

  function buildSequenceCreateForm(projetId, projets, existingSequences) {
    const opts = (projets || []).map((p) =>
      `<option value="${escHtml(String(p.id))}" ${String(p.id) === String(projetId) ? 'selected' : ''}>${escHtml(p.nom_projet || p.name || '')}</option>`
    ).join('') || '<option value="">—</option>';
    return `
      <div class="sp-panel is-form-sm">
        <div class="sp-head"><p class="sp-head-title">Nouvelle séquence</p></div>
        <div class="sp-body">
          <div class="sp-field">
            <label class="sp-label" for="sp-event-projet-id">PROJET</label>
            <select class="input" id="sp-event-projet-id">${opts}</select>
          </div>
          <div class="sp-field">
            <label class="sp-label" for="sp-event-nom">NOM DE LA SÉQUENCE — sera précédé de « Séquence {n} : »</label>
            <input class="input" id="sp-event-nom" type="text" placeholder="ex: Montage">
          </div>
          <div class="sp-field">
            <label class="sp-label">ORDRE — cliquez une position</label>
            ${buildOrderPicker(existingSequences, null, 'sp-event-ordre', null)}
          </div>
          <div class="sp-field">
            <label class="sp-label">PLANIFICATION (début et fin obligatoires si renseignées)</label>
            ${buildPlannedPeriodsHint(existingSequences, null)}
            <div class="sp-row-fields">
              <input class="input" id="sp-event-date-start" type="text" readonly placeholder="Date début">
              <input class="input" id="sp-event-date-end" type="text" readonly placeholder="Date fin">
            </div>
          </div>
          <div class="sp-field">
            <label class="sp-label" for="sp-event-description">DESCRIPTION (optionnelle)</label>
            <textarea class="input" id="sp-event-description" rows="2" placeholder="Contexte métier : phase de test, production de nuit, maintenance..."></textarea>
          </div>
          <div class="sp-form-actions">
            <button class="btn btn-gold" data-action="sp-save-event-add">Créer la séquence</button>
            <button class="btn btn-outline" data-action="sp-cancel">Annuler</button>
          </div>
        </div>
      </div>`;
  }

  function buildSequenceEditForm(seq, existingSequences) {
    return `
      <div class="sp-panel is-form-sm">
        <div class="sp-head"><p class="sp-head-title">Modifier la séquence</p></div>
        <div class="sp-body">
          <div class="sp-field">
            <label class="sp-label" for="sp-seq-nom">NOM DE LA SÉQUENCE — sera précédé de « Séquence {n} : »</label>
            <input class="input" id="sp-seq-nom" type="text" value="${escHtml(splitSequenceName(seq?.nom_sequence || ''))}" placeholder="ex: Montage">
          </div>
          <div class="sp-field">
            <label class="sp-label">ORDRE — cliquez une position</label>
            ${buildOrderPicker(existingSequences, seq?.ordre, 'sp-seq-ordre', seq?.id)}
          </div>
          <div class="sp-field">
            <label class="sp-label">PLANIFICATION (début et fin obligatoires si renseignées)</label>
            ${buildPlannedPeriodsHint(existingSequences, seq?.id)}
            <div class="sp-row-fields">
              <input class="input" id="sp-seq-date-start" type="text" readonly value="${escHtml((seq?.date_debut || '').slice(0, 10))}">
              <input class="input" id="sp-seq-date-end" type="text" readonly value="${escHtml((seq?.date_fin || '').slice(0, 10))}">
            </div>
          </div>
          <div class="sp-field">
            <label class="sp-label" for="sp-seq-description">DESCRIPTION (optionnelle)</label>
            <textarea class="input" id="sp-seq-description" rows="2" placeholder="Contexte métier : phase de test, production de nuit, maintenance...">${escHtml(seq?.description || '')}</textarea>
          </div>
          <div class="sp-form-actions">
            <input type="hidden" id="sp-seq-id" value="${escHtml(String(seq?.id || ''))}">
            <button class="btn btn-gold" data-action="sp-save-sequence-edit">Sauvegarder</button>
            <button class="btn btn-outline" data-action="sp-cancel">Annuler</button>
          </div>
        </div>
      </div>`;
  }

  function buildAdminSequencesSection(state, renderHelpers) {
    const { tt, escHtml } = renderHelpers;
    const seqByProjId = {};
    (state.adminSequences || []).forEach((s) => {
      if (!seqByProjId[s.projet_id]) {
        seqByProjId[s.projet_id] = { projet_id: s.projet_id, nom_projet: s.nom_projet, sequences: [] };
      }
      seqByProjId[s.projet_id].sequences.push(s);
    });
    (state.adminProjets || []).forEach((p) => {
      if (!seqByProjId[p.id]) {
        seqByProjId[p.id] = { projet_id: p.id, nom_projet: p.nom_projet, sequences: [] };
      }
    });
    const projetsAvecSeq = Object.values(seqByProjId);

    return `
      <div class="settings-section card">
        <div class="settings-section-head">
          <div class="settings-num-badge">04</div>
          <div class="settings-section-title">
            <h4>${escHtml(tt(state, 'settings.admin.seqTitle', 'Séquences'))}</h4>
            <p class="settings-section-desc">${escHtml(tt(state, 'settings.admin.seqDesc', 'Gérer les phases (séquences) de chaque projet.'))}</p>
          </div>
        </div>
        <div class="settings-section-body">
          <div class="settings-table-scroll">
          <table class="settings-table">
            <thead><tr>
              <th>${escHtml(tt(state, 'settings.admin.seqName', 'NOM DE LA SÉQUENCE'))}</th>
              <th>${escHtml(tt(state, 'settings.admin.seqDates', 'DATES'))}</th>
              <th>${escHtml(tt(state, 'settings.admin.seqStatus', 'STATUT'))}</th>
              <th>${escHtml(tt(state, 'settings.admin.actions', 'ACTIONS'))}</th>
            </tr></thead>
            <tbody>
              ${projetsAvecSeq.map((pg) => `
                <tr class="settings-table-group-row" data-group="seq-${pg.projet_id}">
                  <td colspan="3">${escHtml(pg.nom_projet || '')} <span style="opacity:.55;font-size:.8em">(${pg.sequences.length})</span></td>
                  <td><div class="settings-row-actions">
                    <button class="btn btn-ghost" data-action="admin-create-sequence" data-projet-id="${pg.projet_id}" style="font-size:.8em;padding:2px 8px">+ Ajouter</button>
                    <button class="btn btn-ghost btn-icon" data-action="toggle-group" data-group="seq-${pg.projet_id}" aria-label="Déplier">${iconChevronDown}</button>
                  </div></td>
                </tr>
                ${pg.sequences.map((s) => {
                  const deb = s.date_debut ? fmtDateLong(s.date_debut) : '';
                  const fin = s.date_fin   ? fmtDateLong(s.date_fin)   : '';
                  const dates = (deb || fin) ? `${deb} → ${fin}` : '—';
                  const status = computeSequenceStatus(s);
                  return `
                  <tr class="settings-table-child-row" data-group="seq-${pg.projet_id}">
                    <td class="settings-table-child-cell">${escHtml(s.nom_sequence || '')}</td>
                    <td>${escHtml(dates)}</td>
                    <td>${buildSequenceStatusBadge(s)}</td>
                    <td><div class="settings-row-actions">
                      <button class="btn btn-ghost btn-icon" data-action="admin-move-sequence" data-id="${s.id}" data-drag-group="seq-${pg.projet_id}" aria-label="${escHtml(tt(state, 'common.move', 'Déplacer'))}">${iconMove}</button>
                      <button class="btn btn-ghost btn-icon" data-action="admin-edit-sequence" data-id="${s.id}" aria-label="${escHtml(tt(state, 'common.edit', 'Modifier'))}">${iconEdit}</button>
                      <button class="btn btn-ghost btn-icon" data-action="admin-toggle-archive-sequence" data-id="${s.id}" data-archived="${status === 'archivee' ? '1' : '0'}" aria-label="${status === 'archivee' ? 'Désarchiver' : 'Archiver'}">${status === 'archivee' ? iconUnarchive : iconArchive}</button>
                      <button class="btn btn-ghost btn-icon" data-action="admin-delete-sequence" data-id="${s.id}" aria-label="${escHtml(tt(state, 'common.delete', 'Supprimer'))}">${iconTrash}</button>
                    </div></td>
                  </tr>`;
                }).join('')}
              `).join('')}
            </tbody>
          </table>
          </div>
        </div>
      </div>`;
  }

  window.UISettingsParts.validateSequencePayload = validateSequencePayload;
  window.UISettingsParts.composeSequenceName = composeSequenceName;
  window.UISettingsParts.splitSequenceName = splitSequenceName;
  window.UISettingsParts.computeSequenceStatus = computeSequenceStatus;
  window.UISettingsParts.buildSequenceStatusBadge = buildSequenceStatusBadge;
  window.UISettingsParts.initSequenceDatePickers = initSequenceDatePickers;
  window.UISettingsParts.buildSequenceMoveForm = buildSequenceMoveForm;
  window.UISettingsParts.buildSequenceCreateForm = buildSequenceCreateForm;
  window.UISettingsParts.buildSequenceEditForm = buildSequenceEditForm;
  window.UISettingsParts.buildAdminSequencesSection = buildAdminSequencesSection;
})();
