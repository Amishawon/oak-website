import test from 'node:test';import assert from 'node:assert/strict';import fs from 'node:fs';
const data=JSON.parse(fs.readFileSync('public/content.json'));
test('all service/detail slugs are unique and content exists',()=>{for(const key of ['services','team','blogs','legal']){assert.equal(new Set(data[key].map(x=>x.slug)).size,data[key].length);for(const x of data[key]){assert.ok(x.title);assert.ok(x.body)}}assert.equal(data.services.length,12)});
test('no fabricated testimonials and booking URL remains unset',()=>{assert.equal(data.testimonials.filter(x=>x.approved&&x.consent).length,0);assert.equal(data.settings.bookingUrl,'')});
test('all local image references exist',()=>{function walk(x){if(typeof x==='string'&&x.startsWith('/assets/'))assert.ok(fs.existsSync('public'+x),x);else if(x&&typeof x==='object')Object.values(x).forEach(walk)}walk(data)});
test('original inventory retained as migration evidence',()=>{const a=JSON.parse(fs.readFileSync('section-inventory.json'));assert.equal(a.length,11);assert.equal(a.reduce((s,p)=>s+p.sections.length,0),28)});
