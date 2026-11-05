/* journzey.ai landing page behaviour. Built from docs/redesign/landing-preview/page.src.html by build_site.py. */
(function(){
'use strict';
var $=function(s,c){return(c||document).querySelector(s)},$$=function(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))};
var clamp=function(v,a,b){return Math.min(b==null?1:b,Math.max(a==null?0:a,v))},lerp=function(a,b,t){return a+(b-a)*t},ease=function(t){return t*t*(3-2*t)};
var reduce=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
var vh=function(){return window.innerHeight||800},vw=function(){return window.innerWidth||1200};
var root=document.documentElement,mouse={x:-1e4,y:-1e4},sm={};
function damp(k,t,f){var v=sm[k]==null?t:sm[k];sm[k]=reduce?t:v+(t-v)*(f||.12);return sm[k]}

/* theme: circular reveal with the View Transitions API */
try{var saved=localStorage.getItem('jz-theme');if(saved==='light')root.setAttribute('data-theme','light')}catch(e){}
function setTheme(light){if(light)root.setAttribute('data-theme','light');else root.removeAttribute('data-theme');try{localStorage.setItem('jz-theme',light?'light':'dark')}catch(e){}}
function toggleTheme(ev){
  var light=root.getAttribute('data-theme')!=='light';
  if(!document.startViewTransition||reduce){setTheme(light);return}
  var r=ev&&ev.currentTarget?ev.currentTarget.getBoundingClientRect():{left:vw()/2,top:vh()/2,width:0,height:0};
  var x=r.left+r.width/2,y=r.top+r.height/2,rad=Math.hypot(Math.max(x,vw()-x),Math.max(y,vh()-y));
  var t=document.startViewTransition(function(){setTheme(light)});
  t.ready.then(function(){root.animate({clipPath:['circle(0px at '+x+'px '+y+'px)','circle('+rad+'px at '+x+'px '+y+'px)']},{duration:760,easing:'cubic-bezier(.16,1,.3,1)',pseudoElement:'::view-transition-new(root)'})}).catch(function(){});
}
$('#themeBtn').addEventListener('click',toggleTheme);$('#themeDock').addEventListener('click',toggleTheme);

/* marquee */
var mkts=['XAUUSD','NAS100','US500','EURUSD','GBPUSD','USDJPY','US30','GER40','XAGUSD','USOIL','BTCUSD','ETHUSD'];
var mq=$('#mq');mq.innerHTML=[0,1].map(function(){return mkts.map(function(m){return'<span>'+m+'</span>'}).join('')}).join('');

/* manifesto words */
var man=$('#manifesto'),mw=[];
(function(){var raw=man.getAttribute('data-text'),hl=false;raw.split(' ').forEach(function(tok){var s=tok;if(s.charAt(0)==='*'){hl=true;s=s.slice(1)}var end=false;if(s.charAt(s.length-1)==='*'){end=true;s=s.slice(0,-1)}var sp=document.createElement('span');sp.className='mw'+(hl?' hl':'');sp.textContent=s;man.appendChild(sp);man.appendChild(document.createTextNode(' '));mw.push(sp);if(end)hl=false});
  if(/behaviour/.test(raw))mw.forEach(function(w){if(/^behaviour/.test(w.textContent))w.className+=' hl'})})();

/* reveals */
var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}})},{threshold:.15,rootMargin:'0px 0px -6% 0px'});
$$('.rv').forEach(function(el){io.observe(el)});
setTimeout(function(){$$('.rv').forEach(function(el){var r=el.getBoundingClientRect();if(r.top<vh()&&r.bottom>0)el.classList.add('in')})},1800);

/* count-up */
var cio=new IntersectionObserver(function(es){es.forEach(function(e){if(!e.isIntersecting)return;cio.unobserve(e.target);var el=e.target,to=+el.getAttribute('data-count'),t0=performance.now();
  (function step(n){var p=clamp((n-t0)/1400);el.textContent=Math.round(to*(1-Math.pow(1-p,4)));if(p<1&&!reduce)requestAnimationFrame(step);else el.textContent=to})(t0)})},{threshold:.6});
$$('[data-count]').forEach(function(el){cio.observe(el)});

/* accordion */
var rows=$$('.fr');
rows.forEach(function(r){$('.fr-head',r).addEventListener('click',function(){var open=r.getAttribute('data-open')==='1';rows.forEach(function(o){o.setAttribute('data-open','0');$('.fr-head',o).setAttribute('aria-expanded','false')});if(!open){r.setAttribute('data-open','1');$('.fr-head',r).setAttribute('aria-expanded','true')}})});

