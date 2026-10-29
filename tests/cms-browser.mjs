import {chromium} from '@playwright/test';import {execFileSync} from 'node:child_process';import fs from 'node:fs';
const action=a=>execFileSync('docker',['compose','exec','-T','wordpress','php','/var/www/html/wp-content/plugins/oak-cms/browser-fixture.php',a],{encoding:'utf8'});
action('clean');action('create');const browser=await chromium.launch();const page=await browser.newPage();const results=[];
try{
await page.route('**/config.json',r=>r.fulfill({json:{contentMode:'wordpress',cmsEndpoint:'/cms/wp-json/oak/v1/content',indexable:false}}));
await page.goto('http://127.0.0.1:4173/service/integration-test-service');await page.getByRole('heading',{name:'Integration test service',exact:true}).waitFor();results.push('New published WordPress service route renders without rebuild');
action('edit');await page.evaluate(()=>window.dispatchEvent(new Event('focus')));await page.locator('.detail-body').getByText('Updated live through WordPress',{exact:true}).waitFor();results.push('WordPress edit reaches React after focus refresh without rebuild');
action('unpublish');await page.evaluate(()=>window.dispatchEvent(new Event('focus')));await page.getByRole('heading',{name:/Wrong Turn/}).waitFor();results.push('Unpublished service becomes not found without rebuild');
await page.route('**/cms/wp-json/**',r=>r.fulfill({status:503,body:'Unavailable'}));await page.reload();await page.getByRole('heading',{name:'Content temporarily unavailable'}).waitFor();results.push('CMS outage produces error state instead of mock fallback');
fs.writeFileSync('test-results/cms-browser.json',JSON.stringify(results,null,2));console.log(results.join('\n'));
}finally{await browser.close();action('clean');}
