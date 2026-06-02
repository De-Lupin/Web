    <div class="page-title">⚙️ Tạo đơn hàng mới</div>

    <!-- HƯỚNG DẪN TẠO ĐƠN -->
    <div style="background:#fff;border:1px solid #e8d5f0;border-radius:10px;margin-bottom:18px;overflow:hidden">
      <div style="background:linear-gradient(135deg,#6c3483,#9b59b6);padding:10px 18px;display:flex;align-items:center;justify-content:space-between;cursor:pointer" onclick="toggleHD()">
        <span style="font-size:13px;font-weight:700;color:#fff">📖 Hướng dẫn tạo đơn hàng</span>
        <span id="hd-icon" style="color:#fff;font-size:14px">▾</span>
      </div>
      <div id="hd-body" style="padding:16px 18px">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px">
          <div style="text-align:center;padding:12px 8px;background:#f9f4ff;border-radius:8px;border:1px solid #e8d5f0">
            <div style="font-size:24px;margin-bottom:6px">📝</div>
            <div style="font-size:11px;font-weight:700;color:#6c3483;margin-bottom:3px">BƯỚC 1</div>
            <div style="font-size:12px;color:#2c3e50;font-weight:600">Nhập thông tin</div>
            <div style="font-size:11px;color:#7f8c8d;margin-top:3px">Người gửi, người nhận, hàng hóa, địa chỉ</div>
          </div>
          <div style="text-align:center;padding:12px 8px;background:#f9f4ff;border-radius:8px;border:1px solid #e8d5f0">
            <div style="font-size:24px;margin-bottom:6px">💰</div>
            <div style="font-size:11px;font-weight:700;color:#6c3483;margin-bottom:3px">BƯỚC 2</div>
            <div style="font-size:12px;color:#2c3e50;font-weight:600">Chọn thanh toán</div>
            <div style="font-size:11px;color:#7f8c8d;margin-top:3px">Tiền mặt, chuyển khoản hoặc ví điện tử</div>
          </div>
          <div style="text-align:center;padding:12px 8px;background:#f9f4ff;border-radius:8px;border:1px solid #e8d5f0">
            <div style="font-size:24px;margin-bottom:6px">✅</div>
            <div style="font-size:11px;font-weight:700;color:#6c3483;margin-bottom:3px">BƯỚC 3</div>
            <div style="font-size:12px;color:#2c3e50;font-weight:600">Xác nhận đơn</div>
            <div style="font-size:11px;color:#7f8c8d;margin-top:3px">Kiểm tra lại và gửi đơn hàng</div>
          </div>
          <div style="text-align:center;padding:12px 8px;background:#f9f4ff;border-radius:8px;border:1px solid #e8d5f0">
            <div style="font-size:24px;margin-bottom:6px">🚛</div>
            <div style="font-size:11px;font-weight:700;color:#6c3483;margin-bottom:3px">BƯỚC 4</div>
            <div style="font-size:12px;color:#2c3e50;font-weight:600">Theo dõi đơn</div>
            <div style="font-size:11px;color:#7f8c8d;margin-top:3px">Xem trạng thái trong mục Lịch sử</div>
          </div>
        </div>
        <div style="background:#fdf8ff;border-radius:8px;padding:12px 16px;border-left:3px solid #9b59b6">
          <div style="font-size:12px;font-weight:700;color:#6c3483;margin-bottom:6px">💡 Lưu ý quan trọng:</div>
          <div style="font-size:12px;color:#5a5a7a;line-height:1.8">
            • Các trường có dấu <span style="color:#e74c3c;font-weight:700">(*)</span> là bắt buộc phải điền<br>
            • Cước phí được tính tự động dựa trên <b>khối lượng</b> và <b>phương thức vận chuyển</b><br>
            • Đơn hàng <b>tiền mặt (COD)</b> sẽ được tạo ngay, thanh toán khi nhận hàng<br>
            • Đơn hàng <b>chuyển khoản/ví điện tử</b> cần hoàn tất thanh toán để được xử lý<br>
            • Bạn có thể <b>chỉnh sửa hoặc hủy</b> đơn trong vòng <b>24 giờ</b> kể từ khi tạo
          </div>
        </div>
      </div>
    </div>

    <!-- Step indicator -->
    <div style="display:flex;align-items:center;gap:0;margin-bottom:20px">
      <div id="step1-ind" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:linear-gradient(135deg,#6c3483,#9b59b6);color:#fff;border-radius:6px 0 0 6px;font-size:13px;font-weight:700">
        <span style="width:22px;height:22px;border-radius:50%;background:rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:11px">1</span> Thông tin đơn hàng
      </div>
      <div style="width:0;height:0;border-top:20px solid transparent;border-bottom:20px solid transparent;border-left:12px solid #9b59b6"></div>
      <div id="step2-ind" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:#e8d5f0;color:#7f8c8d;border-radius:0 6px 6px 0;font-size:13px;font-weight:700;margin-left:-1px">
        <span style="width:22px;height:22px;border-radius:50%;background:rgba(0,0,0,.1);display:flex;align-items:center;justify-content:center;font-size:11px">2</span> Phương thức thanh toán
      </div>
    </div>

    <form method="post" id="frmDon">
    <input type="hidden" name="action" value="tao_don">
    <input type="hidden" name="cuoc_phi_tt" id="cuoc_phi_tt" value="0">
    <div id="step1-area">
<p style="font-size:11px;color:#e74c3c;margin-bottom:10px">(*) Thông tin bắt buộc</p>
    <div class="card">
      <div class="card-hd">Chi tiết đơn hàng</div>
      <div class="card-bd">
        <div class="frow">
          <div class="fg"><label>Người gửi <span class="req">*</span></label>
            <input type="text" name="ten_gui" class="field" required></div>
          <div class="fg"><label>Người nhận <span class="req">*</span></label>
            <input type="text" name="ten_nhan" class="field" required></div>
        </div>
        <div class="frow">
          <div class="fg"><label>SĐT người gửi <span class="req">*</span></label>
            <input type="tel" name="sdt_gui" class="field" required></div>
          <div class="fg"><label>SĐT người nhận <span class="req">*</span></label>
            <input type="tel" name="sdt_nhan" class="field" required></div>
        </div>
        <div class="frow">
          <div class="fg"><label>Email người gửi</label>
            <input type="email" name="email_gui" class="field" placeholder="email@example.com"></div>
          <div class="fg"><label>Email người nhận</label>
            <input type="email" name="email_nhan" class="field" placeholder="email@example.com"></div>
        </div>
        <!-- Địa chỉ lấy hàng -->
        <div class="frow" style="margin-bottom:6px">
          <div class="fg">
            <label>Tỉnh/Thành phố lấy hàng <span class="req">*</span></label>
            <div class="dc-wrap" id="wrap-gui">
              <div class="dc-input" onclick="toggleDC('gui')">
                <span id="disp-gui">Chọn Tỉnh/Thành phố</span>
                <span style="color:#a569bd">▾</span>
              </div>
              <div class="dc-drop" id="drop-gui">
                <div class="dc-search-wrap">
                  <input type="text" class="dc-search" placeholder="🔍 Tìm kiếm Tỉnh/Thành phố..." oninput="filterTinh('gui',this.value)">
                </div>
                <div class="dc-list" id="list-tinh-gui"></div>
              </div>
            </div>
            <input type="hidden" name="tinh_gui" id="tinh-gui">
          </div>
          <div class="fg">
            <label>Tỉnh/Thành phố giao hàng <span class="req">*</span></label>
            <div class="dc-wrap" id="wrap-nhan">
              <div class="dc-input" onclick="toggleDC('nhan')">
                <span id="disp-nhan">Chọn Tỉnh/Thành phố</span>
                <span style="color:#a569bd">▾</span>
              </div>
              <div class="dc-drop" id="drop-nhan">
                <div class="dc-search-wrap">
                  <input type="text" class="dc-search" placeholder="🔍 Tìm kiếm Tỉnh/Thành phố..." oninput="filterTinh('nhan',this.value)">
                </div>
                <div class="dc-list" id="list-tinh-nhan"></div>
              </div>
            </div>
            <input type="hidden" name="tinh_nhan" id="tinh-nhan">
          </div>
        </div>
        <div class="frow">
          <div class="fg"><label>Địa chỉ cụ thể (lấy hàng) <span class="req">*</span></label>
            <input type="text" name="dia_chi_gui" id="dc-gui" class="field" placeholder="Số nhà, tên đường, phường/xã..." required></div>
          <div class="fg"><label>Địa chỉ cụ thể (giao hàng) <span class="req">*</span></label>
            <input type="text" name="dia_chi_nhan" id="dc-nhan" class="field" placeholder="Số nhà, tên đường, phường/xã..." required></div>
        </div>

        <style>
        .dc-wrap{position:relative}
        .dc-input{background:#f9f4ff;border:1.5px solid #e0c8f0;border-radius:6px;padding:8px 10px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-size:13.5px;color:#2c3e50;transition:.15s}
        .dc-input:hover{border-color:#9b59b6}
        .dc-input.selected{color:#2c3e50;border-color:#9b59b6}
        .dc-drop{display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #9b59b6;border-radius:6px;box-shadow:0 8px 24px rgba(108,52,131,.15);z-index:200;margin-top:3px}
        .dc-drop.open{display:block}
        .dc-search-wrap{padding:8px 10px;border-bottom:1px solid #f0e6f6}
        .dc-search{width:100%;border:1.5px solid #e0c8f0;border-radius:6px;padding:7px 10px;font-size:13px;outline:none;background:#f9f4ff}
        .dc-search:focus{border-color:#9b59b6}
        .dc-list{max-height:220px;overflow-y:auto}
        .dc-item{padding:9px 14px;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:8px;transition:.1s;border-bottom:1px solid #fdf8ff}
        .dc-item:hover{background:#f5eef8;color:#6c3483}
        .dc-item.active{background:#f5eef8;color:#6c3483;font-weight:700}
        .dc-letter{width:18px;height:18px;border-radius:50%;background:#e8d5f0;color:#6c3483;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        </style>
        <div class="frow">
          <div class="fg"><label>Tên hàng hóa <span class="req">*</span></label>
            <input type="text" name="ten_hang" class="field" required></div>
          <div class="fg"><label>Ngày gửi <span class="req">*</span></label>
            <input type="date" name="ngay_gui" class="field" value="<?=date('Y-m-d')?>" required></div>
        </div>
        <div class="frow">
          <div class="fg"><label>Loại hàng</label>
            <select name="loai_hang" id="loai_hang" class="field" onchange="calc()">
              <option value="thuong">Hàng thường</option>
              <option value="de_vo">Hàng dễ vỡ</option>
              <option value="lanh">Hàng lạnh/đông lạnh</option>
              <option value="qua_kho">Hàng quá khổ</option>
              <option value="nguy_hiem">Hàng nguy hiểm</option>
            </select></div>
          <div class="fg"><label>Khối lượng (kg) <span class="req">*</span></label>
            <input type="number" name="khoi_luong" id="kl" class="field" min="0" step="0.1" placeholder="0" oninput="calc()" required></div>
        </div>
        <div class="frow">
          <div class="fg"><label>Phương thức vận chuyển <span class="req">*</span></label>
            <select name="phuong_thuc" id="pt" class="field" onchange="calc()" required>
              <option value="duong_bo">Đường bộ (xe tải) — 150đ/kg</option>
              <option value="duong_bien">Đường biển — 100đ/kg</option>
              <option value="hang_khong">Hàng không — 500đ/kg</option>
              <option value="duong_sat">Đường sắt — 80đ/kg</option>
            </select></div>
          <div class="fg"><label>Thể tích (m³) — Tùy chọn</label>
            <input type="number" name="the_tich" id="the_tich" class="field" min="0" step="0.001" placeholder="VD: 0.5" oninput="calc()"></div>
        </div>
        <input type="hidden" name="thanh_toan" value="tien_mat">
        <div class="fg" style="margin-bottom:12px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
            <label style="margin-bottom:0">Ghi chú</label>
            <button type="button" onclick="toggleGhiChuCosan()" style="background:none;border:none;color:#9b59b6;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;padding:0;text-transform:none;letter-spacing:normal">
              + Chọn ghi chú có sẵn
            </button>
          </div>
          <textarea name="ghi_chu" id="gc-text" class="field" placeholder="Nhập ghi chú hoặc chọn có sẵn ở trên..."></textarea>
        </div>

        <!-- FLOATING MODAL GHI CHÚ CÓ SẴN -->
        <div id="gc-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:600;align-items:center;justify-content:center">
          <div style="background:#fff;border-radius:10px;width:420px;max-width:92vw;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,.18);overflow:hidden">
            <!-- Header -->
            <div style="padding:14px 18px;border-bottom:1px solid #f0e6f6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
              <span style="font-size:14px;font-weight:700;color:#2c3e50">Chọn ghi chú</span>
              <button type="button" onclick="toggleGhiChuCosan()" style="background:none;border:none;font-size:18px;color:#95a5a6;cursor:pointer;padding:0;line-height:1">×</button>
            </div>
            <!-- Danh sách scroll được -->
            <div style="overflow-y:auto;flex:1;padding:4px 0">
              <?php
              $gc_list = [
                'Hàng dễ vỡ, vui lòng nhẹ tay',
                'Gọi điện thoại cho người nhận trước khi giao',
                'Giao hàng trong giờ hành chính (8h-17h)',
                'Không giao ngoài giờ hành chính',
                'Cho người nhận xem hàng trước khi nhận',
                'Không cho xem hàng',
                'Giao hàng một phần, nhận lại hàng không giao được',
                'Không tự ý hủy đơn khi không liên lạc được',
                'Hàng cần bảo quản lạnh, tránh nhiệt độ cao',
                'Hàng có giá trị cao, cần ký nhận cẩn thận',
              ];
              foreach($gc_list as $gc): ?>
              <label style="display:flex;align-items:center;gap:12px;padding:11px 18px;cursor:pointer;font-size:13.5px;font-weight:400;color:#2c3e50;text-transform:none;letter-spacing:normal;border-bottom:1px solid #f8f4fc;transition:background .1s" onmouseover="this.style.background='#fdf8ff'" onmouseout="this.style.background='transparent'">
                <input type="checkbox" class="gc-check" value="<?=htmlspecialchars($gc)?>" style="width:16px;height:16px;accent-color:#9b59b6;flex-shrink:0;cursor:pointer">
                <?=htmlspecialchars($gc)?>
              </label>
              <?php endforeach; ?>
            </div>
            <!-- Footer nút -->
            <div style="padding:12px 18px;border-top:1px solid #f0e6f6;display:flex;justify-content:flex-end;gap:8px;flex-shrink:0">
              <button type="button" onclick="toggleGhiChuCosan()" class="btn btn-ghost btn-sm">Đóng</button>
              <button type="button" onclick="xacNhanGhiChu()" class="btn btn-pri btn-sm">Xác nhận</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div><!-- /step1-area -->
    <div class="cuoc-box" id="cuoc-area">
      <div class="cuoc-lbl">🧾 Dự tính cước phí vận chuyển</div>
      <div class="cuoc-val" id="cuoc-display">Nhập khối lượng để tính tự động</div>
      <div class="cuoc-note" id="cuoc-note"></div>
    </div>

    <!-- Step 1 buttons -->
    <div id="step1-btns" class="btn-row">
      <a href="?page=lich-su" class="btn btn-ghost">☰ Xem lịch sử</a>
      <button type="button" class="btn btn-ghost" onclick="xemTruoc()">👁 Xem trước</button>
      <button type="button" class="btn btn-pri" onclick="nextStep()">Tiếp theo → Thanh toán</button>
    </div>

    <!-- Step 2: Payment method -->
    <div id="step2-area" style="display:none">
      <div class="card">
        <div class="card-hd">Bước 2 — Chọn phương thức thanh toán</div>
        <div class="card-bd">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
            <label class="pay-opt" data-val="tien_mat">
              <input type="radio" name="thanh_toan_step2" value="tien_mat" checked onchange="chonPT(this.value)">
              <div class="pay-box active" id="po-tien_mat">
                <div style="font-size:24px">💵</div>
                <div style="font-weight:700;margin-top:6px">Tiền mặt (COD)</div>
                <div style="font-size:11px;color:#7f8c8d;margin-top:3px">Thanh toán khi nhận hàng</div>
              </div>
            </label>
            <label class="pay-opt" data-val="chuyen_khoan">
              <input type="radio" name="thanh_toan_step2" value="chuyen_khoan" onchange="chonPT(this.value)">
              <div class="pay-box" id="po-chuyen_khoan">
                <div style="font-size:24px">🏦</div>
                <div style="font-weight:700;margin-top:6px">Chuyển khoản</div>
                <div style="font-size:11px;color:#7f8c8d;margin-top:3px">Chuyển khoản ngân hàng</div>
              </div>
            </label>

            <label class="pay-opt" data-val="vi_dien_tu">
              <input type="radio" name="thanh_toan_step2" value="vi_dien_tu" onchange="chonPT(this.value)">
              <div class="pay-box" id="po-vi_dien_tu">
                <div style="font-size:24px">📱</div>
                <div style="font-weight:700;margin-top:6px">Ví điện tử</div>
                <div style="font-size:11px;color:#7f8c8d;margin-top:3px">MoMo, ZaloPay, VNPay</div>
              </div>
            </label>
          </div>

          <!-- Payment notice -->
          <div id="pay-notice-cod" style="background:#eafaf1;border:1px solid #a9dfbf;border-radius:6px;padding:12px 16px;font-size:13px;color:#1e8449">
            💵 <b>Tiền mặt (COD):</b> Đơn hàng sẽ được tạo ngay. Bạn thanh toán khi nhận hàng.
          </div>
          <div id="pay-notice-ck" style="display:none;background:#fff3e0;border:1px solid #ffcc80;border-radius:6px;padding:12px 16px;font-size:13px;color:#e65100">
            🏦 <b>Chuyển khoản:</b> Sau khi tạo đơn, hệ thống sẽ hiển thị thông tin chuyển khoản. Đơn sẽ được xử lý sau khi xác nhận thanh toán.
          </div>

          <div id="pay-notice-vi" style="display:none;background:#f3e5f5;border:1px solid #ce93d8;border-radius:6px;padding:12px 16px;font-size:13px;color:#6a1b9a">
            📱 <b>Ví điện tử:</b> Sau khi tạo đơn, mã QR thanh toán sẽ được hiển thị. Tính năng đang phát triển.
          </div>
        </div>
      </div>

      <div class="btn-row">
        <button type="button" class="btn btn-ghost" onclick="prevStep()">← Quay lại</button>
        <button type="button" class="btn btn-ghost" onclick="xemTruoc()">👁 Xem trước</button>
        <button type="submit" class="btn btn-pri" onclick="syncPayment()">✅ Tạo đơn hàng</button>
      </div>
    </div>
    </form>

    <style>
    .pay-opt input{display:none}
    .pay-box{border:2px solid #e0c8f0;border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:.15s;background:#fdf8ff}
    .pay-box:hover{border-color:#9b59b6;background:#f5eef8}
    .pay-box.active{border-color:#6c3483;background:#f5eef8;box-shadow:0 0 0 3px rgba(108,52,131,.12)}
    </style>
