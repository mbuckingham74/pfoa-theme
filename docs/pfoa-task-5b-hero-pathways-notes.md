# PFOA Task 5B: Homepage Hero and Primary Action Pathways

## Scope

Task 5B updates only the existing homepage hero and four primary action
pathways. The front-page loop, assigned Page editor-content region, and the
later adoption, news/events, promotional, and partner regions remain in their
Task 5A order and architecture.

## Hero architecture and fallback behavior

- The hero remains a standalone `template-parts/homepage-hero.php` region and
  supplies the homepage's single H1 from the assigned front Page title.
- The site name remains an eyebrow above the H1; no new factual organization,
  animal, impact, or program claims were added.
- When the assigned front Page has a featured image, WordPress renders that
  image eagerly with its authored alternative text. A scoped scrim preserves
  readable contrast for the existing charcoal, brick, and gold palette.
- When no featured image exists, the hero uses a CSS-only charcoal/brick/gold
  treatment with a restrained circular line motif. It does not download,
  embed, or simulate a rescue photograph.
- Hero height is a responsive minimum, not a fixed height. The content can
  grow naturally for longer Page titles, larger text, and wrapped buttons.
- The hero action buttons resolve through the existing published Page lookup
  helper. Adopt is primary when available; otherwise Volunteer becomes the
  primary action. A button is omitted if its destination cannot be resolved
  safely.

## CTA sourcing

The hero CTA destinations are the published `adoptablecats2` and
`volunteering` Pages already used by the Task 5A pathways. The labels are
short, existing action labels (`Adopt` and `Volunteer`) rather than invented
campaign copy.

The header/footer compatibility Donate fallback remains unchanged. Homepage
pathways use `pfoa_get_homepage_donation_url()`, which prefers the configured
Donate URL, then a published `membership` Page, and otherwise returns an empty
destination so the Donate card becomes a visible non-interactive state instead
of a misleading homepage self-link.

## Pathway sourcing and behavior

- The four established items remain Adopt, Foster, Donate, and Volunteer.
- Adopt and Foster/Volunteer resolve to the existing published Page slugs.
- Donate resolves through the safe homepage-specific helper described above.
- Each available pathway is one full-card native link. There are no nested
  controls or JavaScript click handlers.
- A missing destination renders the same labelled non-interactive status
  pattern established by Task 5A; it does not invent a URL or dead control.
- No custom icon dependency or unapproved image asset was introduced. The
  circular pathway media surfaces are CSS-only branded placeholders with a
  decorative initial and can later be replaced by approved PFOA imagery
  without changing the card/link structure.

## Responsive behavior

- The existing homepage grid uses four columns at wide desktop widths,
  two columns below the compact-header breakpoint, and one column below the
  mobile breakpoint.
- Grid items stretch naturally within each row while card content uses flex
  spacing to keep the action affordance aligned without a fixed card height.
- Labels wrap normally, and the hero CTA group wraps instead of overflowing at
  narrow widths or larger browser text settings.
- The hero image uses `object-fit: cover`; the no-image treatment remains
  fully usable at every breakpoint.
- Existing root gutters, content widths, spacing tokens, and reduced-motion
  rules are reused.

## Accessibility decisions

- The hero retains a logical H1 and the pathway region retains its visible H2
  followed by H3 card headings.
- Links remain native links in document order. Full-card interaction is
  achieved without nesting links or buttons.
- The existing global high-contrast `:focus-visible` ring is retained; hero
  secondary buttons and pathway cards also receive an explicit visible state
  against their colored surfaces.
- Decorative CSS media and initials are hidden from assistive technology.
- Essential labels and destinations are visible without hover, and no new ARIA
  widget or interaction model was introduced.
- Existing reduced-motion handling continues to disable nonessential pathway
  motion.

## Intentionally deferred

- Approved PFOA hero and pathway imagery, focal points, and final brand assets.
- Final client-approved hero message/copy beyond the editable Page title and
  existing action labels.
- Any hero carousel, autoplay, pagination, or image-management workflow.
- A dedicated Foster destination if PFOA later separates fostering from the
  existing Volunteer Page.

## Runtime checks still required

- In a WordPress test copy, confirm the assigned static Homepage title and
  featured-image alternative text render as expected.
- Verify the approved Donate URL in Customizer and confirm the published
  Membership fallback; also test the missing-destination state.
- Check CTA destinations and keyboard focus order at desktop, laptop, tablet,
  mobile, and increased text-size/zoom widths.
- Review the eventual approved images for crop/focal-point changes and rerun
  the compatibility smoke matrix after deployment to a test copy.
