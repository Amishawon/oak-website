# Oak independent website QA and migration report

## Standalone CMS update — 26 October 2026

The current editor at `/admin/` uses standalone PHP and does not require WordPress. The earlier report below describes the previous WordPress implementation and is historical evidence only.

Current checks: the four content tests and Vite production build pass. `tests/standalone-editor.mjs` passes in installed Chrome with an explicitly mocked API, covering login errors, content editing, draft isolation, publishing, responsive layout at 390/768/1280 pixels, appearance settings, homepage section order/visibility, private preview navigation and sign-out. Desktop and mobile editor screenshots were captured; desktop was visually inspected. No browser JavaScript errors occurred. Run with `OAK_BROWSER_CHANNEL=chrome` where the default Playwright browser is unavailable.

The standalone PHP API has not been executed or linted in this environment: PHP is unavailable and the Docker daemon is stopped. Its authentication, storage permissions, file uploads, Apache access rules, server metadata/statuses and publication filtering require real PHP/Apache integration verification before deployment. Existing WordPress test results do not verify the new CMS. No hosting upload or live migration has occurred.

The Therapy by Grace URL was inaccessible through both the web tool and direct HTTP access. Oak's current public visual design was preserved; matching that reference remains pending screenshots or accessible reference material.

---

Updated 15 October 2026. Status: runnable local implementation and successful production build; not deployed to GoDaddy.

## Implemented

React/Vite routes cover all 15 migration page scopes, including service/blog/legal detail templates, plus team listing/profile routes. Shared header, footer, CTA, FAQ, service cards, team cards, review slideshow and six-card testimonial grid are implemented as editable source components. The original primary section inventory is retained in section-inventory.json; page content follows that sequence. This is an independent reconstruction, not a Framer source export or a pixel-identical certification.

Oak logo, colors, Poppins, source-packet content and localized design assets are included. Desktop/tablet/phone layouts, mobile menu, accordions, review controls, hover effects, gallery motion, counter animation and reduced-motion support are implemented. No screenshots are used as page sections. The original Framer project was not modified during this implementation.

The WordPress plugin provides Services, Team, Testimonials, Blogs, FAQs, Careers, Home and Legal content types and global settings. Published approved content is read at runtime; new detail routes and content edits do not require a React rebuild. Six pending review slots remain clearly labeled until consented reviews are available. No invented ratings appear. Appointment and inquiry forms remain disabled; a configurable authorized HTTPS scheduling link is supported.

## Executed checks

| Check | Result |
|---|---|
| npm install | Completed; lockfile generated; installation audit reported zero vulnerabilities |
| npm run build | Passed on Node 26.3.0 / Vite 7.3.7 |
| Content tests | 4 passed: unique slugs/content, review/booking safety, local asset existence, source inventory |
| Browser route tests | 41 routes × 390/768/1200 px = 123 checks passed |
| Browser errors | No JavaScript page errors, horizontal overflow or broken images detected |
| Runtime independence | No Framer/Figma network requests during route tests |
| Interaction smoke tests | Mobile navigation, review previous/next and unknown-route view passed |
| WordPress PHP syntax | Plugin passed PHP lint |
| WordPress integration | Draft seed import, idempotency, public GET, draft/unapproved exclusion, publish/edit/unpublish, consent and rating protection, anonymous mutation denial passed |
| React + real WordPress | New service route, edit on focus, unpublish to not-found and CMS outage handling passed without rebuild |
| Apache/PHP responses | Three valid routes returned 200; three missing/404 routes returned 404 with matching HTML titles |

Evidence is in test-results/browser.json, cms-browser.json, apache.json and responsive PNG captures. The original WordPress integration test output was inspected in the task. Local PHP installation emitted a sendmail-unavailable warning; no email-delivery functionality is claimed. Vite reported React Router use-client directives ignored during bundling; compilation succeeded.

## Visual and functional limits

Desktop and mobile homepage captures were visually inspected and corrected for image-slot mapping and hero text overlap. Automated viewport checks cover all routes, but they do not establish exact visual equivalence to every original Framer page or animation. The Figma connector returned “nothing selected” for the supplied node, so saved Framer structures and reference captures were used. Detailed side-by-side visual acceptance remains recommended before release.

