# PFOA Homepage Integration Audit

**Audit date:** September 9, 2026
**Scope:** Integrated Tasks 5A–5D homepage at committed baseline `9d9f7579834775d1a1e19a18fc11e778330de910` on `main`
**Overall verdict:** **Safe to build upon.** The integrated homepage has no Blocker or Major defect. Four concrete Minor issues should be handled in a focused homepage hardening follow-up, but they do not require holding ordinary Page work.
**Finding counts:** 0 Blockers, 0 Major, 4 Minor, 4 Observations.

## 1. Concise verdict

The seven homepage regions form a coherent document in the intended order: hero H1, optional assigned-Page content, pathways, adoption, news/events, promotions, and partners/supporters. Theme-owned headings descend logically from H1 to section H2s and card H3s; theme-owned section/heading IDs are unique; available cards are single native links; unavailable destinations do not emit empty controls; and DOM order matches visual and tab order.

Content sourcing remains appropriately conservative. Homepage destinations come from a small published-Page slug bridge or the configured Donate URL, with no environment-specific Page IDs or origins. Missing sources are omitted or rendered as explicit non-interactive states. The homepage neither parses legacy Page bodies nor fabricates animals, news, events, partners, fundraiser details, or payment data. Its lookups do not establish secondary loops or alter global `$post`.

The four Minor findings are localized: invalid promo-card nesting, compounding hero height, fixed promo columns in partial states, and two normal-size intro paragraphs that miss the 4.5:1 contrast threshold. None threatens activation, content preservation, navigation, payments, or the compatibility architecture.

## 2. Blockers

None.

No activation failure, content loss, unsafe payment implementation, main-query corruption, or hard dependency makes the homepage unsafe to extend.

## 3. Major findings

None.

Nothing found needs to delay ordinary Page hardening.

## 4. Minor findings

### m-1 — The promo body uses an inline `span` as the parent of an `h3`

- **Files/lines:** `pfoa-theme/template-parts/homepage-promos.php:97-102`; related layout rule at `pfoa-theme/style.css:2407-2414`.
- **Concrete failure mode:** `.homepage-promo__body` is emitted as a `span`, but it contains an `h3`. A `span` accepts phrasing content, not a heading. The local HTML conformance check closes the `span` before the `h3` and discards the later closing tag. Browsers are generally tolerant, but an HTML repair, sanitization, or test pipeline can therefore sever the heading and following copy from the flex wrapper, changing the card layout and accessible DOM from the authored structure.
- **Smallest remediation:** Emit `.homepage-promo__body` as a `div`. The existing class-based CSS can remain unchanged.

### m-2 — Shared section padding compounds the hero's own minimum height

- **Files/lines:** `pfoa-theme/style.css:1627-1629`, `1644-1648`, `1679-1685`, and `2539-2541`.
- **Concrete failure mode:** The generic `.homepage-section` rule adds block padding to the hero, while `.homepage-hero__inner` inherits the hero's minimum height. The child minimum plus the parent's block padding can make the rendered hero materially taller than the declared hero minimum. At a 390px CSS viewport, the variables resolve to a 352px hero minimum plus 64px top and bottom section padding, producing a minimum layout demand of roughly 480px before content growth. This undermines the intended narrow-screen hero sizing and pushes the first action region substantially farther below the fold.
- **Smallest remediation:** Reset `padding-block: 0` on `.homepage-hero`; retain the deliberate responsive padding on `.homepage-hero__content`.

### m-3 — Partial promo states leave fixed empty grid tracks on larger widths

- **Files/lines:** item omission in `pfoa-theme/template-parts/homepage-promos.php:15-26`, `49-55`, and `76-79`; fixed grid tracks in `pfoa-theme/style.css:2298-2305` and `2589-2592`.
- **Concrete failure mode:** Promo destinations correctly disappear when unavailable, but the desktop grid always declares three columns. If only one safe destination remains, the sole card occupies the first third of the row and two full tracks remain blank; with two destinations, one third remains blank. The tablet rule similarly fixes two tracks. This is a visible broken assumption in exactly the requested partial-content state rather than an unavailable control.
- **Smallest remediation:** Let the grid adapt to the number of rendered items, for example with an appropriately bounded `auto-fit`/`minmax()` definition or item-count modifier, while retaining the one-column mobile rule.

