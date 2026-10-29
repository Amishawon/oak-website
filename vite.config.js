import {defineConfig} from 'vite';
const proxy={'/cms/api.php':{target:'http://127.0.0.1:8089'},'/cms/wp-json':{target:'http://127.0.0.1:8088',changeOrigin:true,rewrite:p=>'/?rest_route='+p.replace('/cms/wp-json','')}};
export default defineConfig({server:{proxy},preview:{proxy}});
