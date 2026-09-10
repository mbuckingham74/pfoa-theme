# Task 2 Foundation Architecture Audit

**Audit date:** September 9, 2026
**Scope:** Uncommitted Task 2 implementation in `pfoa-theme/`, `scripts/build-zip.sh`, and `.gitignore`
**Contract documents:** `docs/pfoa-theme-reconnaissance.md` and `docs/pfoa-compatibility-baseline.md`
**Overall verdict:** **Do not proceed** with significant visual implementation until the one Major search-result defect is corrected. There are no activation, Page-compatibility, clean-room, or portability Blockers.

## 1. Executive assessment

The implementation is a sound, deliberately simple classic/hybrid WordPress foundation in most respects. It has the required classic-theme files, a validly structured `theme.json`, a small setup/enqueue layer, conventional loops, core header/footer hooks, registered primary and footer menu locations, core pagination, and no framework or page-builder architecture. The markup provides stable shell, content, article, and compatibility classes that can accept the planned visual work without a foundational rewrite.

The highest-risk compatibility requirement is handled correctly. The root-level filename is exactly `template_builder.php`; its template header makes it selectable for Pages; it uses the main Page loop and `the_content()`; and it does not rewrite editor HTML, remove classes, reproduce ThemeBlvd behavior, or call proprietary APIs. Existing Popup Maker trigger classes in Page content will therefore reach the normal content-filter pipeline unchanged.

One concrete defect should be fixed before the visual build: searches select template parts by post type, so the site's Page-based search results are rendered with the full singular Page part instead of as linked summaries. This directly conflicts with the compatibility baseline's search smoke test. Packaging currently produces a clean and correctly nested archive, but its broad copy rule does not enforce all promised exclusions and its output is not byte-for-byte reproducible across builds made at different times.

**Finding counts:** 0 Blockers, 1 Major, 2 Minor.

## 2. Blockers

None.

Static review found no issue likely to prevent installation or activation, break the 39 assigned Pages, violate the clean-room boundary, or require a foundational rewrite. Real PHP lint and staging activation remain required validation steps, as described below; their absence is not itself a finding.

## 3. Major findings

### M-1 — Search results render full, non-linked Page content

- **Files/lines:** `pfoa-theme/search.php:30-36`, especially line 35; `pfoa-theme/template-parts/content-page.php:12-20`, especially lines 14 and 19.
- **What is wrong:** `search.php` requests `template-parts/content-{post_type}.php`. For a Page result, WordPress therefore loads `content-page.php`, which is a singular Page renderer: it emits an unlinked `h1` title and the entire filtered Page body. It does not provide a result permalink.
- **Why it matters here:** Staging has zero published Posts and its searchable public content is Page-based. A normal query such as the baseline's `?s=Rhodes` will therefore expand complete legacy Pages—including large tables, galleries, PDF archives, and Popup Maker trigger markup—rather than present navigable results. This makes the primary real-world search path unwieldy and can leave a result with no clear route to its canonical Page. It fails the intent of smoke test `SEARCH-01`, which the compatibility baseline classifies as Major.
- **Smallest remediation:** Have `search.php` use the generic summary part for every result (for example, `get_template_part( 'template-parts/content' )`), or add and explicitly call a small `content-search.php` part with a linked heading and core excerpt. Do not use the singular `content-page.php` part in a results collection.

## 4. Minor findings

### m-1 — The packaging copy rule does not enforce the full exclusion contract

- **File/lines:** `scripts/build-zip.sh:58-63`.
- **What is wrong:** The script copies every regular file below `pfoa-theme/` except `.DS_Store` files and files beneath a `.git` directory. It would also copy a `.git` file or any future `.env`, database dump, backup, log, source map, local note, or other development artifact placed under the product directory.
- **Why it matters here:** The present source tree and the archive built during this audit are clean, so there is no current disclosure. However, the release script itself does not guarantee the exclusions required by the Task 2 packaging contract as the theme gains assets and tooling.
- **Smallest remediation:** Keep the shell-based packager, but add a short explicit release exclusion policy (or a maintained file manifest) covering VCS metadata, environment/secret files, database/archive/backup files, logs, source maps, and local development metadata. Validate the finished member list before reporting success.

