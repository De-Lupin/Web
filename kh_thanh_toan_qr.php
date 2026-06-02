<?php
// Lấy thông tin đơn hàng
$ma_don = $conn->real_escape_string($_GET['ma'] ?? '');
$don = null;
if($ma_don) {
    $don = $conn->query("SELECT * FROM don_hang WHERE ma_don='$ma_don' AND nguoi_tao_id=$user_id LIMIT 1")->fetch_assoc();
}
if(!$don) {
    echo '<div class="alert alert-loi">❌ Không tìm thấy đơn hàng.</div>';
    return;
}
$so_tien = floatval($don['doanh_thu'] ?? 0);
$thoi_gian_tao = strtotime($don['ngay_tao'] ?? 'now');
$han_thanh_toan = $thoi_gian_tao + 20 * 60; // 20 phút
$con_lai_giay = max(0, $han_thanh_toan - time());
$pt_tt = $don['thanh_toan'] ?? '';
$is_expired = ($con_lai_giay <= 0 || in_array($don['trang_thai'], ['huy','waiting_payment_expired']));
$is_paid = ($don['payment_status'] === 'paid');
// Nếu hết hạn thì tự hủy đơn luôn
if($is_expired && $don['trang_thai'] === 'waiting_payment'){
    $conn->query("UPDATE don_hang SET trang_thai='huy', payment_status='failed' WHERE ma_don='{$ma_don}'");
}
?>
<div class="page-title">💳 Thanh toán đơn hàng</div>

<!-- Thông tin đơn hàng -->
<div style="background:#fff;border:1px solid #e8d5f0;border-radius:10px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:11px;color:#7f8c8d;margin-bottom:3px">Mã đơn hàng</div>
    <div style="font-family:monospace;font-size:16px;font-weight:700;color:#6c3483"><?=htmlspecialchars($don['ma_don'])?></div>
  </div>
  <div>
    <div style="font-size:11px;color:#7f8c8d;margin-bottom:3px">Số tiền cần thanh toán</div>
    <div style="font-size:22px;font-weight:800;color:#e74c3c"><?=number_format($so_tien,0,',','.')?> đ</div>
  </div>
  <div>
    <div style="font-size:11px;color:#7f8c8d;margin-bottom:3px">Phương thức</div>
    <div style="font-size:13px;font-weight:600;color:#2c3e50"><?=$pt_tt==='chuyen_khoan'?'🏦 Chuyển khoản ngân hàng':'📱 Ví điện tử'?></div>
  </div>
  <div>
    <div style="font-size:11px;color:#7f8c8d;margin-bottom:3px">Trạng thái</div>
    <?php if($is_paid): ?>
      <span class="bdg bdg-green">✅ Đã thanh toán</span>
    <?php elseif($is_expired): ?>
      <span class="bdg bdg-red">⛔ Hết hạn / Đã hủy</span>
    <?php else: ?>
      <span class="bdg bdg-warn">⏳ Chờ thanh toán</span>
    <?php endif; ?>
  </div>
</div>

<?php if($is_paid): ?>
<div style="background:#eafaf1;border:1px solid #a9dfbf;border-radius:10px;padding:24px;text-align:center;margin-bottom:16px">
  <div style="font-size:48px;margin-bottom:12px">✅</div>
  <div style="font-size:18px;font-weight:700;color:#1e8449;margin-bottom:6px">Thanh toán thành công!</div>
  <div style="font-size:13px;color:#27ae60">Đơn hàng của bạn đang được xử lý.</div>
  <a href="?page=lich-su" class="btn btn-pri" style="margin-top:16px;display:inline-block">Xem lịch sử đơn hàng</a>
</div>
<?php elseif($is_expired): ?>
<div style="background:#fdf2f2;border:1px solid #f5b7b1;border-radius:10px;padding:24px;text-align:center;margin-bottom:16px">
  <div style="font-size:48px;margin-bottom:12px">⛔</div>
  <div style="font-size:18px;font-weight:700;color:#c0392b;margin-bottom:6px">Đơn hàng đã hết hạn thanh toán</div>
  <div style="font-size:13px;color:#e74c3c;margin-bottom:16px">Đơn hàng đã bị hủy tự động do quá thời gian thanh toán.</div>
  <a href="?page=tao-don" class="btn btn-pri" style="display:inline-block">Tạo đơn mới</a>
</div>
<?php else: ?>

