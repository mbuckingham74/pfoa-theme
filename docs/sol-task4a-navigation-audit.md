# Task 4A Header and Navigation Audit

**Audit date:** September 9, 2026
**Scope:** Current uncommitted Task 3 and Task 4A working tree, with emphasis on the header and three-level responsive navigation
**Contract documents:** `docs/pfoa-theme-reconnaissance.md`, `docs/pfoa-compatibility-baseline.md`, and `docs/pfoa-task-4a-implementation-notes.md`
**Overall verdict:** **Do not proceed** until the Blocker and Major findings are corrected.
**Finding counts:** 1 Blocker, 3 Major, 1 Minor.

## 1. Executive assessment

The intended implementation is small, conventional, and generally well scoped. It uses a registered WordPress menu location and `wp_nav_menu()`, retains the core walker for ordinary item/link attributes, adds disclosure buttons as siblings of parent links, uses normal list/link/button semantics rather than an ARIA menubar, and does not hard-code PFOA's hierarchy. The mobile visibility model is also fundamentally appropriate: JavaScript controls the main navigation and submenu `hidden` attributes, CSS honors those attributes, and the `no-js` path leaves navigation and descendants visible while hiding inert disclosure controls. Donate is a normal link backed by a sanitized Customizer URL with a safe internal fallback. No framework, page builder, payment processing, or copied legacy/reference implementation was found.

The current tree is not safe to build on yet. A conclusive PHP quoting error in `PFOA_Navigation_Walker::start_lvl()` prevents `functions.php` from parsing, so the theme cannot activate or render. After that is fixed, the current desktop row cannot reliably contain PFOA's known eight-item menu within the header's fixed 1,200 px inner canvas, and the breakpoint logic can hide the element that currently owns keyboard focus. The header navigation also uses a focus-ring color that has insufficient contrast on every dark navigation surface. Escape handling has one smaller scope gap when the mobile menu is open and focus has moved to another header control.

The verdict is therefore **Do not proceed**. The problems are local and do not require an architectural rewrite, but the Blocker and three Major issues should be remediated and re-audited before the remainder of the visual theme is layered onto this shell.

## 2. Blockers

### B-1 — `functions.php` contains an unterminated string in the submenu walker

- **File/relevant line:** `pfoa-theme/functions.php:198-205`, especially `PFOA_Navigation_Walker::start_lvl()` line 204.
- **Concrete problem:** The final concatenated string begins with a double quote but ends with a single quote:

  ```php
  . ">\n';
  ```

  This is a PHP parse error, not merely malformed rendered HTML. The single-quoted middle fragment also uses `\"` where a literal closing double quote was intended.
- **Project-specific impact:** WordPress loads the theme's `functions.php` before rendering templates. The parser cannot complete this file, so theme activation/front-end loading will fail before any primary menu, fallback menu, Donate link, Page content, Popup Maker trigger, search result, or footer can render. This meets the Blocker definition directly.
- **Smallest remediation:** Correct only the string construction so all PHP quotes balance and the emitted `<ul>` has closed `id` and `class` attributes. One valid form is:

  ```php
  $output .= "\n{$indent}<ul id=\"" . esc_attr( $submenu_id ) . '" class="' . esc_attr( $class_names ) . "\">\n";
  ```

  Then run `php -l` on every theme PHP file before activation. No walker redesign is needed.

## 3. Major findings

### M-1 — The desktop header row cannot reliably contain the known PFOA menu

- **File/relevant lines:** `pfoa-theme/style.css:239-243`, `815-820`, `839-849`, `853-902`, `915-931`, `1031-1049`, and the desktop/mobile boundary at `1166`/`1359`.
- **Concrete problem:** The desktop inner header is capped at 1,200 px. At the 1,100 px desktop boundary it is only 1,020 px wide. The minimum branding column, Donate control, column gaps, and Donate margin consume at least 272 px, leaving at most about 748 px for navigation at the breakpoint and about 928 px even when the inner canvas reaches its 1,200 px cap. PFOA's known eight top-level labels are forced onto one line (`flex-wrap: nowrap` and `white-space: nowrap`), and four parent items add separate 32 px disclosure buttons. A static size calculation from the declared font, paddings, labels, and controls puts the menu itself near 970–990 px before a larger real logo is considered. The body then suppresses horizontal overflow at line 63 instead of exposing the collision.
- **Project-specific impact:** The actual baseline menu—not a hypothetical future menu—can overlap the logo or Donate action, or be visually clipped, across part or all of the declared desktop range. The risk is severe immediately above 1,100 px and remains plausible at the 1,440 px audit viewport because the inner header stops growing at 1,200 px. Obscured top-level destinations would make the global navigation unsuitable as a foundation.
- **Smallest remediation:** Treat the collapse point as a content-fit decision. Keep the disclosure layout until the known menu, real logo, and Donate action fit, and give the header a wider header-specific inner canvas and/or modestly reduce desktop control density. Do not rely on `overflow-x: hidden` to conceal a failed fit. Verify the final row with the assigned production-equivalent menu at 1,100 px and 1,440 px, with browser zoom and long labels.

