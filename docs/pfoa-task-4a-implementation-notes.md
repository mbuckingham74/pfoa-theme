# Task 4A implementation notes

Task 4A adds the site header and responsive three-level navigation without
changing WordPress content, menus, plugins, or environment configuration.

## Site administration

- Assign the existing WordPress menu to the theme's **Primary Menu** location
  under **Appearance → Menus**. The menu is rendered dynamically; the theme's
  one-link Home output is only a no-menu recovery fallback.
- Configure the header's **Donate destination** under **Appearance →
  Customize → PFOA Header**. Use the approved donation or donation-information
  URL. Until configured, the link resolves to the site's Membership Page when
  that Page exists, and otherwise to the homepage.
- Configure a PFOA-owned custom logo under **Appearance → Customize → Site
  Identity**. If no logo is configured, the site title links to the homepage.

Payment recipient IDs, PayPal settings, and donation form behavior remain owned
by WordPress or the approved donation integration; none are stored in the
theme.

## Interaction and fallback behavior

Desktop submenus open by pointer hover, keyboard focus, or their separate
disclosure button. Mobile navigation opens below the header and uses separate
disclosure buttons for every parent item, including the third level. Parent
links remain ordinary links at every depth.

The script is deferred and adds a `js` body class. With JavaScript unavailable,
the mobile menu remains in flow, all nested links remain visible, and the
disclosure-only controls are omitted from view so the links are still usable.

The header is sticky only at desktop widths. It accounts for the standard
WordPress admin-bar offset on desktop; tablet and mobile use normal document
flow.

## Responsive breakpoint decision

The compact/mobile header activates below **1320px**. The known PFOA menu has
eight top-level items, separate disclosure buttons, a real logo, and a Donate
action; keeping the full row through the earlier 1100px reference boundary
would make that combination depend on insufficient width. At 1320px and above,
the header uses the site's 1,280px canvas with a 16px safety gutter, bounds the
branding column to 10rem, and leaves roughly 960px for the menu beside Donate.
The desktop row remains single-line within that explicit budget. Below 1320px,
the in-flow disclosure layout provides the reliable fit without shrinking
labels, removing destinations, or introducing desktop horizontal scrolling.
