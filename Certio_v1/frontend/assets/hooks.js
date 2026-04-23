/* ============================================================================
   hooks.js — Hooks React custom
   Plateforme d'examens IPSSI — Phase P2.4

   Bibliothèque de hooks React réutilisables, exposée sur `window.UIHooks`.

   Hooks exportés (8) :
     useTheme         - Gestion thème clair/sombre + accessibilité
     useTranslation   - i18n avec fallback (FR par défaut)
     useApi           - Wrapper fetch avec gestion auth + erreurs
     useAuth          - État de session + login/logout
     useDebounce      - Valeur debouncée
     useLocalStorage  - State synchronisé avec localStorage
     useKeyboardShortcut - Raccourcis clavier
     useModal         - Gestion d'état modale

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useCallback, useRef, useMemo } = React;

  /* ==========================================================================
     1. useTheme - Gestion thème clair/sombre + accessibilité
     Retour : { theme, setTheme, toggle, accessibility, setAccessibility }
     ========================================================================== */

  function useTheme(storageKey = 'theme') {
    const [theme, setThemeState] = useState(() => {
      try {
        return localStorage.getItem(storageKey) ||
               document.documentElement.getAttribute('data-theme') ||
               'light';
      } catch (e) { return 'light'; }
    });

    const [accessibility, setAccessibilityState] = useState(() => {
      try {
        const raw = localStorage.getItem('accessibility');
        return raw ? JSON.parse(raw) : { font: null, contrast: null, size: null, motion: null };
      } catch (e) { return { font: null, contrast: null, size: null, motion: null }; }
    });

    // Synchroniser thème avec attribut HTML
    useEffect(() => {
      document.documentElement.setAttribute('data-theme', theme);
      try { localStorage.setItem(storageKey, theme); } catch (e) {}
    }, [theme, storageKey]);

    // Synchroniser accessibilité
    useEffect(() => {
      const html = document.documentElement;
      ['font', 'contrast', 'size', 'motion'].forEach(key => {
        if (accessibility[key]) {
          html.setAttribute(`data-${key}`, accessibility[key]);
        } else {
          html.removeAttribute(`data-${key}`);
        }
      });
      try { localStorage.setItem('accessibility', JSON.stringify(accessibility)); } catch (e) {}
    }, [accessibility]);

    const setTheme = useCallback((t) => setThemeState(t), []);
    const toggle = useCallback(() => setThemeState(t => t === 'dark' ? 'light' : 'dark'), []);
    const setAccessibility = useCallback((updates) => {
      setAccessibilityState(prev => ({ ...prev, ...updates }));
    }, []);

    return { theme, setTheme, toggle, accessibility, setAccessibility };
  }

  /* ==========================================================================
     2. useTranslation - i18n
     Charge async les traductions et fournit t(key, vars).
     Singleton : translations partagées par tous les composants
     Usage :
       const { t, lang, setLang } = useTranslation();
       <button>{t('common.save')}</button>
       <p>{t('exam.remaining', { time: '12:34' })}</p>
     ========================================================================== */

  // Cache global des traductions chargées (évite rechargements)
  const _translationsCache = {};
  const _translationLoadPromises = {};
  const _translationListeners = new Set();
  let _currentLang = (() => {
    try {
      return localStorage.getItem('lang') ||
             (navigator.language || 'fr').slice(0, 2).toLowerCase();
    } catch (e) { return 'fr'; }
  })();

  function loadTranslations(lang) {
    if (_translationsCache[lang]) return Promise.resolve(_translationsCache[lang]);
    if (_translationLoadPromises[lang]) return _translationLoadPromises[lang];

    _translationLoadPromises[lang] = fetch(`/assets/i18n/${lang}.json`)
      .then(res => {
        if (!res.ok) throw new Error(`i18n/${lang}.json not found`);
        return res.json();
      })
      .then(data => {
        _translationsCache[lang] = data;
        return data;
      })
      .catch((err) => {
        console.warn(`[i18n] Échec chargement ${lang}, fallback FR.`, err);
        if (lang !== 'fr') return loadTranslations('fr');
        _translationsCache[lang] = {};
        return {};
      });

    return _translationLoadPromises[lang];
  }

  // Pré-charger FR au module load
  loadTranslations('fr');

  function useTranslation() {
    const [, force] = useState(0);
    const [lang, setLangState] = useState(_currentLang);

    useEffect(() => {
      const listener = () => force(n => n + 1);
      _translationListeners.add(listener);
      return () => _translationListeners.delete(listener);
    }, []);

    useEffect(() => {
      loadTranslations(lang).then(() => force(n => n + 1));
    }, [lang]);

    const setLang = useCallback((newLang) => {
      _currentLang = newLang;
      try { localStorage.setItem('lang', newLang); } catch (e) {}
      setLangState(newLang);
      _translationListeners.forEach(fn => fn());
    }, []);

    const t = useCallback((key, vars = {}) => {
      const dict = _translationsCache[lang] || _translationsCache['fr'] || {};
      let str = dict[key];
      if (str === undefined) {
        // Fallback FR si la clé manque dans la langue courante
        if (lang !== 'fr' && _translationsCache['fr']) {
          str = _translationsCache['fr'][key];
        }
      }
      if (str === undefined) return key; // ultime fallback : la clé elle-même

      // Substitution {var}
      if (vars && typeof str === 'string') {
        str = str.replace(/\{(\w+)\}/g, (_, name) =>
          vars[name] !== undefined ? vars[name] : `{${name}}`
        );
      }
      return str;
    }, [lang]);

    return { t, lang, setLang };
  }

  /* ==========================================================================
     3. useApi - Wrapper fetch avec gestion auth + erreurs
     CSRF token is GLOBAL (shared by all useApi instances).
     When useAuth sets it, all components can use it immediately.
     Usage :
       const api = useApi();
       const { ok, data, error } = await api.get('/api/auth/me');
       const res = await api.post('/api/auth/login', { email, password });
     ========================================================================== */

  // Global CSRF token — shared across all useApi() instances
  let _csrfToken = null;
  const _csrfListeners = new Set();

  function _setCsrfGlobal(token) {
    _csrfToken = token;
    _csrfListeners.forEach(function (fn) { fn(); });
  }

  function useApi(baseUrl = '') {
    const [, _forceUpdate] = useState(0);

    // Subscribe to global CSRF token changes
    useEffect(function () {
      var listener = function () { _forceUpdate(function (n) { return n + 1; }); };
      _csrfListeners.add(listener);
      return function () { _csrfListeners.delete(listener); };
    }, []);

    const request = useCallback(async (method, path, body = null, opts = {}) => {
      const url = (baseUrl + path).replace(/\/+/g, '/').replace(':/', '://');
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(opts.headers || {}),
      };

      // Ajouter automatiquement le CSRF token sur les méthodes mutantes
      const needsCsrf = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase());
      if (needsCsrf && _csrfToken) {
        headers['X-CSRF-Token'] = _csrfToken;
      }

      try {
        const res = await fetch(url, {
          method: method.toUpperCase(),
          credentials: 'same-origin',
          headers,
          body: body ? JSON.stringify(body) : undefined,
          ...opts,
        });

        let data = null;
        const ct = res.headers.get('content-type') || '';
        if (ct.includes('application/json')) {
          data = await res.json();
        } else {
          data = await res.text();
        }

        if (!res.ok) {
          // Format d'erreur API standardisé
          if (data && typeof data === 'object' && data.ok === false) {
            return {
              ok: false,
              status: res.status,
              error: data.error || { code: 'unknown', message: 'Erreur inconnue' },
              data: null,
            };
          }
          return {
            ok: false,
            status: res.status,
            error: { code: 'http_error', message: `HTTP ${res.status}` },
            data: null,
          };
        }

        // Format succès API standardisé : { ok: true, data: ... }
        if (data && typeof data === 'object' && 'ok' in data) {
          return { ok: data.ok, status: res.status, data: data.data, error: data.error || null };
        }

        // Réponse non-conventionnelle (HTML, texte brut)
        return { ok: true, status: res.status, data, error: null };

      } catch (e) {
        return {
          ok: false,
          status: 0,
          error: { code: 'network', message: e.message || 'Erreur réseau' },
          data: null,
        };
      }
    }, [baseUrl, _csrfToken]);

    // Récupérer le CSRF token au montage
    const refreshCsrf = useCallback(async () => {
      const r = await request('GET', '/api/auth/csrf-token');
      if (r.ok && r.data?.token) {
        _setCsrfGlobal(r.data.token);
      }
      return r.data?.token || null;
    }, [request]);

    return useMemo(() => ({
      request,
      get:    (path, opts) => request('GET', path, null, opts),
      post:   (path, body, opts) => request('POST', path, body, opts),
      put:    (path, body, opts) => request('PUT', path, body, opts),
      patch:  (path, body, opts) => request('PATCH', path, body, opts),
      delete: (path, body, opts) => request('DELETE', path, body, opts),
      refreshCsrf,
      csrfToken: _csrfToken,
      setCsrfToken: _setCsrfGlobal,
    }), [request, refreshCsrf, _csrfToken]);
  }

  /* ==========================================================================
     4. useAuth - État de session
     Singleton : un seul état global de session partagé par tous les composants
     Usage :
       const { user, isAuthenticated, isAdmin, login, logout, loading } = useAuth();
     ========================================================================== */

  let _authState = { user: null, loading: true };
  const _authListeners = new Set();

  function notifyAuth() {
    _authListeners.forEach(fn => fn());
  }

  function useAuth() {
    const [, force] = useState(0);
    const api = useApi();

    useEffect(() => {
      const listener = () => force(n => n + 1);
      _authListeners.add(listener);
      return () => _authListeners.delete(listener);
    }, []);

    // Charger l'état de session au montage
    useEffect(() => {
      if (_authState.user !== null || !_authState.loading) return;

      api.get('/api/auth/me').then(res => {
        if (res.ok && res.data?.authenticated) {
          _authState = { user: res.data.user, loading: false };
          if (res.data.csrf_token) api.setCsrfToken(res.data.csrf_token);
        } else {
          _authState = { user: null, loading: false };
        }
        notifyAuth();
      });
    }, [api]);

    const login = useCallback(async (email, password) => {
      const res = await api.post('/api/auth/login', { email, password });
      if (res.ok && res.data?.user) {
        _authState = { user: res.data.user, loading: false };
        if (res.data.csrf_token) api.setCsrfToken(res.data.csrf_token);
        notifyAuth();
        return { ok: true, user: res.data.user };
      }
      return { ok: false, error: res.error };
    }, [api]);

    const logout = useCallback(async () => {
      await api.post('/api/auth/logout');
      _authState = { user: null, loading: false };
      api.setCsrfToken(null);
      notifyAuth();
    }, [api]);

    return {
      user: _authState.user,
      loading: _authState.loading,
      isAuthenticated: _authState.user !== null,
      isAdmin: _authState.user?.role === 'admin',
      login,
      logout,
      api,
    };
  }

  /* ==========================================================================
     5. useDebounce - Valeur debouncée
     Utile pour la recherche : évite de déclencher 1 appel par caractère.
     Usage :
       const [search, setSearch] = useState('');
       const debouncedSearch = useDebounce(search, 300);
       useEffect(() => { fetch(...) }, [debouncedSearch]);
     ========================================================================== */

  function useDebounce(value, delayMs = 300) {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
      const id = setTimeout(() => setDebounced(value), delayMs);
      return () => clearTimeout(id);
    }, [value, delayMs]);

    return debounced;
  }

  /* ==========================================================================
     6. useLocalStorage - State synchronisé avec localStorage
     Usage :
       const [draft, setDraft] = useLocalStorage('exam_draft', { title: '' });
     ========================================================================== */

  function useLocalStorage(key, defaultValue) {
    const [value, setValue] = useState(() => {
      try {
        const raw = localStorage.getItem(key);
        return raw === null ? defaultValue : JSON.parse(raw);
      } catch (e) { return defaultValue; }
    });

    const setStoredValue = useCallback((newValue) => {
      try {
        const valToStore = typeof newValue === 'function' ? newValue(value) : newValue;
        setValue(valToStore);
        localStorage.setItem(key, JSON.stringify(valToStore));
      } catch (e) { console.warn(`[useLocalStorage] Erreur écriture ${key}:`, e); }
    }, [key, value]);

    const remove = useCallback(() => {
      try { localStorage.removeItem(key); } catch (e) {}
      setValue(defaultValue);
    }, [key, defaultValue]);

    return [value, setStoredValue, remove];
  }

  /* ==========================================================================
     7. useKeyboardShortcut - Raccourci clavier global
     Usage :
       useKeyboardShortcut('cmd+k', () => openCommandPalette());
       useKeyboardShortcut('escape', () => closeModal(), [isOpen]);
     ========================================================================== */

  function parseShortcut(spec) {
    const parts = spec.toLowerCase().split('+').map(p => p.trim());
    const result = { ctrl: false, meta: false, shift: false, alt: false, key: '' };
    parts.forEach(p => {
      if (p === 'ctrl' || p === 'control') result.ctrl = true;
      else if (p === 'meta' || p === 'cmd' || p === 'command') result.meta = true;
      else if (p === 'shift') result.shift = true;
      else if (p === 'alt' || p === 'option') result.alt = true;
      else result.key = p;
    });
    return result;
  }

  function useKeyboardShortcut(spec, handler, deps = []) {
    useEffect(() => {
      const target = parseShortcut(spec);
      const onKey = (e) => {
        // ctrl+k OU cmd+k via "cmd+k" spec
        const isCtrl = e.ctrlKey;
        const isMeta = e.metaKey;
        const ctrlOk = target.ctrl ? isCtrl : (target.meta ? true : !isCtrl);
        const metaOk = target.meta ? isMeta : (target.ctrl ? true : !isMeta);

        // Pour "cmd+k", on accepte ctrl+k OU cmd+k (compatibilité Mac/Win/Linux)
        const isCmdLike = target.meta || target.ctrl;
        const cmdSatisfied = !isCmdLike || isCtrl || isMeta;

        if (
          (target.shift ? e.shiftKey : !e.shiftKey) &&
          (target.alt ? e.altKey : !e.altKey) &&
          (isCmdLike ? cmdSatisfied : (!e.ctrlKey && !e.metaKey)) &&
          e.key.toLowerCase() === target.key
        ) {
          e.preventDefault();
          handler(e);
        }
      };
      window.addEventListener('keydown', onKey);
      return () => window.removeEventListener('keydown', onKey);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, deps);
  }

  /* ==========================================================================
     8. useModal - Gestion d'état modale (open/close/toggle)
     Usage :
       const modal = useModal();
       <Button onClick={modal.open}>Ouvrir</Button>
       <Modal open={modal.isOpen} onClose={modal.close}>...</Modal>
     ========================================================================== */

  function useModal(defaultOpen = false) {
    const [isOpen, setIsOpen] = useState(defaultOpen);
    const open = useCallback(() => setIsOpen(true), []);
    const close = useCallback(() => setIsOpen(false), []);
    const toggle = useCallback(() => setIsOpen(v => !v), []);
    return { isOpen, open, close, toggle, setIsOpen };
  }

  /* ==========================================================================
     EXPORTS
     ========================================================================== */

  root.UIHooks = {
    useTheme,
    useTranslation,
    useApi,
    useAuth,
    useDebounce,
    useLocalStorage,
    useKeyboardShortcut,
    useModal,
    // Helpers internes exposés pour les tests
    _loadTranslations: loadTranslations,
  };

})(window);