/* gallery */
var G=[['dashboard','Dashboard','Every number that matters, at a glance.',0],['logtrade','Log a trade','Voice or typed. Risk, R and P&L calculate themselves.',0],['tease-1','Where your money leaks.','Sign up to see yours.',1],['tease-2','Something that talks back.','Sign up to meet it.',1],['tease-3','Where traders say the quiet part.','Members only.',1],['tease-4','Built to make you better.','Unlocks with your account.',1]];
var IMG=window.JZ_IMG||'img/';
var gt=$('#galTrack'),gc=G.map(function(g){var d=document.createElement('div');d.className='gc';d.innerHTML='<img src="'+IMG+g[0]+'.webp" alt="'+(g[3]?'Blurred preview, available to members':g[1]+' screen')+'" loading="lazy" width="'+(g[3]?900:2000)+'" height="'+(g[3]?562:1250)+'">'+(g[3]?'<span class="lk"><svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/></svg>Members only</span>':'');gt.appendChild(d);return d});
var gal=$('.gal'),galH=$('#galH'),galP=$('#galP'),galBar=$('#galBar'),lastCap=-1;
function layoutGal(){
  var r=gal.getBoundingClientRect(),total=gal.offsetHeight-vh(),p=clamp(-r.top/total),t=damp('gal',p*(G.length-1),.14),W=vw(),mobile=W<760;
  var base=mobile?W*.5:W*.2,big=mobile?W*.84:Math.min(W*.58,1000),x=0,centers=[],ws=[];
  gc.forEach(function(c,i){var d=Math.abs(i-t),f=ease(clamp(1-d)),w=lerp(base,big,f);ws[i]=w;c.style.width=w+'px';c.style.height=w*.625+'px';c.style.setProperty('--dim',(.55*(1-f)).toFixed(3));centers[i]=x+w/2;x+=w+18});
  var i0=Math.floor(t),i1=Math.min(G.length-1,i0+1),c=lerp(centers[i0],centers[i1],t-i0);
  gt.style.transform='translate3d('+(W/2-c)+'px,0,0)';
  var near=Math.round(t);if(near!==lastCap){lastCap=near;galH.style.opacity=0;galP.style.opacity=0;setTimeout(function(){galH.textContent=G[near][1];galP.textContent=G[near][2];galH.style.opacity=1;galP.style.opacity=1},180)}
  galBar.style.transform='scaleX('+p.toFixed(3)+')';
}

/* process */
var proc=$('.proc'),steps=$$('.pr-s'),pls=$$('#pl li'),prN=$('#prN'),curStep=-1;
function layoutProc(){var r=proc.getBoundingClientRect(),total=proc.offsetHeight-vh(),p=clamp(-r.top/total),i=Math.min(3,Math.floor(p*4));if(i!==curStep){curStep=i;steps.forEach(function(s,k){s.classList.toggle('on',k===i)});pls.forEach(function(s,k){s.classList.toggle('on',k===i)});prN.textContent='0'+(i+1)}}

/* hero tilt, manifesto, nav, dock */
var win=$('#heroWin'),nav=$('#nav'),dock=$('#dock'),dks=$$('.dk',dock),dockSecs=$$('[data-dock]'),dkAct={};
function layoutHero(){var y=window.scrollY||0,p=clamp(y/(vh()*.75)),e=damp('hero',ease(p),.1);win.style.setProperty('--rx',(26*(1-e)).toFixed(2)+'deg');win.style.setProperty('--s',(.9+.1*e).toFixed(4));win.style.setProperty('--ty',(-30*e).toFixed(1)+'px')}
function layoutMan(){var r=man.getBoundingClientRect(),p=clamp((vh()*.82-r.top)/(r.height+vh()*.42)),lit=damp('man',p,.14)*(mw.length+3);mw.forEach(function(w,i){var o=clamp(lit-i);w.style.opacity=(.16+.84*o).toFixed(3)})}
function chrome(){var y=window.scrollY||0;nav.classList.toggle('solid',y>40);dock.classList.toggle('show',y>vh()*.6);
  var cur=null;dockSecs.forEach(function(s){var r=s.getBoundingClientRect();if(r.top<vh()*.5&&r.bottom>vh()*.3)cur=s.getAttribute('data-dock')});
  dks.forEach(function(d){d.classList.toggle('on',d.getAttribute('data-t')===cur)})}

/* dock magnify */
dock.addEventListener('mousemove',function(e){mouse.x=e.clientX});dock.addEventListener('mouseleave',function(){mouse.x=-1e4});
var dkS=dks.map(function(){return 1});
function dockTick(){if(!dock.classList.contains('show'))return;dks.forEach(function(d,i){var r=d.getBoundingClientRect(),cx=r.left+r.width/2,dist=Math.abs(mouse.x-cx),t=1+.62*Math.pow(clamp(1-dist/120),2);dkS[i]=lerp(dkS[i],t,.22);d.style.transform=Math.abs(dkS[i]-1)<.003?'':'scale('+dkS[i].toFixed(3)+')'})}

