# PFOA Task 6A Page Compatibility Audit

**Audit date:** September 10, 2026
**Scope:** Task 6A ordinary Page rendering and legacy-content hardening at committed baseline `c4c500e40b52204a56ee3d5775303fffd0ad921b` on `main`
**Overall verdict:** **Not safe to build upon until one Major containment defect is remediated.** The Page-template architecture and clean-room boundary pass. One inline `min-width` cascade failure can still leave legacy content wider than the mobile viewport and, because document overflow is hidden, clip it beyond reach. Two narrower CSS issues are Minor.
**Finding counts:** 0 Blockers, 1 Major, 2 Minor, 4 Observations.

## 1. Concise verdict

`page.php` and `template_builder.php` correctly share `template-parts/content-page.php`. Both run the normal WordPress Page loop, preserve core post classes, and send stored content through `the_content()` and `wp_link_pages()`. The compatibility template is an independently written, conventional template; it contains no ThemeBlvd or Genesis implementation, content parsing, builder behavior, plugin bootstrap, payment data, or JavaScript.

The compatibility class is correctly attached only to the legacy-assigned Page article and the already-established front-page editorial article. Most new bridge selectors are rooted beneath `.page-content-compatibility .entry-content`, so they cannot select the header, navigation, footer, or homepage components outside the editorial content region. The observed half/third Genesis-column subset clears and stacks coherently, images are capped, PDFs are bounded, classic galleries collapse to one column at 480px, and legacy lightbox anchors remain ordinary links.

The bridge is not ready to hand off to Popup Maker work, however. Its stated inline `min-width` protection cannot win against an inline declaration. That leaves a concrete path to mobile clipping in the documented legacy content class. The global table rule and the blanket compatibility-iframe ratio are also broader than the migration need.

## 2. Blockers

None.

The templates have no activation-fatal path, Page content is not omitted or rewritten, and no prohibited framework or plugin dependency was introduced.

## 3. Major findings

### M-1 — The inline `min-width` safeguard cannot override the inline declaration it targets

- **Files/lines:** `pfoa-theme/style.css:727-734`; clipping amplifier at `pfoa-theme/style.css:56-64`; stated behavior at `docs/pfoa-task-6a-page-compatibility-notes.md:33-35`; legacy inline-style fixture at `docs/pfoa-compatibility-baseline.md:179`.
- **Concrete failure mode:** The rule selects elements whose `style` attribute contains `min-width`, then assigns `min-width: 0` from the stylesheet without `!important`. An authored value such as `style="min-width: 730px"` has inline precedence and therefore remains in force. The companion `max-width: 100%` does not cure the conflict: when the used minimum is greater than the maximum, the minimum governs sizing. At a roughly 390px viewport, that element can remain hundreds of pixels wider than the content shell. Because `body` has `overflow-x: hidden`, the excess is not available through document scrolling and can be clipped beyond reach. This contradicts the Task 6A note that inline minimum widths are capped and the baseline requirement that inline presentation styles be overridden where necessary for responsive readability.
- **Why Major:** This is the exact legacy-overflow class Task 6A is intended to harden. It can make Page content inaccessible on mobile and should be corrected before adding another interoperability layer.
- **Smallest remediation:** In the narrowly scoped `[style*="min-width"]` bridge selector, make the containment override capable of beating ordinary inline author CSS (for example, a scoped `min-width: 0 !important`). Preserve inline colors/fonts, and treat any actual inline `!important` outlier as an explicit staging/content-cleanup case. Recheck the representative inline-style Pages at 390px.

## 4. Minor findings

### m-1 — The table compatibility mechanism is global to every `.entry-content`, not local to the legacy bridge

- **Files/lines:** `pfoa-theme/style.css:682-710`; modern/default Page content at `pfoa-theme/template-parts/content-page.php:20-29`; legacy table fixture at `docs/pfoa-compatibility-baseline.md:171`.
- **Concrete failure mode:** `.entry-content table` changes every content table to `display: block`, forces `width: 100%`, supplies its own horizontal scrolling, and imposes borders/cell padding. That includes a modern core Table block on the default-template Page and tables in ordinary Posts, even when no `.page-content-compatibility` ancestor exists. A core `.wp-block-table` already owns overflow at its figure wrapper; the rule can therefore create a second scroll surface, discard intentional intrinsic-width presentation, and override modern block styling unrelated to the migration bridge. Table markup and cell order are not destroyed, but the selector is materially broader than the documented legacy Page need.
- **Task 6A relevance:** The global table rule predates `c4c500e`, but Task 6A relies on it as the implementation of its table-hardening contract. It is included here because its selector scope directly determines whether that compatibility behavior remains contained; no unrelated homepage or shell behavior is being reopened.
- **Smallest remediation:** Scope the invasive `display`/overflow compatibility behavior to `.page-content-compatibility`, and let the core `.wp-block-table` wrapper retain its native overflow behavior. Shared visual table defaults can remain broad if they do not replace the table's display model.

