# Oak custom CMS

This is a standalone Oak editor, with no WordPress installation or plugin required. The React website keeps Oak's existing content, branding and routes.

## Server requirements and installation

Use Apache/cPanel with PHP 8.3+, JSON, sessions and fileinfo enabled. No database or Node server is required on hosting. HTTPS is required for deployment. The CMS is intended for a single administrator and modest website traffic.

1. Build with `npm.cmd run build` on Windows, or `npm run build` elsewhere. Upload the contents of `dist/` to an isolated hostname's document root, including hidden `.htaccess` files.
2. Ensure PHP can write `cms/private/` and `uploads/`. Do not use world-writable permissions. Prefer setting `OAK_CMS_DATA_DIR` to an absolute private directory outside the document root in the PHP environment. If using the bundled private directory, verify `/cms/private/account.json` returns HTTP 403 before setup or entering credentials. This package requires Apache authorization rules; do not serve its private directory through a static server or PHP's development server on a public interface.
3. In cPanel Terminal, set environment variables `OAK_ADMIN_EMAIL` and `OAK_ADMIN_PASSWORD` (at least 14 characters), then run `php cms/setup.php` from the uploaded root. Supply credentials through the environment, not through browser URLs or source files. Unset them afterward. Setup refuses to overwrite an existing account. It imports supplied content as drafts and publishes nothing.
4. Visit `/admin/` and sign in. If Terminal is unavailable, ask the hosting administrator to run the setup command; there is deliberately no anonymous web setup page.
5. Change `config.json` to `{"contentMode":"custom","cmsEndpoint":"/cms/api.php?action=content","indexable":false}`. There must be no `VITE_CONTENT_MODE=mock` override in the production build. Remove `.env.local` overrides before building.
6. Review draft entries. Set an entry to Published and check Content reviewed and approved when ready. Save draft, Preview draft, and finally Publish website. Until first publication, the public API returns 503.
7. Test the public website signed out, including newly published detail routes, draft exclusions and testimonial consent. Keep staging access restricted and indexing disabled until approved for launch.

## Editing

The sidebar includes Homepage, Services, Team, Resources, FAQs, Testimonials, Careers, Legal, Website settings, Website images and Appearance. Edit an entry's title, slug, summary, HTML body, image, and publication status. Team entries have roles, services have categories, and testimonials require verified consent. HTML is sanitized when rendered by the public website. Keep homepage slugs hero, about, services, team, faq and cta because these connect to the existing components.

Move earlier / Move later changes collection order. Appearance edits brand and accent colors, the page background, font, and homepage section order/visibility. The valid homepage section names are Hero, About, Services, Stats, Why Choose Us 01, Team, Testimonials, FAQ and CTA. Enter one name per line. For example, put Team before Services to move that section earlier. Hidden sections can be restored by removing their names from the hidden list. This is a structured website editor, not a freeform drag-and-drop page builder.

Images accepts JPEG, PNG and WebP uploads up to 5 MB. Entry uploads fill the entry image automatically. In Website images, copy the returned URL to the appropriate field. Multiple-image fields use one URL per line. SVG uploads are deliberately unsupported; the supplied Oak SVG logo stays bundled.

Save draft stores edits without changing public content. Preview draft opens a private preview in another tab and follows the draft across page navigation; save changes before opening it. Use Exit preview to return to published content. Publish website saves and publishes the entire current draft. Only Published + Approved entries are visible, and reviews additionally need Consent. Changing an entry back to Draft and publishing removes it publicly. New visitors see published changes immediately; open pages refresh on focus or within 60 seconds.

The editor rejects stale saves when another session has changed content. Copy any important unsaved text elsewhere, reload, and reapply it. Unsaved work prompts before closing the editor. Sessions expire after one hour of inactivity.

## Backup and account recovery

Back up the private data directory, uploads, config.json and deployed assets. Never include account.json, sessions or runtime drafts in a public/source ZIP. Restore a matching backup to an isolated staging environment and verify it before using it live. Redeploying a build must preserve existing private data and uploads. Default seed content is not a backup of live edits.

For account recovery, the hosting administrator can replace only the account password hash using PHP's password_hash with a new strong password, while keeping the account JSON structure. There is no email password reset or multi-user role management in this release. Never delete the content store to reset a password.

The CMS uses server-side sessions, password hashes, CSRF tokens, IP login throttling, write locks, atomic file replacement and revision checks. It is for website content, not patient records or appointment submissions.

## Local validation

The frontend can run in mock mode with `npm.cmd run dev`. For the real PHP integration, use an Apache/PHP staging environment and the installation above. Vite proxies `/cms/api.php` to localhost:8089 when a local PHP backend is available. Existing WordPress files/tests under `cms/oak-cms` are legacy migration artifacts, not part of this standalone CMS's installation.