### M-2 — Breakpoint changes can hide the currently focused navigation element

- **File/relevant lines:** `pfoa-theme/assets/js/navigation.js:88-114` and `211-223`; related hiding rules at `pfoa-theme/style.css:978-985`, `1206-1208`, and `1278-1280`.
- **Concrete problem:** On a desktop-to-mobile media-query change, `handleViewportChange()` calls `setMainMenuState( false, false )`, which hides the entire navigation without checking whether `document.activeElement` is inside it or moving focus to the newly visible menu toggle. On a mobile-to-desktop change, it calls `closeSubmenus()`; every parent becomes `is-dismissed`, so a focused descendant can become CSS-hidden even though the navigation itself is unhidden. Neither direction restores or normalizes focus.
- **Project-specific impact:** Resizing, device rotation, split-window use, and especially browser zoom can cross the 1,100 px breakpoint while a keyboard user is in the menu. The visible focus indicator then disappears and the user's next Tab can resume from an element whose visibility model has changed. This is a real responsive keyboard defect in the exact interaction the audit brief calls out.
- **Smallest remediation:** Before changing visibility, detect whether focus is in content that will be hidden. When collapsing to mobile, move focus to the visible main menu toggle before hiding the navigation. When expanding to desktop, either preserve/open the focused element's ancestor chain or move focus to its nearest still-visible controlling parent link/button before dismissing branches. Keep `hidden`, CSS state, and `aria-expanded` synchronized after that focus decision.

### M-3 — The focus indicator loses contrast on the header's navigation surfaces

- **File/relevant lines:** `pfoa-theme/style.css:25`, `103-106`, `811-813`, `927-940`, `969-975`, `1004-1013`, and `1233-1260`.
- **Concrete problem:** The global focus outline is `#005fcc`. Its contrast is approximately 1.13:1 against the brick surface (`#934244`), 1.61:1 against dark brick (`#713033`), and 2.77:1 against charcoal (`#211e1d`). Those values are below the 3:1 non-text contrast target. Desktop top-level focus also gains a strongly contrasting gold fill, but submenu links and the mobile rows generally change only between the two low-contrast brick tones while retaining the low-contrast blue ring.
- **Project-specific impact:** Keyboard and low-vision users can have difficulty locating focus in the first-, second-, and third-level disclosure navigation, especially on mobile where every row uses a dark surface. A visible, high-contrast focus state is a stated PFOA acceptance requirement.
- **Smallest remediation:** Add a header-scoped focus treatment that contrasts with all charcoal/brick/gold combinations, such as a tested two-color ring or surface-specific light/dark outline, while retaining the existing content-area focus treatment if desired. Verify links, submenu buttons, Donate, and the main toggle on each of their normal and focused backgrounds.

## 4. Minor findings

### m-1 — Escape does not close an open mobile menu when focus is outside the navigation element

- **File/relevant lines:** `pfoa-theme/assets/js/navigation.js:178-209`, especially line 206.
- **Concrete problem:** After checking for an active expanded submenu, the main-menu Escape branch requires `navigation.contains( document.activeElement )`. An open mobile menu therefore ignores Escape if focus has reached the menu toggle itself, Donate, or the branding link. This can occur through normal tabbing while the menu remains open.
- **Project-specific impact:** No destination becomes inaccessible because the toggle still closes the menu, but the reported Escape behavior is inconsistent and a keyboard user cannot rely on Escape throughout the expanded header.
- **Smallest remediation:** After the deepest open submenu gets first refusal, allow Escape to close the open mobile menu when focus is anywhere in the relevant header/menu interaction, including the main toggle. Retain the current one-level-at-a-time behavior so one Escape does not collapse both a nested submenu and the main menu.

## 5. Desktop navigation assessment

Subject to B-1 and M-1, the desktop interaction model is structurally sound:

- `header.php:40-49` uses `wp_nav_menu()` with the registered `primary` location, no hard-coded item hierarchy, no fixed depth, and a minimal explicit fallback.
- The custom walker is justified narrowly: it delegates ordinary item/link output to `Walker_Nav_Menu` and adds only a sibling disclosure button for items WordPress marks as having children.
- Parent anchors are not replaced, nested inside buttons, assigned `href="#"` by the theme, or intercepted with `preventDefault()`. Existing custom-menu `#` destinations remain WordPress-managed data; any parent with a real URL remains an ordinary navigable link.
- The source order is parent link, disclosure button, then child list, which supports three-level sequential keyboard traversal after the output string is fixed.
- Hover, `focus-within`, and explicit `.is-open` states all expose desktop descendants. The nested Fundraising branch receives the same link/button/list mechanics as its parent.
- Current and ancestor classes receive visible styling. The submenu stack is above the header within its stacking context (`z-index: 40` inside a header at `30`). No theme code raises the header to a level that is obviously intended to overtake plugin dialogs.

