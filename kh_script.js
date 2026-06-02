// JS cho cổng khách hàng - main_khach_hang.php

// GHI CHU CO SAN
function toggleGhiChuCosan(){
  const o=document.getElementById('gc-overlay');
  if(!o) return;
  if(o.style.display==='none'||o.style.display===''){
    o.style.display='flex';
  } else {
    o.style.display='none';
    document.querySelectorAll('.gc-check').forEach(c=>c.checked=false);
  }
}
function xacNhanGhiChu(){
  const checks=document.querySelectorAll('.gc-check:checked');
  const vals=[...checks].map(c=>c.value);
  const ta=document.getElementById('gc-text');
  if(vals.length){
    const cur=ta.value.trim();
    const add=vals.join(', ');
    ta.value=cur?(cur+', '+add):add;
  }
  const o=document.getElementById('gc-overlay');
  if(o) o.style.display='none';
  document.querySelectorAll('.gc-check').forEach(c=>c.checked=false);
}

// HUONG DAN
function toggleHD(){
  const body = document.getElementById('hd-body');
  const icon = document.getElementById('hd-icon');
  if(body.style.display==='none'){
    body.style.display='block';
    icon.textContent='▾';
  } else {
    body.style.display='none';
    icon.textContent='▸';
  }
}

// STEP FORM
function nextStep(){
  // Validate bước 1
  const required=['ten_gui','sdt_gui','dia_chi_gui','ten_nhan','sdt_nhan','dia_chi_nhan','ten_hang','ngay_gui','khoi_luong'];
  let ok=true;
  required.forEach(n=>{
    const el=document.querySelector(`[name="${n}"]`);
    if(el && !el.value.trim()){el.style.borderColor='#e74c3c';ok=false;}
    else if(el) el.style.borderColor='';
  });
  // Kiểm tra tỉnh
  const tGui=document.getElementById('tinh-gui')?.value||'';
  const tNhan=document.getElementById('tinh-nhan')?.value||'';
  if(!tGui){alert('Vui lòng chọn Tỉnh/Thành phố lấy hàng');return;}
  if(!tNhan){alert('Vui lòng chọn Tỉnh/Thành phố giao hàng');return;}
  if(!ok){alert('Vui lòng điền đầy đủ thông tin bắt buộc (*)');return;}

  document.getElementById('step1-area').style.display='none';
  document.getElementById('step1-btns').style.display='none';
  document.getElementById('step2-area').style.display='block';
  // Update step indicator
  document.getElementById('step1-ind').style.background='#c8e6c9';
  document.getElementById('step1-ind').style.color='#1e8449';
  document.getElementById('step2-ind').style.background='linear-gradient(135deg,#6c3483,#9b59b6)';
  document.getElementById('step2-ind').style.color='#fff';
}
function prevStep(){
  document.getElementById('step1-area').style.display='block';
  document.getElementById('step1-btns').style.display='flex';
  document.getElementById('step2-area').style.display='none';
  document.getElementById('step1-ind').style.background='linear-gradient(135deg,#6c3483,#9b59b6)';
  document.getElementById('step1-ind').style.color='#fff';
  document.getElementById('step2-ind').style.background='#e8d5f0';
  document.getElementById('step2-ind').style.color='#7f8c8d';
}
function chonPT(val){
  ['tien_mat','chuyen_khoan','the_tin_dung','vi_dien_tu'].forEach(v=>{
    document.getElementById('po-'+v)?.classList.toggle('active',v===val);
  });
  document.getElementById('pay-notice-cod').style.display=val==='tien_mat'?'block':'none';
  document.getElementById('pay-notice-ck').style.display=val==='chuyen_khoan'?'block':'none';
  document.getElementById('pay-notice-the').style.display=val==='the_tin_dung'?'block':'none';
  document.getElementById('pay-notice-vi').style.display=val==='vi_dien_tu'?'block':'none';
}
function syncPayment(){
  const sel=document.querySelector('[name="thanh_toan_step2"]:checked');
  if(sel){
    const hidden=document.querySelector('[name="thanh_toan"]');
    if(hidden) hidden.value=sel.value;
  }
}

