<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=no"/>
<title>Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;800&family=DM+Mono:wght@300;400&family=DM+Sans:wght@300;400&display=swap" rel="stylesheet"/>
<style>
/*
  Banshift SemiBold : placez Banshift-SemiBold.woff2 dans le même dossier et décommentez :
  @font-face {
    font-family:'Banshift';
    src:url('Banshift-SemiBold.woff2') format('woff2');
    font-weight:600;
  }
*/
:root{
  --bg:#07080b;
  --glass:rgba(6,7,12,0.20);
  --gl2:rgba(6,7,12,0.32);
  --gl-light:rgba(255,255,255,0.022);
  --border:rgba(255,255,255,0.07);
  --bds:rgba(255,255,255,0.13);
  --accent:#d4a853;
  --adim:rgba(212,168,83,0.14);
  --t1:rgba(240,236,228,0.95);
  --t2:rgba(240,236,228,0.56);
  --t3:rgba(240,236,228,0.28);
  --green:#4caf7d;--yellow:#f0b429;--red:#e05c5c;--blue:#5b9fd4;
  --r:11px;
  --fd:'Banshift','Outfit',sans-serif;
  --fm:'DM Mono',monospace;
  --fb:'DM Sans',sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{width:100%;height:100%;overflow:hidden;background:var(--bg);font-family:var(--fb);font-weight:300;}

/* SLIDESHOW */
#bg-a,#bg-b{position:fixed;top:0;right:0;bottom:0;left:0;background-size:cover;background-position:center;z-index:0;transition:opacity 2.4s ease-in-out;}
#bg-a{opacity:.42;}#bg-b{opacity:0;}
#vig{position:fixed;top:0;right:0;bottom:0;left:0;background:radial-gradient(ellipse at 50% 35%,transparent 0%,rgba(4,5,9,0.58) 100%);z-index:1;pointer-events:none;}

/* ── Spotlight curseur ── */
#spotlight{
  position:fixed;top:0;left:0;width:100%;height:100%;
  z-index:2;pointer-events:none;
  background:radial-gradient(circle 420px at var(--sx,50%) var(--sy,50%),
    rgba(212,168,83,0.045) 0%,
    rgba(212,168,83,0.012) 40%,
    transparent 70%);
  transition:--sx .08s,--sy .08s;
  opacity:0;-webkit-transition:opacity .6s;transition:opacity .6s;
}
#spotlight.on{opacity:1;}

/* ── Cartes : tilt 3D au survol ── */
.card{
  -webkit-transform-style:preserve-3d;transform-style:preserve-3d;
  -webkit-transform:perspective(900px) rotateX(0) rotateY(0);
  transform:perspective(900px) rotateX(0) rotateY(0);
  -webkit-transition:transform .25s ease,box-shadow .25s ease;
  transition:transform .25s ease,box-shadow .25s ease;
  will-change:transform;
}
/* Reflet interne sur la carte — suit la souris locale */
.card::before{
  content:'';position:absolute;inset:0;border-radius:inherit;z-index:0;
  background:radial-gradient(circle 180px at var(--cx,50%) var(--cy,50%),
    rgba(255,255,255,0.055) 0%,
    transparent 70%);
  pointer-events:none;opacity:0;
  -webkit-transition:opacity .3s;transition:opacity .3s;
}
.card:hover::before{opacity:1;}
/* ══════════════════════════════════════════════
   LAYOUT — compatible TV/iPad/navigateurs anciens
   gap: remplacé par margin-bottom sur les cartes
══════════════════════════════════════════════ */
.grid{
  position:relative;z-index:10;
  display:grid;
  grid-template-columns:320px 1fr;
  grid-gap:24px;
  padding:22px 24px;
  height:100vh;
  max-height:100vh;
  overflow:hidden;
}
.left-col{
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-orient:vertical;-webkit-box-direction:normal;
  -ms-flex-direction:column;flex-direction:column;
  min-height:0;height:100%;
  overflow-y:auto;overflow-x:hidden;
  scrollbar-width:none;
}
.left-col::-webkit-scrollbar{display:none;}
/* Espacement via margin — fonctionne sur tout navigateur TV */
.left-col > .card{ margin-bottom:10px; flex-shrink:0; }
.left-col > .card:last-child{ margin-bottom:0; }
/* RSS dynamique — flex:1 pour remplir l'espace restant */
#card-rss{
  -webkit-box-flex:1;-ms-flex:1 1 auto;flex:1 1 auto;
  min-height:60px;overflow:hidden;
}
#card-rss .ci{ height:100%; }
#card-rss .rss-ticker{
  -webkit-box-flex:1;-ms-flex:1 1 auto;flex:1 1 auto;overflow:hidden;
}
@media(min-width:1800px){
  .grid{grid-template-columns:380px 1fr;grid-gap:30px;padding:28px 30px;}
  .left-col > .card{margin-bottom:14px;}
}
@media(max-width:1023px){
  .grid{grid-template-columns:260px 1fr;grid-gap:14px;padding:14px;}
  .left-col > .card{margin-bottom:8px;}
}
@media(max-height:700px){
  #card-weather{max-height:200px;}
  .w-curve-wrap{display:none;}
}

/* CARD */
.card{
  background:rgba(8,10,17,0.78);
  border:2px solid rgba(255,255,255,0.11);
  border-radius:var(--r);
  overflow:hidden;position:relative;
  animation:fadeUp .5s ease both;
  flex-shrink:0;
  /* Fix border-radius+overflow sur vieux WebKit (TV, iPad ancien) */
  -webkit-mask-image:-webkit-radial-gradient(white,black);
  -webkit-transform:translateZ(0);
  transform:translateZ(0);
  transition:border-color .25s, box-shadow .25s, -webkit-transform .25s;
  transition:border-color .25s, box-shadow .25s, transform .25s;
}
.card:hover{
  border-color:rgba(255,255,255,0.20);
  box-shadow:0 8px 32px rgba(0,0,0,.45), 0 0 0 1px rgba(212,168,83,.08);
  -webkit-transform:translateZ(0) translateY(-2px);
  transform:translateY(-2px);
}
/* Pas de lift sur la carte calendrier (trop grande) */
#card-cal:hover{ -webkit-transform:none; transform:none; box-shadow:0 0 0 1px rgba(212,168,83,.10); }
.card::after{content:'';position:absolute;top:0;right:0;bottom:0;left:0;border-radius:inherit;background:linear-gradient(150deg,rgba(255,255,255,0.025) 0%,transparent 50%);pointer-events:none;}
.ci{padding:10px 13px;display:flex;flex-direction:column;}
#card-cal .ci{height:100%;}
.chip{font-family:var(--fm);font-size:6.5px;letter-spacing:.24em;text-transform:uppercase;color:var(--t3);margin-bottom:5px;flex-shrink:0;transition:color .2s;}
.card:hover .chip{ color:rgba(240,236,228,0.45); }
/* Horloge hover */
#clock-wrap:hover #time-hano{ text-shadow:0 0 22px rgba(212,168,83,.4); }
.divl{height:1px;background:rgba(255,255,255,0.10);margin:6px 0;flex-shrink:0;}

/* ══ CLOCK + POMODORO EMBED ══ */
#card-clock{flex-shrink:0;overflow:hidden;}
#card-clock .ci{padding:0;overflow:hidden;}

/* Conteneur splitté côte à côte */
.ck-split{
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-orient:horizontal;-webkit-box-direction:normal;
  -ms-flex-direction:row;flex-direction:row;
}

/* ─ Moitié gauche : horloge ─ */
.ck-left{
  -webkit-box-flex:1;-ms-flex:1 1 0;flex:1;min-width:0;
  border-right:1px solid rgba(255,255,255,0.07);
}
.ck-top{
  padding:11px 13px 9px;
  background:linear-gradient(135deg,rgba(212,168,83,0.06) 0%,rgba(212,168,83,0.01) 100%);
  border-bottom:1px solid rgba(255,255,255,0.07);
}
.ck-loc{display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:4px;margin-bottom:2px;}
.ck-flag{font-size:10px;}
.ck-city{font-family:var(--fm);font-size:5.5px;letter-spacing:.3em;text-transform:uppercase;color:var(--t3);}
#hanoi-time{
  font-family:var(--fd);font-size:38px;font-weight:800;
  line-height:.95;letter-spacing:.01em;color:var(--t1);
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
}
.sep{color:var(--accent);margin:0 1px;animation:blink 1s step-end infinite;}
@keyframes blink{0%,49%{opacity:.9}50%,100%{opacity:.15}}
#hanoi-date{font-family:var(--fm);font-size:5.5px;color:rgba(240,236,228,0.32);letter-spacing:.06em;margin-top:3px;text-transform:capitalize;}
.ck-bot{
  padding:6px 13px 6px;
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  -webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;
}
.ck-paris-left{display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:4px;}
.ck-flag-sm{font-size:9px;}
.ck-city-sm{font-family:var(--fm);font-size:5.5px;letter-spacing:.3em;text-transform:uppercase;color:var(--t3);}
#paris-time{font-family:var(--fd);font-size:18px;font-weight:800;color:rgba(240,236,228,0.52);letter-spacing:.01em;line-height:1;}
#paris-diff{font-family:var(--fm);font-size:5.5px;color:var(--accent);background:rgba(212,168,83,0.10);border:1px solid rgba(212,168,83,0.20);border-radius:3px;padding:2px 5px;letter-spacing:.04em;}

/* ─ Moitié droite : mini Pomodoro ─ */
.ck-right{
  -webkit-box-flex:1;-ms-flex:1 1 0;flex:1;min-width:0;
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-orient:vertical;-webkit-box-direction:normal;
  -ms-flex-direction:column;flex-direction:column;
  position:relative;
}
.pom-header{
  padding:7px 10px 6px;
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  -webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;
  border-bottom:1px solid rgba(255,255,255,0.07);
}
.pom-header-lbl{font-family:var(--fm);font-size:5.5px;letter-spacing:.28em;text-transform:uppercase;color:var(--t3);}
#pom-fullscreen-btn{
  background:none;border:none;cursor:pointer;
  color:var(--t3);font-size:11px;padding:1px 3px;line-height:1;
  text-decoration:none;display:block;
  transition:color .2s;
}
#pom-fullscreen-btn:hover{color:var(--accent);}
.pom-body{
  -webkit-box-flex:1;-ms-flex:1;flex:1;
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-orient:vertical;-webkit-box-direction:normal;
  -ms-flex-direction:column;flex-direction:column;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  -webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;
  padding:8px 10px;gap:5px;
}
#pom-mode-lbl{font-family:var(--fm);font-size:5.5px;letter-spacing:.28em;text-transform:uppercase;color:var(--accent);opacity:.8;}
#pom-time{
  font-family:var(--fd);font-size:30px;font-weight:800;
  letter-spacing:-.02em;line-height:1;color:var(--t1);
}
#pom-time.pom-urgent{color:rgba(200,90,70,.9);}
.pom-prog-track{width:80%;height:1px;background:rgba(255,255,255,.07);border-radius:1px;overflow:hidden;}
.pom-prog-bar{height:100%;background:var(--accent);border-radius:1px;width:0%;transition:width 1s linear;}
.pom-controls{display:-webkit-box;display:-ms-flexbox;display:flex;gap:7px;-webkit-box-align:center;-ms-flex-align:center;align-items:center;}
#pom-btn{
  width:28px;height:28px;border-radius:50%;
  background:var(--adim);
  border:1px solid rgba(212,168,83,0.35);
  color:var(--accent);
  cursor:pointer;transition:all .18s;
  display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  -webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;
  padding:0;flex-shrink:0;
}
#pom-btn:hover{background:rgba(212,168,83,.22);border-color:rgba(212,168,83,.65);}
#pom-btn svg{display:block;}
.pom-dots{display:-webkit-box;display:-ms-flexbox;display:flex;gap:4px;}
.pom-dot{width:4px;height:4px;border-radius:50%;background:rgba(240,236,228,.15);transition:all .4s;}
.pom-dot.done{background:rgba(240,236,228,.35);}
.pom-dot.active{background:var(--accent);width:10px;border-radius:2px;}

/* ══ WEATHER ══ */
#card-weather{background:rgba(8,10,17,0.55);flex-shrink:0;overflow:hidden;}
#card-weather .ci{overflow:hidden;position:relative;z-index:1;}
#weather-bg{
  position:absolute;top:0;right:0;bottom:0;left:0;z-index:0;
  opacity:.55;transition:background 2s ease;
  pointer-events:none;
}
.w-cur{display:flex;align-items:center;gap:8px;padding-bottom:6px;border-bottom:1px solid var(--border);flex-shrink:0;}
#w-icon{font-size:28px;line-height:1;}
#w-temp{font-family:var(--fd);font-size:38px;font-weight:800;line-height:1;letter-spacing:.01em;color:var(--t1);}
.w-meta{flex:1;}
#w-desc{font-size:9.5px;color:var(--t2);margin-bottom:2px;text-transform:capitalize;}
.w-stats{display:flex;gap:8px;}
.ws .ws-l{font-family:var(--fm);font-size:6.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--t3);}
.ws .ws-v{font-family:var(--fm);font-size:9.5px;color:var(--t2);}
/* SVG courbe pleine largeur + fondu bord droit via mask (indépendant du fond) */
.w-curve-wrap{flex-shrink:0;margin:4px -13px 2px;position:relative;}
.w-curve-wrap::after{ display:none; }
.w-curve-lbl{font-family:var(--fm);font-size:6.5px;letter-spacing:.16em;text-transform:uppercase;color:var(--t3);margin-bottom:3px;padding:0 13px;}
#temp-svg-wrap{
  width:100%;overflow:hidden;display:block;
  -webkit-mask-image:-webkit-linear-gradient(left, black 70%, transparent 100%);
  mask-image:linear-gradient(to right, black 70%, transparent 100%);
}
.w-dl{font-family:var(--fm);font-size:6.5px;letter-spacing:.16em;text-transform:uppercase;color:var(--t3);margin:5px 0 4px;flex-shrink:0;}
.w-days{display:grid;grid-template-columns:repeat(3,1fr);gap:4px;flex-shrink:0;}
.dc{background:var(--gl-light);border:1px solid var(--border);border-radius:7px;padding:4px;display:flex;flex-direction:column;align-items:center;gap:1px;}
.dc-n{font-family:var(--fm);font-size:6.5px;text-transform:uppercase;color:var(--t3);letter-spacing:.1em;}
.dc-i{font-size:18px;line-height:1.1;}
.dc-t{display:flex;gap:3px;align-items:baseline;}
.dc-mx{font-family:var(--fd);font-size:16px;font-weight:800;color:var(--t1);}
.dc-mn{font-family:var(--fm);font-size:8.5px;color:var(--t3);}
.dc-r{font-family:var(--fm);font-size:7px;color:var(--blue);}
.aqi-row{display:flex;align-items:center;gap:6px;padding-top:5px;flex-shrink:0;}
.aqi-dot{width:7px;height:7px;border-radius:50%;background:var(--t3);flex-shrink:0;}
#aqi-val{font-family:var(--fd);font-size:14px;font-weight:800;color:var(--t1);}
#aqi-lbl{font-family:var(--fm);font-size:8px;color:var(--t2);}
.aqi-pm{font-family:var(--fm);font-size:7px;color:var(--t3);margin-left:auto;}

