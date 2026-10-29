import fs from 'node:fs';
const checks=[];
for(const [path,status] of [['/',200],['/service/individual-therapy',200],['/blog/calming-your-mind-in-stressful-times',200],['/missing-page',404],['/service/not-a-service',404],['/404',404]]){
 const response=await fetch('http://127.0.0.1:8089'+path);
 const html=await response.text();
 checks.push({path,expected:status,status:response.status,title:html.match(/<title>(.*?)<\/title>/)?.[1]});
 if(response.status!==status)throw Error(path+' returned '+response.status);
}
fs.writeFileSync('test-results/apache.json',JSON.stringify(checks,null,2));console.log(checks);