The independent implementation preserves primary section roles and order, but simplifies some internal Framer component variants and motion into CSS/React equivalents. It should not be described as a lossless conversion. Full keyboard/screen-reader auditing and cross-browser/device testing beyond Chromium remain outstanding.

Homepage CMS currently connects hero/about title/body and Services/Team/FAQ/CTA titles. Other structural copy and design changes remain source edits. FAQ displays the first four approved entries to preserve the original accordion structure. WordPress image fields support URLs; featured-image selection is not currently mapped to the frontend image field.

The PHP gateway supplies server-visible titles/descriptions and real status codes; full article markup is rendered by React. Advanced SEO features such as an automatically generated CMS sitemap and social-image metadata are not included in this release.

## Release dependencies

- Actual GoDaddy PHP/database configuration, staging upload, SSL, rewrite behavior, account permissions and restore test are unverified.
- Root-relative URLs require a dedicated document root; arbitrary subfolder hosting is not configured.
- SimplePractice authorized link/widget is still missing. Link integration is prepared; arbitrary widget scripts are not accepted.
- Authentic reviews and consent, final staff verification/photos, policy text/effective dates and article review remain content dependencies.
- Default production bundle is a noindex mock-data preview, not approved public clinical content. Switch to WordPress mode and remove mock content on approved production release.
- Source backup ZIP does not contain the client's hosting files or database. A hosting/database backup must be made before any authorized deployment.

No DNS changes, live-domain upload or client-hosting deployment occurred.

## Navigation refresh — 15 October 2026

Rebuilt the header with a centered 72px desktop/70px mobile bar, neutral small type, Oak SVG, centered primary links and dark pill appointment CTA. All existing destinations remain accessible through primary links and More. CTA remains visible when mobile links collapse. No language or login control was invented. Production build passed. Browser inspection verified desktop bounds at 1200px and mobile menu/CTA at 390px; Escape and route-change dismissal passed. This focused check does not rerun the earlier full-site suite.

## Compact navigation refinement — 2026-10-15
Replaced the prior navigation styling with a 60px near-white header, Oak logo, three disclosure groups plus Contact, and a dark appointment pill. All navigation destinations remain available. No sign-in or language controls were invented.
Production build passed. Focused browser checks at 1200px and 390px confirmed no horizontal overflow, 60px header, exclusive desktop dropdowns, mobile CTA visibility, route navigation closing the menu, and Escape dismissal. The mobile regression script now expands About Oak before selecting About. The complete route suite was not rerun for this navigation-only refinement; earlier full-suite results above remain historical. No deployment performed.

## Full-suite rerun after navigation changes — 2026-10-22
Rebuilt (`npm run build`, passed; the rebuild also cleared stray Docker `wp-config.php` and `cms` files from `dist`). Content tests: 4 passed. Browser suite (`tests/browser.mjs`): 41 routes × 390/768/1200 px passed with no overflow, broken images, JavaScript errors or Framer/Figma requests; mobile menu, review controls and unknown-route view passed. WordPress/Docker and Apache checks were not rerun. No deployment performed.

## Visual comparison against saved Framer captures — 2026-10-22
Compared current captures with `oak-home-after.jpg` (original). Found that CMS-driven headings (hero, About, Services, Team, CTA) lost the red italic accent words used in the original, because the plain CMS title replaced the styled heading. Restored accents via an `accent()` helper (hero "for the changes"-style trailing words, "Wellbeing", "fits your life", "Experts", "Next Step"). Build and 41-route × 3-width browser suite passed afterward. Intentional differences remain: development-preview banner, six-card review grid with pending placeholders, template badges and zero-state counters in the original capture not reproduced. Hero layout (text panel beside image, not text over image) differs from the original and has not been changed. Exact pixel equivalence is not claimed.

Correction (2026-10-22): the original hero is also a text panel with the photo in the same card, so the layout already matched. Hero headline now accents "for the changes" as in the original and the heading max-width was widened. Build, content tests and the 41-route × 3-width browser suite passed. Only the homepage has been compared against a saved original capture.
