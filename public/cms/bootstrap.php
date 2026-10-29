<?php
declare(strict_types=1);
const OAK_GROUPS = ['services','team','blogs','faqs','careers','testimonials','home','legal'];
function oak_dir(): string { return getenv('OAK_CMS_DATA_DIR') ?: __DIR__.'/private'; }
function oak_read(string $name): array {
    $path=oak_dir().'/'.$name.'.json';
    return is_file($path) ? json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR) : [];
}
function oak_write(string $name,array $data): void {
    $temp=tempnam(oak_dir(),'oak-');
    try {
        if(file_put_contents($temp,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR))===false) throw new RuntimeException('Cannot save content');
        chmod($temp,0600);
        if(!rename($temp,oak_dir().'/'.$name.'.json')) throw new RuntimeException('Cannot replace content');
    } finally { if(is_file($temp)) unlink($temp); }
}
function oak_public(): array {
    $state=oak_read('content');
    $data=$state['published']??[];
    foreach(OAK_GROUPS as $group) $data[$group]=array_values(array_filter($data[$group]??[],static function($row)use($group){return ($row['approved']??false)===true && ($row['status']??'published')==='published' && ($group!=='testimonials'||($row['consent']??false)===true);}));
    foreach($data['testimonials'] as &$row) if(empty($row['authenticRating'])) $row['rating']=0;
    return $data;
}
function oak_validate(array $data): void {
    foreach(OAK_GROUPS as $group) {
        if(!isset($data[$group])||!is_array($data[$group])||!array_is_list($data[$group])) throw new InvalidArgumentException('Invalid collection: '.$group);
        $slugs=[];
        foreach($data[$group] as $row) {
            if(!is_array($row)||!is_string($row['title']??null)||trim($row['title'])==='') throw new InvalidArgumentException('Every entry needs a title');
            $slug=$row['slug']??'';
            if(!is_string($slug)||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)||isset($slugs[$slug])) throw new InvalidArgumentException('Use unique lowercase slugs in '.$group);
            $slugs[$slug]=true;
            if(isset($row['status'])&&!in_array($row['status'],['draft','published'],true)) throw new InvalidArgumentException('Invalid entry status');
        }
    }
    if(!is_array($data['settings']??null)||!is_array($data['images']??null)) throw new InvalidArgumentException('Missing website settings');
    foreach(['title','phone','email','jobsEmail','fax','intro','bookingUrl'] as $key) if(!is_string($data['settings'][$key]??null)) throw new InvalidArgumentException('Invalid setting: '.$key);
    if(!is_array($data['settings']['locations']??null)) throw new InvalidArgumentException('Invalid locations');
    $sections=['Hero','About','Services','Stats','Why Choose Us 01','Team','Testimonials','FAQ','CTA'];
    foreach(['order','hidden'] as $key) if(isset($data['layout'][$key])&&(!is_array($data['layout'][$key])||count(array_unique($data['layout'][$key]))!==count($data['layout'][$key])||array_diff($data['layout'][$key],$sections))) throw new InvalidArgumentException('Use unique homepage section names from the default list');
    $booking=$data['settings']['bookingUrl']??'';
    if($booking!==''&&(!filter_var($booking,FILTER_VALIDATE_URL)||parse_url($booking,PHP_URL_SCHEME)!=='https')) throw new InvalidArgumentException('Booking link must use HTTPS');
    foreach(['primary','accent','background'] as $key) if(isset($data['theme'][$key])&&!preg_match('/^#[0-9a-fA-F]{6}$/D',$data['theme'][$key])) throw new InvalidArgumentException('Invalid theme color');
    if(!in_array($data['theme']['font']??'Poppins',['Poppins','Georgia','system-ui'],true)) throw new InvalidArgumentException('Invalid font');
}
