<?php
session_start();
require 'config.php';
require_role(['khachhang']);

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Khách hàng';
$username  = $_SESSION['username']  ?? '';
$email     = $_SESSION['email']     ?? '';
$u = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();

$msg = ''; $msg_type = '';

if (($_POST['action']??'') === 'tao_don') {
    $ma = 'VT-'.date('Ymd').'-'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
    $f=[];
    foreach(['ten_hang','ten_gui','sdt_gui','email_gui','dia_chi_gui','ten_nhan','sdt_nhan','email_nhan','dia_chi_nhan','ngay_gui','loai_hang','phuong_thuc','thanh_toan','ghi_chu'] as $k)
        $f[$k]=$conn->real_escape_string(trim($_POST[$k]??''));
    $kl=floatval($_POST['khoi_luong']??0); $tv=floatval($_POST['the_tich']??0); $cuoc=floatval($_POST['cuoc_phi_tt']??0);
    $pt_tt=$f['thanh_toan'];
    if($pt_tt==='tien_mat'){$trang_thai='cho_duyet';$payment_status='unpaid';}
    else{$trang_thai='waiting_payment';$payment_status='pending';}
    $tinh_gui = $conn->real_escape_string(trim($_POST['tinh_gui']??''));
    $tinh_nhan = $conn->real_escape_string(trim($_POST['tinh_nhan']??''));
    $dc_gui = $f['dia_chi_gui'].($tinh_gui ? ', '.$tinh_gui : '');
    $dc_nhan = $f['dia_chi_nhan'].($tinh_nhan ? ', '.$tinh_nhan : '');
    $conn->query("INSERT INTO don_hang(ma_don,nguoi_tao_id,ten_hang,ten_gui,sdt_gui,email_gui,dia_chi_gui,ten_nhan,sdt_nhan,email_nhan,dia_chi_nhan,ngay_tao,loai_hang,khoi_luong,the_tich,phuong_thuc,thanh_toan,ghi_chu,doanh_thu,trang_thai,payment_status)VALUES('{$ma}',{$user_id},'{$f['ten_hang']}','{$f['ten_gui']}','{$f['sdt_gui']}','{$f['email_gui']}','".addslashes($dc_gui)."','{$f['ten_nhan']}','{$f['sdt_nhan']}','{$f['email_nhan']}','".addslashes($dc_nhan)."',NOW(),'{$f['loai_hang']}',{$kl},{$tv},'{$f['phuong_thuc']}','{$f['thanh_toan']}','{$f['ghi_chu']}',{$cuoc},'{$trang_thai}','{$payment_status}')");
    if($pt_tt==='tien_mat'){
        header("Location: ?page=lich-su&tao_ok=1&ma=".urlencode($ma)."&ptt=".urlencode($pt_tt)); exit;
    } else {
        header("Location: ?page=thanh-toan-qr&ma=".urlencode($ma)); exit;
    }
}
if (($_POST['action']??'') === 'huy_don') {
    $ma=$conn->real_escape_string($_POST['ma_don']??'');
    $conn->query("UPDATE don_hang SET trang_thai='huy' WHERE ma_don='$ma' AND nguoi_tao_id=$user_id AND trang_thai IN ('cho_duyet','waiting_payment')");
    header('Location: ?page=lich-su'); exit;
}
if (($_POST['action']??'') === 'sua_don') {
    $ma=$conn->real_escape_string($_POST['ma_don']??'');
    $don=$conn->query("SELECT * FROM don_hang WHERE ma_don='$ma' AND nguoi_tao_id=$user_id LIMIT 1")->fetch_assoc();
    $gio_qua = $don ? (time() - strtotime($don['ngay_tao'])) / 3600 : 999;
    if($don && in_array($don['trang_thai'],['cho_duyet','waiting_payment']) && $gio_qua <= 24){
        $sets=[];
        foreach(['ten_gui','sdt_gui','email_gui','dia_chi_gui','ten_nhan','sdt_nhan','email_nhan','dia_chi_nhan','ten_hang','ghi_chu','loai_hang','phuong_thuc','thanh_toan'] as $k)
            $sets[]="$k='".$conn->real_escape_string(trim($_POST[$k]??''))."'";
        $kl = floatval($_POST['khoi_luong']??0);
        $sets[] = "khoi_luong=$kl";
        // Cập nhật trang_thai theo phương thức thanh toán mới
        $pt_moi = trim($_POST['thanh_toan']??'');
        if($pt_moi === 'tien_mat'){
            $sets[] = "trang_thai='cho_duyet'";
            $sets[] = "payment_status='cod'";
        } else {
            // Đổi sang chuyển khoản/ví → chờ thanh toán QR
            $sets[] = "trang_thai='waiting_payment'";
            $sets[] = "payment_status='pending'";
        }
        $conn->query("UPDATE don_hang SET ".implode(',',$sets)." WHERE ma_don='$ma' AND nguoi_tao_id=$user_id");
        // Redirect đúng chỗ theo pt mới
        if($pt_moi === 'tien_mat'){
            header('Location: ?page=lich-su'); exit;
        } else {
            header('Location: ?page=thanh-toan-qr&ma='.urlencode($ma)); exit;
        }
    }
    header('Location: ?page=lich-su'); exit;
}
if (($_POST['action']??'') === 'cap_nhat_tk') {
    $ht=$conn->real_escape_string(trim($_POST['ho_ten']??''));
    $sdt=$conn->real_escape_string(trim($_POST['sdt']??''));
    $conn->query("UPDATE users SET full_name='$ht',phone='$sdt' WHERE id=$user_id");
    $_SESSION['full_name']=$ht; $full_name=$ht;
    $u=$conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
    $msg='Cập nhật thông tin thành công!'; $msg_type='ok';
}
if (($_POST['action']??'') === 'doi_mk') {
    $mk_cu=$_POST['mk_cu']??''; $mk_moi=$_POST['mk_moi']??''; $mk_xn=$_POST['mk_xn']??'';
    if(!password_verify($mk_cu,$u['password'])){$msg='Mật khẩu hiện tại không đúng.';$msg_type='loi';}
    elseif(strlen($mk_moi)<6){$msg='Mật khẩu mới phải có ít nhất 6 ký tự.';$msg_type='loi';}
    elseif($mk_moi!==$mk_xn){$msg='Xác nhận mật khẩu không khớp.';$msg_type='loi';}
    else{$conn->query("UPDATE users SET password='".password_hash($mk_moi,PASSWORD_DEFAULT)."' WHERE id=$user_id");$msg='Đổi mật khẩu thành công!';$msg_type='ok';}
}

