# PFOA — Adopted Gallery Presentation Contract

**Status:** Adopted
**Date:** 2026-10-13
**Amendment:** 2026-10-13 — Board-mandated adoption event photo coverage (AG-02 strengthened; AG-01 and AG-12 clarified; acceptance matrix extended; Board-Mandated Release Gate added); 2026-10-13 — AG-09 black-paw + explicit span correction; 2026-10-13 — AG-12 universal-green badge + Adopted 2026 vertical rhythm 28/12/10/8 (0.1.129)
**Repository:** PFOA Theme
**Related contracts:** `docs/pfoa-compatibility-baseline.md`; PFOA Gallery Lightbox shared gallery/lightbox interaction behavior

## 1. Purpose
The Adopted Gallery is the public historical record of PFOA adoption occurrences. Its presentation must faithfully represent every Adoption Event and every participating cat while maintaining a consistent, chronological, visually balanced layout. These requirements apply to all existing and future historical Adoption Events.

## 2. Historical completeness
Requirement AG-01 — Every Adoption Event is represented. Every eligible historical Adoption Event must appear as an independent public gallery entry. A cat may participate in multiple historical Adoption Events. Each occurrence must be represented independently, even when the same cat and photograph appeared in an earlier event. Repeated historical occurrences must never be merged, deduplicated, collapsed, consolidated, or hidden for layout optimization, responsive behavior, row packing, or visual compactness. Each occurrence retains its independent chronological position and complete participant representation. Examples: Spencer and Thompson — April 28, 2026 Bonded Pair; Spencer and Thompson — June 17, 2026 Bonded Pair; Spencer, Thompson, and Wilbur — September 19, 2026 Adopted Together. All three events must remain independently visible.
Requirement AG-02 — Every participant has a visible event-specific photograph (Board-mandated highest-priority requirement). This requirement was unanimously approved by the PFOA Board following extensive discussion, subsequently reaffirmed by Nancy, and is a mandatory Production release acceptance criterion. Every participating cat must be visibly represented on the Adopted gallery by a photograph associated with each distinct historical Adoption Event in which that cat participated. This means: (1) every historical adoption occurrence is independently represented; (2) every participating cat has a visible photograph representing that specific occurrence within that event's gallery entry; (3) a cat adopted multiple times must be visibly represented in every corresponding Adoption Event; (4) a generic Cat Profile photograph or a photograph associated with a different adoption occurrence does not automatically satisfy the requirement; (5) a photograph may be reused across events only when its association with each occurrence is independently valid; (6) required event photographs must appear directly on the Adopted page — lightbox-only access is insufficient; (7) layout optimization, duplicate suppression, responsive behavior, and image-selection logic must preserve every required event photograph; (8) at least one event-representative photograph per participant is required, establishing no maximum on appropriate event photographs. A multi-cat event must visibly represent every participant. The gallery must not substitute one representative group photograph for the required individual participant representations. Additional event photographs may be retained according to approved image-selection behavior. Concrete acceptance: Spencer (4001) and Thompson (4003) — April 28, 2026, both cats visibly represented in their Bonded Pair event; June 17, 2026, both cats visibly represented again in their separate Bonded Pair event; September 19, 2026, Spencer, Thompson, and Wilbur each visibly represented in their Adopted Together event — all three historical occurrences must remain independently visible. July 3, 2026 four-cat adoption — Amelia (4011), Cami (4009), Greyer (4035), and Gypsy (4033) — all four cats require visible event-associated photographs within their single Adopted Together event.

## 3. Chronological ordering
Requirement AG-03 — Strict reverse chronology. Adoption Events appear newest first, ordered by historical adoption date. Visual reading order is left to right, followed by top to bottom. For multiple events on the same date, preserve a stable, deterministic secondary order consistent with the established historical presentation.
Requirement AG-04 — Event sequence is immutable for layout purposes. The layout engine must never rearrange Adoption Events to improve row packing. No masonry reordering, column balancing, gap backfilling, or visual sorting independent of event chronology. If the next chronological event cannot fit in the current row, it starts the next row.

## 4. Photograph geometry
Requirement AG-05 — Uniform displayed photograph dimensions. Every displayed cat photograph must have identical width and height at any given viewport. This applies across single-cat events, bonded pairs, Adopted Together events, repeated historical adoption occurrences, and events with additional photographs. Responsive breakpoints may change the standard photograph dimensions uniformly. Individual photographs must never be enlarged or reduced solely to fill available row space. Use consistent aspect ratio and image-fit behavior without distortion.

