# PFOA Task 6B — Popup Maker Interoperability Notes

**Date:** September 10, 2026
**Baseline:** `d2b3d94d14fcc525bc85d4c89c17be6d1e40c420` on `main`
**Scope:** Repository-side theme interoperability only

## Outcome

The theme preserves the existing Popup Maker content and trigger pipeline while
adding only defensive CSS for the observed legacy popup content. Popup Maker
continues to own popup creation, opening, closing, overlays, modal sizing,
stacking, focus containment, Escape handling, and lifecycle behavior.

The theme does not recreate popup content, add a popup trigger, call Popup
Maker APIs, or depend on Popup Maker to render or activate. Popup Maker removal
or replacement remains deferred until the content model and editorial workflow
are approved.

## Collision risks inspected

- The former unqualified theme action-button rule applied to every `button`.
  That could make a Popup Maker close button or plugin-owned control inherit
  the theme's gold button dimensions, padding, font, hover transform, and
  transition.
- Global form, link, heading, media, and focus rules were checked for effects
  on popup contents. The global media cap and visible `:focus-visible` ring
  are defensive and do not hide or reposition popup elements.
- Existing shell overflow, sticky-header, and z-index rules were checked. The
  theme does not assign a popup overlay/container position, z-index, display,
  visibility, fixed height, or scroll lock.
- The legacy Page bridge was checked separately. Its table and media rules are
  scoped to `.page-content-compatibility`; they do not target Popup Maker
  containers unless the plugin output is explicitly placed inside that Page
  content wrapper.
- Trigger classes such as `popmake-*` and `pum-trigger` are not selected for
  rewriting, disabling, or visual reinterpretation.

## Compatibility changes

### Button ownership

The shared theme button presentation now applies to theme-owned button classes,
form submit controls, and `.site-header button`. It no longer applies to every
bare `button` in the document. This leaves Popup Maker controls, including the
observed `.popmake-close` control, under plugin/native ownership while keeping
the PFOA header controls styled.

### Popup content containment

The narrowly scoped `.pum-container .pum-content` bridge:

- keeps popup content and observed inline-width/minimum-width legacy elements
  inside the available popup content width;
- caps images, video, arbitrary iframes, `embed`, and `object` media without
  changing arbitrary iframe aspect ratios;
- applies the existing source-specific 16:9 treatment only to YouTube embed
  URLs;
- gives authored wide tables a local horizontal scroll surface without
  suppressing their data or changing Popup Maker's modal sizing;
- keeps ordinary form fields and image-based payment controls contained; and
- reinforces the theme's visible keyboard focus ring for popup links, buttons,
  close controls, form fields, summaries, and role buttons.

No compatibility rule sets popup `position`, `z-index`, `display: none`,
`visibility`, fixed height, overlay behavior, focus trapping, or Escape
behavior. No theme JavaScript was added.

## Donation and payment boundaries

The theme does not copy payment identifiers, PayPal destinations, recipient
configuration, return URLs, payment JavaScript, or replacement donation forms.
The existing theme-owned Donate URL setting remains the only theme-level Donate
configuration. Popup Maker's existing donation UI and its payment-provider
handoff remain content/plugin behavior and must be tested without completing a
payment during staging UAT.

## Accessibility boundaries

The theme preserves the plugin's native dialog and ARIA semantics. It does not
add duplicate dialog semantics, a focus trap, focus return logic, Escape
handling, overlay dismissal, or background inertness. Static review confirms
that the theme does not remove focus outlines or hide close controls. Actual
focus containment, focus return, Escape behavior, stacking, and close-button
operation require rendered WordPress testing and are not claimed as verified
here.

## Static/fixture smoke coverage

The implementation was checked against representative markup shapes from the
compatibility baseline: `.pum-container`/`.pum-content`, `.popmake-close`,
`popmake-*`/`pum-trigger` trigger classes, fixed-width tables, inline minimum
widths, YouTube iframes, arbitrary iframes, PDF-style embeds, ordinary form
controls, and an `input[type="image"]` donation control. The checks confirm
that trigger classes remain untouched, close/lifecycle selectors are not
reinterpreted, and the added rules are limited to the popup content boundary.
This is static repository validation, not a rendered Popup Maker runtime test.

## Staging/UAT checklist

On a staging copy, with the current Popup Maker configuration active, test each
category at desktop width and approximately 390px wide:

1. **Animal details:** open the Rhodes trigger from `/adoptablecats2/`; verify
   the correct detail/gallery, readable text and images, responsive media,
   keyboard close, Escape, and focus return to the trigger.
2. **Board/staff biography:** open the Danette Grady staff biography from
   `/staff-2/`; verify the biography, close control, keyboard operation, and
   focus behavior. Repeat with a representative board biography if present.
3. **Adoption story:** open the Loki story from `/adoptionstories/`; verify the
   story remains readable, legacy visible text is not lost, and close/keyboard
   behavior works.
4. **Fundraiser details:** open the Potholders detail from `/fundraisers/` or
   `/potholders-2/`; verify images, tables/text, links, scrolling, and close
   behavior without performing a purchase.
5. **Donation UI:** open the existing Donate dialog from `/membership/`; verify
   the UI, image-based control, form target, keyboard close, focus behavior,
   and authorized PayPal handoff without submitting a payment.

Also verify that representative `popmake-*` trigger links and classes remain in
the rendered HTML, popup overlays and close controls are not covered by the
theme header, long popup content scrolls normally, wide tables/media do not
create unreachable document overflow, and 200% zoom remains usable. Record any
plugin-owned focus or stacking defect for the Popup Maker/plugin configuration
rather than adding theme lifecycle code.

Popup Maker removal or replacement is explicitly deferred. No staging or
production changes were made by this task.
