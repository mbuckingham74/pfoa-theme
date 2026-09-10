# PFOA Task 5D: Promotional Tiles and Partners/Supporters

## Scope

Task 5D completes the visual and responsive treatment for the existing
homepage Promotional / informational tiles (`#homepage-promos`) and Partners
and supporters (`#homepage-partners`) regions. The accepted hero, pathways,
adoption, news/events, header, footer, and legacy Page compatibility work is
unchanged.

No custom post type, taxonomy, partner database, page-builder setting, plugin
dependency, external logo, or new JavaScript was introduced.

## Promo content and destination sourcing

The promo rail is built from safe destinations already established by the
homepage architecture:

- Donate uses `pfoa_get_homepage_donation_url()`. It resolves the configured
  approved donation URL first, then the published Membership Page. If neither
  is available, the Donate tile is omitted; the component never falls back to
  the homepage or emits a dead/self-link.
- Potholders uses the first published Page matching `potholders-2`.
- Wish List uses the first published Page matching `wishlist`.

Page titles and permalinks are read from WordPress at render time. The theme
does not copy Page bodies, infer campaign details, add monetary claims, or
parse legacy markup. If an individual Page or permalink cannot be resolved,
that tile is omitted. If no safe promo destination remains, the entire region
is omitted so there is no orphan heading or empty interactive area.

The three promo types use short, theme-owned labels and neutral descriptions
that explain the destination without inventing program facts. The visible
media treatment is CSS-only and intentionally remains useful before approved
PFOA photography or artwork is supplied.

## Partner/supporter fallback and future data

No approved partner roster, logo set, image permissions, or outbound
destinations were present in the repository. The default render therefore
shows a restrained empty state and, when available, a link to the existing
Business Partners Page. It does not manufacture organization names, logos,
links, or claims and does not scrape staging or production content.

The template includes the narrow `pfoa_homepage_partner_items` presentation
filter for a future approved data pass. Each item must provide a meaningful
`name` and may provide an attachment `image_id` and permitted `url`. Invalid
items are ignored. A supplied logo receives the partner name as its image
`alt` text; an item without an image falls back to the supplied name. The
theme still owns no partner content model, and the filter is empty by default.

## Visual architecture

Promos sit on the warm neutral surface to distinguish them from the brick
primary pathway band. Each resolved destination is one full-card native link:

- a rectangular CSS-only media panel with a short visual label;
- a small serif eyebrow for context;
- a WordPress-managed Page title or the configured Donate label;
- concise supporting copy; and
- a visible text action with the existing gold directional marker.

The white cards use a light border, restrained shadow, small lift on hover,
and a branded border response on focus. No information depends on the hover
state.

Partners use a white logo surface with generous clear space. When data is
available, the grid uses flexible logo cells, `object-fit: contain`, bounded
maximum dimensions, and a name fallback. The empty state uses the same border
rhythm and spacing so the section remains coherent before assets arrive.

## Responsive behavior

- Wide desktop uses three promo columns and a fluid partner logo grid.
- Laptop/tablet promo grids reduce to two columns before stacking; partner
  cells wrap with `auto-fit` and `minmax()`.
- Mobile stacks promo cards in document order and reduces media/cell minimums
  without imposing fixed card heights.
- Text, Page titles, partner names, and larger browser text wrap naturally.
- Logo cells constrain width and height without cropping or forcing a shared
  aspect ratio.
- The existing content width, gutter, spacing, color, typography, focus, and
  reduced-motion tokens remain the source of truth.

## Accessibility decisions

- Both regions use native sections with visible H2 headings and valid
  `aria-labelledby` references.
- Promos are one native link per card with meaningful visible title and action
  text; there are no nested interactive elements.
- Partner items are non-interactive when no URL is approved and become one
  native link only when a permitted URL is supplied.
- Partner logos use the approved partner name as meaningful alternative text.
  Name-only fallback items remain readable without relying on imagery.
- CSS-only media is marked `aria-hidden` because its labels are repeated in
  the accessible card content.
- Focus-visible rings are high contrast and include an offset against the
  surrounding surface. Hover never reveals required information.
- Reduced-motion preferences disable card lift and transition effects.

## Deferred content and checks

Final PFOA photography/artwork for promos, the approved partner/supporter
roster, logo files, image permissions, outbound partner URLs, donation
destination preference, and any final editorial copy remain intentionally
deferred.

Browser checks on the activated theme and a staging smoke test still need to
confirm the real Page slugs, configured donation destination, long translated
or editor-supplied titles, partner asset proportions, keyboard focus order,
mobile menu interaction, zoom/larger-text behavior, and the final visual
balance at desktop, tablet, and mobile widths. No staging or production
content, settings, media, menus, plugins, themes, or database records were
changed by this task.
