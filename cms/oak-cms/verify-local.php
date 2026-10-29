<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$_SERVER['HTTP_HOST']='127.0.0.1:8088';
require '/var/www/html/wp-load.php';
function check($condition,$message){if(!$condition){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";}
oak_import_seed();
$all=get_posts(['post_type'=>array_values(oak_types()),'post_status'=>'any','numberposts'=>-1]);
check(count($all)>=40,'draft seed import');
$count=count($all);oak_import_seed();check(count(get_posts(['post_type'=>array_values(oak_types()),'post_status'=>'any','numberposts'=>-1]))===$count,'import idempotency');
wp_set_current_user(0);$r=rest_do_request('/oak/v1/content');check($r->get_status()===200,'anonymous public GET');$d=$r->get_data();check(count($d['services'])===0,'draft content excluded');
$id=wp_insert_post(['post_type'=>'oak_service','post_status'=>'publish','post_title'=>'Local integration fixture','post_name'=>'local-integration-fixture','post_content'=>'Initial content']);
check(count(rest_do_request('/oak/v1/content')->get_data()['services'])===0,'published but unapproved content excluded');
update_post_meta($id,'oak_approved',true);check(count(rest_do_request('/oak/v1/content')->get_data()['services'])===1,'approved published content returned');
wp_update_post(['ID'=>$id,'post_content'=>'Updated without frontend rebuild']);$data=rest_do_request('/oak/v1/content')->get_data();check(str_contains($data['services'][0]['body'],'Updated without frontend rebuild'),'edits visible without rebuild');
$review=wp_insert_post(['post_type'=>'oak_review','post_status'=>'publish','post_title'=>'Local test review']);update_post_meta($review,'oak_approved',true);check(count(rest_do_request('/oak/v1/content')->get_data()['testimonials'])===0,'review without consent excluded');
update_post_meta($review,'oak_consent',true);update_post_meta($review,'oak_rating',5);$r=rest_do_request('/oak/v1/content')->get_data();check($r['testimonials'][0]['rating']===0,'unverified rating suppressed');
$request=new WP_REST_Request('POST','/wp/v2/oak_service');$request->set_param('title','Unauthorized');check(rest_do_request($request)->get_status()===401,'anonymous content mutation denied');
wp_update_post(['ID'=>$id,'post_status'=>'draft']);check(count(rest_do_request('/oak/v1/content')->get_data()['services'])===0,'unpublishing removes public content');
wp_delete_post($id,true);wp_delete_post($review,true);echo "Local WordPress integration checks complete.\n";
