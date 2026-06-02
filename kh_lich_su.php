    <div class="page-title">☰ Lịch sử đơn hàng</div>
    <?php if(isset($_GET['tao_ok'])): ?>
      <?php $ptt = $_GET['ptt'] ?? 'tien_mat'; ?>
      <?php if($ptt === 'tien_mat'): ?>
        <div class="alert alert-ok">✅ Tạo đơn thành công! Mã đơn: <b><?=htmlspecialchars($_GET['ma']??'')?></b> — Trạng thái thanh toán: <b>Chưa thanh toán (Tiền mặt/COD)</b></div>
      <?php else: ?>
        <div class="alert" style="background:#fff3e0;color:#e65100;border:1px solid #ffcc80">
          ✅ Tạo đơn thành công! Mã đơn: <b><?=htmlspecialchars($_GET['ma']??'')?></b><br>
          ⏳ Trạng thái thanh toán: <b>Chờ thanh toán</b> — Vui lòng hoàn tất thanh toán để đơn được xử lý.
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <div class="stat-row">
      <div class="stat-box c1 <?=$filter_tt==='tat-ca'?'on':''?>" onclick="location='?page=lich-su'">
        <div class="sv"><?=$tong?></div><div class="sl">Tổng đơn</div></div>
      <div class="stat-box c2 <?=$filter_tt==='cho-xu-ly'?'on':''?>" onclick="location='?page=lich-su&tt=cho-xu-ly'">
        <div class="sv"><?=$cho?></div><div class="sl">Chờ duyệt</div></div>
      <div class="stat-box <?=$filter_tt==='dang-van'?'on':''?>" style="border-top:3px solid #3498db" onclick="location='?page=lich-su&tt=dang-van'">
        <div class="sv"><?=$dang?></div><div class="sl">Đang vận chuyển</div></div>
      <div class="stat-box c3 <?=$filter_tt==='da-giao'?'on':''?>" onclick="location='?page=lich-su&tt=da-giao'">
        <div class="sv"><?=$xong?></div><div class="sl">Hoàn thành</div></div>
      <div class="stat-box c4 <?=$filter_tt==='da-huy'?'on':''?>" onclick="location='?page=lich-su&tt=da-huy'">
        <div class="sv"><?=$huy?></div><div class="sl">Đã hủy</div></div>
    </div>
    <div class="card">
      <div class="card-bd" style="padding:12px 16px">
        <div class="fbar">
          <select class="field" style="max-width:200px" onchange="location='?page=lich-su&tt='+this.value">
            <option value="tat-ca" <?=$filter_tt==='tat-ca'?'selected':''?>>Tất cả trạng thái</option>
            <option value="cho-xu-ly" <?=$filter_tt==='cho-xu-ly'?'selected':''?>>Chờ duyệt</option>
            <option value="dang-van" <?=$filter_tt==='dang-van'?'selected':''?>>Đang vận chuyển</option>
            <option value="da-giao" <?=$filter_tt==='da-giao'?'selected':''?>>Hoàn thành</option>
            <option value="da-huy" <?=$filter_tt==='da-huy'?'selected':''?>>Đã hủy</option>
          </select>
        </div>
        <?php if(empty($don_list)): ?>
          <div class="empty"><div class="ei">📦</div><p>Chưa có đơn hàng nào.</p></div>
        <?php else: ?>
        <div style="max-height:480px;overflow-y:auto">
        <table class="tbl" style="margin-bottom:0">
          <thead style="position:sticky;top:0;z-index:1;background:#f5eeff"><tr><th>Mã đơn</th><th>Hàng hóa</th><th>Ngày tạo</th><th>Cước phí</th><th>Thanh toán</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
          <?php foreach($don_list as $d): ?>
            <tr onclick="moChiTiet(<?=htmlspecialchars(json_encode($d,JSON_UNESCAPED_UNICODE))?>)">
              <td style="font-family:monospace;font-size:12px;color:#6c3483"><?=htmlspecialchars($d['ma_don']??'')?></td>
              <td><div style="font-weight:600"><?=htmlspecialchars($d['ten_hang']??'')?></div>
                  <div style="font-size:11px;color:#7f8c8d"><?=htmlspecialchars($d['dia_chi_gui']??'')?> → <?=htmlspecialchars($d['dia_chi_nhan']??'')?></div></td>
              <td style="font-size:12px;color:#7f8c8d"><?=date('d/m/Y H:i',strtotime($d['ngay_tao']??'now'))?></td>
              <td style="font-weight:700;color:#6c3483"><?=fmtVND($d['doanh_thu']??0)?></td>
              <td><span class="bdg <?=mauPS($d['payment_status']??'')?>"><?=tenPS($d['payment_status']??'')?></span></td>
              <td><span class="bdg <?=mauTT($d['trang_thai']??'')?>"><?=tenTT($d['trang_thai']??'')?></span></td>
              <td onclick="event.stopPropagation()">
                <?php if($d['trang_thai']==='waiting_payment'): ?>
                <a href="?page=thanh-toan-qr&ma=<?=urlencode($d['ma_don']??'')?>"
                   style="display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,#6c3483,#9b59b6);color:#fff;border-radius:5px;padding:4px 10px;font-size:11px;font-weight:700;white-space:nowrap;text-decoration:none">
                  💳 Thanh toán
                </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <form method="post" id="frmHD" style="display:none">
      <input type="hidden" name="action" id="hdAction">
      <input type="hidden" name="ma_don" id="hdMaDon">
    </form>
    <div class="mbg" id="modalSua">
      <div class="modal" style="max-width:580px">
        <h3>✏️ Chỉnh sửa đơn hàng</h3>
        <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:6px;padding:8px 12px;font-size:12px;color:#b7770d;margin-bottom:14px">
          ⏱️ Chỉ được chỉnh sửa trong vòng <b>24 giờ</b> kể từ khi tạo đơn và khi đơn đang ở trạng thái <b>Chờ duyệt</b>.
        </div>
        <form method="post">
          <input type="hidden" name="action" value="sua_don">
          <input type="hidden" name="ma_don" id="suaMaDon">
          <div class="frow">
            <div class="fg"><label>Người gửi</label><input type="text" name="ten_gui" id="suaTenGui" class="field"></div>
            <div class="fg"><label>Người nhận</label><input type="text" name="ten_nhan" id="suaTenNhan" class="field"></div>
          </div>
          <div class="frow">
            <div class="fg"><label>SĐT người gửi</label><input type="tel" name="sdt_gui" id="suaSdtGui" class="field"></div>
            <div class="fg"><label>SĐT người nhận</label><input type="tel" name="sdt_nhan" id="suaSdtNhan" class="field"></div>
          </div>
          <div class="frow">
            <div class="fg"><label>Email người gửi</label><input type="email" name="email_gui" id="suaEmailGui" class="field"></div>
            <div class="fg"><label>Email người nhận</label><input type="email" name="email_nhan" id="suaEmailNhan" class="field"></div>
          </div>
          <div class="frow">
            <div class="fg"><label>Địa chỉ lấy hàng</label><input type="text" name="dia_chi_gui" id="suaDCGui" class="field"></div>
            <div class="fg"><label>Địa chỉ giao hàng</label><input type="text" name="dia_chi_nhan" id="suaDCNhan" class="field"></div>
          </div>
          <div class="frow">
            <div class="fg"><label>Tên hàng hóa</label><input type="text" name="ten_hang" id="suaTenHang" class="field"></div>
            <div class="fg"><label>Khối lượng (kg)</label><input type="number" name="khoi_luong" id="suaKL" class="field" min="0" step="0.1"></div>
          </div>
          <div class="frow">
            <div class="fg"><label>Loại hàng</label>
              <select name="loai_hang" id="suaLoaiHang" class="field">
                <option value="thuong">Hàng thường</option>
                <option value="de_vo">Hàng dễ vỡ</option>
                <option value="lanh">Hàng lạnh/đông lạnh</option>
                <option value="qua_kho">Hàng quá khổ</option>
                <option value="nguy_hiem">Hàng nguy hiểm</option>
              </select></div>
            <div class="fg"><label>Phương thức vận chuyển</label>
              <select name="phuong_thuc" id="suaPT" class="field">
                <option value="duong_bo">Đường bộ (xe tải)</option>
                <option value="duong_bien">Đường biển</option>
                <option value="hang_khong">Hàng không</option>
                <option value="duong_sat">Đường sắt</option>
              </select></div>
          </div>
          <div class="frow">
            <div class="fg"><label>Phương thức thanh toán</label>
              <select name="thanh_toan" id="suaTT" class="field">
                <option value="tien_mat">Tiền mặt</option>
                <option value="chuyen_khoan">Chuyển khoản</option>
                  <option value="vi_dien_tu">Ví điện tử</option>
              </select></div>
            <div class="fg"><label>Ngày gửi</label><input type="date" name="ngay_gui" id="suaNgayGui" class="field"></div>
          </div>
          <div class="fg" style="margin-bottom:10px"><label>Ghi chú</label><textarea name="ghi_chu" id="suaGhiChu" class="field"></textarea></div>
          <div class="btn-row">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalSua').classList.remove('open')">Hủy</button>
            <button type="submit" class="btn btn-pri">💾 Lưu thay đổi</button>
          </div>
        </form>
      </div>
    </div>