### m-4 — News/events and promo intro text is below AA contrast on the outer surface

- **Files/lines:** color tokens at `pfoa-theme/style.css:16` and `18`; News/events surface and intro at `pfoa-theme/style.css:1946-1950` and `2107-2109`; promo surface and intro at `pfoa-theme/style.css:2269-2271` and `2291-2295`.
- **Concrete failure mode:** Normal-size intro copy uses muted `#6f6963` on outer `#ebe7dd`. Their calculated contrast ratio is approximately **4.39:1**, below WCAG AA's 4.5:1 requirement for normal text. This affects the visible News and events intro and More ways to help intro. The same selector is harmless in Adoption and Partners because those sections use white, where the ratio is approximately 5.42:1.
- **Smallest remediation:** Use a slightly darker scoped intro color on the outer-background sections, or adjust the relevant surface without broadly changing the accepted design token.

## 5. Observations

### O-1 — Integrated document structure is sound under the static-front-Page contract

- `pfoa-theme/front-page.php:18-40` provides one `main` landmark and loads the regions once, in the documented order, after `the_post()` establishes the assigned Page.
- `homepage-hero.php:28-65` supplies the theme-owned H1. The later template parts use visible H2 section labels and H3 card labels. Theme-owned `aria-labelledby` references resolve, and a literal-ID scan found no duplicate ID among the header, footer, front-page template, and homepage parts.
- `homepage-editorial.php:12-32` keeps assigned Page content outside every card link and passes it through `the_content()`, preserving interactive plugin output without nesting it in another control.
- WordPress also selects `front-page.php` when the reading setting is “latest posts.” In that out-of-contract mode, the loop could repeat the fixed homepage IDs for multiple posts. The compatibility baseline explicitly uses an assigned static Homepage, so this remains a configuration/runtime guard rather than a finding for the audited deployment.
- Stored editor content can itself introduce an additional H1 or colliding IDs. The repository does not contain the database content, so the current assigned Page must be checked in rendered QA; the theme must not rewrite legacy content speculatively.

### O-2 — Content-source, link, and post-state handling are safe

- `pfoa-theme/functions.php:305-345` prefers the configured Donate destination, otherwise requires a published Membership Page, and returns an empty homepage destination when neither resolves. The earlier homepage self-link fallback identified in the Task 5A audit has been corrected.
- The slug candidates in the homepage parts match the documented compatibility inventory. No homepage source contains a staging/production hostname or a hard-coded Page ID. The `image_id` accepted by `pfoa_homepage_partner_items` is filter input, not an environment-bound constant.
- All Page destinations are conditional on a published `WP_Post` and a usable permalink. Hero CTAs disappear when unavailable; pathways become labelled static cards; adoption becomes an explicit static state; news/events omits the whole region only when both sources are absent; promos omit missing items and the entire region when empty; partners never invent a roster.
- The Page helper uses `get_page_by_path()` and passes post objects directly into title/permalink APIs. There is no `WP_Query`, `query_posts()`, `setup_postdata()`, or global `$post` assignment to restore.
- No homepage component calls Popup Maker or ThemeBlvd. Existing Popup Maker behavior can still arrive through the assigned Page's normal `the_content()` pipeline, which is compatibility rather than a homepage dependency.
- A configured Donate URL and any future filtered partner URLs remain editorial/runtime inputs. Confirm that their actual configured values are live and are not the homepage itself before launch; static source inspection cannot establish external availability or configuration values.

### O-3 — Remaining empty states are coherent

| Region | Missing or partial-source behavior | Assessment |
|---|---|---|
| Hero image | CSS brand treatment remains behind the H1 and any available CTAs. | Pass, apart from m-2 sizing |
| Hero pathways | Each missing destination removes the hero CTA; pathway cards remain labelled and non-interactive. | Pass |
| Assigned Page content | Region is omitted when stored content is empty. | Pass; filtered-to-empty output needs runtime confirmation |
| Adoption | Visible H2, explanatory copy, and explicit unavailable state remain; no animal is implied. | Pass |
| News/events | Region is omitted when both are missing; one missing companion becomes a labelled static card. | Pass |
| Promos | Missing items and an entirely empty rail are omitted. | Link safety passes; partial layout has m-3 |
| Partners | No roster produces an explained empty state and an optional published Business Partners Page link. | Pass; final assets are explicitly deferred |

