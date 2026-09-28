let cart=[];

function add(p){
  let x=cart.find(i=>i.id==p.id);
  if(x){if(x.qty<p.stok)x.qty++;}
  else cart.push({...p,qty:1});
  render();
}
function ch(id,d){
  let x=cart.find(i=>i.id==id);if(!x)return;
  x.qty+=d;
  if(x.qty<1)cart=cart.filter(i=>i.id!=id);
  if(x.qty>x.stok)x.qty=x.stok;
  render();
}
function money(n){return "Rp "+Number(n||0).toLocaleString("id-ID")}
function render(){
  let c=document.getElementById("cart");if(!c)return;
  let sub=0,count=0;c.innerHTML="";
  document.getElementById("empty").style.display=cart.length?"none":"block";
  cart.forEach(x=>{
    let ss=x.qty*x.harga_jual;sub+=ss;count+=x.qty;
    c.innerHTML+=`<div class="cartrow"><div><b>${escapeHtml(x.nama)}</b><small>${money(x.harga_jual)} × ${x.qty}</small></div><div class="qty"><button type="button" onclick="ch(${x.id},-1)">−</button><b>${x.qty}</b><button type="button" onclick="ch(${x.id},1)">+</button></div><b>${money(ss)}</b></div>`;
  });
  let d=+document.getElementById("disc").value||0,t=Math.max(0,sub-d),method=document.getElementById("method").value,pay=+document.getElementById("pay").value||0;
  document.getElementById("total").textContent=money(t);
  document.getElementById("cartcount").textContent=count;
  let cash=method==="Tunai", debt=method==="Piutang";
  document.getElementById("cashbox").style.display=cash?"block":"none";
  document.getElementById("noncashbox").style.display=cash?"none":"block";
  if(cash){
    document.getElementById("change").textContent=pay>=t?"Kembalian "+money(pay-t):"Kurang "+money(t-pay);
  }else if(debt){
    document.getElementById("change").textContent="Piutang · jatuh tempo sesuai tanggal di form";
  }else{
    document.getElementById("change").textContent="Pembayaran "+method+" · "+money(t);
  }
  document.getElementById("cartdata").value=JSON.stringify(cart.map(x=>({id:x.id,qty:x.qty})));
}
function validateSale(){
  if(!cart.length){alert("Keranjang masih kosong.");return false;}
  let total=Number(document.getElementById("total").textContent.replace(/[^\d]/g,""))||0;
  let method=document.getElementById("method").value,pay=Number(document.getElementById("pay").value)||0;
  if(method==="Tunai" && pay<total){alert("Uang diterima masih kurang.");return false;}
  if(method==="Piutang"){
    document.getElementById("pay").value=0;
    document.getElementById("payment_ref").value="Piutang tempo";
  }else if(method!=="Tunai"){
    document.getElementById("pay").value=total;
    document.getElementById("payment_ref").value="Pembayaran "+method+" (konfirmasi kasir)";
  }
  return true;
}
function escapeHtml(s){return String(s).replace(/[&<>"']/g,m=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[m]));}
function openM(){document.getElementById("modal").classList.add("show");document.getElementById("pid").value="";document.getElementById("mt").textContent="Produk Baru"}
function closeM(){document.getElementById("modal").classList.remove("show")}
function editP(p){openM();document.getElementById("mt").textContent="Edit Produk";pid.value=p.id;pk.value=p.kode;pn.value=p.nama;pb.value=p.harga_beli;pj.value=p.harga_jual;ps.value=p.stok;pm.value=p.stok_min;pc.value=p.category_id||""}
document.addEventListener("DOMContentLoaded",()=>{
  let s=document.getElementById("search");
  if(s)s.oninput=()=>{let k=s.value.toLowerCase();document.querySelectorAll("#tbl tbody tr").forEach(r=>r.style.display=r.innerText.toLowerCase().includes(k)?"":"none")};
  let ps=document.getElementById("possearch");
  if(ps)ps.oninput=()=>{let k=ps.value.toLowerCase();document.querySelectorAll(".prod").forEach(x=>x.style.display=x.dataset.q.includes(k)?"":"none")};
  let bc=document.getElementById("barcode");
  if(bc)bc.addEventListener("keydown",e=>{
    if(e.key!=="Enter")return;
    e.preventDefault();let code=bc.value.trim().toLowerCase();if(!code)return;
    let p=[...document.querySelectorAll(".prod")].find(x=>x.dataset.code.toLowerCase()===code);
    if(p)p.click();else alert("Produk dengan kode tersebut tidak ditemukan.");
    bc.value="";
  });
  render();
});

// TokoFlow PRO: instant theme preview + persistent local preference
function applyThemePreview(){
  const mode=document.querySelector('input[name="mode"]:checked')?.value || 'light';
  const theme=document.querySelector('input[name="theme"]:checked')?.value || 'indigo';
  document.body.classList.remove('mode-light','mode-dark','theme-indigo','theme-blue','theme-emerald','theme-orange','theme-rose','theme-violet');
  document.body.classList.add('mode-'+mode,'theme-'+theme);
  try{localStorage.setItem('tokoflow_theme',theme);localStorage.setItem('tokoflow_mode',mode)}catch(e){}
}
function resetThemePreview(){
  const m=document.querySelector('input[name="mode"][value="light"]'), t=document.querySelector('input[name="theme"][value="indigo"]');
  if(m)m.checked=true;if(t)t.checked=true;applyThemePreview();
}
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('input[name="mode"],input[name="theme"]').forEach(x=>x.addEventListener('change',applyThemePreview));
  if(document.querySelector('.theme-section')){
    const savedMode=localStorage.getItem('tokoflow_mode'), savedTheme=localStorage.getItem('tokoflow_theme');
    if(savedMode && document.querySelector('input[name="mode"][value="'+savedMode+'"]')) document.querySelector('input[name="mode"][value="'+savedMode+'"]').checked=true;
    if(savedTheme && document.querySelector('input[name="theme"][value="'+savedTheme+'"]')) document.querySelector('input[name="theme"][value="'+savedTheme+'"]').checked=true;
    applyThemePreview();
  }
});

function updatePaymentUI(){
  const method=document.querySelector('[name="method"]')?.value || "Tunai";
  const box=document.getElementById("cashbox");
  const pay=document.getElementById("pay");
  if(box) box.style.display = method==="Tunai" ? "block" : "none";
  if(pay && method!=="Tunai") pay.value = method==="Piutang" ? 0 : (window.currentTotal || pay.value || 0);
  if(typeof render==="function") render();
}
document.addEventListener("DOMContentLoaded",()=>{
  const method=document.querySelector('[name="method"]');
  if(method){
    method.addEventListener("change", updatePaymentUI);
    updatePaymentUI();
  }
});

// TokoFlow practical cashier shortcuts
document.addEventListener("keydown",function(e){if(e.target.matches("input,textarea,select"))return;if(e.key==="F2"){e.preventDefault();document.getElementById("possearch")?.focus();}if(e.key==="F4"){e.preventDefault();document.getElementById("pay")?.focus();}if(e.key==="F8"){e.preventDefault();document.querySelector("#saleform button[type=submit]")?.click();}if(e.key==="Escape"){document.querySelector(".modal.show .x")?.click();}});


// Receipt actions: reliable browser printing and export helpers
function receiptText(){
  const el=document.querySelector('[data-receipt]');
  if(!el) return '';
  return el.innerText.replace(/Cetak Struk|Download Struk|Kirim via WhatsApp|Transaksi Baru/g,'').replace(/\n{3,}/g,'\n\n').trim();
}
function printReceipt(){
  if(!document.querySelector('[data-receipt]')) return;
  window.print();
}
function downloadReceipt(){
  const text=receiptText();
  if(!text) return;
  const blob=new Blob([text],{type:'text/plain;charset=utf-8'});
  const url=URL.createObjectURL(blob);
  const a=document.createElement('a');
  a.href=url; a.download='struk-tokoflow.txt';
  document.body.appendChild(a); a.click(); a.remove();
  setTimeout(()=>URL.revokeObjectURL(url),1000);
}
function whatsappReceipt(){
  const text=receiptText();
  if(!text) return;
  window.open('https://wa.me/?text='+encodeURIComponent(text),'_blank','noopener');
}
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelector('[data-print-receipt]')?.addEventListener('click',printReceipt);
  document.querySelector('[data-download-receipt]')?.addEventListener('click',downloadReceipt);
  document.querySelector('[data-whatsapp-receipt]')?.addEventListener('click',whatsappReceipt);
});

