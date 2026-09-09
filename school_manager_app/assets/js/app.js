document.addEventListener('DOMContentLoaded', () => {
  const $=(s,c=document)=>c.querySelector(s), $$=(s,c=document)=>[...c.querySelectorAll(s)];
  const body=document.body, sidebar=$('#sidebar'), toggle=$('#menuToggle'), close=$('#sidebarClose'), overlay=$('#sidebarOverlay'), collapse=$('#sidebarCollapse');
  const isMobile=()=>window.matchMedia('(max-width:720px)').matches;
  const setDrawer=open=>{ if(!sidebar)return; sidebar.classList.toggle('open',open); overlay?.classList.toggle('visible',open); body.classList.toggle('drawer-open',open); toggle?.setAttribute('aria-expanded',String(open)); };
  toggle?.addEventListener('click',()=>setDrawer(!sidebar.classList.contains('open')));
  close?.addEventListener('click',()=>setDrawer(false)); overlay?.addEventListener('click',()=>setDrawer(false));
  $$('.main-nav a').forEach(a=>a.addEventListener('click',()=>{if(isMobile())setDrawer(false);}));

  const savedCollapsed=localStorage.getItem('school-sidebar-collapsed')==='1';
  if(savedCollapsed && !isMobile()) sidebar?.classList.add('collapsed');
  const renderCollapse=()=>{const c=sidebar?.classList.contains('collapsed'); collapse?.setAttribute('aria-expanded',String(!c)); collapse?.setAttribute('title',c?'توسيع القائمة':'تصغير القائمة'); collapse?.setAttribute('aria-label',c?'توسيع القائمة':'تصغير القائمة'); if(collapse) collapse.innerHTML='<span>'+ (c?'›':'‹') +'</span>';};
  renderCollapse();
  collapse?.addEventListener('click',()=>{if(isMobile()){setDrawer(false);return;} sidebar.classList.toggle('collapsed'); localStorage.setItem('school-sidebar-collapsed',sidebar.classList.contains('collapsed')?'1':'0'); renderCollapse();});

  const theme=$('#themeToggle'), savedTheme=localStorage.getItem('school-theme'), prefers=window.matchMedia?.('(prefers-color-scheme: dark)').matches;
  if(savedTheme==='dark'||(!savedTheme&&prefers))body.classList.add('dark-mode');
  const renderTheme=()=>{const dark=body.classList.contains('dark-mode');if(theme){theme.textContent=dark?'☀':'☾';theme.title=dark?'العودة إلى الوضع الفاتح':'التبديل إلى الوضع الداكن';theme.setAttribute('aria-label',theme.title);}};
  renderTheme(); theme?.addEventListener('click',()=>{body.classList.toggle('dark-mode');localStorage.setItem('school-theme',body.classList.contains('dark-mode')?'dark':'light');renderTheme();});

  const accent=localStorage.getItem('school-accent')||'blue'; body.dataset.accent=accent;
  const applyAccent=v=>{body.dataset.accent=v;localStorage.setItem('school-accent',v);$$('[data-accent]').forEach(b=>b.classList.toggle('selected',b.dataset.accent===v));};
  applyAccent(accent);

  const menus=[
    {toggle:$('#notificationToggle'),panel:$('#notificationPanel')},
    {toggle:$('#userMenuToggle'),panel:$('#userPanel')}
  ];
  const closeMenus=except=>menus.forEach(m=>{if(!m.panel)return;const keep=m===except;m.panel.hidden=keep?false:true;m.toggle?.setAttribute('aria-expanded',String(keep));});
  menus.forEach(m=>m.toggle?.addEventListener('click',e=>{e.stopPropagation();const open=m.panel && !m.panel.hidden;closeMenus(open?null:m);}));
  document.addEventListener('click',e=>{if(!e.target.closest('.topbar-menu-wrap'))closeMenus(null);});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){setDrawer(false);closeMenus(null);}});
  $('#refreshPage')?.addEventListener('click',()=>window.location.reload());
  $('#appearanceToggle')?.addEventListener('click',()=>{const p=$('#appearanceOptions');if(p)p.hidden=!p.hidden;});
  $$('[data-accent]').forEach(b=>b.addEventListener('click',()=>applyAccent(b.dataset.accent)));

  $$('[data-auto-dismiss]').forEach(a=>setTimeout(()=>{a.style.opacity='0';a.style.transform='translateY(-8px)';setTimeout(()=>a.remove(),300)},5000));
  $$('[data-confirm-delete]').forEach(el=>el.addEventListener(el.tagName==='FORM'?'submit':'click',e=>{if(!window.confirm('هل أنت متأكد؟ هذه العملية قد تحذف السجل نهائيًا.'))e.preventDefault();}));

  $$('form').forEach(form=>{
    form.noValidate=true;
    const fields=$$('input,select,textarea',form);
    const clear=f=>{f.classList.remove('is-invalid');f.removeAttribute('aria-invalid');f.parentElement?.querySelector('.field-error')?.remove();};
    const error=(f,msg)=>{clear(f);f.classList.add('is-invalid');f.setAttribute('aria-invalid','true');const n=document.createElement('small');n.className='field-error';n.textContent=msg;f.insertAdjacentElement('afterend',n);};
    fields.forEach(f=>{if(f.dataset.hint&&!f.parentElement?.querySelector('.field-help')){const h=document.createElement('small');h.className='field-help';h.textContent=f.dataset.hint;f.insertAdjacentElement('afterend',h);} f.addEventListener('input',()=>clear(f));f.addEventListener('change',()=>clear(f));});
    form.addEventListener('submit',e=>{let first=null;fields.forEach(f=>{clear(f);if(f.disabled||f.type==='hidden')return;let msg='';if(f.required&&!String(f.value).trim())msg='هذا الحقل مطلوب.';else if(f.type==='email'&&f.value&&!f.validity.valid)msg='أدخل بريدًا إلكترونيًا صحيحًا.';else if(f.minLength>0&&f.value&&f.value.length<f.minLength)msg=`أدخل ${f.minLength} أحرف على الأقل.`;else if(f.type==='number'&&f.value&&!f.validity.valid)msg='أدخل رقمًا ضمن الحدود المسموحة.';if(msg){error(f,msg);first??=f;}});if(first){e.preventDefault();first.focus();return;}const submit=$('button[type="submit"]',form);if(submit&&!submit.dataset.allowRepeat&&!form.dataset.confirmDelete){submit.disabled=true;submit.dataset.originalText=submit.textContent;submit.textContent='جارٍ التنفيذ...';}});
  });
  $$('table tbody tr').forEach(row=>row.addEventListener('focusin',()=>row.classList.add('keyboard-focus')));
  window.addEventListener('resize',()=>{if(!isMobile()){setDrawer(false);} else {sidebar?.classList.remove('collapsed');}});
});