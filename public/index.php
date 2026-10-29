<?php
// Optional cPanel PHP gateway: real 404 statuses and server-visible route metadata.
$config=json_decode(file_get_contents(__DIR__.'/config.json'),true)?:[];
$content=[];$mode=$config['contentMode']??'mock';
if($mode==='custom'){
 require __DIR__.'/cms/bootstrap.php';
 if(!is_file(oak_dir().'/content.json')){http_response_code(503);echo 'Oak CMS is not configured.';exit;}
 $content=oak_public();
}elseif($mode==='wordpress'){
 $bootstrap=__DIR__.'/cms/wp-load.php';
 if(!is_file($bootstrap)){http_response_code(503);echo 'Oak CMS is not configured.';exit;}
 define('WP_USE_THEMES',false);require $bootstrap;
 if(!function_exists('oak_content')){http_response_code(503);echo 'Oak CMS plugin is not active.';exit;}
 $content=oak_content()->get_data();
}else{$content=json_decode(file_get_contents(__DIR__.'/content.json'),true)?:[];}
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));$path=rtrim($path,'/')?:'/';
$routes=['/'=>'Home','/about'=>'About Oak','/service'=>'Services','/blog'=>'Resources from Oak','/booking'=>'Request an Appointment','/contact'=>'Contact Oak','/testimonial'=>'What Clients Say About Oak','/programs'=>'Court and Community Programs','/oak-academy'=>'Oak Clinical Academy','/careers'=>'Careers at Oak','/referrals'=>'Professional Referrals','/team'=>'Meet the Team'];
$description=$content['settings']['intro']??'Oak Counseling Services, Inc.';
foreach(['services'=>'service','blogs'=>'blog','team'=>'team','legal'=>'legal'] as $group=>$prefix)foreach($content[$group]??[] as $item){$route='/'.$prefix.'/'.$item['slug'];$routes[$route]=$item['title'];if($path===$route&&!empty($item['excerpt']))$description=$item['excerpt'];}
$found=isset($routes[$path]);if(!$found)http_response_code(404);
$title=($found?$routes[$path]:'Page not found').' | Oak Counseling Services, Inc.';
$html=file_get_contents(__DIR__.'/index.html');
$html=preg_replace('/<title>.*?<\/title>/s','<title>'.htmlspecialchars($title,ENT_QUOTES,'UTF-8').'</title>',$html);
$html=preg_replace('/<meta name="description" content="[^"]*"\s*\/>/','<meta name="description" content="'.htmlspecialchars(strip_tags($description),ENT_QUOTES,'UTF-8').'"/>',$html);
if(in_array($mode,['custom','wordpress'],true)&&!empty($config['indexable'])&&$found&&!isset($_GET['oak-preview']))$html=str_replace('noindex,nofollow','index,follow',$html);
header('Cache-Control: no-store');echo $html;
