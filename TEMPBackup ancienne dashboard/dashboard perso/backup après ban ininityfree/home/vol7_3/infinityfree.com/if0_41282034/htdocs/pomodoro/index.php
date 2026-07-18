<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=no">
<title>Focus Sanctuary</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;800&family=DM+Mono:wght@300;400&family=DM+Sans:wght@300;400&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════
   VARIABLES — alignées sur le dashboard
═══════════════════════════════════════ */
:root {
  --bg:      #07080b;
  --acc:     #d4a853;
  --adim:    rgba(212,168,83,0.14);
  --aglow:   rgba(212,168,83,0.10);
  --overlay: rgba(5,4,2,0.74);
  --t1:      rgba(240,236,228,0.95);
  --t2:      rgba(240,236,228,0.56);
  --t3:      rgba(240,236,228,0.28);
  --border:  rgba(255,255,255,0.09);
  --card:    rgba(8,10,17,0.80);
  --fd: 'Outfit', sans-serif;
  --fm: 'DM Mono', monospace;
  --fb: 'DM Sans', sans-serif;
  --r: 10px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{width:100%;height:100%;overflow:hidden;background:var(--bg);font-family:var(--fb);font-weight:300;}

/* ═══════════════════════════════════════
   SLIDESHOW
═══════════════════════════════════════ */
#bg-a,#bg-b{
  position:fixed;top:0;right:0;bottom:0;left:0;
  background-size:cover;background-position:center;
  z-index:0;transition:opacity 2.4s ease-in-out;
}
/* Photos sombres — opacité réduite + assombrissement */
#bg-a{opacity:.38;filter:brightness(.55) contrast(1.05);}
#bg-b{opacity:0;filter:brightness(.55) contrast(1.05);}
#overlay{
  position:fixed;top:0;right:0;bottom:0;left:0;z-index:1;
  background:var(--overlay);
  transition:background 1.6s ease;
}
/* Grain */
#grain{
  position:fixed;top:0;right:0;bottom:0;left:0;z-index:2;
  opacity:.025;pointer-events:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  background-size:300px;
}
/* Vignette plus forte pour les photos sombres */
#vignette{
  position:fixed;top:0;right:0;bottom:0;left:0;z-index:2;pointer-events:none;
  background:radial-gradient(ellipse at 50% 38%,transparent 18%,rgba(4,3,2,.70) 100%);
}

/* ═══════════════════════════════════════
   APP
═══════════════════════════════════════ */
#app{
  position:relative;z-index:10;
  width:100%;height:100%;
  display:flex;flex-direction:column;
  transition:filter .4s ease;
}
#app.blur{filter:blur(3px) brightness(.65);pointer-events:none;}

/* ═══════════════════════════════════════
   TOPBAR
═══════════════════════════════════════ */
#topbar{
  display:flex;align-items:center;justify-content:space-between;
  padding:18px 24px;flex-shrink:0;
}
.brand{
  font-family:var(--fm);font-size:8px;font-weight:400;
  letter-spacing:.35em;text-transform:uppercase;color:var(--t3);
}
.topbar-right{display:flex;align-items:center;gap:10px;}

/* Bouton popup miniature */
#btn-popup{
  background:none;border:1px solid var(--border);border-radius:var(--r);
  cursor:pointer;padding:5px 8px;color:var(--t3);
  font-family:var(--fm);font-size:7px;letter-spacing:.12em;text-transform:uppercase;
  transition:all .2s;line-height:1;
}
#btn-popup:hover{border-color:rgba(212,168,83,.4);color:var(--acc);}

/* Hamburger */
#btn-menu{
  background:none;border:none;cursor:pointer;
  padding:6px;color:var(--t3);transition:color .2s;
  display:flex;flex-direction:column;gap:4px;align-items:flex-end;
}
#btn-menu:hover{color:var(--t1);}
#btn-menu span{display:block;height:1px;background:currentColor;border-radius:1px;}
#btn-menu span:nth-child(1){width:18px;}
#btn-menu span:nth-child(2){width:12px;}
#btn-menu span:nth-child(3){width:15px;}

/* ═══════════════════════════════════════
   ZONE TIMER
═══════════════════════════════════════ */
#main{
  flex:1;display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  padding:0 24px 16px;position:relative;
}

