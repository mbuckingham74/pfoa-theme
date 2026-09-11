# PFOA Task 7B — First Staging Installation and Rendered UAT

**Run date:** 2026-09-10
**Staging:** https://pfoa-legacy-stage.forkstech.com/
**Production:** https://safehavenpfoa.org/ (not accessed or modified)
**Source commit:** `c033930514315a92aa00203eb2ec4c89a2c96336`

The validated 0.1.1 candidate was installed and activated on staging. This
closeout records the rendered verification of the mobile-navigation
remediation and the related local release gates.

## Scope and artifact

The validated release candidate was installed and activated on staging. No
product code, legacy content, plugin configuration, or production state was
changed during UAT.

## Follow-up 0.1.1 mobile-navigation remediation

The deployed-build verification for the prior corrected build proved that
caching or a failed deployment was not the cause: the served `navigation.js`
matched the local corrected file and ZIP member byte-for-byte, and the served
`style.css` matched the local theme CSS. That corrected ZIP had SHA-256
`43bd51bd2f65bdb0432da4f6c6629fde47545ccc6813d96f52a2d27fb9d445f5`.

The exact rendered failure at approximately 390px was: the compact main menu
opened; 34 menu links were present; `.submenu-toggle` count was 0; five parent
links had authored `href="#"` values; all child lists, including the three-level
Fundraising branch, were immediately visible; and the existing main-menu
Escape/focus and 1319/1320 breakpoint behavior worked.

The confirmed root cause was the custom walker gating button output on the
mutable `$args->has_children` value received by `start_el()`. In the staging
runtime that value was not populated reliably even though WordPress had
already supplied the authoritative child relationship map to
`display_element()`. The remediation records child ownership from that map,
keyed by the walker’s configured ID field, and keeps the authored parent link
while adding one independently controlled button and matching submenu ID for
each rendered parent at every depth.

The 0.1.1 remediation changes PHP menu rendering only; `navigation.js` and CSS
required no changes. A focused local hierarchy fixture passed for three
disclosure buttons, three unique matching submenu IDs, and no buttons on leaf
items.

The validated candidate was manually installed and activated on staging. At
approximately 390px, the fresh compact menu rendered five `.submenu-toggle`
controls for Rescue Animals, Other Services, How To Help, nested Fundraising,
and About Us across 34 menu links. Every control had one matching submenu ID;
the IDs were unique, leaf items had no disclosure buttons, and all five child
submenus were collapsed initially. The authored parent anchors remained
unchanged, including the existing `href="#"` values.

Rescue Animals, Other Services, How To Help, and About Us each expanded and
collapsed independently. How To Help → Fundraising exposed its own control and
three descendants; closing Fundraising left the How To Help branch open and did
not affect sibling branches. The compact menu opened from the main button;
disclosures responded to Enter and Space; visible focus remained on the active
control; Escape closed nested and main mobile states with focus restoration.
At 1319px the compact navigation was active, while at 1320px the desktop
navigation was visible. Crossing the breakpoint left no focused element inside
a hidden region. On desktop, the primary navigation and Rescue Animals dropdown
remained usable, and Escape collapsed the dropdown and restored focus to its
disclosure control.

| Follow-up artifact | Result |
|---|---|
| Candidate ZIP | `dist/pfoa-theme-0.1.1.zip` |
| Candidate SHA-256 | `e6e9433ce71c1c5e741bab19a7858abd162ffa5e9c4f6bf3ded81f9aa6ae491d` |
| Confirmed deployed theme | `PFOA 0.1.1` active on staging |
| Staging installation/rendered verification | **PASS** |

| Item | Result |
|---|---|
| ZIP | `dist/pfoa-theme-0.1.0.zip` |
| SHA-256 | `ddb36fafc02acbc3e73aae52dac973559ce691c9a7ded5476218e5d23cb75e7b` |
| Repository gate | PASS |
| ZIP integrity, source parity, required/forbidden members | PASS |
| ZIP reproducibility | PASS |

The artifact uploaded to staging was byte-identical to the validated ZIP.

## Pre-activation staging state

- Staging was reachable before the change.
- Active theme was the legacy `Peninsula Friends of Animals` / Alyeska
  (`wp-theme-alyeska`, Alyeska v3.0.1).
- WordPress reported version 7.1.
- Popup Maker was present and serving the existing Popup Maker dialogs; the
  public Popup Maker assets reported version 1.24.0.