/* ══ MUSIQUE / PODCAST — carte fractionnée ══ */
#card-music{flex-shrink:0;overflow:hidden;}
#card-music .ci{padding:0;height:auto;}

/* Section YouTube (haut) */
#yt-section{border-bottom:1px solid var(--border);}
#sc-tabs{
  display:flex;gap:0;border-bottom:1px solid var(--border);
  overflow-x:auto;scrollbar-width:none;
}
#sc-tabs::-webkit-scrollbar{display:none;}
.sc-tab{
  flex:1;padding:5px 6px;
  font-family:var(--fm);font-size:7px;letter-spacing:.03em;
  color:var(--t3);background:none;border:none;border-right:1px solid var(--border);
  cursor:pointer;transition:color .15s,background .15s;
  -webkit-tap-highlight-color:transparent;white-space:nowrap;text-align:center;
}
.sc-tab:last-child{border-right:none;}
.sc-tab.active{color:var(--accent);background:rgba(212,168,83,.06);}
.sc-tab:hover:not(.active){color:var(--t2);}
/* Iframes cachées — audio seulement */
#sc-stack{position:absolute;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;top:0;left:0;}
.sc-frame{display:none;width:1px;height:1px;border:none;}
.sc-frame.active{display:block;}
/* Volume row */
#vol-row{
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  gap:8px;padding:5px 10px;border-top:1px solid var(--border);
}
#vol-icon{font-size:11px;color:var(--t3);flex-shrink:0;cursor:pointer;-webkit-user-select:none;user-select:none;}
#vol-slider{
  -webkit-box-flex:1;-ms-flex:1;flex:1;
  -webkit-appearance:none;appearance:none;
  height:2px;background:var(--border);border-radius:2px;outline:none;cursor:pointer;
}
#vol-slider::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:10px;height:10px;border-radius:50%;background:var(--accent);cursor:pointer;transition:-webkit-transform .1s;transition:transform .1s;}
#vol-slider::-webkit-slider-thumb:hover{-webkit-transform:scale(1.3);transform:scale(1.3);}
#vol-slider::-moz-range-thumb{width:10px;height:10px;border-radius:50%;background:var(--accent);border:none;cursor:pointer;}
#vol-pct{font-family:var(--fm);font-size:7px;color:var(--t3);min-width:24px;text-align:right;}
#sc-credit{
  padding:3px 10px 4px;font-size:7.5px;color:var(--t3);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-family:var(--fm);
}
#sc-credit a{color:var(--t3);text-decoration:none;}
#sc-credit a:hover{color:var(--accent);}

/* Section Podcast (bas) */
#pod-section{position:relative;}
#pod-header{
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  gap:6px;padding:4px 10px;border-bottom:1px solid var(--border);
}
#pod-chip{font-family:var(--fm);font-size:6px;letter-spacing:.22em;text-transform:uppercase;color:var(--t3);-webkit-box-flex:1;-ms-flex:1;flex:1;}
#morning-badge{
  display:none;font-family:var(--fm);font-size:6.5px;letter-spacing:.08em;
  color:#f0b429;background:rgba(240,180,41,0.13);border:1px solid rgba(240,180,41,0.3);
  padding:2px 7px;border-radius:4px;
}
#morning-badge.on{display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:3px;}
#pod-default-btn{
  display:none;font-family:var(--fm);font-size:6.5px;
  background:none;border:1px solid var(--border);color:var(--t3);
  padding:2px 7px;border-radius:4px;cursor:pointer;transition:all .15s;white-space:nowrap;
}
#pod-default-btn.on{display:block;}
#pod-default-btn:hover{color:var(--accent);border-color:var(--accent);}
#pod-tabs{
  display:-webkit-box;display:-ms-flexbox;display:flex;
  gap:0;border-bottom:1px solid var(--border);overflow-x:auto;scrollbar-width:none;
}
#pod-tabs::-webkit-scrollbar{display:none;}
#pod-info{
  padding:4px 10px 3px;font-family:var(--fm);font-size:7px;color:var(--t2);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-height:16px;
}
#pod-info a{color:var(--t2);text-decoration:none;}
#pod-info a:hover{color:var(--accent);}
#pod-controls{
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  gap:7px;padding:4px 10px 5px;
}
#pod-play-btn{
  flex-shrink:0;
  width:28px;height:28px;border-radius:50%;
  background:var(--adim);border:1px solid rgba(212,168,83,.30);
  color:var(--acc);
  cursor:pointer;transition:background .2s, border-color .2s, opacity .15s;
  display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  -webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;
  padding:0;
}
#pod-play-btn:hover{background:rgba(212,168,83,.22);border-color:rgba(212,168,83,.6);}
#pod-play-btn:active{-webkit-transform:scale(.93);transform:scale(.93);}
#pod-play-btn svg{display:block;}
#pod-prog-wrap{
  -webkit-box-flex:1;-ms-flex:1;flex:1;
  height:3px;background:var(--border);border-radius:2px;cursor:pointer;position:relative;overflow:hidden;
}
#pod-prog{height:100%;background:var(--accent);border-radius:2px;width:0;-webkit-transition:width .4s linear;transition:width .4s linear;}
#pod-time{font-family:var(--fm);font-size:6.5px;color:var(--t3);flex-shrink:0;min-width:50px;text-align:right;}
#pod-vol-icon{font-size:10px;color:var(--t3);cursor:pointer;flex-shrink:0;}
#pod-vol-sl{
  flex-shrink:0;width:42px;-webkit-appearance:none;appearance:none;
  height:2px;background:var(--border);border-radius:2px;outline:none;cursor:pointer;
}
#pod-vol-sl::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:8px;height:8px;border-radius:50%;background:var(--t3);cursor:pointer;}
#pod-vol-sl::-moz-range-thumb{width:8px;height:8px;border-radius:50%;background:var(--t3);border:none;cursor:pointer;}
#pod-status{font-family:var(--fm);font-size:6.5px;color:var(--t3);padding:0 10px 4px;min-height:12px;}

/* ══ TODO TILE ══ */
#card-music{ min-height:210px; }
#view-todo{ display:none;flex-direction:column;height:100%;min-height:210px; }
#view-music{ display:block;position:relative; }

/* En-tête todo */
#todo-header{
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  gap:3px;padding:5px 8px;border-bottom:1px solid var(--border);flex-shrink:0;
}
.todo-nav-btn{
  background:none;border:none;color:var(--t3);cursor:pointer;
  font-size:13px;line-height:1;padding:0 3px;flex-shrink:0;
  transition:color .15s,transform .1s;
}
.todo-nav-btn:hover{ color:var(--accent);transform:scale(1.2); }
#todo-list-name{
  -webkit-box-flex:1;-ms-flex:1;flex:1;text-align:center;
  font-family:var(--fm);font-size:7px;letter-spacing:.18em;
  text-transform:uppercase;color:var(--accent);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.todo-hdr-btn{
  background:none;border:1px solid var(--border);border-radius:3px;
  color:var(--t3);cursor:pointer;font-size:8px;padding:2px 5px;
  line-height:1;flex-shrink:0;transition:color .15s,border-color .15s;
  font-family:var(--fm);
}
.todo-hdr-btn:hover{ color:var(--accent);border-color:rgba(212,168,83,.38); }

/* Liste items */
#todo-items{
  -webkit-box-flex:1;-ms-flex:1;flex:1;
  overflow-y:auto;padding:4px 8px 6px;
  scrollbar-width:none;
}
#todo-items::-webkit-scrollbar{ display:none; }
.todo-item{
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:flex-start;-ms-flex-align:flex-start;align-items:flex-start;
  gap:7px;padding:4px 2px;
  border-bottom:1px solid rgba(255,255,255,.03);
  border-left:2px solid transparent;
  transition:border-left-color .15s,background .15s,padding-left .15s;
}
.todo-item:last-child{ border-bottom:none; }
.todo-item:hover{ border-left-color:var(--accent);background:rgba(255,255,255,.015);padding-left:3px; }
.todo-check{
  width:11px;height:11px;border-radius:50%;border:1px solid var(--t3);
  flex-shrink:0;cursor:pointer;margin-top:2px;position:relative;
  transition:background .15s,border-color .15s,transform .1s;
}
.todo-check:hover{ border-color:var(--accent);transform:scale(1.18); }
.todo-check.checking{ background:rgba(212,168,83,.25);border-color:var(--accent); }
.todo-check.done{ background:var(--accent);border-color:var(--accent); }
.todo-check.done::after{
  content:'✓';position:absolute;top:50%;left:50%;
  -webkit-transform:translate(-50%,-50%);transform:translate(-50%,-50%);
  font-size:6px;color:#07080b;line-height:1;
}
.todo-text{ -webkit-box-flex:1;-ms-flex:1;flex:1;min-width:0; }
.todo-title{
  font-family:var(--fm);font-size:8px;color:var(--t2);
  line-height:1.45;word-break:break-word;
}
.todo-title.done{ text-decoration:line-through;opacity:.3; }
.todo-due{
  font-family:var(--fm);font-size:6px;color:var(--t3);
  white-space:nowrap;flex-shrink:0;margin-top:3px;
}
.todo-due.overdue{ color:#e05c5c; }
.todo-empty{
  font-family:var(--fm);font-size:7.5px;color:var(--t3);
  text-align:center;padding:18px 0;
}

/* Barre décompte retour todo (5s) */
#music-return-bar{
  position:absolute;bottom:0;left:0;height:1px;
  background:var(--accent);opacity:.45;width:0%;
}

/* ══ TODOS MODAL ══ */
#todos-modal{
  position:fixed;inset:0;z-index:900;display:none;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  -webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;
  -webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);
}
#todos-modal.open{
  display:-webkit-box;display:-ms-flexbox;display:flex;
}
#todos-modal-bg{
  position:absolute;inset:0;background:rgba(7,8,11,.82);cursor:pointer;
}
#todos-modal-panel{
  position:relative;z-index:1;
  width:min(96vw,1100px);max-height:88vh;
  background:rgba(12,14,20,.98);
  border:1px solid rgba(255,255,255,.10);border-radius:10px;
  overflow:hidden;
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-orient:vertical;-webkit-box-direction:normal;
  -ms-flex-direction:column;flex-direction:column;
  box-shadow:0 24px 64px rgba(0,0,0,.65);
  animation:modalIn .22s ease both;
}
@keyframes modalIn{ from{opacity:0;transform:translateY(10px) scale(.97);}to{opacity:1;transform:none;} }
#todos-modal-hdr{
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  -webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;
  padding:10px 16px;border-bottom:1px solid var(--border);flex-shrink:0;
}
#todos-modal-title{
  font-family:var(--fm);font-size:8px;letter-spacing:.2em;
  text-transform:uppercase;color:var(--accent);
}
#todos-modal-close{
  background:none;border:1px solid var(--border);color:var(--t3);cursor:pointer;
  width:22px;height:22px;border-radius:50%;
  display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;
  -webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;
  font-size:10px;transition:color .15s,border-color .15s;
}
#todos-modal-close:hover{ color:var(--accent);border-color:rgba(212,168,83,.4); }
#todos-modal-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
  gap:1px;background:var(--border);
  overflow-y:auto;-webkit-box-flex:1;-ms-flex:1;flex:1;
}
.modal-col{
  background:rgba(12,14,20,.98);padding:10px;
  overflow-y:auto;max-height:calc(88vh - 56px);
  scrollbar-width:thin;scrollbar-color:var(--border) transparent;
}
.modal-col::-webkit-scrollbar{ width:3px; }
.modal-col::-webkit-scrollbar-thumb{ background:var(--border);border-radius:2px; }
.modal-col-hdr{
  font-family:var(--fm);font-size:7px;letter-spacing:.18em;
  text-transform:uppercase;color:var(--accent);
  padding-bottom:6px;border-bottom:1px solid var(--border);
  margin-bottom:4px;
  display:-webkit-box;display:-ms-flexbox;display:flex;
  -webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:6px;
}
.modal-col-count{
  font-size:6px;color:var(--t3);background:rgba(255,255,255,.05);
  border-radius:8px;padding:1px 5px;margin-left:auto;
}

/* ══ HOVER — effets réactifs globaux ══ */
/* Cartes — déjà tilt 3D, ajouter glow accent subtil */
.card{ transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease; }
.card:hover{ border-color:rgba(212,168,83,0.18); box-shadow:0 8px 32px rgba(0,0,0,.35),0 0 0 1px rgba(212,168,83,.08); }
#card-cal:hover{ border-color:rgba(212,168,83,0.18); box-shadow:0 8px 32px rgba(0,0,0,.35),0 0 0 1px rgba(212,168,83,.08); }

/* Boutons nav calendrier */
.cal-nav-btn{ transition:background .15s,color .15s,transform .1s; }
.cal-nav-btn:hover{ background:rgba(212,168,83,.14); color:var(--accent); transform:scale(1.08); }

/* Boutons tabs musique/podcast */
.sc-tab{ position:relative; overflow:hidden; }
.sc-tab::after{ content:''; position:absolute; bottom:0; left:50%; width:0; height:1px; background:var(--accent); transition:width .2s,left .2s; }
.sc-tab:hover::after{ width:100%; left:0; }
.sc-tab.active::after{ width:100%; left:0; }

/* Sliders volume */
#vol-slider,#pod-vol-sl{ transition:opacity .15s; }
#vol-slider:hover,#pod-vol-sl:hover{ opacity:1; }

/* Barre de progression podcast */
#pod-prog-wrap{ transition:height .15s,opacity .15s; }
#pod-prog-wrap:hover{ height:5px; }

/* Bouton play/pause musique + podcast */
#yt-play-btn{ transition:opacity .15s,transform .1s,color .15s !important; }
#yt-play-btn:hover{ opacity:1 !important; transform:scale(1.15); }
#pod-play-btn{ transition:background .2s,border-color .2s,transform .1s; }
#pod-play-btn:hover{ transform:scale(1.12); }

/* Items RSS */
.rss-item{ border-left:2px solid transparent; padding-left:6px; transition:color .2s,border-color .2s,padding-left .15s; }
.rss-item:hover{ border-left-color:var(--accent); padding-left:8px; }

/* Prévisions météo */
.dc{ transition:background .15s,border-color .15s,transform .1s; }
.dc:hover{ background:rgba(212,168,83,.07); border-color:rgba(212,168,83,.2); transform:translateY(-1px); }