### m-2 — Every fixed-dimension compatibility iframe is forced to a full-width 16:9 box

- **Files/lines:** source-specific YouTube handling at `pfoa-theme/style.css:578-583`; new blanket rule at `pfoa-theme/style.css:771-780`; documented Home Front dimensions at `docs/pfoa-compatibility-baseline.md:159` and `177`; preservation contract at `docs/pfoa-theme-reconnaissance.md:407`.
- **Concrete failure mode:** The compatibility selector matches any iframe with `width` and `height`, not only the documented YouTube embeds, and replaces its box with `width: 100%`, `height: auto`, and `aspect-ratio: 16 / 9`. The known Home Front attributes include 300x150, 180x90, and 180x95 ratios, so their authored boxes are unconditionally changed and the small players expand to the full Page column. Any later non-video fixed-dimension iframe on one of the 39 compatibility Pages would receive the same video ratio, potentially producing excess height or a clipped embedded viewport. This does not change the iframe destination or remove its content, so it is not a release blocker by itself.
- **Smallest remediation:** Keep responsive sizing limited to the confirmed video sources (or an explicit media wrapper) and remove the blanket aspect-ratio assumption for arbitrary compatibility iframes. Verify the intended Home Front presentation in a browser rather than inferring every iframe's aspect from one generic rule.

## 5. Observations

### O-1 — Shared Page/template architecture passes

- `pfoa-theme/page.php:17-25` and `pfoa-theme/template_builder.php:24-32` each run `have_posts()`, call `the_post()`, and delegate exactly once to the same Page part. The only deliberate difference is the boolean compatibility argument.
- `pfoa-theme/template-parts/content-page.php:12-29` adds `page-content-compatibility` only when requested, retains `post_class()`, emits one Page-title H1, calls `the_content()`, and preserves paginated Page links with a labelled navigation landmark.
- The refactor did not remove meaningful behavior from the prior templates. The previous compatibility template contained the same title/content/Page-link sequence inline; Task 6A moved it into the shared part.
- `pfoa-theme/template-parts/homepage-editorial.php:18-29` retains the compatibility hook only around stored front-page editor content. The rest of the homepage, plus header/navigation/footer, is outside that selector ancestry.

### O-2 — The Genesis Columns bridge is appropriately small in static review

- `pfoa-theme/style.css:736-769` addresses only `.gca-column.one-half`, `.gca-column.one-third`, `.first`, and the documented clearing markers. It is CSS fallback behavior, not a Genesis API or framework reproduction.
- The global border-box rule at `pfoa-theme/style.css:44-48` keeps the 50%/33.333% widths inclusive of the added padding. `.first` clears the preceding floated row, and `.entry-content::after`/`.gca-columns::after` contains floats.
- `pfoa-theme/style.css:1602-1607` removes floats, sets full width, and removes column padding at 720px and below. This is a safe readable collapse for the observed FAQ and adoption-story markup.
- Runtime must still check both active-plugin and absent-plugin states. Static repository review cannot establish whether a separately enqueued legacy stylesheet leaves margins or other declarations on these classes.

### O-3 — Images, PDFs, galleries, links, and focus behavior otherwise pass static review

- Images and video are capped without fixed heights at `pfoa-theme/style.css:567-576`; WordPress captions and alignments remain present at `541-617`, with aligned floats removed on narrow screens at `1609-1614`.
- The classic gallery structure is preserved and laid out by its existing classes at `pfoa-theme/style.css:626-680`. Six-column legacy output becomes two columns by 720px (`1616-1628`) and one column by 480px (`1664-1675`). These rules do not select `.wp-block-gallery` merely because its class name contains the word “gallery.”
- PDF `embed`/`object` elements with the confirmed MIME type are capped and given a bounded reading height at `pfoa-theme/style.css:783-786`. Ordinary PDF anchors and authored targets are untouched.
- No rule hides ThemeBlvd lightbox anchors, Popup Maker triggers, shortcode-like text, headings, or captions. With old lightbox JavaScript absent, the anchor destination remains the normal fallback.
- Task 6A does not override the global `:focus-visible` rule at `pfoa-theme/style.css:103-106` and adds no focusable widget. Stored-content alt text and heading order remain editorial/runtime concerns; the theme does not strip accessibility attributes.

### O-4 — Clean-room and scope compliance pass

