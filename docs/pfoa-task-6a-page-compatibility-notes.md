# PFOA Task 6A: Page Rendering and Legacy Content Hardening

## Outcome

Ordinary Pages and Pages assigned to the historical `template_builder.php`
filename now use the same `template-parts/content-page.php` presentation. The
compatibility template remains a clean-room WordPress template: it runs the
normal Page loop, passes through the shared Page part, and leaves the Page
content in the normal `the_content()` filter pipeline.

The default `page.php` path receives the normal Page class. The compatibility
path adds `page-content-compatibility` to the article, which keeps the bridge
rules local to legacy-assigned Page content. The front Page's existing
editorial wrapper continues to expose that same compatibility hook.

## Supported migration bridge

The bridge is intentionally limited to patterns confirmed in the compatibility
baseline:

- Page titles use the established serif hierarchy, a gold title rule, and a
  readable `75ch` prose measure. Headings, links, lists, blockquotes, images,
  captions, galleries, forms, and WordPress block content remain ordinary
  semantic output.
- Images, video, iframes, `embed`, and `object` elements are capped by their
  Page content container. Known YouTube video iframes are fluid and use a
  16:9 aspect ratio; arbitrary iframes and non-PDF document embeds receive
  containment only. PDF `embed`/`object` output is fluid and given a bounded
  reading height.
- Tables retain their HTML structure, cell order, and authored data. Legacy
  fixed-width tables under the compatibility wrapper receive a local horizontal
  scroll surface, while modern WordPress Table blocks retain their native
  wrapper behavior. Shared baseline borders and cell defaults remain ordinary
  table presentation; CSS does not rewrite table markup or guess table intent.
- Legacy inline width and minimum-width declarations are capped only on
  viewport-escaping content elements. Ordinary inline minimum widths are
  overridden with a scoped `min-width: 0 !important` so they cannot defeat
  mobile containment; inline `!important` outliers remain staging/content-cleanup
  items. Inline colors and fonts are preserved until an editor can make an
  intentional content-cleanup decision.
- Only `.gca-column.one-half` and `.gca-column.one-third` are supported from
  the observed legacy column markup. `.first` starts a new floated row;
  `.clear`, `.clearfix`, and `.gca-columns` receive the minimum clearing
  behavior needed by those columns. The columns remain side-by-side on wide
  screens and stack at 720px and below.
- Legacy image/lightbox classes receive no replacement runtime or visual
  framework. Their normal anchors and images remain usable links/images when
  the historical effect is unavailable.

No Page content is rewritten in PHP. No legacy builder, lightbox, PDF viewer,
Popup Maker implementation, external dependency, or new JavaScript behavior
was added. Existing Popup Maker triggers and filtered plugin output continue
to arrive unchanged through `the_content()`.

## Deferred staging/content UAT

Repository-only validation cannot confirm staging plugin state, popup focus
return, PayPal configuration, or the current menu assignment. Before theme
activation, run the compatibility baseline matrix against a staging copy,
including:

- all 40 published Page paths, with `/missionandobjectives/` as the default
  `page.php` control case;
- `/eventscalendar/`, `/faqs/`, `/wishlist/`, `/adopted2026/`,
  `/fromthehomefront-2/`, `/pettidings/`, and `/spayneuter/` at desktop and
  approximately 390px wide;
- representative Popup Maker animal, staff, adoption-story, fundraiser, and
  donation triggers, including keyboard close and focus return;
- the PayPal handoff without completing a payment, search, and a known-invalid
  URL/404.

If a content pattern still cannot be made safe with these narrow rules, record
it as a staging/content-cleanup item. Do not add a builder or parse Page HTML
to compensate.