// SIDEBAR TOGGLE
let sidebarOpen = true;
function toggleSidebar(){
  sidebarOpen=!sidebarOpen;
  document.getElementById('sidebar').classList.toggle('closed',!sidebarOpen);
  document.getElementById('main').classList.toggle('expanded',!sidebarOpen);
  document.getElementById('overlay').classList.toggle('show', sidebarOpen && window.innerWidth<768);
  const ft=document.getElementById('footer');
  if(ft) ft.classList.toggle('expanded',!sidebarOpen);
}
function closeSidebar(){
  sidebarOpen=false;
  document.getElementById('sidebar').classList.add('closed');
  document.getElementById('main').classList.add('expanded');
  document.getElementById('overlay').classList.remove('show');
  const ft=document.getElementById('footer');
  if(ft) ft.classList.add('expanded');
}
// Mobile: sidebar đóng mặc định
if(window.innerWidth<768){
  document.getElementById('sidebar').classList.add('closed');
  document.getElementById('main').classList.add('expanded');
  const ft=document.getElementById('footer');
  if(ft) ft.classList.add('expanded');
  sidebarOpen=false;
}

// TÍNH CƯỚC
const GIA={duong_bo:150,duong_bien:100,hang_khong:500,duong_sat:80};
const HS={thuong:1.0,de_vo:1.2,lanh:1.25,qua_kho:1.6,nguy_hiem:1.8};

// Phụ phí khoảng cách theo 4 vùng địa lý (sau sáp nhập 2025)
const VUNG = {
  'TP. Hồ Chí Minh':1,
  'Đồng Nai':1,
  'Tây Ninh':1,
  'An Giang':2,
  'Cần Thơ':2,
  'Cà Mau':2,
  'Đồng Tháp':2,
  'Khánh Hòa':3,
  'Đà Nẵng':3,
  'Quảng Trị':3,
  'Huế':3,
  'Quảng Ngãi':3,
  'Gia Lai':3,
  'Đắk Lắk':3,
  'Lâm Đồng':3,
  'Hà Nội':4,
  'Hải Phòng':4,
  'Thái Nguyên':4,
  'Phú Thọ':4,
  'Lào Cai':4,
  'Thanh Hóa':4,
  'Nghệ An':4,
  'Hà Tĩnh':4,
  'Quảng Ninh':4,
  'Sơn La':4,
  'Điện Biên':4,
  'Lai Châu':4,
  'Cao Bằng':4,
  'Lạng Sơn':4,
  'Tuyên Quang':4,
  'Ninh Bình':4,
  'Bắc Ninh':4,
};
// Hệ số khoảng cách: cùng vùng × 1.0, cách 1 vùng × 1.2, cách 2 vùng × 1.4, cách 3 vùng × 1.6
function hsKhoangCach(tGui, tNhan){
  const v1 = VUNG[tGui] || 2;
  const v2 = VUNG[tNhan] || 2;
  const diff = Math.abs(v1-v2);
  return [1.0, 1.2, 1.4, 1.6][Math.min(diff,3)];
}

// Phí bốc xếp cố định theo phương thức
const PHI_BOC_XEP={duong_bo:50000,duong_bien:80000,hang_khong:120000,duong_sat:60000};
// Phụ phí nhiên liệu (% trên cước cơ bản)
const PHI_NHIEN_LIEU={duong_bo:0.10,duong_bien:0.08,hang_khong:0.15,duong_sat:0.07};

function fmtVND(n){return n<=0?'0 đ':Math.round(n).toLocaleString('vi-VN')+' đ';}
function calcKL(kl,tv){return Math.max(kl||0,(tv||0)*200);}

function calc(){
  const kl=parseFloat(document.getElementById('kl')?.value)||0;
  const tv=parseFloat(document.getElementById('the_tich')?.value)||0;
  const pt=document.getElementById('pt')?.value||'duong_bo';
  const lh=document.getElementById('loai_hang')?.value||'thuong';
  const disp=document.getElementById('cuoc-display');
  const note=document.getElementById('cuoc-note');
  const hid=document.getElementById('cuoc_phi_tt');
  if(!disp)return;
  const klT=calcKL(kl,tv);
  if(klT<=0){disp.textContent='Nhập khối lượng để tính tự động';if(note)note.textContent='';if(hid)hid.value=0;return;}

  // Lấy tỉnh gửi/nhận từ dropdown
  const tGui  = document.getElementById('tinh-gui')?.value  || '';
  const tNhan = document.getElementById('tinh-nhan')?.value || '';

  // Tính từng loại phí
  const cuocCoBan   = klT * GIA[pt] * HS[lh];
  const hsKC        = hsKhoangCach(tGui, tNhan);
  const cuocKC      = cuocCoBan * (hsKC - 1); // phần phụ phí thêm
  const phiNL       = cuocCoBan * PHI_NHIEN_LIEU[pt];
  const phiBX       = PHI_BOC_XEP[pt];
  const tongCuoc    = cuocCoBan + cuocKC + phiNL + phiBX;

  disp.textContent = fmtVND(tongCuoc);

  // Hiện chi tiết
  let details = `KL tính cước: ${klT.toFixed(2)} kg | Cước cơ bản: ${fmtVND(cuocCoBan)}`;
  if(cuocKC>0) details += ` | Phụ phí khoảng cách (×${hsKC}): ${fmtVND(cuocKC)}`;
  details += ` | Nhiên liệu: ${fmtVND(phiNL)} | Bốc xếp: ${fmtVND(phiBX)}`;
  if(!tGui||!tNhan) details += ' ⚠️ Chọn tỉnh để tính phụ phí khoảng cách chính xác';
  if(note) note.textContent = details;
  if(hid) hid.value = Math.round(tongCuoc);
}

