# PFOA Task 5A: Homepage Static Structure

## Section order

`front-page.php` keeps the normal `front-page.php` WordPress hierarchy and
renders the assigned static front Page once. When that Page exists, the
homepage regions appear in this document order:

1. Hero (`#homepage-hero`)
2. Primary action pathways (`#homepage-pathways`)
3. Adoption teaser (`#homepage-adoption`)
4. News and events teasers (`#homepage-news-events`)
5. Promotional and informational tiles (`#homepage-promos`)
6. Partners and supporters (`#homepage-partners`)

The assigned static front Page remains the front-page loop context so the hero
can use its title and featured image. Its stored editor body is intentionally
not rendered by the custom homepage; it remains available in WordPress for
later migration and content cleanup. The regions above are the static
scaffold for later homepage component work.

## Content sourcing

- The hero uses the assigned front Page title as its H1 and the Page's featured
  image when one is assigned. No reference-site imagery or unapproved copy is
  bundled.
- The action pathways use the existing Adoptable Cats and Volunteering Pages
  when those published Pages are available. Donate uses the existing
  `pfoa_get_donation_url()` setting and fallback, so payment configuration is
  not duplicated in the theme.
- Adoption, news, events, and promotional tiles link to known existing Page
  destinations by slug. Their titles are read from WordPress at render time;
  the theme does not copy Page bodies or scrape legacy markup into cards.
- Partners currently render a safe, clearly labeled empty state plus a link to
  the existing Business Partners Page when it exists. Approved logos,
  permissions, and outbound destinations are not yet confirmed.
- `pfoa_get_page_by_paths()` only returns published Pages and supports a small
  ordered fallback list. It is a lookup helper, not a content model.

## Template parts

The homepage is split into meaningful, independently replaceable parts:

- `template-parts/homepage-hero.php`
- `template-parts/homepage-pathways.php`
- `template-parts/homepage-adoption.php`
- `template-parts/homepage-news-events.php`
- `template-parts/homepage-promos.php`
- `template-parts/homepage-partners.php`

There are no custom post types, taxonomies, tables, builder configuration, or
plugin dependencies in this task. The existing header, footer,
`template_builder.php`, navigation script, and Page rendering behavior remain
unchanged.

## Responsive structure

The homepage reuses the accepted 1,200px content width, 1,280px wide canvas,
gutter variables, spacing scale, and typography tokens. Homepage CSS is scoped
under `.front-page-content`.

- Wide layouts use four pathway columns, a two-column adoption teaser, a 2:1
  news/events relationship, three promotional columns, and a full-width
  partners boundary.
- Below 1,320px, pathways use two columns to match the accepted compact header
  breakpoint.
- Below 720px, pathways, adoption, news/events, and promotional regions stack
  in document order. Media placeholders use flexible aspect ratios and the
  hero uses a responsive minimum size rather than a fixed height.
- Borders, wrapping, `minmax()`, and flexible padding allow longer headings,
  missing destinations, larger text, and narrow widths without requiring
  horizontal page overflow.

The visible media surfaces in this task are intentionally neutral structural
placeholders. Later tasks can replace them with approved PFOA imagery without
changing section order or link structure.

## Editor-content behavior

The custom front page does not call `the_content()` or render the assigned
front Page's legacy editor body. The Page remains the active loop object while
the custom sections render, preserving access to its title, featured image,
ID, and metadata for homepage components. Stored Page content is untouched.

Ordinary Pages and the `template_builder.php` compatibility bridge continue to
use the shared Page content part and its normal `the_content()` and
`wp_link_pages()` pipeline. If the front-page query is empty, the normal
no-results part is used.

The former `template-parts/homepage-editorial.php` partial was removed in the
0.1.2 front-page remediation because it had no remaining role after the
editor-body insertion path was removed. No CSS hiding workaround replaces it.

## Deliberately deferred decisions

- Adoptable animals are not queried or extracted from the legacy Adoptable
  Cats Page or Popup Maker dialogs. A verified animal source and editorial
  workflow are required before an animal-card grid is added.
- News is not synthesized from Page HTML and no Posts are assumed. Events are
  not parsed from the manually maintained Events Calendar table. Dynamic
  collections wait for an approved source.
- Partner logos, social promotional tiles, hero copy beyond the editable Page
  title, and final image focal points wait for approved media/content.
- No newsletter form, carousel, modal, or page-builder-style section settings
  were introduced.

## Accessibility decisions

- Each region is a native `section` with a visible heading and a stable
  `aria-labelledby` reference; the hero supplies the single homepage H1.
- Pathways and teasers use normal links with visible, meaningful text. A
  missing Page destination produces a non-interactive status rather than an
  empty link or button.
- Decorative placeholder surfaces use `aria-hidden="true"`; informative
  hero images use WordPress's featured-image output so authored alternative
  text is preserved.
- DOM order matches the visual order, headings descend from H1 to section H2
  and card H3, and no hover-only interaction or unnecessary ARIA widget is
  added.
- Existing global focus-visible and reduced-motion rules continue to apply;
  the homepage pathway transition is explicitly reduced as well.
