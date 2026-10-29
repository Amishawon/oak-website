// Browser contract checks with an explicit API fixture. These do not test PHP.
import {chromium} from '@playwright/test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
const browser=await chromium.launch({headless:true,...(process.env.OAK_BROWSER_CHANNEL?{channel:process.env.OAK_BROWSER_CHANNEL}:{})});
const page=await browser.newPage();
const errors=[];page.on('pageerror',e=>errors.push(e.message));
let authenticated=false,revision=1,saved=JSON.parse(fs.readFileSync('public/content.json')),published=structuredClone(saved),calls=[];
const respond=(route,data,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify(data)});
await page.route('**/cms/api.php?*',async route=>{
 const action=new URL(route.request().url()).searchParams.get('action');calls.push(action);
 const data=route.request().postDataJSON();
 if(action==='session')return respond(route,{authenticated,csrf:'fixture-token'});
 if(action==='login'){if(data.password!=='fixture-password')return respond(route,{error:'Email or password is incorrect.'},401);authenticated=true;return respond(route,{csrf:'fixture-token'})}
 if(action==='logout'){authenticated=false;return respond(route,{ok:true})}
 if(action==='content')return respond(route,published);
 if(!authenticated)return respond(route,{error:'Sign in to continue.'},401);
 if(action==='draft')return respond(route,{revision,draft:saved,published});
 if(action==='save'||action==='publish'){assert.equal(data.revision,revision);saved=data.draft;revision++;if(action==='publish')published=structuredClone(saved);return respond(route,{revision,draft:saved,published})}
 return respond(route,{error:'Unknown fixture action'},404);
});
try{
 await page.goto('http://127.0.0.1:4173/admin/');
 await page.getByLabel('Email',{exact:true}).fill('fixture@oak.example');
 await page.getByLabel('Password',{exact:true}).fill('wrong-password');
 await page.getByRole('button',{name:'Sign in',exact:true}).click();
 await page.getByRole('status').filter({hasText:'incorrect'}).waitFor();
 await page.getByLabel('Password',{exact:true}).fill('fixture-password');
 await page.getByRole('button',{name:'Sign in',exact:true}).click();
 await page.getByRole('heading',{name:'Homepage',exact:true}).waitFor();
 await page.locator('nav').getByRole('button',{name:'faqs',exact:true}).click();
 await page.getByRole('button',{name:'Edit',exact:true}).first().click();
 await page.getByLabel('Title',{exact:true}).fill('A saved FAQ question');
 await page.getByRole('button',{name:'Save draft',exact:true}).click();
 await page.getByRole('status').filter({hasText:'Draft saved'}).waitFor();
 assert.equal(saved.faqs[0].title,'A saved FAQ question');
 assert.notEqual(published.faqs[0].title,'A saved FAQ question');
 await page.locator('nav').getByRole('button',{name:'appearance',exact:true}).click();
 await page.locator('select[name=font]').selectOption('Georgia');
 await page.getByLabel('Homepage section order').fill('Hero\nTeam\nServices\nAbout\nStats\nWhy Choose Us 01\nTestimonials\nFAQ\nCTA');
 await page.getByLabel('Hidden homepage sections').fill('Stats');
 page.once('dialog',d=>d.accept());
 await page.getByRole('button',{name:'Publish website',exact:true}).click();
 await page.getByRole('status').filter({hasText:'Website published'}).waitFor();
 assert.equal(published.theme.font,'Georgia');assert.deepEqual(published.layout.hidden,['Stats']);
 for(const width of [390,768,1280]){
  await page.setViewportSize({width,height:900});
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),false,'editor overflow at '+width);
 }
 fs.mkdirSync('test-results',{recursive:true});
 await page.screenshot({path:'test-results/standalone-editor-mobile.png',fullPage:true});
 await page.setViewportSize({width:1280,height:900});
 await page.screenshot({path:'test-results/standalone-editor-desktop.png',fullPage:true});
 await page.route('**/config.json',route=>respond(route,{contentMode:'custom',cmsEndpoint:'/cms/api.php?action=content',indexable:false}));
 await page.goto('http://127.0.0.1:4173/');
 await page.locator('.hero').waitFor();
 assert.equal(await page.locator('main [data-section=Stats]').count(),0);
 const sections=await page.locator('main > [data-section]').evaluateAll(nodes=>nodes.map(n=>n.dataset.section));
 assert.ok(sections.indexOf('Team')<sections.indexOf('Services'),'CMS section order is reflected in website');
 assert.match(await page.evaluate(()=>getComputedStyle(document.documentElement).fontFamily),/Georgia/);
 await page.goto('http://127.0.0.1:4173/?oak-preview=1');
 await page.getByText('Private draft preview',{exact:false}).waitFor();
 await page.locator('nav').getByRole('link',{name:'Contact',exact:true}).click();
 await page.getByText('Private draft preview',{exact:false}).waitFor();
 await page.getByRole('button',{name:'Exit preview',exact:true}).click();
 await page.locator('.hero').waitFor();
 assert.equal(await page.getByRole('button',{name:'Exit preview'}).count(),0);
 await page.goto('http://127.0.0.1:4173/admin/');
 await page.getByRole('button',{name:'Sign out',exact:true}).click();
 await page.getByRole('button',{name:'Sign in',exact:true}).waitFor();
 assert.equal(authenticated,false);assert.deepEqual(errors,[]);
 console.log('Standalone editor browser contracts passed: login errors, editing, draft isolation, publishing, responsive layout, theme, section order, authenticated preview and sign out. API was mocked; PHP integration remains unverified.');
}catch(error){console.error('Browser errors:',errors);console.error((await page.locator('body').innerText()).slice(0,1800));await page.screenshot({path:'test-results/standalone-editor-failure.png',fullPage:true});throw error}finally{await browser.close()}
