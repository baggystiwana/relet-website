document.addEventListener('click',event=>{
  const button=event.target.closest('[data-load-gumlet]');
  if(!button)return;
  const shell=button.closest('[data-gumlet-shell]');
  if(!shell)return;
  const frame=document.createElement('iframe');
  frame.loading='lazy';
  frame.title=button.dataset.title;
  frame.src=button.dataset.gumletSrc;
  frame.referrerPolicy='origin';
  frame.allow='accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture; fullscreen; clipboard-write;';
  frame.allowFullscreen=true;
  frame.style.cssText='border:none;position:absolute;inset:0;height:100%;width:100%;';
  shell.replaceChildren(frame);
});
