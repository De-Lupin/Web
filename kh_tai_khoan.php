    <div class="page-title">👤 Tài khoản của tôi</div>
    <div class="card">
      <div class="card-hd">Thông tin cá nhân</div>
      <div class="card-bd">
        <form method="post">
          <input type="hidden" name="action" value="cap_nhat_tk">
          <div class="frow">
            <div class="fg"><label>Họ và tên</label>
              <input type="text" name="ho_ten" class="field" value="<?=htmlspecialchars($u['full_name']??'')?>"></div>
            <div class="fg"><label>Số điện thoại</label>
              <input type="tel" name="sdt" class="field" value="<?=htmlspecialchars($u['phone']??'')?>"></div>
          </div>
          <div class="frow">
            <div class="fg"><label>Email</label>
              <input type="email" class="field" value="<?=htmlspecialchars($email)?>" readonly></div>
            <div class="fg"><label>Tên đăng nhập</label>
              <input type="text" class="field" value="<?=htmlspecialchars($username)?>" readonly></div>
          </div>
          <div class="btn-row"><button type="submit" class="btn btn-pri">💾 Lưu thay đổi</button></div>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-hd">Bảo mật</div>
      <div class="card-bd">
        <form method="post">
          <input type="hidden" name="action" value="doi_mk">
          <div class="fg" style="margin-bottom:12px"><label>Mật khẩu hiện tại</label>
            <input type="password" name="mk_cu" class="field" placeholder="••••••••" required></div>
          <div class="frow">
            <div class="fg"><label>Mật khẩu mới</label>
              <input type="password" name="mk_moi" class="field" placeholder="Tối thiểu 6 ký tự" required></div>
            <div class="fg"><label>Xác nhận mật khẩu mới</label>
              <input type="password" name="mk_xn" class="field" placeholder="••••••••" required></div>
          </div>
          <div class="btn-row"><button type="submit" class="btn btn-pri">🔒 Đổi mật khẩu</button></div>
        </form>
      </div>
    </div>
