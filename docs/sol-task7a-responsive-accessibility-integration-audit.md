# PFOA Task 7A — Responsive and Accessibility Integration Audit

**Audit date:** September 10, 2026

**Baseline:** `b508ba3e6ffa0ebf00d6a191965f9054d21e5831` on `main`

**Scope:** Repository-wide static/local integration QA; no staging or production access

**Overall verdict:** **Safe for a first staging installation and rendered UAT.** No Blocker or Major defect was found. Seven concrete Minor issues remain: four previously reported homepage issues are still present, and three additional keyboard/semantic/focus issues should be corrected before release.

**Finding counts:** 0 Blockers, 0 Major, 7 Minor, 10 runtime/UAT observations.

## 1. Concise verdict

The theme remains a conventional, clean-room WordPress theme. The header,
footer, homepage, ordinary Page templates, compatibility template, and Popup
Maker bridge compose without a static activation, content-loss, dependency, or
cross-component overflow failure. The 1320px navigation boundary is synchronized
between CSS and JavaScript; the footer changes from four to two to one column;
homepage grids use zero-minimum tracks and stack at the narrow breakpoint; and
the legacy bridge contains the documented fixed-width media/table patterns
without rewriting Page content.

The keyboard and semantic foundation is also generally sound: native links and
buttons are used, submenu disclosures remain separate from parent links, the
skip link targets the one `main` landmark, breakpoint changes move focus away
from content that is about to be hidden, header/footer focus rings remain white
on dark surfaces, and reduced-motion handling covers all transitions and smooth
scrolling.

The seven Minors are real but bounded. Desktop Escape handling is defeated by a
focus event that reopens the submenu. The homepage's brick pathway cards use a
blue focus outline with only 1.13:1 contrast against the surrounding brick.
Shared empty-result output creates a second H1 on empty search/archive/posts
pages. The four open homepage findings remain: invalid promo-card nesting,
compounded hero height, fixed empty promo tracks in partial states, and 4.39:1
intro-text contrast on the outer surface.

## 2. Blockers

None.

No PHP/JavaScript/JSON parse failure, fatal dependency, omitted Page content,
unsafe payment implementation, inaccessible fixed overlay, or confirmed
unreachable primary content makes the theme unsafe for a first staging
installation.

## 3. Major findings

None.

The remaining defects do not prevent activation, ordinary content access,
keyboard traversal, or the baseline fallback behaviors. They should be handled
in a focused hardening pass before release, without an architecture rewrite.

## 4. Minor findings

### m-1 — Desktop Escape closes and immediately reopens a focused submenu

- **Files/lines:** `pfoa-theme/assets/js/navigation.js:63-68`,
  `174-179`, and `217-219`.
- **Concrete failure mode:** When desktop focus is on a submenu descendant,
  Escape calls `closeSubmenu( button, true )`. The function closes the branch
  and then focuses its disclosure button. That synchronous focus fires the
  parent item's desktop `focusin` handler, which calls
  `setSubmenuState( button, true )` and reopens the branch. A local Chromium
  fixture at a 1400px viewport reproduced the final state as
  `aria-expanded="true"`, `.is-open`, with focus on the disclosure. The same
  fixture at compact width ended closed because the `focusin` reopening is
  desktop-only.
- **Why it matters to PFOA:** The accepted navigation explicitly supports the
  three-level Fundraising branch and advertises deepest-first Escape behavior.
  Keyboard users inside a desktop dropdown cannot actually dismiss that branch
  with Escape, although they can still Tab out; this is a current interaction
  regression rather than a speculative re-audit of Task 4A.
- **Smallest remediation:** Suppress the focus-driven reopen during Escape
  restoration, or focus the disclosure only after recording a state that the
  `focusin` handler respects. Recheck first- and second-level branches with
  focus on both links and disclosure buttons.

### m-2 — The 3px pathway focus ring does not contrast with the brick section

