const toggle=document.querySelector('.nav-toggle'),nav=document.querySelector('.nav-links');
function setNav(open){if(!toggle||!nav)return;nav.classList.toggle('open',open);toggle.setAttribute('aria-expanded',String(open));toggle.setAttribute('aria-label',open?'Close menu':'Open menu')}
if(toggle&&nav){
  toggle.addEventListener('click',()=>setNav(!nav.classList.contains('open')));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&nav.classList.contains('open')){setNav(false);toggle.focus()}});
  document.addEventListener('click',e=>{if(nav.classList.contains('open')&&!nav.contains(e.target)&&e.target!==toggle)setNav(false)});
  nav.addEventListener('click',e=>{if(e.target.closest('a'))setNav(false)});
}
document.querySelectorAll('[data-year]').forEach(el=>el.textContent=new Date().getFullYear());

// Build FAQ structured data only from visible on-page questions and answers.
const faqItems=[...document.querySelectorAll('.faq-list details')].map(item=>{
  const question=item.querySelector('summary')?.textContent.trim();
  const answer=item.querySelector('p')?.textContent.trim();
  return question&&answer?{'@type':'Question',name:question,acceptedAnswer:{'@type':'Answer',text:answer}}:null;
}).filter(Boolean);
if(faqItems.length){
  const faqSchema=document.createElement('script');
  faqSchema.type='application/ld+json';
  faqSchema.textContent=JSON.stringify({'@context':'https://schema.org','@type':'FAQPage',mainEntity:faqItems});
  document.head.appendChild(faqSchema);
}

const swipeOneEndpoint='https://api.swipeone.com/forms/6a731122bf95d8007f6fee5d/submit';

function splitName(value){
  const parts=String(value||'').trim().split(/\s+/).filter(Boolean);
  return {first:parts.shift()||'',last:parts.join(' ')||'-'};
}

function normaliseUKPhone(value){
  let phone=String(value||'').trim().replace(/[^\d+]/g,'');
  if(phone.startsWith('+44'))phone='0'+phone.slice(3);
  else if(phone.startsWith('0044'))phone='0'+phone.slice(4);
  else if(phone.startsWith('44')&&!phone.startsWith('440'))phone='0'+phone.slice(2);
  return phone;
}

function swipeOnePayload(data){
  const name=splitName(data.get('name'));
  const details=[
    `Postcode: ${data.get('postcode')||''}`,
    `Customer: ${data.get('customer_type')||''}`,
    `Service: ${data.get('service')||''}`,
    `Property status: ${data.get('occupancy')||''}`,
    `Access: ${data.get('access')||''}`,
    `Urgency: ${data.get('urgency')||''}`,
    `Preferred contact: ${data.get('contact_method')||''}`,
    '',
    'Job details:',
    data.get('details')||''
  ].join('\n');
  return {
    '435c1b3142':name.first,
    '5fabfd0504':name.last,
    '3b02e1c157':data.get('email')||'',
    '115af791da_countryCode':'GB',
    '115af791da_number':normaliseUKPhone(data.get('phone')),
    '99298f4bd0':data.get('service')||'Property maintenance enquiry',
    'ynd9wydy96':details,
    '_pageUrl':location.href
  };
}

async function submitSwipeOne(form){
  const button=form.querySelector('button[type="submit"]');
  let status=form.querySelector('[data-form-status]');
  if(!status){status=document.createElement('p');status.className='notice';status.dataset.formStatus='';status.setAttribute('role','status');status.setAttribute('aria-live','polite');form.appendChild(status)}
  const original=button?.textContent||'Submit request';
  if(button){button.disabled=true;button.textContent='Submitting…'}
  status.textContent='Sending your job request…';
  try{
    const response=await fetch(swipeOneEndpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(swipeOnePayload(new FormData(form)))});
    const result=await response.json();
    if(!response.ok||result.status!=='success')throw new Error(result.message||'Submission failed');
    if(result.data?.redirectUrl){location.href=result.data.redirectUrl;return}
    form.reset();
    status.textContent=result.data?.message||'Thank you. Your job request has been received and we’ll be in touch shortly.';
    status.focus?.();
  }catch(_){
    status.innerHTML='We could not send the form. Please try again, call <a href="tel:+441156612041">0115 661 2041</a>, or use <a href="https://wa.me/447971241112">WhatsApp</a>.';
  }finally{
    if(button){button.disabled=false;button.textContent=original}
  }
}
const pageForm=document.querySelector('[data-quote-form]');
if(pageForm){
  const phoneInput=pageForm.querySelector('input[name="phone"]');
  if(phoneInput){phoneInput.placeholder='e.g. 07971 241112';phoneInput.inputMode='tel';phoneInput.autocomplete='tel-national'}
  const submitButton=pageForm.querySelector('button[type="submit"]');
  if(submitButton)submitButton.textContent='Send job request';
  const note=pageForm.querySelector('.notice');
  if(note)note.innerHTML='<strong>Photos:</strong> after submitting, send photographs or video by WhatsApp to <a href="https://wa.me/447971241112">07971 241112</a>. Your job details will be sent securely to Re-Let through Swipe One.';
  pageForm.addEventListener('submit',e=>{e.preventDefault();submitSwipeOne(pageForm)});
}