/* Halo — très diffus, quasiment imperceptible aux bords */
#timer-glow{
  position:absolute;
  width:1100px;max-width:160vw;
  height:600px;
  border-radius:50%;
  background:radial-gradient(
    ellipse 100% 100% at 50% 50%,
    rgba(212,168,83,0.11) 0%,
    rgba(212,168,83,0.05) 22%,
    rgba(212,168,83,0.02) 45%,
    rgba(212,168,83,0.005) 65%,
    transparent 80%
  );
  pointer-events:none;
  opacity:0;
  transition:opacity 1.6s ease;
}
#timer-glow.on{opacity:1;}

/* Pastilles cycle */
.dots{display:flex;gap:9px;margin-bottom:22px;}
.dot{width:5px;height:5px;border-radius:50%;background:var(--t3);transition:all .5s;}
.dot.done{background:rgba(240,236,228,.38);}
.dot.active{background:var(--acc);width:20px;border-radius:3px;box-shadow:0 0 8px rgba(212,168,83,.5);}

/* Étiquette mode */
#mode-lbl{
  font-family:var(--fm);font-size:7.5px;letter-spacing:.5em;
  text-transform:uppercase;color:var(--acc);margin-bottom:10px;opacity:.75;
}

/* Timer */
#timer{
  font-family:var(--fd);font-weight:800;
  font-size:clamp(76px,18vw,152px);
  line-height:.88;letter-spacing:.01em;
  color:var(--t1);font-variant-numeric:tabular-nums;
  text-shadow:0 0 80px rgba(212,168,83,.08),0 2px 40px rgba(0,0,0,.5);
  margin-bottom:20px;
  user-select:none;
}
#timer.urgent{color:rgba(200,80,60,.90);}
/* Deux-points — fixe, sans clignotement */
#t-colon{color:var(--acc);margin:0 2px;}

/* Barre progression */
.progress-track{
  width:100%;max-width:300px;height:1px;
  background:rgba(240,236,228,.06);
  border-radius:1px;margin-bottom:30px;overflow:hidden;
}
.progress-bar{height:100%;background:var(--acc);border-radius:1px;width:0%;transition:width 1s linear;}

/* ═══════════════════════════════════════
   CONTROLES
═══════════════════════════════════════ */
.controls{display:flex;align-items:center;gap:10px;margin-bottom:28px;}

#btn-main{
  width:64px;height:64px;border-radius:50%;
  background:var(--adim);
  border:1px solid rgba(212,168,83,.35);
  color:var(--acc);
  cursor:pointer;transition:all .2s;
  display:inline-flex;align-items:center;justify-content:center;
  padding:0;
}
#btn-main:hover{background:rgba(212,168,83,.22);border-color:rgba(212,168,83,.7);box-shadow:0 0 24px rgba(212,168,83,.18);}
#btn-main:active{transform:scale(.96);}
#btn-main svg{display:block;}

#btn-skip{
  padding:11px 18px;
  background:none;border:1px solid var(--border);border-radius:3px;
  color:var(--t3);font-family:var(--fm);font-size:8.5px;letter-spacing:.14em;text-transform:uppercase;
  cursor:pointer;transition:all .2s;
}
#btn-skip:hover:not(:disabled){border-color:rgba(240,236,228,.28);color:var(--t2);}
#btn-skip:disabled{opacity:.2;cursor:not-allowed;}

#btn-reset{
  background:none;border:none;color:var(--t3);
  cursor:pointer;font-size:16px;padding:8px 6px;
  transition:color .2s;
}
#btn-reset:hover{color:var(--t2);}

