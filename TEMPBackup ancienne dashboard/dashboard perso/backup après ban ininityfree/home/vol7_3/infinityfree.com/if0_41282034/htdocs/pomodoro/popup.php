<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=yes">
<title>Pomodoro</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;800&family=DM+Mono:wght@400&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#07080b;--acc:#d4a853;--adim:rgba(212,168,83,0.14);
  --t1:rgba(240,236,228,0.95);--t2:rgba(240,236,228,0.50);--t3:rgba(240,236,228,0.25);
  --border:rgba(255,255,255,0.09);
  --fs-timer:38px;--fs-mode:6px;--fs-btn:28px;--fs-open:8px;--gap:6px;
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{width:100%;height:100%;overflow:hidden;background:var(--bg);font-family:"DM Mono",monospace;-webkit-user-select:none;user-select:none;}
#wrap{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:var(--gap);background:rgba(8,10,17,0.98);border:1px solid rgba(255,255,255,0.07);border-radius:4px;position:relative;overflow:hidden;}
#glow{position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse 80% 80% at 50% 40%,rgba(212,168,83,0.10) 0%,rgba(212,168,83,0.03) 55%,transparent 80%);opacity:0;transition:opacity 1.2s;}
#glow.on{opacity:1;}
#p-mode{font-size:var(--fs-mode);letter-spacing:.35em;text-transform:uppercase;color:var(--acc);opacity:.75;line-height:1;white-space:nowrap;flex-shrink:0;}
#p-time{font-family:"Outfit",sans-serif;font-weight:800;font-size:var(--fs-timer);line-height:1;letter-spacing:.01em;color:var(--t1);font-variant-numeric:tabular-nums;flex-shrink:0;white-space:nowrap;}
#p-time.urgent{color:rgba(210,70,50,.95);}
#p-colon{color:var(--acc);margin:0 1px;}
#p-prog-track{width:70%;max-width:140px;height:1px;background:rgba(240,236,228,.06);border-radius:1px;overflow:hidden;flex-shrink:0;}
#p-prog{height:100%;background:var(--acc);width:0%;transition:width 2s linear;}
#p-controls{display:flex;gap:6px;align-items:center;flex-shrink:0;}
#p-btn{width:var(--fs-btn);height:var(--fs-btn);border-radius:50%;background:var(--adim);border:1px solid rgba(212,168,83,.30);color:var(--acc);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0;flex-shrink:0;transition:background .2s,border-color .2s,transform .1s;}
#p-btn:hover{background:rgba(212,168,83,.22);border-color:rgba(212,168,83,.55);}
#p-btn:active{transform:scale(.92);}
#p-btn svg{display:block;}
#p-open{display:flex;align-items:center;padding:2px 5px;border-radius:2px;background:none;border:1px solid var(--border);color:var(--t3);font-family:"DM Mono",monospace;font-size:var(--fs-open);line-height:1;cursor:pointer;transition:all .2s;text-decoration:none;}
#p-open:hover{border-color:rgba(212,168,83,.35);color:var(--acc);}
.hide-sm{transition:opacity .15s;}
@media(max-height:62px){.hide-sm{opacity:0;pointer-events:none;height:0;overflow:hidden;}}
@media(max-width:72px){#p-open{display:none;}}
@media(max-height:32px){#p-time{display:none;}}
</style>
</head>
<body>
<div id="wrap">
  <div id="glow"></div>
  <div id="p-mode" class="hide-sm">FOCUS</div>
  <div id="p-time"><span id="p-min">25</span><span id="p-colon">:</span><span id="p-sec">00</span></div>
  <div id="p-prog-track" class="hide-sm"><div id="p-prog"></div></div>
  <div id="p-controls">
    <button id="p-btn" title="Play / Pause">
      <svg id="p-btn-icon" viewBox="0 0 10 10" fill="currentColor"><polygon points="2,1 9,5 2,9"/></svg>
    </button>
    <a id="p-open" class="hide-sm" href="/pomodoro/" target="_blank" title="Ouvrir en plein ecran">&#10162;</a>
  </div>
</div>
<script>
/* == TAILLE ADAPTATIVE == */
function applySize(){
  var w=window.innerWidth, h=window.innerHeight, s=Math.min(w,h);
  var r=document.documentElement;
  r.style.setProperty("--fs-timer", Math.max(10,Math.min(72,s*.55)).toFixed(1)+"px");
  r.style.setProperty("--fs-mode",  Math.max(5, Math.min(10,s*.13)).toFixed(1)+"px");
  r.style.setProperty("--fs-btn",   Math.max(18,Math.min(44,s*.28)).toFixed(1)+"px");
  r.style.setProperty("--fs-open",  Math.max(5, Math.min(10,s*.10)).toFixed(1)+"px");
  r.style.setProperty("--gap",      Math.max(2, Math.min(10,h*.06)).toFixed(1)+"px");
  var ic=document.getElementById("p-btn-icon");
  if(ic){var isz=Math.max(6,Math.min(18,s*.14)).toFixed(1)+"px"; ic.style.width=isz; ic.style.height=isz;}
}
applySize();
window.addEventListener("resize", applySize);

/* == SONS WEB AUDIO == */
var AC=null;
function getAC(){
  if(!AC){try{AC=new(window.AudioContext||window.webkitAudioContext)();}catch(e){}}
  return AC;
}
function unlockAudio(){if(AC&&AC.state==="suspended")AC.resume();}
document.addEventListener("click",function u(){getAC();unlockAudio();document.removeEventListener("click",u);},{once:true});

function playTone(freq,type,vol,dur,attack,delay){
  var ac=getAC(); if(!ac) return;
  var t0=ac.currentTime+(delay||0);
  var osc=ac.createOscillator(), g=ac.createGain();
  osc.connect(g); g.connect(ac.destination);
  osc.type=type||"sine"; osc.frequency.setValueAtTime(freq,t0);
  g.gain.setValueAtTime(0,t0);
  g.gain.linearRampToValueAtTime(vol,t0+(attack||0.01));
  g.gain.exponentialRampToValueAtTime(0.001,t0+dur);
  osc.start(t0); osc.stop(t0+dur+0.05);
}

/* Son 1 : fin TRAVAIL — cloche 3 notes descendantes (880→660→440 Hz) */
function soundWorkEnd(){
  playTone(880, "sine",    0.35, 1.2, 0.005, 0.00);
  playTone(1760,"sine",    0.12, 0.8, 0.005, 0.00);
  playTone(660, "sine",    0.30, 1.0, 0.005, 0.40);
  playTone(1320,"sine",    0.10, 0.7, 0.005, 0.40);
  playTone(440, "sine",    0.28, 1.4, 0.005, 0.76);
  playTone(880, "sine",    0.08, 1.0, 0.005, 0.76);
}

/* Son 2 : fin PAUSE — 3 pulses ascendants (réveil doux) */
function soundBreakEnd(){
  playTone(520, "triangle",0.40, 0.25, 0.008, 0.00);
  playTone(780, "sine",    0.15, 0.20, 0.005, 0.00);
  playTone(620, "triangle",0.45, 0.35, 0.008, 0.32);
  playTone(930, "sine",    0.18, 0.30, 0.005, 0.32);
  playTone(740, "triangle",0.40, 0.50, 0.008, 0.68);
}

/* Son 3 : avertissement 30s avant fin PAUSE — ping discret x2 */
function soundPreEnd(){
  playTone(1050,"sine",0.22,0.50,0.004,0.00);
  playTone(1050,"sine",0.12,0.35,0.004,0.55);
}

/* == POLLING == */
var API="/pomodoro/api.php";
var LABELS={work:"FOCUS",short_break:"PAUSE",long_break:"LONGUE PAUSE"};
var cur={}, prevMode=null, prevRem=null, warned30=false;

function xhr(url,method,body,cb){
  var x=new XMLHttpRequest();
  x.open(method||"GET", url+(method==="GET"?"&_="+Date.now():""), true);
  if(method==="POST") x.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
  x.timeout=3000;
  x.onreadystatechange=function(){if(x.readyState===4&&x.status===200&&cb){try{cb(JSON.parse(x.responseText));}catch(e){}}};
  x.send(body||null);
}
function api(a,cb){xhr(API,"POST","action="+a+"&_="+Date.now(),cb);}

function poll(){
  xhr(API+"?action=state","GET",null,function(d){
    if(!d.mode) return;
    cur=d;
    var isBreak=(d.mode==="short_break"||d.mode==="long_break");

    /* Transitions sonores */
    if(prevMode!==null && prevMode!==d.mode){
      if(prevMode==="work") soundWorkEnd();
      else { soundBreakEnd(); warned30=false; }
    }
    if(prevMode!==d.mode) warned30=false;

    /* Avertissement 30s avant fin de pause */
    if(d.running && isBreak && !warned30 && d.rem<=30 && d.rem>0){
      if(prevRem===null||prevRem>30){ soundPreEnd(); warned30=true; }
    }

    prevMode=d.mode; prevRem=d.rem;

    /* Rendu */
    var acc=(d.mode==="work")?"#d4a853":"#6eba94";
    document.documentElement.style.setProperty("--acc",acc);
    document.getElementById("p-mode").textContent=LABELS[d.mode]||"FOCUS";
    document.getElementById("p-min").textContent=("0"+Math.floor(d.rem/60)).slice(-2);
    document.getElementById("p-sec").textContent=("0"+(d.rem%60)).slice(-2);
    var pct=d.dur>0?(1-d.rem/d.dur)*100:0;
    document.getElementById("p-prog").style.width=pct.toFixed(1)+"%";
    document.getElementById("p-time").className=(d.running&&d.rem<=60)?"urgent":"";
    document.getElementById("glow").className=d.running?"on":"";
    var ic=document.getElementById("p-btn-icon");
    if(ic) ic.innerHTML=d.running
      ?"<rect x=\"2\" y=\"1\" width=\"2.5\" height=\"8\"/><rect x=\"5.5\" y=\"1\" width=\"2.5\" height=\"8\"/>"
      :"<polygon points=\"2,1 9,5 2,9\"/>";
  });
}

document.getElementById("p-btn").addEventListener("click",function(){
  getAC(); unlockAudio();
  api(cur.running?"pause":"start",poll);
});
poll();
setInterval(poll,10000);
</script>
</body>
</html>