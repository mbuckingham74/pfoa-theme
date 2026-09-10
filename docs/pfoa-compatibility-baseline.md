# PFOA Compatibility Baseline

**Status:** Build Sequence Task 1 — Establish the compatibility baseline
**Observation date:** September 9, 2026
**Product repository:** `mbuckingham74/pfoa-theme`
**Compatibility target:** [PFOA staging](https://pfoa-legacy-stage.forkstech.com/)
**Production reference:** [safehavenpfoa.org](https://safehavenpfoa.org/)

This is a read-only baseline of public staging behavior. It is intended to be
the regression reference for later theme work. No staging or production
content, settings, media, menus, plugins, themes, or database records were
changed.

## Method and scope

The inventory was collected from the public WordPress REST API and public HTML:

- `GET /wp-json/wp/v2/pages?per_page=100&orderby=id&order=asc` with public Page fields.
- `GET /wp-json/wp/v2/types` for the publicly exposed type list.
- Public HTML inspection of the homepage and representative Pages.
- Safe browser opening of the Rhodes, Danette Grady, Loki, and donation dialogs; the Rhodes dialog was also closed to verify the close path.
- No login, admin access, form submission, payment, file upload, or consequential external action.

The source contract is the exact copy in
[`docs/pfoa-theme-reconnaissance.md`](pfoa-theme-reconnaissance.md).

## 1. Published URL inventory

The staging REST API returned **40 published Pages**, matching the
reconnaissance expectation. All observed Pages have `parent: 0`; no child Page
relationships were reported by the API. The `Page title` column preserves the
REST-rendered title spelling, including legacy titles that omit spaces.

| Page ID | Page title | Slug | Public path | Template | Parent |
|---:|---|---|---|---|---|
| 19273 | A Truly Pawsome Event | `atrulypawsomeevent` | `/atrulypawsomeevent/` | `template_builder.php` | — |
| 20323 | Adoptable Cats | `adoptablecats2` | `/adoptablecats2/` | `template_builder.php` | — |
| 10838 | Adopted2020 | `adopted2020` | `/adopted2020/` | `template_builder.php` | — |
| 13582 | Adopted2021 | `adopted2021` | `/adopted2021/` | `template_builder.php` | — |
| 15980 | Adopted2022 | `adopted2022` | `/adopted2022/` | `template_builder.php` | — |
| 17569 | Adopted2023 | `adopted2023` | `/adopted2023/` | `template_builder.php` | — |
| 19043 | Adopted2024 | `adopted2024` | `/adopted2024/` | `template_builder.php` | — |
| 20846 | Adopted2025 | `adopted2025` | `/adopted2025/` | `template_builder.php` | — |
| 22514 | Adopted2026 | `adopted2026` | `/adopted2026/` | `template_builder.php` | — |
| 5656 | AdoptionStories | `adoptionstories` | `/adoptionstories/` | `template_builder.php` | — |
| 4264 | BoardOfDirectors | `boardofdirectors` | `/boardofdirectors/` | `template_builder.php` | — |
| 5998 | BusinessPartners | `businesspartners` | `/businesspartners/` | `template_builder.php` | — |
| 9199 | Catnip&Sip | `catnipsip-2` | `/catnipsip-2/` | `template_builder.php` | — |
| 5110 | ContactUs | `contactus` | `/contactus/` | `template_builder.php` | — |
| 7838 | Employment | `employment` | `/employment/` | `template_builder.php` | — |
| 11790 | EstateAuction | `estateauction` | `/estateauction/` | `template_builder.php` | — |
| 4657 | EventsCalendar | `eventscalendar` | `/eventscalendar/` | `template_builder.php` | — |
| 4419 | Facilities | `facilities` | `/facilities/` | `template_builder.php` | — |
| 4384 | FAQs | `faqs` | `/faqs/` | `template_builder.php` | — |
| 11290 | FromTheHomeFront | `fromthehomefront-2` | `/fromthehomefront-2/` | `template_builder.php` | — |
| 4511 | Fundraisers | `fundraisers` | `/fundraisers/` | `template_builder.php` | — |
| 4703 | Homepage | `homepage` | `/` | `template_builder.php` | — |
| 5421 | InMemoriam | `inmemoriam` | `/inmemoriam/` | `template_builder.php` | — |
| 5574 | LifetimeCare | `lifetimecare` | `/lifetimecare/` | `template_builder.php` | — |
| 5045 | Membership | `membership` | `/membership/` | `template_builder.php` | — |
| 5062 | MemorialWall | `memorialwall` | `/memorialwall/` | `template_builder.php` | — |
| 6738 | MissionAndObjectives | `missionandobjectives` | `/missionandobjectives/` | default | — |
| 4728 | News/Announcements | `news-announcements` | `/news-announcements/` | `template_builder.php` | — |
| 19667 | Our First 25 Years | `25years` | `/25years/` | `template_builder.php` | — |
| 6938 | PetEstatePlan | `petestateplan` | `/petestateplan/` | `template_builder.php` | — |
| 4799 | PetTidings | `pettidings` | `/pettidings/` | `template_builder.php` | — |
| 17894 | Potholders | `potholders-2` | `/potholders-2/` | `template_builder.php` | — |
| 4683 | SpayNeuter | `spayneuter` | `/spayneuter/` | `template_builder.php` | — |
| 4159 | SponsoredAnimals | `sponsoredanimals` | `/sponsoredanimals/` | `template_builder.php` | — |
| 4242 | SponsorshipFAQs | `sponsorshipfaqs` | `/sponsorshipfaqs/` | `template_builder.php` | — |
| 4968 | Staff | `staff-2` | `/staff-2/` | `template_builder.php` | — |
| 7898 | thank-you | `thank-you` | `/thank-you/` | `template_builder.php` | — |
| 4589 | Volunteering | `volunteering` | `/volunteering/` | `template_builder.php` | — |
| 5006 | Volunteers | `volunteers-2` | `/volunteers-2/` | `template_builder.php` | — |
| 5027 | WishList | `wishlist` | `/wishlist/` | `template_builder.php` | — |

Representative public responses returned HTTP 200 for the homepage,
`/missionandobjectives/`, `/contactus/`, `/fromthehomefront-2/`,
`/eventscalendar/`, and `/pettidings/`.

## 2. Menu hierarchy

The public `#primary-menu` hierarchy is below. Parent items with `#` are
custom non-destination menu items in the current site. The nested
`How To Help → Fundraising` branch is three levels deep and must remain
keyboard-, pointer-, and touch-operable.

```text
Home                                /
Home Front                          /fromthehomefront-2/
Rescue Animals                      #
  Adoptable Cats                    /adoptablecats2/
  Adopted2026                       /adopted2026/
  Sponsored Animals                 /sponsoredanimals/
  Adoption Stories                  /adoptionstories/
  In Memoriam                       /inmemoriam/
Other Services                      #
  Spay/Neuter Program               /spayneuter/
  Lifetime Care                     /lifetimecare/
  General FAQs                      /faqs/
How To Help                         #
  Fundraising                       #
    Catnip & Sip                    /catnipsip-2/
    Fundraisers                     /fundraisers/
    Events Calendar                 /eventscalendar/
  Membership                        /membership/
  Volunteer!                        /volunteering/
  Sponsor a Rescue Animal           /sponsorshipfaqs/
  Memorial Wall                     /memorialwall/
  Wish List                         /wishlist/
About Us                            #
  Our Board Of Directors            /boardofdirectors/
  Our Staff                         /staff-2/
  Our Volunteers                    /volunteers-2/
  Our Facilities                    /facilities/
  Our Mission and Objectives        /missionandobjectives/
  Our Business Partners             /businesspartners/
  Events Calendar                   /eventscalendar/
  Pet Tidings Newsletter            /pettidings/
  News/Announcements                /news-announcements/
Jobs                                /employment/
Contact Us                          /contactus/
```

`Events Calendar` intentionally appears in both the Fundraising and About Us
branches. The current public menu exposes only the top-level items in the
collapsed accessibility tree; the nested hierarchy was verified from the
rendered menu HTML.

## 3. Legacy-template baseline

The public Page REST responses report:

| Assignment | Count | Affected Pages |
|---|---:|---|
| `template_builder.php` | **39** | Every published Page in the URL inventory except `MissionAndObjectives` |
| Default template (`template` empty) | **1** | `MissionAndObjectives` (ID 6738, `/missionandobjectives/`) |
| Any other template | **0** | None observed |

This is an assignment inventory only. No Page template assignments were
changed. A later theme must provide an independently written
`template_builder.php` and render the normal loop and `the_content()`.

## 4. Plugin-dependent behavior inventory

The public type list exposes `popup`, `popup_theme`, and `pum_cta` in addition
to ordinary Pages, and public HTML exposes Popup Maker markup and `popmake-*`
trigger classes. These observations establish public compatibility behavior;
they are not a substitute for confirming the private admin plugin list.

| Case | Originating Page | Visible trigger/link text | Public selector or target | Observed/expected outcome | Consequential boundary |
|---|---|---|---|---|---|
| Animal detail | [`/adoptablecats2/`](https://pfoa-legacy-stage.forkstech.com/adoptablecats2/) | “Click on the kitty’s photo…”; Rhodes image | `img.popmake-acrhodes4047`; `#popmake-23487`; `.pum-content.popmake-content` | Public click opened a dialog headed **RHODES** with Domestic shorthair/male, brown tabby, medium, young, no special needs, “More information coming soon,” and a five-image gallery. The close button is `.popmake-close`. | No consequential action. Current close returned focus to `BODY`, not the original trigger; focus containment/return remains a regression requirement. |
| Staff biography | [`/staff-2/`](https://pfoa-legacy-stage.forkstech.com/staff-2/) | Image alt/visible biography card: “Danette Grady, Executive Director” | `img.popmake-smdanettegrady`; `#popmake-5279` | Public click opened a dialog headed **Danette Grady, Executive Director** with the biography beginning “Danette Grady is PFOA’s first Executive Director.” A close button was present. | No consequential action. |
| Adoption story | [`/adoptionstories/`](https://pfoa-legacy-stage.forkstech.com/adoptionstories/) | “Just another update on Loki… [more]” | `img.popmake-asloki`; `#popmake-16025` | Public click opened Loki’s adoption update, including the story text and “Thanks for saving Loki. Kathryn Cooper.” | No consequential action. The literal `[more]` text is also part of the rendered teaser and is recorded below as shortcode-like legacy output. |
| Fundraiser detail | [`/fundraisers/`](https://pfoa-legacy-stage.forkstech.com/fundraisers/) | “PURCHASE OUR POTHOLDERS!” | `.popmake-frpotholders`; `#popmake-4535` | Public HTML contains a Popup Maker detail with potholder availability, vendor list, pricing/contact guidance, and image links. Preserve the trigger even if the dialog presentation changes. | No purchase or external vendor action performed. |
| Legacy image lightbox | [`/spayneuter/`](https://pfoa-legacy-stage.forkstech.com/spayneuter/) | Image link for `GettinFixed.jpg` | `a.themeblvd-lightbox.mfp-image.tb-thumb-link.image.thumbnail`; `/wp-content/uploads/2018/02/GettinFixed.jpg` | Current HTML uses ThemeBlvd lightbox classes. With the legacy runtime present it is an image lightbox; if that runtime is absent, the compatibility expectation is a usable normal image link. | No purchase or form action. A new theme must not copy the ThemeBlvd runtime. |
| Donation handoff | [`/membership/`](https://pfoa-legacy-stage.forkstech.com/membership/) | “Donate via PayPal”; membership copy says clicking Donate begins PayPal | `img.popmake-donate.pum-trigger`; `#popmake-5367`; form action `https://www.paypal.com/donate` | Opening the dialog showed **SUPPORT PFOA!**, a PayPal image control, check/money-order instructions, PO Box 404, and Federal ID 91-2127240. Public source HTML shows a POST form targeting `_top`; another public donation dialog (`#popmake-14022`) exposes PayPal Giving and the three-option donation copy. | **No form submission and no payment.** Recipient configuration, designated purpose behavior, return URL, and thank-you flow cannot be safely confirmed from public read-only behavior and require authorized admin/sandbox QA. Do not duplicate payment identifiers in theme code. |
| YouTube/video embed | [`/fromthehomefront-2/`](https://pfoa-legacy-stage.forkstech.com/fromthehomefront-2/) | “WEDNESDAY’S KITTENS VIDEO” and other Home Front media | `iframe[src*="youtube.com/embed"]`; representative `https://www.youtube.com/embed/JCOibRk6kYE?rel=0` | Public Page contains YouTube iframes with legacy fixed dimensions (including 300×150 and smaller 180×90/180×95 variants). The new theme must preserve the embed and make it responsive. | Playback was not initiated; availability remains dependent on YouTube. |
| PDF link | [`/pettidings/`](https://pfoa-legacy-stage.forkstech.com/pettidings/) | “Click here for Pet Tidings Current Edition!” | `a.btn.blue.btn-shortcode[href*=".pdf"]`; current observed PDF path `/wp-content/uploads/2025/10/PT2025-2-Fall-Winter-Print-Version.pdf` | Public link targets a PDF in a new tab. The Page exposes 71 PDF links in the rendered content, including the historical archive. | No PDF download was required for this baseline. |
| Site search | [`/?s=Rhodes`](https://pfoa-legacy-stage.forkstech.com/?s=Rhodes) | “Search the site…” | `#search-trigger`; GET form with `s` query | The query returned HTTP 200 with title `Rhodes | Search Results | Peninsula Friends of Animals`. Preserve core GET search behavior and a usable no-results state later. | No data submitted beyond a public GET query. |
| Known-valid ordinary Page | [`/missionandobjectives/`](https://pfoa-legacy-stage.forkstech.com/missionandobjectives/) | Page content and heading | Page ID 6738; default template assignment | Returned HTTP 200 and is the only published Page observed without `template_builder.php`. It is the default-template control case. | No consequential action. |
| Deliberately invalid URL | `/compatibility-baseline-invalid-20260909/` | Browser error state | HTTP response 404; page title `Page not found | Peninsula Friends of Animals`; H1 `404 Error` | The new theme must retain a clear 404 response, heading, navigation, and recovery links. | No consequential action. |

## 5. Legacy-content patterns

These are representative regression fixtures, not an exhaustive content scan.

| Pattern | Representative Page | Observed public markup/behavior | Compatibility expectation |
|---|---|---|---|
| Fixed-width/layout tables | [`/eventscalendar/`](https://pfoa-legacy-stage.forkstech.com/eventscalendar/), `/` | Events includes `<table style="width: 730px" border="1" ...>`; homepage includes a fixed `1279px` table. | Keep data and cell order; contain true data tables with horizontal overflow where needed and neutralize layout tables carefully on mobile. |
| `.gca-column`, `.one-half`, `.first` | [`/faqs/`](https://pfoa-legacy-stage.forkstech.com/faqs/) | `<div class="gca-column one-half first">` and a sibling `.gca-column.one-half`. | Provide scoped fallback grid/clearfix styles if Genesis Columns Advanced CSS is unavailable. |
| `.one-third` | [`/adoptionstories/`](https://pfoa-legacy-stage.forkstech.com/adoptionstories/) | Repeated `.gca-column.one-third` cards, including a `.first` card. | Preserve readable one-, two-, and three-column behavior at the relevant widths. |
| Aligned WordPress images | [`/wishlist/`](https://pfoa-legacy-stage.forkstech.com/wishlist/), `/thank-you/` | Wishlist uses several `img.alignright` images; thank-you uses centered aligned rescue images. | Preserve `alignleft`, `alignright`, and `aligncenter` semantics without overflow. |
| Galleries and captions | [`/adopted2026/`](https://pfoa-legacy-stage.forkstech.com/adopted2026/) | `#gallery-9.gallery.galleryid-22514.gallery-columns-6.gallery-size-medium`, `dl.gallery-item`, `dt.gallery-icon`, `dd.wp-caption-text.gallery-caption`, responsive `srcset`. | Keep gallery/caption structure, alt text, responsive images, and usable single-column mobile layout. |
| Legacy lightbox classes | [`/spayneuter/`](https://pfoa-legacy-stage.forkstech.com/spayneuter/), `/wishlist/` | `themeblvd-lightbox`, `tb-lightbox-shortcode`, `mfp-image`, and related thumbnail classes. | Preserve normal image-link fallback if the old lightbox is removed; do not port proprietary framework code. |
| Embedded iframes/video | [`/fromthehomefront-2/`](https://pfoa-legacy-stage.forkstech.com/fromthehomefront-2/) | YouTube iframe embeds use explicit legacy width/height attributes and `allowfullscreen`. | Apply a responsive media wrapper without deleting the iframe or changing its destination. |
| PDFs | [`/pettidings/`](https://pfoa-legacy-stage.forkstech.com/pettidings/), `/membership/` | Pet Tidings has 71 PDF links; Membership includes `MembershipVolunteerApplication.pdf`. | Keep ordinary PDF links, indicate file type where appropriate, and retain new-tab behavior where authored. |
| Inline width/color/font styling | [`/pettidings/`](https://pfoa-legacy-stage.forkstech.com/pettidings/), `/faqs/` | Repeated inline `style` attributes set widths, colors, font sizes, alignment, table layout, and spacing. | Override only what is necessary for overflow, contrast, and responsive readability. |
| Visible shortcode-like output | [`/adoptionstories/`](https://pfoa-legacy-stage.forkstech.com/adoptionstories/) | Rendered adoption-story teasers visibly include literal `[more]` text. | Do not silently hide it. Confirm the responsible shortcode/content workflow before remediation. |

## 6. Smoke-test matrix

This matrix is the executable baseline for later theme tasks. “Desktop + mobile”
means the test must be run at both wide desktop and approximately 390px mobile
width; “Desktop” or “Mobile” narrows the primary concern.

| ID | Page/path | Feature | Expected behavior | Relevance | Severity |
|---|---|---|---|---|---|
| CB-01 | All 40 published paths | URL/content retention | Every inventoried URL returns successfully and renders its primary Page content without a fatal error. | Desktop + mobile | Blocker |
| NAV-01 | `/` | Primary navigation | Home, Home Front, all top-level branches, and the three-level Fundraising branch retain destinations and nesting. | Desktop | Major |
| NAV-02 | `/` | Mobile disclosure navigation | Menu opens in flow; nested parents expose independent disclosure controls; no horizontal overflow or inaccessible flyout is required. | Mobile | Major |
| PAGE-01 | `/missionandobjectives/` | Default-template ordinary Page | Default-template content, heading, landmarks, images, and footer render normally. | Desktop + mobile | Major |
| POP-01 | `/adoptablecats2/` | Adoptable animal Popup Maker trigger | Rhodes trigger opens the correct detail and gallery; dialog is readable at 390px; close works by keyboard; focus returns to the trigger. | Desktop + mobile | Major |
| POP-02 | `/staff-2/` | Staff biography popup | Danette Grady trigger opens the correct biography and close control remains usable. | Desktop + mobile | Major |
| POP-03 | `/adoptionstories/` | Adoption-story popup | Loki teaser trigger opens the correct story and does not expose broken shortcode/popup markup. | Desktop + mobile | Major |
| DON-01 | `/membership/` | PayPal donation handoff | Donate opens the configured dialog and reaches the authorized PayPal handoff without theme-owned or duplicated account configuration. | Desktop + mobile | Blocker |
| MEDIA-01 | `/fromthehomefront-2/` | YouTube embed | Representative iframe remains present, loads at its authored destination, and fits the content width without page overflow. | Desktop + mobile | Major |
| DOC-01 | `/pettidings/` | PDF archive | Current-edition and representative historical PDF links remain ordinary usable links and open in the authored target context. | Desktop + mobile | Major |
| LEG-01 | `/eventscalendar/` | Fixed-width event table | Event data remains readable; the page does not force document-wide horizontal scrolling. | Desktop + mobile | Major |
| LEG-02 | `/faqs/`, `/wishlist/` | Legacy columns and aligned images | `.gca-column`, `.one-half`, `.first`, aligned images, and inline styles remain readable when legacy CSS is absent. | Desktop + mobile | Major |
| LEG-03 | `/adopted2026/` | Gallery/caption output | Gallery images, captions, alt text, and responsive image sizes remain usable, including one-column mobile flow. | Desktop + mobile | Major |
| LBOX-01 | `/spayneuter/` | ThemeBlvd/lightbox image link | Legacy lightbox class does not break the link; with old runtime absent, the image still opens as a normal link. | Desktop + mobile | Minor |
| SEARCH-01 | `/?s=Rhodes` | WordPress/site search | Search query returns a valid results page with query context and usable navigation; no-results behavior is also readable. | Desktop + mobile | Major |
| ERR-01 | `/compatibility-baseline-invalid-20260909/` | 404 behavior | Response remains HTTP 404 with a clear H1, navigation, recovery path, and no fatal error. | Desktop + mobile | Major |

## 7. Baseline summary

- **Observed published Page count:** 40. This matches the reconnaissance document; no count discrepancy was observed.
- **Observed template assignments:** 39 `template_builder.php`; 1 default template (`MissionAndObjectives`, ID 6738); 0 other assignments.
- **Smoke-test cases:** 16.
- **Confirmed plugin-dependent behaviors:** Popup Maker animal detail (Rhodes), staff biography (Danette Grady), adoption story (Loki), fundraiser detail markup (Potholders), site-wide donation dialog and PayPal form metadata without submission, ThemeBlvd lightbox selectors, YouTube iframe embeds, PDF archive links, WordPress search, and 404 handling.
- **Confirmed legacy-content fixtures:** fixed-width tables, Genesis Columns Advanced classes (`.gca-column`, `.one-half`, `.one-third`, `.first`), aligned images, WordPress galleries/captions, ThemeBlvd lightbox classes, iframes, PDFs, inline presentation styles, and visible `[more]` output.
- **Unresolved unknowns:** private/admin plugin inventory; Popup Maker focus containment and focus return beyond the observed current `BODY` focus after close; authoritative animal/news/event data sources; exact menu-location reassignment procedure after theme activation; PayPal recipient/purpose/return/thank-you behavior under an authorized sandbox or admin test; current approved social/partner roster; and which obsolete external links should be removed.
- **Discrepancies from `pfoa-theme-reconnaissance.md`:** none observed. The 40-Page count, three-level menu branch, and 39/1 template split were confirmed.

The next task may begin with a theme skeleton, but this baseline must remain the
read-only reference and no staging content or configuration should be used as a
condition of implementation.