### m-2 — ZIP bytes vary between otherwise unchanged builds

- **File/lines:** `scripts/build-zip.sh:58-66`, especially the ordinary `cp` at line 62 and the unsorted `find`/`zip` flow.
- **What is wrong:** Staged copies receive build-time filesystem metadata, and input order/ZIP metadata are not normalized. In the audit test, two builds of unchanged source made three seconds apart had identical member lists but different SHA-256 hashes.
- **Why it matters here:** The archive contents and installation shape remain correct, so this does not block development or installation. It does mean the reported reproducible packaging process cannot produce a stable artifact checksum for the same source state.
- **Smallest remediation:** Preserve or normalize file modification times, feed a sorted member list to `zip`, and omit nonessential extra metadata (for example, with the platform-appropriate `zip` flags). A Node, Composer, or CI pipeline is not needed.

## 5. `template_builder.php` compatibility assessment

**Assessment: pass.**

- The filename is exactly `pfoa-theme/template_builder.php`, matching the stored database value.
- The `Template Name` and `Template Post Type: page` headers are conventional and make the file a recognized Page template.
- Lines 22-44 use `have_posts()`, `the_post()`, and a normal Page loop.
- Line 32 calls `the_content()` directly, retaining core content filters, shortcode processing, embed handling, gallery output, password protection, and plugin filters.
- Lines 19-46 provide predictable `.site-main`, `.content-shell`, `.page-content-compatibility`, `.page-content`, and `.entry-content` boundaries for later compatibility styling.
- The template preserves `post_class()`, the Page ID, the title, and paginated Page content.
- It contains no ThemeBlvd/Alyeska implementation and no CMSMasters, Elementor, WPBakery, Revolution Slider, or other proprietary/page-builder dependency.
- It does not parse, sanitize, rename, or strip classes from Page content. Nothing in the template should interfere with `popmake-*` or `pum-*` triggers; Popup Maker behavior will still depend on the plugin remaining active and must be exercised on staging.

