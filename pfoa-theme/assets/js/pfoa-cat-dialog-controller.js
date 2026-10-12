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
 * Each live handle also exposes `destroy()`.
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
 * Provider availability: a live provider (both init+dispose functions) is
 * required before ANY dialog-body mutation (loading, fragment, fallback,
 * clear) and before insertion. Pre-open the controller fails closed (no
 * preventDefault, no fetch, no dialog) when the provider is missing.
 * Post-open, when the provider disappears before a response or dispose, the
 * controller aborts the mutation, keeps the previous safe body content, and
 * keeps the dialog open. A fallback is attempted only when safe disposal
 * succeeds; when the provider is missing the fallback also aborts, leaving
 * the loading or previous content in place.
 *
 * Disposal: `safeDispose(body)` returns true only when a live provider
 * exists and `disposeProfileGalleries(body)` does not throw (its return
 * value is ignored; 0 is valid -- only a missing provider or a throw is a
 * failure). Every `innerHTML` mutation is preceded by a successful
 * `safeDispose`. On failure the mutation is skipped: replacement keeps the
 * old content with the dialog open; close preserves the body but still hides
 * the dialog and restores focus.
 *
 * Destroy lifecycle: `destroy()` is ownership-guarded and idempotent. A
 * stale (non-owner) handle's `destroy()` is a strict no-op: it performs no
 * operations at all (no dialog lookup, no sequence change, no abort, no
 * listener removal, no disposal, no DOM or focus changes, no cache change).
 * The owning handle's `destroy()` bumps the sequence, aborts the live
 * request, snapshots dialog/body/invoker, removes listeners, then -- only
 * for the owned dialog body -- attempts `safeDispose`: on success it clears
 * the body, hides the dialog, and restores focus; on failure it preserves
 * the body but still hides the dialog and restores focus. It never disposes
 * the document or an unowned scope, invalidates late responses, clears the
 * cache only for its own handle, and leaves a fresh `start()` working.
 *
 * Presentation-epoch guard: epoch comparison is explicitly injected via
 * options (`documentEpoch`, `getResponseEpoch`, `isEpochMatch`). Insertion
 * requires a non-empty document epoch, a non-empty response epoch, and
 * strict `===` equality, independently of injected callbacks. An injected
 * `isEpochMatch` may further reject but never overrides a missing epoch or
 * a mismatch. A missing document epoch never yields insertion (fallback
 * instead); no epoch is required pre-open so open+fetch still occur.
 *
 * Canonical link validation: only same-origin HTTP(S) Cat Profile canonical
 * URLs are eligible (`/pfoa-cat/<slug>/` with matching slug, `_self` or missing
 * target, no `download`). A usable established document origin is required:
 * when the base is missing, unparseable, non-http(s), or has no valid origin,
 * validation fails closed. Unsafe links fail closed pre-open and never reach
 * fetch or fallback construction. Fallbacks reuse only the originally
 * validated safe href.
 *
 * Fragment validation (defense-in-depth, not a replacement for future
 * server output safety): before insertion the fragment is parsed with
 * DOMParser (or a template/div fallback) and inspected -- exactly one DIV
 * root with class `pfoa-cat-profile` and matching data attribute (text nodes
 * are read via childNodes so non-whitespace text outside the root rejects;
 * whitespace-only text and comments are ignored), an element
 * allowlist, attribute allowlist, no `on*` handlers, safe style, safe
 * http(s) URLs, YouTube-only iframes. Unparseable input fails closed.
 *
 * Fail-closed: any missing dependency (dialog shell, dialog body, fetch,
 * provider) leaves ordinary navigation untouched -- no preventDefault, no
 * dialog open, no fetch. Post-open failures keep the already-opened dialog
 * visible with a fallback that preserves canonical navigation via an
 * explicit link, or preserve prior content when disposal is unsafe.
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
  var PFOA_CAT_PATH_RE = /^\/pfoa-cat\/([a-z0-9-]+)\/?$/;

  var FRAG_ALLOWED_TAGS = {
    DIV: 1, H1: 1, H2: 1, H3: 1, H4: 1, H5: 1, H6: 1, P: 1,
    UL: 1, OL: 1, LI: 1, A: 1, IMG: 1, DL: 1, DT: 1, DD: 1,
    BR: 1, STRONG: 1, EM: 1, B: 1, I: 1, U: 1, SPAN: 1,
    BLOCKQUOTE: 1, FIGURE: 1, FIGCAPTION: 1, IFRAME: 1
  };
  var FRAG_ALLOWED_ATTRS = {
    'class': 1, id: 1, href: 1, src: 1, srcset: 1, sizes: 1, alt: 1,
    title: 1, width: 1, height: 1, loading: 1, decoding: 1, allow: 1,
    allowfullscreen: 1, frameborder: 1, target: 1, rel: 1, style: 1
  };
  var FRAG_IFRAME_ALLOWED_ATTRS = {
    src: 1, width: 1, height: 1, title: 1, allow: 1,
    allowfullscreen: 1, frameborder: 1, loading: 1, 'class': 1, id: 1, style: 1
  };

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

  function isNonEmptyString(value) {
    if (typeof value !== 'string') {
      return false;
    }
    return value.replace(/^\s+|\s+$/g, '') !== '';
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
    var injectedIsEpochMatch = (Object.prototype.hasOwnProperty.call(opts, 'isEpochMatch') &&
      typeof opts.isEpochMatch === 'function') ? opts.isEpochMatch : null;

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

    function urlCtor() {
      try {
        if (win && typeof win.URL === 'function') {
          return win.URL;
        }
      } catch (err) {
        /* fall through */
      }
      try {
        if (typeof URL === 'function') {
          return URL;
        }
      } catch (err) {
        /* fall through */
      }
      return null;
    }

    function getBase() {
      try {
        if (win && win.location && typeof win.location.href === 'string' &&
          win.location.href.replace(/^\s+|\s+$/g, '') !== '') {
          return win.location.href;
        }
      } catch (err) {
        /* fall through */
      }
      try {
        if (doc && typeof doc.baseURI === 'string' &&
          doc.baseURI.replace(/^\s+|\s+$/g, '') !== '') {
          return doc.baseURI;
        }
      } catch (err) {
        /* fall through */
      }
      try {
        if (doc && typeof doc.URL === 'string' &&
          doc.URL.replace(/^\s+|\s+$/g, '') !== '') {
          return doc.URL;
        }
      } catch (err) {
        /* fall through */
      }
      return null;
    }

    function getValidBase() {
      var base = getBase();
      if (!isNonEmptyString(base)) {
        return null;
      }
      var C = urlCtor();
      if (!C) {
        return null;
      }
      try {
        var bp = new C(base);
        var proto = String(bp.protocol || '').toLowerCase();
        if (proto !== 'http:' && proto !== 'https:') {
          return null;
        }
        if (!bp.origin || bp.origin === 'null') {
          return null;
        }
        return { href: base, origin: bp.origin };
      } catch (err) {
        return null;
      }
    }

    function isEligibleHref(href, slug) {
      if (!isNonEmptyString(href) || !isNonEmptyString(slug)) {
        return false;
      }
      var C = urlCtor();
      if (!C) {
        return false;
      }
      var validBase = getValidBase();
      if (!validBase) {
        return false;
      }
      var parsed = null;
      try {
        parsed = new C(href, validBase.href);
      } catch (err) {
        return false;
      }
      if (!parsed) {
        return false;
      }
      var proto = '';
      try {
        proto = String(parsed.protocol || '').toLowerCase();
      } catch (err) {
        return false;
      }
      if (proto !== 'http:' && proto !== 'https:') {
        return false;
      }
      try {
        if (!parsed.origin || parsed.origin === 'null' || parsed.origin !== validBase.origin) {
          return false;
        }
      } catch (err) {
        return false;
      }
      var pathname = '';
      try {
        pathname = String(parsed.pathname || '');
      } catch (err) {
        return false;
      }
      var m = PFOA_CAT_PATH_RE.exec(pathname);
      if (!m || m[1] !== slug) {
        return false;
      }
      return true;
    }

    function isEligibleAnchor(anchor, slug, href) {
      if (hasAttr(anchor, 'download')) {
        return false;
      }
      if (hasAttr(anchor, 'target')) {
        var tv = getAttr(anchor, 'target');
        if (typeof tv !== 'string') {
          return false;
        }
        if (tv.replace(/^\s+|\s+$/g, '').toLowerCase() !== '_self') {
          return false;
        }
      }
      return isEligibleHref(href, slug);
    }

    function isSafeUrlAttr(value) {
      if (typeof value !== 'string') {
        return false;
      }
      var t = value.replace(/^\s+|\s+$/g, '');
      if (t === '') {
        return false;
      }
      var low = t.toLowerCase();
      if (low.indexOf('javascript:') === 0 || low.indexOf('data:') === 0 ||
        low.indexOf('vbscript:') === 0 || low.indexOf('file:') === 0 ||
        low.indexOf('blob:') === 0) {
        return false;
      }
      var C = urlCtor();
      if (!C) {
        return false;
      }
      var base = getBase();
      var p = null;
      try {
        p = base ? new C(t, base) : new C(t);
      } catch (err) {
        return false;
      }
      var proto = '';
      try {
        proto = String(p.protocol || '').toLowerCase();
      } catch (err) {
        return false;
      }
      return proto === 'http:' || proto === 'https:';
    }

    function isSafeStyleAttr(value) {
      var slow = String(value === null || value === undefined ? '' : value).toLowerCase();
      if (slow.indexOf('url(') !== -1) {
        return false;
      }
      if (slow.indexOf('expression(') !== -1) {
        return false;
      }
      if (slow.indexOf('javascript:') !== -1) {
        return false;
      }
      if (slow.indexOf('vbscript:') !== -1) {
        return false;
      }
      if (slow.indexOf('data:text/html') !== -1) {
        return false;
      }
      return true;
    }

    function isYouTubeSrc(src) {
      var C = urlCtor();
      if (!C) {
        return false;
      }
      var base = getBase();
      var p = null;
      try {
        p = base ? new C(String(src).replace(/^\s+|\s+$/g, ''), base) : new C(String(src).replace(/^\s+|\s+$/g, ''));
      } catch (err) {
        return false;
      }
      var proto = '';
      try {
        proto = String(p.protocol || '').toLowerCase();
      } catch (err) {
        return false;
      }
      if (proto !== 'https:') {
        return false;
      }
      var host = '';
      try {
        host = String(p.hostname || '').toLowerCase();
      } catch (err) {
        return false;
      }
      if (host === 'youtu.be' || host === 'youtube.com' || host === 'youtube-nocookie.com') {
        return true;
      }
      if (host.length > 'youtube.com'.length &&
        host.slice(host.length - 'youtube.com'.length - 1) === '.youtube.com') {
        return true;
      }
      if (host.length > 'youtube-nocookie.com'.length &&
        host.slice(host.length - 'youtube-nocookie.com'.length - 1) === '.youtube-nocookie.com') {
        return true;
      }
      if (host.length > 'youtu.be'.length &&
        host.slice(host.length - 'youtu.be'.length - 1) === '.youtu.be') {
        return true;
      }
      return false;
    }

    function nodeTag(node) {
      try {
        return String(node && node.tagName ? node.tagName : '').toUpperCase();
      } catch (err) {
        return '';
      }
    }

    function nodeKids(node) {
      try {
        if (node && node.children && typeof node.children.length === 'number') {
          return node.children;
        }
      } catch (err) {
        /* fall through */
      }
      try {
        if (node && node.childNodes && typeof node.childNodes.length === 'number') {
          return node.childNodes;
        }
      } catch (err) {
        /* fall through */
      }
      return [];
    }

    function nodeText(node) {
      try {
        if (node && typeof node.textContent === 'string') {
          return node.textContent;
        }
      } catch (err) {
        /* fall through */
      }
      try {
        if (node && typeof node.data === 'string') {
          return node.data;
        }
      } catch (err) {
        /* fall through */
      }
      try {
        if (node && typeof node.nodeValue === 'string') {
          return node.nodeValue;
        }
      } catch (err) {
        /* fall through */
      }
      return '';
    }

    function listAttrNames(node) {
      try {
        if (node && typeof node.getAttributeNames === 'function') {
          var ns = node.getAttributeNames();
          if (ns && typeof ns.length === 'number') {
            var out = [];
            for (var i = 0; i < ns.length; i++) {
              out.push(String(ns[i]));
            }
            return out;
          }
        }
      } catch (err) {
        /* fall through */
      }
      try {
        if (node && node.attributes && typeof node.attributes.length === 'number') {
          var out2 = [];
          for (var j = 0; j < node.attributes.length; j++) {
            var a = node.attributes[j];
            if (a && typeof a.name === 'string') {
              out2.push(String(a.name));
            } else if (typeof a === 'string') {
              out2.push(a);
            }
          }
          if (out2.length > 0) {
            return out2;
          }
        }
      } catch (err) {
        /* fall through */
      }
      try {
        if (node && node.attrs && typeof node.attrs === 'object') {
          return Object.keys(node.attrs);
        }
      } catch (err) {
        /* fall through */
      }
      return [];
    }

    function nodeGetAttr(node, name) {
      try {
        if (node && typeof node.getAttribute === 'function') {
          var v = node.getAttribute(name);
          if (v !== null && v !== undefined) {
            return v;
          }
          var low = String(name).toLowerCase();
          if (low !== String(name)) {
            try {
              var v2 = node.getAttribute(low);
              if (v2 !== null && v2 !== undefined) {
                return v2;
              }
            } catch (ignored) {
              /* ignore */
            }
          }
          return v;
        }
      } catch (err) {
        /* fall through */
      }
      try {
        if (node && node.attrs && typeof node.attrs === 'object') {
          var want = String(name).toLowerCase();
          var keys = Object.keys(node.attrs);
          for (var i = 0; i < keys.length; i++) {
            if (String(keys[i]).toLowerCase() === want) {
              return node.attrs[keys[i]];
            }
          }
        }
      } catch (err) {
        /* fall through */
      }
      return null;
    }

    function nodeHasAttr(node, name) {
      try {
        if (node && typeof node.hasAttribute === 'function') {
          try {
            if (node.hasAttribute(name)) {
              return true;
            }
          } catch (err) {
            /* fall through */
          }
          try {
            if (node.hasAttribute(String(name).toLowerCase())) {
              return true;
            }
          } catch (err) {
            /* fall through */
          }
        }
      } catch (err) {
        /* fall through */
      }
      return nodeGetAttr(node, name) !== null;
    }

    function parseFragmentDoc(text) {
      var DP = null;
      try {
        DP = (win && win.DOMParser) ? win.DOMParser : null;
      } catch (err) {
        DP = null;
      }
      if (!DP) {
        try {
          if (typeof DOMParser === 'function') {
            DP = DOMParser;
          }
        } catch (err) {
          DP = null;
        }
      }
      if (DP) {
        try {
          var parser = new DP();
          var parsedDoc = parser.parseFromString(text, 'text/html');
          if (!parsedDoc) {
            return null;
          }
          var b = parsedDoc.body || null;
          // childNodes first: .children hides non-whitespace text nodes
          // outside the fragment root in real browsers.
          var ch = b ? (b.childNodes || b.children || null) : null;
          if (!ch || typeof ch.length !== 'number') {
            return null;
          }
          var arr = [];
          for (var i = 0; i < ch.length; i++) {
            arr.push(ch[i]);
          }
          return {
            top: arr,
            doctype: parsedDoc.doctype || null,
            head: parsedDoc.head || null,
            body: b,
            documentElement: parsedDoc.documentElement || null
          };
        } catch (err) {
          return null;
        }
      }
      try {
        if (doc && typeof doc.createElement === 'function') {
          var tpl = null;
          try {
            tpl = doc.createElement('template');
          } catch (err) {
            tpl = null;
          }
          if (tpl) {
            try {
              tpl.innerHTML = text;
            } catch (err) {
              /* ignore */
            }
            try {
              var content = tpl.content || null;
              var cc = content ? (content.childNodes || content.children || null) : null;
              if (cc && typeof cc.length === 'number' && cc.length > 0) {
                var a2 = [];
                for (var k = 0; k < cc.length; k++) {
                  a2.push(cc[k]);
                }
                return { top: a2, doctype: null, head: null, body: null, documentElement: null };
              }
            } catch (err) {
              /* fall through to div */
            }
          }
          var tmp = null;
          try {
            tmp = doc.createElement('div');
          } catch (err) {
            tmp = null;
          }
          if (tmp) {
            try {
              tmp.innerHTML = text;
            } catch (err) {
              /* ignore */
            }
            try {
              var kids = tmp.childNodes || tmp.children || null;
              if (kids && typeof kids.length === 'number' && kids.length > 0) {
                var a3 = [];
                for (var q = 0; q < kids.length; q++) {
                  a3.push(kids[q]);
                }
                return { top: a3, doctype: null, head: null, body: null, documentElement: null };
              }
            } catch (err) {
              return null;
            }
            return null;
          }
        }
      } catch (err) {
        return null;
      }
      return null;
    }

    function isValidFragment(bodyText, expectedSlug) {
      // Client-side defense-in-depth only; server output safety still applies.
      if (!isNonEmptyString(bodyText) || !isNonEmptyString(expectedSlug)) {
        return false;
      }
      // Fail-closed raw-text guard (not sanitization): full-document wrappers
      // never validate as fragments. DOM inspection below still applies.
      var lowRaw = String(bodyText).toLowerCase();
      if (lowRaw.indexOf('<!doctype') !== -1 || lowRaw.indexOf('<html') !== -1 ||
        lowRaw.indexOf('<head') !== -1 || lowRaw.indexOf('<body') !== -1) {
        return false;
      }
      var parsed = parseFragmentDoc(bodyText);
      if (!parsed || !parsed.top) {
        return false;
      }
      if (parsed.doctype) {
        return false;
      }
      if (parsed.head) {
        var hk = null;
        try {
          hk = parsed.head.childNodes || parsed.head.children || [];
        } catch (err) {
          hk = [];
        }
        for (var hi = 0; hi < hk.length; hi++) {
          var hn = hk[hi];
          if (!hn) {
            continue;
          }
          if (hn.nodeType === 1) {
            return false;
          }
          if (hn.nodeType === 3) {
            if (!isBlank(nodeText(hn))) {
              return false;
            }
            continue;
          }
          if (hn.nodeType === 8) {
            continue;
          }
          if (nodeTag(hn) !== '') {
            return false;
          }
        }
      }
      var top = parsed.top;
      var elementRoots = [];
      for (var i = 0; i < top.length; i++) {
        var n = top[i];
        if (!n) {
          continue;
        }
        if (n.nodeType === 1) {
          elementRoots.push(n);
        } else if (n.nodeType === 3) {
          if (!isBlank(nodeText(n))) {
            return false;
          }
        } else if (n.nodeType === 8) {
          /* ignore comments */
        } else if (nodeTag(n) !== '') {
          elementRoots.push(n);
        }
      }
      if (elementRoots.length !== 1) {
        return false;
      }
      var r = elementRoots[0];
      if (nodeTag(r) !== 'DIV') {
        return false;
      }
      var cls = nodeGetAttr(r, 'class');
      if (typeof cls !== 'string') {
        return false;
      }
      var tokens = cls.split(/\s+/);
      var hasProfile = false;
      for (var c = 0; c < tokens.length; c++) {
        if (tokens[c] === 'pfoa-cat-profile') {
          hasProfile = true;
          break;
        }
      }
      if (!hasProfile) {
        return false;
      }
      if (nodeGetAttr(r, 'data-pfoa-cat-profile') !== expectedSlug) {
        return false;
      }
      var all = [r];
      var stack = [r];
      while (stack.length > 0) {
        var cur = stack.pop();
        var kids = nodeKids(cur);
        for (var k = 0; k < kids.length; k++) {
          var kid = kids[k];
          if (!kid) {
            continue;
          }
          if (kid.nodeType === 3 || kid.nodeType === 8) {
            continue;
          }
          var tag = nodeTag(kid);
          if (tag === '' && kid.nodeType !== 1) {
            continue;
          }
          all.push(kid);
          stack.push(kid);
        }
      }
      for (var e = 0; e < all.length; e++) {
        var el = all[e];
        var t = nodeTag(el);
        if (!FRAG_ALLOWED_TAGS[t]) {
          return false;
        }
        var names = listAttrNames(el);
        for (var a = 0; a < names.length; a++) {
          var rawName = String(names[a]);
          var low = rawName.toLowerCase();
          if (low.indexOf('on') === 0) {
            return false;
          }
          if (low === 'style') {
            if (!isSafeStyleAttr(nodeGetAttr(el, names[a]))) {
              return false;
            }
            continue;
          }
          if (low === 'target') {
            var tv = nodeGetAttr(el, names[a]);
            var tlow = String(tv === null || tv === undefined ? '' : tv)
              .replace(/^\s+|\s+$/g, '').toLowerCase();
            if (tlow !== '_self' && tlow !== '_blank') {
              return false;
            }
            continue;
          }
          if (low === 'referrerpolicy') {
            // Narrow allow: referrerpolicy only on IFRAME (approved video
            // embeds) with a restricted value set; rejected everywhere else.
            if (t !== 'IFRAME') {
              return false;
            }
            var rpv = nodeGetAttr(el, names[a]);
            var rplow = String(rpv === null || rpv === undefined ? '' : rpv)
              .replace(/^\s+|\s+$/g, '').toLowerCase();
            if (rplow !== 'strict-origin-when-cross-origin' && rplow !== 'no-referrer' &&
              rplow !== 'same-origin' && rplow !== 'strict-origin') {
              return false;
            }
            continue;
          }
          if (low === 'fetchpriority') {
            // Narrow allow: fetchpriority only on IMG with a restricted
            // value set; rejected everywhere else.
            if (t !== 'IMG') {
              return false;
            }
            var fpv = nodeGetAttr(el, names[a]);
            var fplow = String(fpv === null || fpv === undefined ? '' : fpv)
              .replace(/^\s+|\s+$/g, '').toLowerCase();
            if (fplow !== 'high' && fplow !== 'low' && fplow !== 'auto') {
              return false;
            }
            continue;
          }
          if (t === 'IFRAME') {
            if (!FRAG_IFRAME_ALLOWED_ATTRS[low]) {
              return false;
            }
            continue;
          }
          if (FRAG_ALLOWED_ATTRS[low]) {
            continue;
          }
          if (low.indexOf('data-') === 0 || low.indexOf('aria-') === 0) {
            continue;
          }
          return false;
        }
        if (nodeHasAttr(el, 'href')) {
          var hv = nodeGetAttr(el, 'href');
          if (!isSafeUrlAttr(typeof hv === 'string' ? hv : String(hv === null || hv === undefined ? '' : hv))) {
            return false;
          }
        }
        if (nodeHasAttr(el, 'srcset')) {
          var sv = nodeGetAttr(el, 'srcset');
          var sText = typeof sv === 'string' ? sv : String(sv === null || sv === undefined ? '' : sv);
          if (isBlank(sText)) {
            return false;
          }
          var slow = sText.toLowerCase();
          if (slow.indexOf('javascript:') !== -1 || slow.indexOf('data:') !== -1 ||
            slow.indexOf('vbscript:') !== -1) {
            return false;
          }
          var cands = sText.split(',');
          for (var s = 0; s < cands.length; s++) {
            var cand = cands[s].replace(/^\s+|\s+$/g, '');
            if (cand === '') {
              return false;
            }
            var firstTok = cand.split(/\s+/)[0];
            if (!isSafeUrlAttr(firstTok)) {
              return false;
            }
          }
        }
        if (t === 'IMG') {
          if (!nodeHasAttr(el, 'src')) {
            return false;
          }
          var imgSrc = nodeGetAttr(el, 'src');
          if (!isSafeUrlAttr(typeof imgSrc === 'string' ? imgSrc : String(imgSrc))) {
            return false;
          }
        }
        if (t === 'A' && nodeHasAttr(el, 'href')) {
          var aHref = nodeGetAttr(el, 'href');
          if (!isSafeUrlAttr(typeof aHref === 'string' ? aHref : String(aHref))) {
            return false;
          }
        }
        if (t === 'IFRAME') {
          if (!nodeHasAttr(el, 'src')) {
            return false;
          }
          var fSrc = nodeGetAttr(el, 'src');
          var fText = typeof fSrc === 'string' ? fSrc : String(fSrc);
          if (!isSafeUrlAttr(fText) || !isYouTubeSrc(fText)) {
            return false;
          }
        } else if (nodeHasAttr(el, 'src') && t !== 'IMG' && t !== 'IFRAME') {
          var gSrc = nodeGetAttr(el, 'src');
          if (!isSafeUrlAttr(typeof gSrc === 'string' ? gSrc : String(gSrc))) {
            return false;
          }
        }
      }
      return true;
    }

    function isEpochOk(response, bodyText) {
      var respEpoch = null;
      try {
        respEpoch = getResponseEpoch(response, bodyText);
      } catch (err) {
        respEpoch = null;
      }
      if (!isNonEmptyString(documentEpoch) || !isNonEmptyString(respEpoch) ||
        respEpoch !== documentEpoch) {
        return false;
      }
      if (injectedIsEpochMatch) {
        try {
          return !!injectedIsEpochMatch(respEpoch);
        } catch (err) {
          return false;
        }
      }
      return true;
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

    function safeDispose(body) {
      if (!body) {
        return false;
      }
      var provider = liveProvider();
      if (!provider) {
        return false;
      }
      try {
        provider.disposeProfileGalleries(body);
      } catch (err) {
        return false;
      }
      return true;
    }

    function setHtml(el, html) {
      try {
        el.innerHTML = html;
      } catch (err) {
        /* stub hosts always accept string assignment */
      }
    }

    function hideDialog(dlg) {
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
    }

    function restoreFocus(who) {
      if (who && typeof who.focus === 'function' && inDocument(doc, who)) {
        try {
          who.focus();
        } catch (err) {
          /* ignore focus failures */
        }
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
      // Fallback also requires safe disposal; when the provider is gone or
      // disposal throws, this aborts and leaves loading/previous content.
      if (!safeDispose(body)) {
        return;
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
        // On dispose failure preserve the old body content (never silently
        // clear) but still hide the dialog and restore focus.
        if (safeDispose(body)) {
          setHtml(body, '');
        }
      }
      hideDialog(dlg);
      var who = invoker;
      invoker = null;
      restoreFocus(who);
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

      // Loading requires safe disposal; on failure preserve old content and
      // keep the dialog open state, but still proceed to fetch.
      if (safeDispose(body)) {
        setHtml(body, loadingHtml());
      }
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
          if (!isEpochOk(response, bodyText)) {
            showFallback(myId, href);
            return;
          }
          if (!isValidFragment(bodyText, slug)) {
            showFallback(myId, href);
            return;
          }
          var liveBody = findBody(liveDlg);
          if (!liveBody) {
            return;
          }
          // Provider must still be live; on disappearance abort the mutation,
          // keep previous safe content, and keep the dialog open. A fallback
          // here would also require safe disposal (hence the provider), so it
          // aborts as well, leaving loading/previous content in place.
          if (!liveProvider()) {
            return;
          }
          if (!safeDispose(liveBody)) {
            return;
          }
          setHtml(liveBody, bodyText);
          var initializer = liveProvider();
          if (!initializer) {
            // Init requires a live provider; do not leave an apparently
            // successful fragment. Dispose + fallback when safe; otherwise the
            // just-inserted fragment remains (documented race limitation).
            if (safeDispose(liveBody)) {
              setHtml(liveBody, fallbackHtml(href));
            }
            return;
          }
          try {
            initializer.initProfileGalleries(liveBody);
          } catch (err) {
            if (safeDispose(liveBody)) {
              setHtml(liveBody, fallbackHtml(href));
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
      if (!isEligibleAnchor(anchor, slug, href)) {
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
      // Ownership guard: a stale handle must not touch anything. This also
      // makes destroy idempotent: after a successful teardown the handle is
      // no longer the owner, so a second call is a no-op.
      if (!(cached && cached.handle === handle)) {
        return;
      }
      var dlg = null;
      var b = null;
      var who = invoker;
      try {
        dlg = getDialog();
      } catch (err) {
        dlg = null;
      }
      try {
        b = dlg ? findBody(dlg) : null;
      } catch (err) {
        b = null;
      }
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
      if (dlg && b) {
        // Only the owned dialog body is ever disposed/cleared. On unsafe
        // disposal preserve the body (never silently clear or abandon markup
        // without disposal) but still hide the dialog and restore focus.
        var ok = false;
        try {
          ok = safeDispose(b);
        } catch (err) {
          ok = false;
        }
        if (ok) {
          try {
            setHtml(b, '');
          } catch (err) {
            /* ignore */
          }
        }
        hideDialog(dlg);
        restoreFocus(who);
      } else if (dlg) {
        hideDialog(dlg);
        restoreFocus(who);
      }
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