/* ═══════════════════════════════════════
   AUDIO AMBIANT
═══════════════════════════════════════ */
.ambient{
  display:flex;align-items:center;gap:14px;
  padding:9px 18px;
  background:rgba(8,10,17,.55);
  border:1px solid var(--border);
  border-radius:var(--r);
}
.amb-lbl{font-family:var(--fm);font-size:7px;letter-spacing:.18em;text-transform:uppercase;color:var(--t3);white-space:nowrap;}
.amb-range{
  -webkit-appearance:none;appearance:none;
  width:72px;height:1.5px;background:rgba(240,236,228,.09);
  border-radius:2px;outline:none;cursor:pointer;
}
.amb-range::-webkit-slider-thumb{-webkit-appearance:none;width:9px;height:9px;border-radius:50%;background:var(--acc);cursor:pointer;transition:transform .1s;}
.amb-range::-webkit-slider-thumb:hover{transform:scale(1.4);}
.amb-range::-moz-range-thumb{width:9px;height:9px;border-radius:50%;background:var(--acc);border:none;cursor:pointer;}
.amb-sep{width:1px;height:18px;background:var(--border);}

/* ═══════════════════════════════════════
   BANNER
═══════════════════════════════════════ */
#banner{
  position:fixed;top:0;left:0;right:0;z-index:50;
  padding:12px;text-align:center;
  font-family:var(--fm);font-size:8.5px;letter-spacing:.28em;text-transform:uppercase;
  transform:translateY(-100%);
  transition:transform .4s cubic-bezier(.34,1.56,.64,1);
}
#banner.show{transform:translateY(0);}
#banner.work{background:rgba(212,168,83,.90);color:#07080b;}
#banner.brk{background:rgba(90,160,120,.88);color:#050f09;}

/* ═══════════════════════════════════════
   SIDEBAR — solid, pas de backdrop-filter
═══════════════════════════════════════ */
#sb-overlay{position:fixed;top:0;right:0;bottom:0;left:0;z-index:90;display:none;}
#sb-overlay.on{display:block;}

#sidebar{
  position:fixed;top:0;right:0;bottom:0;width:286px;
  background:#0b0c10;
  border-left:2px solid rgba(255,255,255,0.07);
  z-index:100;
  transform:translateX(100%);
  transition:transform .30s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;
  overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--border) transparent;
}
#sidebar::-webkit-scrollbar{width:3px;}
#sidebar::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px;}
#sidebar.open{transform:translateX(0);}

.sb-head{
  display:flex;align-items:center;justify-content:space-between;
  padding:18px 20px 14px;
  border-bottom:1px solid var(--border);flex-shrink:0;
}
.sb-title{font-family:var(--fm);font-size:7.5px;letter-spacing:.32em;text-transform:uppercase;color:var(--t3);}
#btn-sb-close{
  background:none;border:none;color:var(--t3);font-size:20px;
  cursor:pointer;padding:4px;line-height:1;transition:color .2s;
}
#btn-sb-close:hover{color:var(--t1);}

.sb-section{padding:14px 20px;border-bottom:1px solid var(--border);}
.sb-sec-ttl{font-family:var(--fm);font-size:6.5px;letter-spacing:.34em;text-transform:uppercase;color:var(--t3);margin-bottom:11px;}

.cfg-row{display:flex;gap:7px;}
.cfg-btn{
  flex:1;padding:6px 10px;background:none;
  border:1px solid var(--border);border-radius:3px;
  color:var(--t3);font-family:var(--fm);font-size:7.5px;letter-spacing:.08em;
  cursor:pointer;transition:all .15s;text-align:center;
}
.cfg-btn:hover{border-color:rgba(240,236,228,.28);color:var(--t2);}
.cfg-btn.on{border-color:var(--acc);color:var(--acc);background:var(--adim);}

/* Toggle */
.toggle-row{display:flex;align-items:center;justify-content:space-between;}
.toggle-lbl{font-family:var(--fm);font-size:7px;letter-spacing:.1em;text-transform:uppercase;color:var(--t2);}
.toggle{
  width:32px;height:17px;border-radius:9px;
  background:rgba(240,236,228,.07);border:1px solid var(--border);
  position:relative;cursor:pointer;transition:all .25s;flex-shrink:0;
}
.toggle.on{background:var(--adim);border-color:var(--acc);}
.toggle::after{
  content:'';position:absolute;top:2px;left:2px;
  width:11px;height:11px;border-radius:50%;
  background:rgba(240,236,228,.3);transition:transform .25s,background .25s;
}
.toggle.on::after{transform:translateX(15px);background:var(--acc);}