## 5. Adoption Event grouping
Requirement AG-06 — Events are indivisible layout units. All participating cat photographs, event metadata, date, captions, and classification badge remain associated with their Adoption Event. An event card must not be split across unrelated outer gallery rows. Its internal photograph arrangement may wrap at responsive breakpoints when necessary.
Requirement AG-07 — Event card width follows content. At desktop widths where participants fit side by side, the event card occupies the width required by its uniformly sized photographs, internal spacing, and card decoration. A one-photo event is narrower than a two-photo event; a four-photo event is wider. Card dimensions must account for borders, padding, captions, and badges without clipping or overflow. Events larger than the available row capacity must be accommodated within their own responsive card layout while preserving uniform photograph dimensions.

## 6. Sequential row construction
Requirement AG-08 — Chronology-first sequential packing. Determine available row capacity from the gallery container width, uniform photograph dimensions, event-card dimensions, and spacing. Process events sequentially in chronological order. Place the next complete event card into the current row when it fits. Otherwise, close that row and begin a new row with that event. Never inspect later events to find a better-fitting substitute. Do not stretch cat photographs to eliminate unused space.

## 7. Decorative PFOA filler panel
Requirement AG-09 — One filler panel per incomplete row. When sequential event placement leaves unused horizontal space, display one decorative PFOA filler panel occupying the remaining usable row width. The panel is visually separate from Adoption Event cards. It must: begin immediately after the row's final event card; span ALL remaining grid columns via an explicit span (span = row capacity − occupied columns; gridColumnStart 'span N', gridColumnEnd 'auto', data-pfoa-filler-span marker); match the full row height via stretch with no interior gaps; align cleanly with the row's event-card presentation; use a white background with a subtle theme-consistent border; display one simple solid-black cat paw centered horizontally and vertically on the white background; size the paw modestly with generous whitespace; preserve paw proportions regardless of panel width; remain noninteractive. The panel must not: represent a cat or Adoption Event; contain a logo, wordmark, text, adoption date, cat caption, classification badge, or badge-like element; launch a lightbox or contain a clickable destination; appear in adoption counts or historical data; generate unnecessary accessibility announcements; use multiple repeated paw tiles to occupy the remaining space. Use one filler panel regardless of how many photograph-width units remain — multiple unused slots receive one paw filler spanning all remainder, never a 1-column panel at the edge. If the available space is too narrow for a meaningful panel, the responsive implementation may omit the panel while preserving chronology, photograph dimensions, and event grouping. The paw is a simple inline SVG mark rendered by the theme; it must not depend on a header logo asset, logo image, or wordmark text, and must render identically whether or not a site logo exists. Filler panels use the same shared gallery card geometry as real event cards, including border-box sizing, spacing, and row alignment: an N-slot filler has the same outer geometry as an N-slot real event card (shared photo/gap/shell tokens; same border, padding, and gutters; same row height/alignment via stretch), with no independent filler widths, margins, or photo stretches.

## 8. Responsive behavior
Requirement AG-10 — Preserve semantics at every viewport. Responsive layouts must maintain: chronological order; complete Adoption Event membership; uniform cat photograph dimensions within the viewport; clearly grouped event cards; correct dates and badges; working gallery/lightbox interactions. The number of photograph slots per row may vary with viewport width. Event photos may wrap within their own cards when necessary. Filler panels adapt or disappear when insufficient space exists. Horizontal overflow must not be introduced by event cards or filler panels.

## 9. Presentation ownership
Requirement AG-11 — Separation of responsibilities. Cat Profiles own canonical cat identity, metadata, and current Adoptable/Adopted state. Adoption Events own historical adoption records, dates, membership, and event classifications. PFOA Theme owns public gallery layout, event cards, responsive geometry, photograph arrangement, decorative filler panels, dates, and badges. PFOA Gallery Lightbox owns shared gallery/lightbox interactions. Presentation layout must not require modifications to historical event data.

## 10. Established visual behavior
Requirement AG-12 — Preserve accepted presentation semantics. Retain: no badge on single-cat events; one green Adopted Together badge on every multi-cat event (two-member bonded pairs, non-bonded pairs, trios, quads and larger alike); bonded-pair classification retained in event classes and metadata (never a public red badge on the Adopted gallery — red Bonded Pair remains an Adoptable Cats presentation label only); existing approved captions and adoption dates; correct event image associations; working lightbox interactions; legacy-gallery duplicate suppression only within the same represented event entry to avoid exact-duplicate display inside one event. Duplicate suppression, layout optimization, responsive behavior, and image-selection logic must never remove, hide, collapse, or relegate to lightbox-only any photograph required by AG-02. In particular, duplicate suppression must never operate across separate historical Adoption Events and must never eliminate a required participant photograph on the ground that the same cat, image file, or derivative appeared in a different event. Valid repeated photographs across events are required coverage, not duplicates. Bonded relationships remain independent of historical Adoption Event membership.