/* Événements calendrier */
.cal-evt{ transition:background .12s,opacity .12s,transform .1s; }
.cal-evt:hover{ transform:translateX(1px) scaleX(0.995); z-index:10 !important; }

/* Bouton pomodoro mini */
#pom-btn{ transition:background .15s,border-color .15s,transform .1s; }
#pom-btn:hover{ transform:scale(1.1); }

/* Lien ⤢ plein écran pomo */
#pom-fullscreen-btn{ transition:color .15s,transform .1s; }
#pom-fullscreen-btn:hover{ transform:scale(1.2); }

/* Chip étiquettes */
.chip{ transition:color .2s; cursor:default; }
.card:hover .chip{ color:rgba(240,236,228,.5); }

/* AQI dot */
.aqi-dot{ transition:transform .2s; }
#card-weather:hover .aqi-dot{ transform:scale(1.4); }

.rss-item{font-family:var(--fm);font-size:8.5px;color:var(--t2);line-height:1.55;
  white-space:normal;word-break:break-word;
  cursor:pointer;padding:3px 0;border-bottom:1px solid rgba(255,255,255,0.04);transition:color .2s;}
.rss-item:last-child{border-bottom:none;}
.rss-item:hover{color:var(--accent);}

/* ══════════════════════════════════════════════
   CALENDAR 4 JOURS
══════════════════════════════════════════════ */
#card-cal{height:100%;overflow:hidden;animation:fadeUp .5s ease .04s both;display:flex;flex-direction:column;}

/* ── En-tête ── */
#cal-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:7px 12px 5px;border-bottom:1px solid var(--border);flex-shrink:0;gap:8px;
}
.cal-nav{display:flex;align-items:center;gap:5px;flex-shrink:0;}
.cal-nav-btn{
  background:none;border:1px solid var(--border);color:var(--t2);
  font-family:var(--fm);font-size:9px;border-radius:5px;padding:3px 8px;
  cursor:pointer;transition:all .15s;-webkit-tap-highlight-color:transparent;line-height:1;
}
.cal-nav-btn:hover{background:var(--gl-light);color:var(--t1);}
.cal-nav-btn.today-btn{border-color:var(--accent);color:var(--accent);}
.cal-nav-btn.today-btn:hover{background:var(--adim);}
#cal-range-label{font-family:var(--fm);font-size:8.5px;letter-spacing:.06em;color:var(--t2);white-space:nowrap;}
#cal-sync-info{font-family:var(--fm);font-size:6.5px;color:var(--t3);flex:1;text-align:center;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}

/* ── Mini-calendrier dropdown ── */
#cal-mini-wrap{position:relative;flex-shrink:0;}
#cal-mini-btn{
  background:none;border:1px solid var(--border);color:var(--t2);
  font-family:var(--fm);font-size:8px;border-radius:5px;padding:3px 9px;
  cursor:pointer;transition:all .15s;-webkit-tap-highlight-color:transparent;
  display:flex;align-items:center;gap:4px;
}
#cal-mini-btn:hover{background:var(--gl-light);color:var(--t1);}
#cal-mini-btn.open{border-color:var(--accent);color:var(--accent);background:var(--adim);}
#cal-mini-btn .mini-arrow{font-size:7px;transition:transform .2s;}
#cal-mini-btn.open .mini-arrow{transform:rotate(180deg);}

#cal-mini-panel{
  display:none;position:absolute;right:0;top:calc(100% + 6px);z-index:50;
  background:rgba(8,10,17,0.97);border:1px solid var(--bds);border-radius:var(--r);
  padding:10px;width:186px;
  
  box-shadow:0 12px 40px rgba(0,0,0,.6);
}
#cal-mini-panel.visible{display:block;}

/* En-tête du mini-cal */
#cal-mini-nav{display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;}
.mini-nav-btn{
  background:none;border:none;color:var(--t3);font-size:12px;cursor:pointer;padding:2px 5px;
  border-radius:4px;transition:color .15s,background .15s;line-height:1;
}
.mini-nav-btn:hover{color:var(--t1);background:var(--gl-light);}
#cal-mini-month-lbl{font-family:var(--fm);font-size:8px;letter-spacing:.1em;text-transform:uppercase;color:var(--t2);}

/* Grille mini-cal */
#cal-mini-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;}
.mini-dow{font-family:var(--fm);font-size:6px;letter-spacing:.1em;text-transform:uppercase;
  color:var(--t3);text-align:center;padding-bottom:4px;}
.mini-day{
  font-family:var(--fm);font-size:8px;color:var(--t2);
  text-align:center;padding:3px 0;border-radius:4px;cursor:pointer;transition:all .15s;
}
.mini-day:hover:not(.empty){background:var(--gl-light);color:var(--t1);}
.mini-day.empty{cursor:default;}
.mini-day.is-today{background:var(--accent);color:#07080b;font-weight:700;}
.mini-day.in-view{background:var(--adim);color:var(--accent);}
.mini-day.is-today.in-view{background:var(--accent);color:#07080b;}
.mini-day.has-evt::after{content:'';display:block;width:3px;height:3px;border-radius:50%;background:var(--accent);margin:1px auto 0;opacity:.7;}

/* ── Barre des jours ── */
#cal-days-bar{
  display:grid;grid-template-columns:42px repeat(4,1fr);
  border-bottom:1px solid var(--border);flex-shrink:0;
}
.cal-day-hd{padding:4px 3px;text-align:center;}
.cal-day-hd .dh-name{font-family:var(--fm);font-size:7px;letter-spacing:.15em;text-transform:uppercase;color:var(--t3);}
.cal-day-hd .dh-num{
  font-family:var(--fd);font-size:18px;font-weight:600;color:var(--t2);line-height:1;
  width:28px;height:28px;display:flex;align-items:center;justify-content:center;
  border-radius:50%;margin:2px auto 0;
}
.cal-day-hd.is-today .dh-name{color:var(--accent);}
.cal-day-hd.is-today .dh-num{background:var(--accent);color:#07080b;}
.cal-day-hd.is-past .dh-num{color:var(--t3);}

/* ── Corps scrollable ── */
#cal-body{
  flex:1;overflow-y:auto;overflow-x:hidden;position:relative;
  scrollbar-width:thin;scrollbar-color:var(--red) rgba(255,255,255,0.05);
}
#cal-body::-webkit-scrollbar{width:4px;}
#cal-body::-webkit-scrollbar-track{background:rgba(255,255,255,0.03);border-radius:2px;}
#cal-body::-webkit-scrollbar-thumb{background:var(--red);border-radius:2px;}

#cal-grid{
  display:grid;grid-template-columns:42px repeat(4,1fr);
  position:relative;
}

/* Colonne temps */
.cal-time-col{display:flex;flex-direction:column;}
.cal-time-slot{
  padding:0 6px 0 0;font-family:var(--fm);font-size:6px;color:var(--t3);
  letter-spacing:.04em;text-align:right;line-height:0;position:relative;top:-5px;
  height:52px;flex-shrink:0;box-sizing:border-box;
}

/* Colonnes jours */
.cal-col{position:relative;border-left:1px solid var(--border);}
.cal-col-slot{height:52px;border-bottom:1px solid rgba(255,255,255,0.03);}
.cal-col-slot:nth-child(2n){border-bottom-color:rgba(255,255,255,0.06);}

/* ── Événements — couleurs thématiques ── */
.cal-evt{
  position:absolute;left:0;right:0;
  background:var(--adim);border-left:2px solid var(--accent);
  border-radius:4px;padding:2px 5px;overflow:hidden;
  cursor:default;transition:background .15s,opacity .15s;
  min-height:20px;z-index:2;
}
.cal-evt:hover{background:rgba(212,168,83,0.28);}
.cal-evt.is-past{opacity:.35;}
.cal-evt.is-past:hover{opacity:.65;}
/* Catégories */
.cal-evt.cat-sport{background:rgba(76,175,125,0.14);border-left-color:#4caf7d;}
.cal-evt.cat-sport:hover{background:rgba(76,175,125,0.26);}
.cal-evt.cat-cours{background:rgba(91,159,212,0.14);border-left-color:#5b9fd4;}
.cal-evt.cat-cours:hover{background:rgba(91,159,212,0.26);}
.cal-evt.cat-social{background:rgba(172,130,220,0.14);border-left-color:#ac82dc;}
.cal-evt.cat-social:hover{background:rgba(172,130,220,0.26);}
.cal-evt.cat-taches{background:rgba(150,155,165,0.12);border-left-color:#8a8f9a;}
.cal-evt.cat-taches:hover{background:rgba(150,155,165,0.22);}
.cal-evt.cat-jeu{background:rgba(240,120,80,0.13);border-left-color:#e07850;}
.cal-evt.cat-jeu:hover{background:rgba(240,120,80,0.24);}

.cal-evt-time{font-family:var(--fm);font-size:6.5px;color:rgba(212,168,83,.65);display:block;white-space:nowrap;overflow:hidden;}
.cal-evt.cat-sport  .cal-evt-time{color:rgba(76,175,125,.75);}
.cal-evt.cat-cours  .cal-evt-time{color:rgba(91,159,212,.75);}
.cal-evt.cat-social .cal-evt-time{color:rgba(172,130,220,.75);}
.cal-evt.cat-taches .cal-evt-time{color:rgba(150,155,165,.65);}
.cal-evt.cat-jeu    .cal-evt-time{color:rgba(240,120,80,.75);}
.cal-evt-title{font-family:var(--fb);font-size:8.5px;color:var(--t1);font-weight:400;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;margin-top:1px;line-height:1.1;}
/* Évènements courts : masquage adaptatif */
.cal-evt.tiny .cal-evt-time{ display:none; }
.cal-evt.tiny .cal-evt-title{ margin-top:0; font-size:7.5px; }
.cal-evt.micro .cal-evt-time,
.cal-evt.micro .cal-evt-title{ display:none; }

/* ── Marqueur heure actuelle — pleine largeur ── */
#cal-now-line{
  position:absolute;
  /* left sera calculé en JS pour commencer après la col temps */
  right:0;height:2px;
  background:linear-gradient(90deg,transparent 0%,var(--red) 8%,var(--red) 100%);
  z-index:6;pointer-events:none;
}
#cal-now-line::before{
  content:'';position:absolute;left:0;top:-3px;
  width:8px;height:8px;border-radius:50%;
  background:var(--red);box-shadow:0 0 6px var(--red);
}
#cal-now-time-lbl{
  position:absolute;right:4px;top:-9px;
  font-family:var(--fm);font-size:6.5px;color:var(--red);
  letter-spacing:.05em;white-space:nowrap;
}

/* ── Bouton debug caché ── */
#debug-btn{
  position:fixed;bottom:8px;left:10px;z-index:100;
  width:16px;height:16px;border-radius:50%;
  background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);
  cursor:pointer;opacity:0;transition:opacity .3s;
  font-size:0;line-height:0;text-decoration:none;display:block;
}
#debug-btn:hover{opacity:1;background:rgba(212,168,83,.18);border-color:var(--accent);}

/* UTILS */
.loading{font-family:var(--fm);font-size:10px;color:var(--t3);animation:pulse 1.4s ease-in-out infinite;}
.err-txt{font-family:var(--fm);font-size:9px;color:rgba(224,92,92,.7);}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
@-webkit-keyframes fadeUp{from{opacity:0;-webkit-transform:translateY(10px)}to{opacity:1;-webkit-transform:translateY(0)}}
@keyframes pulse{0%,100%{opacity:.25}50%{opacity:.85}}
::-webkit-scrollbar{width:2px;height:2px;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px;}
</style>
</head>
<body>

<!-- ══════════════════════════════════════════════
   ⚙️  CONFIGURATION
══════════════════════════════════════════════ -->
<script>
var CONFIG = {
  weather: { lat:21.0285, lon:105.8542, city:"Hà Nội" },

  calDataUrl:    "/data.json",
  calRefreshUrl: "/api_calendrier.php",
  rssUrl:        "/rss.php",
  wpJsonUrl:     "/wallpapers.json",   // source de vérité des fonds d'écran

  photoInterval: 120,
  photoUrls: []  // rempli dynamiquement par initPhotos() via wallpapers.json
};
</script>

<div id="bg-a"></div>
<div id="bg-b"></div>
<div id="spotlight"></div>
<div id="vig"></div>