- The Task 6A commit changes only the two Page templates, their shared template part, CSS, and its implementation note. It adds no JavaScript, package, asset, include, shortcode, content query, content rewrite, custom post type, popup/lightbox/PDF runtime, or payment configuration.
- Product-source scans found no ThemeBlvd, Alyeska, CMSMasters, Elementor, WPBakery, Revolution Slider, staging hostname, or production hostname reference. “Genesis Columns Advanced” appears only in the explanatory CSS comment; no Genesis source or framework behavior is imported.
- Existing `popmake-*`, `pum-*`, legacy lightbox, gallery, responsive-image, iframe, and shortcode markup can pass through `the_content()` unchanged. Whether the responsible plugins remain active and interoperable is correctly deferred to staging.

## 6. Validation results

- Confirmed the starting working tree was clean and `HEAD` was `c4c500e40b52204a56ee3d5775303fffd0ad921b` on `main`, matching `origin/main`.
- Reviewed the complete Task 6A commit against its parent. The product change is limited to `pfoa-theme/page.php`, `pfoa-theme/template_builder.php`, `pfoa-theme/template-parts/content-page.php`, and `pfoa-theme/style.css`.
- Ran `php -l` under PHP 8.5.10 across all 25 theme PHP files: all passed.
- Ran `git diff --check c4c500e^ c4c500e`: passed.
- Parsed `pfoa-theme/theme.json` with PHP's JSON parser: passed.
- Ran `node --check pfoa-theme/assets/js/navigation.js`: passed.
- Ran `bash -n scripts/build-zip.sh`: passed without building or modifying the distribution archive.
- Scanned theme PHP/CSS/JS and the Task 6A added lines for environment hostnames, prohibited builders/frameworks, new script hooks, content parsing/rewriting, custom content types, and plugin implementation. No Task 6A scope violation was found.

This audit created only `docs/sol-task6a-page-compatibility-audit.md`. It did not modify theme product files, the distribution archive, staging, or production, and it did not commit or push.

## 7. Required runtime/staging checks

After M-1 is fixed and before activation, run the Page-focused portions of the compatibility matrix on a staging copy:

1. Load all 40 published Page paths at desktop and approximately 390px. Use `/missionandobjectives/` as the default `page.php` control and representative legacy-assigned Pages as `template_builder.php` controls. Confirm one H1, primary content, header/footer, no fatal output, and no unreachable horizontal content.
2. On `/eventscalendar/`, confirm the fixed 730px table keeps every cell in source order, does not force document-wide scrolling, and exposes any internal horizontal scroll to keyboard, touch, and pointer users. Check a modern core Table block separately after m-1's scoping remediation.
3. On `/pettidings/` and `/faqs/`, inspect actual inline `width`, `min-width`, and any `!important` declarations at 390px. Confirm nothing is masked by `body { overflow-x: hidden; }`; record content-authored outliers rather than broadening the bridge indiscriminately.
4. On `/faqs/` and `/adoptionstories/`, verify half/third column rows and clearing above 720px, one-column order at and below 720px, and both Genesis-plugin-styles-present and plugin-styles-absent cases.
5. On `/wishlist/` and `/thank-you/`, verify aligned images stay within the column and stack without lost surrounding text. On `/adopted2026/`, verify six-column gallery images, captions, alt attributes, link targets, and one-column 390px flow.
6. On `/fromthehomefront-2/`, verify each YouTube destination, playback surface, width, and decided aspect ratio without clipping or page overflow. Check any non-video iframe, `embed`, or `object` discovered in the 40-Page set before retaining a generic ratio rule.
7. On `/pettidings/` and `/membership/`, verify current and historical PDF links retain their authored targets and any actual inline PDF viewer remains readable and scrollable at mobile sizes.
8. On `/spayneuter/` and `/wishlist/`, test legacy lightbox-class anchors with the old runtime absent; each image must still open through its normal link.
9. Confirm representative animal, staff, adoption-story, fundraiser, and donation triggers remain present after `the_content()` filtering. Popup focus, stacking, close, and PayPal handoff behavior belong to the next interoperability task, but Task 6A must not have removed their initiating markup.
10. Repeat keyboard-only and 200% zoom checks for Page links, forms, table overflow, embedded media, visible focus, and stored heading order. Do not infer dialog accessibility from this static audit.

## 8. Build-upon decision

The shared Page architecture, narrow column fallback, native WordPress content pipeline, and clean-room boundary are sound. Task 6A is **not yet safe to build upon** because M-1 can clip exactly the legacy fixed/minimum-width content the task promises to contain. Remediate that cascade defect and rerun the focused checks above; the Minor selector-scope items can be corrected in the same bounded CSS pass without changing templates or introducing a framework.

**Remediate before proceeding**
