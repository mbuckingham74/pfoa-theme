/*!
 * PFOA cat profile dialog controller.
 *
 * Inert module: loading this file attaches no DOM listeners, performs no
 * network requests, contacts no provider, and queries no dialog element.
 * Nothing happens until the host explicitly calls
 * `window.PfoaCatDialogController.start(options)`. There is no automatic
 * start on DOMContentLoaded / load.
 *
 * Public surface: `window.PfoaCatDialogController = { start, _resetForTests }`.
 * `start(options)` is idempotent: repeated calls return the same controller
 * and attach exactly one document click handler. `_resetForTests` is a
 * test-only hook that detaches listeners and clears cached state so a
 * fixture can isolate runs; the production path stays idempotent.
 *
 * Single implementation serving both presentation modes: one code path, no
 * per-mode flags, no per-mode branches, no mode arguments.
 *
 * Gallery lifecycle is delegated to the `window.PFOAGalleryLightbox`
 * provider (`initProfileGalleries` / `disposeProfileGalleries`). This file
 * owns no gallery overlay, manipulates no overlay element, and calls only
 * provider methods for gallery work. When `start()` succeeds it calls
 * `provider.initProfileGalleries(document)` once to claim eligible initial
 * profile galleries; it never calls dispose at start.
 *
 * Presentation-epoch guard: the final server wire format is not yet
 * selected, so epoch comparison is explicitly injected via options:
 *   - `documentEpoch` (string|null; default null)
 *   - `getResponseEpoch(response, bodyText)` (default reads the
 *     `X-PFOA-Presentation-Epoch` response header, else null)
 *   - `isEpochMatch(responseEpoch)` (default rejects missing epochs and
 *     otherwise applies strict `===` against `documentEpoch`)
 *   - `dialogId` (default 'pfoa-cat-dialog')
 *   - `documentOverride` / `windowOverride`, `fetchImpl`, `getProvider`
 *     (dependency injection for tests; all optional with safe defaults)
 * Before insertion the response epoch is resolved via `getResponseEpoch`
 * and validated via `isEpochMatch`. A missing (null / '' / undefined) or
 * mismatched epoch rejects insertion: the fragment is never inserted,
 * galleries are never initialized for it, and a fallback carrying a
 * canonical link is shown instead.
 *
 * Fail-closed: any missing dependency (dialog shell, dialog body, fetch,
 * provider) leaves ordinary navigation untouched -- no preventDefault, no
 * dialog open, no fetch. Post-open failures keep the already-opened dialog
 * visible with a fallback that preserves canonical navigation via an
 * explicit link.
 */
