<?php
// Local CLI integration test only; never executable through HTTP.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$_SERVER['HTTP_HOST']='127.0.0.1:8088';
define('WP_INSTALLING',true);
require '/var/www/html/wp-load.php';
require_once ABSPATH.'wp-admin/includes/upgrade.php';
if(!is_blog_installed())wp_install('Oak Local CMS','oak-local-admin','local@example.test',false,'',bin2hex(random_bytes(24)));
require_once ABSPATH.'wp-admin/includes/plugin.php';activate_plugin('oak-cms/oak-cms.php');
// Plugin init runs on the next request after activation.
echo "Local WordPress installed and plugin activated.\n";
