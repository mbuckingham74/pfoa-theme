# Task 5A Homepage Static Structure Architecture Audit

**Audit date:** September 9, 2026
**Scope:** Uncommitted Task 5A homepage structure relative to accepted baseline `e8105b8092e3a32d305f4bdd86d0b621a790a5ec`
**Contract documents:** `docs/pfoa-theme-reconnaissance.md`, `docs/pfoa-compatibility-baseline.md`, and `docs/pfoa-task-5a-homepage-structure-notes.md`
**Overall verdict:** **Safe to commit and build upon.** One Minor empty-state defect is worth correcting, but it is not foundational.
**Finding counts:** 0 Blockers, 0 Major, 1 Minor, 3 Observations.

## 1. Concise verdict

Task 5A is a sound, narrowly scoped foundation for later homepage work. The assigned static front Page remains the main-query object; its editor content still passes through `the_content()`; the new Page lookups use current published slugs rather than IDs or environment-specific origins; and no lookup or secondary loop changes global `$post`. The seven template parts correspond to the seven intended regions and do not introduce a content model, builder, plugin dependency, or unnecessary JavaScript.

The staging compatibility path is preserved. Existing homepage Page content is emitted once inside the established `page-content-compatibility` boundary, while the new CSS is scoped beneath `.front-page-content`. The implementation neither executes the legacy builder nor strips legacy editor markup or Popup Maker trigger classes.

One Minor issue exists in the new homepage use of the previously accepted donation helper: when no Donate URL is configured and the Membership Page is unavailable, the helper returns the homepage URL. The new “Donate” and “Support PFOA” links then point back to the page the visitor is already viewing. This should be made non-interactive or omitted in that state, but it does not make the architecture unsafe to commit or extend.

## 2. Blockers

None.

No activation failure, main-query corruption, loss of assigned Page content, environment-ID dependency, or prohibited content/plugin architecture was found.

## 3. Major findings

None.

The implementation does not require remediation before later hero, card, or content work begins.

## 4. Minor findings

### m-1 — An unavailable donation destination becomes a misleading homepage self-link

- **Files/lines:** `pfoa-theme/template-parts/homepage-pathways.php:24-25`; `pfoa-theme/template-parts/homepage-promos.php:24-29`; inherited behavior in `pfoa-theme/functions.php:272-294`.
- **Concrete failure mode:** Both new homepage donation links call `pfoa_get_donation_url()`. With no configured theme URL, that function looks up the Membership Page; if the lookup/permalink is unavailable, it returns `home_url( '/' )`. On `front-page.php`, that produces a valid-looking “Donate” or “Support PFOA” control whose destination is the current homepage. In addition, the older fallback lookup does not apply Task 5A's published-status guard, so an unpublished Membership Page can supply a public link that ordinary visitors cannot use.
- **Why it matters here:** Donate is one of the four primary pathways and is repeated in the promotional region. The audit brief specifically requires unavailable sources to avoid dead or misleading links. The current staging baseline has a published Membership Page, so this does not block activation there; it is an edge-state defect rather than a foundational failure.
- **Smallest remediation:** Give the homepage a way to distinguish a usable configured/published donation destination from the generic homepage fallback, and render a non-interactive status or omit the donation tile when no usable destination exists. Do not add payment identifiers or require Popup Maker. Any shared-helper change should preserve the already accepted header/footer behavior deliberately rather than changing it incidentally.

## 5. Observations

### O-1 — The static-front-Page loop and global post state are correct

- `pfoa-theme/front-page.php:19-34` uses the main loop and calls `the_post()` before loading any homepage part. Under the project's configured static-front-Page semantics, the assigned Page is therefore the active global post for the hero and editorial regions.
- `pfoa-theme/front-page.php:25-27` gates the editorial region on the assigned Page's content, and `pfoa-theme/template-parts/homepage-editorial.php:18-29` emits that same Page's ID/classes, calls `the_content()`, and retains `wp_link_pages()`.
- The homepage lookups use `get_page_by_path()` and pass returned `WP_Post` objects explicitly to `get_permalink()` and `get_the_title()`. There is no `WP_Query`, `query_posts()`, `setup_postdata()`, or secondary loop to restore. `pfoa_get_page_by_paths()` at `pfoa-theme/functions.php:307-317` does not assign to global `$post`.
- WordPress also selects `front-page.php` when a site is configured to show latest posts. That mode is outside this Task 5A contract; in that mode the current loop would repeat the seven fixed-ID regions for every post. The configured staging/static-Page mode does not have this behavior.

### O-2 — The slug bridge is limited, documented, and fail-safe apart from m-1