The CSS containment rule for deep flyouts (`style.css:1025-1029`) is positional rather than true collision detection, so the actual Fundraising branch and long labels still need browser testing near both viewport edges. No separate finding is assigned because M-1 must first establish a row that fits.

## 6. Mobile/disclosure assessment

The intended mobile model passes the main structural checks apart from M-2 and m-1:

- The main toggle is a native button with `aria-controls="site-navigation"` and synchronized `aria-expanded` state.
- With JavaScript active, `navigation.hidden` makes the collapsed main menu unavailable to keyboard and accessibility-tree navigation. Each closed submenu receives its own `hidden` attribute, and the CSS explicitly honors it.
- Opening restores normal tabbing without custom `tabindex` management. Every parent retains its separate anchor and disclosure button.
- Nested buttons operate independently, and the same walker/JavaScript path supports the known third level.
- Closing the main menu through its toggle or through Escape from within the navigation closes all submenu states. Escape from a nested descendant closes only the deepest active branch first and focuses its disclosure button.
- The open navigation is static and in normal document flow at 1,099 px and below; it does not require an off-screen flyout or overlay.

When an ancestor submenu is closed directly, descendant expanded state is retained and reappears when the ancestor is reopened. That behavior is not inherently inaccessible because focus remains on the visible ancestor control and the hidden ancestor removes descendants from interaction. It should nevertheless be included in browser/screen-reader verification so the retained state is confirmed as understandable rather than accidental.

## 7. Keyboard/focus/ARIA assessment

The semantic choices are appropriate. The primary `<nav>` has an accessible label; toggles are buttons; each disclosure exposes `aria-expanded`; `aria-controls` points to a deterministic menu-item/depth ID after B-1 is repaired; and there are no `role="menu"`, `role="menubar"`, `aria-haspopup`, or positive/custom navigation tab stops. WordPress's own anchor output remains responsible for link attributes and `aria-current` behavior.

The JavaScript's reverse-order search for an expanded ancestor means Escape from the third level closes the deepest branch, restores focus to that branch's button, and lets a later Escape close its parent. Mobile main-menu Escape from a focused menu descendant restores focus to the main toggle. These are good, conventional behaviors.

M-2, M-3, and m-1 are the material exceptions: focus can be hidden during responsive mode changes, the focus indicator is not reliably distinguishable on dark navigation surfaces, and the main Escape branch is narrower than the open header state.

## 8. JavaScript assessment

`pfoa-theme/assets/js/navigation.js` passed `node --check`. It is a scoped strict-mode IIFE, uses no framework or global export, guards its required DOM elements, obtains all submenu controls from the navigation landmark, and mutates only the intended body/header/navigation/menu state. It does not replace links, cancel link clicks, inject HTML, bind duplicate handlers, or alter WordPress/plugin content.

The main and submenu ARIA/hidden synchronization is internally consistent during ordinary same-breakpoint operation. Mobile collapsed elements are genuinely hidden; desktop removes the `hidden` attribute and lets hover/focus/disclosure CSS control presentation. The two substantive state defects are M-2's media-query transition and m-1's Escape scope. No independent syntax/runtime bug was found in the JavaScript.

## 9. Sticky/admin-bar assessment

The sticky implementation is restrained and can remain after runtime verification:

- Sticky positioning is enabled only at 1,100 px and wider; tablet/mobile remain in normal flow.
- The logged-in desktop offset is the standard 32 px used at widths where the WordPress admin bar has its desktop height.
- Main/footer IDs receive scroll margins greater than the 5.25 rem minimum header height, with an additional logged-in allowance.
- Sticky positioning preserves the header's space in flow, so there is no obvious layout jump from switching to `position: fixed`.
- A header stack of 30 is modest. The theme does not style Popup Maker's overlay/container or assign the header an extreme global stack value, so it does not statically appear designed to cover dialogs.

Actual admin-toolbar height/offset, same-page anchor landing, and Popup Maker overlay/close-control stacking must still be checked in WordPress. If those checks fail, the smallest safe response is to reduce or defer sticky behavior rather than add a complex scroll system.

## 10. Donate configuration assessment

Donate is structurally safe:

