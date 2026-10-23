# DEV-only temporary synthetic lifecycle QA exclusion — activation & removal

> Status: ACTIVE on DEV only (theme 0.1.212). Production unaffected by design.
> This is presentation-only. No database writes, no content edits, no deletions.

## 1. What is excluded (exact verified inventory, DEV 2026-10-23)

14 synthetic QA Cat Profiles (`pfoa_cat`, `publish`):

| ID | Title | Slug |
|----|-------|------|
| 25351 | QA TEST — LCD01-R02 DEV — 20261023-091525-396be284 | qa-test-lcd01-r02-dev-20261023-091525-396be284 |
| 25347 | QA TEST — Corrected Retry — QA-LCD01-20261022-REG | qa-test-corrected-retry-qa-lcd01-20261022-reg |
| 25341 | QA TEST — Bond B — QA-LCD01-20261022-REG | qa-test-bond-b-qa-lcd01-20261022-reg |
| 25338 | QA TEST — Bond A — QA-LCD01-20261022-REG | qa-test-bond-a-qa-lcd01-20261022-reg |
| 25335 | QA TEST — After Adoption — QA-LCD01-20261022-REG | qa-test-after-adoption-qa-lcd01-20261022-reg |
| 25332 | QA TEST — Same Day — QA-LCD01-20261022-REG | qa-test-same-day-qa-lcd01-20261022-reg |
| 25327 | QA TEST — Together C — QA-LC-20261022-01 | qa-test-together-c-qa-lc-20261022-01 |
| 25324 | QA TEST — Together B — QA-LC-20261022-01 | qa-test-together-b-qa-lc-20261022-01 |
| 25321 | QA TEST — Together A — QA-LC-20261022-01 | qa-test-together-a-qa-lc-20261022-01 |
| 25316 | QA TEST — Stagger B — QA-LC-20261022-01 | qa-test-stagger-b-qa-lc-20261022-01 |
| 25313 | QA TEST — Stagger A — QA-LC-20261022-01 | qa-test-stagger-a-qa-lc-20261022-01 |
| 25309 | QA TEST — Joint B — QA-LC-20261022-01 | qa-test-joint-b-qa-lc-20261022-01 |
| 25304 | QA TEST — Joint A — QA-LC-20261022-01 | qa-test-joint-a-qa-lc-20261022-01 |
| 25294 | QA TEST — Solo — QA-LC-20261022-01 | qa-test-solo-qa-lc-20261022-01 |

12 synthetic QA Adoption Events (`pfoa_adoption_event`, `publish`, `_pfoa_event_year` 2026):

| Event ID | Members | Date |
|----------|---------|------|
| 25302 | 25294 | 2026-10-22 |
| 25303 | 25294 | 2026-10-22 |
| 25312 | 25309, 25304 | 2026-10-22 |
| 25319 | 25316, 25313 | 2026-10-21 |
| 25320 | 25313 | 2026-10-22 |
| 25330 | 25321, 25324 | 2026-10-22 |
| 25331 | 25321, 25324, 25327 | 2026-10-22 |
| 25344 | 25332 | 2026-10-22 |
| 25345 | 25335 | 2026-10-21 |
| 25346 | 25338, 25341 | 2026-10-22 |
| 25350 | 25347 | 2026-10-22 |
| 25355 | 25351 | 2026-10-22 |

Reconciliation note: NONE of the 14 profile IDs appear in any retained UAT
manifest/CSV/JSON/fixture in `pfoa/` or `pfoa-theme/` (verified 2026-10-23;
`25347`/`25327` hits elsewhere are byte-count/filesize collisions, not post
IDs). Total adoption events on DEV went 61 (manifest) → 73 (12 synthetic).
All 12 synthetic events are year 2026, so Adopted 2026 + Recently Adopted are
the affected galleries. Nine legitimate adoptable listings (12 profile IDs:
25099, 24346, 24357, 24406, 24407, 23671, 24183, 24184, 23674, 24396, 24401,
23678) are NOT in the deny lists and render normally.

## 2. How it works (all in theme, all reversible)

- New file: `pfoa-theme/inc/pfoa-dev-synthetic-exclusion.php`
  (host gate + deny lists + static-card filter + direct-access 404 guard).
  Required from `pfoa-theme/functions.php` (marked block).
- In-loop guards in `functions.php` (each marked
  `PFOA DEV-ONLY TEMPORARY SYNTHETIC EXCLUSION — REMOVE AT FINAL CLEANUP`):
  event-ID skip + member-cat skip in `pfoa_present_adoption_events()`,
  event-ID skip + member-cat skip in `pfoa_get_recently_adopted_events()`,
  early `false` (+ `dev-synthetic-excluded` report row) in
  `pfoa_recently_adopted_is_currently_adopted()`.
- Host gate: active ONLY when `get_option('home')` host is
  `dev.safehavenpfoa.org`. `wp_get_environment_type()` is NOT used (DEV
  reports `production`). Admin, feeds, and REST are never filtered.
- Public policy for direct URLs: denied `/pfoa-cat/<slug>/` and
  `?pfoa_cat_fragment=<denied-slug>` return the theme 404 on DEV public.
  wp-admin records, revisions, attachments, receipts, and history are intact.

## 3. Verification (DEV)

```bash
# 14/12 exclusions active, legit untouched
curl -s https://dev.safehavenpfoa.org/adoptablecats2/ | grep -c 'QA TEST'   # expect 0
curl -s https://dev.safehavenpfoa.org/adopted2026/   | grep -c 'QA TEST'   # expect 0
curl -s -o /dev/null -w '%{http_code}\n' https://dev.safehavenpfoa.org/pfoa-cat/qa-test-solo-qa-lc-20261022-01/  # expect 404
curl -s -o /dev/null -w '%{http_code}\n' https://dev.safehavenpfoa.org/pfoa-cat/bowman/  # expect 200
```

Plus browser pass at 1440px + 390px: Adoptable Cats shows the nine
legitimate listings with bonded grouping; Adopted 2026 and Recently Adopted
show no synthetic cards; Explore/homepage/nav work; wp-admin records intact.

## 4. Removal procedure (final pre-launch synthetic-data cleanup)

This exclusion is temporary. The authorized final cleanup deletes the QA
records themselves; when that happens, remove this filter in the same change:

1. `git rm pfoa-theme/inc/pfoa-dev-synthetic-exclusion.php` (or plain `rm`).
2. In `pfoa-theme/functions.php`, delete the `require_once` block marked
   `PFOA DEV-ONLY TEMPORARY SYNTHETIC EXCLUSION`.
3. In `pfoa-theme/functions.php`, delete the five marked guard blocks
   (`pfoa_present_adoption_events` ×2, `pfoa_get_recently_adopted_events`
   ×2, `pfoa_recently_adopted_is_currently_adopted` ×1).
4. Bump `Version:` in `pfoa-theme/style.css` and update the four version-gate
   fixtures (`theme-homepage-hero-links`,
   `theme-adoption-inmemoriam-caption`, `theme-recently-adopted-selection`,
   `theme-adoption-page-title`).
5. `bash scripts/verify-theme.sh`, commit, deploy with
   `bash scripts/deploy-theme.sh --environment dev --commit HEAD --deploy`,
   re-verify public pages show only legitimate post-cleanup content.

Do NOT run DB deletes from this procedure — final record cleanup is separately
authorized.