/* YouTube */
.yt-tabs{display:flex;margin-bottom:8px;border-bottom:1px solid var(--border);}
.yt-tab{
  flex:1;padding:6px 4px;background:none;border:none;
  color:var(--t3);font-family:var(--fm);font-size:7px;
  letter-spacing:.1em;cursor:pointer;transition:all .15s;text-transform:uppercase;
  border-bottom:1px solid transparent;margin-bottom:-1px;
}
.yt-tab.on{color:var(--acc);border-bottom-color:var(--acc);}
#yt-frame{width:100%;border:none;display:block;border-radius:3px;overflow:hidden;}
.yt-vol{display:flex;align-items:center;gap:8px;margin-top:8px;}
.yt-vol-lbl{font-family:var(--fm);font-size:7px;color:var(--t3);letter-spacing:.08em;}
#yt-vol-pct{font-family:var(--fm);font-size:7px;color:var(--t3);min-width:24px;text-align:right;}

/* Stats */
.stats-mini{display:flex;gap:7px;margin-bottom:11px;}
.stat-pill{
  flex:1;text-align:center;padding:9px 4px;
  background:rgba(240,236,228,.02);
  border:1px solid var(--border);border-radius:5px;
}
.stat-val{font-family:var(--fd);font-size:19px;font-weight:700;color:rgba(240,236,228,.72);display:block;line-height:1;}
.stat-lbl{font-family:var(--fm);font-size:5.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--t3);margin-top:4px;display:block;}
.sb-link{
  display:block;text-align:center;padding:9px 16px;
  border:1px solid var(--border);border-radius:3px;
  color:var(--t3);font-family:var(--fm);font-size:7px;
  letter-spacing:.18em;text-decoration:none;text-transform:uppercase;
  transition:all .2s;
}
.sb-link:hover{border-color:var(--acc);color:var(--acc);}

/* ═══════════════════════════════════════
   ANIMATIONS
═══════════════════════════════════════ */
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
#main>*{animation:fadeUp .55s ease both;}
.dots{animation-delay:.06s;}
#mode-lbl{animation-delay:.12s;}
#timer{animation-delay:.18s;}
.progress-track{animation-delay:.23s;}
.controls{animation-delay:.28s;}
.ambient{animation-delay:.33s;}

@media(max-width:480px){
  #timer{font-size:clamp(60px,22vw,100px);}
  .ambient{gap:10px;padding:8px 12px;}
  .amb-range{width:56px;}
}
</style>
</head>
<body>
<div id="bg-a"></div>
<div id="bg-b"></div>
<div id="overlay"></div>
<div id="grain"></div>
<div id="vignette"></div>
<div id="banner"></div>
<div id="sb-overlay"></div>

<div id="app">
  <header id="topbar">
    <span class="brand">Focus Sanctuary</span>
    <div class="topbar-right">
      <button id="btn-popup" title="Fenêtre miniature">⊞ Mini</button>
      <button id="btn-menu" title="Paramètres" aria-label="Ouvrir paramètres">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <main id="main">
    <div id="timer-glow"></div>

    <div class="dots">
      <div class="dot" id="d0"></div>
      <div class="dot" id="d1"></div>
      <div class="dot" id="d2"></div>
      <div class="dot" id="d3"></div>
    </div>

    <div id="mode-lbl">FOCUS</div>

    <div id="timer">
      <span id="t-min">25</span><span id="t-colon">:</span><span id="t-sec">00</span>
    </div>

    <div class="progress-track">
      <div class="progress-bar" id="prog"></div>
    </div>

    <div class="controls">
      <button id="btn-main" title="Play / Pause">
        <svg id="btn-main-icon" width="22" height="22" viewBox="0 0 10 10" fill="currentColor">
          <polygon points="2,1 9,5 2,9"/>
        </svg>
      </button>
      <button id="btn-skip" disabled>Passer →</button>
      <button id="btn-reset" title="Réinitialiser">↺</button>
    </div>

    <div class="ambient">
      <span class="amb-lbl">☁ Pluie</span>
      <input type="range" class="amb-range" id="r-rain" min="0" max="100" value="0">
      <div class="amb-sep"></div>
      <span class="amb-lbl">🔥 Feu</span>
      <input type="range" class="amb-range" id="r-fire" min="0" max="100" value="0">
    </div>
  </main>