function xemTruoc(){
  const g=n=>document.querySelector(`[name="${n}"]`)?.value||'—';
  const ptM={duong_bo:'Đường bộ',duong_bien:'Đường biển',hang_khong:'Hàng không',duong_sat:'Đường sắt'};
  const lhM={thuong:'Hàng thường',de_vo:'Hàng dễ vỡ',lanh:'Hàng lạnh',qua_kho:'Hàng quá khổ',nguy_hiem:'Hàng nguy hiểm'};
  const ttM={tien_mat:'Tiền mặt (COD)',chuyen_khoan:'Chuyển khoản',vi_dien_tu:'Ví điện tử'};
  const cuoc=document.getElementById('cuoc-display')?.textContent||'—';
  // Lấy thanh toán từ radio bước 2 hoặc hidden field
  const ttSel=document.querySelector('[name="thanh_toan_step2"]:checked')?.value
              || document.querySelector('[name="thanh_toan"]')?.value || 'tien_mat';
  // Lấy tỉnh
  const tGui=document.getElementById('disp-gui')?.textContent||'—';
  const tNhan=document.getElementById('disp-nhan')?.textContent||'—';
  const rows=[
    ['Người gửi', g('ten_gui')],
    ['SĐT người gửi', g('sdt_gui')],
    ['Tỉnh/TP lấy hàng', tGui],
    ['Địa chỉ lấy', g('dia_chi_gui')],
    ['Người nhận', g('ten_nhan')],
    ['SĐT người nhận', g('sdt_nhan')],
    ['Tỉnh/TP giao hàng', tNhan],
    ['Địa chỉ giao', g('dia_chi_nhan')],
    ['Tên hàng', g('ten_hang')],
    ['Loại hàng', lhM[g('loai_hang')]||g('loai_hang')],
    ['Khối lượng', (()=>{ const v=parseFloat(g('khoi_luong')||0); return v>=1000?`${(v/1000).toLocaleString('vi-VN')} tấn`:`${v.toLocaleString('vi-VN')} kg`; })()],
    ['Phương thức', ptM[g('phuong_thuc')]||g('phuong_thuc')],
    ['Thanh toán', ttM[ttSel]||ttSel],
    ['Ngày gửi', g('ngay_gui')],
    ['Cước phí', `<b style="color:#6c3483">${cuoc}</b>`],
  ];
  document.getElementById('modalXTContent').innerHTML=rows.map(([k,v])=>`<div class="mrow"><span class="mk">${k}</span><span class="mv">${v}</span></div>`).join('');
  document.getElementById('modalXT').classList.add('open');
}