/* owl eyes follow the pointer */
window.addEventListener('pointermove',function(e){mouse.px=e.clientX;mouse.py=e.clientY},{passive:true});
var owls=[$('#owlHero svg'),$('#owlEnd svg')];
function eyes(){owls.forEach(function(o){if(!o)return;var r=o.getBoundingClientRect();if(r.bottom<0||r.top>vh())return;var cx=r.left+r.width/2,cy=r.top+r.height*.45,dx=(mouse.px==null?cx:mouse.px)-cx,dy=(mouse.py==null?cy+200:mouse.py)-cy,d=Math.hypot(dx,dy)||1,k=Math.min(1,d/260)*7;
  $$('.pupil',o).forEach(function(p){p.style.transform='translate('+(dx/d*k).toFixed(2)+'px,'+(dy/d*k).toFixed(2)+'px)'})})}

/* stars */
var cv=$('#stars'),cx=cv.getContext('2d'),stars=[],cw=0,ch=0,dpr=1,heroEl=$('.hero');
function sizeStars(){dpr=Math.min(2,window.devicePixelRatio||1);cw=heroEl.clientWidth;ch=heroEl.clientHeight;cv.width=cw*dpr;cv.height=ch*dpr;stars=[];var n=Math.round(cw*ch/9500);for(var i=0;i<n;i++)stars.push({x:Math.random()*cw,y:Math.random()*ch,r:Math.random()*1.3+.3,a:Math.random()*Math.PI*2,s:Math.random()*.6+.2,v:Math.random()*.06+.01,z:Math.random()*.8+.2})}
function drawStars(t){cx.setTransform(dpr,0,0,dpr,0,0);cx.clearRect(0,0,cw,ch);var col=getComputedStyle(root).getPropertyValue('--star').trim()||'255,255,255',px=((mouse.px==null?cw/2:mouse.px)/vw()-.5),py=((mouse.py==null?200:mouse.py)/vh()-.5);
  stars.forEach(function(s){if(!reduce){s.y-=s.v;if(s.y<-4)s.y=ch+4}var tw=.45+.55*Math.sin(t/900*s.s+s.a);cx.fillStyle='rgba('+col+','+(.12+.7*tw*s.z).toFixed(3)+')';cx.beginPath();cx.arc(s.x+px*14*s.z,s.y+py*10*s.z,s.r,0,6.283);cx.fill()})}
sizeStars();window.addEventListener('resize',function(){sizeStars();buildGrid()});

/* final CTA icon grid */
var IC=['<path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/>','<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/>','<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>','<path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z"/>','<path d="M4 4h7a3 3 0 013 3v13a3 3 0 00-3-3H4V4z"/><path d="M20 4h-3a3 3 0 00-3 3v13a3 3 0 013-3h3V4z"/>','<path d="M4 5h16v11H9l-5 4V5z"/>','<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/>','<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>','<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>','<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>'];
var COL=['#b9a4ff','#ff8fc2','#4ade80','#60a5fa','#fbbf24'];
function buildGrid(){var g=$('#gridIco'),W=g.clientWidth,H=g.clientHeight,ts=W<900?64:76,cols=Math.ceil(W/ts)+1,rowsN=Math.ceil(H/ts)+1,seed=7;function rnd(){seed=(seed*16807)%2147483647;return seed/2147483647}
  g.innerHTML='';for(var r=0;r<rowsN;r++){var row=document.createElement('div');row.className='gr';for(var c=0;c<cols;c++){var t=document.createElement('div'),on=rnd()<.34;t.className='tile'+(on?' on':'');if(on){t.style.setProperty('--td',(rnd()*5).toFixed(2)+'s');t.style.setProperty('--tc',COL[Math.floor(rnd()*COL.length)])}t.innerHTML='<svg viewBox="0 0 24 24">'+IC[Math.floor(rnd()*IC.length)]+'</svg>';row.appendChild(t)}g.appendChild(row)}}
buildGrid();

/* pricing toggle (monthly / yearly) */
var seg=$('#seg');
if(seg){var swaps=$$('[data-m]'),links=$$('[data-hm]');
  $$('button',seg).forEach(function(b){b.addEventListener('click',function(){var y=b.getAttribute('data-y');seg.setAttribute('data-y',y);$$('button',seg).forEach(function(o){o.setAttribute('aria-pressed',String(o===b))});swaps.forEach(function(el){el.classList.add('out')});
    setTimeout(function(){swaps.forEach(function(el){el.textContent=y==='1'?el.getAttribute('data-ay'):el.getAttribute('data-m');el.classList.remove('out')});links.forEach(function(a){a.setAttribute('href',y==='1'?a.getAttribute('data-hy'):a.getAttribute('data-hm'))})},220)})})}

/* main loop */
var vis=true;document.addEventListener('visibilitychange',function(){vis=!document.hidden});
function tick(t){if(vis){layoutHero();layoutMan();layoutGal();layoutProc();chrome();dockTick();eyes();var hr=heroEl.getBoundingClientRect();if(hr.bottom>0&&(!reduce||!tick.once)){drawStars(t);tick.once=true}}requestAnimationFrame(tick)}
requestAnimationFrame(tick);
})();
