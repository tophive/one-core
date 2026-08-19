/* global BPDemoSteps, wp */
(function(){
  const { createElement: h, render, useState, useEffect } = window.wp.element || {};
  if (!h || !render) return;

  const PLUGIN_LABELS = { buddypress: 'BuddyPress', bbpress: 'bbPress' };
  const SETUP_OPTIONS = [
    { key: 'buddypress', label: 'BuddyPress', locked: true },
    { key: 'menus', label: 'Menus', locked: true },
    { key: 'customizer', label: 'Customizer', locked: true },
    { key: 'forums', label: 'bbPress Forums', locked: false },
  ];

  function Toggle({ checked, disabled, onChange }) {
    return h('button', { type:'button', disabled:!!disabled, onClick:()=>{ if(!disabled) onChange(!checked); }, className:`one-demo-toggle${checked?' is-active':''}${disabled?' is-disabled':''}`, 'aria-pressed':checked?'true':'false' }, h('span',{className:'one-demo-toggle__thumb'}));
  }

  function App(){
    const [tab,setTab]=useState('setup');
    const [selected,setSelected]=useState(()=>{ const d=BPDemoSteps.defaults||{}; const s={}; SETUP_OPTIONS.forEach(o=>s[o.key]=!!d[o.key]); return s; });
    const [done]=useState(()=>{ try { const saved=JSON.parse(localStorage.getItem('one_demo_done')||'{}'); if(BPDemoSteps.setup_imported){ saved.buddypress=true; saved.menus=true; saved.customizer=true; } return saved; } catch(e){ return BPDemoSteps.setup_imported?{buddypress:true,menus:true,customizer:true}:{}; } });
    const [pageSelection,setPageSelection]=useState({});
    const pages=Array.isArray(BPDemoSteps.pages)?BPDemoSteps.pages:[];

    useEffect(()=>{
      const steps=[];
      if(tab==='setup'){
        const slugs=[];
        if(selected.buddypress&&!done.buddypress) slugs.push('buddypress');
        if(selected.forums&&!done.forums) slugs.push('bbpress');
        slugs.forEach(slug=>steps.push({step:'install_plugins',payload:{slugs:[slug],label:`Installing plugin: ${PLUGIN_LABELS[slug]||slug}`}}));
        if(selected.buddypress&&!done.buddypress) steps.push({step:'configure_buddypress',payload:{label:'Configuring BuddyPress community…'}});
        if(selected.customizer&&!done.customizer) steps.push({step:'import_customizer',payload:{label:'Importing Customizer…'}});
        if(selected.menus&&!done.menus) steps.push({step:'import_menus',payload:{label:'Importing Menus…'}});
        if(selected.forums&&!done.forums) steps.push({step:'import_forums',payload:{label:'Importing bbPress forums…'}});
        if(!BPDemoSteps.setup_imported){
          steps.push({step:'import_widgets',payload:{label:'Importing Widgets…'}});
          steps.push({step:'setup_homepage',payload:{label:'Setting up Homepage…'}});
        }
      } else {
        const chosen=pages.filter(p=>pageSelection[p.key]).map(p=>p.key);
        if(chosen.length){
          steps.push({step:'install_plugins',payload:{slugs:['elementor'],label:'Checking Elementor…'}});
          steps.push({step:'import_pages',payload:{pages:chosen,reimport:chosen.some(k=>pages.find(p=>p.key===k)?.status==='imported')?1:0,label:`Importing ${chosen.length} starter page${chosen.length>1?'s':''}…`}});
        }
      }
      window.ONE_DEMO_SELECTED_STEPS=steps;
      window.ONE_DEMO_ACTIVE_TAB=tab;
    },[tab,selected,done,pageSelection,pages]);

    function setupView(){
      return h('div',null,
        h('div',{className:'one-demo-selector__header'},h('div',null,h('h3',null,'Demo setup'),h('p',null,'Core community settings and optional forums. Starter pages are managed separately.')),BPDemoSteps.setup_imported?h('span',{className:'one-demo-setup-badge'},'Setup imported'):null),
        h('div',{className:'one-demo-options'},SETUP_OPTIONS.map(o=>h('div',{key:o.key,className:`one-demo-option${o.locked?' is-locked':''}`},h('div',{className:'one-demo-option__main'},h('div',{className:'one-demo-option__icon'},o.label.charAt(0)),h('div',{className:'one-demo-option__copy'},h('strong',null,o.label),done[o.key]?h('span',{className:'one-demo-option__meta is-done'},h('i'), 'Imported'):o.locked?h('span',{className:'one-demo-option__meta'},h('i'),'Required'):h('span',{className:'one-demo-option__meta'},'Optional'))),h(Toggle,{checked:!!selected[o.key],disabled:o.locked,onChange:()=>setSelected(c=>({...c,[o.key]:!c[o.key]}))}))))
      );
    }

    function pagesView(){
      return h('div',null,
        h('div',{className:'one-demo-selector__header'},h('div',null,h('h3',null,'Starter pages'),h('p',null,'Import only the pages you need. Existing One demo pages are updated in place; customer pages are never overwritten.'))),
        h('div',{className:'one-demo-page-list'},pages.length?pages.map(p=>{
          const conflict=p.status==='conflict', imported=p.status==='imported', checked=!!pageSelection[p.key];
          return h('article',{key:p.key,className:`one-demo-page${conflict?' has-conflict':''}${imported?' is-imported':''}`},
            h('div',{className:'one-demo-page__copy'},h('div',{className:'one-demo-page__icon'},p.title.charAt(0)),h('div',null,h('div',{className:'one-demo-page__title-row'},h('strong',null,p.title),h('span',{className:`one-demo-page__status is-${p.status}`},conflict?'Existing page':imported?'Imported':'Available')),h('p',null,p.description),h('code',null,'/'+p.slug+'/'))),
            h('div',{className:'one-demo-page__actions'},imported&&p.view_url?h('a',{href:p.view_url,target:'_blank',rel:'noopener noreferrer',className:'one-demo-page__link'},'View'):null, conflict?h('span',{className:'one-demo-page__note'},'Protected'):h('button',{type:'button',className:`one-demo-page__button${checked?' is-selected':''}`,onClick:()=>setPageSelection(c=>({...c,[p.key]:!c[p.key]}))},checked?'Selected':imported?'Re-import':'Import'))
          );
        }):h('div',{className:'one-demo-empty'},'No starter pages are available yet.'))
      );
    }

    return h('div',{className:'one-demo-selector'},h('div',{className:'one-demo-tabs',role:'tablist'},h('button',{type:'button',className:tab==='setup'?'is-active':'',onClick:()=>setTab('setup')},'Setup'),h('button',{type:'button',className:tab==='pages'?'is-active':'',onClick:()=>setTab('pages')},'Pages',pages.length?h('span',null,pages.length):null)),tab==='setup'?setupView():pagesView());
  }
  const root=document.getElementById('one-demo-react-root'); if(root) render(h(App),root);
})();