<!-- Đồng hồ đếm ngược -->
<div style="background:linear-gradient(135deg,#fff3e0,#ffe0b2);border:2px solid #ff9800;border-radius:10px;padding:14px 20px;margin-bottom:18px;display:flex;align-items:center;gap:14px">
  <div style="font-size:32px">⏱️</div>
  <div style="flex:1">
    <div style="font-size:12px;font-weight:700;color:#e65100;margin-bottom:4px">THỜI HẠN THANH TOÁN</div>
    <div style="display:flex;align-items:center;gap:8px">
      <div id="countdown-display" style="font-size:28px;font-weight:800;color:#d84315;font-family:monospace;letter-spacing:2px">--:--</div>
      <div style="font-size:12px;color:#bf360c">còn lại để hoàn tất thanh toán</div>
    </div>
  </div>
  <div style="text-align:center">
    <div id="countdown-bar-wrap" style="width:120px;height:8px;background:#ffcc80;border-radius:4px;overflow:hidden">
      <div id="countdown-bar" style="height:100%;background:linear-gradient(90deg,#ff6f00,#ff9800);border-radius:4px;transition:width 1s linear;width:100%"></div>
    </div>
    <div style="font-size:10px;color:#e65100;margin-top:4px">Tự động hủy khi hết giờ</div>
  </div>
</div>

