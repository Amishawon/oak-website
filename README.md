# Oak Counseling Services website

React + Vite website with Oak's branding, content and routes, plus a standalone custom PHP CMS at `/admin/`. No WordPress, database or running Node server is required on cPanel.

The CMS edits homepage copy, services, team, resources, FAQs, testimonials, careers, legal policies, contact details, images, colors, fonts and homepage section order/visibility. It supports draft saving, authenticated website preview and explicit publishing. The public design follows the supplied Therapy by Grace reference with forest green actions, pale blue sections, serif headings, a portrait hero, and stacked service rows. Oak content, routes, and CMS controls are retained.

## Local frontend

```sh
npm ci
npm run dev
npm test
npm run build
npm run preview -- --port 4173
```

On Windows use `npm.cmd` when PowerShell execution policy blocks npm.ps1. The default config uses explicit development mock content with indexing disabled. Mock mode lets you view the site without PHP; the CMS itself needs a PHP server.

## Standalone CMS

See [CMS_ADMIN_GUIDE.md](CMS_ADMIN_GUIDE.md) for requirements, secure account setup, publishing and backups, and [DEPLOY_GODADDY.md](DEPLOY_GODADDY.md) for staging. The public API is `/cms/api.php?action=content`. Set config.json contentMode to custom after installation. The frontend refreshes content on load, on focus and every 60 seconds, without a rebuild.

- `public/admin/`: standalone editor UI
- `public/cms/`: PHP authentication, storage, API and CLI setup
- `public/cms/private/`: protected runtime account/content storage, excluded from source control
- `public/uploads/`: website media, excluded from source control
- `public/index.php`: server metadata and HTTP status gateway
- `src/`: public website, routes, styles and CMS adapter
- `public/content.json`: supplied development seed; entries are imported as drafts
- `cms/oak-cms/`, `docker-compose.yml`, `tests/cms-browser.mjs`: previous WordPress migration artifacts, retained for reference only

No live migration from an existing WordPress database is performed. If a deployed WordPress CMS contains edits absent from the supplied content.json, export and reconcile them before switching the public site.
