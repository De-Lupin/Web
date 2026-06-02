    <div class="page-title">🔍 Tra cứu đơn hàng</div>
    <div class="card">
      <div class="card-hd">Tìm kiếm</div>
      <div class="card-bd">
        <div class="fbar">
          <select id="tc_type" class="field" style="max-width:160px">
            <option value="ma_don">Theo mã đơn</option>
            <option value="ten_hang">Theo tên hàng</option>
            <option value="ten_gui">Theo người gửi</option>
            <option value="ten_nhan">Theo người nhận</option>
          </select>
          <input type="text" id="tc_q" class="field" placeholder="Nhập từ khóa để lọc..." oninput="timKiem()">
          <button class="btn btn-ghost btn-sm" onclick="resetSearch()">↺ Xem tất cả</button>
        </div>
        <div id="tc_area"></div>
      </div>
    </div>
    <script>
    const allDon=<?=json_encode(array_values($don_all??[]),JSON_UNESCAPED_UNICODE)?>;
    const mauMap={cho_duyet:'bdg-warn',dang_giao:'bdg-blue',hoan_thanh:'bdg-green',da_thanh_toan:'bdg-green',huy:'bdg-red'};
    const tenMap={cho_duyet:'Chờ duyệt',dang_giao:'Đang giao',hoan_thanh:'Hoàn thành',da_thanh_toan:'Đã thanh toán',huy:'Đã hủy',waiting_payment:'Chờ thanh toán'};

    function renderDon(list){
      const area=document.getElementById('tc_area');
      if(!list.length){area.innerHTML='<div class="empty"><div class="ei">😐</div><p>Không tìm thấy kết quả nào.</p></div>';return;}
      let html='<div style="max-height:480px;overflow-y:auto"><table class="tbl" style="margin-bottom:0"><thead style="position:sticky;top:0;z-index:1;background:#f5eeff"><tr><th>Mã đơn</th><th>Hàng hóa</th><th>Ngày tạo</th><th>Cước phí</th><th>Thanh toán</th><th>Trạng thái</th></tr></thead><tbody>';
      list.forEach(d=>{
        const mau=mauMap[d.trang_thai]||'bdg-gray';
        const ten=tenMap[d.trang_thai]||d.trang_thai;
        const psMap={unpaid:'bdg-gray',pending:'bdg-warn',paid:'bdg-green',failed:'bdg-red'};
        const psLabel={unpaid:'Chưa TT',pending:'Chờ TT',paid:'Đã TT',failed:'TT thất bại'};
        const psMau=psMap[d.payment_status]||'bdg-gray';
        const psLabel2=psLabel[d.payment_status]||'—';
        html+=`<tr onclick="moChiTiet(${JSON.stringify(d).replace(/'/g,'&#39;')})">
          <td style="font-family:monospace;font-size:12px;color:#6c3483">${d.ma_don||''}</td>
          <td><div style="font-weight:600">${d.ten_hang||''}</div><div style="font-size:11px;color:#7f8c8d">${d.dia_chi_gui||''} → ${d.dia_chi_nhan||''}</div></td>
          <td style="font-size:12px;color:#7f8c8d">${(d.ngay_tao||'').slice(0,16)}</td>
          <td style="font-weight:700;color:#6c3483">${Number(d.doanh_thu||0).toLocaleString('vi-VN')} đ</td>
          <td><span class="bdg ${psMau}">${psLabel2}</span></td>
          <td><span class="bdg ${mau}">${ten}</span></td>
        </tr>`;
      });
      html+='</tbody></table></div>';
      area.innerHTML=html;
    }

    function timKiem(){
      const type=document.getElementById('tc_type').value;
      const q=document.getElementById('tc_q').value.trim().toLowerCase();
      if(!q){renderDon(allDon);return;}
      renderDon(allDon.filter(d=>(d[type]||'').toLowerCase().includes(q)));
    }

    function resetSearch(){
      document.getElementById('tc_q').value='';
      renderDon(allDon);
    }

    // Hiện tất cả khi load trang
    renderDon(allDon);
    </script>

