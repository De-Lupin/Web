<?php
session_start();
require 'config.php';
require_role(['khachhang']);

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Khách hàng';
$username  = $_SESSION['username']  ?? '';
$email     = $_SESSION['email']     ?? '';

$u = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();

$msg = '';
$msg_type = '';

// ============ TẠO ĐƠN ============
if (isset($_POST['action']) && $_POST['action'] === 'tao_don') {
    $ma = 'VT-'.date('Ymd').'-'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
    $fields = ['ten_hang','ten_gui','sdt_gui','email_gui','dia_chi_gui',
               'ten_nhan','sdt_nhan','email_nhan','dia_chi_nhan',
               'ngay_gui','loai_hang','phuong_thuc','thanh_toan','ghi_chu'];
    $data = [];
    foreach ($fields as $f) $data[$f] = $conn->real_escape_string(trim($_POST[$f]??''));
    $kl   = floatval($_POST['khoi_luong']??0);
    $tv   = floatval($_POST['the_tich']??0);
    $cuoc = floatval($_POST['cuoc_phi_tt']??0);

    $conn->query("INSERT INTO don_hang
        (ma_don,nguoi_tao_id,ten_hang,ten_gui,sdt_gui,email_gui,dia_chi_gui,
         ten_nhan,sdt_nhan,email_nhan,dia_chi_nhan,ngay_tao,loai_hang,
         khoi_luong,the_tich,phuong_thuc,thanh_toan,ghi_chu,doanh_thu,trang_thai)
        VALUES
        ('{$ma}',{$user_id},'{$data['ten_hang']}','{$data['ten_gui']}','{$data['sdt_gui']}',
         '{$data['email_gui']}','{$data['dia_chi_gui']}','{$data['ten_nhan']}',
         '{$data['sdt_nhan']}','{$data['email_nhan']}','{$data['dia_chi_nhan']}',
         NOW(),'{$data['loai_hang']}',{$kl},{$tv},'{$data['phuong_thuc']}',
         '{$data['thanh_toan']}','{$data['ghi_chu']}',{$cuoc},'cho_duyet')");
    header("Location: ?page=lich-su&tao_ok=1&ma=".urlencode($ma)); exit;
}

// ============ HỦY ĐƠN ============
if (isset($_POST['action']) && $_POST['action'] === 'huy_don') {
    $ma = $conn->real_escape_string($_POST['ma_don']??'');
    $conn->query("UPDATE don_hang SET trang_thai='huy' WHERE ma_don='$ma' AND nguoi_tao_id=$user_id AND trang_thai='cho_duyet'");
    header('Location: ?page=lich-su'); exit;
}

// ============ SỬA ĐƠN ============
if (isset($_POST['action']) && $_POST['action'] === 'sua_don') {
    $ma = $conn->real_escape_string($_POST['ma_don']??'');
    $don_cu = $conn->query("SELECT * FROM don_hang WHERE ma_don='$ma' AND nguoi_tao_id=$user_id LIMIT 1")->fetch_assoc();
    if ($don_cu && $don_cu['trang_thai']==='cho_duyet') {
        $fields2 = ['ten_gui','sdt_gui','email_gui','dia_chi_gui','ten_nhan','sdt_nhan','email_nhan','dia_chi_nhan','ten_hang','ghi_chu'];
        $sets = [];
        foreach ($fields2 as $f) {
            $v = $conn->real_escape_string(trim($_POST[$f]??''));
            $sets[] = "$f='$v'";
        }
        $conn->query("UPDATE don_hang SET ".implode(',',$sets)." WHERE ma_don='$ma' AND nguoi_tao_id=$user_id");
    }
    header('Location: ?page=lich-su'); exit;
}

// ============ CẬP NHẬT TÀI KHOẢN ============
if (isset($_POST['action']) && $_POST['action'] === 'cap_nhat_tk') {
    $ht  = $conn->real_escape_string(trim($_POST['ho_ten']??''));
    $sdt = $conn->real_escape_string(trim($_POST['sdt']??''));
    $conn->query("UPDATE users SET full_name='$ht',phone='$sdt' WHERE id=$user_id");
    $_SESSION['full_name'] = $ht;
    $full_name = $ht;
    $u = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
    $msg='Cập nhật thông tin thành công!'; $msg_type='ok';
}

// ============ ĐỔI MẬT KHẨU ============
if (isset($_POST['action']) && $_POST['action'] === 'doi_mk') {
    $mk_cu  = $_POST['mk_cu']  ?? '';
    $mk_moi = $_POST['mk_moi'] ?? '';
    $mk_xn  = $_POST['mk_xn']  ?? '';
    if (!password_verify($mk_cu, $u['password'])) {
        $msg='Mật khẩu hiện tại không đúng.'; $msg_type='loi';
    } elseif (strlen($mk_moi)<6) {
        $msg='Mật khẩu mới phải có ít nhất 6 ký tự.'; $msg_type='loi';
    } elseif ($mk_moi!==$mk_xn) {
        $msg='Xác nhận mật khẩu không khớp.'; $msg_type='loi';
    } else {
        $hash=password_hash($mk_moi,PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hash' WHERE id=$user_id");
        $msg='Đổi mật khẩu thành công!'; $msg_type='ok';
    }
}

// ============ DỮ LIỆU ============
$page      = $_GET['page'] ?? 'tao-don';
$filter_tt = $_GET['tt']   ?? 'tat-ca';
$where_tt  = '';
$tt_map_w  = [
    'cho-xu-ly' => "trang_thai='cho_duyet'",
    'dang-van'  => "trang_thai='dang_giao'",
    'da-giao'   => "trang_thai IN ('hoan_thanh','da_thanh_toan')",
    'da-huy'    => "trang_thai='huy'",
];
if (isset($tt_map_w[$filter_tt])) $where_tt = "AND ".$tt_map_w[$filter_tt];

$tong = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE nguoi_tao_id=$user_id")->fetch_assoc()['c']??0;
$cho  = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE nguoi_tao_id=$user_id AND trang_thai='cho_duyet'")->fetch_assoc()['c']??0;
$xong = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE nguoi_tao_id=$user_id AND trang_thai IN ('hoan_thanh','da_thanh_toan')")->fetch_assoc()['c']??0;
$huy  = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE nguoi_tao_id=$user_id AND trang_thai='huy'")->fetch_assoc()['c']??0;

$don_list = $conn->query("SELECT * FROM don_hang WHERE nguoi_tao_id=$user_id $where_tt ORDER BY ngay_tao DESC")->fetch_all(MYSQLI_ASSOC);
$don_all  = $conn->query("SELECT * FROM don_hang WHERE nguoi_tao_id=$user_id ORDER BY ngay_tao DESC")->fetch_all(MYSQLI_ASSOC);

function fmtVND($n){ return number_format($n,0,',','.').' đ'; }
function mauTT($tt){ return match($tt){ 'cho_duyet'=>'bdg-warn','dang_giao'=>'bdg-blue','hoan_thanh','da_thanh_toan'=>'bdg-green','huy'=>'bdg-red',default=>'bdg-gray'}; }
function tenTT($tt){ return match($tt){ 'cho_duyet'=>'Chờ duyệt','dang_giao'=>'Đang giao','hoan_thanh'=>'Hoàn thành','da_thanh_toan'=>'Đã thanh toán','huy'=>'Đã hủy',default=>$tt}; }

$words = explode(' ', $full_name);
$initials = mb_strtoupper(mb_substr($words[0],0,1));
if(count($words)>1) $initials .= mb_strtoupper(mb_substr(end($words),0,1));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cổng Khách Hàng — Vận Tải Hàng Hóa</title>

<style>
/* Reset toàn bộ để tránh xung đột với style.css của project */
html,body{margin:0!important;padding:0!important;background:#F0F2F5!important;font-size:14px!important}
.loginwrapper,.logincontainer,.leftpanel,.rightpanel,.inputgroup,.loginbtn,.passwordwrapper{all:unset}
:root{
  --pri:#1A65C8;--pri2:#1550A8;--pri-lt:#E3EEFB;
  --bg:#F0F2F5;--card:#fff;--border:#DDE3EE;
  --text:#1A2340;--text2:#5A6480;--text3:#9AA3BC;
  --green:#1E7D4E;--green-lt:#E6F5EE;
  --red:#C62828;--red-lt:#FFEBEE;
  --amber:#C47A00;--amber-lt:#FFF8E1;
  --r:8px;--r2:6px;
  --font:'Segoe UI',system-ui,sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;font-size:14px}
input,select,textarea,button{font-family:inherit;font-size:14px}
a{color:inherit;text-decoration:none}
.topbar{background:var(--pri2);padding:0 20px;height:52px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,.15)}
.brand{display:flex;align-items:center;gap:8px;font-weight:700;font-size:15px;color:#fff}
.topbar-right{display:flex;align-items:center;gap:12px}
.avatar{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.4);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
.user-name{font-size:13px;font-weight:600;color:#fff}
.btn-out{display:flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;cursor:pointer;font-size:12px;padding:6px 12px;border-radius:var(--r2);transition:.15s}
.btn-out:hover{background:rgba(255,255,255,.22)}
.nav-tabs{background:var(--card);border-bottom:1px solid var(--border);display:flex;padding:0 16px;gap:2px;overflow-x:auto}
.nav-tab{display:flex;align-items:center;gap:6px;padding:11px 14px;font-size:13px;color:var(--text2);border-bottom:2px solid transparent;cursor:pointer;white-space:nowrap;transition:.15s}
.nav-tab:hover{color:var(--text)}
.nav-tab.active{color:var(--pri);border-bottom-color:var(--pri);font-weight:600}
.wrap{max-width:780px;margin:0 auto;padding:22px 16px}
.page-title{font-size:16px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);margin-bottom:14px;overflow:hidden}
.card-hd{padding:10px 16px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;letter-spacing:.5px;color:var(--pri);text-transform:uppercase}
.card-bd{padding:16px}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
.frow.c1{grid-template-columns:1fr}
.fg{display:flex;flex-direction:column;gap:4px}
label{font-size:11px;font-weight:700;letter-spacing:.3px;color:var(--text2);text-transform:uppercase}
.req{color:var(--red)}
.field{background:#F4F6FA;border:1.5px solid var(--border);border-radius:var(--r2);padding:8px 10px;color:var(--text);outline:none;width:100%;transition:.15s}
.field:focus{border-color:var(--pri);box-shadow:0 0 0 2px rgba(26,101,200,.15)}
.field[readonly]{color:var(--text3);cursor:default;background:#f8f8f8}
textarea.field{resize:vertical;min-height:64px}
select.field{cursor:pointer}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--r2);font-size:13px;font-weight:600;cursor:pointer;border:none;transition:.15s}
.btn:active{transform:scale(.98)}
.btn-pri{background:var(--pri);color:#fff}
.btn-pri:hover{background:var(--pri2)}
.btn-ghost{background:#F4F6FA;color:var(--text2);border:1px solid var(--border)}
.btn-ghost:hover{background:#e8ecf4}
.btn-danger{background:var(--red);color:#fff}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-row{display:flex;gap:8px;justify-content:flex-end;margin-top:14px}
.cuoc-box{background:var(--pri-lt);border:1.5px solid var(--pri);border-radius:var(--r);padding:14px 18px;margin-bottom:14px}
.cuoc-val{font-size:24px;font-weight:800;color:var(--pri);margin:4px 0}
.cuoc-lbl{font-size:12px;color:var(--text2)}
.cuoc-note{font-size:11px;color:var(--text3);margin-top:2px}
.bdg{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700}
.bdg-warn{background:var(--amber-lt);color:var(--amber)}
.bdg-blue{background:var(--pri-lt);color:var(--pri)}
.bdg-green{background:var(--green-lt);color:var(--green)}
.bdg-red{background:var(--red-lt);color:var(--red)}
.bdg-gray{background:#EEF0F5;color:var(--text2)}
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
.stat-box{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:12px 14px;text-align:center;cursor:pointer;transition:.15s}
.stat-box:hover,.stat-box.on{border-color:var(--pri);background:var(--pri-lt)}
.stat-box .sv{font-size:24px;font-weight:800}
.stat-box .sl{font-size:11px;color:var(--text2);margin-top:2px}
.alert{padding:10px 14px;border-radius:var(--r2);font-size:13px;margin-bottom:14px}
.alert-ok{background:var(--green-lt);color:var(--green);border:1px solid #b7dfc8}
.alert-loi{background:var(--red-lt);color:var(--red);border:1px solid #f1b8b8}
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{padding:8px 10px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.3px;color:var(--text3);border-bottom:1px solid var(--border);text-transform:uppercase}
.tbl td{padding:10px;border-bottom:1px solid var(--border);vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tr:hover td{background:#F4F6FA;cursor:pointer}
.empty{text-align:center;padding:36px 20px;color:var(--text3)}
.empty .ei{font-size:34px;margin-bottom:8px}
.fbar{display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap}
.mbg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:500;align-items:center;justify-content:center}
.mbg.open{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:22px;max-width:500px;width:90%;max-height:85vh;overflow-y:auto}
.modal h3{font-size:15px;font-weight:700;margin-bottom:14px}
.mrow{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px}
.mrow:last-of-type{border-bottom:none}
.mk{color:var(--text2);font-size:12px}.mv{font-weight:600;text-align:right;max-width:60%}
.rtbl{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:12px}
.rtbl th{padding:8px 10px;font-size:11px;font-weight:700;color:var(--text3);border-bottom:1px solid var(--border);text-align:left;text-transform:uppercase}
.rtbl td{padding:8px 10px;border-bottom:1px solid var(--border)}
.rtbl tr:last-child td{border-bottom:none}
.formula{background:#F4F6FA;border:1px solid var(--border);border-radius:var(--r2);padding:10px 14px;font-size:12px;color:var(--text2);line-height:1.8}
@media(max-width:560px){.frow{grid-template-columns:1fr}.stat-row{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>

<div class="topbar">
  <div class="brand">🚛 Vận Tải Hàng Hóa</div>
  <div class="topbar-right">
    <div style="display:flex;align-items:center;gap:8px">
      <div class="avatar"><?=htmlspecialchars($initials)?></div>
      <span class="user-name"><?=htmlspecialchars($full_name)?></span>
    </div>
    <a href="logout.php" class="btn-out">🚪 Đăng xuất</a>
  </div>
</div>

<div class="nav-tabs">
  <a href="?page=tao-don"   class="nav-tab <?=$page==='tao-don'?'active':''?>">⚙️ Tạo đơn hàng</a>
  <a href="?page=lich-su"   class="nav-tab <?=$page==='lich-su'?'active':''?>">☰ Lịch sử</a>
  <a href="?page=tra-cuu"   class="nav-tab <?=$page==='tra-cuu'?'active':''?>">🔍 Tra cứu đơn</a>
  <a href="?page=tinh-cuoc" class="nav-tab <?=$page==='tinh-cuoc'?'active':''?>">🧮 Tính cước phí</a>
  <a href="?page=tai-khoan" class="nav-tab <?=$page==='tai-khoan'?'active':''?>">👤 Tài khoản</a>
</div>

<?php if ($msg): ?>
<div class="wrap" style="padding-bottom:0"><div class="alert alert-<?=$msg_type?>"><?=htmlspecialchars($msg)?></div></div>
<?php endif; ?>

<div class="wrap">

<?php if ($page==='tao-don'): ?>
<div class="page-title">⚙️ Tạo đơn hàng mới</div>
<form method="post" id="frmDon">
<input type="hidden" name="action" value="tao_don">
<input type="hidden" name="cuoc_phi_tt" id="cuoc_phi_tt" value="0">
<div class="card">
  <div class="card-hd">Thông tin khách hàng</div>
  <div class="card-bd">
    <p style="font-size:11px;color:var(--red);margin-bottom:12px">(*) Thông tin bắt buộc</p>
    <div class="frow">
      <div class="fg"><label>Họ và tên <span class="req">*</span></label>
        <input type="text" name="ho_ten" class="field" value="<?=htmlspecialchars($u['full_name']??'')?>" required></div>
      <div class="fg"><label>Số điện thoại <span class="req">*</span></label>
        <input type="tel" name="sdt" class="field" value="<?=htmlspecialchars($u['phone']??'')?>" required></div>
    </div>
    <div class="frow">
      <div class="fg"><label>Email</label>
        <input type="email" class="field" value="<?=htmlspecialchars($email)?>" readonly></div>
      <div class="fg"><label>Địa chỉ <span class="req">*</span></label>
        <input type="text" name="dia_chi" class="field" placeholder="Địa chỉ" required></div>
    </div>
    <div class="frow">
      <div class="fg"><label>CMND/CCCD <span class="req">*</span></label>
        <input type="text" name="cmnd" class="field" placeholder="Số CMND/CCCD" required></div>
      <div class="fg"><label>Tên công ty (nếu có)</label>
        <input type="text" name="ten_cty" class="field" placeholder="Nếu có"></div>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-hd">Chi tiết đơn hàng</div>
  <div class="card-bd">
    <div class="frow">
      <div class="fg"><label>Họ tên người gửi <span class="req">*</span></label>
        <input type="text" name="ten_gui" class="field" required></div>
      <div class="fg"><label>Họ tên người nhận <span class="req">*</span></label>
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
    <div class="frow">
      <div class="fg"><label>Địa chỉ lấy hàng <span class="req">*</span></label>
        <input type="text" name="dia_chi_gui" class="field" required></div>
      <div class="fg"><label>Địa chỉ giao hàng <span class="req">*</span></label>
        <input type="text" name="dia_chi_nhan" class="field" required></div>
    </div>
    <div class="frow">
      <div class="fg"><label>Tên hàng hóa <span class="req">*</span></label>
        <input type="text" name="ten_hang" class="field" required></div>
      <div class="fg"><label>Ngày gửi đơn <span class="req">*</span></label>
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
          <option value="duong_bo">Đường bộ (xe tải) — 1.500đ/kg</option>
          <option value="duong_bien">Đường biển — 1.200đ/kg</option>
          <option value="hang_khong">Hàng không — 8.000đ/kg</option>
          <option value="duong_sat">Đường sắt — 900đ/kg</option>
        </select></div>
      <div class="fg"><label>Phương thức thanh toán</label>
        <select name="thanh_toan" class="field">
          <option value="tien_mat">Tiền mặt</option>
          <option value="chuyen_khoan">Chuyển khoản</option>
          <option value="the_tin_dung">Thẻ tín dụng</option>
          <option value="vi_dien_tu">Ví điện tử</option>
        </select></div>
    </div>
    <div class="fg" style="margin-bottom:12px"><label>Thể tích kiện hàng (m³) — Tùy chọn</label>
      <input type="number" name="the_tich" id="the_tich" class="field" min="0" step="0.001" placeholder="VD: 0.5" oninput="calc()"></div>
    <div class="fg"><label>Ghi chú</label>
      <textarea name="ghi_chu" class="field" placeholder="Thông tin thêm..."></textarea></div>
  </div>
</div>
<div class="cuoc-box">
  <div class="cuoc-lbl">🧾 Dự tính cước phí (giá thực tế 2025)</div>
  <div class="cuoc-val" id="cuoc-display">Nhập khối lượng để tính tự động</div>
  <div class="cuoc-note" id="cuoc-note"></div>
</div>
<div class="btn-row">
  <a href="?page=lich-su" class="btn btn-ghost">☰ Xem lịch sử</a>
  <button type="button" class="btn btn-ghost" onclick="xemTruoc()">👁 Xem trước</button>
  <button type="submit" class="btn btn-pri">+ Thêm đơn hàng</button>
</div>
</form>

<?php elseif ($page==='lich-su'): ?>
<div class="page-title">☰ Lịch sử đơn hàng</div>
<?php if (isset($_GET['tao_ok'])): ?>
  <div class="alert alert-ok">✅ Tạo đơn thành công! Mã đơn: <b><?=htmlspecialchars($_GET['ma']??'')?></b></div>
<?php endif; ?>
<div class="stat-row">
  <div class="stat-box <?=$filter_tt==='tat-ca'?'on':''?>" onclick="location='?page=lich-su'">
    <div class="sv"><?=$tong?></div><div class="sl">Tổng đơn</div></div>
  <div class="stat-box <?=$filter_tt==='cho-xu-ly'?'on':''?>" onclick="location='?page=lich-su&tt=cho-xu-ly'">
    <div class="sv"><?=$cho?></div><div class="sl">Chờ duyệt</div></div>
  <div class="stat-box <?=$filter_tt==='da-giao'?'on':''?>" onclick="location='?page=lich-su&tt=da-giao'">
    <div class="sv"><?=$xong?></div><div class="sl">Hoàn thành</div></div>
  <div class="stat-box <?=$filter_tt==='da-huy'?'on':''?>" onclick="location='?page=lich-su&tt=da-huy'">
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
    <?php if (empty($don_list)): ?>
      <div class="empty"><div class="ei">📦</div><p style="margin-bottom:12px">Chưa có đơn hàng nào.</p>
        <a href="?page=tao-don" class="btn btn-pri btn-sm">Tạo đơn ngay</a></div>
    <?php else: ?>
    <table class="tbl">
      <thead><tr><th>Mã đơn</th><th>Hàng hóa</th><th>Ngày tạo</th><th>Cước phí</th><th>Trạng thái</th></tr></thead>
      <tbody>
      <?php foreach ($don_list as $d): ?>
        <tr onclick="moChiTiet(<?=htmlspecialchars(json_encode($d,JSON_UNESCAPED_UNICODE))?>)">
          <td style="font-family:monospace;font-size:12px;color:var(--pri)"><?=htmlspecialchars($d['ma_don']??'')?></td>
          <td><div style="font-weight:600"><?=htmlspecialchars($d['ten_hang']??'')?></div>
              <div style="font-size:11px;color:var(--text2)"><?=htmlspecialchars($d['dia_chi_gui']??'')?> → <?=htmlspecialchars($d['dia_chi_nhan']??'')?></div></td>
          <td style="font-size:12px;color:var(--text2)"><?=date('d/m/Y H:i',strtotime($d['ngay_tao']??'now'))?></td>
          <td style="font-weight:700;color:var(--pri)"><?=fmtVND($d['doanh_thu']??0)?></td>
          <td><span class="bdg <?=mauTT($d['trang_thai']??'')?>"><?=tenTT($d['trang_thai']??'')?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<form method="post" id="frmHD" style="display:none">
  <input type="hidden" name="action" id="hdAction">
  <input type="hidden" name="ma_don" id="hdMaDon">
</form>
<div class="mbg" id="modalSua">
  <div class="modal">
    <h3>✏️ Chỉnh sửa đơn hàng</h3>
    <p style="font-size:12px;color:var(--text2);margin-bottom:14px">⚠️ Chỉ chỉnh sửa được đơn đang chờ duyệt.</p>
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
        <div class="fg"><label>Địa chỉ lấy</label><input type="text" name="dia_chi_gui" id="suaDCGui" class="field"></div>
        <div class="fg"><label>Địa chỉ giao</label><input type="text" name="dia_chi_nhan" id="suaDCNhan" class="field"></div>
      </div>
      <div class="fg" style="margin-bottom:12px"><label>Tên hàng hóa</label><input type="text" name="ten_hang" id="suaTenHang" class="field"></div>
      <div class="fg" style="margin-bottom:12px"><label>Ghi chú</label><textarea name="ghi_chu" id="suaGhiChu" class="field"></textarea></div>
      <div class="btn-row">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalSua').classList.remove('open')">Hủy</button>
        <button type="submit" class="btn btn-pri">💾 Lưu</button>
      </div>
    </form>
  </div>
</div>

<?php elseif ($page==='tra-cuu'): ?>
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
      <input type="text" id="tc_q" class="field" placeholder="Nhập từ khóa..." oninput="timKiem()">
      <button class="btn btn-pri btn-sm" onclick="timKiem()">🔍 Tìm</button>
    </div>
    <div id="tc_area"><div class="empty"><div class="ei">🔍</div><p>Nhập từ khóa để tra cứu</p></div></div>
  </div>
</div>
<script>
const allDon=<?=json_encode(array_values($don_all??[]),JSON_UNESCAPED_UNICODE)?>;
const mauMap={cho_duyet:'bdg-warn',dang_giao:'bdg-blue',hoan_thanh:'bdg-green',da_thanh_toan:'bdg-green',huy:'bdg-red'};
const tenMap={cho_duyet:'Chờ duyệt',dang_giao:'Đang giao',hoan_thanh:'Hoàn thành',da_thanh_toan:'Đã thanh toán',huy:'Đã hủy'};
function timKiem(){
  const type=document.getElementById('tc_type').value;
  const q=document.getElementById('tc_q').value.trim().toLowerCase();
  const area=document.getElementById('tc_area');
  if(!q){area.innerHTML='<div class="empty"><div class="ei">🔍</div><p>Nhập từ khóa để tra cứu</p></div>';return;}
  const found=allDon.filter(d=>(d[type]||'').toLowerCase().includes(q));
  if(!found.length){area.innerHTML='<div class="empty"><div class="ei">😐</div><p>Không tìm thấy đơn hàng nào.</p></div>';return;}
  let html='<table class="tbl"><thead><tr><th>Mã đơn</th><th>Hàng hóa</th><th>Ngày tạo</th><th>Cước phí</th><th>Trạng thái</th></tr></thead><tbody>';
  found.forEach(d=>{
    const mau=mauMap[d.trang_thai]||'bdg-gray';
    const ten=tenMap[d.trang_thai]||d.trang_thai;
    html+=`<tr onclick="moChiTiet(${JSON.stringify(d).replace(/'/g,'&#39;')})">
      <td style="font-family:monospace;font-size:12px;color:var(--pri)">${d.ma_don||''}</td>
      <td><div style="font-weight:600">${d.ten_hang||''}</div><div style="font-size:11px;color:var(--text2)">${d.dia_chi_gui||''} → ${d.dia_chi_nhan||''}</div></td>
      <td style="font-size:12px;color:var(--text2)">${(d.ngay_tao||'').slice(0,16)}</td>
      <td style="font-weight:700;color:var(--pri)">${Number(d.doanh_thu||0).toLocaleString('vi-VN')} đ</td>
      <td><span class="bdg ${mau}">${ten}</span></td>
    </tr>`;
  });
  html+='</tbody></table>';
  area.innerHTML=html;
}
</script>

<?php elseif ($page==='tinh-cuoc'): ?>
<div class="page-title">🧮 Tính cước phí vận chuyển</div>
<div class="card">
  <div class="card-hd">Nhập thông tin</div>
  <div class="card-bd">
    <div class="frow">
      <div class="fg"><label>Phương thức vận chuyển</label>
        <select id="tc_pt" class="field" onchange="calcTC()">
          <option value="duong_bo">Đường bộ (xe tải) — 1.500đ/kg</option>
          <option value="duong_bien">Đường biển — 1.200đ/kg</option>
          <option value="hang_khong">Hàng không — 8.000đ/kg</option>
          <option value="duong_sat">Đường sắt — 900đ/kg</option>
        </select></div>
      <div class="fg"><label>Loại hàng</label>
        <select id="tc_loai" class="field" onchange="calcTC()">
          <option value="thuong">Hàng thường</option>
          <option value="de_vo">Hàng dễ vỡ</option>
          <option value="lanh">Hàng lạnh/đông lạnh</option>
          <option value="qua_kho">Hàng quá khổ</option>
          <option value="nguy_hiem">Hàng nguy hiểm</option>
        </select></div>
    </div>
    <div class="fg" style="margin-bottom:12px"><label>Khối lượng thực tế (kg)</label>
      <input type="number" id="tc_kl" class="field" min="0" value="0" oninput="calcTC()"></div>
    <div class="fg" style="margin-bottom:12px"><label>Thể tích (m³) — Tùy chọn</label>
      <input type="number" id="tc_tv" class="field" min="0" step="0.001" placeholder="VD: 0.5" oninput="calcTC()"></div>
    <div class="cuoc-box" style="margin-bottom:0">
      <div class="cuoc-lbl">💰 Cước phí ước tính</div>
      <div class="cuoc-val" id="tc_result">Điền thông tin để tính</div>
      <div class="cuoc-note" id="tc_note"></div>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-hd">Bảng đơn giá tham khảo (2025)</div>
  <div class="card-bd">
    <table class="rtbl">
      <thead><tr><th>Phương thức</th><th>Đơn giá/kg</th><th>Ghi chú</th></tr></thead>
      <tbody>
        <tr><td>Đường bộ (xe tải)</td><td style="font-weight:700;color:var(--pri)">1.500 đ/kg</td><td style="color:var(--text2)">Hàng lẻ ghép chuyến, 2–5 ngày</td></tr>
        <tr><td>Đường biển</td><td style="font-weight:700;color:var(--pri)">1.200 đ/kg</td><td style="color:var(--text2)">Rẻ hơn đường bộ, thời gian dài hơn</td></tr>
        <tr><td>Hàng không</td><td style="font-weight:700;color:var(--pri)">8.000 đ/kg</td><td style="color:var(--text2)">Nhanh nhất, phù hợp hàng nhỏ giá trị cao</td></tr>
        <tr><td>Đường sắt</td><td style="font-weight:700;color:var(--pri)">900 đ/kg</td><td style="color:var(--text2)">Rẻ nhất, thời gian dài</td></tr>
      </tbody>
    </table>
    <table class="rtbl">
      <thead><tr><th>Loại hàng</th><th>Hệ số</th><th>Lý do</th></tr></thead>
      <tbody>
        <tr><td>Hàng thường</td><td>×1.0</td><td style="color:var(--text2)">Không phụ phí</td></tr>
        <tr><td>Hàng dễ vỡ</td><td>×1.2</td><td style="color:var(--text2)">+20% đóng gói đặc biệt</td></tr>
        <tr><td>Hàng lạnh</td><td>×1.25</td><td style="color:var(--text2)">+25% xe lạnh chuyên dụng</td></tr>
        <tr><td>Hàng quá khổ</td><td>×1.6</td><td style="color:var(--text2)">+60% xe chuyên dụng + giấy phép</td></tr>
        <tr><td>Hàng nguy hiểm</td><td>×1.8</td><td style="color:var(--text2)">+80% chứng từ, bảo hiểm</td></tr>
      </tbody>
    </table>
    <div class="formula">
      <b>Công thức:</b> Cước = Đơn giá/kg × KL tính cước × Hệ số loại hàng<br>
      <b>KL tính cước</b> = max(KL thực, KL thể tích) &nbsp;|&nbsp; KL thể tích = Thể tích (m³) × 200
    </div>
  </div>
</div>

<?php elseif ($page==='tai-khoan'): ?>
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
<?php endif; ?>
</div>

<!-- Modal chi tiết đơn -->
<div class="mbg" id="modalDon">
  <div class="modal">
    <h3>📋 Chi tiết đơn hàng</h3>
    <div id="modalDonContent"></div>
    <div class="btn-row" style="margin-top:14px">
      <button class="btn btn-ghost" onclick="document.getElementById('modalDon').classList.remove('open')">Đóng</button>
      <button class="btn btn-ghost" id="btnSua" onclick="moSua()" style="display:none">✏️ Chỉnh sửa</button>
      <button class="btn btn-danger" id="btnHuy" onclick="huyDon()" style="display:none">🚫 Hủy đơn</button>
    </div>
  </div>
</div>

<!-- Modal xem trước -->
<div class="mbg" id="modalXT">
  <div class="modal">
    <h3>📋 Xác nhận thông tin đơn hàng</h3>
    <div id="modalXTContent"></div>
    <div class="btn-row" style="margin-top:14px">
      <button class="btn btn-ghost" onclick="document.getElementById('modalXT').classList.remove('open')">✕ Đóng</button>
      <button class="btn btn-pri" onclick="document.getElementById('frmDon').submit()">✅ Xác nhận gửi đơn</button>
    </div>
  </div>
</div>

<script>
const GIA={duong_bo:1500,duong_bien:1200,hang_khong:8000,duong_sat:900};
const HS={thuong:1.0,de_vo:1.2,lanh:1.25,qua_kho:1.6,nguy_hiem:1.8};
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
  const c=klT*GIA[pt]*HS[lh];
  disp.textContent=fmtVND(c);
  if(note)note.textContent=`KL tính cước: ${klT.toFixed(2)} kg × ${GIA[pt].toLocaleString('vi-VN')} đ/kg × ${HS[lh]}`;
  if(hid)hid.value=Math.round(c);
}

function calcTC(){
  const kl=parseFloat(document.getElementById('tc_kl')?.value)||0;
  const tv=parseFloat(document.getElementById('tc_tv')?.value)||0;
  const pt=document.getElementById('tc_pt')?.value||'duong_bo';
  const lh=document.getElementById('tc_loai')?.value||'thuong';
  const res=document.getElementById('tc_result');
  const note=document.getElementById('tc_note');
  if(!res)return;
  const klT=calcKL(kl,tv);
  if(klT<=0){res.textContent='Điền thông tin để tính';if(note)note.textContent='';return;}
  const c=klT*GIA[pt]*HS[lh];
  res.textContent=fmtVND(c);
  if(note)note.textContent=`KL tính cước: ${klT.toFixed(2)} kg × ${GIA[pt].toLocaleString('vi-VN')} đ/kg × ${HS[lh]}`;
}

function xemTruoc(){
  const g=n=>document.querySelector(`[name="${n}"]`)?.value||'—';
  const ptM={duong_bo:'Đường bộ',duong_bien:'Đường biển',hang_khong:'Hàng không',duong_sat:'Đường sắt'};
  const lhM={thuong:'Hàng thường',de_vo:'Hàng dễ vỡ',lanh:'Hàng lạnh',qua_kho:'Hàng quá khổ',nguy_hiem:'Hàng nguy hiểm'};
  const ttM={tien_mat:'Tiền mặt',chuyen_khoan:'Chuyển khoản',the_tin_dung:'Thẻ tín dụng',vi_dien_tu:'Ví điện tử'};
  const cuoc=document.getElementById('cuoc-display')?.textContent||'—';
  const rows=[
    ['Khách hàng',g('ho_ten')],['SĐT',g('sdt')],
    ['Người gửi',g('ten_gui')],['Email gửi',g('email_gui')],['Địa chỉ lấy',g('dia_chi_gui')],
    ['Người nhận',g('ten_nhan')],['Email nhận',g('email_nhan')],['Địa chỉ giao',g('dia_chi_nhan')],
    ['Tên hàng',g('ten_hang')],['Loại hàng',lhM[g('loai_hang')]||g('loai_hang')],
    ['Khối lượng',g('khoi_luong')+' kg'],
    ['Phương thức',ptM[g('phuong_thuc')]||g('phuong_thuc')],
    ['Thanh toán',ttM[g('thanh_toan')]||g('thanh_toan')],
    ['Ngày gửi',g('ngay_gui')],
    ['Cước phí ước tính',`<b style="color:var(--pri)">${cuoc}</b>`],
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
  const mauM={cho_duyet:'bdg-warn',dang_giao:'bdg-blue',hoan_thanh:'bdg-green',da_thanh_toan:'bdg-green',huy:'bdg-red'};
  const tenM={cho_duyet:'Chờ duyệt',dang_giao:'Đang giao',hoan_thanh:'Hoàn thành',da_thanh_toan:'Đã thanh toán',huy:'Đã hủy'};
  const mau=mauM[d.trang_thai]||'bdg-gray';
  const rows=[
    ['Mã đơn',`<span style="font-family:monospace;color:var(--pri)">${d.ma_don||'—'}</span>`],
    ['Ngày tạo',(d.ngay_tao||'').slice(0,16)],
    ['Tên hàng',d.ten_hang||'—'],['Loại hàng',lhM[d.loai_hang]||d.loai_hang||'—'],
    ['Khối lượng',(d.khoi_luong||0)+' kg'],
    ['Phương thức',ptM[d.phuong_thuc]||d.phuong_thuc||'—'],
    ['Thanh toán',ttM[d.thanh_toan]||d.thanh_toan||'—'],
    ['Người gửi',(d.ten_gui||'—')+' — '+(d.sdt_gui||'')],
    ['Địa chỉ lấy',d.dia_chi_gui||'—'],
    ['Người nhận',(d.ten_nhan||'—')+' — '+(d.sdt_nhan||'')],
    ['Địa chỉ giao',d.dia_chi_nhan||'—'],
    ['Cước phí',`<b style="color:var(--pri)">${Number(d.doanh_thu||0).toLocaleString('vi-VN')} đ</b>`],
    ['Trạng thái',`<span class="bdg ${mau}">${tenM[d.trang_thai]||d.trang_thai}</span>`],
    ['Ghi chú',d.ghi_chu||'—'],
  ];
  document.getElementById('modalDonContent').innerHTML=rows.map(([k,v])=>`<div class="mrow"><span class="mk">${k}</span><span class="mv">${v}</span></div>`).join('');
  const canEdit=d.trang_thai==='cho_duyet';
  document.getElementById('btnSua').style.display=canEdit?'inline-flex':'none';
  document.getElementById('btnHuy').style.display=canEdit?'inline-flex':'none';
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
  document.getElementById('suaGhiChu').value=d.ghi_chu||'';
  document.getElementById('modalDon').classList.remove('open');
  document.getElementById('modalSua').classList.add('open');
}

document.querySelectorAll('.mbg').forEach(m=>{
  m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');});
});
</script>
</body>
</html>