let _don=null;
function moChiTiet(d){
  if(typeof d==='string')d=JSON.parse(d);
  _don=d;
  const ptM={duong_bo:'Đường bộ',duong_bien:'Đường biển',hang_khong:'Hàng không',duong_sat:'Đường sắt'};
  const lhM={thuong:'Hàng thường',de_vo:'Hàng dễ vỡ',lanh:'Hàng lạnh',qua_kho:'Hàng quá khổ',nguy_hiem:'Hàng nguy hiểm'};
  const ttM={tien_mat:'Tiền mặt',chuyen_khoan:'Chuyển khoản',the_tin_dung:'Thẻ tín dụng',vi_dien_tu:'Ví điện tử'};
  const mauM={cho_duyet:'bdg-warn',dang_giao:'bdg-blue',dang_van_chuyen:'bdg-blue',dang_xu_ly:'bdg-blue',dang_lay_hang:'bdg-blue',da_giao:'bdg-green',hoan_thanh:'bdg-green',da_thanh_toan:'bdg-green',huy:'bdg-red',waiting_payment:'bdg-orange'};
  const tenM={cho_duyet:'Chờ duyệt',dang_giao:'Đang giao',dang_van_chuyen:'Đang vận chuyển',dang_xu_ly:'Đang xử lý',dang_lay_hang:'Đang lấy hàng',da_giao:'Đã giao',hoan_thanh:'Hoàn thành',da_thanh_toan:'Đã thanh toán',huy:'Đã hủy',waiting_payment:'Chờ thanh toán'};
  const mau=mauM[d.trang_thai]||'bdg-gray';
  const psMapM={unpaid:'bdg-gray',pending:'bdg-warn',paid:'bdg-green',failed:'bdg-red'};
  const psLabelM={unpaid:'Chưa thanh toán',pending:'Chờ thanh toán',paid:'Đã thanh toán',failed:'Thanh toán thất bại'};
  const psMauM=psMapM[d.payment_status]||'bdg-gray';
  const psLblM=psLabelM[d.payment_status]||'—';
  const klVal = parseFloat(d.khoi_luong||0);
  const klHien = klVal >= 1000 ? `${(klVal/1000).toLocaleString('vi-VN')} tấn` : `${klVal.toLocaleString('vi-VN')} kg`;
  const rows=[['Mã đơn',`<span style="font-family:monospace;color:#6c3483">${d.ma_don||'—'}</span>`],['Ngày tạo',(d.ngay_tao||'').slice(0,16)],['Tên hàng',d.ten_hang||'—'],['Loại hàng',lhM[d.loai_hang]||d.loai_hang||'—'],['Khối lượng',klHien],['Phương thức vận chuyển',ptM[d.phuong_thuc]||d.phuong_thuc||'—'],['Phương thức thanh toán',ttM[d.thanh_toan]||d.thanh_toan||'—'],['Người gửi',(d.ten_gui||'—')+' — '+(d.sdt_gui||'')],['Địa chỉ lấy',d.dia_chi_gui||'—'],['Người nhận',(d.ten_nhan||'—')+' — '+(d.sdt_nhan||'')],['Địa chỉ giao',d.dia_chi_nhan||'—'],['Cước phí',`<b style="color:#6c3483">${Number(d.doanh_thu||0).toLocaleString('vi-VN')} đ</b>`],['Trạng thái thanh toán',`<span class="bdg ${psMauM}">${psLblM}</span>`],['Trạng thái',`<span class="bdg ${mau}">${tenM[d.trang_thai]||d.trang_thai}</span>`],['Ghi chú',d.ghi_chu||'—']];
  document.getElementById('modalDonContent').innerHTML=rows.map(([k,v])=>`<div class="mrow"><span class="mk">${k}</span><span class="mv">${v}</span></div>`).join('');
  // Chỉ hiện nút Sửa/Hủy/ThanhToan khi đang ở trang lịch sử
  const isLichSu = window.location.search.includes('page=lich-su') || window.location.search === '';
  const canEdit = isLichSu && (d.trang_thai==='cho_duyet'||d.trang_thai==='waiting_payment');
  document.getElementById('btnSua').style.display=canEdit?'inline-flex':'none';
  document.getElementById('btnHuy').style.display=canEdit?'inline-flex':'none';
  const btnTT = document.getElementById('btnThanhToan');
  if(btnTT){
    if(isLichSu && d.trang_thai==='waiting_payment'){
      btnTT.style.display='inline-flex';
      btnTT.href='?page=thanh-toan-qr&ma='+encodeURIComponent(d.ma_don||'');
    } else {
      btnTT.style.display='none';
    }
  }
  document.getElementById('modalDon').classList.add('open');
}
function huyDon(){
  if(!_don)return;
  if(!confirm('Bạn có chắc muốn hủy đơn '+_don.ma_don+'?'))return;
  document.getElementById('hdAction').value='huy_don';
  document.getElementById('hdMaDon').value=_don.ma_don;
  document.getElementById('frmHD').submit();
}
function moSua(){
  if(!_don)return;
  const d=_don;

  // Kiểm tra 24h
  const ngayTao=new Date(d.ngay_tao);
  const now=new Date();
  const diff=(now-ngayTao)/1000/3600;
  if(diff>24){
    alert('Đã quá 24 giờ kể từ khi tạo đơn. Không thể chỉnh sửa nữa.');
    return;
  }
  if(d.trang_thai==='waiting_payment'){
    if(!confirm('Đơn này đang chờ thanh toán. Bạn vẫn muốn chỉnh sửa?')) return;
  }

  document.getElementById('suaMaDon').value=d.ma_don||'';
  document.getElementById('suaTenGui').value=d.ten_gui||'';
  document.getElementById('suaTenNhan').value=d.ten_nhan||'';
  document.getElementById('suaSdtGui').value=d.sdt_gui||'';
  document.getElementById('suaSdtNhan').value=d.sdt_nhan||'';
  document.getElementById('suaEmailGui').value=d.email_gui||'';
  document.getElementById('suaEmailNhan').value=d.email_nhan||'';
  document.getElementById('suaDCGui').value=d.dia_chi_gui||'';
  document.getElementById('suaDCNhan').value=d.dia_chi_nhan||'';
  document.getElementById('suaTenHang').value=d.ten_hang||'';
  document.getElementById('suaKL').value=d.khoi_luong ? parseFloat(d.khoi_luong) : '';
  document.getElementById('suaGhiChu').value=d.ghi_chu||'';
  document.getElementById('suaNgayGui').value=(d.ngay_gui||d.ngay_tao||'').slice(0,10);
  const loaiEl=document.getElementById('suaLoaiHang');
  if(loaiEl) [...loaiEl.options].forEach(o=>o.selected=o.value===d.loai_hang);
  const ptEl=document.getElementById('suaPT');
  if(ptEl) [...ptEl.options].forEach(o=>o.selected=o.value===d.phuong_thuc);
  const ttEl=document.getElementById('suaTT');
  if(ttEl) [...ttEl.options].forEach(o=>o.selected=o.value===d.thanh_toan);

  document.getElementById('modalDon').classList.remove('open');
  document.getElementById('modalSua').classList.add('open');
}

