'use strict';

/**
 * api-sequences.js
 *
 * Requêtes CRUD pour les séquences de projets.
 * Étend apiDataLoader.
 */
Object.assign(apiDataLoader, (() => {

  async function loadSequences(projetId = null) {
    const qs = projetId ? `?projet_id=${encodeURIComponent(projetId)}` : '';
    return apiDataLoader._get(`/api/sequences${qs}`);
  }

  async function createSequence(payload) {
    return apiDataLoader._post('/api/sequences', payload);
  }

  async function editSequence(id, payload) {
    const url = `/api/sequences/${encodeURIComponent(id)}`;
    const resp = await fetch(url, {
      method: 'PATCH',
      headers: apiDataLoader._headers(),
      body: JSON.stringify(payload),
    });
    if (resp.status === 401) { auth.logout(); throw new Error('Session expirée'); }
    if (!resp.ok) throw new Error(await apiDataLoader._errorMessage(resp, url));
    return resp.json();
  }

  async function deleteSequence(id) {
    const url = `/api/sequences/${encodeURIComponent(id)}`;
    const resp = await fetch(url, {
      method: 'DELETE',
      headers: apiDataLoader._headers(),
    });
    if (resp.status === 401) { auth.logout(); throw new Error('Session expirée'); }
    if (!resp.ok) throw new Error(await apiDataLoader._errorMessage(resp, url));
    return resp.json();
  }

  return {
    loadSequences,
    createSequence,
    editSequence,
    deleteSequence
  };

})());
