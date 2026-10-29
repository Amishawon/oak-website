export async function loadContent(signal){
 const config=await fetch('/config.json',{cache:'no-store',signal}).then(r=>{if(!r.ok)throw Error('Configuration unavailable');return r.json()});
 const mode=import.meta.env.VITE_CONTENT_MODE||config.contentMode;
 if(new URLSearchParams(location.search).get('oak-preview')==='1')sessionStorage.setItem('oak-preview','1');
 const preview=mode==='custom'&&sessionStorage.getItem('oak-preview')==='1';
 const url=mode==='custom'?(preview?'/cms/api.php?action=draft':(import.meta.env.VITE_CMS_ENDPOINT||config.cmsEndpoint)):mode==='wordpress'?(import.meta.env.VITE_CMS_ENDPOINT||config.cmsEndpoint):'/content.json';
 const response=await fetch(url,{cache:'no-store',signal,credentials:preview?'same-origin':'omit'});
 if(!response.ok)throw Error('Content is temporarily unavailable. Please call Oak.');
 const result=await response.json(),data=preview?result.draft:result;for(const key of ['services','team','blogs','faqs','careers','testimonials','home','legal'])if(!Array.isArray(data[key]))throw Error('Invalid CMS response');
 return {...data,mode,preview,indexable:!preview&&['custom','wordpress'].includes(mode)&&config.indexable===true};
}
export function safeUrl(value){try{const u=new URL(value,location.origin);return ['https:','http:'].includes(u.protocol)?u.href:''}catch{return ''}}