<!-- Hướng dẫn thanh toán -->
<div style="background:#fff;border:1px solid #e8d5f0;border-radius:10px;overflow:hidden;margin-bottom:18px">
  <div style="background:linear-gradient(135deg,#6c3483,#9b59b6);padding:14px 20px">
    <div style="font-size:14px;font-weight:700;color:#fff">💡 Vui lòng thanh toán bằng 1 trong 2 cách sau</div>
  </div>
  <div style="padding:20px">

    <!-- CÁCH 1: QR CODE -->
    <div style="border:2px solid #e8d5f0;border-radius:10px;padding:18px;margin-bottom:18px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6c3483,#9b59b6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;flex-shrink:0">1</div>
        <div>
          <div style="font-size:14px;font-weight:700;color:#2c3e50">Thanh toán bằng mã QR theo hướng dẫn</div>
          <div style="font-size:11px;color:#7f8c8d">Quét mã QR bằng ứng dụng ngân hàng hoặc ví điện tử</div>
        </div>
      </div>

      <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">
        <!-- QR Image -->
        <div style="text-align:center;flex-shrink:0">
          <div style="background:#fff;border:3px solid #9b59b6;border-radius:12px;padding:12px;display:inline-block;box-shadow:0 4px 16px rgba(108,52,131,.15)">
            <img id="qr-vietqr"
                 src="https://img.vietqr.io/image/970423-10000580855-compact.png?amount=<?= intval($so_tien) ?>&addInfo=<?= urlencode($don['ma_don']) ?>&accountName=<?= urlencode('NGUYEN NGO CONG VINH') ?>"
                 alt="Mã QR thanh toán TPBank"
                 style="width:200px;height:200px;display:block;border-radius:6px"
                 onerror="this.src='qr_thanhtoan.jpg'">
          </div>
          <div style="margin-top:8px">
            <div style="font-size:11px;color:#7f8c8d">TPBank — VietQR</div>
            <div style="font-size:12px;font-weight:700;color:#6c3483">NGUYEN NGO CONG VINH</div>
            <div style="font-size:12px;color:#2c3e50">1000 0580 855</div>
          </div>
        </div>
        <!-- Hướng dẫn quét -->
        <div style="flex:1;min-width:200px">
          <div style="font-size:12px;font-weight:700;color:#6c3483;margin-bottom:10px">Các bước thực hiện:</div>
          <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">
            <div style="display:flex;align-items:flex-start;gap:8px;font-size:12px;color:#2c3e50">
              <span style="background:#e8d5f0;color:#6c3483;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;margin-top:1px">1</span>
              Mở ứng dụng ngân hàng hoặc ví điện tử trên điện thoại
            </div>
            <div style="display:flex;align-items:flex-start;gap:8px;font-size:12px;color:#2c3e50">
              <span style="background:#e8d5f0;color:#6c3483;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;margin-top:1px">2</span>
              Chọn chức năng <b>Quét QR / Scan QR</b>
            </div>
            <div style="display:flex;align-items:flex-start;gap:8px;font-size:12px;color:#2c3e50">
              <span style="background:#e8d5f0;color:#6c3483;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;margin-top:1px">3</span>
              Hướng camera vào mã QR bên cạnh để quét
            </div>
            <div style="display:flex;align-items:flex-start;gap:8px;font-size:12px;color:#2c3e50">
              <span style="background:#e8d5f0;color:#6c3483;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;margin-top:1px">4</span>
              Nhập số tiền <b style="color:#e74c3c"><?=number_format($so_tien,0,',','.')?> đ</b> và xác nhận chuyển khoản
            </div>
          </div>
          <!-- Nút xem ứng dụng hỗ trợ -->
          <button onclick="toggleDanhSachNH()" style="background:linear-gradient(135deg,#6c3483,#9b59b6);color:#fff;border:none;border-radius:6px;padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px">
            🏦 <span id="btn-nh-text">Xem ứng dụng hỗ trợ quét QR</span> <span id="btn-nh-arrow">▾</span>
          </button>
        </div>
      </div>

      <!-- Danh sách ngân hàng hỗ trợ -->
      <div id="danh-sach-nh" style="display:none;margin-top:16px;border-top:1px solid #f0e6f6;padding-top:16px">
        <div style="font-size:12px;font-weight:700;color:#6c3483;margin-bottom:12px">📋 Ngân hàng & ví điện tử hỗ trợ thanh toán QR:</div>
        <?php if($pt_tt === 'vi_dien_tu'): ?>
        <div style="font-size:12px;font-weight:700;color:#ae2070;margin-bottom:8px">📱 Ví điện tử hỗ trợ quét QR:</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px" id="bank-grid">
          <!-- Ví điện tử - sinh bởi JS -->
        </div>
        <?php else: ?>
        <div style="font-size:12px;font-weight:700;color:#6c3483;margin-bottom:8px">🏦 Ngân hàng hỗ trợ quét QR:</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px" id="bank-grid">
          <!-- Ngân hàng - sinh bởi JS -->
        </div>
        <?php endif; ?>
        <div style="margin-top:12px;background:#f9f4ff;border-radius:6px;padding:10px 12px;font-size:11px;color:#7f8c8d">
          ℹ️ Hỗ trợ tiêu chuẩn <b>VietQR</b> — tương thích với hầu hết ứng dụng ngân hàng tại Việt Nam
        </div>
      </div>
    </div>

    <!-- CÁCH 2: Ứng dụng trực tiếp -->
    <div style="border:2px solid #e8d5f0;border-radius:10px;padding:18px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6c3483,#9b59b6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;flex-shrink:0">2</div>
        <div>
          <div style="font-size:14px;font-weight:700;color:#2c3e50">Thanh toán trực tiếp trên ứng dụng hỗ trợ theo hướng dẫn</div>
          <div style="font-size:11px;color:#7f8c8d">Chọn ứng dụng ngân hàng để mở trực tiếp và thanh toán</div>
        </div>
        <button onclick="toggleHDApp()" style="margin-left:auto;background:#f0e6f6;color:#6c3483;border:1px solid #d5a8e8;border-radius:6px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;flex-shrink:0">
          📖 Hướng dẫn
        </button>
      </div>

      <!-- Hướng dẫn 3 bước (ẩn mặc định) -->
      <div id="hd-app" style="display:none;background:#f9f4ff;border-radius:8px;padding:16px;margin-bottom:14px;border:1px solid #e8d5f0">
        <div style="font-size:12px;font-weight:700;color:#6c3483;margin-bottom:12px">Hướng dẫn thanh toán qua ứng dụng:</div>
        <div style="display:flex;flex-direction:column;gap:12px">
          <div style="display:flex;align-items:flex-start;gap:12px">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#6c3483,#9b59b6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0">1</div>
            <div style="padding-top:4px">
              <div style="font-size:13px;font-weight:600;color:#2c3e50">Nhấn vào logo của ứng dụng hỗ trợ thanh toán QR trong danh sách</div>
              <div style="font-size:11px;color:#7f8c8d;margin-top:3px">Chọn ứng dụng ngân hàng hoặc ví điện tử bạn đang dùng từ danh sách bên dưới</div>
            </div>
          </div>
          <div style="display:flex;align-items:flex-start;gap:12px">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#6c3483,#9b59b6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0">2</div>
            <div style="padding-top:4px">
              <div style="font-size:13px;font-weight:600;color:#2c3e50">Đồng ý mở ứng dụng hỗ trợ thanh toán QR theo yêu cầu thông báo trên màn hình</div>
              <div style="font-size:11px;color:#7f8c8d;margin-top:3px">Khi trình duyệt hỏi "Mở ứng dụng?", nhấn <b>Đồng ý / Open</b> để chuyển sang ứng dụng</div>
            </div>
          </div>
          <div style="display:flex;align-items:flex-start;gap:12px">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#6c3483,#9b59b6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0">3</div>
            <div style="padding-top:4px">
              <div style="font-size:13px;font-weight:600;color:#2c3e50">Xác nhận thanh toán và hoàn tất giao dịch</div>
              <div style="font-size:11px;color:#7f8c8d;margin-top:3px">Kiểm tra số tiền <b style="color:#e74c3c"><?=number_format($so_tien,0,',','.')?> đ</b> và xác nhận trong ứng dụng của bạn</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Grid logo để bấm mở app -->
      <?php if($pt_tt === 'vi_dien_tu'): ?>
      <div style="font-size:12px;color:#7f8c8d;margin-bottom:10px">Chọn ví điện tử để mở và thanh toán:</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px" id="app-grid">
        <!-- Ví điện tử - sinh bởi JS -->
      </div>
      <?php else: ?>
      <div style="font-size:12px;color:#7f8c8d;margin-bottom:10px">Chọn ứng dụng ngân hàng để mở và thanh toán:</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px" id="app-grid">
        <!-- Ngân hàng - sinh bởi JS -->
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- Nút hủy đơn -->
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <a href="?page=lich-su" class="btn btn-ghost">← Về lịch sử đơn</a>
  <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?')">
    <input type="hidden" name="action" value="huy_don">
    <input type="hidden" name="ma_don" value="<?=htmlspecialchars($ma_don)?>">
    <button type="submit" class="btn btn-danger">🚫 Hủy đơn hàng</button>
  </form>