</div>

<!-- Sidebar -->
<div id="sidebar">
  <div class="sb-head">
    <span class="sb-title">Paramètres</span>
    <button id="btn-sb-close">×</button>
  </div>

  <div class="sb-section">
    <div class="sb-sec-ttl">Durée de travail</div>
    <div class="cfg-row">
      <button class="cfg-btn on" data-wmin="25">25 min</button>
      <button class="cfg-btn" data-wmin="50">50 min</button>
    </div>
  </div>

  <div class="sb-section">
    <div class="sb-sec-ttl">Durée des pauses</div>
    <div class="cfg-row">
      <button class="cfg-btn on" data-bsz="short">5 / 30 min</button>
      <button class="cfg-btn" data-bsz="long">10 / 60 min</button>
    </div>
  </div>

  <div class="sb-section">
    <div class="sb-sec-ttl">Ambiance</div>
    <div class="toggle-row">
      <span class="toggle-lbl">Mode Zen</span>
      <div class="toggle" id="zen-toggle"></div>
    </div>
  </div>

  <div class="sb-section">
    <div class="sb-sec-ttl">Musique</div>
    <div class="yt-tabs">
      <button class="yt-tab on" data-vi="0">Sombre</button>
      <button class="yt-tab" data-vi="1">Baroque</button>
      <button class="yt-tab" data-vi="2">Lo-fi</button>
    </div>
    <iframe id="yt-frame" height="76"
      src="https://www.youtube.com/embed/bt2KHW-EQWE?autoplay=1&mute=1&vq=tiny&enablejsapi=1&loop=1&playlist=bt2KHW-EQWE"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
      allowfullscreen></iframe>
    <div class="yt-vol">
      <span class="yt-vol-lbl">Vol.</span>
      <input type="range" class="amb-range" id="r-yt" min="0" max="100" value="60" style="flex:1">
      <span id="yt-vol-pct">60%</span>
    </div>
  </div>

  <div class="sb-section">
    <div class="sb-sec-ttl">Statistiques</div>
    <div class="stats-mini">
      <div class="stat-pill"><span class="stat-val" id="s-today">–</span><span class="stat-lbl">Auj.</span></div>
      <div class="stat-pill"><span class="stat-val" id="s-week">–</span><span class="stat-lbl">Semaine</span></div>
      <div class="stat-pill"><span class="stat-val" id="s-total">–</span><span class="stat-lbl">Total</span></div>
    </div>
    <a href="stats.html" target="_blank" class="sb-link">Tableau de bord →</a>
  </div>
</div>

<audio id="a-rain" loop preload="none" src="/pomodoro/assets/audio/rain.mp3"></audio>
<audio id="a-fire" loop preload="none" src="/pomodoro/assets/audio/fire.mp3"></audio>

<script>
var API = '/pomodoro/api.php';

// 3 playlists disponibles (vérifiées)
var YT_IDS = [
  'bt2KHW-EQWE',  // Dark / Atmospheric instrumental
  'DWcT0aS3v8E',  // Baroque / Classique focus
  'jfKfPfyJRdk',  // Lo-fi Chill
];

var MODE_COLORS = {
  work:        { acc:'#d4a853', dim:'rgba(212,168,83,0.14)', glow:'rgba(212,168,83,0.10)', overlay:'rgba(5,4,2,0.74)', bg:'#07080b', brk:false },
  short_break: { acc:'#6eba94', dim:'rgba(110,186,148,0.12)', glow:'rgba(110,186,148,0.08)', overlay:'rgba(2,6,4,0.74)', bg:'#050f08', brk:true },
  long_break:  { acc:'#6eba94', dim:'rgba(110,186,148,0.12)', glow:'rgba(110,186,148,0.08)', overlay:'rgba(2,6,4,0.74)', bg:'#050f08', brk:true },
};
var MODE_LABELS = {
  work:        'FOCUS',
  short_break: 'PAUSE',
  long_break:  'LONGUE PAUSE',
};

var cur = {}, bgList = {work:[],break:[]}, activeBg = 0, zen = false;
var bgs = [document.getElementById('bg-a'), document.getElementById('bg-b')];