- Other relevant legacy integrations observed included PayPal Donations,
  Gallery Lightbox, Genesis Columns Advanced, WP Video Lightbox, Soliloquy,
  Lazy Load for Videos, UpdraftPlus, Wordfence, SeedProd, and WP Super Cache.
- No plugin updates or maintenance actions were performed.

## Installation and activation

The validated `pfoa-theme-0.1.1.zip` was manually installed on staging and
activated. The new `pfoa-theme` stylesheet was present on the front end,
advertised version `0.1.1`, WordPress admin remained reachable, and no PHP
fatal or critical error was observed. The theme remains active on staging at
task completion.

The homepage now has the new theme hero and section system at the top. The
legacy homepage editorial block remains below it, which explains the apparent
“second hero”; the old Alyeska theme shell is not active.

## Compatibility smoke tests

The 40 published paths from the compatibility baseline were exercised at wide
desktop and approximately 390px mobile width. Every path rendered a primary
heading, loaded the new theme, and produced no fatal-error state.

| ID | Result | Evidence / note |
|---|---|---|
| CB-01 | PASS | All 40 inventoried paths rendered on desktop and mobile; no fatal state. |
| NAV-01 | PASS | At 1440px, the eight assigned top-level items and 34 menu links were present; the three-level Fundraising branch was retained and fit without overflow. |
| NAV-02 | PASS | The 0.1.1 build rendered five `.submenu-toggle` controls at 390px, one for each actual parent-with-children including nested Fundraising. Five unique `aria-controls` values matched five rendered submenus; leaf items had no buttons; fresh child lists were collapsed; representative parent, nested, keyboard, breakpoint, and desktop checks passed. |
| PAGE-01 | PASS WITH OBSERVATION | `/missionandobjectives/` rendered its heading, content, landmarks, and footer. Mobile document overflow was observed from the legacy Popup Maker close control `#pbCloseBtn`, not from the page content. |
| POP-01 | FAIL — Major | `/adoptablecats2/` opened the correct Rhodes detail and gallery and was readable at 390px. The close button worked, but Escape did not close the dialog and focus did not return to the trigger. |
| POP-02 | PASS WITH OBSERVATION | `/staff-2/` opened the correct Danette Grady biography and the close control was usable at mobile width. The dialog had no explicit `role`/`aria-modal` and focus remained on Popup Maker content while open. |
| POP-03 | FAIL — Major | `/adoptionstories/` opened the correct Loki story and was readable. Escape did not close it; the dialog had no explicit `role`/`aria-modal`; literal `[more]` text remained in the story content. |
| DON-01 | PASS WITH OBSERVATION | The established staging value was saved back to Popup Maker Donate record 5367. The rendered `/membership/` popup remained reachable and its no-payment PayPal handoff opened the PFOA donation page; the rendered hidden input still did not expose a `value` attribute. |
| MEDIA-01 | PASS WITH OBSERVATION | `/fromthehomefront-2/` retained the authored YouTube destination and the loaded 300px player fit the 390px content column. The iframe has an empty title, and the page’s separate 410px overflow comes from `#pbCloseBtn`. |
| DOC-01 | FAIL — Content cleanup | `/pettidings/` retained 70 ordinary PDF links, including historical links and authored targets. The current Fall/Winter 2025 PDF remains literal `[button]...[/button]` content rather than a normal rendered PDF link. |
| LEG-01 | PASS WITH OBSERVATION | `/eventscalendar/` retained the event table and it fit the 348px content column without document-wide horizontal scrolling. The table has no `th` header cells. |
| LEG-02 | FAIL — Content cleanup | FAQ Genesis columns and images fit at mobile width. `/wishlist/` exposes three literal `[lightbox]` shortcodes instead of rendered images or normal-link fallbacks. |
| LEG-03 | PASS WITH OBSERVATION | `/adopted2026/` retained the gallery and mobile image sizing without document overflow. The sampled gallery included two images with empty alt text. |
| LBOX-01 | FAIL — Minor / Content cleanup | `/spayneuter/` exposes the authored `[lightbox]` shortcode for `GettinFixed.jpg`; no normal image-link fallback is rendered. |
| SEARCH-01 | PASS | `/?s=Rhodes` returned query context, Page-oriented summaries, and usable result links. A no-results query returned a readable “Nothing found” state with recovery content. |
| ERR-01 | PASS WITH OBSERVATION | `/compatibility-baseline-invalid-20260909/` rendered the WordPress “Page not found” state with navigation, a homepage recovery link, and no fatal error. The browser harness did not expose the raw HTTP status code separately. |