</div>

<!-- ===== MODAL NHẬP THÔNG TIN CHUYỂN KHOẢN (THÊM MỚI) ===== -->
<div id="modal-chuyen-khoan" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:14px;padding:24px 24px 20px;width:min(420px,94vw);box-shadow:0 8px 40px rgba(0,0,0,.25);position:relative">

    <!-- Tiêu đề -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
      <div id="modal-app-icon" style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;background:#6c3483;color:#fff"></div>
      <div>
        <div style="font-size:15px;font-weight:700;color:#2c3e50" id="modal-app-name">Nhập thông tin chuyển khoản</div>
        <div style="font-size:11px;color:#7f8c8d">Kiểm tra kỹ trước khi xác nhận</div>
      </div>
      <button onclick="dongModal()" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#95a5a6;line-height:1" title="Đóng">✕</button>
    </div>

    <!-- 4 trường thông tin -->
    <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:20px">

      <!-- Số tài khoản -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#7f8c8d;display:block;margin-bottom:5px">SỐ TÀI KHOẢN NHẬN</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input id="modal-stk" type="text" value="10000580855" readonly
            style="flex:1;border:1.5px solid #d5a8e8;border-radius:8px;padding:9px 12px;font-size:14px;font-weight:700;color:#6c3483;background:#f9f4ff;font-family:monospace;letter-spacing:1px">
          <button onclick="saoCHep('modal-stk','btn-copy-stk')" id="btn-copy-stk"
            style="background:#6c3483;color:#fff;border:none;border-radius:8px;padding:9px 13px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap">📋 Sao chép</button>
        </div>
        <div style="font-size:11px;color:#9b59b6;margin-top:4px">TPBank — NGUYEN NGO CONG VINH</div>
      </div>

      <!-- Số tiền -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#7f8c8d;display:block;margin-bottom:5px">SỐ TIỀN CẦN CHUYỂN</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input id="modal-sotien" type="text" value="<?= number_format($so_tien,0,',','.') ?> đ" readonly
            style="flex:1;border:1.5px solid #f5b7b1;border-radius:8px;padding:9px 12px;font-size:16px;font-weight:800;color:#e74c3c;background:#fdf2f2;font-family:monospace">
          <button onclick="saoCHep('modal-sotien','btn-copy-tien')" id="btn-copy-tien"
            style="background:#e74c3c;color:#fff;border:none;border-radius:8px;padding:9px 13px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap">📋 Sao chép</button>
        </div>
      </div>

      <!-- Mã đơn hàng (nội dung CK) -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#7f8c8d;display:block;margin-bottom:5px">NỘI DUNG CHUYỂN KHOẢN (MÃ ĐƠN)</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input id="modal-madon" type="text" value="<?= htmlspecialchars($don['ma_don']) ?>" readonly
            style="flex:1;border:1.5px solid #a9dfbf;border-radius:8px;padding:9px 12px;font-size:14px;font-weight:700;color:#1e8449;background:#eafaf1;font-family:monospace;letter-spacing:.5px">
          <button onclick="saoCHep('modal-madon','btn-copy-madon')" id="btn-copy-madon"
            style="background:#27ae60;color:#fff;border:none;border-radius:8px;padding:9px 13px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap">📋 Sao chép</button>
        </div>
      </div>

      <!-- OTP -->
      <div>
        <label style="font-size:11px;font-weight:700;color:#7f8c8d;display:block;margin-bottom:5px">MÃ OTP CHUYỂN TIỀN</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input id="modal-otp" type="text" placeholder="Nhập mã OTP từ ứng dụng / SMS"
            maxlength="8"
            style="flex:1;border:1.5px solid #fad7a0;border-radius:8px;padding:9px 12px;font-size:15px;font-weight:700;color:#d35400;background:#fef9ef;font-family:monospace;letter-spacing:2px"
            oninput="this.value=this.value.replace(/\D/g,'')">
        </div>
        <div style="font-size:11px;color:#e67e22;margin-top:4px">OTP do ứng dụng ngân hàng gửi, nhập để xác nhận trước khi mở app</div>
      </div>

    </div>

    <!-- Nút xác nhận -->
    <div style="display:flex;gap:10px">
      <button onclick="dongModal()"
        style="flex:1;background:#f0f0f0;color:#7f8c8d;border:none;border-radius:8px;padding:11px;font-size:13px;font-weight:600;cursor:pointer">
        Hủy
      </button>
      <button id="btn-xac-nhan-ck" onclick="xacNhanMoApp()"
        style="flex:2;background:linear-gradient(135deg,#6c3483,#9b59b6);color:#fff;border:none;border-radius:8px;padding:11px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px">
        ✅ Xác nhận &amp; Mở ứng dụng
      </button>
    </div>

    <div style="font-size:10px;color:#bdc3c7;text-align:center;margin-top:12px">
      ⚠️ OTP chỉ dùng để xác thực danh tính — không chia sẻ với bất kỳ ai
    </div>
  </div>
