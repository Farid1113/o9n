/* NOVA MARKET offline support. Never cache checkout API, admin routes or private data. */
const CACHE='nova-static-v1';
const ASSETS=['./assets/css/store.css','./assets/js/store.js','./assets/img/favicon.svg','./assets/img/placeholder.svg'];
self.addEventListener('install',event=>{event.waitUntil(caches.open(CACHE).then(c=>c.addAll(ASSETS)).then(()=>self.skipWaiting()))});
self.addEventListener('activate',event=>{event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim()))});
self.addEventListener('fetch',event=>{
  const req=event.request;if(req.method!=='GET')return;
  const u=new URL(req.url);if(u.origin!==self.location.origin||u.pathname.includes('/control/')||u.pathname.endsWith('/api.php')||u.pathname.endsWith('/install.php'))return;
  if(/\/assets\//.test(u.pathname)){event.respondWith(caches.match(req).then(r=>r||fetch(req).then(n=>{if(n.ok){const copy=n.clone();caches.open(CACHE).then(c=>c.put(req,copy))}return n})));return;}
  // Store pages use network-first so prices and product stock are fresh.
  if(req.mode==='navigate')event.respondWith(fetch(req).catch(()=>new Response('<h1>NOVA MARKET</h1><p>Internet connection required to place an order.</p>',{headers:{'Content-Type':'text/html; charset=utf-8'}})));
});
