// Minimal behavior for placeholders: clocks (Paris + Hanoi), build date, simple slideshow gradients
(function(){
  const buildDate = new Date().toISOString();
  document.getElementById('build-date').textContent = buildDate.split('T')[0];

  // Timezones
  function updateClocks(){
    const now = new Date();
    const paris = now.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false,timeZone:'Europe/Paris'});
    const parisDate = now.toLocaleDateString('fr-FR',{weekday:'long',day:'2-digit',month:'long',timeZone:'Europe/Paris'});
    document.getElementById('time-paris').textContent = paris;
    document.getElementById('date-paris').textContent = parisDate;

    const hanoi = now.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit',hour12:false,timeZone:'Asia/Ho_Chi_Minh'});
    document.getElementById('time-hanoi').textContent = hanoi;

    // compute day offset between Paris and Hanoi dates
    const dParis = new Date(now.toLocaleString('en-US',{timeZone:'Europe/Paris'})).getDate();
    const dHanoi = new Date(now.toLocaleString('en-US',{timeZone:'Asia/Ho_Chi_Minh'})).getDate();
    const diff = dHanoi - dParis;
    let badge='';
    if(diff>0) badge=`+${diff}j`;
    else if(diff<0) badge=`${diff}j`;
    document.getElementById('hanoi-offset').textContent = badge;
  }
  updateClocks();
  setInterval(updateClocks,1000);

  // Mode matin detection (Paris 6h-10h)
  function updateMorningMode(){
    const h = new Date().toLocaleString('en-US',{timeZone:'Europe/Paris'});
    const hour = new Date(h).getHours();
    const el = document.querySelector('#card-clock .meta');
    if(hour>=6 && hour<10){
      el.innerHTML += ' • <span style="color:var(--accent);font-weight:600">Mode matin</span>';
    }
  }
  updateMorningMode();

  // Simple slideshow using gradients (aesthetic placeholder)
  const bg = document.getElementById('bg-slideshow');
  const slides = [
    'linear-gradient(120deg,#071018 0%, #0b0c10 100%)',
    'linear-gradient(120deg,#0b1020 0%, #061018 100%)',
    'linear-gradient(120deg,#071018 0%, #10202a 100%)'
  ];
  let si=0;
  function nextSlide(){
    si=(si+1)%slides.length;
    bg.style.transition='background 1.2s ease-in-out';
    bg.style.background=slides[si];
  }
  setInterval(nextSlide,120000);

  // simple tab behaviour demo
  const tabs = document.querySelectorAll('.tabs .tab');
  tabs.forEach(t=>t.addEventListener('click',()=>{
    tabs.forEach(x=>x.classList.remove('active'));
    t.classList.add('active');
  }));

  // Audio BUS placeholder
  window.AUDIO_BUS = {
    current:null,
    register(name,stopFn){
      // register a named source with a stop function (placeholder)
      console.log('AUDIO_BUS register',name);
      return ()=>console.log('stop',name);
    },
    play(name){
      console.log('AUDIO_BUS play',name);
      if(window.AUDIO_BUS.current && window.AUDIO_BUS.current!==name){
        console.log('Stopping',window.AUDIO_BUS.current);
      }
      window.AUDIO_BUS.current = name;
    }
  };
})();