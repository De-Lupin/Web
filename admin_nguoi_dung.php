<?php
session_start(); require 'config.php'; require_role(['admin']);
$msg = '';

// Thêm user
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['them_user'])) {
    $un   = trim($_POST['username']  ?? '');
    $pw   = trim($_POST['password']  ?? '');
    $email= trim($_POST['email']     ?? '');
    $fn   = trim($_POST['full_name'] ?? '');
    $phone= trim($_POST['phone']     ?? '');
    $role = $_POST['role']           ?? 'khachhang';
    if ($un && $pw && $email && $fn) {
        $stmt = $conn->prepare("INSERT INTO users (username,password,email,full_name,phone,role) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("ssssss",$un,$pw,$email,$fn,$phone,$role);
        if ($stmt->execute()) $msg=['type'=>'success','text'=>"Đã tạo tài khoản <strong>$un</strong> thành công!"];
        else $msg=['type'=>'danger','text'=>'Lỗi: '.($stmt->error=='Duplicate entry'?'Username hoặc email đã tồn tại!':$stmt->error)];
        $stmt->close();
    } else $msg=['type'=>'danger','text'=>'Vui lòng nhập đầy đủ thông tin!'];
}

// Sửa user
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['sua_user'])) {
    $id   = (int)$_POST['user_id'];
    $fn   = trim($_POST['full_name'] ?? '');
    $email= trim($_POST['email']     ?? '');
    $phone= trim($_POST['phone']     ?? '');
    $role = $_POST['role']           ?? 'khachhang';
    $act  = (int)($_POST['is_active']??1);
    $pw   = trim($_POST['new_password']??'');
    if ($pw) {
        $conn->query("UPDATE users SET full_name='".mysqli_real_escape_string($conn,$fn)."',email='".mysqli_real_escape_string($conn,$email)."',phone='".mysqli_real_escape_string($conn,$phone)."',role='$role',is_active=$act,password='" . mysqli_real_escape_string($conn,$pw) . "' WHERE id=$id");
    } else {
        $conn->query("UPDATE users SET full_name='".mysqli_real_escape_string($conn,$fn)."',email='".mysqli_real_escape_string($conn,$email)."',phone='".mysqli_real_escape_string($conn,$phone)."',role='$role',is_active=$act WHERE id=$id");
    }
    $msg=['type'=>'success','text'=>'Đã cập nhật tài khoản thành công!'];
}

// Khoá/Mở khoá
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_active'])) {
    $id  = (int)$_POST['user_id'];
    $act = (int)$_POST['current_active'];
    $new = $act ? 0 : 1;
    $conn->query("UPDATE users SET is_active=$new WHERE id=$id AND role!='admin'");
    $msg=['type'=>'success','text'=>$new?'Đã mở khóa tài khoản!':'Đã khóa tài khoản!'];
}

// Xóa user
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['xoa_user'])) {
    $id = (int)$_POST['user_id'];
    $conn->query("DELETE FROM users WHERE id=$id AND role!='admin'");
    $msg=['type'=>'success','text'=>'Đã xóa tài khoản!'];
}

// Lọc & tìm kiếm
$search = trim($_GET['search'] ?? '');
$role_f = $_GET['role']        ?? '';
$status = $_GET['status']      ?? '';
$page   = max(1,(int)($_GET['page']??1));
$per    = 10; $offset = ($page-1)*$per;

$w = "WHERE role!='admin'";
if ($search) $w .= " AND (username LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR full_name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR email LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($role_f) $w .= " AND role='".mysqli_real_escape_string($conn,$role_f)."'";
if ($status!=='') $w .= " AND is_active=".($status==='1'?1:0);

$total = $conn->query("SELECT COUNT(*) AS c FROM users $w")->fetch_assoc()['c'];
$pages = max(1,ceil($total/$per));
$rows  = $conn->query("SELECT * FROM users $w ORDER BY created_at DESC LIMIT $per OFFSET $offset");

$role_map=['admin'=>['l'=>'Admin','c'=>'b-admin'],'dieuphoI'=>['l'=>'Điều Phối','c'=>'b-dieuphoI'],'khachhang'=>['l'=>'Khách Hàng','c'=>'b-khachhang']];

$active = 'nguoi_dung'; require 'sidebar_admin.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản Lý Người Dùng</title>
<link rel="stylesheet" href="admin_layout.css">
<style>
.filter-row{display:flex;gap:8px;flex-wrap:wrap;flex:1}
.filter-row select,.filter-row input{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none}
.filter-row select:focus,.filter-row input:focus{border-color:var(--primary)}
</style>
</head><body>
<div class="app">
<?php require 'sidebar_admin.php'; ?>

