# PFOA Custom WordPress Theme: Reconnaissance and Implementation Specification

**Status:** implementation-planning contract
**Observation date:** September 9, 2026
**Visual reference:** [Pet Rescue demo](https://petrescue.cmsmasters.studio/)
**Compatibility target:** [PFOA staging](https://pfoa-legacy-stage.forkstech.com/)
**Eventual public domain:** [safehavenpfoa.org](https://safehavenpfoa.org/)

This specification is based on visual and behavioral inspection of the public sites and the public WordPress REST responses from staging. It is a clean-room description of visible behavior. It does not authorize copying the reference theme's code, assets, fonts, icons, templates, shortcodes, or plugin implementation.

## 1. Executive Summary

The target should be a conventional PHP WordPress theme with `theme.json` support, not a page-builder theme and not a dependency on CMSMasters or its plugins. The closest useful interpretation of the reference is a warm, editorial animal-rescue site: a dark compact header, a large photographic hero, a brick-red action band, flat image-led cards, restrained gold accents, generous white space, and a substantial information footer.

Visual fidelity matters most in six places: the centered desktop canvas, the dark header and gold active/CTA treatment, the hero's scale and typography, the four action pathways, the adoption/news/event card rhythm, and the brick-red footer. Tiny decorative details are not worth reproducing.

PFOA's public content is not organized like the reference site's content. The staging REST API exposes 40 published Pages and zero published Posts. The current adoptable animals, staff biographies, board biographies, adoption stories, fundraiser details, and donation dialog depend materially on Popup Maker entries and `popmake-*` trigger classes embedded in Page content. Events and news are manually maintained Pages, not publicly exposed event or post collections. Most Pages are assigned to the old Alyeska/ThemeBlvd `template_builder.php` template, and several contain ThemeBlvd lightbox markup, Genesis Columns Advanced classes, fixed-width tables, inline presentation styles, YouTube embeds, and PDF links.

Therefore the first theme must prioritize compatibility over an idealized content model. It should render existing Page content through the normal loop, include an independently written `template_builder.php` compatibility template, preserve plugin trigger classes, and provide graceful styles for legacy tables, columns, images, embeds, and shortcodes. It must not parse legacy page HTML to manufacture dynamic animals, news, or events.

The first homepage should use PFOA-owned imagery and existing destinations. A static hero is acceptable for the first working theme; a carousel is optional later. The four main pathways should be Adopt, Foster/Volunteer, Donate, and Help/Volunteer, mapped to existing PFOA functions. Featured-animal, news, and event lists should become dynamic only after their authoritative data sources and editorial workflows are confirmed. Until then, use clearly labeled teasers linking to the existing Pages rather than duplicating or fabricating content.

## 2. Reference Design System

### 2.1 Canvas and content rhythm

- On wide screens the site is a centered canvas approximately 1,280 px wide. The outer viewport is visually subdued with a warm neutral or blurred photographic treatment; the content canvas itself carries the readable content.
- At tablet and mobile widths the canvas becomes full width and the outer backdrop disappears from practical view.
- The homepage alternates high-impact full-bleed modules inside the canvas with calm white sections. Sections are separated by whitespace, thin warm-gray rules, or a strong background-color change rather than by boxed cards everywhere.
- Recommended implementation values, chosen for practicality rather than asserted as source-site measurements:
  - `max-width`: 1,280 px for hero/header bands; 1,200 px for padded content.
  - Horizontal content padding: 40 px desktop, 24 px tablet, 20 px mobile.
  - Major vertical section spacing: 72–88 px desktop, 52–64 px tablet, 40–48 px mobile.
  - Component spacing scale: 8, 12, 16, 24, 32, 48, 64, 80 px.

### 2.2 Color language

The recurring visible palette is:

| Role | Approximate character | Implementation target |
|---|---|---|
| Header | near-black warm charcoal | `#211e1d` range |
| Primary brand band/footer | muted brick/oxblood | `#934244` to `#9d484a` range |
| CTA/active accent | warm mustard/gold | `#e9ad4e` to `#f0b754` range |
| Main background | white | `#ffffff` |
| Outer/background neutral | warm light gray-beige | `#ebe7dd` range |
| Primary text | warm charcoal | `#302d2b` range |
| Muted text/rules | gray/taupe | `#77716c` / `#e5e0da` range |

These are visual targets, not values to copy. Final tokens should be adjusted with PFOA's own branding and verified for WCAG contrast. Gold should usually be a background or accent; small gold text on white should be avoided.

### 2.3 Typography

- The reference pairs a sturdy, friendly slab-like serif for section titles and card names with a neutral sans serif for body copy.
- Hero statements use a large, high-contrast italic serif with a much smaller gold bold-serif subhead.
- Navigation is compact, uppercase, and serif-forward; body copy is calmer and more contemporary.
- Recommended clean-room stacks, requiring no remote font service:
  - Display/heading: Georgia or another PFOA-approved, locally bundled open-licensed serif.
  - Hero: italic form of the approved display serif.
  - Body/UI: system sans (`system-ui`, Segoe UI, Helvetica Neue, Arial, sans-serif).
- Approximate hierarchy:
  - Hero heading: `clamp(2.7rem, 6vw, 5.5rem)`, line-height about 0.95–1.05.
  - Page H1: `clamp(2.25rem, 4vw, 3.75rem)`.
  - Section H2: `clamp(1.8rem, 3vw, 2.5rem)`.
  - Card H3/H4: 1.25–1.6 rem.
  - Body: 1rem–1.0625rem, line-height 1.55–1.7.
  - Small metadata: 0.8–0.9rem.

### 2.4 Header and navigation

- Desktop header height is visually about 80–88 px, with a white logo at left and a single horizontal navigation at right.
- The current item and hovered top-level item use a gold rectangular fill. Other items are white on charcoal and separated by subtle vertical rules.
- Desktop submenus drop below the parent in a brick-red panel with white text and fine horizontal separators. Dropdowns appear on pointer hover; the implementation must also open them by keyboard focus and explicit button interaction.
- At desktop scroll positions the header remains visible as a translucent charcoal bar over content. At tablet/mobile the inspected header behaves as a normal top element rather than a persistent obstruction.
- Tablet/mobile replace the full menu with a square brick-red menu toggle. The open menu expands in document flow beneath the header, using full-width brick-red rows and separate disclosure buttons for nested items. This is a good accessible model for PFOA.
- The reference's cart control has no PFOA equivalent. Use that visual priority for a Donate action or omit the second control entirely.

### 2.5 Hero

- The hero is a large landscape photograph, approximately 1.5–1.6:1 in visible aspect ratio, with cover-cropping.
- Copy is overlaid directly on the image with localized darkening or text shadow. On desktop the copy may sit centered or offset; on mobile it is simplified and must not collide with the subject.
- The inspected reference rotates among multiple slides and shows three small pagination dots. Slide copy varies substantially.
- A static hero is the recommended first implementation. If a carousel is later authorized, it should pause on hover/focus, have labeled controls, respect reduced motion, and never make important content available only through autoplay.
- Use PFOA-provided photographs and real PFOA copy. Do not recreate or download the reference imagery.

### 2.6 Buttons, links, and states

- Primary CTAs are compact gold rectangles with modest 4–6 px rounding, dark serif text, and a small border/shadow that makes the control tangible.
- Secondary links often appear as bold brick text with a small gold directional marker rather than as large buttons.
- Hovered/active navigation becomes gold. Dropdown rows remain brick with a subtle tonal hover change.
- The reference showed a functional browser-default focus outline on an icon control, but it is not a strong branded focus system. The PFOA theme should deliberately improve this with a 3 px high-contrast `:focus-visible` ring and offset on every interactive control.
- Use short 150–200 ms color/transform transitions only; disable nonessential motion under `prefers-reduced-motion`.

### 2.7 Cards, panels, and imagery

- Most cards are flat and borderless. Hierarchy comes from image scale, serif titles, color, and whitespace—not heavy shadows.
- The four action pathways use circular photographs, large white titles, centered white copy, and gold buttons on a brick-red band.
- Featured-animal cards use landscape images above a brick title and short body copy. The reference uses four columns on desktop, two on tablet, and one on mobile.
- A consistent `aspect-ratio` around 5:3 or 3:2 is a practical target for animal/news thumbnails. Use `object-fit: cover` and permit focal-position control when WordPress supports it.
- News cards combine image, italic date, brick serif title, excerpt, and a compact Read More link.
- Event rows use a prominent month/day block alongside date/time and a serif title.
- Promotional blocks are image-led tiles rather than generic bordered cards. PFOA's Donate, Potholders, Wish List, Pet Tidings, and social links fit this pattern.
- Partner logos sit on white with generous clear space. They should not be forced into identical crops; constrain their maximum width/height and use `object-fit: contain`.

### 2.8 Footer

- The reference footer uses the same brick tone as the action band.
- Desktop starts with four link-list columns, then a large centered logo, a compact secondary menu, round gold social buttons, and centered legal text.
- Tablet uses two columns; mobile stacks each group into one column while keeping the centered identity/social block below.
- PFOA should substitute useful information for reference-only link groups: Rescue Animals, Ways to Help, About PFOA, and Visit/Contact.

## 3. Reference Homepage Component Breakdown

1. **Desktop header:** logo, primary menu with dropdowns, active-state block, small utility action.
2. **Hero carousel:** large photo, high-contrast serif statement, supporting line/CTA, pagination dots.
3. **Four-pathway action band:** Adopt, Foster, Donate, Volunteer; circular image, short copy, gold CTA.
4. **Featured animals:** heading and subhead, four image-led animal cards, link to all animals.
5. **Recent News:** three image/excerpt entries in a larger content column.
6. **Upcoming Events:** compact date-led entries in a narrower column.
7. **Promotional rail:** donation, shop, and social image tiles.
8. **Partners:** centered italic statement and a loose logo row/grid on white.
9. **Information footer:** four curated lists followed by brand, secondary navigation, social circles, and legal line.
10. **Newsletter dialog:** an automatic promotional modal is present on first visit. PFOA has no verified public subscription integration, so this is not in the first-theme scope.

## 4. PFOA Existing Site Inventory

### 4.1 Primary navigation

The public staging menu is:

- Home
- Home Front
- Rescue Animals
  - Adoptable Cats
  - Adopted2026
  - Sponsored Animals
  - Adoption Stories
  - In Memoriam
- Other Services
  - Spay/Neuter Program
  - Lifetime Care
  - General FAQs
- How To Help
  - Fundraising
    - Catnip & Sip
    - Fundraisers
    - Events Calendar
  - Membership
  - Volunteer!
  - Sponsor a Rescue Animal
  - Memorial Wall
  - Wish List
- About Us
  - Our Board Of Directors
  - Our Staff
  - Our Volunteers
  - Our Facilities
  - Our Mission and Objectives
  - Our Business Partners
  - Events Calendar
  - Pet Tidings Newsletter
  - News/Announcements
- Jobs
- Contact Us

This includes a three-level branch under How To Help > Fundraising, so the new desktop and disclosure navigation must support at least three levels.

### 4.2 Published Pages and editorial groupings

The staging REST API exposes 40 published Pages. In addition to the menu destinations above, visible Pages include:

- Our First 25 Years.
- Adopted archives for 2020 through 2025, plus the current Adopted2026 page.
- A Truly Pawsome Event.
- Potholders.
- Estate Auction.
- Pet Estate Plan.
- Thank-you.
- Homepage.

The content groups that must survive are:

| Group | Existing content |
|---|---|
| Adoption | Adoptable Cats, yearly adopted archives, adoption stories, sponsored/special-needs animals, In Memoriam |
| Animal services | Spay/Neuter Program, Lifetime Care, FAQs |
| Giving/help | PayPal donation flow, membership, volunteer opportunities including fostering, animal sponsorship, Wish List, Memorial Wall, fundraising/events |
| Organization | Mission/objectives, 25-year history, facilities, board, staff, volunteers, jobs, contact |
| Editorial/archive | Home Front weekly features, Pet Tidings PDFs/archive, News/Announcements, event and fundraiser pages, pet-estate-planning content |

### 4.3 Pages versus Posts and structured content

- **Ordinary Posts:** none are currently published through the public WordPress REST API. The only public category is Uncategorized with a count of zero.
- **News:** the public News/Announcements material is a manually composed Page, not a Post archive.
- **Events:** the Events Calendar is a Page containing a manually maintained table, not a publicly exposed event post type.
- **Adoptable animals:** the index is Page content containing a fixed table/grid of images and names. Images carry Popup Maker trigger classes. The profile shown after activation is a Popup Maker dialog containing attributes, narrative, a photo gallery, and sometimes a YouTube embed.
- **Staff/board/adoption stories/fundraiser details:** the index content is ordinary Page content, while many detail experiences are Popup Maker dialogs activated by `popmake-*` classes.
- **Home Front:** a Page containing current and archived weekly feature links/media; the public REST type list also exposes some Soliloquy slider entries. Their current editorial role needs admin-side verification.
- **Page templates:** 39 of the 40 public Pages report the legacy `template_builder.php` assignment; Mission and Objectives reports the default template.

### 4.4 Publicly observable plugin/theme dependencies

The public page output identifies the current Alyeska theme and ThemeBlvd framework styles. It also exposes markup or assets for Popup Maker, Genesis Columns Advanced, Gallery Lightbox Slider, WP Video Lightbox, and Soliloquy. This is evidence of public behavior, not a complete installed-plugin inventory.

Material dependencies are:

- Popup Maker for numerous dialogs and the `popmake-*` trigger convention.
- The existing PayPal donation interaction, exposed from a Popup Maker trigger and a PayPal charity/profile or donation form.
- ThemeBlvd lightbox classes on legacy image links.
- Genesis Columns Advanced classes on several editorial Pages.
- YouTube iframe embeds and media-library PDFs.

Do not guess which plugins are active solely from a public asset URL. Confirm the admin plugin list before implementation and again before launch.

### 4.5 Contact, social, and footer content

Visible site-wide information includes:

- Public hours: 11:00 am–4:00 pm Tuesday–Saturday, by appointment.
- Mailing address: P.O. Box 404, Sequim, WA 98382.
- Physical address: 257509 Highway 101, Port Angeles, WA.
- Main phone: (360) 452-0414.
- Fax: (360) 452-0412.
- Federal ID: 91-2127240.
- A map image and a Google Maps destination.
- Facebook and Instagram; header links also include AmazonSmile, Petfinder, and AdoptAPet.
- The current copyright line and organization name.

AmazonSmile is described as discontinued on the Wish List Page while it remains in the header. Treat that as a content-governance decision to verify, not as a theme behavior to preserve blindly.

## 5. Reference → PFOA Component Mapping

| Reference component | PFOA content/function | Proposed implementation | Exists now? | Verify later |
|---|---|---|---|---|
| Logo + primary navigation | Existing PFOA identity and three-level menu | `custom-logo` plus `wp_nav_menu`; desktop dropdowns and mobile disclosure navigation | Yes | Reassign the existing menu to the new theme location after activation |
| Header utility/cart control | Donation priority | Replace cart with a clear Donate CTA that invokes or links to the existing donation destination | Yes | Exact preferred PayPal destination and whether Popup Maker remains |
| Hero carousel | Current Rhodes/adoption homepage message and PFOA media | Static PFOA-owned hero first; optional accessible multi-slide enhancement later | Yes, as static Page content | Approved photo/copy, focal crop, and whether rotation is required |
| Four action pathways | Adoptable Cats; fostering within Volunteer; donation; volunteering/help | Reusable pathway cards linking to existing destinations; use Foster/Volunteer wording until a dedicated foster Page exists | Mostly | Whether PFOA wants a standalone Foster Page; approved card imagery |
| Featured animals grid | Adoptable Cats Page and Popup Maker profiles | Provider-backed animal cards with a safe fallback teaser linking to Adoptable Cats; do not parse the legacy table | Content yes; queryable source no | Admin workflow, source of truth, and whether a companion animal plugin/API exists |
| Recent News | News/Announcements Page, Home Front, Pet Tidings | Selected Page teaser cards now; native Posts query only when published Posts exist | Yes as Pages | Whether editors will migrate future news to Posts |
| Upcoming Events | Events Calendar Page and fundraiser Pages | Link/teaser to current Events Calendar; dynamic event rows only after a structured event source is approved | Yes as Page | Event editing workflow and whether an event plugin/CPT exists privately |
| Donation promo tile | Existing PayPal donation interaction | PFOA-owned image/graphic and CTA preserving the existing donation flow | Yes | Sandbox/production testing and return/thank-you behavior |
| Shop promo tile | Potholders and fundraising | Promote Potholders or current fundraising instead of inventing a store | Yes | Whether any checkout/order flow exists or the tile should be informational only |
| Social promo tile | Facebook/Instagram | Standard accessible text/icon link using PFOA-provided or theme-authored simple SVG icon treatment | Yes | Current approved networks; remove obsolete services |
| Partner logo strip | Business Partners Page | Curated responsive logo grid using current, approved partner media and links | Yes | Current partner roster, permissions, and outbound URLs |
| Four-column information footer | Rescue, Ways to Help, About, Contact/Visit | Footer menus plus centrally managed hours/address/contact/social data | Yes | Which links belong in footer and authoritative contact details |
| Newsletter popup | Contact Page mentions newsletter notification | Omit until a real provider, consent text, privacy policy, and form are confirmed | Unclear | Provider, list ownership, double opt-in, privacy requirements |

## 6. Recommended Theme Architecture

### 6.1 Theme model

Use a classic/hybrid PHP theme with `theme.json`. PHP templates should own site structure and the normal WordPress loop; `theme.json` should expose a constrained palette, typography sizes, spacing, content widths, and core block defaults. Do not build a custom framework and do not introduce a page builder.

The theme owns presentation only. Content types and operational data must remain in WordPress or in plugins. In particular, an animal custom post type should not live in the theme. If a structured animal source is later needed, use an existing plugin or a small companion plugin so animal data survives theme changes.

### 6.2 Template files

Minimum practical set:

- `style.css` with theme metadata and either the main stylesheet or a small import-free entry point.
- `functions.php` as a coordinator for setup, menus, assets, and includes.
- `theme.json` for design tokens and editor/front-end alignment.
- `header.php` and `footer.php`.
- `front-page.php` for the mapped homepage.
- `page.php` for ordinary Pages.
- `template_builder.php` as an independently written legacy-assignment compatibility template that renders the standard loop and Page content; it must contain no ThemeBlvd code.
- `single.php`, `home.php`, and `archive.php` so future standard Posts work without redesign.
- `search.php` and `404.php`.
- `index.php` as the required final fallback.
- `comments.php` only if comments are enabled in the requirements; otherwise do not add unused UI.

Useful template parts:

- Site header, primary navigation, and mobile menu.
- Hero.
- Pathway card/grid.
- Section heading.
- Animal card and animal-grid fallback state.
- Editorial/news card.
- Event row.
- Promotional tile.
- Partner logo.
- Footer link group and contact block.
- Generic content Page, post summary, empty state, and pagination.

Keep component APIs small: pass a known array/post object into a template part, escape at output, and avoid a service-container or bespoke rendering framework.

### 6.3 Menus and editable settings

Register:

- `primary` for the existing main menu.
- `footer` for a curated secondary menu.
- Optionally `utility` for external adoption/social destinations if PFOA wants editors to manage them as menu items.

Theme locations are theme-specific even though menu objects survive in the database. Activation documentation and the staging checklist must explicitly map the existing menu to `primary`.

Use the Customizer only for small site-wide choices that do not belong in Page content:

- Donation URL/action mode.
- Hero image, heading, supporting text, and CTA destination.
- Organization phone, public hours, mailing/physical address, and map URL.
- Social/profile URLs.
- Optional Page selections for pathway destinations.

Use core Site Identity for logo/title. Do not create a general-purpose section builder in the Customizer. Footer link groups should be menus; stable contact details can be Customizer settings or a single documented options group. Generic sidebar widget areas are not needed. One optional footer announcement/promo widget area is defensible only if PFOA has a real editorial need.

### 6.4 CSS and assets

- Define design tokens once in `theme.json` and matching CSS custom properties where traditional templates need them.
- Use modern CSS Grid/Flexbox, `minmax()`, `clamp()`, `aspect-ratio`, logical properties, and container-safe media.
- Keep a small component-oriented stylesheet structure: foundations, layout, components, legacy-content compatibility, and template-specific rules. A build system is optional; source CSS must remain understandable without a proprietary toolchain.
- Do not load a CSS framework. Do not depend on jQuery for theme behavior.
- Bundle only PFOA-owned, properly licensed, or original theme assets. Prefer current-color inline SVG drawn for the new theme or PFOA-provided icons; do not extract icons from either inspected site.
- All internal URLs must come from WordPress functions. Never hard-code the staging or production origin in theme templates.

### 6.5 JavaScript

The required theme JavaScript should be limited to:

- Accessible mobile-navigation disclosure behavior.
- Desktop submenu assistance where CSS alone cannot provide correct keyboard/touch behavior.
- Optional back-to-top behavior if retained.

A carousel is not required for the first working theme. If later added, write the minimal behavior independently or use a small, audited, permissively licensed library; it must not be a reference-theme dependency.

Load scripts through `wp_enqueue_script`, in the footer or with an appropriate loading strategy. Feature-detect, avoid global variables, and allow the site to remain usable if JavaScript fails.

### 6.6 Native WordPress behavior

- Render all ordinary content through `have_posts()`, `the_post()`, and `the_content()`.
- Use core image functions so `srcset`, sizes, alt text, and attachment metadata work.
- Use `WP_Query` only for real WordPress Posts or a verified public content type. Hide a dynamic section or show a relevant Page link when the source collection is empty.
- Preserve core search, pagination, password-protected Page behavior, embeds, galleries, captions, alignment classes, block styles, and editor styles.
- Do not scrape another Page's rendered HTML to populate homepage cards.

### 6.7 Legacy-content compatibility layer

The new theme needs narrowly scoped compatibility CSS and behavior for:

- Fixed and wide tables: responsive wrapper/overflow treatment without deleting cells or changing data order.
- `.gca-column`, `.one-half`, `.one-third`, `.first`, and clearfix patterns so old columns remain readable if the plugin styles are absent.
- `.alignleft`, `.alignright`, `.aligncenter`, WordPress galleries/captions, and large legacy images.
- Responsive YouTube iframes and other embeds.
- Existing Popup Maker trigger classes and dialog output.
- Existing ThemeBlvd lightbox links: degrade to normal image links if the old lightbox runtime is removed. The theme should not recreate proprietary ThemeBlvd JavaScript.
- Visible legacy shortcodes: audit and either preserve the responsible plugin or replace the small set with standard links/buttons through an explicit content cleanup. Never silently hide shortcode text.

Compatibility selectors should live under a Page-content wrapper so they do not pollute new components.

### 6.8 Accessibility baseline

- Skip link, landmarks, one meaningful H1, and logical heading order.
- Keyboard-operable multilevel navigation with explicit submenu buttons, `aria-expanded`, Escape behavior, and focus return.
- No hover-only navigation or information.
- At least 44 × 44 CSS px targets for primary mobile controls.
- Strong `:focus-visible` treatment on links, buttons, cards, menu disclosures, and modal controls.
- Text alternatives for informative images; empty alt for decoration; no new text baked into images.
- Sufficient hero overlays and color contrast.
- Reduced-motion support.
- Popup/dialog QA for focus entry, focus containment where appropriate, Escape/close control, background inertness, and focus return.
- Descriptive labels for external adoption, PayPal, PDF, map, social, and video links.

## 7. Responsive Behavior Specification

Use content-driven breakpoints rather than pretending to know the reference's source values. Recommended breakpoints are 1,100 px and 720 px, with a minor 480 px adjustment only where copy needs it.

### 7.1 Desktop: 1,100 px and above

- Center the site within a 1,280 px maximum canvas; display a warm neutral outer background on wider viewports.
- Show the full horizontal three-level menu. First-level dropdowns open on hover and focus; deeper submenus must remain inside the viewport.
- Allow the header to become sticky/translucent after scroll if it does not cover anchored content. Provide a scroll offset.
- Hero remains a large 1.5–1.6:1 visual with strong display copy.
- Action pathways: four equal columns.
- Featured animals: four columns.
- Lower content: approximately a 2:1:1 relationship for news, events, and promotional tiles, or an equivalent grid that preserves their relative emphasis.
- Partner logos: up to five across, contained rather than cropped.
- Footer link groups: four columns.

### 7.2 Medium/tablet: 720–1,099 px

- Use full-width canvas and 24 px gutters.
- Replace the inline nav with logo, Donate action, and menu toggle. Open navigation expands below the header.
- Header should be static; avoid consuming persistent vertical space.
- Hero keeps the same general ratio but uses smaller copy and a safer center/focal crop.
- Action pathways: two columns.
- Featured animals: two columns.
- News summaries: two columns; each summary may use a small side thumbnail.
- Events may stay three across near 768–1,099 px when dates fit; switch to two or one column as soon as titles wrap poorly.
- Promotional tiles: three across when practical, otherwise two plus one.
- Partner logos: three or four across and wrapping.
- Footer groups: two columns, followed by centered logo/social/legal content.

### 7.3 Mobile: below 720 px

- Use 20 px gutters; do not allow the document to exceed viewport width.
- Compact logo at left; Donate and menu controls at right. The open menu is a single-column brick panel with full-width rows. Each parent has a separate disclosure control; nested items indent rather than fly out.
- Hero height should be roughly 250–360 px depending on copy. Crop with `object-position`, not by stretching. Keep overlay copy short and suppress nonessential decorative text if it compromises contrast.
- Action pathways: one column, with image → heading → copy → CTA order.
- Featured animals and news: one column with full-width consistent landscape images.
- Events: one row/card per line; keep month/day adjacent to the title/time rather than stacking individual date fragments.
- Promotional tiles: one column.
- Partner logos: two columns, with a final odd logo centered.
- Footer groups: one column; centered identity/social/legal content last.
- True data tables may scroll horizontally within a labeled wrapper. Legacy layout tables should be neutralized to a readable single-column flow only when the cell order remains correct.
- Embedded media is width 100% with preserved aspect ratio. PDFs remain ordinary links with file type indicated.

## 8. Reusable Component List

1. Site container and padded content container.
2. Site header with logo, Donate action, desktop nav, and mobile disclosure nav.
3. Hero with static and optional-carousel variants.
4. Section heading with optional eyebrow, description, and action link.
5. Pathway card and responsive pathway grid.
6. Animal card, animal attribute list, and animal grid/empty fallback.
7. News/editorial summary card.
8. Event date row.
9. Promotional image tile.
10. Partner logo item and responsive partner grid.
11. Primary, secondary, and text-link button styles.
12. Metadata line/chip.
13. Responsive media/embed wrapper.
14. Responsive data-table wrapper.
15. Legacy Page-content wrapper and compatibility rules.
16. Footer link group, contact block, social list, and legal line.
17. Pagination and no-results state.
18. Notice/announcement block for time-sensitive operational messages.

## 9. Compatibility Requirements

- Do not change Page IDs, slugs, permalinks, menu item destinations, media attachment URLs, or existing content as a condition of activating the theme.
- Preserve the existing static front-page assignment and allow `front-page.php` to enhance presentation without deleting Page content.
- Include a clean-room `template_builder.php` compatibility template because nearly every public Page stores that legacy template filename.
- Render `the_content()` on every Page type so current embeds, forms, galleries, PDF links, plugin output, and shortcodes receive normal WordPress filters.
- Preserve `popmake-*` classes in Page content and verify representative Popup Maker triggers. Do not rename or strip arbitrary classes from editor content.
- The existing PayPal interaction must retain its configured recipient, purpose, return behavior, and thank-you flow. Theme code must not contain duplicated payment account values.
- Existing menu objects must remain; document/rehearse assignment to the new theme locations.
- The theme must not require CMSMasters, Elementor, WPBakery, Revolution Slider, ThemeBlvd, Alyeska, or another page builder/framework.
- If an old plugin remains for content functionality, the theme must not require its presentation framework to boot. Plugin absence should produce a readable link/teaser or ordinary content—not a fatal error.
- Internal theme URLs must use WordPress URL functions so the same ZIP works at staging and `safehavenpfoa.org`.
- Preserve core body classes, post classes, admin bar spacing, responsive image attributes, captions, galleries, and editor alignment classes.
- Keep old content readable even when it contains inline color/font/width values; override only the properties needed to prevent overflow, illegibility, or conflict with the new layout.
- Do not bundle reference-site images, logos, icons, fonts, or code. Maintain a small asset provenance record for every bundled non-code asset.
- The distributable artifact must be a normal theme ZIP whose top-level directory contains the theme files directly, without repository tooling, database dumps, secrets, or operating-system metadata.

## 10. Meaningful Risks / Unknowns

1. **Popup Maker is serving as a content store, not only a popup utility.** Animal profiles, biographies, stories, fundraiser details, and donation UI rely on it. Replacing its triggers or failing to load plugin output would remove real content. Verify representative items and the admin workflow before styling them.
2. **There is no public structured animal source.** The visible animal grid is a fixed Page table linked to popup entries. A dynamic reference-style animal query cannot be implemented robustly by parsing this markup. Confirm whether a private/companion animal system exists; otherwise scope a separate content-model decision.
3. **News and events are not queryable collections.** Staging has zero Posts and a manual events Page. Reference-style automatic Recent News and Upcoming Events sections require an approved editorial change; until then, link to existing Pages.
4. **Legacy Page-template assignment is pervasive.** If `template_builder.php` is missing or behaves differently, 39 public Pages may select an unexpected template. The compatibility template and URL smoke test are launch blockers.
5. **Legacy markup is fragile on small screens.** Fixed-width tables, layout tables, inline widths/fonts/colors, image-based buttons, and empty alt attributes appear in public content. Theme CSS can contain overflow, but some accessibility problems require explicit content cleanup and cannot be solved safely by generic CSS.
6. **ThemeBlvd presentation behavior will disappear with the old theme.** Existing `themeblvd-lightbox`/`tb-lightbox` links must degrade to normal links or receive an independently implemented, optional lightbox. Do not carry old framework code forward.
7. **Donation behavior is high consequence.** The header and membership/sponsorship flows use a Popup Maker-triggered PayPal interaction. Confirm the live recipient/configuration, test the handoff without completing a payment, and verify the thank-you Page. Do not infer account data from markup.
8. **Menu location assignments do not automatically follow a theme switch.** The menu content survives, but the new theme location may be empty until mapped.
9. **Content includes external and obsolete destinations.** AmazonSmile is visibly outdated; partner and social rosters may also change. Obtain the approved list rather than automatically reproducing every header link.
10. **Accessibility of existing dialogs is uncertain.** A public animal dialog was keyboard-exposed as a dialog and had a close button, but focus containment, Escape behavior, background inertness, and focus return need full testing.
11. **Production portability needs a URL audit.** Public content mixes root-relative and absolute URLs. Theme templates can be domain-neutral, but a staging-to-production database migration may still require a controlled WordPress-aware search/replace outside the theme.

## 11. Recommended Build Sequence

Each item is sized as a coding-agent task with a verifiable outcome.

1. **Establish the compatibility baseline.** Export a read-only list of published URLs, current menu hierarchy, assigned templates, visible plugin-dependent selectors, and a representative smoke-test set. Record expected outcomes for one animal popup, one bio popup, one lightbox/image link, PayPal handoff, YouTube embed, PDF, search, and 404.
2. **Create the installable theme skeleton.** Add metadata, `theme.json`, setup supports, menu locations, safe enqueueing, required fallback templates, and the independent `template_builder.php` compatibility template. Confirm activation with plugins both present and selectively unavailable in a test copy.
3. **Build the global visual foundation.** Implement tokens, typography, centered canvas, spacing, buttons, links, focus states, responsive media, WordPress alignment/gallery defaults, and the scoped legacy-content wrapper. Compare desktop/tablet/mobile foundations with the reference language.
4. **Implement the site shell.** Build the dark header, PFOA identity, Donate action, three-level desktop navigation, mobile disclosure menu, skip link, and brick footer using existing menu/contact destinations. Test pointer, keyboard, touch-width, and no-JavaScript behavior.
5. **Build the static homepage structure.** Implement the PFOA-owned hero, four pathway cards, adoption teaser/fallback, editorial and event teasers, donation/potholder/social promos, partners, and footer rhythm. Use existing Page links and do not manufacture news/events/animal records.
6. **Harden ordinary Page rendering.** Test and style long-form Pages, tables, columns, galleries, captions, aligned images, iframe/video, PDF links, old archives, and the current Home Front/Newsletter material. Fix overflow with narrowly scoped compatibility rules.
7. **Verify plugin interoperability.** Exercise representative Popup Maker triggers across animals, staff/board, stories, fundraiser content, and donation. Ensure z-index, dialog sizing, focus, close controls, and scroll behavior work inside the new shell. Preserve normal-link fallbacks for removed lightbox behavior.
8. **Add verified dynamic providers.** Only after source-of-truth decisions, connect animal/news/event components to official WordPress queries or plugin APIs. Keep each provider behind a small theme function/filter and retain the existing Page-link fallback. Do not add a content type to the theme.
9. **Run responsive and accessibility QA.** Test at approximately 390, 768, 1,024, and 1,440 px; keyboard-only navigation; zoom to 200%; reduced motion; high-content cards; missing images; long menu labels; and dialog focus. Resolve horizontal overflow and contrast failures.
10. **Package and rehearse activation.** Build the clean ZIP, install it on a copy of staging, assign menus/settings, flush permalinks only if needed, run the full URL/plugin smoke matrix, verify there are no staging-domain literals in theme output, and document rollback to the previous theme.

## 12. Acceptance Criteria for the First Working Theme

### Installation and clean-room requirements

- The delivered ZIP installs through WordPress and activates without fatal errors or warnings on the staging stack.
- The theme contains no CMSMasters, ThemeBlvd, Alyeska, Elementor, WPBakery, Revolution Slider, page-builder, or reference-site code/assets.
- Every bundled asset has a PFOA-owned, original, or documented compatible license/source.
- The theme works without a Node/build process in production; any development tooling is excluded from the ZIP.

### Content and compatibility

- All 40 currently published Page URLs continue to resolve and display their primary content.
- The existing homepage assignment, current menu object, media, PDFs, embeds, and search remain functional.
- Pages assigned to `template_builder.php` render through the independent compatibility template.
- No raw shortcode text becomes newly visible because of the theme switch. Any pre-existing raw shortcode is identified in the QA report with a deliberate remediation decision.
- Representative wide tables, old column layouts, aligned images, gallery/lightbox links, YouTube embeds, and PDF links are usable at desktop and mobile widths.
- No theme PHP/JS/CSS contains the staging hostname or a production-only absolute path.

### Homepage and visual fidelity

- The homepage clearly reflects the reference's visual language: centered wide canvas, dark header, large PFOA hero, brick action band, gold CTAs, serif-led hierarchy, image-forward sections, generous white rhythm, partner area, and brick information footer.
- Homepage copy and images are real PFOA content/assets; no reference content is used.
- Four primary pathways lead to valid existing PFOA destinations.
- Animal, news, and event areas never show invented data. When no structured collection exists, they present an intentional teaser/link fallback rather than an empty/broken grid.
- Current partner, social, donation, and contact destinations have owner approval.

### Responsive and interaction behavior

- At 1,440 px the layout is centered and does not grow beyond the intended canvas.
- At approximately 1,024/768 px navigation collapses cleanly, primary grids reduce columns, and the footer uses two columns where appropriate.
- At 390 px every primary section is readable in one column; partner logos use a contained two-column grid; no page-level horizontal scrollbar appears except inside an explicit wide-table wrapper.
- Desktop submenu and mobile disclosure navigation support three levels and work with pointer, keyboard, and touch-width interaction.
- If the hero is static, it has no inert carousel controls. If it rotates, it has accessible controls, pause behavior, and reduced-motion handling.

### Accessibility and functional smoke tests

- A visible skip link reaches main content.
- Interactive controls have a clear, high-contrast `:focus-visible` state.
- Heading order and landmarks are logical; informative images have useful alt text and new decorative images have empty alt text.
- The four-pathway cards, donation CTA, social links, and partner links are not conveyed by image text alone.
- A current adoptable-animal trigger opens the correct existing detail, the dialog is readable at 390 px, its close control works by keyboard, and focus returns to the trigger.
- A representative staff/board or adoption-story trigger still opens the correct content.
- The Donate action reaches the currently configured PayPal destination without the theme duplicating payment account details. No payment needs to be completed during QA.
- The theme remains readable when JavaScript is disabled and when an optional content plugin is unavailable; operational functions may degrade to documented links, never fatal errors.

### Handoff

- The implementation includes a short administrator note covering menu assignment, Customizer fields, hero/media sizing, partner management, donation configuration ownership, and the fallback behavior for animals/news/events.
- The implementation includes a reproducible ZIP command/process and a completed staging smoke-test checklist.
- Any deferred content-model decisions are listed explicitly; they are not disguised as finished dynamic components.