<div class="grid">

  <!-- ══════════════ COLONNE GAUCHE ══════════════ -->
  <div class="left-col">

    <!-- HORLOGE + MINI POMODORO -->
    <div class="card" id="card-clock" style="animation-delay:0s">
      <div class="ci">
        <div class="ck-split">
          <!-- Gauche : horloge -->
          <div class="ck-left">
            <div class="ck-top">
              <div class="ck-loc">
                <span class="ck-flag">🇻🇳</span>
                <span class="ck-city">Hà Nội</span>
              </div>
              <div id="hanoi-time">--<span class="sep">:</span>--</div>
              <div id="hanoi-date"></div>
            </div>
            <div class="ck-bot">
              <div class="ck-paris-left">
                <span class="ck-flag-sm">🇫🇷</span>
                <span class="ck-city-sm">Paris</span>
              </div>
              <div id="paris-time">--:--</div>
              <span id="paris-diff"></span>
            </div>
          </div>
          <!-- Droite : mini Pomodoro -->
          <div class="ck-right">
            <div class="pom-header">
              <span class="pom-header-lbl">Pomodoro</span>
              <a id="pom-fullscreen-btn" href="/pomodoro/" target="_blank" title="Plein écran">⤢</a>
            </div>
            <div class="pom-body">
              <div id="pom-mode-lbl">FOCUS</div>
              <div id="pom-time">25:00</div>
              <div class="pom-prog-track">
                <div class="pom-prog-bar" id="pom-prog"></div>
              </div>
              <div class="pom-controls">
                <button id="pom-btn" title="Play / Pause">
                  <!-- SVG play/pause mis à jour par JS -->
                  <svg id="pom-btn-icon" width="10" height="10" viewBox="0 0 10 10" fill="currentColor">
                    <polygon points="2,1 9,5 2,9"/>
                  </svg>
                </button>
                <div class="pom-dots">
                  <div class="pom-dot active" id="pd0"></div>
                  <div class="pom-dot" id="pd1"></div>
                  <div class="pom-dot" id="pd2"></div>
                  <div class="pom-dot" id="pd3"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MÉTÉO -->
    <div class="card" id="card-weather" style="animation-delay:.06s">
      <div id="weather-bg"></div>
      <div class="ci">
        <div class="chip">Météo — <span id="w-city">Hà Nội</span></div>
        <div class="w-cur">
          <div id="w-icon">⋯</div>
          <div id="w-temp"><span class="loading">…</span></div>
          <div class="w-meta">
            <div id="w-desc"></div>
            <div class="w-stats">
              <div class="ws"><span class="ws-l">Ressenti</span><span class="ws-v" id="w-feels">–</span></div>
              <div class="ws"><span class="ws-l">Humidité</span><span class="ws-v" id="w-hum">–</span></div>
              <div class="ws"><span class="ws-l">Vent</span><span class="ws-v" id="w-wind">–</span></div>
            </div>
          </div>
        </div>
        <div class="w-curve-wrap">
          <div class="w-curve-lbl">Température — 12h</div>
          <div id="temp-svg-wrap"><span class="loading">…</span></div>
        </div>
        <div class="w-dl">Prévisions 3 jours</div>
        <div class="w-days" id="w-days">
          <div class="dc"><span class="loading">…</span></div>
          <div class="dc"><span class="loading">…</span></div>
          <div class="dc"><span class="loading">…</span></div>
        </div>
        <div class="divl"></div>
        <div class="aqi-row">
          <div class="aqi-dot" id="aqi-dot"></div>
          <span id="aqi-val"><span class="loading">…</span></span>
          <span id="aqi-lbl" style="margin-left:5px"></span>
          <span class="aqi-pm" id="aqi-pm"></span>
        </div>
      </div>
    </div>

    <!-- TODO + MUSIQUE — carte avec bascule -->
    <div class="card" id="card-music" style="animation-delay:.08s">

      <!-- ══ VUE TODO (défaut) ══ -->
      <div id="view-todo">
        <div id="todo-header">
          <button class="todo-nav-btn" id="todo-prev" title="Liste précédente">&#8249;</button>
          <span id="todo-list-name">QUOTIDIEN</span>
          <button class="todo-nav-btn" id="todo-next" title="Liste suivante">&#8250;</button>
          <button class="todo-hdr-btn" id="todo-expand-btn" title="Toutes les listes">&#8862;</button>
          <button class="todo-hdr-btn" id="todo-music-btn" title="Afficher la musique">&#9834;</button>
        </div>
        <div id="todo-items">
          <div class="todo-empty">Chargement…</div>
        </div>
      </div>

      <!-- ══ VUE MUSIQUE (cachée par défaut) ══ -->
      <div id="view-music">
        <div id="music-return-bar"></div>
        <div class="ci">

          <!-- Moitié haute : YouTube audio -->
          <div id="yt-section">
            <div id="sc-tabs">
              <button class="sc-tab active" data-idx="0">&#9834; Lofi Mix 1</button>
              <button class="sc-tab" data-idx="1">&#9834; Lofi Mix 2</button>
              <button class="sc-tab" data-idx="2">&#9834; Ambient</button>
              <button class="sc-tab" id="music-to-todo-btn" title="Afficher les todos" style="flex:0 0 auto;border-left:1px solid var(--border);padding:5px 8px;">&#9776;</button>
            </div>
            <div id="sc-stack" aria-hidden="true">
              <iframe id="yt-frame-0" class="sc-frame active" frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media"
                src="https://www.youtube.com/embed/SllpB3W5f6s?autoplay=1&mute=1&vq=tiny&enablejsapi=1&loop=1&playlist=SllpB3W5f6s"
              ></iframe>
              <iframe id="yt-frame-1" class="sc-frame" frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media"
                data-src="https://www.youtube.com/embed/J0shA9J-4Nc?autoplay=1&mute=1&vq=tiny&enablejsapi=1&loop=1&playlist=J0shA9J-4Nc"
              ></iframe>
              <iframe id="yt-frame-2" class="sc-frame" frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media"
                data-src="https://www.youtube.com/embed/u9YOvF22tsI?autoplay=1&mute=1&vq=tiny&enablejsapi=1&loop=1&playlist=u9YOvF22tsI"
              ></iframe>
            </div>
            <div id="vol-row">
              <button id="yt-play-btn" title="Play / Pause" style="
                background:none;border:none;color:var(--accent);cursor:pointer;
                display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;
                -webkit-box-align:center;-ms-flex-align:center;align-items:center;
                justify-content:center;width:22px;height:22px;padding:0;flex-shrink:0;
                opacity:.85;transition:opacity .15s;
              ">
                <svg id="yt-play-icon" width="14" height="14" viewBox="0 0 10 10" fill="currentColor">
                  <polygon points="2,1 9,5 2,9"/>
                </svg>
              </button>
              <span id="vol-icon" title="Mute/Unmute">&#128266;</span>
              <input type="range" id="vol-slider" min="0" max="100" value="80">
              <span id="vol-pct">80%</span>
            </div>
            <div id="sc-credit">
              <span id="sc-credit-0">YT · <a href="https://www.youtube.com/watch?v=SllpB3W5f6s" target="_blank">Lofi Mix 1</a></span>
              <span id="sc-credit-1" style="display:none">YT · <a href="https://www.youtube.com/watch?v=J0shA9J-4Nc" target="_blank">Lofi Mix 2</a></span>
              <span id="sc-credit-2" style="display:none">YT · <a href="https://www.youtube.com/watch?v=u9YOvF22tsI" target="_blank">Ambient</a></span>
            </div>
          </div>

          <!-- Moitié basse : Podcast -->
          <div id="pod-section">
            <div id="pod-header">
              <span id="pod-chip">&#127897; PODCASTS</span>
              <span id="morning-badge">&#127749; MODE MATIN</span>
              <button id="pod-default-btn" title="Revenir à la playlist musicale">&#8634; Playlist</button>
            </div>
            <div id="pod-tabs">
              <button class="sc-tab active" data-pod="journal">Journal 15'</button>
              <button class="sc-tab" data-pod="matinale">Grande Matinale</button>
            </div>
            <div id="pod-info">Chargement…</div>
            <div id="pod-controls">
              <button id="pod-play-btn" title="Play/Pause">
                <svg id="pod-play-icon" width="11" height="11" viewBox="0 0 10 10" fill="currentColor">
                  <polygon points="2,1 9,5 2,9"/>
                </svg>
              </button>
              <div id="pod-prog-wrap" title="Avancer"><div id="pod-prog"></div></div>
              <span id="pod-time">--:-- / --:--</span>
              <span id="pod-vol-icon" title="Mute podcast">&#128266;</span>
              <input type="range" id="pod-vol-sl" min="0" max="100" value="90">
            </div>
            <div id="pod-status"></div>
            <audio id="pod-audio" preload="none"></audio>
          </div>

        </div>
      </div><!-- /view-music -->

    </div><!-- /card-music -->

    <!-- RSS LE MONDE -->
    <div class="card" id="card-rss" style="animation-delay:.1s">
      <div class="ci">
        <div class="chip">Le Monde</div>
        <div class="rss-ticker" id="rss-ticker">
          <div class="rss-item"><span class="loading">Chargement…</span></div>
        </div>
      </div>
    </div>

  </div><!-- /left-col -->

  <!-- ══════════════ CALENDRIER 4 JOURS ══════════════ -->
  <div class="card" id="card-cal">
    <div id="cal-header">
      <div class="cal-nav">
        <button class="cal-nav-btn" id="cal-prev">&#8249;</button>
        <button class="cal-nav-btn today-btn" id="cal-today">Auj.</button>
        <button class="cal-nav-btn" id="cal-next">&#8250;</button>
        <span id="cal-range-label">…</span>
      </div>
      <span id="cal-sync-info">–</span>
      <!-- Mini-calendrier mensuel -->
      <div id="cal-mini-wrap">
        <button id="cal-mini-btn">
          <span id="cal-mini-btn-lbl">mois</span>
          <span class="mini-arrow">▾</span>
        </button>
        <div id="cal-mini-panel">
          <div id="cal-mini-nav">
            <button class="mini-nav-btn" id="mini-prev">‹</button>
            <span id="cal-mini-month-lbl">…</span>
            <button class="mini-nav-btn" id="mini-next">›</button>
          </div>
          <div id="cal-mini-grid"></div>
        </div>
      </div>
    </div>
    <div id="cal-days-bar"><div></div></div>
    <div id="cal-body">
      <div id="cal-grid"></div>
    </div>
  </div><!-- /card-cal -->

</div><!-- /grid -->

<!-- Bouton debug discret (hover bas-gauche) -->
<a id="debug-btn" href="/debug.html" title="Debug"></a>



<script>
/* ══════════════════════════════════════════
   SLIDESHOW — URLs hardcodées dans CONFIG
══════════════════════════════════════════ */
var bgA=document.getElementById('bg-a'),bgB=document.getElementById('bg-b');
var bgs=[bgA,bgB],activeBg=0,wpQueue=[];

function shuffleArray(arr){
  var a=arr.slice();
  for(var i=a.length-1;i>0;i--){var j=Math.floor(Math.random()*(i+1));var t=a[i];a[i]=a[j];a[j]=t;}
  return a;
}
function showWallpaper(url){
  var next=1-activeBg;
  bgs[next].style.backgroundImage="url('"+url+"')";
  bgs[next].style.backgroundSize='cover';
  bgs[next].style.backgroundPosition='center';
  bgs[activeBg].style.opacity='0';
  bgs[next].style.opacity='.45';
  activeBg=next;
}
function nextWallpaper(){
  if(!CONFIG.photoUrls.length)return;
  if(!wpQueue.length)wpQueue=shuffleArray(CONFIG.photoUrls);
  showWallpaper(wpQueue.shift());
}
function initPhotos(){
  bgs[0].style.background='linear-gradient(160deg,#0b1a2e 0%,#1a3a2a 50%,#0d1e3a 100%)';
  bgs[0].style.opacity='.55';
  bgs[0].style.transition=bgs[1].style.transition='opacity 2.4s ease-in-out';
  activeBg=0;
  var x=new XMLHttpRequest();
  x.open('GET',CONFIG.wpJsonUrl+'?_='+Date.now(),true);
  x.timeout=5000;
  x.onreadystatechange=function(){
    if(x.readyState!==4)return;
    if(x.status===200){
      try{
        var data=JSON.parse(x.responseText);
        if(data.photos&&data.photos.length){
          CONFIG.photoUrls=data.photos.map(function(p){return p.url;});
        }
      }catch(e){}
    }
    nextWallpaper();
    setInterval(nextWallpaper, CONFIG.photoInterval*1000);
  };
  x.send();
}


/* ══════════════════════════════════════════
   CLOCK
══════════════════════════════════════════ */
function updateClock(){
  var d=new Date();
  try{
    var ht=d.toLocaleTimeString('fr-FR',{timeZone:'Asia/Ho_Chi_Minh',hour:'2-digit',minute:'2-digit',hour12:false}).split(':');
    document.getElementById('hanoi-time').innerHTML=ht[0]+'<span class="sep">:</span>'+ht[1];
    var hd=d.toLocaleDateString('fr-FR',{timeZone:'Asia/Ho_Chi_Minh',weekday:'long',day:'numeric',month:'long'});
    document.getElementById('hanoi-date').textContent=hd.charAt(0).toUpperCase()+hd.slice(1);
    document.getElementById('paris-time').textContent=d.toLocaleTimeString('fr-FR',{timeZone:'Europe/Paris',hour:'2-digit',minute:'2-digit',hour12:false});
    var hDay=parseInt(d.toLocaleDateString('en-US',{timeZone:'Asia/Ho_Chi_Minh',day:'numeric'}),10);
    var pDay=parseInt(d.toLocaleDateString('en-US',{timeZone:'Europe/Paris',day:'numeric'}),10);
    var diff=pDay-hDay;
    document.getElementById('paris-diff').textContent=diff!==0?(diff>0?'+'+diff+'j':diff+'j'):'';
  }catch(e){}
}
updateClock();
setInterval(updateClock,1000);