<main class="main">
<div class="topbar">
    <div>
        <div class="topbar-title">👥 Quản Lý Người Dùng</div>
        <div class="breadcrumb"><a href="admin_dashboard.php">Dashboard</a> › Người dùng</div>
    </div>
    <div class="user-chip">
        <div class="chip-avatar"><?= mb_strtoupper(mb_substr($_SESSION['full_name']??'A',0,1)) ?></div>
        <div><div class="chip-name"><?= htmlspecialchars($_SESSION['full_name']??'Admin') ?></div>
        <div class="chip-role">Super Admin</div></div>
    </div>
</div>
<div class="content">
    <?php if(!empty($msg)): ?><div class="alert alert-<?=$msg['type']?>"><?=$msg['text']?></div><?php endif; ?>

    <!-- Stat -->
    <div class="stat-cards" style="margin-bottom:20px">
        <div class="stat-card"><div class="sc-icon">👥</div><div class="sc-label">Tổng</div>
            <div class="sc-value"><?=$conn->query("SELECT COUNT(*) AS c FROM users WHERE role!='admin'")->fetch_assoc()['c']?></div><div class="sc-sub">Tài khoản</div></div>
        <div class="stat-card" style="border-top-color:#27ae60"><div class="sc-icon">📋</div><div class="sc-label">Điều Phối</div>
            <div class="sc-value" style="color:#27ae60"><?=$conn->query("SELECT COUNT(*) AS c FROM users WHERE role='dieuphoI'")->fetch_assoc()['c']?></div><div class="sc-sub">Tài khoản</div></div>
        <div class="stat-card" style="border-top-color:#2980b9"><div class="sc-icon">🏢</div><div class="sc-label">Khách Hàng</div>
            <div class="sc-value" style="color:#2980b9"><?=$conn->query("SELECT COUNT(*) AS c FROM users WHERE role='khachhang'")->fetch_assoc()['c']?></div><div class="sc-sub">Tài khoản</div></div>
        <div class="stat-card" style="border-top-color:#e74c3c"><div class="sc-icon">🔒</div><div class="sc-label">Đã Khóa</div>
            <div class="sc-value" style="color:#e74c3c"><?=$conn->query("SELECT COUNT(*) AS c FROM users WHERE is_active=0 AND role!='admin'")->fetch_assoc()['c']?></div><div class="sc-sub">Tài khoản</div></div>
    </div>

    <div class="page-header">
        <form method="GET" class="filter-row">
            <div class="search-box" style="flex:1;min-width:200px">
                🔍<input type="text" name="search" placeholder="Tìm username, tên, email..." value="<?=htmlspecialchars($search)?>">
            </div>
            <select name="role" onchange="this.form.submit()">
                <option value="">— Tất cả role —</option>
                <option value="dieuphoI" <?=$role_f==='dieuphoI'?'selected':''?>>Điều Phối</option>
                <option value="khachhang" <?=$role_f==='khachhang'?'selected':''?>>Khách Hàng</option>
            </select>
            <select name="status" onchange="this.form.submit()">
                <option value="">— Tất cả trạng thái —</option>
                <option value="1" <?=$status==='1'?'selected':''?>>Đang hoạt động</option>
                <option value="0" <?=$status==='0'?'selected':''?>>Đã khóa</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="admin_nguoi_dung.php" class="btn btn-ghost">Xóa lọc</a>
        </form>
        <button class="btn btn-primary" onclick="document.getElementById('modal_them').classList.add('open')">➕ Thêm Tài Khoản</button>
    </div>

    <div class="table-wrap">
        <div class="table-scroll">
        <table>
            <thead><tr><th>#</th><th>Tài khoản</th><th>Họ tên</th><th>Email</th><th>Điện thoại</th><th>Role</th><th>Ngày tạo</th><th>Đăng nhập cuối</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php if($rows && $rows->num_rows>0): $stt=($page-1)*$per+1;
                while($r=$rows->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--muted)"><?=$stt++?></td>
                <td><strong><?=htmlspecialchars($r['username'])?></strong></td>
                <td><?=htmlspecialchars($r['full_name'])?></td>
                <td style="font-size:12px"><?=htmlspecialchars($r['email'])?></td>
                <td style="font-size:12px"><?=htmlspecialchars($r['phone']??'—')?></td>
                <td><span class="badge <?=$role_map[$r['role']]['c']??''?>"><?=$role_map[$r['role']]['l']??$r['role']?></span></td>
                <td style="font-size:12px;color:var(--muted)"><?=date('d/m/Y',strtotime($r['created_at']))?></td>
                <td style="font-size:12px;color:var(--muted)"><?=$r['last_login']?date('d/m H:i',strtotime($r['last_login'])):'Chưa đăng nhập'?></td>
                <td>
                    <?php if($r['is_active']): ?>
                        <span class="badge b-active">✅ Hoạt động</span>
                    <?php else: ?>
                        <span class="badge b-inactive">🔒 Đã khóa</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:5px">
                        <button class="btn btn-warning btn-sm" onclick="openSua(<?=htmlspecialchars(json_encode($r))?>)" title="Sửa">✏️</button>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="user_id" value="<?=$r['id']?>">
                            <input type="hidden" name="current_active" value="<?=$r['is_active']?>">
                            <button type="submit" name="toggle_active" class="btn btn-sm <?=$r['is_active']?'btn-danger':'btn-success'?>" title="<?=$r['is_active']?'Khóa':'Mở khóa'?>">
                                <?=$r['is_active']?'🔒':'🔓'?>
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Xóa tài khoản này?')" style="display:inline">
                            <input type="hidden" name="user_id" value="<?=$r['id']?>">
                            <button type="submit" name="xoa_user" class="btn btn-danger btn-sm" title="Xóa">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="10"><div class="empty-state"><div class="ei">👥</div><p>Không tìm thấy tài khoản nào.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        <?php if($pages>1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$pages;$i++): ?>
            <a href="?page=<?=$i?>&search=<?=urlencode($search)?>&role=<?=urlencode($role_f)?>&status=<?=urlencode($status)?>"
               class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</main>