// PROVINCE DATA & LOGIC
// Danh sách 34 tỉnh thành Việt Nam sau sáp nhập (Nghị quyết 202/2025/QH15, hiệu lực 1/7/2025)
const TINH_VN = [
  'An Giang',
  'Bắc Ninh',
  'Cao Bằng',
  'Cần Thơ',
  'Cà Mau',
  'Đà Nẵng',
  'Đắk Lắk',
  'Điện Biên',
  'Đồng Nai',
  'Đồng Tháp',
  'Gia Lai',
  'Hà Nội',
  'Hà Tĩnh',
  'Hải Phòng',
  'Huế',
  'Khánh Hòa',
  'Lai Châu',
  'Lâm Đồng',
  'Lạng Sơn',
  'Lào Cai',
  'Nghệ An',
  'Ninh Bình',
  'Phú Thọ',
  'Quảng Ngãi',
  'Quảng Ninh',
  'Quảng Trị',
  'Sơn La',
  'Tây Ninh',
  'Thái Nguyên',
  'Thanh Hóa',
  'TP. Hồ Chí Minh',
  'Tuyên Quang',
];

function renderTinhList(type, filter=''){
  const list = document.getElementById('list-tinh-'+type);
  if(!list) return;
  const filtered = filter ? TINH_VN.filter(t=>t.toLowerCase().includes(filter.toLowerCase())) : TINH_VN;
  let lastLetter = '';
  list.innerHTML = filtered.map(t=>{
    const letter = t[0].toUpperCase();
    const showLetter = letter !== lastLetter;
    lastLetter = letter;
    return `<div class="dc-item" onclick="chonTinh('${type}','${t}')">${showLetter?`<span class="dc-letter">${letter}</span>`:''}<span>${t}</span></div>`;
  }).join('');
}

function toggleDC(type){
  const drop = document.getElementById('drop-'+type);
  const other = type==='gui'?'nhan':'gui';
  document.getElementById('drop-'+other)?.classList.remove('open');
  drop.classList.toggle('open');
  if(drop.classList.contains('open')){
    renderTinhList(type);
    drop.querySelector('.dc-search')?.focus();
  }
}

function filterTinh(type, val){
  renderTinhList(type, val);
}

function chonTinh(type, tinh){
  document.getElementById('disp-'+type).textContent = tinh;
  document.getElementById('tinh-'+type).value = tinh;
  document.getElementById('drop-'+type).classList.remove('open');
  document.getElementById('wrap-'+type).querySelector('.dc-input').classList.add('selected');
  // Auto update dia_chi placeholder
  const dc = document.getElementById('dc-'+type);
  if(dc) dc.placeholder = 'Số nhà, tên đường, phường/xã... ' + tinh;
  // Cập nhật cước phí khi đổi tỉnh
  calc();
}

// Close dropdown when click outside
document.addEventListener('click', e=>{
  if(!e.target.closest('.dc-wrap')){
    document.querySelectorAll('.dc-drop').forEach(d=>d.classList.remove('open'));
  }
});

document.querySelectorAll('.mbg').forEach(m=>{
  m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');});
});

// Click nền tối gc-overlay thì đóng
document.addEventListener('click', e=>{
  const o=document.getElementById('gc-overlay');
  if(o && e.target===o){
    o.style.display='none';
    document.querySelectorAll('.gc-check').forEach(c=>c.checked=false);
  }
});