<?php
// CLI only: no web-based first-user takeover.
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/bootstrap.php';
if(is_file(oak_dir().'/account.json')) { fwrite(STDERR,"CMS already configured.\n"); exit(1); }
$email=getenv('OAK_ADMIN_EMAIL')?:'';
$password=getenv('OAK_ADMIN_PASSWORD')?:'';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<14) { fwrite(STDERR,"Set OAK_ADMIN_EMAIL and OAK_ADMIN_PASSWORD (14+ characters).\n"); exit(1); }
if(!is_dir(oak_dir())&&!mkdir(oak_dir(),0700,true)) throw new RuntimeException('Cannot create private storage');
$seed=json_decode(file_get_contents(__DIR__.'/../content.json'),true,512,JSON_THROW_ON_ERROR);
foreach(OAK_GROUPS as $group) {
    foreach($seed[$group] as $index=>&$row) {
        $row['status']='draft';
        if(empty($row['slug'])) $row['slug']=trim(preg_replace('/[^a-z0-9]+/','-',strtolower($row['title'])),'-').'-'.($index+1);
    }
    unset($row);
}
unset($row);
$seed['theme']=['primary'=>'#3aad5c','accent'=>'#ec1652','background'=>'#ffffff','font'=>'Poppins'];
oak_validate($seed);
oak_write('content',['revision'=>1,'draft'=>$seed,'published'=>[]]);
oak_write('account',['email'=>strtolower($email),'password'=>password_hash($password,PASSWORD_DEFAULT)]);
echo "Oak CMS created. Sign in at /admin/. All imported entries are drafts.\n";
