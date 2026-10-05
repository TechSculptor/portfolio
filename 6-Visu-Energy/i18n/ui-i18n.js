'use strict';

// Ce module gere toute la logique de traduction (FR/EN):
// lecture des textes, chargement JSON, application des labels UI.
const uiI18n = (() => {
  function normalizeToken(value) {
    // Normalise une chaine pour comparer des valeurs utilisateur
    // sans etre sensible a la casse ou aux accents.
    return String(value || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  function tr(ctx, path) {
    // Lit une cle de traduction de type "a.b.c" dans le dictionnaire actif.
    // Fallback sur FR si la langue courante est incomplete.
    const dictionary = ctx.S.translations || {};
    const source = dictionary[ctx.S.lang] || dictionary.fr || {};
    return path.split('.').reduce((acc, key) => (acc && key in acc ? acc[key] : null), source);
  }

  function trText(ctx, path, fallback) {
    // Retourne toujours une string exploitable par l'UI.
    const value = tr(ctx, path);
    return typeof value === 'string' ? value : fallback;
  }

  function updateDocumentTitle(ctx) {
    document.title = trText(ctx, 'meta.appTitle', 'VISU-ENERGY');
  }

  async function loadTranslations(ctx) {
    // Certains formats JSON sont { fr: {...} } et d'autres directement {...}.
    // Cette fonction uniformise les deux formats.
    const unwrapLocalePayload = (payload, locale) => {
      if (!payload || typeof payload !== 'object') return null;
      if (payload[locale] && typeof payload[locale] === 'object') return payload[locale];
      return payload;
    };

    const loadLocale = async (locale) => {
      // On tente plusieurs chemins pour supporter les differents contextes
      // (serveur web, sous-dossier, base path Vite, etc.).
      const basePath = window.location.pathname.replace(/[^/]*$/, '');
      const candidates = [
        `i18n/${locale}.json`,
        `./i18n/${locale}.json`,
        `${basePath}i18n/${locale}.json`,
      ];

      for (const path of candidates) {
        try {
          const res = await fetch(path, { cache: 'no-store' });
          if (!res.ok) continue;
          const payload = await res.json();
          const localeObj = unwrapLocalePayload(payload, locale);
          if (localeObj && typeof localeObj === 'object') return localeObj;
        } catch (_) {
          // try next path candidate
        }
      }
      return null;
    };

    // Chargement FR/EN en parallele pour reduire le temps d'attente.
    const [fr, en] = await Promise.all([loadLocale('fr'), loadLocale('en')]);
    const next = { fr: fr || {}, en: en || {} };
    ctx.S.translations = next;

    // On garde une trace de la source i18n pour afficher un indicateur de sante.
    const hasFr = Object.keys(next.fr).length > 0;
    const hasEn = Object.keys(next.en).length > 0;
    if (!hasFr || !hasEn) {
      ctx.S.i18nSource = 'missing';
      console.warn('[i18n] Impossible de charger completement fr/en depuis i18n/*.json');
    } else if (fr && en) {
      ctx.S.i18nSource = 'external';
    }

    ctx.render();
  }

  function localizeProjectStatus(ctx, status) {
    // Accepte plusieurs variantes du meme statut (FR/EN) et renvoie
    // la version traduite standardisee pour l'interface.
    const s = normalizeToken(status);
    if (s === 'en preparation' || s === 'preparing') return trText(ctx, 'project.status.preparation', 'En preparation');
    if (s === 'en cours' || s === 'in progress') return trText(ctx, 'project.status.inProgress', 'En cours');
    if (s === 'termine' || s === 'completed') return trText(ctx, 'project.status.completed', 'Termine');
    return String(status || trText(ctx, 'common.notDefined', 'Non defini'));
  }

  function restoreLanguage(ctx) {
    // Recharge la langue memorisee localement entre deux sessions.
    const saved = localStorage.getItem('visu_lang');
    if (saved === 'fr' || saved === 'en') ctx.S.lang = saved;
  }

  function applyLanguage(ctx) {
    // Met a jour tous les labels statiques hors zone de rendu principal.
    updateDocumentTitle(ctx);

    const navDashboard = document.getElementById('nav-dashboard-label');
    const navExport = document.getElementById('nav-export-label');
    const navIncidents = document.getElementById('nav-incidents-label');
    const navSettings = document.getElementById('nav-settings-label');
    const loginSubtitle = document.getElementById('login-brand-subtitle');
    const loginUsername = document.getElementById('login-user-label');
    const loginPassword = document.getElementById('login-pass-label');
    const loginSubmit = document.getElementById('login-submit-label');
    const logoutBtn = document.getElementById('logout-btn-label');
    const themeBtn = document.getElementById('theme-btn-label');
    const langBtn = document.getElementById('lang-btn');
    const loginUsernameInput = document.getElementById('login-user');
    const loginPasswordInput = document.getElementById('login-pass');
    const notifTitle = document.getElementById('notif-title');
    // Les éléments #notif-item-N ne sont plus traduits ici. Ils étaient des
    // exemples de maquette du temps où la liste était statique dans index.html.
    // Depuis, topbar.js la remplit avec les VRAIES alarmes, en réutilisant ces
    // mêmes identifiants : ces trois lignes écrasaient donc le texte des trois
    // premières alarmes par « Alerte tension levée sur VISU-BOX 02 » et
    // compagnie. Le contenu réel vient de la base, il n'a rien à traduire ici.
    const notifBtn = document.getElementById('notif-btn');
    const settingsBtn = document.getElementById('settings-btn');
    const i18nChip = document.getElementById('i18n-chip');
    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('topbar-search-input');
    const notifCloseBtn = document.querySelector('.notif-close-btn');
    const adminModalCloseBtn = document.querySelector('[data-action="close-admin-modal"]');
    const adminTargetLoginInput = document.getElementById('admin-target-login');
    const sideNav = document.querySelector('.side-nav');
    const manageAccountBtn = document.getElementById('manage-account-btn-label');
    const navAbout = document.getElementById('nav-about-label');
    const navFaq = document.getElementById('nav-faq-label');
    const chatSendBtn = document.getElementById('chat-send-btn');
    const chatToggleBtnEl = document.getElementById('chat-toggle-btn');
    const chatHeaderTitle = document.getElementById('chat-header-title');
    const chatIntroHint = document.getElementById('chat-intro-hint');
    const chatInput = document.getElementById('chat-input');
    const themeBtnTopbar = document.getElementById('theme-btn');

    if (navDashboard) navDashboard.textContent = trText(ctx, 'navPark', 'Gestion du parc');
    if (navExport) navExport.textContent = trText(ctx, 'navExport', 'Export CSV');
    if (navIncidents) navIncidents.textContent = trText(ctx, 'navIncidents', 'Incidents');
    if (navSettings) navSettings.textContent = trText(ctx, 'settings.nav.title', 'Paramètres');
    if (loginSubtitle) loginSubtitle.textContent = trText(ctx, 'loginSubtitle', 'Connexion');
    if (loginUsername) loginUsername.textContent = trText(ctx, 'loginUsername', 'Identifiant');
    if (loginPassword) loginPassword.textContent = trText(ctx, 'loginPassword', 'Mot de passe');
    if (loginSubmit) loginSubmit.textContent = trText(ctx, 'loginSubmit', 'Accéder au dashboard');
    if (logoutBtn) logoutBtn.textContent = trText(ctx, 'logout', 'Se deconnecter');
    if (themeBtn) themeBtn.textContent = trText(ctx, 'theme', 'Theme');
    if (langBtn) langBtn.textContent = ctx.S.lang.toUpperCase();
    const loginLangBtn = document.getElementById('login-lang-btn');
    if (loginLangBtn) loginLangBtn.textContent = ctx.S.lang.toUpperCase();

    // Aria-labels des boutons statiques.
    const changeLangLabel = trText(ctx, 'common.changeLanguage', 'Changer de langue');
    const changeThemeLabel = trText(ctx, 'common.changeTheme', 'Changer de thème');
    const openMenuLabel = trText(ctx, 'common.openMenu', 'Ouvrir le menu');
    const closeMenuLabel = trText(ctx, 'common.closeMenu', 'Fermer le menu');
    const showPwdLabel = trText(ctx, 'common.showPassword', 'Afficher le mot de passe');
    const hidePwdLabel = trText(ctx, 'common.hidePassword', 'Masquer le mot de passe');

    if (langBtn) { langBtn.setAttribute('aria-label', changeLangLabel); langBtn.title = changeLangLabel; }
    if (loginLangBtn) { loginLangBtn.setAttribute('aria-label', changeLangLabel); loginLangBtn.title = changeLangLabel; }
    const loginThemeBtn = document.getElementById('login-theme-btn');
    if (loginThemeBtn) { loginThemeBtn.setAttribute('aria-label', changeThemeLabel); loginThemeBtn.title = changeThemeLabel; }
    const sidebarCloseBtn = document.querySelector('[data-action="sidebar-close"].sidebar-close-btn');
    if (sidebarCloseBtn) { sidebarCloseBtn.setAttribute('aria-label', closeMenuLabel); sidebarCloseBtn.title = closeMenuLabel; }
    const sidebarBurgerBtn = document.querySelector('[data-action="sidebar-toggle"]');
    if (sidebarBurgerBtn) { sidebarBurgerBtn.setAttribute('aria-label', openMenuLabel); sidebarBurgerBtn.title = openMenuLabel; }
    document.querySelectorAll('[data-action="toggle-password-visibility"]').forEach((btn) => {
      const pressed = btn.getAttribute('aria-pressed') === 'true';
      const label = pressed ? hidePwdLabel : showPwdLabel;
      btn.setAttribute('aria-label', label);
      btn.title = label;
    });

    // Re-traduit les bandeaux de feedback visibles (login + admin).
    ['login-feedback', 'admin-feedback'].forEach((id) => {
      const el = document.getElementById(id);
      if (el && !el.hidden && el.dataset.feedbackKey) {
        el.textContent = trText(ctx, el.dataset.feedbackKey, el.dataset.feedbackFallback || '');
      }
    });

    // Labels du panneau admin.
    const adminTitle   = document.getElementById('admin-panel-title');
    const adminSub     = document.getElementById('admin-panel-subtitle');
    const adminTarget  = document.getElementById('admin-target-login-label');
    const adminNewPwd  = document.getElementById('admin-new-password-label');
    const adminConfirm = document.getElementById('admin-confirm-password-label');
    const adminSubmit  = document.getElementById('admin-password-label');
    const adminOpenBtn = document.getElementById('admin-panel-btn');
    if (adminTitle)   adminTitle.textContent   = trText(ctx, 'adminPanel.title',         'Gestion des mots de passe');
    if (adminSub)     adminSub.textContent     = trText(ctx, 'adminPanel.subtitle',      'Réservé à l\'administrateur');
    if (adminTarget)  adminTarget.textContent  = trText(ctx, 'adminPanel.targetLogin',   'Identifiant de l\'utilisateur');
    if (adminNewPwd)  adminNewPwd.textContent  = trText(ctx, 'adminPanel.newPassword',   'Nouveau mot de passe');
    if (adminConfirm) adminConfirm.textContent = trText(ctx, 'adminPanel.confirmPassword','Confirmer le mot de passe');
    if (adminSubmit)  adminSubmit.textContent  = trText(ctx, 'adminPanel.submit',        'Changer le mot de passe');
    if (adminOpenBtn) adminOpenBtn.textContent = trText(ctx, 'adminPanel.openBtn',       'Admin');
    if (loginUsernameInput) loginUsernameInput.placeholder = trText(ctx, 'loginUsernamePlaceholder', 'Votre identifiant');
    if (loginPasswordInput) loginPasswordInput.placeholder = trText(ctx, 'loginPasswordPlaceholder', '••••••••');
    if (notifTitle) notifTitle.textContent = trText(ctx, 'topbar.notifications.title', 'Notifications');
    if (notifBtn) {
      const notifLabel = trText(ctx, 'topbar.notifications.button', 'Notifications');
      notifBtn.setAttribute('aria-label', notifLabel);
      notifBtn.title = notifLabel;
    }
    if (settingsBtn) {
      const settingsLabel = trText(ctx, 'topbar.settings.button', 'Parametres');
      settingsBtn.setAttribute('aria-label', settingsLabel);
      settingsBtn.title = settingsLabel;
    }
    if (searchBtn) {
      const searchLabel = trText(ctx, 'topbar.search.button', 'Rechercher');
      searchBtn.setAttribute('aria-label', searchLabel);
      searchBtn.title = searchLabel;
    }
    if (searchInput) searchInput.placeholder = trText(ctx, 'topbar.search.placeholder', 'Rechercher...');
    if (notifCloseBtn) {
      const closeLabel = trText(ctx, 'common.close', 'Fermer');
      notifCloseBtn.setAttribute('aria-label', closeLabel);
      notifCloseBtn.title = closeLabel;
    }
    if (adminModalCloseBtn) adminModalCloseBtn.setAttribute('aria-label', trText(ctx, 'common.close', 'Fermer'));
    if (adminTargetLoginInput) adminTargetLoginInput.placeholder = trText(ctx, 'adminPanel.targetLoginPlaceholder', 'Identifiant');
    if (sideNav) sideNav.setAttribute('aria-label', trText(ctx, 'common.mainNavigation', 'Navigation principale'));
    if (manageAccountBtn) manageAccountBtn.textContent = trText(ctx, 'manageAccount', 'Gérer mon compte');
    if (navAbout) navAbout.textContent = trText(ctx, 'navAbout', 'À propos');
    if (navFaq) navFaq.textContent = trText(ctx, 'navFaq', 'FAQ');
    if (chatSendBtn) chatSendBtn.textContent = trText(ctx, 'chatSend', 'Envoyer');
    if (chatToggleBtnEl) {
      const chatLabel = trText(ctx, 'chatOpenAssistant', "Ouvrir l'assistant IA");
      chatToggleBtnEl.setAttribute('aria-label', chatLabel);
      chatToggleBtnEl.title = trText(ctx, 'chatWithAI', "Discuter avec l'IA");
    }
    if (chatHeaderTitle) chatHeaderTitle.textContent = trText(ctx, 'chatTitle', 'Assistant Visu-IA');
    if (chatIntroHint) chatIntroHint.textContent = trText(ctx, 'chatIntroHint', "Besoin d'aide ? Discutez avec l'assistant");
    if (chatInput) chatInput.placeholder = trText(ctx, 'chatInputPlaceholder', 'Posez une question...');
    if (themeBtnTopbar) {
      const themeLabel = trText(ctx, 'common.changeTheme', 'Changer de thème');
      themeBtnTopbar.setAttribute('aria-label', themeLabel);
      themeBtnTopbar.title = themeLabel;
    }
    if (i18nChip) {
      // Le chip apparait seulement si les JSON i18n sont incomplets/introuvables.
      if (ctx.S.i18nSource === 'missing') {
        i18nChip.hidden = false;
        i18nChip.textContent = trText(ctx, 'i18nNotice.missing', 'Traductions JSON introuvables');
      } else {
        i18nChip.hidden = true;
      }
    }
  }

  function toggleLanguage(ctx) {
    // Alterne FR/EN puis persiste le choix.
    ctx.S.lang = ctx.S.lang === 'fr' ? 'en' : 'fr';
    document.documentElement.setAttribute('lang', ctx.S.lang);
    localStorage.setItem('visu_lang', ctx.S.lang);
    const activeLocale = ctx.S.translations[ctx.S.lang] || {};
    // Si le dictionnaire est vide, on relance un chargement a la demande.
    if (!Object.keys(activeLocale).length) {
      loadTranslations(ctx);
    }
    // applyLanguage ici directement: le renderer retourne tot quand non connecte,
    // ce qui empeche applyLanguage de s'executer via ctx.render() sur l'ecran login.
    applyLanguage(ctx);
    ctx.render();
  }

  return {
    normalizeToken,
    tr,
    trText,
    loadTranslations,
    localizeProjectStatus,
    restoreLanguage,
    applyLanguage,
    toggleLanguage,
    updateDocumentTitle,
  };
})();