$page=$_GET['page']??'tong-quan';
// Tự động hủy các đơn waiting_payment quá 20 phút
$conn->query("UPDATE don_hang SET trang_thai='huy', payment_status='failed' WHERE nguoi_tao_id=$user_id AND trang_thai='waiting_payment' AND TIMESTAMPDIFF(MINUTE, ngay_tao, NOW()) > 20");
$filter_tt=$_GET['tt']??'tat-ca';
$where_tt='';
$tt_map=['cho-xu-ly'=>"trang_thai='cho_duyet'",'dang-van'=>"trang_thai IN ('dang_van_chuyen','dang_xu_ly','dang_lay_hang','dang_giao')",'da-giao'=>"trang_thai IN ('hoan_thanh','da_thanh_toan','da_giao')",'da-huy'=>"trang_thai='huy'"];
if(isset($tt_map[$filter_tt])) $where_tt="AND ".$tt_map[$filter_tt];

$tong=$conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE nguoi_tao_id=$user_id")->fetch_assoc()['c']??0;
$cho=$conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE nguoi_tao_id=$user_id AND trang_thai='cho_duyet'")->fetch_assoc()['c']??0;
$dang=$conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE nguoi_tao_id=$user_id AND trang_thai='dang_van_chuyen'")->fetch_assoc()['c']??0;
$xong=$conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE nguoi_tao_id=$user_id AND trang_thai IN ('hoan_thanh','da_thanh_toan')")->fetch_assoc()['c']??0;
$huy=$conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE nguoi_tao_id=$user_id AND trang_thai='huy'")->fetch_assoc()['c']??0;
$don_list=$conn->query("SELECT * FROM don_hang WHERE nguoi_tao_id=$user_id $where_tt ORDER BY ngay_tao DESC")->fetch_all(MYSQLI_ASSOC);
$don_all=$conn->query("SELECT * FROM don_hang WHERE nguoi_tao_id=$user_id ORDER BY ngay_tao DESC")->fetch_all(MYSQLI_ASSOC);
$don_recent=$conn->query("SELECT * FROM don_hang WHERE nguoi_tao_id=$user_id ORDER BY ngay_tao DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

function fmtVND($n){return number_format($n,0,',','.').' đ';}
function tenTT($tt){return match($tt){'cho_duyet'=>'Chờ duyệt','dang_giao'=>'Đang giao','dang_van_chuyen'=>'Đang vận chuyển','dang_xu_ly'=>'Đang xử lý','dang_lay_hang'=>'Đang lấy hàng','da_giao'=>'Đã giao','hoan_thanh'=>'Hoàn thành','da_thanh_toan'=>'Đã thanh toán','huy'=>'Đã hủy','waiting_payment'=>'Chờ thanh toán',default=>$tt};}
function mauTT($tt){return match($tt){'cho_duyet'=>'bdg-warn','dang_giao','dang_van_chuyen','dang_xu_ly','dang_lay_hang'=>'bdg-blue','da_giao','hoan_thanh','da_thanh_toan'=>'bdg-green','huy'=>'bdg-red','waiting_payment'=>'bdg-orange',default=>'bdg-gray'};}
function tenPS($ps){return match($ps??''){'unpaid'=>'Chưa thanh toán','pending'=>'Chờ thanh toán','paid'=>'Đã thanh toán','failed'=>'Thanh toán thất bại',default=>'—'};}
function mauPS($ps){return match($ps??''){'unpaid'=>'bdg-gray','pending'=>'bdg-warn','paid'=>'bdg-green','failed'=>'bdg-red',default=>'bdg-gray'};}

$words=explode(' ',$full_name);
$initials=mb_strtoupper(mb_substr($words[0],0,1));
if(count($words)>1) $initials.=mb_strtoupper(mb_substr(end($words),0,1));

$page_titles=['tong-quan'=>'Tổng Quan','tao-don'=>'Tạo Đơn Hàng','lich-su'=>'Lịch Sử Đơn Hàng','tra-cuu'=>'Tra Cứu Đơn','tai-khoan'=>'Tài Khoản','thanh-toan-qr'=>'Thanh Toán Đơn Hàng'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cổng Khách Hàng — Vận Tải Hàng Hóa</title>
<link rel="stylesheet" href="kh_style.css">
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <button class="btn-toggle" onclick="toggleSidebar()" title="Menu">☰</button>
  <div class="topbar-brand">🚛 Vận Tải Hàng Hóa</div>
  <div class="topbar-right">
    <div class="t-avatar"><?=htmlspecialchars($initials)?></div>
    <span class="t-name"><?=htmlspecialchars($full_name)?></span>
    <a href="logout.php" class="btn-out">🚪 Đăng xuất</a>
  </div>
</div>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="layout">
  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sb-profile">
      <div class="sb-avatar"><?=htmlspecialchars($initials)?></div>
      <div class="sb-name"><?=htmlspecialchars($full_name)?></div>
      <div class="sb-role">👤 Khách hàng</div>
    </div>
    <div class="sb-section">
      <div class="sb-section-label">Chức năng</div>
      <a href="?page=tong-quan"  class="sb-item <?=$page==='tong-quan'?'active':''?>"><span class="ic">📊</span> Tổng quan</a>
      <a href="?page=tao-don"    class="sb-item <?=$page==='tao-don'?'active':''?>"><span class="ic">⚙️</span> Tạo đơn hàng</a>
      <a href="?page=lich-su"    class="sb-item <?=$page==='lich-su'?'active':''?>"><span class="ic">☰</span> Lịch sử đơn hàng</a>
      <a href="?page=tra-cuu"    class="sb-item <?=$page==='tra-cuu'?'active':''?>"><span class="ic">🔍</span> Tra cứu đơn</a>
      <a href="?page=tai-khoan"  class="sb-item <?=$page==='tai-khoan'?'active':''?>"><span class="ic">👤</span> Tài khoản</a>
    </div>

  </aside>

  <!-- MAIN -->
  <main class="main" id="main">
    <div class="breadcrumb">
      <a href="?page=tong-quan">🏠 Trang chủ</a>
      <span class="sep">›</span>
      <span><?=$page_titles[$page]??'Trang chủ'?></span>
    </div>

    <div class="content">

    <?php if($msg): ?>
      <div class="alert alert-<?=$msg_type?>"><?=htmlspecialchars($msg)?></div>
    <?php endif; ?>

    <?php if($page==='tong-quan'): ?>
    <?php include 'kh_tong_quan.php'; ?>
        <?php elseif($page==='tao-don'): ?>
    <?php include 'kh_tao_don.php'; ?>
    <?php elseif($page==='lich-su'): ?>
    <?php include 'kh_lich_su.php'; ?>
        <?php elseif($page==='tra-cuu'): ?>
    <?php include 'kh_tra_cuu.php'; ?>
    <?php elseif($page==='tai-khoan'): ?>
    <?php include 'kh_tai_khoan.php'; ?>
    <?php elseif($page==='thanh-toan-qr'): ?>
    <?php include 'kh_thanh_toan_qr.php'; ?>
    <?php endif; ?>

    <?php if($page==='tong-quan'): ?>
        <?php endif; ?>

    </div><!-- /content -->
  </main>
</div><!-- /layout -->

<!-- Modal chi tiết -->
<div class="mbg" id="modalDon">
  <div class="modal">
    <h3>📋 Chi tiết đơn hàng</h3>
    <div id="modalDonContent"></div>
    <div class="btn-row" style="margin-top:12px">
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modalDon').classList.remove('open')">Đóng</button>
      <button class="btn btn-ghost btn-sm" id="btnSua" onclick="moSua()" style="display:none">✏️ Sửa</button>
      <button class="btn btn-danger btn-sm" id="btnHuy" onclick="huyDon()" style="display:none">🚫 Hủy đơn</button>
      <a class="btn btn-pri btn-sm" id="btnThanhToan" href="#" style="display:none">💳 Thanh toán ngay</a>
    </div>
  </div>
</div>

<!-- Modal xem trước -->
<div class="mbg" id="modalXT">
  <div class="modal">
    <h3>📋 Xác nhận thông tin đơn hàng</h3>
    <div id="modalXTContent"></div>
    <div class="btn-row" style="margin-top:12px">
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modalXT').classList.remove('open')">✕ Đóng</button>
      <button class="btn btn-pri btn-sm" onclick="document.getElementById('frmDon').submit()">✅ Xác nhận gửi</button>
    </div>
  </div>
</div>

<footer class="site-footer" id="footer">
  <div style="background:linear-gradient(135deg,#5b2c8d 0%,#8e44ad 100%);padding:28px 32px 0">
    <div style="max-width:900px;margin:0 auto;display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:32px;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,.15)">
      <!-- Cột 1: Logo + mô tả -->
      <div>
        <div style="font-size:20px;font-weight:800;color:#fff;margin-bottom:8px">🚛 Vận Tải Hàng Hóa</div>
        <div style="font-size:12px;color:rgba(255,255,255,.6);line-height:1.7">Hệ thống quản lý vận tải hàng hóa chuyên nghiệp.<br>Kết nối khách hàng và đơn vị vận chuyển nhanh chóng, an toàn.</div>
      </div>
      <!-- Cột 2: Liên kết nhanh -->
      <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:10px">Chức năng</div>
        <div style="display:flex;flex-direction:column;gap:7px">
          <a href="?page=tong-quan"  style="font-size:13px;color:rgba(255,255,255,.7);text-decoration:none">📊 Tổng quan</a>
          <a href="?page=tao-don"    style="font-size:13px;color:rgba(255,255,255,.7);text-decoration:none">⚙️ Tạo đơn hàng</a>
          <a href="?page=lich-su"    style="font-size:13px;color:rgba(255,255,255,.7);text-decoration:none">☰ Lịch sử đơn hàng</a>
          <a href="?page=tra-cuu"    style="font-size:13px;color:rgba(255,255,255,.7);text-decoration:none">🔍 Tra cứu đơn</a>
          <a href="?page=tai-khoan"  style="font-size:13px;color:rgba(255,255,255,.7);text-decoration:none">👤 Tài khoản</a>
        </div>
      </div>
      <!-- Cột 3: Liên hệ -->
      <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:10px">Liên hệ</div>
        <div style="display:flex;flex-direction:column;gap:7px;font-size:13px;color:rgba(255,255,255,.7)">
          <span>📞 Hotline: 1900-xxxx</span>
          <span>📧 hotro@vantaihanhoa.vn</span>
          <span>🏠 123 Đường ABC, TP. HCM</span>
          <span>🕐 Hỗ trợ: 7:00 – 22:00</span>
        </div>
      </div>
    </div>
    <!-- Copyright bar -->
    <div style="max-width:900px;margin:0 auto;padding:14px 0;display:flex;align-items:center;justify-content:space-between">
      <div style="font-size:12px;color:rgba(255,255,255,.4)">© 2026 Vận Tải Hàng Hóa. All rights reserved.</div>
      <div style="font-size:12px;color:rgba(255,255,255,.4)">Phiên bản 1.0</div>
    </div>
  </div>
</footer>
<script src="kh_script.js"></script>

<!-- WIDGET HỖ TRỢ KHÁCH HÀNG -->
<div id="support-popup" style="display:none;position:fixed;bottom:90px;right:24px;z-index:9998;width:300px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.18);overflow:hidden;animation:popupIn .2s ease">
  <!-- Header -->
  <div style="background:linear-gradient(135deg,#6c3483,#9b59b6);padding:16px 18px;display:flex;align-items:center;gap:12px">
    <div style="width:40px;height:40px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px">🚛</div>
    <div>
      <div style="font-size:14px;font-weight:700;color:#fff">Hỗ trợ khách hàng</div>
      <div style="font-size:11px;color:rgba(255,255,255,.75)">Liên hệ ngay với chúng tôi</div>
    </div>
    <button onclick="toggleSupport()" style="margin-left:auto;background:none;border:none;color:rgba(255,255,255,.8);font-size:20px;cursor:pointer;line-height:1;padding:0">×</button>
  </div>
  <!-- Nội dung -->
  <div style="padding:16px;display:flex;flex-direction:column;gap:10px">
    <a href="tel:0775553749" style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#f9f4ff;border:1.5px solid #e8d5f0;border-radius:10px;text-decoration:none;transition:.15s" onmouseover="this.style.borderColor='#9b59b6'" onmouseout="this.style.borderColor='#e8d5f0'">
      <div style="width:38px;height:38px;background:linear-gradient(135deg,#27ae60,#2ecc71);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">📞</div>
      <div>
        <div style="font-size:11px;color:#7f8c8d;margin-bottom:2px">Gọi điện hỗ trợ</div>
        <div style="font-size:14px;font-weight:700;color:#2c3e50">0775 553 749</div>
      </div>
    </a>
    <a href="https://zalo.me/0775553749" target="_blank" style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#f9f4ff;border:1.5px solid #e8d5f0;border-radius:10px;text-decoration:none;transition:.15s" onmouseover="this.style.borderColor='#9b59b6'" onmouseout="this.style.borderColor='#e8d5f0'">
      <div style="width:38px;height:38px;background:linear-gradient(135deg,#0068ff,#00aaff);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">💬</div>
      <div>
        <div style="font-size:11px;color:#7f8c8d;margin-bottom:2px">Nhắn Zalo</div>
        <div style="font-size:14px;font-weight:700;color:#2c3e50">0775 553 749</div>
      </div>
    </a>
    <a href="mailto:hotro@vantaihanhoa.vn" style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#f9f4ff;border:1.5px solid #e8d5f0;border-radius:10px;text-decoration:none;transition:.15s" onmouseover="this.style.borderColor='#9b59b6'" onmouseout="this.style.borderColor='#e8d5f0'">
      <div style="width:38px;height:38px;background:linear-gradient(135deg,#e74c3c,#c0392b);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">✉️</div>
      <div>
        <div style="font-size:11px;color:#7f8c8d;margin-bottom:2px">Gửi email</div>
        <div style="font-size:14px;font-weight:700;color:#2c3e50">hotro@vantaihanhoa.vn</div>
      </div>
    </a>
    <div style="text-align:center;font-size:11px;color:#95a5a6;margin-top:2px">Hỗ trợ 24/7, Thứ 2 – Chủ Nhật</div>
  </div>
</div>

<!-- NÚT CHAT NỔI -->
<button id="support-btn" onclick="toggleSupport()" style="position:fixed;bottom:24px;right:24px;z-index:9999;width:56px;height:56px;border-radius:50%;border:none;background:linear-gradient(135deg,#6c3483,#9b59b6);box-shadow:0 4px 16px rgba(108,52,131,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 2C6.477 2 2 6.145 2 11.25c0 2.547 1.11 4.847 2.9 6.497L4 22l4.5-1.5A10.86 10.86 0 0012 21.5c5.523 0 10-4.145 10-9.25S17.523 2 12 2z" fill="white"/>
    <circle cx="8.5" cy="11.5" r="1.2" fill="#9b59b6"/>
    <circle cx="12" cy="11.5" r="1.2" fill="#9b59b6"/>
    <circle cx="15.5" cy="11.5" r="1.2" fill="#9b59b6"/>
  </svg>
  <!-- Chấm xanh online -->
  <span style="position:absolute;top:4px;right:4px;width:12px;height:12px;background:#2ecc71;border-radius:50%;border:2px solid #fff"></span>
</button>

<style>
@keyframes popupIn {
  from { opacity:0; transform:translateY(10px) scale(.97); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}
</style>
<script>
function toggleSupport(){
  const p = document.getElementById('support-popup');
  p.style.display = p.style.display === 'none' ? 'block' : 'none';
}
// Đóng khi bấm ra ngoài
document.addEventListener('click', function(e){
  const popup = document.getElementById('support-popup');
  const btn   = document.getElementById('support-btn');
  if(popup && btn && !popup.contains(e.target) && !btn.contains(e.target)){
    popup.style.display = 'none';
  }
});
</script>
</body>
</html>