(function (root) {
  'use strict';

  var DIALOG_ATTR = 'data-pfoa-cat-dialog';
  var BODY_ATTR = 'data-pfoa-cat-dialog-body';
  var CLOSE_ATTR = 'data-pfoa-cat-dialog-close';
  var STATUS_ATTR = 'data-pfoa-cat-dialog-status';
  var SLUG_RE = /^[a-z0-9-]+$/;
  var EPOCH_HEADER = 'X-PFOA-Presentation-Epoch';
  var DEFAULT_DIALOG_ID = 'pfoa-cat-dialog';

  /* Idempotent success record: { handle, doc, click, key, invalidate }. */
  var cached = null;

  function getAttr(el, name) {
    if (!el || typeof el.getAttribute !== 'function') {
      return null;
    }
    try {
      return el.getAttribute(name);
    } catch (err) {
      return null;
    }
  }

  function hasAttr(el, name) {
    if (!el) {
      return false;
    }
    if (typeof el.hasAttribute === 'function') {
      try {
        if (el.hasAttribute(name)) {
          return true;
        }
      } catch (err) {
        /* fall through to getAttribute check */
      }
    }
    return getAttr(el, name) !== null;
  }

  function parentOf(node) {
    if (!node) {
      return null;
    }
    return node.parentNode || node.parentElement || node.parent || null;
  }

  function isAnchor(node) {
    if (!node || node.nodeType !== 1) {
      return false;
    }
    var tag = node.tagName ? String(node.tagName).toUpperCase() : '';
    return tag === 'A';
  }

  function nearest(node, isMatch) {
    var n = node;
    while (n) {
      if (n.nodeType === 1) {
        var hit = false;
        try {
          hit = !!isMatch(n);
        } catch (err) {
          hit = false;
        }
        if (hit) {
          return n;
        }
      }
      n = parentOf(n);
    }
    return null;
  }

  function isWithin(node, ancestor) {
    var n = parentOf(node);
    while (n) {
      if (n === ancestor) {
        return true;
      }
      n = parentOf(n);
    }
    return false;
  }

  function walkFind(node, isMatch) {
    var kids = (node && node.children) || [];
    for (var i = 0; i < kids.length; i++) {
      var kid = kids[i];
      if (kid && kid.nodeType === 1) {
        var hit = false;
        try {
          hit = !!isMatch(kid);
        } catch (err) {
          hit = false;
        }
        if (hit) {
          return kid;
        }
        var deep = walkFind(kid, isMatch);
        if (deep) {
          return deep;
        }
      }
    }
    return null;
  }

  function findBody(dlg) {
    if (!dlg) {
      return null;
    }
    if (typeof dlg.querySelector === 'function') {
      try {
        var hit = dlg.querySelector('[' + BODY_ATTR + ']');
        if (hit) {
          return hit;
        }
      } catch (err) {
        /* fall through to manual walk */
      }
    }
    return walkFind(dlg, function (el) {
      return hasAttr(el, BODY_ATTR);
    });
  }

  function inDocument(doc, node) {
    if (!doc || !node) {
      return false;
    }
    if (typeof doc.contains === 'function') {
      try {
        return !!doc.contains(node);
      } catch (err) {
        /* fall through to manual walk */
      }
    }
    var n = node;
    while (n) {
      if (n === doc) {
        return true;
      }
      n = parentOf(n);
    }
    return false;
  }

  function isBlank(value) {
    if (typeof value !== 'string') {
      return true;
    }
    return value.replace(/^\s+|\s+$/g, '') === '';
  }

  function escapeAttr(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function loadingHtml() {
    return '<p ' + STATUS_ATTR + '="loading">Loading profile&hellip;</p>';
  }

  function fallbackHtml(href) {
    return '<p ' + STATUS_ATTR + '="error">This profile is unavailable right now.</p>' +
      '<p><a href="' + escapeAttr(href) + '">Open full profile</a></p>';
  }

  function defaultGetResponseEpoch(response) {
    try {
      if (response && response.headers && typeof response.headers.get === 'function') {
        var value = response.headers.get(EPOCH_HEADER);
        if (value === null || value === undefined) {
          return null;
        }
        var text = String(value);
        if (text === '') {
          return null;
        }
        return text;
      }
    } catch (err) {
      /* fall through to null */
    }
    return null;
  }

  function makeDefaultIsEpochMatch(documentEpoch) {
    return function (responseEpoch) {
      if (responseEpoch === null || responseEpoch === undefined || responseEpoch === '') {
        return false;
      }
      return responseEpoch === documentEpoch;
    };
  }

  function providerLooksValid(provider) {
    return !!provider &&
      typeof provider.initProfileGalleries === 'function' &&
      typeof provider.disposeProfileGalleries === 'function';
  }

  function start(options) {
    if (cached) {
      return cached.handle;
    }
    var opts = options || {};

    var win = opts.windowOverride || null;
    if (!win && typeof window !== 'undefined') {
      try {
        win = window;
      } catch (err) {
        win = null;
      }
    }
    if (!win) {
      win = root;
    }

    var doc = opts.documentOverride || null;
    if (!doc) {
      if (win && win.document) {
        doc = win.document;
      } else if (typeof document !== 'undefined') {
        try {
          doc = document;
        } catch (err) {
          doc = null;
        }
      }
    }

    var dialogId = opts.dialogId || DEFAULT_DIALOG_ID;
    var documentEpoch = Object.prototype.hasOwnProperty.call(opts, 'documentEpoch')
      ? opts.documentEpoch
      : null;
    var getProvider = opts.getProvider || function () {
      try {
        if (win && win.PFOAGalleryLightbox) {
          return win.PFOAGalleryLightbox;
        }
      } catch (err) {
        /* fall through to null */
      }
      return null;
    };
    var injectedFetch = opts.fetchImpl || null;
    var getResponseEpoch = opts.getResponseEpoch || defaultGetResponseEpoch;
    var isEpochMatch = opts.isEpochMatch || makeDefaultIsEpochMatch(documentEpoch);

    if (!doc || typeof doc.addEventListener !== 'function') {
      return { started: false };
    }

    function liveProvider() {
      var provider = null;
      try {
        provider = getProvider();
      } catch (err) {
        provider = null;
      }
      return providerLooksValid(provider) ? provider : null;
    }

    function fetchNow() {
      if (typeof injectedFetch === 'function') {
        return injectedFetch;
      }
      try {
        if (win && typeof win.fetch === 'function') {
          return win.fetch;
        }
      } catch (err) {
        /* fall through */
      }
      if (typeof fetch === 'function') {
        try {
          return fetch;
        } catch (err) {
          /* fall through to null */
        }
      }
      return null;
    }

    if (!liveProvider()) {
      return { started: false };
    }

    /* Per-start live state. */
    var seq = 0;
    var invoker = null;
    var liveAbort = null;

    function getDialog() {
      if (typeof doc.getElementById !== 'function') {
        return null;
      }
      try {
        return doc.getElementById(dialogId);
      } catch (err) {
        return null;
      }
    }

    function newAbort() {
      var AC = null;
      try {
        AC = (win && win.AbortController) || null;
      } catch (err) {
        AC = null;
      }
      if (!AC && typeof AbortController === 'function') {
        try {
          AC = AbortController;
        } catch (err) {
          AC = null;
        }
      }
      if (!AC) {
        return null;
      }
      try {
        return new AC();
      } catch (err) {
        return null;
      }
    }

    function setHtml(el, html) {
      try {
        el.innerHTML = html;
      } catch (err) {
        /* stub hosts always accept string assignment */
      }
    }

    function showFallback(myId, href) {
      if (myId !== seq) {
        return;
      }
      var dlg = getDialog();
      if (!dlg || dlg.hidden) {
        return;
      }
      var body = findBody(dlg);
      if (!body) {
        return;
      }
      var provider = liveProvider();
      if (provider) {
        try {
          provider.disposeProfileGalleries(body);
        } catch (err) {
          /* keep the fallback visible even if dispose fails */
        }
      }
      setHtml(body, fallbackHtml(href));
    }

    function closeDialog() {
      var dlg = getDialog();
      seq += 1;
      if (liveAbort && typeof liveAbort.abort === 'function') {
        try {
          liveAbort.abort();
        } catch (err) {
          /* ignore abort failures */
        }
      }
      liveAbort = null;
      if (!dlg || dlg.hidden) {
        invoker = null;
        return;
      }
      var body = findBody(dlg);
      if (body) {
        var provider = liveProvider();
        if (provider) {
          try {
            provider.disposeProfileGalleries(body);
          } catch (err) {
            /* keep closing even if dispose fails */
          }
        }
        setHtml(body, '');
      }
      try {
        dlg.hidden = true;
      } catch (err) {
        /* ignore */
      }
      try {
        if (typeof dlg.setAttribute === 'function') {
          dlg.setAttribute('hidden', '');
        }
      } catch (err) {
        /* ignore */
      }
      var who = invoker;
      invoker = null;
      if (who && typeof who.focus === 'function' && inDocument(doc, who)) {
        try {
          who.focus();
        } catch (err) {
          /* ignore focus failures */
        }
      }
    }

    function openFor(anchor, href, slug) {
      var dlg = getDialog();
      if (!dlg) {
        return;
      }
      var body = findBody(dlg);
      if (!body) {
        return;
      }
      seq += 1;
      var myId = seq;
      if (liveAbort && typeof liveAbort.abort === 'function') {
        try {
          liveAbort.abort();
        } catch (err) {
          /* ignore abort failures */
        }
      }
      liveAbort = newAbort();
      var signal = liveAbort && liveAbort.signal ? liveAbort.signal : undefined;
      invoker = anchor;

      var opener = liveProvider();
      if (opener) {
        try {
          opener.disposeProfileGalleries(body);
        } catch (err) {
          /* keep opening even if dispose fails */
        }
      }
      setHtml(body, loadingHtml());
      try {
        dlg.hidden = false;
      } catch (err) {
        /* ignore */
      }
      try {
        if (typeof dlg.removeAttribute === 'function') {
          dlg.removeAttribute('hidden');
        }
      } catch (err) {
        /* ignore */
      }
      try {
        if (typeof body.focus === 'function') {
          body.focus();
        }
      } catch (err) {
        /* ignore focus failures */
      }

      var fetchFn = fetchNow();
      if (!fetchFn) {
        showFallback(myId, href);
        return;
      }
      var base = String(href).split('#')[0].split('?')[0];
      var url = base + '?pfoa_cat_fragment=' + encodeURIComponent(slug);
      var init = { method: 'GET', credentials: 'same-origin', signal: signal };
      var result = null;
      try {
        result = fetchFn(url, init);
      } catch (err) {
        showFallback(myId, href);
        return;
      }
      Promise.resolve(result).then(function (response) {
        if (myId !== seq) {
          return;
        }
        var openDlg = getDialog();
        if (!openDlg || openDlg.hidden) {
          return;
        }
        if (!response || !response.ok) {
          showFallback(myId, href);
          return;
        }
        var textPromise = null;
        try {
          textPromise = response.text();
        } catch (err) {
          showFallback(myId, href);
          return;
        }
        Promise.resolve(textPromise).then(function (bodyText) {
          if (myId !== seq) {
            return;
          }
          var liveDlg = getDialog();
          if (!liveDlg || liveDlg.hidden) {
            return;
          }
          if (isBlank(bodyText)) {
            showFallback(myId, href);
            return;
          }
          var epoch = null;
          try {
            epoch = getResponseEpoch(response, bodyText);
          } catch (err) {
            epoch = null;
          }
          var matched = false;
          try {
            matched = !!isEpochMatch(epoch);
          } catch (err) {
            matched = false;
          }
          if (!matched) {
            showFallback(myId, href);
            return;
          }
          var liveBody = findBody(liveDlg);
          if (!liveBody) {
            return;
          }
          var disposer = liveProvider();
          if (disposer) {
            try {
              disposer.disposeProfileGalleries(liveBody);
            } catch (err) {
              /* keep replacing even if dispose fails */
            }
          }
          setHtml(liveBody, bodyText);
          var initializer = liveProvider();
          if (initializer) {
            try {
              initializer.initProfileGalleries(liveBody);
            } catch (err) {
              /* fragment stays visible even if init fails */
            }
          }
        }, function () {
          showFallback(myId, href);
        });
      }, function () {
        if (myId !== seq) {
          return;
        }
        showFallback(myId, href);
      });
    }

    function onDocumentClick(event) {
      if (!event) {
        return;
      }
      try {
        if (event.defaultPrevented) {
          return;
        }
      } catch (err) {
        /* treat unreadable events as live */
      }
      if (event.button !== undefined && event.button !== null && event.button !== 0) {
        return;
      }
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
        return;
      }
      var target = event.target || null;

      var closer = nearest(target, function (el) {
        return hasAttr(el, CLOSE_ATTR);
      });
      if (closer) {
        var shutDlg = getDialog();
        if (shutDlg && (closer === shutDlg || isWithin(closer, shutDlg))) {
          try {
            if (typeof event.preventDefault === 'function') {
              event.preventDefault();
            }
          } catch (err) {
            /* ignore */
          }
          closeDialog();
        }
        return;
      }

      var anchor = nearest(target, function (el) {
        return isAnchor(el) && hasAttr(el, DIALOG_ATTR);
      });
      if (!anchor) {
        return;
      }
      var slug = getAttr(anchor, DIALOG_ATTR);
      if (isBlank(slug) || !SLUG_RE.test(slug)) {
        return;
      }
      var href = getAttr(anchor, 'href');
      if (isBlank(href)) {
        return;
      }

      /* Fail-closed pre-open validation, in order. */
      var dlg = getDialog();
      if (!dlg) {
        return;
      }
      var body = findBody(dlg);
      if (!body) {
        return;
      }
      if (!fetchNow()) {
        return;
      }
      if (!liveProvider()) {
        return;
      }

      try {
        if (typeof event.preventDefault === 'function') {
          event.preventDefault();
        }
      } catch (err) {
        /* ignore */
      }
      openFor(anchor, href, slug);
    }

    function onDocumentKeyDown(event) {
      if (!event) {
        return;
      }
      var key = event.key || null;
      var code = event.keyCode || 0;
      if (key !== 'Escape' && key !== 'Esc' && code !== 27) {
        return;
      }
      var dlg = getDialog();
      if (!dlg || dlg.hidden) {
        return;
      }
      closeDialog();
    }

    try {
      doc.addEventListener('click', onDocumentClick);
    } catch (err) {
      return { started: false };
    }
    try {
      doc.addEventListener('keydown', onDocumentKeyDown);
    } catch (err) {
      try {
        doc.removeEventListener('click', onDocumentClick);
      } catch (ignored) {
        /* ignore */
      }
      return { started: false };
    }

    /* Claim eligible initial profile galleries exactly once. */
    var initial = liveProvider();
    if (initial) {
      try {
        initial.initProfileGalleries(doc);
      } catch (err) {
        /* start still succeeds; galleries stay unclaimed */
      }
    }

    function invalidate() {
      seq += 1;
      if (liveAbort && typeof liveAbort.abort === 'function') {
        try {
          liveAbort.abort();
        } catch (err) {
          /* ignore */
        }
      }
      liveAbort = null;
      invoker = null;
    }

    function destroy() {
      try {
        doc.removeEventListener('click', onDocumentClick);
      } catch (err) {
        /* ignore */
      }
      try {
        doc.removeEventListener('keydown', onDocumentKeyDown);
      } catch (err) {
        /* ignore */
      }
      invalidate();
      if (cached && cached.handle === handle) {
        cached = null;
      }
    }

    var handle = { started: true, destroy: destroy };
    cached = {
      handle: handle,
      doc: doc,
      click: onDocumentClick,
      key: onDocumentKeyDown,
      invalidate: invalidate
    };
    return handle;
  }

  function resetForTests() {
    if (cached) {
      try {
        cached.doc.removeEventListener('click', cached.click);
      } catch (err) {
        /* ignore */
      }
      try {
        cached.doc.removeEventListener('keydown', cached.key);
      } catch (err) {
        /* ignore */
      }
      try {
        cached.invalidate();
      } catch (err) {
        /* ignore */
      }
    }
    cached = null;
  }

  root.PfoaCatDialogController = {
    start: start,
    _resetForTests: resetForTests
  };
})(typeof window !== 'undefined' ? window : typeof globalThis !== 'undefined' ? globalThis : this);
