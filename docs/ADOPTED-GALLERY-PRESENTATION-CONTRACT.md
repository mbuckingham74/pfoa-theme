# PFOA — Adopted Gallery Presentation Contract

**Status:** Adopted
**Date:** 2026-10-13
**Repository:** PFOA Theme
**Related contracts:** `docs/pfoa-compatibility-baseline.md`; PFOA Gallery Lightbox shared gallery/lightbox interaction behavior

## 1. Purpose
The Adopted Gallery is the public historical record of PFOA adoption occurrences. Its presentation must faithfully represent every Adoption Event and every participating cat while maintaining a consistent, chronological, visually balanced layout. These requirements apply to all existing and future historical Adoption Events.

## 2. Historical completeness
Requirement AG-01 — Every Adoption Event is represented. Every eligible historical Adoption Event must appear as an independent public gallery entry. A cat may participate in multiple historical Adoption Events. Each occurrence must be represented independently, even when the same cat and photograph appeared in an earlier event. Examples: Spencer and Thompson — April 28, 2026 Bonded Pair; Spencer and Thompson — June 17, 2026 Bonded Pair; Spencer, Thompson, and Wilbur — September 19, 2026 Adopted Together. All three events must remain independently visible.
Requirement AG-02 — Every participant has a visible photograph. Every participating cat in an Adoption Event must have at least one visible photograph within that event's gallery entry. A multi-cat event must visibly represent every participant. The gallery must not substitute one representative group photograph for the required individual participant representations. Additional event photographs may be retained according to approved image-selection behavior.

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
Requirement AG-09 — One filler panel per incomplete row. When sequential event placement leaves unused horizontal space, display one decorative PFOA filler panel occupying the remaining usable row width. The panel is visually separate from Adoption Event cards. It must: span the available unused horizontal width; align cleanly with the row's event-card presentation; use a restrained background and border consistent with the theme; display the official PFOA logo centered horizontally and vertically; scale the logo proportionally within the available panel dimensions; preserve the logo's aspect ratio; remain noninteractive. The panel must not: represent a cat or Adoption Event; contain an adoption date, cat caption, or classification badge; launch a lightbox or contain a clickable destination; appear in adoption counts or historical data; generate unnecessary accessibility announcements; use multiple repeated logo tiles to occupy the remaining space. Use one filler panel regardless of how many photograph-width units remain. If the available space is too narrow for a meaningful panel, the responsive implementation may omit the panel while preserving chronology, photograph dimensions, and event grouping. Use an existing official PFOA logo asset from the theme or approved shared assets.

## 8. Responsive behavior
Requirement AG-10 — Preserve semantics at every viewport. Responsive layouts must maintain: chronological order; complete Adoption Event membership; uniform cat photograph dimensions within the viewport; clearly grouped event cards; correct dates and badges; working gallery/lightbox interactions. The number of photograph slots per row may vary with viewport width. Event photos may wrap within their own cards when necessary. Filler panels adapt or disappear when insufficient space exists. Horizontal overflow must not be introduced by event cards or filler panels.

## 9. Presentation ownership
Requirement AG-11 — Separation of responsibilities. Cat Profiles own canonical cat identity, metadata, and current Adoptable/Adopted state. Adoption Events own historical adoption records, dates, membership, and event classifications. PFOA Theme owns public gallery layout, event cards, responsive geometry, photograph arrangement, decorative filler panels, dates, and badges. PFOA Gallery Lightbox owns shared gallery/lightbox interactions. Presentation layout must not require modifications to historical event data.

## 10. Established visual behavior
Requirement AG-12 — Preserve accepted presentation semantics. Retain: red Bonded Pair badges for qualifying two-member bonded events; green Adopted Together badges for qualifying multi-cat events; existing approved captions and adoption dates; correct event image associations; working lightbox interactions; legacy-gallery duplicate suppression within the same represented event. Duplicate suppression must never eliminate a photograph required to represent a separate historical Adoption Event. Bonded relationships remain independent of historical Adoption Event membership.

## 11. Acceptance tests
Future changes to the gallery must verify the following scenarios:
- Single-cat event → One required cat photograph
- Bonded pair → Both cats visible, correct red badge
- Three-cat event → Three participants visible, correct classification
- Four-cat event → All four participants visible in one event card
- Repeat adoption → Same cat appears in each applicable historical event
- Quad followed by pair → Both fit together when row capacity allows
- Quad followed by trio → Trio moves to next row when insufficient capacity
- Incomplete row → One centered-logo filler panel where space permits
- Multiple unused slots → One filler panel, never repeated logos
- Narrow viewport → Responsive grouping, uniform photos, no overflow
- Same-day events → Stable deterministic order
- Lightbox activation → Correct participant/event photographs
- Chronology check → Never reordered for visual packing
Reference cases: Spencer/Thompson April and June events, plus September event with Wilbur; July 3, 2026 Amelia/Cami/Greyer/Gypsy quad; Olyvia/Oly/Sally three-member event; representative single-cat and bonded-pair events.

## 12. Future compatibility
The design must accommodate future Adoption Events without manual page-specific row adjustments. Participant counts, photograph counts, and gallery length may grow. Row capacity and photograph dimensions may be configured through theme presentation rules but must not depend on hardcoded historical Event IDs. The contract applies equally as additional annual Adopted galleries are introduced. Changes to this contract require explicit project-owner approval.

## Terminology Notes
These notes clarify presentation terms used in this contract without changing agreed behavior:

- Adopted Gallery is a new presentation term; distinct from adopted2026 event presenter / Adopted2026 gallery / AdoptedYYYY page layout.
- Adoption Event reuses the existing domain object with immutable-history semantics (not a presentation object).
- participant in this contract means event member (`ordered_participant_ids` / membership rows); display unit is the event card.
- event card in this contract means the Adopted Event panel; distinct from managed card / stored card / `pfoa_cat_cards`.
- filler panel is new; distinct from manual cards / editorial content; decorative only.
- photograph in this contract means image / attachment / display derivative; use image-fit without distortion.
- badge in this contract means presentation classification label (red Bonded Pair, green Adopted Together); not the bonded-pair labels previously noted as non-adoption badges.
- caption reuses existing WP/gallery hierarchy (visible caption > title, never filename/alt).
- chronology in this contract means historical adoption-date order; distinct from rendered page order / event order / admission sequence.