WordPress gives `front-page.php` precedence on the site front page even when that Page has a custom template assignment. Accordingly, the assigned Homepage will use `front-page.php`, while the other assigned Pages can use `template_builder.php`. This is expected hierarchy behavior, not a defect: `front-page.php` also runs the normal loop and delegates to `content-page.php`, whose line 19 calls `the_content()`. The implementation therefore retains the Homepage content and filters. This hierarchy behavior was cross-checked against the [official WordPress classic front-page documentation](https://developer.wordpress.org/themes/classic-themes/functionality/custom-front-page-templates/).

The wrapper and CSS do not target Popup Maker's global dialog container. That is appropriate at this foundation stage; plugin dialog layout, stacking, and focus behavior still need the planned staging interoperability tests.

## 6. Packaging assessment

**Assessment: pass for current archive structure and integrity; minor remediation needed for guardrails and deterministic bytes.**

The audit ran the build script in an isolated temporary repository copy so the working repository and its product files were not changed. Results:

- The script failed safely in its explicit prerequisite checks by inspection and passed `bash -n`.
- It reads version `0.1.0` from `style.css` and overwrites the predictable path `dist/pfoa-theme-0.1.0.zip`.
- The archive contains exactly one top-level `pfoa-theme/` directory.
- Theme files—including `style.css`, `functions.php`, `index.php`, `theme.json`, templates, and template parts—land directly at the expected paths beneath that directory.
- Repository documentation, scripts, `.gitignore`, and other repository-level tooling are outside the copied source root and are absent from the archive.
- No secret, database, local-path, development-artifact, `.git`, or `.DS_Store` material exists in the current theme source or appeared in the tested archive.
- `unzip -t` reported no compressed-data errors.
- Two immediate builds produced the same checksum, but builds separated by three seconds produced different checksums from unchanged source, confirming finding m-2.
- `/dist/*.zip` and `.DS_Store` are ignored by `.gitignore` as intended.

Version naming is reasonable for the foundation. The simple shell packager remains appropriate; it only needs the narrow hardening described in the Minor findings.

## 7. Clean-room and portability assessment

**Assessment: pass.**

A case-insensitive scan of product source and packaging files found no meaningful evidence of CMSMasters, Pet Rescue, ThemeBlvd, Alyeska, Elementor, WPBakery/Visual Composer, or Revolution Slider implementation or dependency. There are no bundled images, fonts, icons, scripts, or third-party assets to raise provenance concerns. The implementation uses ordinary WordPress and generic semantic terminology only.

Product source contains no literal staging hostname, production hostname, or local filesystem path. Internal runtime destinations use core functions such as `home_url()`, `get_stylesheet_uri()`, and `get_template_directory()`. The schema and GPL metadata URLs are stable documentation/license references, not environment coupling. No payment identifiers or other operational configuration are duplicated in theme code.

The source/tooling separation is clean: installable files live in `pfoa-theme/`; contracts live in `docs/`; packaging lives in `scripts/`; build output is assigned to ignored `dist/`.

## 8. WordPress correctness and visual-readiness notes

Apart from M-1, the templates use conventional hierarchy roles and loop behavior. `wp_head()`, `wp_body_open()`, `wp_footer()`, `body_class()`, `post_class()`, `wp_link_pages()`, core thumbnail functions, search forms, archive descriptions, and core pagination are present in appropriate contexts. Dynamic output is escaped appropriately or emitted through conventional WordPress template tags and filters. No hard-coded environment values or plausible static-review fatal paths were found.

The architecture is appropriately small: one setup function, one enqueue function, standard PHP templates, `theme.json`, one stylesheet, no framework, no custom content model, no builder, no service container, no custom query layer, and no JavaScript dependency. That leaves the planned header, hero, pathway, editorial, event, promotional, partner, footer, responsive, and accessibility work free to evolve without undoing a premature component system.

The compatibility CSS is contained beneath `.entry-content` rather than using global legacy-class selectors. It gives basic containment to wide tables and media, fallback widths to Genesis Columns Advanced classes, standard aligned-image behavior, responsive YouTube treatment, and usable gallery/caption layout. It does not hide shortcode-like text, recreate a proprietary lightbox, or style Popup Maker globally. The dedicated `.page-content-compatibility` hook remains available for narrower overrides if later QA shows that a legacy rule should not apply to ordinary post content.

No finding is recorded for the deliberately simple navigation or unfinished visual/accessibility treatments: explicit disclosure controls, mobile-menu JavaScript, final tokens, and complete branded interaction states belong to the upcoming implementation tasks. The current semantic shell and full-depth `wp_nav_menu()` output do not force a rewrite of those features.

## 9. PHP validation status

Real PHP lint was **not run**. No `php` executable is installed on the audit host. The Docker CLI is present, but a bounded daemon check failed immediately because its configured Unix socket does not exist; no image was pulled and the development machine was not modified.

Static inspection found no visible PHP syntax error, undefined theme-owned function call, proprietary API dependency, or code path likely to fatal on the declared WordPress 6.4+/PHP 7.4+ baseline. This is not a substitute for the parser.

Before any staging installation, run `php -l` over every PHP file using an available PHP 7.4-or-newer runtime. Then install/activate the ZIP on a staging copy and execute the compatibility smoke matrix, especially the assigned Pages, Popup Maker flows, donation handoff, search, and 404 response.

Other completed checks:

- `jq empty pfoa-theme/theme.json`: pass.
- WordPress `theme.json` v2 remains supported on the declared baseline; the file's version and structure were cross-checked with the [official v2 reference](https://developer.wordpress.org/block-editor/reference-guides/theme-json-reference/theme-json-v2/).
- `bash -n scripts/build-zip.sh`: pass.
- `shellcheck`: one non-functional `SC1007` warning for the intentional empty `CDPATH` command environment at line 5; no packaging failure resulted.
- Static clean-room, hostname, absolute-path, and suspicious-artifact scans: pass for the current tree.
- Isolated ZIP build, member-layout check, and `unzip -t`: pass.

## 10. Recommendation

**Remediate first / Do not proceed.** Correct M-1 before beginning significant visual implementation, then proceed with the foundation. The change is small and local, but the defect affects the site's actual Page-based search model and a Major baseline smoke test. The two packaging findings can be corrected alongside that change or before the first distributable release; they do not require architectural changes.

After remediation, the appropriate verdict should be **Proceed** subject to real `php -l`, staging activation, menu assignment, and the documented compatibility smoke tests.
