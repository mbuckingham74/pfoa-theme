# PFOA Task 6A Remediation Recheck

**Recheck date:** September 10, 2026
**Baseline:** `c4c500e40b52204a56ee3d5775303fffd0ad921b` on `main`
**Scope:** Only the three findings in `docs/sol-task6a-page-compatibility-audit.md`
**Overall result:** All three findings are resolved. No directly adjacent acceptance defect was introduced by the remediation.

This was a focused static recheck of the uncommitted remediation against the committed Task 6A baseline. It did not repeat the general Task 6A audit, access staging, or perform rendered-browser testing. The original Sol audit was preserved without modification.

## Finding 1 — Major inline `min-width` containment failure

**Status: Resolved.**

The new rule at `pfoa-theme/style.css:730-732` is:

```css
.page-content-compatibility .entry-content :where(div, section, article, p, figure, table, img, iframe, video, embed, object, td, th)[style*="min-width"] {
	min-width: 0 !important;
}
```

- An author-important stylesheet declaration wins over an ordinary inline author declaration such as the audited `style="min-width: 730px"` case. Together with the existing scoped `max-width: 100%` rule, it removes the CSS min/max conflict that previously allowed the inline minimum to govern and escape the mobile content column.
- The selector is narrow: it requires the `.page-content-compatibility .entry-content` ancestry, one of the enumerated overflow-prone elements, and an inline style attribute containing `min-width`. It does not select default-template Page content, the site shell, navigation, footer, or unrelated descendants without that inline property.
- `!important` is justified here because a normal author stylesheet declaration cannot override the ordinary inline author declaration that caused the finding. The declaration changes only `min-width`; it does not reset authored colors, typography, alignment, or other inline presentation.
- An inline `min-width: ... !important` would still win, but that case is explicitly documented as a staging/content-cleanup outlier and was not the documented failure mode.

No obvious adjacent layout regression is created. The rule removes a lower bound only where a legacy inline minimum exists, while the element's ordinary width, intrinsic content, and scoped maximum-width handling remain available to determine layout.

## Finding 2 — Minor table compatibility scope

**Status: Resolved.**

- The global `.entry-content table` rule at `pfoa-theme/style.css:690-695` no longer changes the table display model, forces `width: 100%`, or adds an overflow surface. Its remaining border, border-collapse, and cell presentation are shared visual defaults and do not reproduce the invasive legacy containment behavior identified by the audit.
- The containment behavior now lives at `pfoa-theme/style.css:777-782` under `.page-content-compatibility .entry-content`. It gives non-block legacy tables a bounded, local `overflow-x: auto` surface, so fixed-width or intrinsically wide content is contained without document-wide overflow or hidden cells.
- `table:not(.wp-block-table table)` excludes the table descendant in normal core Table block output. The `.wp-block-table` figure/wrapper therefore retains its native overflow responsibility, and the inner table retains its normal table display model rather than receiving a second scroll surface.
- The remediation does not alter table markup, captions, headers, row/cell order, or data. It does not hide or clip overflow; legacy content remains reachable through the local scroll surface. Rendered keyboard/touch behavior remains part of the already-deferred Page UAT rather than evidence of a new static defect.

The legacy behavior no longer spills into default Pages, ordinary Posts, or modern core Table blocks. The remaining shared visual defaults are consistent with the original audit's stated acceptable boundary.

## Finding 3 — Minor blanket 16:9 iframe sizing

**Status: Resolved.**

- The former `.page-content-compatibility .entry-content iframe[width][height]` rule is absent. Arbitrary fixed-dimension iframes are no longer assigned `width: 100%`, `height: auto`, or `aspect-ratio: 16 / 9` by the compatibility bridge.
- Arbitrary compatibility iframes now receive only `display: block` and `max-width: 100%` at `pfoa-theme/style.css:769-773`, in addition to the theme's existing global containment. Their authored height and non-video proportions are not replaced with a video ratio.
- Known YouTube and privacy-enhanced YouTube embed sources retain the source-specific fluid 16:9 rule at `pfoa-theme/style.css:578-584`. This remains reasonable for the documented Home Front video case and does not apply to unrelated iframe destinations.
- PDF `embed` and `object` elements retain `width: 100%` and `height: min(75vh, 48rem)` at `pfoa-theme/style.css:784-787`, together with `max-width: 100%`. The rule bounds the viewer to the content column and viewport-oriented reading height without hiding the document or its fallback content.

No new fixed-size or clipping defect appears in the remediation. The change removes forced generic sizing; it does not add a replacement width, height, overflow clipping, or aspect-ratio rule for arbitrary iframes.

## Directly adjacent acceptance check

No directly adjacent acceptance defect was found:

- the compatibility ancestry and content-element allowlist are unchanged;
- modern block tables lose only the unintended legacy display/overflow behavior;
- legacy non-block tables retain containment;
- video treatment remains source-specific;
- arbitrary iframe dimensions are no longer normalized to video proportions;
- PDF containment remains present; and
- other than the intended compatibility CSS declarations, the remediation does not change PHP, JavaScript, templates, homepage components, header/navigation, footer, plugin behavior, or the content model.

The updated Task 6A notes accurately describe the inspected CSS. Focused static assertions for the inline minimum, legacy and block tables, YouTube and arbitrary iframes, and PDF objects passed. `git diff --check` also passed. Staging and rendered-browser checks remain deferred as reported and should still exercise the existing Task 6A UAT matrix before activation.

**Remediation accepted**
