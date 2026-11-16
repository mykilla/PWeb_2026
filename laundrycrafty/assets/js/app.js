const $=(s,r=document)=>r.querySelector(s),$$=(s,r=document)=>[...r.querySelectorAll(s)];
$('#burger')?.addEventListener('click',()=>$('#side').classList.toggle('open'));
$$('[data-confirm]').forEach(b=>b.addEventListener('click',e=>{if(!confirm(b.dataset.confirm))e.preventDefault()}));
$$('[data-autosubmit]').forEach(s=>s.addEventListener('change',()=>s.form.submit()));
const lay=$('#layanan'),ber=$('#berat'),tot=$('#total');
function hitung(){if(!tot)return;const h=+(lay.selectedOptions[0]?.dataset.harga||0),b=+ber.value||0;tot.textContent='Rp '+Math.round(h*b).toLocaleString('id-ID')}
lay?.addEventListener('change',hitung);ber?.addEventListener('input',hitung);
$$('form[data-validate]').forEach(f=>f.addEventListener('submit',e=>{const m=f.elements.tanggal_masuk,s=f.elements.tanggal_selesai;
 if(m&&s&&s.value<m.value){e.preventDefault();alert('Tanggal selesai tidak boleh sebelum tanggal masuk.')}}));
setTimeout(()=>$$('.flash:not(.err)').forEach(f=>f.style.display='none'),4500);