function xhr(url, method, body, cb) {
  var x = new XMLHttpRequest();
  x.open(method||'GET', url+(method==='GET'?'&_='+Date.now():''), true);
  if (method==='POST') x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
  x.timeout = 3000;
  x.onreadystatechange = function() {
    if (x.readyState===4 && x.status===200 && cb) {
      try { cb(JSON.parse(x.responseText)); } catch(e) { if(cb) cb({}); }
    }
  };
  x.send(body||null);
}
function api(action, params, cb) {
  var body = 'action='+action;
  if (params) for (var k in params) body += '&'+k+'='+encodeURIComponent(params[k]);
  xhr(API, 'POST', body, cb);
}
function apiGet(action, cb) { xhr(API+'?action='+action, 'GET', null, cb); }

/* ════════════════════════════════════════
   SONS — Web Audio API (synthétisés, pas de fichiers)
════════════════════════════════════════ */
var _AC = null;
function getAC(){
  if(!_AC){ try{ _AC=new(window.AudioContext||window.webkitAudioContext)(); }catch(e){} }
  return _AC;
}
/* Déverrouillage au premier geste utilisateur */
document.addEventListener('click', function _u(){ getAC(); if(_AC&&_AC.state==='suspended')_AC.resume(); document.removeEventListener('click',_u); }, {once:true});

function _playTone(freq,type,vol,dur,attack,delay){
  var ac=getAC(); if(!ac) return;
  var t0=ac.currentTime+(delay||0);
  var osc=ac.createOscillator(), g=ac.createGain();
  osc.connect(g); g.connect(ac.destination);
  osc.type=type||'sine'; osc.frequency.setValueAtTime(freq,t0);
  g.gain.setValueAtTime(0,t0);
  g.gain.linearRampToValueAtTime(vol,t0+(attack||0.01));
  g.gain.exponentialRampToValueAtTime(0.001,t0+dur);
  osc.start(t0); osc.stop(t0+dur+0.05);
}

/* Fin TRAVAIL — cloche 3 notes descendantes 880→660→440 Hz */
function soundWorkEnd(){
  _playTone(880, 'sine',     0.38, 1.2, 0.005, 0.00);
  _playTone(1760,'sine',     0.13, 0.8, 0.005, 0.00);
  _playTone(660, 'sine',     0.33, 1.0, 0.005, 0.40);
  _playTone(1320,'sine',     0.10, 0.7, 0.005, 0.40);
  _playTone(440, 'sine',     0.30, 1.5, 0.005, 0.76);
  _playTone(880, 'sine',     0.09, 1.0, 0.005, 0.76);
}

/* Fin PAUSE — 3 pulses ascendants triangulaires */
function soundBreakEnd(){
  _playTone(520, 'triangle', 0.42, 0.25, 0.008, 0.00);
  _playTone(780, 'sine',     0.16, 0.20, 0.005, 0.00);
  _playTone(620, 'triangle', 0.46, 0.35, 0.008, 0.32);
  _playTone(930, 'sine',     0.18, 0.30, 0.005, 0.32);
  _playTone(740, 'triangle', 0.42, 0.55, 0.008, 0.68);
}

/* 30s avant fin PAUSE — double ping discret */
function soundPreEnd(){
  _playTone(1050,'sine', 0.24, 0.50, 0.004, 0.00);
  _playTone(1050,'sine', 0.14, 0.35, 0.004, 0.58);
}

/* État persistant pour la détection des transitions */
var _prevMode  = null;
var _prevRem   = null;
var _warned30  = false;

function poll() {
  apiGet('state', function(d) {
    if (!d.mode) return;
    var prevMode = cur.mode;   /* mode avant mise à jour */
    cur = d;

    /* ── Détection transitions → sons ── */
    var isBreak = (d.mode==='short_break'||d.mode==='long_break');
    if(_prevMode !== null && _prevMode !== d.mode){
      if(_prevMode === 'work') soundWorkEnd();
      else { soundBreakEnd(); _warned30 = false; }
    }
    if(_prevMode !== d.mode) _warned30 = false;
    if(d.running && isBreak && !_warned30 && d.rem<=30 && d.rem>0){
      if(_prevRem===null||_prevRem>30){ soundPreEnd(); _warned30=true; }
    }
    _prevMode = d.mode;
    _prevRem  = d.rem;

    /* ── UI existante ── */
    if (d.just_completed && d.completed_mode) showBanner(d.completed_mode);
    if (d.mode !== prevMode) onModeChange(d.mode);
    renderUI(d);
  });
}

