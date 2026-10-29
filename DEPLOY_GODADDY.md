# Oak deployment on GoDaddy cPanel

The current CMS is standalone PHP. WordPress and a database are not required.

1. Back up the existing document root. Use an isolated staging hostname and PHP 8.3+ with Apache rewrite/authorization, sessions, fileinfo and JSON.
2. Build and upload `dist/`, including the `.htaccess` files. Preserve any existing `cms/private/` runtime store and `uploads/` on subsequent deployments. Never replace live data with a fresh seed.
3. Follow [CMS_ADMIN_GUIDE.md](CMS_ADMIN_GUIDE.md) for private-storage configuration, CLI account setup and login at `/admin/`.
4. Set config.json to custom mode and the `/cms/api.php?action=content` endpoint. Keep indexable false on staging.
5. Save and preview drafts, then publish approved content. Check public and private routes signed out, upload restrictions, login, CSRF rejection, draft exclusion, consent checks, stale-save conflicts, deep-route HTTP statuses, and storage backups on the actual server.
6. Confirm all pages and responsive behavior, images, fonts, navigation, contact links and authorized booking link.

No files have been uploaded or DNS changed by this implementation. Production upload and domain changes require explicit authorization. Before launch, confirm client content approval, a working backup/restore, HTTPS, indexing configuration and approved scheduling details. The reference design still requires screenshots because its URL could not be accessed during implementation.

The frontend is static; the editor API and routing gateway run PHP on cPanel. This is a root-hostname deployment. Subfolder deployment requires coordinated router, Vite base and endpoint changes. Backup private data and uploads separately from source ZIPs. Older WordPress deployment instructions and Docker integration tests do not apply to the standalone CMS.