- **Files/lines:** focus token and global ring at
  `pfoa-theme/style.css:25` and `103-106`; pathway surface at
  `1890-1893`; pathway focus state at `1935-1941`.
- **Concrete failure mode:** The focused full-card pathway link retains the
  global `#005fcc` outline. Its 3px outline is painted outside the card against
  the solid `#934244` pathway section, a contrast ratio of approximately
  **1.13:1**, below the 3:1 focus-indicator target. The focus state also adds a
  one-pixel white border, so focus is not entirely absent, but the promised
  high-contrast 3px ring is not delivered on these four primary cards.
- **Why it matters to PFOA:** Adopt, Foster, Donate, and Volunteer are the
  homepage's main action routes. Their keyboard focus should be as reliably
  locatable as the accepted white-ring header and footer controls.
- **Smallest remediation:** Add a pathway-scoped light/dark two-color outline
  or another 3px indicator that reaches at least 3:1 against both the focused
  card and surrounding brick. Do not change the already accepted header/footer
  focus treatment.

### m-3 — Shared no-results output creates two H1 headings in common contexts

- **Files/lines:** existing page H1s at `pfoa-theme/home.php:16-18`,
  `archive.php:16-18`, and `search.php:18-26`; shared empty-state H1 at
  `pfoa-theme/template-parts/content-none.php:12-15`.
- **Concrete failure mode:** When the posts index, an archive, or a search has
  no results, the template first emits its contextual H1 and then loads
  `content-none.php`, which emits a second H1, “Nothing found.” The shared part
  is also used by contexts without an earlier H1, so changing it unconditionally
  to an H2 would create the inverse problem there.
- **Why it matters to PFOA:** Core search and readable no-results output are in
  the compatibility baseline. Two top-level headings weaken the promised
  single-H1 page structure and make the empty result state less predictable for
  heading navigation.
- **Smallest remediation:** Let callers supply the appropriate heading level,
  or have contexts with an existing page H1 render the no-results label as H2
  while preserving an H1 where the shared part is the page's only heading.

### m-4 — The promo body uses a `span` as the parent of an H3

- **Files/lines:** `pfoa-theme/template-parts/homepage-promos.php:97-102`;
  related flex rule at `pfoa-theme/style.css:2529-2536`.
- **Concrete failure mode:** `.homepage-promo__body` is a `span` containing an
  H3. A `span` permits phrasing content, not a heading, so the authored card is
  not conforming HTML. Current Chromium retains the intended DOM, but a local
  HTML Tidy check closed the wrapper before the H3 and discarded the authored
  closing `span`, demonstrating that repair pipelines need not preserve the
  intended boundary.
- **Why it matters to PFOA:** The invalid boundary contains the accessible name
  and copy for every homepage promo link. A repair that separates it from the
  flex wrapper can alter both reading structure and card layout.
- **Smallest remediation:** Emit `.homepage-promo__body` as a `div`; its
  existing class-based CSS can remain unchanged.

### m-5 — Shared section padding still compounds the hero minimum height

- **Files/lines:** shared padding at `pfoa-theme/style.css:1749-1751`;
  hero minimum at `1766-1769`; inherited child minimum at `1801-1807`;
  narrow overrides at `2661-2670`.
- **Concrete failure mode:** The hero is a `.homepage-section`, so it receives
  block padding while its inner flex child inherits the hero's minimum height.
  The parent's rendered height becomes the child's minimum plus both padding
  edges. A local Chromium fixture at 1440px measured a 688px declared/inner
  minimum, 86.4px padding on each edge, and an **860.8px rendered hero**. At a
  390px CSS viewport, the declared 352px minimum plus 40px padding per edge
  creates roughly a **432px** minimum before content growth.
- **Why it matters to PFOA:** This materially exceeds the declared responsive
  sizing and the intended 250–360px mobile range, pushing editable homepage
  content and the primary pathways farther below the fold.