## Sol Task 7A rendered verification items

| Item | Result | Evidence / note |
|---|---|---|
| 1. Real menu widths and input modes | PASS WITH OBSERVATION | Tested the actual assigned menu at 390, 1319, 1320, and 1440px. The 1319/1320 transition is observable, the desktop menu fits, the compact menu opens in flow, and the 0.1.1 build supplies five independent mobile disclosures including nested Fundraising. The desktop menu toggle remains visibly present alongside the full menu. |
| 2. Breakpoint focus and zoom | PARTIAL | Breakpoint transition, admin-bar offset, responsive hero sizing, and mobile overflow were checked. A complete browser-zoom/text-enlargement focus matrix through 200% was not independently instrumented in this run. |
| 3. No-JavaScript navigation | PASS — limited | With script execution disabled for a reload in the browser harness, the assigned menu remained visible and its links remained present. This was a targeted fail-open check, not a full keyboard-only no-JS audit. |
| 4. Actual homepage content | PASS WITH OBSERVATION | Tested 390, 720, 1024, 1319, 1320, and 1440px. The hero, Adopt and Volunteer CTAs, four Ways to Help cards, adoption, news/events, promotions, and partner section all rendered without horizontal overflow. The hero currently uses the intended gradient fallback because no current hero image is configured; the partner section is an approved empty-state message. |
| 5. Legacy Page inventory | FAIL — mixed | Real Genesis columns, galleries, tables, images, forms, and long content remained usable in representative Pages. Raw legacy shortcodes and the hidden Popup Maker close control create the content-cleanup/integration findings listed below. |
| 6. Embeds and PDFs | FAIL — mixed | YouTube embeds retained their destinations and fit when loaded. Iframe titles are empty, and the current Pet Tidings PDF is still raw button-shortcode content rather than a normal link. |
| 7. Popup Maker dialogs | FAIL — Major | Rhodes, Danette, Loki, and Donate were inspected at mobile width. Rhodes and Loki did not close on Escape or return focus; dialog semantics were not explicit. The expected Potholders trigger/popup (`.popmake-frpotholders` / `#popmake-4535`) was not present on `/potholders-2/`, so that representative was not testable without changing content/configuration. |
| 8. Donation handoff | PASS WITH OBSERVATION | The existing Donate popup and PayPal action were reachable. A no-payment click opened PayPal’s “Donate to Peninsula Friends of Animals” page with amount/designation controls. The rendered hidden input’s missing `value` attribute remains recorded as an observation. |
| 9. Stored accessibility data | FAIL — targeted findings | H1s were present across the 40-page smoke run. Targeted checks found empty iframe titles, missing alt text on sampled legacy images, a table without `th` cells, and surviving raw shortcode-like output. This is not a full WCAG certification. |
| 10. Computed visual contrast | PARTIAL | The real fallback hero gradients, responsive bounds, and representative focus/dialog surfaces were inspected at standard widths. A full forced-colors and 200% contrast matrix was not completed. |

## Responsive and browser coverage

- Desktop widths: 1440px, plus breakpoint checks at 1319px and 1320px.
- Additional homepage widths: 1024px, 720px, and 390px.
- Mobile smoke width: approximately 390px across all 40 published paths.
- Logged-in staging browser profile was used for activation and primary UAT; a
  logged-out staging tab was used to confirm the `/fromthehomefront-2/` overflow
  was not solely caused by the WordPress admin bar.
- No production or `test.safehavenpfoa.org` browser navigation was performed.

## Task-specific PayPal follow-up

- The new theme’s Donate destination is configured under **Appearance →
  Customize → PFOA Header → Donate destination**. It remains blank on staging,
  so the documented internal fallback resolves the header, homepage pathway,
  homepage promo, and footer Donate CTAs to the published staging
  `/membership/` Page rather than to the homepage.
- The PayPal `hosted_button_id` is owned by the existing Popup Maker donation
  content, not by the theme or the PayPal Donations settings page. The
  established staging value already present in the Donate popup and the
  secondary Donate Version 1 popup was saved back to Popup Maker record 5367.
  No other popup fields were edited.