</div>

<!-- Modal thêm user -->
<div class="modal-overlay" id="modal_them">
<div class="modal-box">
    <div class="modal-header"><h3>➕ Thêm Tài Khoản Mới</h3>
    <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('open')">✕</button></div>
    <form method="POST">
        <div class="form-grid">
            <div class="field"><label>Username *</label><input type="text" name="username" required placeholder="Nhập username"></div>
            <div class="field"><label>Mật khẩu *</label><input type="password" name="password" required placeholder="Tối thiểu 6 ký tự"></div>
            <div class="field span2"><label>Họ và tên *</label><input type="text" name="full_name" required placeholder="Nhập họ tên đầy đủ"></div>
            <div class="field"><label>Email *</label><input type="email" name="email" required placeholder="example@email.com"></div>
            <div class="field"><label>Điện thoại</label><input type="text" name="phone" placeholder="09xxxxxxxx"></div>
            <div class="field span2"><label>Phân quyền</label>
                <select name="role">
                    <option value="dieuphoI">📋 Điều Phối Viên</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('open')">Hủy</button>
            <button type="submit" name="them_user" class="btn btn-primary">💾 Tạo Tài Khoản</button>
        </div>
    </form>
</div>
</div>

<!-- Modal sửa user -->
<div class="modal-overlay" id="modal_sua">
<div class="modal-box">
    <div class="modal-header"><h3>✏️ Chỉnh Sửa Tài Khoản</h3>
    <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('open')">✕</button></div>
    <form method="POST">
        <input type="hidden" name="user_id" id="sua_id">
        <div style="background:#f8f9fa;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px">
            Username: <strong id="sua_un"></strong>
        </div>
        <div class="form-grid">
            <div class="field span2"><label>Họ và tên</label><input type="text" name="full_name" id="sua_fn" required></div>
            <div class="field"><label>Email</label><input type="email" name="email" id="sua_email" required></div>
            <div class="field"><label>Điện thoại</label><input type="text" name="phone" id="sua_phone"></div>
            <div class="field"><label>Phân quyền</label>
                <select name="role" id="sua_role">
                    <option value="dieuphoI">📋 Điều Phối Viên</option>
                    <option value="khachhang">🏢 Khách Hàng</option>
                </select>
            </div>
            <div class="field"><label>Trạng thái</label>
                <select name="is_active" id="sua_act">
                    <option value="1">✅ Hoạt động</option>
                    <option value="0">🔒 Khóa</option>
                </select>
            </div>
            <div class="field span2"><label>Mật khẩu mới (để trống = không đổi)</label>
                <input type="password" name="new_password" placeholder="Nhập mật khẩu mới nếu muốn đổi">
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('open')">Hủy</button>
            <button type="submit" name="sua_user" class="btn btn-primary">💾 Lưu Thay Đổi</button>
        </div>
    </form>
</div>
</div>

<script>
function openSua(u){
    document.getElementById('sua_id').value    = u.id;
    document.getElementById('sua_un').textContent = u.username;
    document.getElementById('sua_fn').value    = u.full_name;
    document.getElementById('sua_email').value = u.email;
    document.getElementById('sua_phone').value = u.phone||'';
    document.getElementById('sua_role').value  = u.role;
    document.getElementById('sua_act').value   = u.is_active;
    document.getElementById('modal_sua').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(m=>{
    m.addEventListener('click',e=>{ if(e.target===m) m.classList.remove('open'); });
});
</script>
</body></html>