// Modern UI interactions: shift close reconciliation + audit expandable rows
let shiftExpected=0;
function openShiftCloseModal(id,start,cash,noncash,expected){
  shiftExpected=Number(expected)||0;
  const m=document.getElementById('shiftCloseModal');
  if(!m)return;
  m.classList.add('show');m.setAttribute('aria-hidden','false');
  const actual=document.getElementById('shiftActual');
  if(actual){actual.value='';setTimeout(()=>actual.focus(),80)}
  updateShiftDiff();
}
function closeShiftCloseModal(){const m=document.getElementById('shiftCloseModal');if(m){m.classList.remove('show');m.setAttribute('aria-hidden','true')}}
function updateShiftDiff(){
  const input=document.getElementById('shiftActual'),preview=document.getElementById('shiftActualPreview'),diff=document.getElementById('shiftDiffPreview');
  if(!input||!preview||!diff)return;
  const actual=Number(input.value)||0,delta=actual-shiftExpected;
  preview.textContent=money(actual);
  diff.className='shift-diff '+(input.value===''?'neutral':delta===0?'good':Math.abs(delta)<10000?'warn':'bad');
  diff.textContent=input.value===''?'Masukkan kas akhir fisik':(delta===0?'Kas sesuai harapan':(delta>0?'Selisih lebih '+money(delta):'Selisih kurang '+money(Math.abs(delta))));
}
function toggleAuditRow(row){
  row.classList.toggle('expanded');
  const detail=row.nextElementSibling;
  if(detail&&detail.classList.contains('audit-detail-row'))detail.classList.toggle('open');
}
document.addEventListener('click',e=>{const m=document.getElementById('shiftCloseModal');if(m&&e.target===m)closeShiftCloseModal()});

/* TokoFlow V2 mobile navigation */
document.addEventListener("DOMContentLoaded",()=>{
  document.querySelectorAll("aside nav a").forEach(a=>{
    a.addEventListener("click",()=>document.body.classList.remove("menu-open"));
  });
  document.addEventListener("keydown",e=>{
    if(e.key==="Escape")document.body.classList.remove("menu-open");
  });
});
