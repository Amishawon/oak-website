<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function reply(int $status,array $data): never { http_response_code($status); echo json_encode($data,JSON_THROW_ON_ERROR); exit; }
try {
    if(!is_file(oak_dir().'/account.json')) reply(503,['error'=>'CMS setup is required.']);
    $action=$_GET['action']??'content'; $method=$_SERVER['REQUEST_METHOD'];
    if($action==='content'&&$method==='GET') {
        if(empty(oak_read('content')['published'])) reply(503,['error'=>'Website content has not been published yet.']);
        reply(200,oak_public());
    }
    ini_set('session.use_strict_mode','1');
    session_name('oak_editor');
    session_set_cookie_params(['httponly'=>true,'secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'),'samesite'=>'Strict','path'=>'/']);
    session_start();
    if(!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    if($action==='session'&&$method==='GET') reply(200,['authenticated'=>!empty($_SESSION['editor'])&&time()-($_SESSION['seen']??0)<3600,'csrf'=>$_SESSION['csrf']]);
    if($method!=='POST'&&$method!=='GET') reply(405,['error'=>'Method not allowed']);
    if($method==='POST'&&!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??'')) reply(403,['error'=>'Session expired. Reload the editor.']);
    if((int)($_SERVER['CONTENT_LENGTH']??0)>9*1024*1024) reply(413,['error'=>'Request is too large.']);
    $body=$action==='upload'?[]:json_decode(file_get_contents('php://input')?:'{}',true,512,JSON_THROW_ON_ERROR);
    if($action==='login'&&$method==='POST') {
        $lock=fopen(oak_dir().'/login.lock','c+'); flock($lock,LOCK_EX);
        $attempts=oak_read('attempts'); $key=hash('sha256',$_SERVER['REMOTE_ADDR']??'unknown');
        $recent=array_values(array_filter($attempts[$key]??[],fn($t)=>$t>time()-900));
        if(count($recent)>=10) reply(429,['error'=>'Too many sign-in attempts. Try again in 15 minutes.']);
        $account=oak_read('account');
        if(strtolower($body['email']??'')!==$account['email']||!password_verify($body['password']??'',$account['password'])) {
            $recent[]=time(); $attempts[$key]=$recent; oak_write('attempts',$attempts); reply(401,['error'=>'Email or password is incorrect.']);
        }
        unset($attempts[$key]); oak_write('attempts',$attempts); flock($lock,LOCK_UN); fclose($lock);
        session_regenerate_id(true); $_SESSION['editor']=true; $_SESSION['seen']=time(); $_SESSION['csrf']=bin2hex(random_bytes(32));
        reply(200,['csrf'=>$_SESSION['csrf']]);
    }
    if(empty($_SESSION['editor'])||time()-($_SESSION['seen']??0)>=3600) reply(401,['error'=>'Sign in to continue.']);
    $_SESSION['seen']=time();
    if($action==='logout'&&$method==='POST') { $_SESSION=[]; session_destroy(); reply(200,['ok'=>true]); }
    if($action==='draft'&&$method==='GET') reply(200,oak_read('content'));
    if($action==='save'&&$method==='POST'||$action==='publish'&&$method==='POST') {
        $lock=fopen(oak_dir().'/content.lock','c+'); flock($lock,LOCK_EX); $state=oak_read('content');
        if(($body['revision']??null)!==$state['revision']) reply(409,['error'=>'Content changed in another editor. Reload before saving.']);
        oak_validate($body['draft']);
        $state['draft']=$body['draft']; $state['revision']++;
        if($action==='publish') $state['published']=$body['draft'];
        oak_write('content',$state); flock($lock,LOCK_UN); fclose($lock); reply(200,$state);
    }
    if($action==='upload'&&$method==='POST') {
        $file=$_FILES['image']??null;
        if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>5*1024*1024) reply(400,['error'=>'Choose an image under 5 MB.']);
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if(!isset($types[$mime])||!getimagesize($file['tmp_name'])) reply(400,['error'=>'Use a JPEG, PNG, or WebP image.']);
        $name=bin2hex(random_bytes(16)).'.'.$types[$mime];
        if(!move_uploaded_file($file['tmp_name'],__DIR__.'/../uploads/'.$name)) throw new RuntimeException('Upload failed');
        reply(200,['url'=>'/uploads/'.$name]);
    }
    reply(404,['error'=>'Unknown action']);
} catch(InvalidArgumentException|JsonException $e) { reply(400,['error'=>$e->getMessage()]); }
catch(Throwable $e) { error_log((string)$e); reply(500,['error'=>'Could not complete the request. Check server storage and try again.']); }