// Cookie / similar-technology consent.
const uiCss=document.createElement('link');uiCss.rel='stylesheet';uiCss.href='/assets/consent-modal.css';document.head.appendChild(uiCss);
const cookieKey='relet-cookie-preferences-v2';
const safeStorage={
  get(){try{return localStorage.getItem(cookieKey)}catch(_){return null}},
  set(v){try{localStorage.setItem(cookieKey,v);return true}catch(_){return false}}
};
let clickRankLoaded=false;
function loadOptionalTools(){
  if(clickRankLoaded)return;
  clickRankLoaded=true;
  const s=document.createElement('script');s.src='/assets/clickrank.js';s.async=true;s.dataset.consentCategory='analytics';document.head.appendChild(s);
}
function parseConsent(raw){
  if(!raw)return null;
  try{const x=JSON.parse(raw);if(x&&typeof x.analytics==='boolean')return x}catch(_){/* ignore */}
  return null;
}
let consent=parseConsent(safeStorage.get());
if(consent?.analytics)loadOptionalTools();

const banner=document.createElement('section');
banner.className='cookie-banner';banner.setAttribute('role','dialog');banner.setAttribute('aria-modal','false');banner.setAttribute('aria-labelledby','cookie-title');banner.setAttribute('aria-describedby','cookie-copy');
banner.innerHTML=`
  <h2 id="cookie-title">Your cookie choices</h2>
  <p id="cookie-copy">We use essential browser storage to remember your choice. Optional analytics and SEO-performance technology is off unless you allow it. Read our <a href="/cookie-policy.html">Cookie Policy</a>.</p>
  <div class="cookie-actions">
    <button class="btn" type="button" data-cookie="accept">Accept all</button>
    <button class="btn" type="button" data-cookie="reject">Reject non-essential</button>
    <button class="btn btn-outline" type="button" data-cookie="manage" aria-expanded="false">Manage preferences</button>
  </div>
  <div class="cookie-manage" hidden>
    <div class="cookie-choice"><div><strong>Essential</strong><p>Remembers your cookie choice and supports requested site functions.</p></div><label class="switch-label"><input type="checkbox" checked disabled> Always on</label></div>
    <div class="cookie-choice"><div><strong>Analytics &amp; SEO performance</strong><p>Allows the ClickRank script to load for SEO/performance analysis. It remains blocked if you reject this category.</p></div><label class="switch-label"><input type="checkbox" data-analytics> Allow</label></div>
    <div class="cookie-actions"><button class="btn" type="button" data-cookie="save">Save choices</button></div>
  </div>`;
document.body.appendChild(banner);

const reopen=document.createElement('button');reopen.className='cookie-settings';reopen.type='button';reopen.textContent='Cookie settings';reopen.hidden=!consent;document.body.appendChild(reopen);
const analyticsBox=banner.querySelector('[data-analytics]');
if(consent)analyticsBox.checked=!!consent.analytics;
if(consent)banner.hidden=true;

function saveConsent(analytics){
  const wasAnalytics=!!consent?.analytics;
  consent={essential:true,analytics:Boolean(analytics),updated:new Date().toISOString(),version:2};
  safeStorage.set(JSON.stringify(consent));
  if(consent.analytics)loadOptionalTools();
  banner.hidden=true;reopen.hidden=false;
  document.dispatchEvent(new CustomEvent('relet:consent',{detail:consent}));
  if(wasAnalytics&&!consent.analytics)location.reload();
}
banner.addEventListener('click',e=>{
  const btn=e.target.closest('[data-cookie]');if(!btn)return;
  const action=btn.dataset.cookie;
  if(action==='accept')saveConsent(true);
  if(action==='reject')saveConsent(false);
  if(action==='manage'){
    const panel=banner.querySelector('.cookie-manage');
    panel.hidden=!panel.hidden;btn.setAttribute('aria-expanded',String(!panel.hidden));
  }
  if(action==='save')saveConsent(analyticsBox.checked);
});
reopen.addEventListener('click',()=>{
  banner.hidden=false;
  const panel=banner.querySelector('.cookie-manage');panel.hidden=false;
  const manage=banner.querySelector('[data-cookie="manage"]');if(manage)manage.setAttribute('aria-expanded','true');
  analyticsBox.checked=!!consent?.analytics;
  banner.querySelector('[data-cookie="accept"]')?.focus();
});