## 11. Acceptance tests
Future changes to the gallery must verify the following scenarios:
- Single-cat event → One required cat photograph, no badge
- Bonded pair → Both cats visible, green Adopted Together badge (bonded classification retained in event classes/metadata, never a red badge)
- Non-bonded two-cat event → Both cats visible, green Adopted Together badge
- Three-cat event → Three participants visible, green Adopted Together badge
- Four-cat event → All four participants visible in one event card, green Adopted Together badge
- Repeat adoption → Same cat appears in each applicable historical event
- Event-specific photographic association → Every visible participant photograph is associated with the specific Adoption Event displayed; a generic Cat Profile photograph or a photograph from a different occurrence does not satisfy AG-02 unless its association with the displayed occurrence is independently valid
- Repeated adoption occurrences → Repeated events for the same cat or pair remain independently visible with per-event participant photographs (for example, Spencer/Thompson April 28, June 17, and September 19 events)
- Visible without lightbox → Every required event photograph is visible directly on the Adopted page; lightbox-only access is insufficient
- No cross-event suppression → Valid repeated photographs across separate events are preserved; duplicate suppression, layout optimization, responsive behavior, and image-selection logic do not remove required event photographs
- Quad followed by pair → Both fit together when row capacity allows
- Quad followed by trio → Trio moves to next row when insufficient capacity
- Incomplete row → One centered-paw filler spanning full remainder
- Multiple unused slots → One paw filler spanning all remainder, never 1-col at edge
- Narrow viewport → Responsive grouping, uniform photos, no overflow
- Same-day events → Stable deterministic order
- Lightbox activation → Correct participant/event photographs
- Chronology check → Never reordered for visual packing
Reference cases: Spencer/Thompson April and June events, plus September event with Wilbur; July 3, 2026 Amelia/Cami/Greyer/Gypsy quad; Olyvia/Oly/Sally three-member event; representative single-cat and bonded-pair events.

## 12. Board-Mandated Release Gate
This contract records the Board's highest-priority historical adoption presentation requirement, unanimously approved by the PFOA Board following extensive discussion and subsequently reaffirmed by Nancy. Failure to visibly represent any participating cat with an appropriate photograph for each applicable historical Adoption Event constitutes a Production release blocker, regardless of other functional or visual QA results.

## 13. Verification ownership
The AG-02 Board-mandated coverage requirement must be verified by: (a) automated verification where practical covering event-specific photographic association, repeated adoption occurrences, direct page visibility without lightbox, and no cross-event suppression of required photographs; (b) independent browser QA by Sol 6.1; and (c) formal Marilyn UAT acceptance of this requirement. Production release requires all three verification paths to confirm AG-02 coverage.

## 14. Future compatibility
The design must accommodate future Adoption Events without manual page-specific row adjustments. Participant counts, photograph counts, and gallery length may grow. Row capacity and photograph dimensions may be configured through theme presentation rules but must not depend on hardcoded historical Event IDs. The contract applies equally as additional annual Adopted galleries are introduced. Changes to this contract require explicit project-owner approval.

## Terminology Notes
These notes clarify presentation terms used in this contract without changing agreed behavior:

- Adopted Gallery is a new presentation term; distinct from adopted2026 event presenter / Adopted2026 gallery / AdoptedYYYY page layout.
- Adoption Event reuses the existing domain object with immutable-history semantics (not a presentation object).
- participant in this contract means event member (`ordered_participant_ids` / membership rows); display unit is the event card.
- event card in this contract means the Adopted Event panel; distinct from managed card / stored card / `pfoa_cat_cards`.
- filler panel is new; distinct from manual cards / editorial content; decorative only.
- photograph in this contract means image / attachment / display derivative; use image-fit without distortion.
- badge in this contract means presentation classification label (Adopted gallery: green Adopted Together on every multi-cat event, none on single-cat events; bonded classification retained in classes/metadata; red Bonded Pair is an Adoptable Cats label, not shown on the Adopted gallery); not the bonded-pair labels previously noted as non-adoption badges.
- caption reuses existing WP/gallery hierarchy (visible caption > title, never filename/alt).
- chronology in this contract means historical adoption-date order; distinct from rendered page order / event order / admission sequence.
