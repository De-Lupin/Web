    <div class="welcome">
      <div>
        <h2>Xin chào, <?=htmlspecialchars($full_name)?>! 👋</h2>
        <p>Chào mừng đến với cổng khách hàng vận tải hàng hóa.</p>
      </div>
      <div class="big">🚛</div>
    </div>
    <div class="stat-row">
      <div class="stat-box c1" onclick="location='?page=lich-su'">
        <div class="si">📦</div><div class="sv"><?=$tong?></div><div class="sl">Tổng đơn hàng</div></div>
      <div class="stat-box c2" onclick="location='?page=lich-su&tt=cho-xu-ly'">
        <div class="si">⏳</div><div class="sv"><?=$cho?></div><div class="sl">Chờ duyệt</div></div>
      <div class="stat-box" style="border-top:3px solid #3498db" onclick="location='?page=lich-su&tt=dang-van'">
        <div class="si">🚚</div><div class="sv"><?=$dang?></div><div class="sl">Đang vận chuyển</div></div>
      <div class="stat-box c3" onclick="location='?page=lich-su&tt=da-giao'">
        <div class="si">✅</div><div class="sv"><?=$xong?></div><div class="sl">Hoàn thành</div></div>
      <div class="stat-box c4" onclick="location='?page=lich-su&tt=da-huy'">
        <div class="si">❌</div><div class="sv"><?=$huy?></div><div class="sl">Đã hủy</div></div>
    </div>
    <div class="card">
      <div class="card-hd"><span>📋 Đơn hàng gần đây</span>
        <a href="?page=lich-su" style="font-size:11px;color:#9b59b6;font-weight:600">Xem tất cả →</a></div>
      <div class="card-bd" style="padding:0">
        <?php if(empty($don_recent)): ?>
          <div class="empty"><div class="ei">📦</div><p style="margin-bottom:10px">Chưa có đơn hàng nào.</p>
            <a href="?page=tao-don" class="btn btn-pri btn-sm">Tạo đơn ngay</a></div>
        <?php else: ?>
        <div style="max-height:480px;overflow-y:auto">
        <table class="tbl" style="margin-bottom:0">
          <thead style="position:sticky;top:0;z-index:1;background:#f5eeff"><tr><th>Mã đơn</th><th>Hàng hóa</th><th>Ngày tạo</th><th>Cước phí</th><th>Thanh toán</th><th>Trạng thái</th></tr></thead>
          <tbody>
          <?php foreach($don_recent as $d): ?>
            <tr onclick="moChiTiet(<?=htmlspecialchars(json_encode($d,JSON_UNESCAPED_UNICODE))?>)">
              <td style="font-family:monospace;font-size:12px;color:#6c3483"><?=htmlspecialchars($d['ma_don']??'')?></td>
              <td><div style="font-weight:600"><?=htmlspecialchars($d['ten_hang']??'')?></div>
                  <div style="font-size:11px;color:#7f8c8d"><?=htmlspecialchars($d['dia_chi_gui']??'')?> → <?=htmlspecialchars($d['dia_chi_nhan']??'')?></div></td>
              <td style="font-size:12px;color:#7f8c8d"><?=date('d/m/Y',strtotime($d['ngay_tao']??'now'))?></td>
              <td style="font-weight:700;color:#6c3483"><?=fmtVND($d['doanh_thu']??0)?></td>
              <td><span class="bdg <?=mauPS($d['payment_status']??'')?>"><?=tenPS($d['payment_status']??'')?></span></td>
              <td><span class="bdg <?=mauTT($d['trang_thai']??'')?>"><?=tenTT($d['trang_thai']??'')?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
