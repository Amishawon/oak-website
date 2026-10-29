<?php
/**
 * Plugin Name: Oak Counseling Content
 * Description: Structured Oak website content, public read-only API, and draft content import.
 * Version: 1.0.0
 * Requires PHP: 8.0
 */
if (!defined('ABSPATH')) exit;
function oak_types(){return ['services'=>'oak_service','team'=>'oak_team','testimonials'=>'oak_review','blogs'=>'oak_blog','faqs'=>'oak_faq','careers'=>'oak_career','home'=>'oak_home','legal'=>'oak_legal'];}
function oak_fields(){return ['approved'=>'boolean','category'=>'string','role'=>'string','image'=>'string','consent'=>'boolean','authenticRating'=>'boolean','rating'=>'integer'];}
add_action('init',function(){
 foreach(oak_types() as $label=>$type){
  register_post_type($type,['labels'=>['name'=>ucfirst($label),'singular_name'=>ucfirst($label)],'public'=>false,'show_ui'=>true,'show_in_rest'=>true,'supports'=>['title','editor','excerpt','thumbnail','custom-fields','page-attributes'],'menu_icon'=>'dashicons-leaf','capability_type'=>'post','map_meta_cap'=>true]);
  foreach(oak_fields() as $key=>$kind)register_post_meta($type,'oak_'.$key,['single'=>true,'type'=>$kind,'show_in_rest'=>true,'sanitize_callback'=>function($v)use($kind,$key){if($kind==='boolean')return rest_sanitize_boolean($v);if($kind==='integer')return max(0,min(5,absint($v)));return $key==='image'?esc_url_raw($v):sanitize_text_field($v);},'auth_callback'=>function($allowed,$key,$id){return current_user_can('edit_post',$id);}]);
 }
});
add_action('add_meta_boxes',function(){foreach(oak_types() as $type)add_meta_box('oak_fields','Oak website fields','oak_fields_box',$type,'normal');});
function oak_fields_box($post){wp_nonce_field('oak_save','oak_nonce');echo '<p>Publish only verified content. Approved must be checked for public API visibility. Reviews additionally require consent.</p>';foreach(oak_fields() as $k=>$kind){$v=get_post_meta($post->ID,'oak_'.$k,true);echo '<p><label>'.esc_html($k).' ';if($kind==='boolean')echo '<input type="checkbox" name="oak_'.esc_attr($k).'" value="1" '.checked($v,true,false).'>';else echo '<input style="width:100%" name="oak_'.esc_attr($k).'" type="'.($kind==='integer'?'number':'text').'" value="'.esc_attr($v).'" '.($kind==='integer'?'min="0" max="5"':'').'>';echo '</label></p>';}echo '<p>Use a media-library URL for image. Category: Therapy, Programs or Academy. Homepage slug hero/about controls its text. Ordering uses the Order field.</p>';}
add_action('save_post',function($id){if(!isset($_POST['oak_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['oak_nonce'])),'oak_save')||!current_user_can('edit_post',$id)||wp_is_post_autosave($id)||wp_is_post_revision($id))return;if(!in_array(get_post_type($id),oak_types(),true))return;foreach(oak_fields() as $k=>$kind){$v=isset($_POST['oak_'.$k])?wp_unslash($_POST['oak_'.$k]):false;update_post_meta($id,'oak_'.$k,$kind==='boolean'?(bool)$v:($kind==='integer'?max(0,min(5,absint($v))):($k==='image'?esc_url_raw($v):sanitize_text_field($v))));}});
function oak_default_settings(){return ['title'=>'Oak Counseling Services, Inc.','phone'=>'860-316-0262','email'=>'info@oakcounselingservices.com','jobsEmail'=>'Jobs@oakcounselingservices.com','fax'=>'860-318-7442','intro'=>'Oak Counseling Services, Inc. offers collaborative counseling, medication services, and community support.','bookingUrl'=>'','locations'=>['12 Curtis St., Suite 24, Meriden, CT 06450','185 Silas Deane Hwy, 3rd Floor, Wethersfield, CT 06109']];}
add_action('admin_menu',function(){add_options_page('Oak Website','Oak Website','manage_options','oak-website','oak_settings_page');});
function oak_settings_page(){
 if(!current_user_can('manage_options'))return;
 if(isset($_POST['oak_settings_nonce'])&&wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['oak_settings_nonce'])),'oak_settings')){
  $s=oak_default_settings();foreach($s as $k=>$v){$raw=wp_unslash($_POST[$k]??'');$s[$k]=$k==='locations'?array_values(array_filter(array_map('sanitize_text_field',explode("\n",$raw)))):($k==='bookingUrl'?esc_url_raw($raw,['https']):(in_array($k,['email','jobsEmail'])?sanitize_email($raw):sanitize_text_field($raw)));}update_option('oak_settings',$s);echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
 }
 if(isset($_POST['oak_import_nonce'])&&wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['oak_import_nonce'])),'oak_import')){oak_import_seed();echo '<div class="notice notice-success"><p>Missing seed entries imported as drafts. Existing content was not overwritten.</p></div>';}
 $s=wp_parse_args(get_option('oak_settings',[]),oak_default_settings());echo '<div class="wrap"><h1>Oak Website</h1><form method="post">';wp_nonce_field('oak_settings','oak_settings_nonce');foreach($s as $k=>$v){echo '<p><label>'.esc_html($k).'<br><textarea rows="2" cols="80" name="'.esc_attr($k).'">'.esc_textarea(is_array($v)?implode("\n",$v):$v).'</textarea></label></p>';}submit_button('Save settings');echo '</form><hr><h2>Import supplied development content</h2><p>All seed entries are drafts and unapproved. Review before publishing. Images reference the frontend /assets directory; upload replacement photos to WordPress Media.</p><form method="post">';wp_nonce_field('oak_import','oak_import_nonce');submit_button('Import missing drafts');echo '</form></div>';
}
function oak_import_seed(){
 $data=json_decode(file_get_contents(__DIR__.'/seed.json'),true);if(!$data)return;
 foreach(oak_types() as $group=>$type)foreach($data[$group]??[] as $index=>$item){$slug=sanitize_title($item['slug']??$item['title']);if(get_page_by_path($slug,OBJECT,$type))continue;$id=wp_insert_post(['post_type'=>$type,'post_status'=>'draft','post_name'=>$slug,'post_title'=>sanitize_text_field($item['title']),'post_content'=>wp_kses_post($item['body']??''),'post_excerpt'=>sanitize_textarea_field($item['excerpt']??''),'menu_order'=>$index]);if(is_wp_error($id)||!$id)continue;foreach(oak_fields() as $k=>$kind)update_post_meta($id,'oak_'.$k,$k==='approved'?false:($item[$k]??($kind==='boolean'?false:($kind==='integer'?0:''))));}
 if(!get_option('oak_settings'))update_option('oak_settings',oak_default_settings());
}
function oak_content(){
 $seed=json_decode(file_get_contents(__DIR__.'/seed.json'),true);$out=['settings'=>wp_parse_args(get_option('oak_settings',[]),oak_default_settings()),'images'=>$seed['images']??[],'copy'=>[]];
 foreach(oak_types() as $group=>$type){$out[$group]=[];$posts=get_posts(['post_type'=>$type,'post_status'=>'publish','has_password'=>false,'numberposts'=>-1,'orderby'=>'menu_order title','order'=>'ASC','meta_query'=>[['key'=>'oak_approved','value'=>'1']]]);foreach($posts as $p){$row=['id'=>$p->ID,'slug'=>$p->post_name,'title'=>$p->post_title,'body'=>wp_kses_post(wpautop($p->post_content)),'excerpt'=>$p->post_excerpt];foreach(oak_fields() as $k=>$kind){$v=get_post_meta($p->ID,'oak_'.$k,true);$row[$k]=$kind==='boolean'?(bool)$v:($kind==='integer'?(int)$v:(string)$v);}if($group==='testimonials'&&!$row['consent'])continue;if(!$row['authenticRating'])$row['rating']=0;$out[$group][]=$row;}}
 $response=new WP_REST_Response($out,200);$response->header('Cache-Control','no-store, max-age=0');return $response;
}
add_action('rest_api_init',function(){register_rest_route('oak/v1','/content',['methods'=>'GET','callback'=>'oak_content','permission_callback'=>'__return_true']);});
// The custom endpoint is the only anonymous content surface. Core CRUD routes
// remain available to authenticated editors, including the block editor.
add_filter('rest_pre_dispatch',function($result,$server,$request){if(preg_match('#^/wp/v2/oak_[a-z]+(?:/|$)#',$request->get_route())&&!current_user_can('edit_posts'))return new WP_Error('oak_private_editor_api','Use the public Oak content endpoint.',['status'=>401]);return $result;},10,3);