/* ══════════════════════════════════════════
   WEATHER + SVG CURVE
══════════════════════════════════════════ */
var WMO={0:['☀️','Dégagé'],1:['🌤️','Presque dégagé'],2:['⛅','Partiellement nuageux'],3:['☁️','Couvert'],45:['__fog__','Brouillard'],48:['__fog__','Brouillard givrant'],51:['🌦️','Bruine légère'],53:['🌦️','Bruine'],55:['🌧️','Bruine dense'],61:['🌧️','Pluie légère'],63:['🌧️','Pluie'],65:['🌧️','Forte pluie'],71:['🌨️','Neige légère'],73:['❄️','Neige'],75:['❄️','Forte neige'],80:['🌦️','Averses légères'],81:['🌧️','Averses'],82:['⛈️','Averses fortes'],95:['⛈️','Orage'],96:['⛈️','Orage+grêle'],99:['⛈️','Orage fort']};
var DSH=['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
function wmo(c){return WMO[c]||['🌡️','Inconnu'];}

function buildTempSVG(times,temps,codes,si){
  var n=Math.min(13,times.length-si);
  if(n<2)return '';
  var sl=temps.slice(si,si+n);
  var mn=sl.reduce(function(a,b){return Math.min(a,b)},Infinity)-1;
  var mx=sl.reduce(function(a,b){return Math.max(a,b)},-Infinity)+1;
  var _wrap=document.getElementById('temp-svg-wrap');
  var W=_wrap&&_wrap.offsetWidth>80?_wrap.offsetWidth:340;
  var H=52;
  var xi=function(i){return(i/(n-1))*W;};
  var yi=function(t){return H-((t-mn)/(mx-mn))*(H-14)-7;};
  var pts=sl.map(function(t,i){return xi(i).toFixed(1)+','+yi(t).toFixed(1);}).join(' ');
  var fill=pts+' '+W+','+H+' 0,'+H;
  var emojis='';var prevCode=-1;
  for(var i=0;i<n;i++){
    var code=codes[si+i];
    if(code!==prevCode){
      var ico=wmo(code)[0];
      if(ico==='__fog__'){
        emojis+='<text x="'+xi(i).toFixed(1)+'" y="'+(yi(sl[i])-10).toFixed(1)+'" font-size="13" text-anchor="middle" fill="rgba(180,195,210,0.9)" font-family="sans-serif">≋</text>';
      }else{
        emojis+='<text x="'+xi(i).toFixed(1)+'" y="'+(yi(sl[i])-10).toFixed(1)+'" font-size="12" text-anchor="middle">'+ico+'</text>';
      }
      prevCode=code;
    }
  }
  var labels='';
  for(var j=0;j<n;j+=3){
    var t=times[si+j].split('T')[1].substring(0,5);
    labels+='<text x="'+xi(j).toFixed(1)+'" y="'+(H+10)+'" font-size="8" text-anchor="middle" fill="rgba(240,236,228,0.28)" font-family="DM Mono,monospace">'+t+'</text>';
  }
  var nowMark='';
  try{
    var hn=parseInt(new Date().toLocaleString('en-US',{timeZone:'Asia/Ho_Chi_Minh',hour:'2-digit',hour12:false}),10);
    var startH=parseInt((times[si]||'').split('T')[1]||'0',10);
    var frac=(hn-startH)/Math.max(1,n-1);
    if(frac>=0&&frac<=1)nowMark='<line x1="'+(frac*W).toFixed(1)+'" y1="0" x2="'+(frac*W).toFixed(1)+'" y2="'+H+'" stroke="rgba(224,92,92,0.55)" stroke-width="1" stroke-dasharray="3,2"/>';
  }catch(e){}
  return '<svg viewBox="0 0 '+W+' '+(H+14)+'" width="100%" height="66" xmlns="http://www.w3.org/2000/svg" overflow="visible"><defs><linearGradient id="tg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#d4a853" stop-opacity="0.22"/><stop offset="100%" stop-color="#d4a853" stop-opacity="0"/></linearGradient></defs>'+nowMark+'<polygon points="'+fill+'" fill="url(#tg)"/><polyline points="'+pts+'" fill="none" stroke="#d4a853" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'+emojis+labels+'</svg>';
}

function fetchWeather(){
  var l=CONFIG.weather;
  document.getElementById('w-city').textContent=l.city;
  var url='https://api.open-meteo.com/v1/forecast?latitude='+l.lat+'&longitude='+l.lon
    +'&current=temperature_2m,apparent_temperature,weather_code,wind_speed_10m,relative_humidity_2m'
    +'&hourly=temperature_2m,weather_code,precipitation_probability'
    +'&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max'
    +'&timezone=Asia%2FBangkok&forecast_days=4&wind_speed_unit=kmh';
  var x=new XMLHttpRequest();
  x.open('GET',url,true);x.timeout=10000;
  x.onreadystatechange=function(){
    if(x.readyState!==4||x.status!==200)return;
    try{
      var data=JSON.parse(x.responseText);
      var c=data.current,w=wmo(c.weather_code);
      var wicon=document.getElementById('w-icon');
      if(w[0]==='__fog__'){
        wicon.innerHTML='<img src="https://openweathermap.org/img/wn/50d@2x.png" style="width:32px;height:32px;" alt="brouillard">';
      }else{wicon.textContent=w[0];}
      setWeatherBg(c.weather_code);
      document.getElementById('w-temp').textContent=Math.round(c.temperature_2m)+'°';
      document.getElementById('w-desc').textContent=w[1];
      document.getElementById('w-feels').textContent=Math.round(c.apparent_temperature)+'°';
      document.getElementById('w-hum').textContent=c.relative_humidity_2m+'%';
      document.getElementById('w-wind').textContent=Math.round(c.wind_speed_10m)+' km/h';
      var h=data.hourly,hanoiH=0;
      try{hanoiH=parseInt(new Date().toLocaleString('en-US',{timeZone:'Asia/Ho_Chi_Minh',hour:'2-digit',hour12:false}),10);}catch(e){}
      var si=0;
      for(var i=0;i<h.time.length;i++){if(parseInt(h.time[i].split('T')[1],10)===hanoiH){si=i;break;}}
      document.getElementById('temp-svg-wrap').innerHTML=buildTempSVG(h.time,h.temperature_2m,h.weather_code,si);
      var dc=document.getElementById('w-days');dc.innerHTML='';
      for(var k=1;k<=3;k++){
        if(!data.daily.time[k])continue;
        var dd=new Date(data.daily.time[k]+'T12:00:00'),dw=wmo(data.daily.weather_code[k]),rain=data.daily.precipitation_probability_max[k]||0;
        var card=document.createElement('div');card.className='dc';
        card.innerHTML='<span class="dc-n">'+DSH[dd.getDay()]+' '+dd.getDate()+'</span><span class="dc-i">'+dw[0]+'</span><div class="dc-t"><span class="dc-mx">'+Math.round(data.daily.temperature_2m_max[k])+'°</span><span class="dc-mn">'+Math.round(data.daily.temperature_2m_min[k])+'°</span></div><span class="dc-r">💧'+rain+'%</span>';
        dc.appendChild(card);
      }
    }catch(e){document.getElementById('w-temp').innerHTML='<span class="err-txt">Erreur</span>';}
  };
  x.onerror=function(){document.getElementById('w-temp').innerHTML='<span class="err-txt">–</span>';};
  x.send();
}

var AQI_L=[{max:50,color:'#4caf7d',lbl:'Bon'},{max:100,color:'#f0b429',lbl:'Modéré'},{max:150,color:'#ff8c00',lbl:'Mauvais (sensibles)'},{max:200,color:'#e05c5c',lbl:'Mauvais'},{max:300,color:'#9c27b0',lbl:'Très mauvais'},{max:9999,color:'#7f0000',lbl:'Dangereux'}];
function aqiLvl(v){for(var i=0;i<AQI_L.length;i++){if(v<=AQI_L[i].max)return AQI_L[i];}return AQI_L[5];}
function fetchAQI(){
  var l=CONFIG.weather;
  var x=new XMLHttpRequest();
  x.open('GET','https://air-quality-api.open-meteo.com/v1/air-quality?latitude='+l.lat+'&longitude='+l.lon+'&current=us_aqi,pm2_5,pm10',true);
  x.timeout=10000;
  x.onreadystatechange=function(){
    if(x.readyState!==4||x.status!==200)return;
    try{
      var data=JSON.parse(x.responseText);
      var v=data.current.us_aqi,lvl=aqiLvl(v);
      var dot=document.getElementById('aqi-dot');
      dot.style.background=lvl.color;dot.style.boxShadow='0 0 6px '+lvl.color;
      document.getElementById('aqi-val').textContent=v;
      document.getElementById('aqi-lbl').textContent=lvl.lbl;
      var pm25=data.current.pm2_5,pm10=data.current.pm10;
      document.getElementById('aqi-pm').textContent='PM2.5:'+(pm25!=null?Math.round(pm25)+'μg':'–')+' PM10:'+(pm10!=null?Math.round(pm10)+'μg':'–');
    }catch(e){document.getElementById('aqi-val').innerHTML='<span class="err-txt">–</span>';}
  };
  x.send();
}
fetchWeather();fetchAQI();
setInterval(fetchWeather,10*60*1000);setInterval(fetchAQI,30*60*1000);

/* ══════════════════════════════════════════
   RSS — via rss.php (serveur local, XHR)
══════════════════════════════════════════ */
var rssItems=[],rssIdx=0;
function fetchRSS(){
  var x=new XMLHttpRequest();
  x.open('GET',CONFIG.rssUrl+'?_='+Date.now(),true);
  x.timeout=8000;
  x.onreadystatechange=function(){
    if(x.readyState!==4)return;
    if(x.status===200){
      try{
        var d=JSON.parse(x.responseText);
        if(d.items&&d.items.length){
          rssItems=d.items;
          renderRSS();
        }
      }catch(e){}
    }
  };
  x.send();
}
function renderRSS(){
  if(!rssItems.length)return;
  var ticker=document.getElementById('rss-ticker');ticker.innerHTML='';
  for(var i=0;i<Math.min(4,rssItems.length);i++){
    var item=rssItems[(rssIdx+i)%rssItems.length];
    var div=document.createElement('div');div.className='rss-item';
    div.textContent='• '+item.title;
    (function(url){if(url)div.addEventListener('click',function(){window.open(url,'_blank');});})(item.link);
    ticker.appendChild(div);
  }
}
fetchRSS();
setInterval(fetchRSS,15*60*1000);
setInterval(function(){rssIdx=(rssIdx+1)%Math.max(1,rssItems.length);renderRSS();},8000);

/* ══════════════════════════════════════════
   INIT
══════════════════════════════════════════ */

/* ══════════════════════════════════════════
   FOND MÉTÉO iOS-STYLE
══════════════════════════════════════════ */
var WX_BG={
  sun_d: 'linear-gradient(160deg,#1a3a6e 0%,#e8a84a 100%)',
  sun_n: 'linear-gradient(160deg,#040d21 0%,#1a2e5e 100%)',
  cloud: 'linear-gradient(160deg,#2c3e50 0%,#4a5568 100%)',
  pcloud:'linear-gradient(160deg,#1a3a6e 0%,#6b7d92 100%)',
  rain:  'linear-gradient(160deg,#0d1b2e 0%,#1e3a5f 60%,#0d2035 100%)',
  storm: 'linear-gradient(160deg,#1a0533 0%,#2d1b4e 60%,#0d0d0d 100%)',
  snow:  'linear-gradient(160deg,#a8c4d8 0%,#e8f0f7 100%)',
  fog:   'linear-gradient(160deg,#4a5568 0%,#718096 100%)',
};
function setWeatherBg(code){
  var h=parseInt(new Date().toLocaleString('en-US',{timeZone:'Asia/Ho_Chi_Minh',hour:'2-digit',hour12:false}),10)||12;
  var night=(h<6||h>=20);
  var bg=WX_BG.sun_d;
  if(code===0){bg=night?WX_BG.sun_n:WX_BG.sun_d;}
  else if(code<=2){bg=WX_BG.pcloud;}
  else if(code===3){bg=WX_BG.cloud;}
  else if(code<=48){bg=WX_BG.fog;}
  else if(code<=67||code<=82){bg=WX_BG.rain;}
  else if(code<=77){bg=WX_BG.snow;}
  else{bg=WX_BG.storm;}
  var el=document.getElementById('weather-bg');
  if(el)el.style.background=bg;
}

/* ══════════════════════════════════════════
   BUS AUDIO — exclusivité entre sources
══════════════════════════════════════════ */
var AUDIO_BUS = (function(){
  var sources = {};
  return {
    register: function(id, stopFn){ sources[id] = stopFn; },
    stop: function(except){
      for(var k in sources){ if(k !== except && sources[k]) sources[k](); }
    }
  };
})();

/* ══════════════════════════════════════════
   MUSIQUE — YouTube audio uniquement (iframes cachées)
══════════════════════════════════════════ */
(function(){
  var tabs    = document.querySelectorAll('#sc-tabs .sc-tab');
  var credits = document.querySelectorAll('#sc-credit>span');
  var frames  = [
    document.getElementById('yt-frame-0'),
    document.getElementById('yt-frame-1'),
    document.getElementById('yt-frame-2')
  ];
  var volSlider  = document.getElementById('vol-slider');
  var volPct     = document.getElementById('vol-pct');
  var volIcon    = document.getElementById('vol-icon');
  var playBtn    = document.getElementById('yt-play-btn');
  var playIcon   = document.getElementById('yt-play-icon');
  var currentVol = 80;
  var prevVol    = 80;
  var activeIdx  = 0;
  var ytPlaying  = false;

  function setYtIcon(playing){
    if(!playIcon) return;
    playIcon.innerHTML = playing
      ? '<rect x="2" y="1" width="2.5" height="8"/><rect x="5.5" y="1" width="2.5" height="8"/>'
      : '<polygon points="2,1 9,5 2,9"/>';
    ytPlaying = playing;
  }

  function ytCmd(fr, func, args){
    if(!fr || !fr.contentWindow) return;
    try{
      fr.contentWindow.postMessage(JSON.stringify({
        event:'command', func:func, args:args||''
      }),'*');
    }catch(e){}
  }
  function unmute(fr){
    setTimeout(function(){
      ytCmd(fr,'unMute');
      ytCmd(fr,'setVolume',[currentVol]);
    }, 800);
  }
  /* ── Écoute messages YT (état lecture) ── */
  window.addEventListener('message', function(e){
    try{
      var d = JSON.parse(e.data);
      if(d.event === 'infoDelivery' && typeof d.info === 'object' && typeof d.info.playerState !== 'undefined'){
        setYtIcon(d.info.playerState === 1); /* 1 = playing */
      }
    }catch(e2){}
  });

  /* ── Bouton play/pause YT ── */
  if(playBtn){
    playBtn.addEventListener('click', function(){
      var fr = frames[activeIdx];
      if(!fr) return;
      if(!fr.src && fr.dataset.src){
        fr.src = fr.dataset.src;
        fr.addEventListener('load', function(){ unmute(fr); setYtIcon(true); },{once:true});
        return;
      }
      if(ytPlaying){
        ytCmd(fr,'pauseVideo');
        setYtIcon(false);
      } else {
        AUDIO_BUS.stop('yt'); /* coupe podcast */
        ytCmd(fr,'playVideo');
        unmute(fr);
        setYtIcon(true);
      }
    });
  }

  /* Enregistrement dans le bus audio */
  AUDIO_BUS.register('yt', function(){
    frames.forEach(function(f){ if(f&&f.src) ytCmd(f,'pauseVideo'); });
    setYtIcon(false);
  });

  function pauseOthers(exceptIdx){
    frames.forEach(function(f,i){
      if(i!==exceptIdx && f && f.src) ytCmd(f,'pauseVideo');
    });
  }

  tabs.forEach(function(tab){
    tab.addEventListener('click', function(){
      var idx = parseInt(this.dataset.idx, 10);
      /* Exclusivité : coupe podcast via bus */
      AUDIO_BUS.stop('yt');
      pauseOthers(idx);
      tabs.forEach(function(t){ t.classList.remove('active'); });
      frames.forEach(function(f){ if(f) f.classList.remove('active'); });
      credits.forEach(function(c){ c.style.display='none'; });
      this.classList.add('active');
      var fr = frames[idx];
      if(fr){ fr.classList.add('active'); }
      if(credits[idx]) credits[idx].style.display='';
      if(fr){
        if(fr.dataset.src && !fr.src){
          fr.src = fr.dataset.src;
          fr.addEventListener('load', function(){ unmute(fr); setYtIcon(true); },{once:true});
        } else {
          ytCmd(fr,'playVideo');
          unmute(fr);
          setYtIcon(true);
        }
      }
      activeIdx = idx;
    });
  });

  function applyVolume(vol){
    var fr = frames[activeIdx];
    if(fr && fr.src){
      ytCmd(fr,'setVolume',[vol]);
      if(vol===0) ytCmd(fr,'mute');
      else ytCmd(fr,'unMute');
    }
  }
  function updateVolUI(vol){
    volPct.textContent = vol+'%';
    volIcon.textContent = vol===0?'🔇':vol<40?'🔉':'🔊';
  }
  volSlider.addEventListener('input', function(){
    currentVol = parseInt(this.value,10);
    updateVolUI(currentVol);
    applyVolume(currentVol);
    if(currentVol>0) prevVol=currentVol;
  });
  volIcon.addEventListener('click', function(){
    if(currentVol>0){ prevVol=currentVol; currentVol=0; }
    else { currentVol=prevVol||80; }
    volSlider.value=currentVol;
    updateVolUI(currentVol);
    applyVolume(currentVol);
  });
  updateVolUI(currentVol);

  /* ── Exposé globalement ── */
  window._ytPause = function(){
    var fr = frames[activeIdx];
    if(fr && fr.src){ ytCmd(fr,'pauseVideo'); setYtIcon(false); }
  };
  window._ytPlay = function(){
    var fr = frames[activeIdx];
    if(fr && fr.src){ ytCmd(fr,'playVideo'); unmute(fr); setYtIcon(true); }
  };
  window._ytMute = function(){
    frames.forEach(function(f){ if(f&&f.src) ytCmd(f,'mute'); });
  };
  window._ytUnmute = function(){
    var fr = frames[activeIdx];
    if(fr && fr.src){ ytCmd(fr,'unMute'); ytCmd(fr,'setVolume',[currentVol]); }
  };
})();

/* ══════════════════════════════════════════
   PODCAST — France Info
   Flux RSS via proxy local rss_podcast.php
══════════════════════════════════════════ */
(function(){
  var FEEDS = {
    journal:  'https://radiofrance-podcast.net/podcast09/rss_12495.xml',
    matinale: 'https://radiofrance-podcast.net/podcast09/rss_10241.xml'
  };
  var PROXY = '/rss_podcast.php?url=';

  var audio      = document.getElementById('pod-audio');
  var playBtn    = document.getElementById('pod-play-btn');
  var progWrap   = document.getElementById('pod-prog-wrap');
  var progBar    = document.getElementById('pod-prog');
  var timeEl     = document.getElementById('pod-time');
  var infoEl     = document.getElementById('pod-info');
  var statusEl   = document.getElementById('pod-status');
  var volSl      = document.getElementById('pod-vol-sl');
  var volIc      = document.getElementById('pod-vol-icon');
  var podTabs    = document.querySelectorAll('#pod-tabs .sc-tab');
  var morningBdg = document.getElementById('morning-badge');
  var defBtn     = document.getElementById('pod-default-btn');

  var curType    = 'journal';
  var podVol     = 0.9;
  var podPrevVol = 0.9;

  function checkMorning(){
    var h = new Date().getHours();
    return (h >= 6 && h < 10);
  }
  function fmtSec(s){
    if(isNaN(s)||s<=0) return '--:--';
    var m=Math.floor(s/60), sec=Math.floor(s%60);
    return (m<10?'0':'')+m+':'+(sec<10?'0':'')+sec;
  }
  function parseRSS(xml){
    var items=[];
    var matches=xml.match(/<item[\s\S]*?<\/item>/gi)||[];
    for(var i=0;i<matches.length;i++){
      var raw=matches[i];
      var title=(raw.match(/<title[^>]*>(?:<!\[CDATA\[)?([\s\S]*?)(?:\]\]>)?<\/title>/i)||[])[1]||'';
      var enc=(raw.match(/enclosure[^>]+url="([^"]+)"/i)||[])[1]||'';
      var dur=(raw.match(/<itunes:duration[^>]*>([\s\S]*?)<\/itunes:duration>/i)||[])[1]||'';
      var pub=(raw.match(/<pubDate[^>]*>([\s\S]*?)<\/pubDate>/i)||[])[1]||'';
      if(enc) items.push({title:title.trim(),url:enc,dur:dur.trim(),date:pub.trim()});
    }
    return items;
  }
  function setStatus(msg){ if(statusEl) statusEl.textContent=msg; }
  function formatDate(d){
    try{
      var dt=new Date(d);
      return dt.toLocaleDateString('fr-FR',{weekday:'short',day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'});
    }catch(e){return d;}
  }

  function setPodIcon(playing){
    var ic = document.getElementById('pod-play-icon');
    if(!ic) return;
    ic.innerHTML = playing
      ? '<rect x="2" y="1" width="2.5" height="8"/><rect x="5.5" y="1" width="2.5" height="8"/>'
      : '<polygon points="2,1 9,5 2,9"/>';
  }

  function loadFeed(type, autoplay){
    curType = type;
    var feedUrl = FEEDS[type];
    if(!feedUrl){ setStatus('Flux non configuré'); return; }
    infoEl.textContent = 'Chargement…';
    setStatus('');
    var x=new XMLHttpRequest();
    x.open('GET', PROXY+encodeURIComponent(feedUrl)+'&_='+Date.now(), true);
    x.timeout=12000;
    x.onreadystatechange=function(){
      if(x.readyState!==4)return;
      if(x.status!==200){
        infoEl.textContent='Flux indisponible';
        setStatus('Vérifier la connexion');
        return;
      }
      var items=parseRSS(x.responseText);
      if(!items.length){ infoEl.textContent='Aucun épisode trouvé'; return; }
      var ep=items[0];
      infoEl.innerHTML='<a href="'+ep.url+'" target="_blank" title="'+ep.title+'">'+
        ep.title.substring(0,55)+(ep.title.length>55?'…':'')+'</a>';
      setStatus(ep.date ? formatDate(ep.date) : '');
      audio.src=ep.url;
      audio.volume=podVol;
      progBar.style.width='0';
      timeEl.textContent='--:-- / --:--';
      if(autoplay){
        audio.play().then(function(){
          setPodIcon(true);
          if(window._ytPause) window._ytPause(); /* coupe YT */
        }).catch(function(){ setPodIcon(false); setStatus('Autoplay bloqué — cliquez ▶'); });
      } else {
        setPodIcon(false);
      }
    };
    x.send();
  }

  playBtn.addEventListener('click',function(){
    if(!audio.src||audio.src===window.location.href){ loadFeed(curType,true); return; }
    if(audio.paused){
      AUDIO_BUS.stop('podcast'); /* coupe YT */
      audio.play().then(function(){ setPodIcon(true); }).catch(function(){ setPodIcon(false); });
    } else {
      audio.pause();
      setPodIcon(false);
    }
  });

  /* Enregistrement dans le bus audio */
  AUDIO_BUS.register('podcast', function(){
    audio.pause(); setPodIcon(false);
  });

  audio.addEventListener('timeupdate',function(){
    if(!audio.duration) return;
    var pct=(audio.currentTime/audio.duration)*100;
    progBar.style.width=pct.toFixed(1)+'%';
    timeEl.textContent=fmtSec(audio.currentTime)+' / '+fmtSec(audio.duration);
  });

  progWrap.addEventListener('click',function(e){
    if(!audio.duration) return;
    var rect=this.getBoundingClientRect();
    audio.currentTime=((e.clientX-rect.left)/rect.width)*audio.duration;
  });

  audio.addEventListener('ended',function(){
    setPodIcon(false);
    setStatus('Épisode terminé — rechargement…');
    setTimeout(function(){ loadFeed(curType,true); },1500);
  });

  function applyPodVol(v){
    audio.volume=v; audio.muted=(v===0);
    volIc.textContent=v===0?'🔇':v<0.4?'🔉':'🔊';
  }
  volSl.addEventListener('input',function(){
    podVol=parseInt(this.value,10)/100;
    if(podVol>0) podPrevVol=podVol;
    applyPodVol(podVol);
  });
  volIc.addEventListener('click',function(){
    if(podVol>0){ podPrevVol=podVol; podVol=0; }
    else { podVol=podPrevVol||0.9; }
    volSl.value=Math.round(podVol*100);
    applyPodVol(podVol);
  });
  applyPodVol(podVol);

  podTabs.forEach(function(tab){
    tab.addEventListener('click',function(){
      podTabs.forEach(function(t){t.classList.remove('active');});
      this.classList.add('active');
      var wasPlaying=!audio.paused;
      audio.pause(); setPodIcon(false);
      loadFeed(this.dataset.pod, wasPlaying);
    });
  });

  defBtn.addEventListener('click',function(){
    audio.pause(); setPodIcon(false);
    morningBdg.classList.remove('on');
    defBtn.classList.remove('on');
    if(window._ytPlay) window._ytPlay();
  });

  function activateMorningMode(){
    morningBdg.classList.add('on');
    defBtn.classList.add('on');
    podTabs.forEach(function(t){ t.classList.toggle('active', t.dataset.pod==='matinale'); });
    AUDIO_BUS.stop('podcast'); /* coupe YT */
    /* Charger sans autoplay — déclencher au premier touch/click si autoplay bloqué */
    loadFeed('matinale', false);
    var morningDeferred = true;
    function tryMorningPlay(){
      if(!morningDeferred) return;
      morningDeferred = false;
      document.removeEventListener('click', tryMorningPlay);
      document.removeEventListener('keydown', tryMorningPlay);
      if(audio.src && audio.paused){
        audio.play().then(function(){
          setPodIcon(true);
          AUDIO_BUS.stop('podcast');
        }).catch(function(){ setPodIcon(false); });
      }
    }
    /* Tenter l'autoplay direct d'abord */
    setTimeout(function(){
      if(audio.src){
        audio.play().then(function(){
          setPodIcon(true);
          AUDIO_BUS.stop('podcast');
          morningDeferred = false;
        }).catch(function(){
          /* Bloqué par le navigateur → attendre une interaction */
          document.addEventListener('click',   tryMorningPlay, {once:true});
          document.addEventListener('keydown', tryMorningPlay, {once:true});
        });
      }
    }, 1200);
  }

  if(checkMorning()){ activateMorningMode(); }
  else { loadFeed('journal',false); }

})();

/* ══════════════════════════════════════════
   CALENDRIER 4 JOURS
══════════════════════════════════════════ */
(function(){

  /* ── Constantes ── */
  var NB_DAYS    = 4;
  var HOUR_START = 7;
  var HOUR_END   = 23;
  var SLOT_H     = 52;   // px par heure
  var TIME_COL_W = 42;   // px colonne temps (doit matcher le CSS)
  var DAYS_FR    = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
  var MONTHS_FR  = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
  var MONTHS_FR_S= ['jan','fév','mar','avr','mai','jun','jul','aoû','sep','oct','nov','déc'];

  /* ── État ── */
  var allEvents  = [];
  var dayOffset  = 0;      // offset en jours depuis aujourd'hui
  var miniMonth  = null;   // {year, month} du mini-calendrier
  var nowTimer   = null;
  var miniOpen   = false;

  /* ── Utilitaires ── */
  function today0() {
    var d = new Date(); d.setHours(0,0,0,0); return d;
  }
  function addDays(d, n) {
    var r = new Date(d); r.setDate(r.getDate() + n); return r;
  }
  function parseFrDate(str) {
    if (!str) return null;
    var p = str.split(' '), d = p[0].split('/'), t = (p[1]||'00:00').split(':');
    return new Date(+d[2], +d[1]-1, +d[0], +t[0], +t[1], 0);
  }
  function fmtTime(d) {
    return ('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2);
  }
  function fmtShortDate(d) {
    return d.getDate()+' '+MONTHS_FR_S[d.getMonth()];
  }
  function sameDay(a, b) {
    return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate();
  }

  /* ── Chargement données — XHR pur (compat TV) ── */
  function loadCalData() {
    var x=new XMLHttpRequest();
    x.open('GET',CONFIG.calDataUrl+'?_='+Date.now(),true);
    x.timeout=10000;
    x.onreadystatechange=function(){
      if(x.readyState!==4)return;
      if(x.status===200){
        try{
          var json=JSON.parse(x.responseText);
          allEvents=(json.data||[]).map(function(e){
            var start = parseFrDate(e.date_formattee);
            var dur = 60;
            if (e.duree_minutes && parseInt(e.duree_minutes) > 0) {
              dur = parseInt(e.duree_minutes);
            } else if (e.timestamp_fin && e.timestamp) {
              dur = Math.round((parseInt(e.timestamp_fin) - parseInt(e.timestamp)) / 60);
              if (dur <= 0 || dur > 1440) dur = 60;
            }
            return{titre:e.titre, date:start, ts:e.timestamp, dur:dur};
          }).filter(function(e){return e.date;});

          /* ── Déduplication : supprimer les événements de même titre
             à moins de 60 minutes d'intervalle (doublons iCal) ── */
          allEvents = (function(evts){
            var seen = {}; // clé : titre + tranche horaire (arrondie à 60min)
            return evts.filter(function(e){
              var bucket = e.titre + '|' + Math.floor(e.date.getTime() / (60*60*1000));
              if (seen[bucket]) return false;
              seen[bucket] = true;
              return true;
            });
          })(allEvents);
          var meta=json.metadata||{};
          document.getElementById('cal-sync-info').textContent=
            (meta.derniere_maj?'⟳ '+meta.derniere_maj:'')+
            (meta.compte_total?'  ·  '+meta.compte_total+' évts':'');
          render();renderMiniCal();
        }catch(e){
          document.getElementById('cal-sync-info').textContent='⚠ Données invalides';
          render();
        }
      } else {
        document.getElementById('cal-sync-info').textContent='⚠ Données inaccessibles ('+x.status+')';
        render();
      }
    };
    x.onerror=function(){
      document.getElementById('cal-sync-info').textContent='⚠ Erreur réseau';
      render();
    };
    x.send();
  }
  function refreshServer() {
    var x=new XMLHttpRequest();
    x.open('GET',CONFIG.calRefreshUrl+'?_='+Date.now(),true);
    x.timeout=30000;
    x.onreadystatechange=function(){
      if(x.readyState===4) loadCalData();
    };
    x.send();
  }

  /* ── Catégorie couleur selon titre ── */
  function evtCategory(titre) {
    /* Normalisation : retrait des accents pour compat vieux navigateurs
       (evite les regex avec caracteres Unicode dans le pattern)        */
    var t = titre.toLowerCase()
      .replace(/[àâä]/g,'a').replace(/[éèêë]/g,'e').replace(/[îï]/g,'i')
      .replace(/[ôö]/g,'o').replace(/[ùûü]/g,'u').replace(/ç/g,'c')
      .replace(/œ/g,'oe').replace(/æ/g,'ae');

    /* Sport / activite physique */
    var sport = ['sport','seance','entrainement','gym','yoga','running',
      'course','musculation','fitness','jambes','haut du corps','cardio','natation'];
    for (var si=0; si<sport.length; si++) { if (t.indexOf(sport[si]) !== -1) return 'sport'; }

    /* Cours / seminaire / academique */
    var cours = ['cours','seminaire','formation','classe','atelier','conference',
      'lecture','droit','vietnamien','memoire','synthese','travaux diriges'];
    for (var ci=0; ci<cours.length; ci++) { if (t.indexOf(cours[ci]) !== -1) return 'cours'; }

    /* Social / appel / RDV */
    var social = ['appel','appeler','telephone','rdv','rendez-vous','rendez vous',
      'gwendoline','mamie','amis','diner','repas','fete','anniversaire','axel','reunion'];
    for (var oi=0; oi<social.length; oi++) { if (t.indexOf(social[oi]) !== -1) return 'social'; }

    /* Taches / menage */
    var taches = ['menage','nettoy','ranger','achat','admin',
      'facture','dossier','paperasse','horaires'];
    for (var ti=0; ti<taches.length; ti++) { if (t.indexOf(taches[ti]) !== -1) return 'taches'; }

    /* Jeux / loisirs */
    var jeu = ['jeu','epic','game','loisir','cinema','film','serie'];
    for (var ji=0; ji<jeu.length; ji++) { if (t.indexOf(jeu[ji]) !== -1) return 'jeu'; }

    return ''; /* defaut accent dore */
  }

  /* ════════════════════════════════
     RENDU PRINCIPAL 4 JOURS
  ════════════════════════════════ */
  function render() {
    var tod = today0();
    var start = addDays(tod, dayOffset);

    /* Label plage */
    var end3 = addDays(start, NB_DAYS - 1);
    document.getElementById('cal-range-label').textContent =
      fmtShortDate(start) + ' – ' + fmtShortDate(end3) + ' ' + end3.getFullYear();

    /* Barre des jours */
    var bar = document.getElementById('cal-days-bar');
    bar.innerHTML = '<div></div>';
    for (var i = 0; i < NB_DAYS; i++) {
      var day = addDays(start, i);
      var hd = document.createElement('div');
      hd.className = 'cal-day-hd';
      if (sameDay(day, tod)) hd.classList.add('is-today');
      else if (day < tod)    hd.classList.add('is-past');
      hd.innerHTML =
        '<span class="dh-name">'+DAYS_FR[day.getDay()]+'</span>'+
        '<span class="dh-num">'+day.getDate()+'</span>';
      bar.appendChild(hd);
    }

    /* Grille */
    var grid = document.getElementById('cal-grid');
    grid.innerHTML = '';
    grid.style.minHeight = ((HOUR_END - HOUR_START) * SLOT_H) + 'px';

    /* Colonne temps */
    var tc = document.createElement('div');
    tc.className = 'cal-time-col';
    for (var h = HOUR_START; h < HOUR_END; h++) {
      var ts = document.createElement('div');
      ts.className = 'cal-time-slot';
      ts.textContent = ('0'+h).slice(-2)+':00';
      tc.appendChild(ts);
    }
    grid.appendChild(tc);

    /* 4 colonnes jours */
    var now = new Date();
    for (var di = 0; di < NB_DAYS; di++) {
      var colDay = addDays(start, di);
      var col = document.createElement('div');
      col.className = 'cal-col';
      for (var hh = HOUR_START; hh < HOUR_END; hh++) {
        var sl = document.createElement('div'); sl.className = 'cal-col-slot'; col.appendChild(sl);
      }
      /* Événements */
      var ds = new Date(colDay); ds.setHours(0,0,0,0);
      var de = new Date(colDay); de.setHours(23,59,59,999);
      var dayEvts = allEvents.filter(function(e){ return e.date>=ds && e.date<=de; });

      /* ── Placement des événements style iOS ── */
      dayEvts = dayEvts.slice().sort(function(a,b){ return a.date - b.date; });

      /* Calcule fin de chaque événement */
      dayEvts.forEach(function(evt){
        evt._end = new Date(evt.date.getTime() + Math.max(15, evt.dur||60) * 60000);
      });

      /* Groupe les événements en clusters qui se chevauchent dans le temps */
      var clusters = [];
      dayEvts.forEach(function(evt){
        var placed = false;
        for (var ci = 0; ci < clusters.length; ci++) {
          var cl = clusters[ci];
          /* Chevauchement : l'evt commence avant la fin du cluster */
          var clEnd = Math.max.apply(null, cl.map(function(e){ return e._end.getTime(); }));
          if (evt.date.getTime() < clEnd) {
            cl.push(evt); placed = true; break;
          }
        }
        if (!placed) clusters.push([evt]);
      });

      /* Pour chaque cluster, assigne des colonnes */
      clusters.forEach(function(cl){
        /* Réassigne les lanes au sein du cluster */
        var lanes = [];
        cl.forEach(function(evt){
          var sMs = evt.date.getTime();
          var eMs = evt._end.getTime();
          var lane = 0;
          for (var li = 0; li < lanes.length; li++) {
            if (lanes[li] <= sMs) { lane = li; lanes[li] = eMs; break; }
            lane = li + 1;
          }
          if (lane >= lanes.length) lanes.push(eMs);
          else lanes[lane] = eMs;
          evt._lane  = lane;
        });
        var total = lanes.length;

        /* Si 1 seule colonne dans le cluster → pleine largeur */
        if (total === 1) {
          cl[0]._clW    = 1;   /* fraction 0..1 de la largeur */
          cl[0]._clL    = 0;
          cl[0]._single = true;
          return;
        }

        /* Plusieurs colonnes → iOS : largeur généreuse, décalage minimal */
        cl.forEach(function(evt){
          var lane  = evt._lane;
          /* Largeur : prend toute la place disponible à droite
             sauf si une autre lane commence après */
          var nextConflict = cl.filter(function(o){
            return o._lane > lane &&
              o.date.getTime() < evt._end.getTime() &&
              o._end.getTime() > evt.date.getTime();
          });
          var lanesUsed = nextConflict.length ? nextConflict[0]._lane : total;
          evt._clW = 1 - lane * (1/total) * 0.55;   /* laisse 55%/N de décalage */
          evt._clL = lane * (1/total) * 0.55;
          evt._single = false;
        });
      });

      dayEvts.forEach(function(evt){
          var eH  = evt.date.getHours() + evt.date.getMinutes()/60;
          var top = Math.max(0, (eH - HOUR_START)) * SLOT_H;
          var dur = Math.max(15, evt.dur || 60);
          var h   = (dur / 60) * SLOT_H - 2;
          var ev = document.createElement('div');
          var cat = evtCategory(evt.titre);
          ev.className = 'cal-evt' + (evt.date < now ? ' is-past' : '') + (cat ? ' cat-'+cat : '');
          ev.style.top    = top + 'px';
          ev.style.height = h + 'px';
          if (evt._single) {
            ev.style.left  = '1px';
            ev.style.right = '1px';
            ev.style.width = 'auto';
          } else {
            ev.style.left  = (evt._clL * 100).toFixed(1) + '%';
            ev.style.width = (evt._clW * 100).toFixed(1) + '%';
            ev.style.right = 'auto';
          }
          ev.title = evt.titre + ' · ' + fmtTime(evt.date) + ' (' + dur + 'min)';
          /* Masquage adaptatif du texte selon la hauteur réelle */
          if (h < 18) ev.classList.add('micro');       /* < ~13min : rien */
          else if (h < 32) ev.classList.add('tiny');   /* < ~22min : titre seul */
          ev.innerHTML =
            '<span class="cal-evt-time">'+fmtTime(evt.date)+'</span>'+
            '<span class="cal-evt-title">'+evt.titre+'</span>';
          col.appendChild(ev);
        });
      grid.appendChild(col);
    }

    /* Ligne heure actuelle (si période courante contient aujourd'hui) */
    var old = document.getElementById('cal-now-line');
    if (old) old.remove();
    var todayInView = (tod >= start && tod <= end3);
    if (todayInView) {
      drawNowLine(grid, start);
    } else {
      if (nowTimer) { clearInterval(nowTimer); nowTimer = null; }
    }
  }

  /* ── Ligne rouge pleine largeur ── */
  function drawNowLine(grid, viewStart) {
    if (nowTimer) clearInterval(nowTimer);
    function draw() {
      var old = document.getElementById('cal-now-line');
      if (old) old.remove();
      var n = new Date();
      var frac = n.getHours() + n.getMinutes()/60;
      if (frac < HOUR_START || frac >= HOUR_END) return;
      var topPx = (frac - HOUR_START) * SLOT_H;
      /* Décaler left selon la colonne du jour */
      var todayColIdx = Math.round((today0() - viewStart) / 86400000); // 0-3
      var gridWidth = grid.offsetWidth || 0;
      var colWidth = gridWidth > TIME_COL_W ? (gridWidth - TIME_COL_W) / NB_DAYS : 0;
      var leftPx = TIME_COL_W + todayColIdx * colWidth;
      var line = document.createElement('div');
      line.id = 'cal-now-line';
      line.style.top  = topPx + 'px';
      line.style.left = leftPx + 'px';
      /* Label heure */
      var lbl = document.createElement('span');
      lbl.id = 'cal-now-time-lbl';
      lbl.textContent = fmtTime(n);
      line.appendChild(lbl);
      grid.appendChild(line);
    }
    draw();
    nowTimer = setInterval(draw, 30000);
    /* Auto-scroll */
    setTimeout(function(){
      var body = document.getElementById('cal-body');
      if (!body) return;
      var frac = new Date().getHours() + new Date().getMinutes()/60;
      body.scrollTop = Math.max(0, (frac - HOUR_START - 1.5)) * SLOT_H;
    }, 80);
  }

  /* ════════════════════════════════
     MINI-CALENDRIER MENSUEL
  ════════════════════════════════ */
  function initMiniMonth() {
    var d = new Date();
    miniMonth = { year: d.getFullYear(), month: d.getMonth() };
  }

  function renderMiniCal() {
    if (!miniMonth) return;
    var y = miniMonth.year, m = miniMonth.month;
    document.getElementById('cal-mini-month-lbl').textContent =
      MONTHS_FR[m] + ' ' + y;
    document.getElementById('cal-mini-btn-lbl').textContent =
      MONTHS_FR_S[m] + ' ' + y;

    var grid = document.getElementById('cal-mini-grid');
    grid.innerHTML = '';

    /* Jours de la semaine */
    ['L','M','M','J','V','S','D'].forEach(function(d){
      var cell = document.createElement('div');
      cell.className = 'mini-dow';
      cell.textContent = d;
      grid.appendChild(cell);
    });

    /* Premier jour du mois (adapté lundi=1) */
    var first = new Date(y, m, 1).getDay(); // 0=dim
    var offset = (first === 0) ? 6 : first - 1;
    for (var e = 0; e < offset; e++) {
      var empty = document.createElement('div');
      empty.className = 'mini-day empty';
      grid.appendChild(empty);
    }

    var daysInMonth = new Date(y, m+1, 0).getDate();
    var tod = today0();
    /* Calculer les jours visibles actuellement */
    var viewStart = addDays(tod, dayOffset);
    var evtDays = {};
    allEvents.forEach(function(ev){
      if (ev.date.getFullYear()===y && ev.date.getMonth()===m) {
        evtDays[ev.date.getDate()] = true;
      }
    });

    for (var day = 1; day <= daysInMonth; day++) {
      var cell = document.createElement('div');
      cell.className = 'mini-day';
      cell.textContent = day;
      var dayDate = new Date(y, m, day);
      if (sameDay(dayDate, tod)) cell.classList.add('is-today');
      /* Vérifier si dans la vue 4J */
      var isInView = false;
      for (var vi = 0; vi < NB_DAYS; vi++) {
        if (sameDay(addDays(viewStart, vi), dayDate)) { isInView = true; break; }
      }
      if (isInView) cell.classList.add('in-view');
      if (evtDays[day]) cell.classList.add('has-evt');
      /* Click : naviguer vers ce jour */
      (function(dd){
        cell.addEventListener('click', function(){
          var diff = Math.round((dd - tod) / 86400000);
          dayOffset = diff;
          /* Fermer le panel */
          toggleMini(false);
          render();
          renderMiniCal();
        });
      })(dayDate);
      grid.appendChild(cell);
    }
  }

  function toggleMini(force) {
    miniOpen = (force !== undefined) ? force : !miniOpen;
    var panel = document.getElementById('cal-mini-panel');
    var btn   = document.getElementById('cal-mini-btn');
    if (miniOpen) {
      panel.classList.add('visible');
      btn.classList.add('open');
      renderMiniCal();
    } else {
      panel.classList.remove('visible');
      btn.classList.remove('open');
    }
  }

  /* ── Boutons navigation principale ── */
  document.getElementById('cal-prev').addEventListener('click', function(){
    dayOffset -= NB_DAYS; render(); renderMiniCal();
  });
  document.getElementById('cal-next').addEventListener('click', function(){
    dayOffset += NB_DAYS; render(); renderMiniCal();
  });
  document.getElementById('cal-today').addEventListener('click', function(){
    dayOffset = 0; render(); renderMiniCal();
  });

  /* ── Boutons mini-cal ── */
  document.getElementById('cal-mini-btn').addEventListener('click', function(e){
    e.stopPropagation(); toggleMini();
  });
  document.getElementById('mini-prev').addEventListener('click', function(){
    miniMonth.month--;
    if (miniMonth.month < 0) { miniMonth.month = 11; miniMonth.year--; }
    renderMiniCal();
  });
  document.getElementById('mini-next').addEventListener('click', function(){
    miniMonth.month++;
    if (miniMonth.month > 11) { miniMonth.month = 0; miniMonth.year++; }
    renderMiniCal();
  });

  /* Fermer le panel si clic ailleurs */
  document.addEventListener('click', function(e){
    if (miniOpen && !document.getElementById('cal-mini-wrap').contains(e.target)) {
      toggleMini(false);
    }
  });

  /* ── Init ── */
  initMiniMonth();
  loadCalData();
  setInterval(function(){ refreshServer(); }, 60 * 60 * 1000);
  setTimeout(function(){ refreshServer(); }, 5 * 60 * 1000);

})();


/* ══════════════════════════════════════════
   MINI POMODORO EMBED
═══════════════════════════════════════════ */
(function(){
  var POM_API = '/pomodoro/api.php';
  var MODE_LABELS = { work:'FOCUS', short_break:'PAUSE', long_break:'LONGUE PAUSE' };
  var pomState = {};
  var prevMode = null, prevRem = null, warned30 = false;

  /* ── Sons Web Audio (partagés avec popup.php) ── */
  var AC = null;
  function getAC(){
    if(!AC){ try{ AC=new(window.AudioContext||window.webkitAudioContext)(); }catch(e){} }
    return AC;
  }
  /* Déverrouillage au premier geste sur le dashboard */
  document.addEventListener('click', function u(){ getAC(); if(AC&&AC.state==='suspended')AC.resume(); document.removeEventListener('click',u); }, {once:true});

  function playTone(freq,type,vol,dur,attack,delay){
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
  /* Fin travail : cloche 3 notes descendantes */
  function soundWorkEnd(){
    playTone(880,'sine',0.30,1.2,0.005,0.00); playTone(1760,'sine',0.10,0.8,0.005,0.00);
    playTone(660,'sine',0.26,1.0,0.005,0.40); playTone(1320,'sine',0.08,0.7,0.005,0.40);
    playTone(440,'sine',0.24,1.4,0.005,0.76);
  }
  /* Fin pause : 3 pulses ascendants */
  function soundBreakEnd(){
    playTone(520,'triangle',0.35,0.25,0.008,0.00); playTone(780,'sine',0.12,0.20,0.005,0.00);
    playTone(620,'triangle',0.40,0.35,0.008,0.32); playTone(930,'sine',0.15,0.30,0.005,0.32);
    playTone(740,'triangle',0.35,0.50,0.008,0.68);
  }
  /* 30s avant fin pause : ping x2 */
  function soundPreEnd(){
    playTone(1050,'sine',0.18,0.50,0.004,0.00);
    playTone(1050,'sine',0.10,0.35,0.004,0.55);
  }

  function pomXhr(cb){
    var x = new XMLHttpRequest();
    x.open('POST', POM_API, true);
    x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    x.timeout = 2500;
    x.onreadystatechange = function(){
      if(x.readyState===4 && x.status===200){
        try{ cb(JSON.parse(x.responseText)); }catch(e){}
      }
    };
    x.send('action=state&_='+Date.now());
  }
  function pomCmd(action, cb){
    var x = new XMLHttpRequest();
    x.open('POST', POM_API, true);
    x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    x.timeout = 2500;
    x.onreadystatechange = function(){
      if(x.readyState===4 && x.status===200){ pomPoll(); if(cb) cb(); }
    };
    x.send('action='+action+'&_='+Date.now());
  }

  function pomPoll(){
    pomXhr(function(d){
      if(!d.mode) return;
      pomState = d;
      var isBreak = (d.mode==='short_break'||d.mode==='long_break');

      /* ── Sons : détection transitions ── */
      if(prevMode!==null && prevMode!==d.mode){
        if(prevMode==='work') soundWorkEnd();
        else { soundBreakEnd(); warned30=false; }
      }
      if(prevMode!==d.mode) warned30=false;
      if(d.running && isBreak && !warned30 && d.rem<=30 && d.rem>0){
        if(prevRem===null||prevRem>30){ soundPreEnd(); warned30=true; }
      }
      prevMode=d.mode; prevRem=d.rem;

      /* Rendu */
      var rem  = d.rem;
      var mins = Math.floor(rem/60);
      var secs = rem%60;
      var el = document.getElementById('pom-time');
      if(el){
        el.textContent = (mins<10?'0':'')+mins+':'+(secs<10?'0':'')+secs;
        el.className = (d.running && rem<=60) ? 'pom-urgent' : '';
      }
      var ml = document.getElementById('pom-mode-lbl');
      if(ml) ml.textContent = MODE_LABELS[d.mode] || 'FOCUS';

      /* Accent couleur selon mode */
      var accent = (d.mode==='work') ? '#d4a853' : '#6eba94';
      var pbar = document.getElementById('pom-prog');
      if(pbar){
        var pct = d.dur>0 ? Math.round((1-rem/d.dur)*1000)/10 : 0;
        pbar.style.width = pct+'%';
        pbar.style.background = accent;
      }
      var ml2 = document.getElementById('pom-mode-lbl');
      if(ml2) ml2.style.color = accent;

      /* Bouton */
      var icon = document.getElementById('pom-btn-icon');
      if(icon){
        icon.innerHTML = d.running
          ? '<rect x="2" y="1" width="2.5" height="8"/><rect x="5.5" y="1" width="2.5" height="8"/>'
          : '<polygon points="2,1 9,5 2,9"/>';
      }

      /* Pastilles */
      for(var i=0;i<4;i++){
        var dot = document.getElementById('pd'+i);
        if(!dot) continue;
        dot.classList.remove('active','done');
        if(i < d.pos) dot.classList.add('done');
        else if(i===d.pos && d.mode==='work') dot.classList.add('active');
      }
    });
  }

  var btn = document.getElementById('pom-btn');
  if(btn) btn.addEventListener('click', function(){
    pomCmd(pomState.running ? 'pause' : 'start');
  });

  pomPoll();
  setInterval(pomPoll, 10000);
})();

initPhotos();

/* ══════════════════════════════════════════
   TODOS — CalDAV iCloud Reminders
   API : /api_todos.php
══════════════════════════════════════════ */
(function(){
  /* ── Listes dans l'ordre d'affichage ── */
  var LISTS = [
    { id:'Quotidien',     label:'Quotidien'     },
    { id:'Dashboard',     label:'Dashboard'     },
    { id:'Courses',       label:'Courses'       },
    { id:'Moyen terme',   label:'Moyen terme'   },
    { id:'Long terme',    label:'Long terme'    },
    { id:'La Parisienne', label:'La Parisienne' }
  ];
  var DEFAULT_IDX = 0;   /* Quotidien par défaut */
  var CACHE_TTL   = 60000; /* 60s côté client */
  var RETURN_DELAY = 5000; /* retour auto todo après 5s sur musique */

  var curIdx     = DEFAULT_IDX;
  var cache      = {};      /* {listLabel: {ts, items}} */
  var musicTimer = null;

  /* ── DOM ── */
  var viewTodo   = document.getElementById('view-todo');
  var viewMusic  = document.getElementById('view-music');
  var todoItems  = document.getElementById('todo-items');
  var listNameEl = document.getElementById('todo-list-name');
  var prevBtn    = document.getElementById('todo-prev');
  var nextBtn    = document.getElementById('todo-next');
  var expandBtn  = document.getElementById('todo-expand-btn');
  var musicBtn   = document.getElementById('todo-music-btn');
  var modal      = document.getElementById('todos-modal');
  var modalGrid  = document.getElementById('todos-modal-grid');
  var modalClose = document.getElementById('todos-modal-close');
  var modalBg    = document.getElementById('todos-modal-bg');
  var returnBar  = document.getElementById('music-return-bar');

  /* ── Bascule vues ── */
  function showTodo(){
    if(viewMusic) viewMusic.style.display='none';
    if(viewTodo)  viewTodo.style.display='flex';
    if(returnBar){ returnBar.style.transition='none'; returnBar.style.width='100%';
      setTimeout(function(){
        returnBar.style.transition='width '+RETURN_DELAY+'ms linear';
        returnBar.style.width='0%';
      },30);
    }
    clearTimeout(musicTimer);
    musicTimer = setTimeout(showMusic, RETURN_DELAY);
  }
  function showMusic(){
    clearTimeout(musicTimer);
    if(viewTodo)  viewTodo.style.display='none';
    if(viewMusic) viewMusic.style.display='';
    if(returnBar){ returnBar.style.transition='none'; returnBar.style.width='0'; }
  }
  /* Reset timer si l'utilisateur interagit dans la vue todo */
  if(viewTodo) viewTodo.addEventListener('click', function(e){
    if(e.target.id==='todo-music-btn') return; /* géré par son propre listener */
    clearTimeout(musicTimer);
    musicTimer = setTimeout(showMusic, RETURN_DELAY);
  });
  if(musicBtn) musicBtn.addEventListener('click', function(){ clearTimeout(musicTimer); showMusic(); });
  /* Bouton ☰ dans la vue musique → todo */
  var musicToTodo = document.getElementById('music-to-todo-btn');
  if(musicToTodo) musicToTodo.addEventListener('click', showTodo);

  /* ── Utilitaires ── */
  function escHtml(s){
    return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function fmtDue(iso){
    if(!iso) return null;
    var d=new Date(iso); if(isNaN(d.getTime())) return null;
    var now=new Date(); now.setHours(0,0,0,0);
    var day=new Date(d); day.setHours(0,0,0,0);
    var diff=Math.round((day-now)/86400000);
    var hm=('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2);
    var overdue=diff<0;
    var label = overdue ? 'En retard' : diff===0 ? (hm==='00:00'?'Auj.':'Auj. '+hm) : diff===1 ? 'Demain' : 'J+'+diff;
    return {label:label, overdue:overdue};
  }

  /* ── Chargement ── */
  function loadList(listLabel, cb){
    var cached=cache[listLabel];
    if(cached && (Date.now()-cached.ts)<CACHE_TTL){ cb&&cb(cached.items); return; }
    var x=new XMLHttpRequest();
    x.open('GET','/api_todos.php?action=todos&name='+encodeURIComponent(listLabel)+'&_='+Date.now(),true);
    x.timeout=12000;
    x.onreadystatechange=function(){
      if(x.readyState!==4)return;
      if(x.status!==200){ cb&&cb(null); return; }
      try{
        var d=JSON.parse(x.responseText);
        var items=Array.isArray(d)?d:[];
        cache[listLabel]={ts:Date.now(),items:items};
        cb&&cb(items);
      }catch(e){ cb&&cb(null); }
    };
    x.send();
  }

  /* ── Rendu d'une liste dans un container ── */
  function renderList(items, container, listLabel){
    container.innerHTML='';
    if(!items||items.error){
      var msg=items&&items.error?items.error:'Erreur de connexion';
      container.innerHTML='<div class="todo-empty">'+escHtml(msg)+'</div>';
      return;
    }
    if(!items.length){
      container.innerHTML='<div class="todo-empty">&#10003; Liste vide</div>';
      return;
    }
    items.forEach(function(todo){
      var due=fmtDue(todo.due);
      var row=document.createElement('div');
      row.className='todo-item';
      row.innerHTML=
        '<div class="todo-check" title="Marquer fait"></div>'+
        '<div class="todo-text"><div class="todo-title">'+escHtml(todo.title)+'</div></div>'+
        (due?'<div class="todo-due'+(due.overdue?' overdue':'')+'">'+escHtml(due.label)+'</div>':'');
      var chk=row.querySelector('.todo-check');
      var ttl=row.querySelector('.todo-title');
      chk.addEventListener('click',function(e){
        e.stopPropagation();
        if(chk.classList.contains('done')||chk.classList.contains('checking'))return;
        chk.classList.add('checking');
        /* Optimiste : UI immédiate */
        completeTodo(todo.uid, listLabel, function(ok){
          if(ok){
            chk.classList.remove('checking'); chk.classList.add('done');
            ttl.classList.add('done');
            delete cache[listLabel]; /* invalider le cache */
            setTimeout(function(){
              row.style.opacity='0'; row.style.transition='opacity .3s';
              setTimeout(function(){row.remove();},320);
            },420);
          } else {
            chk.classList.remove('checking');
          }
        });
      });
      container.appendChild(row);
    });
  }

  /* ── Compléter via API ── */
  function completeTodo(uid, listLabel, cb){
    var x=new XMLHttpRequest();
    x.open('POST','/api_todos.php',true);
    x.setRequestHeader('Content-Type','application/json');
    x.timeout=12000;
    x.onreadystatechange=function(){
      if(x.readyState!==4)return;
      try{ var d=JSON.parse(x.responseText); cb&&cb(d.ok===true); }
      catch(e){ cb&&cb(false); }
    };
    x.send(JSON.stringify({action:'complete',uid:uid,list:listLabel}));
  }

  /* ── Rendu de la liste courante dans la tuile ── */
  function renderCurrent(){
    var list=LISTS[curIdx];
    listNameEl.textContent=list.label.toUpperCase();
    todoItems.innerHTML='<div class="todo-empty">Chargement\u2026</div>';
    loadList(list.label, function(items){
      renderList(items||[], todoItems, list.label);
    });
  }

  /* ── Navigation entre listes ── */
  if(prevBtn) prevBtn.addEventListener('click',function(){
    curIdx=(curIdx-1+LISTS.length)%LISTS.length; renderCurrent();
  });
  if(nextBtn) nextBtn.addEventListener('click',function(){
    curIdx=(curIdx+1)%LISTS.length; renderCurrent();
  });

  /* ── Modal toutes les listes ── */
  function openModal(){
    if(!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden','false');
    modalGrid.innerHTML='';
    LISTS.forEach(function(list){
      var col=document.createElement('div');
      col.className='modal-col';
      var cnt=document.createElement('div'); cnt.className='modal-items';
      col.innerHTML='<div class="modal-col-hdr">'+escHtml(list.label)+'<span class="modal-col-count">\u2026</span></div>';
      col.appendChild(cnt);
      modalGrid.appendChild(col);
      var countEl=col.querySelector('.modal-col-count');
      loadList(list.label,function(items){
        items=items||[];
        countEl.textContent=Array.isArray(items)?items.length:'!';
        renderList(items, cnt, list.label);
      });
    });
  }
  function closeModal(){
    if(!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden','true');
  }
  if(expandBtn) expandBtn.addEventListener('click',openModal);
  if(modalClose) modalClose.addEventListener('click',closeModal);
  if(modalBg) modalBg.addEventListener('click',closeModal);
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeModal(); });

  /* ── Init ── */
  renderCurrent();

  /* Rafraîchissement silencieux toutes les 2 minutes */
  setInterval(function(){
    delete cache[LISTS[curIdx].label];
    renderCurrent();
  }, 120000);

})();

/* ══════════════════════════════════════════
   CURSEUR — spotlight + tilt cartes
══════════════════════════════════════════ */
(function(){
  var spot   = document.getElementById('spotlight');
  var cards  = document.querySelectorAll('.card');
  var raf    = null;
  var mx = window.innerWidth/2, my = window.innerHeight/2;

  /* Spotlight global */
  if(spot){
    spot.classList.add('on');
    document.addEventListener('mousemove', function(e){
      mx = e.clientX; my = e.clientY;
      if(raf) return;
      raf = requestAnimationFrame(function(){
        raf = null;
        spot.style.setProperty('--sx', mx+'px');
        spot.style.setProperty('--sy', my+'px');
      });
    });
    /* Disparaît quand la souris quitte la fenêtre */
    document.addEventListener('mouseleave', function(){ spot.classList.remove('on'); });
    document.addEventListener('mouseenter', function(){ spot.classList.add('on'); });
  }

  /* Tilt + reflet local par carte */
  cards.forEach(function(card){
    var tiltRaf = null;
    card.addEventListener('mousemove', function(e){
      if(tiltRaf) return;
      tiltRaf = requestAnimationFrame(function(){
        tiltRaf = null;
        var rect = card.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;
        var px = cx / rect.width;   /* 0..1 */
        var py = cy / rect.height;
        /* Tilt max 2.5° */
        var ry =  (px - 0.5) * 5;
        var rx = -(py - 0.5) * 5;
        card.style.webkitTransform = 'perspective(900px) rotateX('+rx+'deg) rotateY('+ry+'deg)';
        card.style.transform       = 'perspective(900px) rotateX('+rx+'deg) rotateY('+ry+'deg)';
        /* Reflet local */
        card.style.setProperty('--cx', cx+'px');
        card.style.setProperty('--cy', cy+'px');
      });
    });
    card.addEventListener('mouseleave', function(){
      card.style.webkitTransform = 'perspective(900px) rotateX(0deg) rotateY(0deg)';
      card.style.transform       = 'perspective(900px) rotateX(0deg) rotateY(0deg)';
    });
  });
})();
</script>

<!-- ══ TODOS MODAL ══ -->
<div id="todos-modal" aria-hidden="true" role="dialog" aria-label="Toutes les listes">
  <div id="todos-modal-bg"></div>
  <div id="todos-modal-panel">
    <div id="todos-modal-hdr">
      <span id="todos-modal-title">Mes listes</span>
      <button id="todos-modal-close" aria-label="Fermer">&#10005;</button>
    </div>
    <div id="todos-modal-grid"></div>
  </div>
</div>

</body>
</html>