- **Smallest remediation:** Set `padding-block: 0` on `.homepage-hero` and
  retain the deliberate responsive padding on `.homepage-hero__content`.

### m-6 — Partial promo states retain fixed empty grid tracks

- **Files/lines:** conditional item creation/omission at
  `pfoa-theme/template-parts/homepage-promos.php:15-26`, `49-55`, and
  `76-79`; grid tracks at `pfoa-theme/style.css:2420-2427` and
  `2711-2715`.
- **Concrete failure mode:** Missing destinations are correctly omitted, but
  the desktop grid always creates three columns and the intermediate grid two.
  One safe promo therefore occupies only one-third or one-half of the row; two
  desktop promos leave a complete empty track. The current documented PFOA
  Page inventory supplies all three fallback destinations, but the component's
  explicit partial-source behavior is visually broken if configuration or Page
  availability changes.
- **Why it matters to PFOA:** The theme deliberately promises graceful
  degradation without inventing or self-linking destinations. Fixed blank
  tracks make that supported state look incomplete and waste scarce tablet
  width.
- **Smallest remediation:** Use a bounded `auto-fit`/`minmax()` grid or an
  item-count modifier while retaining the one-column mobile rule.

### m-7 — Two homepage intro paragraphs remain below AA text contrast

- **Files/lines:** muted and outer tokens at `pfoa-theme/style.css:16` and
  `18`; News/events surface and intro at `2229-2231` and `2068-2073`;
  promo surface and intro at `2390-2393` and `2413-2418`.
- **Concrete failure mode:** Normal-size muted text `#6f6963` on outer
  `#ebe7dd` has approximately **4.39:1** contrast, below the 4.5:1 AA
  threshold. This exact pairing is used by the visible News and events intro
  and More ways to help intro. The same intro selector passes on white in the
  Adoption and Partners sections.
- **Why it matters to PFOA:** These are ordinary explanatory paragraphs, not
  decorative or disabled text, and they introduce two major homepage regions.
- **Smallest remediation:** Darken only the intro text on outer-background
  sections or adjust those surfaces; do not broadly reopen the accepted token
  system.

## 5. Integrated responsive assessment

- **Header/navigation:** CSS and JavaScript both transition at 1320px. Compact
  navigation is in flow, uses full-width wrapping rows, and supplies 52px
  disclosure targets. Desktop branding, navigation, and Donate use a bounded
  three-column budget. Focus is deliberately moved to a visible control when a
  viewport change would hide it. Real menu fit, touch behavior at desktop width,
  and flyout edge collision remain UAT items; m-1 is the only locally
  demonstrated interaction defect.
- **Footer:** The layout changes from four columns to two below 1320px and one
  at 720px. Groups have zero-minimum tracks, no fixed height, and ordinary
  wrapping links/addresses. Legal navigation stacks on mobile. No conflicting
  breakpoint or static overflow defect was found.
- **Homepage:** Pathways move 4 → 2 → 1 columns; adoption and news/events move
  2 → 1; promos ultimately move to one; partners use a bounded auto-fit grid.
  Cards have zero-minimum tracks and flexible copy. The concrete exceptions are
  m-5's hero height and m-6's partial promo tracks. Long real titles, focal
  crops, and image-dependent height/contrast need browser testing.
- **Ordinary and legacy Pages:** Gutters move 40 → 24 → 20px. Default Page
  prose stays within 75ch. Compatibility Pages cap observed inline widths,
  override ordinary inline minimum widths, stack the confirmed Genesis half
  and third columns at 720px, collapse classic galleries to one column at
  480px, constrain media, and provide local table overflow. The global
  `body { overflow-x: hidden; }` makes rendered overflow inspection essential:
  absence of a document scrollbar alone is not proof that every real legacy
  outlier is reachable.

## 6. Keyboard, focus, and semantic assessment

