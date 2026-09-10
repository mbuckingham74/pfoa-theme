# PFOA Task 4B: Footer and Site Shell

## Architecture

The footer is a conventional PHP template in `pfoa-theme/footer.php`. It uses
the existing site identity/custom-logo support, WordPress navigation APIs, the
existing `pfoa_get_donation_url()` setting, and a small set of sanitized footer
Customizer values. It does not depend on Popup Maker, ThemeBlvd, a page
builder, or a new content model.

The footer has four desktop groups: identity, Explore PFOA navigation, Visit &
contact, and Connect & support. The legal row is separate and contains the
current year, the WordPress site name, and an optional legal menu.

## Menus and configuration

The theme keeps the existing `footer` location and labels it **Footer Menu —
Explore PFOA**. It adds:

- **Footer Menu — Ways to Help** (`footer_support`) for optional support,
  volunteer, membership, or other help links.
- **Footer Legal Menu** (`footer_legal`) for approved legal or utility links.

Assign menus under **Appearance → Menus** after activation. The existing menu
objects are preserved, but WordPress theme-location assignments may need to be
reassigned when the theme is activated.

The **PFOA Footer** Customizer section manages public hours, mailing and
physical addresses, phone, fax, and optional Facebook and Instagram URLs. The
initial contact defaults reflect the established project baseline and can be
updated or cleared by an administrator. The existing **PFOA Header → Donate
destination** setting remains the only theme-owned Donate URL mechanism; the
footer uses the same setting and its existing internal fallback.

## Responsive behavior

The footer uses four columns at the wide desktop breakpoint, two columns with
the existing compact/tablet shell below 1320px, and one stacked column below
720px. It uses the existing gutter and spacing tokens, has no fixed height, and
allows labels, addresses, and menu items to wrap naturally.

## Accessibility and fallback behavior

- The footer uses a native `<footer>` landmark, labeled navigation regions,
  semantic `<address>` content, normal links, and visible text names for social
  links.
- Links remain underlined or visibly contained, and footer controls use the
  high-contrast white focus ring already used by the site shell.
- No external link is forced into a new tab by the theme.
- A custom logo is used when available; otherwise the escaped WordPress site
  name links home. The WordPress site tagline is shown only when it exists.
- If the Explore PFOA menu is not assigned, the theme omits that optional
  navigation group rather than enumerating WordPress Pages. Missing support
  and legal menus likewise omit those optional link groups; identity, contact,
  and Donate remain available.
- Empty contact or social settings are omitted without leaving empty labels or
  controls. Contact fields are escaped, and the phone is exposed as a `tel:`
  link when it contains a usable number.
- No privacy, map, social, payment, or other destination URL is invented in
  the template. Legal and social destinations are rendered only from approved
  WordPress menu/configuration data.

## Deferred staging/client checks

After activation on an authorized staging copy, assign the three footer menu
locations and confirm the approved footer contact/social roster. Verify the
footer at approximately 390px, 768px, 1024px, and 1440px, at 200% zoom, with
keyboard navigation, reduced motion, and a missing-logo state. Confirm that
the existing Donate handoff and representative Popup Maker/content pages still
work; no payment should be submitted during QA.
