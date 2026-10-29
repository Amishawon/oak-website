<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$_SERVER['HTTP_HOST']='127.0.0.1:8088';require '/var/www/html/wp-load.php';
$action=$argv[1]??'';$id=(int)get_option('oak_browser_fixture');
if($action==='create'){$id=wp_insert_post(['post_type'=>'oak_service','post_status'=>'publish','post_name'=>'integration-test-service','post_title'=>'Integration test service','post_excerpt'=>'Initial public content','post_content'=>'Initial public content']);update_option('oak_browser_fixture',$id);update_post_meta($id,'oak_approved',true);update_post_meta($id,'oak_category','Therapy');}
if($action==='edit'&&$id)wp_update_post(['ID'=>$id,'post_excerpt'=>'Updated live through WordPress','post_content'=>'Updated live through WordPress']);
if($action==='unpublish'&&$id)wp_update_post(['ID'=>$id,'post_status'=>'draft']);
if($action==='clean'&&$id){wp_delete_post($id,true);delete_option('oak_browser_fixture');}
echo "Fixture $action complete.\n";
