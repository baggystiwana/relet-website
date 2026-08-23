const toggle=document.querySelector('.nav-toggle'),nav=document.querySelector('.nav-links');
if(toggle&&nav)toggle.addEventListener('click',()=>{const open=nav.classList.toggle('open');toggle.setAttribute('aria-expanded',String(open))});
document.querySelectorAll('[data-year]').forEach(el=>el.textContent=new Date().getFullYear());

function emailQuote(data){
  const subject=encodeURIComponent('Re-Let quote request: '+(data.get('service')||'Property work'));
  const body=encodeURIComponent(`Name: ${data.get('name')||''}\nPhone: ${data.get('phone')||''}\nEmail: ${data.get('email')||''}\nPostcode: ${data.get('postcode')||''}\nCustomer: ${data.get('customer_type')||''}\nService: ${data.get('service')||''}\nProperty status: ${data.get('occupancy')||''}\nAccess: ${data.get('access')||''}\nUrgency: ${data.get('urgency')||''}\nPreferred contact: ${data.get('contact_method')||''}\n\nDetails:\n${data.get('details')||''}`);
  location.href=`mailto:info@relet.co.uk?subject=${subject}&body=${body}`;
}
const pageForm=document.querySelector('[data-quote-form]');
if(pageForm)pageForm.addEventListener('submit',e=>{e.preventDefault();emailQuote(new FormData(pageForm))});

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

// Quote modal used by service-page CTAs where dialog support is available.
const dialog=document.createElement('dialog');dialog.className='quote-modal';
dialog.innerHTML=`<div class="modal-head"><div><p class="eyebrow">Request a quote</p><h2>Tell us what needs doing and we’ll get back to you with the next step.</h2></div><button class="modal-close" type="button" aria-label="Close quote form">×</button></div><form class="form"><div class="form-row"><label>Name<input name="name" autocomplete="name" required></label><label>Phone number<input name="phone" type="tel" autocomplete="tel" required></label></div><label>Email<input name="email" type="email" autocomplete="email"></label><div class="form-row"><label>Property postcode<input name="postcode" autocomplete="postal-code" required></label><label>You are<select name="customer_type"><option>Landlord</option><option>Letting agent</option><option>Property manager</option><option>Homeowner</option></select></label></div><label>Service needed<select name="service" required><option value="">Choose one</option><option>Landlord repair</option><option>Void turnaround</option><option>End-of-tenancy / void cleaning</option><option>Joinery / doors / windows</option><option>Refurbishment</option><option>Other</option></select></label><label>Brief job description<textarea name="details" required></textarea></label><label>Preferred contact method<select name="contact_method"><option>Phone</option><option>Email</option><option>WhatsApp</option></select></label><label class="consent"><input type="checkbox" required> I agree that Re-Let may use my details to respond to this enquiry. See our <a href="/privacy.html">Privacy Policy</a>.</label><p class="notice">Required fields are limited to what we need for a useful first response.</p><button class="btn" type="submit">Prepare enquiry email</button></form>`;
document.body.appendChild(dialog);
let opener;
document.addEventListener('click',e=>{
  const link=e.target.closest('a[href$="contact.html"],a[href="/contact.html"]');
  if(link&&dialog.showModal){e.preventDefault();opener=link;dialog.showModal();dialog.querySelector('input')?.focus()}
});
dialog.querySelector('.modal-close')?.addEventListener('click',()=>dialog.close());
dialog.addEventListener('close',()=>opener?.focus());
dialog.addEventListener('click',e=>{if(e.target===dialog)dialog.close()});
dialog.querySelector('form')?.addEventListener('submit',e=>{e.preventDefault();emailQuote(new FormData(e.target))});