function renderUI(d) {
  var rem = d.rem;
  document.getElementById('t-min').textContent = ('0'+Math.floor(rem/60)).slice(-2);
  document.getElementById('t-sec').textContent = ('0'+(rem%60)).slice(-2);
  // Pas de clignotement — deux-points fixe
  var pct = d.dur > 0 ? (1 - rem/d.dur)*100 : 0;
  document.getElementById('prog').style.width = pct.toFixed(1)+'%';
  document.getElementById('timer').classList.toggle('urgent', d.running && rem<=60);
  document.getElementById('timer-glow').classList.toggle('on', d.running);
  for (var i=0; i<4; i++) {
    var dot = document.getElementById('d'+i);
    if (!dot) continue;
    dot.classList.remove('active','done');
    if (i < d.pos) dot.classList.add('done');
    else if (i===d.pos && d.mode==='work') dot.classList.add('active');
  }
  document.getElementById('mode-lbl').textContent = MODE_LABELS[d.mode]||'FOCUS';
  var bm = document.getElementById('btn-main');
  var ic=document.getElementById('btn-main-icon');
  if(ic) ic.innerHTML=d.running
    ?'<rect x="2" y="1" width="2.5" height="8"/><rect x="5.5" y="1" width="2.5" height="8"/>'
    :'<polygon points="2,1 9,5 2,9"/>';
  document.getElementById('btn-skip').disabled = (d.mode==='work');
}

function onModeChange(mode) {
  var c = MODE_COLORS[mode]||MODE_COLORS.work;
  var css = document.documentElement.style;
  css.setProperty('--acc', c.acc);
  css.setProperty('--adim', c.dim);
  css.setProperty('--aglow', c.glow);
  css.setProperty('--overlay', c.overlay);
  document.body.style.background = c.bg;
  if (!zen) {
    setTimeout(function() {
      var list = c.brk ? bgList['break'] : bgList['work'];
      // Mode pause : on garde le fond work (photos bibliothèques)
      if (!list || !list.length) list = bgList['work'];
      if (list && list.length) swapBg(list[Math.floor(Math.random()*list.length)]);
    }, 300);
  }
}

function swapBg(url) {
  if (zen||!url) return;
  var next = 1-activeBg;
  bgs[next].style.backgroundImage = "url('"+url+"')";
  bgs[activeBg].style.opacity = '0';
  bgs[next].style.opacity = '.38';
  activeBg = next;
}
function loadBgs() {
  apiGet('bg', function(d) {
    bgList = { work:d.work||[], break:d.break||[] };
    var list = bgList['work'];
    if (list && list.length) swapBg(list[Math.floor(Math.random()*list.length)]);
  });
}

function showBanner(mode) {
  var el = document.getElementById('banner');
  var msgs = { work:'✦  Session accomplie — pause méritée', short_break:'✦  Pause terminée — retour au travail', long_break:'✦  Grande pause terminée' };
  el.textContent = msgs[mode]||'';
  el.className = 'show '+(mode==='work'?'work':'brk');
  setTimeout(function(){ el.className=''; }, 4500);
}

/* Boutons principaux */
document.getElementById('btn-main').addEventListener('click', function(){ api(cur.running?'pause':'start',{},poll); });
document.getElementById('btn-skip').addEventListener('click', function(){ if(cur.mode!=='work') api('skip',{},poll); });
document.getElementById('btn-reset').addEventListener('click', function(){ api('reset',{},poll); });

/* Popup miniature */
document.getElementById('btn-popup').addEventListener('click', function() {
  window.open(
    '/pomodoro/popup.php',
    'pom_mini',
    'width=230,height=108,resizable=yes,menubar=no,toolbar=no,location=no,status=no,scrollbars=no'
  );
});