</div>

<?php endif; ?>

<!-- JS xử lý đếm ngược và danh sách ngân hàng -->
<script>
(function(){
  // ===== PHƯƠNG THỨC THANH TOÁN =====
  const PT_TT = '<?= $pt_tt ?>'; // 'vi_dien_tu' hoặc 'chuyen_khoan'

  // ===== THÔNG TIN THANH TOÁN =====
  // Tài khoản nhận: TPBank - 10000580855 - NGUYEN NGO CONG VINH
  // BIN TPBank theo chuẩn VietQR: 970423
  const BANK_BIN   = '970423';        // BIN TPBank (VietQR)
  const ACC_NO     = '10000580855';   // Số tài khoản
  const ACC_NAME   = 'NGUYEN NGO CONG VINH';
  const SO_TIEN    = <?= intval($so_tien) ?>;          // Số tiền (đồng)
  const MA_DON     = '<?= addslashes($don['ma_don']) ?>';  // Mã đơn hàng

  // Tạo URL VietQR động (tự điền số tiền + nội dung chuyển khoản)
  const vietQRUrl = `https://img.vietqr.io/image/${BANK_BIN}-${ACC_NO}-compact.png`
    + `?amount=${SO_TIEN}&addInfo=${encodeURIComponent(MA_DON)}&accountName=${encodeURIComponent(ACC_NAME)}`;

  // Tạo deep link cho từng ngân hàng/ví theo chuẩn VietQR
  // Deep link dạng: <scheme>://qr?bank=BIN&acc=STK&amount=SOTIEN&info=NOIDUNG
  function buildDeepLink(b) {
    const params = `bank=${BANK_BIN}&acc=${ACC_NO}&amount=${SO_TIEN}&info=${encodeURIComponent(MA_DON)}&name=${encodeURIComponent(ACC_NAME)}`;
    if (b.viWallet) {
      // Ví điện tử dùng cấu trúc khác
      return b.scheme + `transfer?amount=${SO_TIEN}&description=${encodeURIComponent(MA_DON)}`;
    }
    return b.scheme + `?${params}`;
  }

  // ===== DỮ LIỆU NGÂN HÀNG =====
  const banksNH = [
    { name:'TPBank',       color:'#6c2bbd', emoji:'🏦', scheme:'tpbank://qr',       viWallet:false },
    { name:'Vietcombank',  color:'#006437', emoji:'🏦', scheme:'vcb://qr',          viWallet:false },
    { name:'VietinBank',   color:'#c00000', emoji:'🏦', scheme:'vietinbank://qr',   viWallet:false },
    { name:'BIDV',         color:'#005baa', emoji:'🏦', scheme:'bidv://qr',         viWallet:false },
    { name:'Agribank',     color:'#d4000d', emoji:'🏦', scheme:'agribank://qr',     viWallet:false },
    { name:'MBBank',       color:'#003087', emoji:'🏦', scheme:'mbmobile://qr',     viWallet:false },
    { name:'Techcombank',  color:'#d0021b', emoji:'🏦', scheme:'techcombank://qr',  viWallet:false },
    { name:'ACB',          color:'#0070b8', emoji:'🏦', scheme:'acb://qr',          viWallet:false },
    { name:'VPBank',       color:'#00a651', emoji:'🏦', scheme:'vpbank://qr',       viWallet:false },
    { name:'Sacombank',    color:'#0057a8', emoji:'🏦', scheme:'sacombank://qr',    viWallet:false },
    { name:'HDBank',       color:'#003087', emoji:'🏦', scheme:'hdbank://qr',       viWallet:false },
    { name:'SHB',          color:'#c8252d', emoji:'🏦', scheme:'shb://qr',          viWallet:false },
    { name:'VIB',          color:'#002776', emoji:'🏦', scheme:'vib://qr',          viWallet:false },
    { name:'SeABank',      color:'#e31e24', emoji:'🏦', scheme:'seabank://qr',      viWallet:false },
    { name:'OCB',          color:'#004b87', emoji:'🏦', scheme:'ocb://qr',          viWallet:false },
    { name:'LienVietPost', color:'#d4281e', emoji:'🏦', scheme:'lienviet://qr',     viWallet:false },
  ];
  const banksVi = [
    { name:'MoMo',         color:'#ae2070', emoji:'💜', scheme:'momo://',           viWallet:true  },
    { name:'ZaloPay',      color:'#0068ff', emoji:'💙', scheme:'zalopay://',        viWallet:true  },
    { name:'VNPay',        color:'#e2001a', emoji:'❤️', scheme:'vnpay://',          viWallet:true  },
    { name:'ShopeePay',    color:'#ee4d2d', emoji:'🧡', scheme:'airpay://',         viWallet:true  },
  ];
  // Chọn danh sách hiển thị theo phương thức thanh toán của đơn
  const banks = PT_TT === 'vi_dien_tu' ? banksVi : banksNH;

  // ===== ĐỒNG HỒ ĐẾM NGƯỢC =====
  const deadlineMs = Date.now() + <?= $con_lai_giay * 1000 ?>; // Tính từ thời gian còn lại server trả về
  const totalMs = 20 * 60 * 1000;

  function updateCountdown(){
    const now = Date.now();
    const remaining = Math.max(0, deadlineMs - now);
    const mins = Math.floor(remaining / 60000);
    const secs = Math.floor((remaining % 60000) / 1000);

    const disp = document.getElementById('countdown-display');
    const bar  = document.getElementById('countdown-bar');
    if(!disp) return;

    if(remaining <= 0){
      disp.textContent = '00:00';
      disp.style.color = '#c0392b';
      if(bar) bar.style.width = '0%';
      clearInterval(timerInterval);
      // Tự động reload để hiện thông báo hết hạn
      setTimeout(() => location.reload(), 1500);
      return;
    }

    disp.textContent = String(mins).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
    const pct = (remaining / totalMs) * 100;
    if(bar) bar.style.width = pct + '%';

    if(remaining < 60000) {
      disp.style.color = '#c0392b';
      if(bar) bar.style.background = '#e74c3c';
    } else if(remaining < 180000) {
      disp.style.color = '#e67e22';
      if(bar) bar.style.background = 'linear-gradient(90deg,#e67e22,#f39c12)';
    }
  }

  let timerInterval;
  if(document.getElementById('countdown-display')){
    updateCountdown();
    timerInterval = setInterval(updateCountdown, 1000);
  }

  // ===== DANH SÁCH NGÂN HÀNG (hiển thị logo dạng card) =====
  function renderBankGrid(containerId, clickable){
    const grid = document.getElementById(containerId);
    if(!grid) return;
    banks.forEach(b => {
      const card = document.createElement('div');
      card.style.cssText = `
        background:#fff;border:1.5px solid #e8d5f0;border-radius:8px;
        padding:10px 8px;text-align:center;cursor:${clickable?'pointer':'default'};
        transition:.15s;font-size:11px;font-weight:600;color:#2c3e50;
      `;
      card.innerHTML = `
        <div style="width:36px;height:36px;border-radius:50%;background:${b.color};
             color:#fff;display:flex;align-items:center;justify-content:center;
             font-size:16px;margin:0 auto 6px">${b.emoji}</div>
        <div>${b.name}</div>
      `;
      if(clickable){
        card.onmouseenter = () => { card.style.borderColor='#9b59b6'; card.style.background='#f9f4ff'; };
        card.onmouseleave = () => { card.style.borderColor='#e8d5f0'; card.style.background='#fff'; };
        card.onclick = () => {
          // Mở modal nhập thông tin trước khi nhảy app
          moModal(b);
        };
      }
      grid.appendChild(card);
    });
  }

  renderBankGrid('bank-grid', false);
  renderBankGrid('app-grid', true);

  // ===== MODAL NHẬP THÔNG TIN CHUYỂN KHOẢN (THÊM MỚI) =====
  let _bankDangChon = null;

  window.moModal = function(b) {
    _bankDangChon = b;
    const modal = document.getElementById('modal-chuyen-khoan');
    document.getElementById('modal-app-name').textContent = 'Thanh toán qua ' + b.name;
    const icon = document.getElementById('modal-app-icon');
    icon.style.background = b.color;
    icon.textContent = b.emoji;
    document.getElementById('modal-otp').value = '';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  window.dongModal = function() {
    document.getElementById('modal-chuyen-khoan').style.display = 'none';
    document.body.style.overflow = '';
    _bankDangChon = null;
  };

  // Đóng modal khi bấm ra ngoài
  document.getElementById('modal-chuyen-khoan').addEventListener('click', function(e){
    if(e.target === this) window.dongModal();
  });

  window.saoCHep = function(inputId, btnId) {
    const el = document.getElementById(inputId);
    // Lấy giá trị thực (bỏ " đ" nếu có)
    const val = el.value.replace(/[^\d]/g,'') || el.value;
    navigator.clipboard.writeText(el.value).catch(() => {
      // Fallback cho trình duyệt cũ
      el.select(); document.execCommand('copy');
    });
    const btn = document.getElementById(btnId);
    const orig = btn.textContent;
    btn.textContent = '✅ Đã sao chép';
    btn.style.opacity = '.7';
    setTimeout(() => { btn.textContent = orig; btn.style.opacity = '1'; }, 1800);
  };

  window.xacNhanMoApp = function() {
    const otp = document.getElementById('modal-otp').value.trim();
    if(!otp) {
      document.getElementById('modal-otp').focus();
      document.getElementById('modal-otp').style.border = '2px solid #e74c3c';
      setTimeout(() => { document.getElementById('modal-otp').style.border = '1.5px solid #fad7a0'; }, 1500);
      return;
    }
    if(!_bankDangChon) return;
    window.dongModal();
    const deepLink = buildDeepLink(_bankDangChon);
    window.location.href = deepLink;
    setTimeout(() => {
      try { window.open(vietQRUrl, '_blank'); } catch(e){}
    }, 2000);
  };

  // ===== TOGGLE DANH SÁCH NH =====
  window.toggleDanhSachNH = function(){
    const el = document.getElementById('danh-sach-nh');
    const txt = document.getElementById('btn-nh-text');
    const arr = document.getElementById('btn-nh-arrow');
    const open = el.style.display === 'none';
    el.style.display = open ? 'block' : 'none';
    txt.textContent = open ? 'Ẩn danh sách' : 'Xem ứng dụng hỗ trợ quét QR';
    arr.textContent = open ? '▴' : '▾';
  };

  // ===== TOGGLE HƯỚNG DẪN APP =====
  window.toggleHDApp = function(){
    const el = document.getElementById('hd-app');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
  };

})();
</script>
