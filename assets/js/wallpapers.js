// wallpapers loader: reads a manifest and crossfades two background layers (#bg-a, #bg-b)
(function(){
  const A = document.getElementById('bg-a');
  const B = document.getElementById('bg-b');
  const VIG = document.getElementById('vig');
  const INTERVAL_MS = 120000; // 120s
  let idx = 0;
  let images = [];

  function setLayer(el, url){
    el.style.backgroundImage = url ? `url("${url}")` : '';
    el.style.backgroundSize = 'cover';
    el.style.backgroundPosition = 'center center';
  }

  function crossfadeNext(){
    if(images.length===0){
      // alternate subtle gradients
      const grads = [
        'linear-gradient(120deg,#071018 0%, #0b0c10 100%)',
        'linear-gradient(120deg,#0b1020 0%, #061018 100%)',
        'linear-gradient(120deg,#071018 0%, #10202a 100%)'
      ];
      idx = (idx+1)%grads.length;
      A.style.opacity = 0;
      B.style.opacity = 1;
      setTimeout(()=>{ A.style.background = grads[idx]; B.style.background = grads[(idx+1)%grads.length]; A.style.opacity = 1; B.style.opacity = 0; }, 20);
      return;
    }

    const next = images[idx % images.length];
    const showA = (idx % 2) === 0;
    const topLayer = showA ? A : B;
    const bottomLayer = showA ? B : A;
    preload(next).finally(()=>{
      setLayer(topLayer, next);
      topLayer.style.opacity = 1;
      bottomLayer.style.opacity = 0;
    });
    idx++;
  }

  function preload(url){
    return new Promise((res)=>{
      const i = new Image();
      i.onload = ()=>res(true);
      i.onerror = ()=>res(false);
      i.src = url;
    });
  }

  function loadManifest(){
    const path = 'assets/wallpapers/wallpapers.json';
    fetch(path).then(r=>{
      if(!r.ok) throw new Error('no-manifest');
      return r.json();
    }).then(j=>{
      if(Array.isArray(j.photos) && j.photos.length>0){
        // photos can be {id,url}
        const urls = j.photos.map(p=>p.url).filter(Boolean);
        if(urls.length===0) throw new Error('empty-manifest');
        images = urls;
        // immediately show the first wallpaper while the next one preloads
        setLayer(A, images[0]);
        A.style.opacity = 1;
        B.style.opacity = 0;
        idx = 1;
        preload(images[1 % images.length]).finally(()=>{
          crossfadeNext();
          setInterval(crossfadeNext, INTERVAL_MS);
        });
      } else {
        throw new Error('empty-manifest');
      }
    }).catch(()=>{
      // fallback to a small curated list of remote images (CC0 / unsplash sample)
      images = [
        'https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=2000&q=80',
        'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=2000&q=80',
        'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=2000&q=80'
      ];
      setLayer(A, images[0]);
      A.style.opacity = 1;
      B.style.opacity = 0;
      idx = 1;
      preload(images[1 % images.length]).finally(()=>{ crossfadeNext(); setInterval(crossfadeNext, INTERVAL_MS); });
    });
  }

  // initialize styles
  [A,B].forEach(el=>{
    el.style.position='fixed'; el.style.inset='0'; el.style.zIndex='-1'; el.style.transition='opacity 1.2s ease-in-out'; el.style.opacity='0'; el.style.backgroundColor='#071018';
    el.style.backgroundRepeat='no-repeat';
  });
  VIG.style.position='fixed'; VIG.style.inset='0'; VIG.style.zIndex='0'; VIG.style.pointerEvents='none'; VIG.style.background = 'linear-gradient(180deg, rgba(0,0,0,0.18), transparent 40%)';

  loadManifest();
})();