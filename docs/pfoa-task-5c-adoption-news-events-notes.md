# PFOA Task 5C: Adoption and News/Events Teasers

## Scope

Task 5C completes the visual treatment for the existing homepage Adoption and
News and events regions. The hero, primary pathways, promotional tiles,
partners/supporters, header, footer, and legacy Page compatibility template
remain outside this task.

No animal, news, or event content model was introduced. The theme remains a
presentation layer over the existing WordPress-managed Pages.

## Adoption content and destination sourcing

The Adoption teaser looks up the first published Page matching the established
`adoptablecats2` slug through `pfoa_get_page_by_paths()`. Its title and
permalink are read at render time. The teaser does not query or extract the
legacy animal table, Popup Maker dialogs, image galleries, animal attributes,
or status information.

When the published adoption Page resolves, the feature card is one native
full-card link to that Page. The supporting copy deliberately describes the
destination as adoption information and listings; it does not identify a
specific animal or imply that the abstract media surface is a current listing.
The branded media surface is intentionally ready to accept approved PFOA
imagery in a later content pass without changing the card structure.

If the Page or its permalink cannot be resolved, the section remains useful as
a clearly labelled adoption information region with a non-interactive card and
an explicit unavailable message. No empty link, homepage self-link, or dead
destination is emitted.

## News and events sourcing

The News card uses the existing ordered lookup for `news-announcements`, with
the documented `fromthehomefront-2` Home Front fallback. The Events card uses
the existing published `eventscalendar` Page lookup. Titles and permalinks are
read from WordPress at render time.

These cards are entry points, not feeds. The theme does not query Posts, parse
the News/Announcements Page body, parse the manually maintained event table,
invent dates/details, or call an event/news plugin API.

Each resolved destination becomes a single full-card native link with clear
link text. If one destination is unavailable, its companion card remains
visible as a non-interactive, visually muted card with an explicit status so
the difference between clickable and unavailable content is clear. If neither
destination resolves, the entire region is omitted to avoid an orphan heading
or empty interactive area.

## Visual architecture

Both regions reuse the accepted PFOA charcoal, brick, gold, beige, white,
Georgia display type, system sans body type, content width, gutters, spacing
tokens, borders, radius, and focus treatment.

- Adoption is a large two-column feature card with a branded abstract media
  panel, adoption Page title, concise supporting copy, and a full-card entry
  point. The media panel uses CSS-only geometry and typography so no
  unapproved or misleading animal asset is bundled.
- News and events use a warm neutral section with a wider News card and a
  narrower Events card on larger screens. Each card has its own abstract
  branded media treatment, source label, WordPress-managed title, concise
  description, and visible text-link treatment.
- Unavailable cards use muted media and surface treatments while retaining
  readable labels and status copy.
- Hover effects are limited to small color, border, shadow, and lift changes;
  no information depends on hover.

## Responsive behavior

The adoption feature and News/events grid use fluid `minmax()` columns and
content-driven height. On small screens the feature card and both teaser cards
stack in document order, while their media surfaces retain flexible aspect
ratios and the content continues to wrap naturally. Padding and type use the
existing responsive tokens and `clamp()` values, so longer Page titles,
larger browser text, zoom, and narrow viewports do not require fixed-height
cards or horizontal scrolling.

## Accessibility decisions

- Each region is a native `section` with a visible H2 and a valid
  `aria-labelledby` reference.
- Card headings remain H3 descendants of the region heading hierarchy.
- Available cards use normal links with meaningful visible link text. Each
  card contains only one link, with no nested controls.
- Unavailable cards are plain non-interactive containers and state their
  unavailable status in visible text.
- Abstract media surfaces are `aria-hidden` because all actionable and
  contextual information is repeated in the card copy.
- Focus-visible styling creates a high-contrast ring on the full-card links.
- Reduced-motion preferences disable the small card lift transitions.
- No new JavaScript, ARIA widget, plugin dependency, or hover-only interaction
  was added.

## Deferred content decisions

Approved adoption photography, animal-card data, editorial news entries,
structured event data, event dates, and any future content workflow remain
deferred until PFOA confirms authoritative sources and editorial ownership.