- `pfoa_customize_register()` stores only a URL, sanitizes it with `esc_url_raw`, and leaves provider/account configuration outside the theme.
- `header.php:53` escapes the URL again with `esc_url()` and renders an ordinary anchor. No click interception or theme-owned payment form is present.
- An unset value looks up the known `membership` Page and falls back to `home_url( '/' )` if no usable permalink is returned. Neither fallback constructs a malformed external URL or embeds payment credentials.
- A homepage fallback is imperfect as a donation journey but safe; it is not elevated to a finding.
- A case-insensitive source scan found no PayPal recipient, payment account ID, credential, client ID, or payment-processing code in `pfoa-theme/`.

Runtime verification must cover a configured approved URL, the published Membership fallback, the homepage fallback, and the authorized external handoff without completing a payment.

## 11. Responsive-risk assessment

- **1,440 px:** M-1 applies because the header inner row remains capped at 1,200 px. After remediation, verify the complete top-level row, About Us dropdown, Fundraising third level, Donate, the real logo, and both viewport edges.
- **1,024 px and 768 px:** The mobile/tablet mode is active. Static grid sizing gives branding the flexible column and Donate/toggle fixed control columns; the navigation occupies a full second row. No inherent horizontal flyout remains, but M-2 must be fixed and the full long menu must be scrolled/tabbed in a browser.
- **390 px:** The 20 px gutters leave roughly 350 px for the first header row. The 84 px minimum Donate link, 48 px toggle, and two 16 px gaps leave a flexible branding area of roughly 186 px, which is plausible for the capped logo. Verify the real logo and site-title fallback, text zoom, long translations, and absence of document-level horizontal overflow.
- **All modes:** `body { overflow-x: hidden; }` can conceal rather than diagnose header overflow. The final QA should temporarily inspect overflow rather than treating the lack of a horizontal scrollbar as proof of fit.

Header/navigation selectors are scoped to `.site-header`, `.main-navigation`, `.primary-menu`, or their named controls. They do not target ordinary Page lists or links. Conversely, the scoped `.entry-content` compatibility rules do not reach the navigation. No new CSS framework or layout dependency is present.

## 12. Runtime verification still required

The following are validation work, not additional findings:

1. After B-1 is fixed, run `php -l` on every theme PHP file using PHP 7.4 or newer. PHP is not installed on this audit host, and no runtime was installed.
2. Install/activate the corrected ZIP on a WordPress test copy; assign the existing menu to Primary Menu; confirm exact three-level markup, unique submenu IDs, normal WordPress classes/attributes, and the one-link no-menu fallback.
3. Exercise pointer, keyboard, touch-width, Shift+Tab, deepest-first Escape, outside-click, and repeated open/close flows at approximately 1,440, 1,100, 1,024, 768, and 390 px, plus 200% zoom.
4. Cross the breakpoint in both directions with focus on a top-level link, disclosure button, second-level link, and third-level link; confirm focus always remains visible after M-2 is fixed.
5. Disable JavaScript and confirm the primary menu and all three levels remain visible/reachable, disclosure controls remain hidden, and Donate remains available.
6. Test the custom logo and site-title fallback, including rendered alternative text/accessible name and high-zoom collision behavior.
7. Test logged-out and logged-in sticky behavior, admin-bar offset, same-page anchors, and the static-to-sticky transition.
8. Open representative Popup Maker animal, biography, story, fundraiser, and donation dialogs; confirm the header/dropdowns do not cover their overlay or close control and that existing `popmake-*` triggers still work.
9. Test configured Donate, published Membership fallback, homepage fallback, and the approved payment-provider handoff without submitting payment.
10. Re-run the baseline Page/search/404/legacy-content smoke tests and validate the package member list includes `assets/js/navigation.js`. The current script passed `bash -n`, `theme.json` parsed as JSON, and the navigation script passed `node --check`.

Static regression review found no Task 4A content rewrite, query replacement, shortcode/class stripping, footer-menu deregistration, template-loop change, or hostname/path coupling. `template_builder.php`, normal Page rendering, search summaries, Popup Maker trigger markup inside `the_content()`, existing loops, footer registration, packaging structure, and Task 3 tokens are not plausibly regressed by the header implementation itself. A clean-room scan found no CMSMasters, Pet Rescue, ThemeBlvd, Alyeska, Elementor, WPBakery, Revolution Slider, framework, or page-builder implementation/dependency in product source.

## 13. Recommendation

**Do not proceed.** Correct B-1 first because the current theme cannot parse. Then correct M-1, M-2, and M-3 before building additional visual components on the header shell. m-1 is small enough to fix in the same focused pass but does not independently block subsequent work. Re-run static checks, real PHP lint, rendered WordPress navigation tests, breakpoint/focus tests, and the targeted Popup Maker/Donate/admin-bar checks before changing the verdict.

This audit added only this documentation file. No product implementation file was modified.