- Primary navigation uses a labelled native `nav`, native parent links, and
  separate native disclosure buttons with synchronized `aria-expanded` and
  `aria-controls`. No positive tab order, ARIA menu pattern, or nested
  interactive control was found. The no-JavaScript path leaves all links in
  document flow. m-1 is the current Escape defect.
- Header and footer controls use the accepted white, two-color focus treatment.
  Main content controls inherit the 3px global focus ring; card-specific focus
  states do not suppress it. m-2 is the one exact solid-surface failure. Focus
  visibility on real hero imagery and inside plugin dialogs remains UAT.
- The templates provide a skip link, one `main`, a site header, a site footer,
  labelled navigation landmarks, Page-title H1s, and normal section/card
  heading descent. Native links and buttons are chosen appropriately. m-3 and
  m-4 are the static semantic exceptions.
- Theme-owned informative images use WordPress attachment output; decorative
  homepage media is hidden from accessibility APIs; partner image-only links
  receive partner-name alt text. Stored attachment alt text, legacy content
  alternatives, table headers/captions, form labels/errors, iframe titles, and
  PDF link wording are content/runtime facts and cannot be certified from this
  repository.

## 7. Contrast, motion, legacy content, and Popup Maker

- White on charcoal/brick, dark text on gold, brand links on white, muted text
  on white, and the scoped white header/footer focus rings pass the relevant
  static contrast thresholds. m-2 and m-7 are the exact failures. Pixel-level
  hero/photographic contrast and plugin-owned colors require rendering.
- Hover never exposes unique content. Link destinations remain in ordinary
  text/controls. Motion is limited to short color/transform transitions and
  smooth scrolling; the global reduced-motion query disables smooth scrolling,
  collapses transition/animation duration, and prevents repeated animation.
  No carousel or animation system has appeared.
- The legacy bridge does not remove cells, reorder content, hide shortcode-like
  text, take over lightbox links, or apply a generic layout framework. Wide
  legacy tables use local horizontal overflow rather than hidden overflow, but
  keyboard/touch access to real table contents must be checked in target
  browsers. Arbitrary iframes keep their authored viewport; only known YouTube
  sources receive 16:9 sizing.
- The Popup Maker bridge does not apply the shared gold action-button
  presentation to bare buttons, set dialog
  position/display/visibility/z-index/height, impose scroll locking, or add
  JavaScript. `.popmake-close`/`.pum-close` are not visually or behaviorally
  reimplemented. `.pum-container .pum-content` only caps known
  width/media/form outliers and adds a focus ring. The theme remains functional
  when Popup Maker is absent. Runtime focus containment/return, Escape, close,
  overlay stacking, long-dialog scrolling, and PayPal handoff remain plugin/UAT
  responsibilities.

## 8. Scope and clean-room sanity

Product-source scans found no CMSMasters, Pet Rescue, ThemeBlvd, Alyeska,
Elementor, WPBakery, Revolution Slider, page-builder runtime, proprietary asset,
external font, custom post type, taxonomy, custom database table, payment
credential, staging/production hostname, or theme-owned Popup Maker API call.
The only Genesis and Popup Maker references in product source are explanatory
comments and narrowly scoped compatibility selectors. No dependency or new
structured content model was introduced.

## 9. Validation results

- Confirmed `main`, exact `HEAD`
  `b508ba3e6ffa0ebf00d6a191965f9054d21e5831`, matching `origin/main`, and a
  clean starting working tree.
- Ran `php -l` across all **25** theme PHP files under PHP 8.5.10: all passed.
- Ran `node --check pfoa-theme/assets/js/navigation.js`: passed.
- Parsed `pfoa-theme/theme.json` with `jq`: passed.
- Ran `bash -n` and ShellCheck on `scripts/build-zip.sh`: passed.
- Ran `git diff --check` before and after adding this report: passed.
- Verified `dist/pfoa-theme-0.1.0.zip` integrity/member scope and compared every
  archived file byte-for-byte with `pfoa-theme/`: passed; no source or archive
  member mismatch was found.