- Opening the popup for verification naturally incremented Popup Maker’s
  existing analytics open-count telemetry; no analytics reset or unrelated
  popup/site setting was changed.
- In a logged-out staging browser, `/membership/` opened the existing Donate
  popup. Its form retained `action="https://www.paypal.com/donate"`,
  `method="post"`, and `target="_top"`. Clicking the PayPal image once
  navigated to PayPal’s PFOA donation page with amount and designation
  controls visible. No payment or donation was submitted.
- The live hidden input still did not expose a `value` attribute after the
  save, although the handoff resolved to the correct PFOA donation page. This
  is retained as an observation rather than treated as a theme defect.
- **DON-01 disposition: PASS WITH OBSERVATION** — functional Donate/PayPal
  handoff verified; no homepage self-link, empty CTA destination, or
  theme-generated replacement payment identifier was observed.
- This follow-up changed only the staging Popup Maker 5367 content save. No
  theme product code, plugins, unrelated popup fields, or other site settings
  were changed.

## Defects by severity

### Blocker (historical UAT finding; follow-up completed)

1. **DON-01 — staging PayPal configuration was incomplete at initial UAT.**
   The established staging value was saved back to Popup Maker record 5367 and
   the no-payment rendered handoff was verified. See the follow-up disposition
   above; the hidden input’s missing rendered `value` attribute remains an
   observation for any later integration pass.

### Resolved in 0.1.1

1. **NAV-02 — PASS.** The 0.1.1 PHP walker remediation restored independent
   mobile disclosure controls for every rendered parent-with-children,
   including the three-level Fundraising branch. Rendered staging verification
   and the focused local hierarchy fixture both passed.

### Major

1. **POP-01 / POP-03 — Popup Maker keyboard behavior is incomplete.** Rhodes
   and Loki dialogs are readable and close by their visible button, but Escape
   does not close them and focus does not return to the triggering control.
   Popup semantics are also not explicit in the rendered markup.

These popup findings appear tied to the existing Popup Maker runtime/configuration
and were not changed during this task.

### Minor

1. **Legacy Popup Maker close control overflow.** On a 390px viewport,
   `#pbCloseBtn` extended to approximately x=440. This produced document widths
   of 410px on `/fromthehomefront-2/`, 434px on `/missionandobjectives/`, and
   441px on `/news-announcements/`. The logged-out Home Front check still showed
   410px, so this is not only an admin-bar artifact.
2. **Legacy iframe accessibility.** The representative YouTube iframes have
   empty `title` attributes.

### Content cleanup / migration follow-up

1. Literal `[lightbox]` output is visible on `/spayneuter/` and `/wishlist/`.
2. Literal `[button]` output is visible on `/fromthehomefront-2/` and
   `/pettidings/`, including the current Fall/Winter 2025 Pet Tidings link.
3. Literal `[more]` output remains in the Loki adoption story.
4. Some sampled legacy gallery images have empty alt text, and the event table
   has no header cells.
5. The homepage intentionally preserves the existing editorial content below
   the new theme hero; this should be reviewed as a content architecture choice,
   not removed during theme UAT.
6. The expected Potholders popup trigger was not found on `/potholders-2/`.

## Recommended next action

The 0.1.1 mobile-navigation follow-up is complete with a rendered PASS. Do not
begin the separate planned 0.1.2 legacy homepage-content remediation in this
closeout. The PayPal configuration follow-up remains complete with the
functional PASS WITH OBSERVATION disposition above. Popup Maker keyboard/focus
behavior remains a separate finding. Handle the shortcode, alt-text,
iframe-title, table-header, and missing-Potholders-trigger items as controlled
legacy content cleanup.

## Completion state

- Staging activation: **successful**.
- PFOA theme active on staging: **yes**.
- Rollback performed: **no**.
- Staging product code changed during UAT: **no**; the local 0.1.1 PHP walker
  remediation is recorded in the closeout commit.
- Mobile-navigation remediation: **PASS**.
- PayPal follow-up: **staging Popup Maker record 5367 updated with the
  established value; rendered no-payment handoff functionally PASS WITH
  OBSERVATION**.
- UAT documentation: this file is updated for the 0.1.1 closeout and included
  in the remediation commit.
- Legacy homepage-content remediation: **not started; separate planned 0.1.2
  task**.
- Production `safehavenpfoa.org`: **not accessed or modified**.
- `test.safehavenpfoa.org`: **not accessed**.