- `pfoa-theme/functions.php:297-317` contains one small helper that accepts ordered candidate slugs and returns only a published Page.
- The slugs used at `homepage-pathways.php:12-13`, `homepage-adoption.php:16`, `homepage-news-events.php:12-13`, `homepage-promos.php:12-15`, and `homepage-partners.php:15` match the compatibility inventory: `volunteering`, `adoptablecats2`, `news-announcements`, `fromthehomefront-2`, `eventscalendar`, `potholders-2`, `wishlist`, and `businesspartners`.
- No production/staging Page ID, hostname, title match, or content scrape is present. A renamed or unpublished Page is not silently replaced with an unrelated Page; its dependent link is suppressed or converted to an explicit non-interactive state. The sole ordered fallback—News/Announcements to Home Front—is documented and consistent with the reconnaissance mapping.
- The helper may perform one lookup per distinct candidate on a cold cache. The number is small and fixed, repeated paths benefit from WordPress's path/object caches, and there is no reason to introduce a query abstraction for this migration bridge.

### O-3 — Component, accessibility, compatibility, and scope boundaries are appropriate

- `pfoa-theme/front-page.php:23-33` gives each intended region one template part in the required document order. Each part owns a materially different source/fallback and later component boundary; the split does not create hidden state or duplicated query-loop management.
- `homepage-hero.php:19-40` supplies the single theme-owned H1 and uses core featured-image output. The remaining regions use visible H2 labels, with H3 headings for cards/teasers. Section `aria-labelledby` references are valid; there are no empty links/buttons, ARIA widgets, positive tab stops, or hover-only interactions.
- Missing editor content omits its region (`front-page.php:25-27`). Missing Adopt/Volunteer Pages create non-interactive pathway states (`homepage-pathways.php:42-60`). Missing adoption/news/events sources produce labelled, non-interactive states; optional Potholders/Wish List promos are omitted; and partners show no invented logos or destinations. These states are deliberately structural and can be replaced in later content tasks without changing the section API.
- `homepage-editorial.php:18-29` preserves the compatibility wrapper and normal content-filter pipeline. The Task 5A stylesheet is contained under `.front-page-content` from `pfoa-theme/style.css:1626-1983`; it does not add broad legacy selectors or target Popup Maker/ThemeBlvd output. Existing editor content remains subject to the accepted `.entry-content` and `.page-content-compatibility` rules.
- Static source inspection found no new custom post type, taxonomy, structured animal/news/event model, page builder, Popup Maker API call, ThemeBlvd API call, plugin requirement, animation system, or JavaScript. The only motion is a small CSS hover transition with reduced-motion handling at `style.css:1746-1756` and `1979-1983`.

## 6. Empty-state assessment by region

| Region | Missing-source behavior | Assessment |
|---|---|---|
| Hero | Missing featured image uses the existing brand background; Page title remains the H1. | Pass |
| Editor content | Region is omitted when the assigned Page has no stored content. | Pass for the current static Page contract |
| Primary pathways | Missing Adopt/Volunteer destinations become labelled, non-interactive cards. Donate has the self-link edge case in m-1. | Pass except m-1 |
| Adoption | Shows a labelled non-interactive readiness state and no animal data or dead link. | Pass; intentionally temporary |
| News/events | Each missing Page independently becomes a labelled non-interactive state. No posts/events are fabricated. | Pass |
| Promotions | Unavailable Potholders/Wish List Pages are omitted rather than rendered as empty links. Donate has the edge case in m-1. | Pass except m-1 |
| Partners | No logos or outbound organizations are invented; the known Business Partners Page is linked only when published. | Pass; intentionally temporary |

The visible readiness copy and neutral media surfaces are implementation scaffolding explicitly deferred by Task 5A. They are not treated as cosmetic findings. Before public launch, later content tasks should replace or omit internal-sounding readiness copy, but that does not require an architectural change now.

## 7. Validation performed

- Confirmed `HEAD` is the accepted baseline commit `e8105b8092e3a32d305f4bdd86d0b621a790a5ec` on `main`; Task 5A is uncommitted.
- Reviewed the complete Task 5A diff plus all seven untracked homepage template parts and the three contract documents.
- Ran `php -l` on all 25 theme PHP files: all passed.
- Ran `git diff --check`: passed with no whitespace errors.
- Scanned the Task 5A sources for secondary queries/post setup, post-type or taxonomy registration, plugin/framework calls, and added script markup: none found.
- Did not modify staging or production and did not run payment or plugin interactions.

Rendered WordPress QA remains a normal pre-launch gate, not an audit finding: verify the assigned Homepage at desktop and approximately 390 px, confirm the legacy editor table/content remains readable, test a configured Donate URL and the published Membership fallback, and rerun the compatibility smoke matrix after deployment to a test copy.

## 8. Recommendation

Task 5A is safe to commit and build upon. m-1 should be corrected in a small focused follow-up or alongside the next homepage content pass; it does not justify holding the static component architecture. No consolidation, content-model abstraction, plugin integration, or rewrite is warranted.

This audit added only this documentation file. No product file was modified, and no commit or push was performed.

**Proceed**