No missing-source branch emits an empty `href`, an interactive-looking dead CTA, an orphan section heading, or fabricated factual content.

### O-4 — CSS and clean-room boundaries remain appropriately narrow

- Homepage component rules at `pfoa-theme/style.css:1626-2603` are all rooted beneath `.front-page-content`; they do not target accepted header/footer selectors or broad legacy Page elements. The editor region continues to receive the previously accepted `.entry-content` and `.page-content-compatibility` behavior.
- The homepage reuses the established content widths, gutters, spacing, color, typography, border, radius, focus, and motion tokens. Grids use zero-minimum tracks and stack at the mobile breakpoint; labels and card copy are not given fixed heights.
- Native full-card links provide the only card interaction. There are no nested links/buttons, positive tab stops, hover-only essential facts, or new ARIA widgets. Global focus-visible and reduced-motion rules apply, with additional component states.
- Source scans found no CMSMasters code/assets, proprietary font/icon/image asset, page builder, custom post type, taxonomy, structured animal/news/event model, plugin bootstrap, homepage JavaScript, or new framework. The partner filter is a narrow presentation input and is empty by default.

## 6. Validation results

- Confirmed the starting working tree was clean and `HEAD` was `9d9f7579834775d1a1e19a18fc11e778330de910` on `main` (also matching `origin/main`).
- Ran `php -l` across all **25** theme PHP files: all passed.
- Ran `git diff --check` against the committed baseline before creating this report and reran it with this report present: passed.
- Parsed `pfoa-theme/theme.json` with PHP's JSON parser: valid.
- Ran `node --check pfoa-theme/assets/js/navigation.js`: passed.
- Ran `bash -n scripts/build-zip.sh`: passed without executing the packaging script.
- Scanned homepage PHP for secondary-query/global-post APIs, post-type/taxonomy registration, builder/framework calls, environment origins, and hard-coded Page IDs: none found.
- Scanned the header/footer/homepage templates for repeated literal IDs: none found.
- Ran a local HTML Tidy conformance check against the promo body structure: it reported the missing `</span>` before the `h3` and discarded closing `span` described in m-1.
- Calculated static token contrast for the affected muted/outer pairing: approximately 4.39:1; white/brand, white/brand-dark, dark-text/gold, muted/white, and gold/charcoal pairings used by essential homepage text meet the normal-text threshold.

This audit created only `docs/sol-homepage-integration-audit.md`. It did not modify theme product files, staging, or production, and it did not commit or push.

## 7. Deferred runtime and browser checks

Before launch, verify in an activated WordPress test copy:

- the assigned static Homepage is still the single main-query object and its filtered editor output does not add a second H1, collide with theme-owned IDs, or collapse to an empty labelled region;
- actual Adopt, Volunteer, Membership/Donate, News/Home Front, Events, Potholders, Wish List, and Business Partners destinations, including that configured Donate and future partner URLs are not self-links or dead external destinations;
- hero image alt text, crop/focal point, scrim contrast, and visual height at desktop, tablet, approximately 390px, increased text size, and browser zoom;
- keyboard focus order and visible focus indicators on every full-card link, including whether focus painting is clipped by rounded/overflow containers in any target browser;
- real long Page titles, translated labels, one- and two-item promo rails after m-3, and partner logos with varied intrinsic proportions;
- Popup Maker triggers, wide legacy homepage content, embedded media, and any other filtered editor output preserved by `homepage-editorial.php`;
- gradient-backed decorative display text and the final approved imagery for visual contrast, since static token checks cannot establish every pixel of those backgrounds.

The completed homepage is safe to build upon. Address m-1 through m-4 in a bounded homepage hardening pass; no architecture rewrite, content-model expansion, or plugin integration is warranted.

**Proceed**