/* Sidebar */
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sb-overlay').classList.add('on');
  document.getElementById('app').classList.add('blur');
  loadStats();
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sb-overlay').classList.remove('on');
  document.getElementById('app').classList.remove('blur');
}
document.getElementById('btn-menu').addEventListener('click', openSidebar);
document.getElementById('btn-sb-close').addEventListener('click', closeSidebar);
document.getElementById('sb-overlay').addEventListener('click', closeSidebar);

document.querySelectorAll('[data-wmin]').forEach(function(b) {
  b.addEventListener('click', function() {
    document.querySelectorAll('[data-wmin]').forEach(function(x){x.classList.remove('on');});
    b.classList.add('on');
    var bsz=(document.querySelector('[data-bsz].on')||{}).dataset||{};
    api('cfg',{wmin:b.dataset.wmin,bsz:bsz.bsz||'short'},poll);
  });
});
document.querySelectorAll('[data-bsz]').forEach(function(b) {
  b.addEventListener('click', function() {
    document.querySelectorAll('[data-bsz]').forEach(function(x){x.classList.remove('on');});
    b.classList.add('on');
    var wmin=(document.querySelector('[data-wmin].on')||{}).dataset||{};
    api('cfg',{wmin:wmin.wmin||25,bsz:b.dataset.bsz},poll);
  });
});

document.getElementById('zen-toggle').addEventListener('click', function() {
  zen = !zen;
  this.classList.toggle('on', zen);
  if (zen) {
    bgs[0].style.opacity='0'; bgs[1].style.opacity='0';
    document.body.style.background = (MODE_COLORS[cur.mode||'work']||{}).bg||'#07080b';
  } else {
    document.body.style.background='';
    var list = bgList['work'];
    if (list&&list.length) swapBg(list[Math.floor(Math.random()*list.length)]);
  }
});

/* Audio ambiant */
var aRain=document.getElementById('a-rain'), aFire=document.getElementById('a-fire');
function setAudio(el,val){ var v=val/100; if(v>0){el.volume=v;if(el.paused)el.play().catch(function(){});}else{el.pause();el.volume=0;} }
document.getElementById('r-rain').addEventListener('input',function(){setAudio(aRain,parseInt(this.value));});
document.getElementById('r-fire').addEventListener('input',function(){setAudio(aFire,parseInt(this.value));});

/* YouTube */
var ytFrame=document.getElementById('yt-frame');
ytFrame.addEventListener('load',function(){
  setTimeout(function(){
    var v=parseInt(document.getElementById('r-yt').value);
    try{ytFrame.contentWindow.postMessage(JSON.stringify({event:'command',func:'unMute',args:''}),'*');
        ytFrame.contentWindow.postMessage(JSON.stringify({event:'command',func:'setVolume',args:[v]}),'*');}catch(e){}
  },1200);
});
document.getElementById('r-yt').addEventListener('input',function(){
  var v=parseInt(this.value);
  document.getElementById('yt-vol-pct').textContent=v+'%';
  try{ytFrame.contentWindow.postMessage(JSON.stringify({event:'command',func:v===0?'mute':'unMute',args:''}),'*');
      ytFrame.contentWindow.postMessage(JSON.stringify({event:'command',func:'setVolume',args:[v]}),'*');}catch(e){}
});
document.querySelectorAll('.yt-tab').forEach(function(t){
  t.addEventListener('click',function(){
    document.querySelectorAll('.yt-tab').forEach(function(x){x.classList.remove('on');});
    t.classList.add('on');
    var id=YT_IDS[parseInt(t.dataset.vi)];
    ytFrame.src='https://www.youtube.com/embed/'+id+'?autoplay=1&mute=0&vq=tiny&enablejsapi=1&loop=1&playlist='+id;
  });
});

/* Stats */
function loadStats() {
  apiGet('stats',function(d){
    document.getElementById('s-today').textContent=fmtH(d.today);
    document.getElementById('s-week').textContent=fmtH(d.week);
    document.getElementById('s-total').textContent=fmtH(d.total);
  });
}
function fmtH(m){ if(!m)return'0h'; var h=Math.floor(m/60),mn=m%60; return h>0?h+'h'+(mn>0?mn:''):mn+'m'; }

poll();
setInterval(poll,1000);
loadBgs();
</script>
</body>
</html>