- Ran targeted static scans for hidden/suppressed focus, positive tab order,
  nested interactive controls, fixed sizing/overflow, motion, prohibited
  frameworks/dependencies, environment origins, payment secrets, content-model
  registration, and Popup Maker lifecycle takeover. Only the issues documented
  above were substantiated.
- Calculated exact sRGB contrast for the token pairs used by findings m-2 and
  m-7: blue/brick 1.13:1 and muted/outer 4.39:1.
- Used bounded local headless Chromium fixtures—not staging—to reproduce m-1
  and measure m-5. The desktop Escape fixture ended expanded/open; the compact
  control fixture ended closed. The 1440px hero fixture rendered at 860.8px
  from a 688px inherited minimum plus 86.4px padding on each edge.
- Ran an isolated HTML Tidy check for m-4: it reported a missing closing
  `span` before the H3 and discarded the later closing `span`.

This audit adds only this report. It does not modify theme product files,
the distribution archive, staging, or production, and it performs no payment
or external form action.

## 10. Cannot be accepted or rejected until rendered staging/browser UAT

These are verification items, not additional findings unless the rendered test
demonstrates a defect:

1. **Real menu widths and input modes:** assigned PFOA menu, actual logo,
   Donate label, long labels, all three levels, flyout edge direction, pointer,
   touch-at-desktop-width, and compact in-flow height around 1319/1320px.
2. **Breakpoint focus and zoom:** cross 1320px in both directions with focus on
   links/toggles at each depth; test browser zoom and text-only enlargement to
   200%, including sticky/admin-bar and same-page anchor offsets.
3. **No-JavaScript navigation:** verify the real assigned hierarchy remains
   visible, readable, and reachable at desktop and mobile widths before any
   optional plugin behavior is considered.
4. **Actual homepage content:** real PFOA hero image alt text, focal crop,
   scrim/pixel contrast, long title wrapping, CTA focus painting, and real card
   titles/partner-logo proportions at approximately 390, 720/768, 1024, 1320,
   and 1440px.
5. **Legacy Page inventory:** representative 1279px/730px and other real
   tables, inline `!important` outliers, Genesis columns with plugin CSS present
   and absent, aligned images, galleries/captions, long links/headings, forms,
   and proof that `body` overflow suppression masks no unreachable content.
6. **Embeds and PDFs:** real YouTube players, any non-YouTube iframe discovered
   on the 40 Pages, PDF links/viewers, intrinsic ratios, keyboard/touch internal
   scrolling, iframe titles, and third-party availability.
7. **Popup Maker dialogs:** Rhodes, Danette Grady, Loki, Potholders, and Donate
   at desktop/mobile/200% zoom; correct content, trigger retention, close-button
   focus, focus entry/containment/return, Escape, background inertness, overlay
   stacking above the sticky header, long-content scrolling, and responsive
   media/tables/forms.
8. **Donation handoff:** approved configured Donate destination and existing
   Popup Maker/PayPal route, including recipient/purpose/return/thank-you
   behavior, without completing a payment.
9. **Stored accessibility data:** one rendered H1 and noncolliding IDs after
   `the_content()` filters, informative/decorative alt decisions, table
   captions/headers, form labels/instructions/errors, descriptive PDF/map/social
   labels, and any surviving raw shortcode-like output.
10. **Computed visual contrast:** real imagery/gradients, plugin theme colors,
    forced-colors/high-contrast behavior, and focus outlines that may be clipped
    by rounded/overflow card or dialog containers in target browsers.

## 11. Recommendation

Proceed to a focused repository remediation for m-1 through m-7, then run the
rendered staging/browser matrix above. The fixes should remain local to the
navigation state transition, homepage focus/markup/layout/contrast rules, and
context-sensitive no-results heading. No general refactor, content-model work,
Popup Maker replacement, or framework addition is warranted.
