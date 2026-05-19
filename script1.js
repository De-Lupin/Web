// DỮ LIỆU
let database = [];
let editingIndex = null;

// KIỂM TRA HỢP LỆ (validation gốc)
function validationGoc() {
    let isValid = true;
    document.querySelectorAll("input, textarea").forEach(el => el.classList.remove("error"));

    const requiredIds = [
        "ten", "sdt", "email", "diachi", "cccd",
        "nguoigui_ten", "nguoigui_sdt", "nguoigui_diachi",
        "nguoinhan_ten", "nguoinhan_sdt", "nguoinhan_diachi",
        "tenhang", "ngaygui"
    ];

    requiredIds.forEach(id => {
        let el = document.getElementById(id);
        if (el && el.value.trim() === "") {
            el.classList.add("error");
            isValid = false;
        }
    });

    const numericFields = ["sdt", "nguoigui_sdt", "nguoinhan_sdt", "khoiluong", "soluong"];
    numericFields.forEach(id => {
        let el = document.getElementById(id);
        if (el && el.value.trim() !== "" && isNaN(el.value)) {
            el.classList.add("error");
            isValid = false;
        }
    });

    // Kiểm tra trùng khớp thông tin khách hàng và người gửi
    if (document.getElementById("ten").value.trim() !== document.getElementById("nguoigui_ten").value.trim()) {
        document.getElementById("nguoigui_ten").classList.add("error");
        isValid = false;
    }

    if (document.getElementById("sdt").value.trim() !== document.getElementById("nguoigui_sdt").value.trim()) {
        document.getElementById("nguoigui_sdt").classList.add("error");
        isValid = false;
    }

    if (document.getElementById("diachi").value.trim() !== document.getElementById("nguoigui_diachi").value.trim()) {
        document.getElementById("nguoigui_diachi").classList.add("error");
        isValid = false;
    }

    return isValid;
}

// validation mở rộng (gọi cả gốc lẫn các trường mới)
function validation() {
    let isValidGoc = validationGoc();

    const newRequiredFields = ["kichthuoc_dai", "kichthuoc_rong", "kichthuoc_cao", "quangduong", "giatri_hang"];
    newRequiredFields.forEach(id => {
        let el = document.getElementById(id);
        if (el && el.value.trim() === "") {
            el.classList.add("error");
            isValidGoc = false;
        }
    });

    const newNumericFields = ["kichthuoc_dai", "kichthuoc_rong", "kichthuoc_cao", "quangduong", "giatri_hang"];
    newNumericFields.forEach(id => {
        let el = document.getElementById(id);
        if (el && el.value.trim() !== "" && isNaN(el.value)) {
            el.classList.add("error");
            isValidGoc = false;
        }
    });

    return isValidGoc;
}


// TÍNH CƯỚC PHÍ TỰ ĐỘNG
function tinhCuocVatTuTuDong() {
    let kl  = parseFloat(document.getElementById("khoiluong").value) || 0;
    let sl  = parseInt(document.getElementById("soluong").value)     || 1;
    let qd  = parseFloat(document.getElementById("quangduong").value) || 0;

    let dai  = parseFloat(document.getElementById("kichthuoc_dai").value)  || 0;
    let rong = parseFloat(document.getElementById("kichthuoc_rong").value) || 0;
    let cao  = parseFloat(document.getElementById("kichthuoc_cao").value)  || 0;

    let theTichM3 = (dai * rong * cao) / 1_000_000;

    let chiPhiTheoKhoiLuong = kl * qd * 4_000;
    let chiPhiTheoTheTich   = theTichM3 * qd * 500_000;

    let tongTien = Math.max(chiPhiTheoKhoiLuong, chiPhiTheoTheTich) * sl;
    if (tongTien < 0) tongTien = 0;

    document.getElementById("hien_thi_tien_cuoc").innerHTML =
        `💰 Dự tính cước phí: ${tongTien.toLocaleString('vi-VN')} VNĐ <br>
        <span style="font-size:12px; font-weight:normal; color:#666;">
            (Trọng lượng: ${kl}kg | Thể tích quy đổi: ${theTichM3.toFixed(3)} m³)
        </span>`;

    return tongTien;
}


// THÊM ĐƠN HÀNG
function themDonHang() {
    let tb = document.getElementById("thongbao");
    let giaCuocHienTai = tinhCuocVatTuTuDong();

    if (!validation()) {
        tb.innerText = "❌ Thông tin không hợp lệ hoặc thông tin khách hàng và người gửi không trùng khớp!";
        tb.className = "message fail";
        tb.style.display = "block";
        return;
    }

    let newOrder = {};
    document.querySelectorAll("input, textarea").forEach(el => {
        newOrder[el.id] = el.value;
    });

    // Lấy thêm các trường select và input mới
    newOrder.loaihang       = document.getElementById("loaihang").value;
    newOrder.phuongthuc_vc  = document.getElementById("phuongthuc_vc").value;
    newOrder.kichthuoc_dai  = document.getElementById("kichthuoc_dai").value;
    newOrder.kichthuoc_rong = document.getElementById("kichthuoc_rong").value;
    newOrder.kichthuoc_cao  = document.getElementById("kichthuoc_cao").value;
    newOrder.quangduong     = document.getElementById("quangduong").value;
    newOrder.giatri_hang    = document.getElementById("giatri_hang").value;
    newOrder.trangthai_don  = document.getElementById("trangthai_don").value;
    newOrder.cuocPhiMoi     = giaCuocHienTai;
    newOrder.timeSave       = new Date().getTime();

    database.push(newOrder);

    document.getElementById("lichsuBtn").style.display = "inline-block";
    tb.innerText = "✅ Thêm đơn hàng thành công!";
    tb.className = "message success";
    tb.style.display = "block";

    _resetForm();
}


// XEM LỊCH SỬ

function xemLichSu() {
    if (database.length === 0) return;

    let container = document.getElementById("tableContainer");
    container.style.display = "block";

    let tableBody = document.getElementById("bang");
    tableBody.innerHTML = "";

    database.forEach((order, index) => {
        let row = tableBody.insertRow();
        row.insertCell(0).innerText = order.ten;
        row.insertCell(1).innerText = order.sdt;
        row.insertCell(2).innerText = order.tenhang + " (" + (order.loaihang || "Hàng thường") + ")";
        row.insertCell(3).innerText = order.phuongthuc_vc || "Đường bộ";

        let cuocPhiTamTinh = order.cuocPhiMoi
            ? parseInt(order.cuocPhiMoi).toLocaleString('vi-VN') + " VNĐ"
            : "0 VNĐ";
        row.insertCell(4).innerText = cuocPhiTamTinh;
        row.insertCell(5).innerText = order.trangthai_don || "Chờ điều phối";

        let cellAction = row.insertCell(6);
        cellAction.innerHTML = `<button class="btn-edit-row" onclick="loadToForm(${index})">Sửa</button>`;
    });

    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}

// ============================================================
// TẢI ĐƠN VÀO FORM ĐỂ SỬA
// ============================================================
function loadToForm(index) {
    let order = database[index];
    let now = new Date().getTime();
    let hoursPassed = (now - order.timeSave) / (1000 * 60 * 60);

    if (hoursPassed > 12) {
        alert("Đã quá 12 giờ, bạn không thể chỉnh sửa đơn hàng này!");
        return;
    }

    editingIndex = index;

    // Điền dữ liệu vào tất cả input / textarea
    for (let key in order) {
        let inputEl = document.getElementById(key);
        if (inputEl && inputEl.tagName !== "SELECT") inputEl.value = order[key];
    }

    // Điền riêng các select
    const selectKeys = ["loaihang", "phuongthuc_vc"];
    selectKeys.forEach(key => {
        let el = document.getElementById(key);
        if (el && order[key] !== undefined) el.value = order[key];
    });

    tinhCuocVatTuTuDong();

    document.getElementById("capnhatBtn").style.display = "inline-block";
    document.getElementById("btnThem").style.display = "none";
    document.getElementById("thongbao").style.display = "none";

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================================
// CẬP NHẬT ĐƠN HÀNG
// ============================================================
function capNhatDonHang() {
    if (editingIndex === null) return;

    if (!validation()) {
        alert("Thông tin cập nhật không hợp lệ hoặc thông tin không trùng khớp!");
        return;
    }

    let giaCuocMoi = tinhCuocVatTuTuDong();

    document.querySelectorAll("input, textarea").forEach(el => {
        database[editingIndex][el.id] = el.value;
    });

    database[editingIndex].loaihang       = document.getElementById("loaihang").value;
    database[editingIndex].phuongthuc_vc  = document.getElementById("phuongthuc_vc").value;
    database[editingIndex].kichthuoc_dai  = document.getElementById("kichthuoc_dai").value;
    database[editingIndex].kichthuoc_rong = document.getElementById("kichthuoc_rong").value;
    database[editingIndex].kichthuoc_cao  = document.getElementById("kichthuoc_cao").value;
    database[editingIndex].quangduong     = document.getElementById("quangduong").value;
    database[editingIndex].giatri_hang    = document.getElementById("giatri_hang").value;
    database[editingIndex].cuocPhiMoi     = giaCuocMoi;

    xemLichSu();

    let tb = document.getElementById("thongbao");
    tb.innerText = "✅ Cập nhật thông tin thành công!";
    tb.className = "message success";
    tb.style.display = "block";

    document.getElementById("capnhatBtn").style.display = "none";
    document.getElementById("btnThem").style.display = "inline-block";
    editingIndex = null;

    _resetForm();
}

// ============================================================
// HÀM TIỆN ÍCH
// ============================================================
function _resetForm() {
    document.querySelectorAll("input:not([disabled]), textarea").forEach(el => el.value = "");
    document.getElementById("hien_thi_tien_cuoc").innerHTML =
        `💰 Dự tính cước phí: 0 VNĐ <br>
        <span style="font-size:12px; font-weight:normal; color:#666;">
            (Tự động tính dựa trên Khối lượng, Kích thước và Quãng đường)
        </span>`;
}

// ============================================================
// KHỞI TẠO KHI DOM SẴN SÀNG
// ============================================================
window.addEventListener('DOMContentLoaded', () => {

    // --- Thay input "Loại hàng" bằng select ---
    let oldLoaiHang = document.getElementById("loaihang");
    if (oldLoaiHang) {
        let selectLoai = document.createElement("select");
        selectLoai.id = "loaihang";
        selectLoai.className = "select-custom";
        selectLoai.innerHTML = `
            <option value="Hàng thường">Hàng thường</option>
            <option value="Hàng dễ vỡ">Hàng dễ vỡ</option>
            <option value="Hàng đông lạnh">Hàng đông lạnh</option>
            <option value="Hàng cồng kềnh">Hàng cồng kềnh</option>
            <option value="Hàng hóa chất / Nguy hiểm">Hàng hóa chất / Nguy hiểm</option>
        `;
        oldLoaiHang.parentNode.replaceChild(selectLoai, oldLoaiHang);
    }

    // --- Thêm nhóm kích thước ---
    let soLuongGroup = document.getElementById("soluong").parentNode;
    let kichThuocDiv = document.createElement("div");
    kichThuocDiv.className = "form-group";
    kichThuocDiv.innerHTML = `
        <label>Kích thước đơn hàng (Dài x Rộng x Cao cm) *</label>
        <div class="grid-3">
            <input type="number" id="kichthuoc_dai"  placeholder="Dài">
            <input type="number" id="kichthuoc_rong" placeholder="Rộng">
            <input type="number" id="kichthuoc_cao"  placeholder="Cao">
        </div>
    `;
    soLuongGroup.parentNode.insertBefore(kichThuocDiv, soLuongGroup.nextSibling);

    // --- Thêm nhóm vận chuyển / quãng đường / giá trị / trạng thái ---
    let ghiChuLabel = document.getElementById("ghichu").previousElementSibling;
    let boSungDiv = document.createElement("div");
    boSungDiv.className = "grid";
    boSungDiv.style.cssText = "margin-top:15px; margin-bottom:15px;";
    boSungDiv.innerHTML = `
        <div class="form-group">
            <label>Phương thức vận chuyển *</label>
            <select id="phuongthuc_vc" class="select-custom">
                <option value="Đường bộ (Xe tải)">Đường bộ (Xe tải)</option>
                <option value="Đường bộ (Container)">Đường bộ (Container)</option>
                <option value="Đường sắt">Đường sắt</option>
                <option value="Đường thủy">Đường thủy</option>
                <option value="Đường hàng không">Đường hàng không</option>
            </select>
        </div>
        <div class="form-group">
            <label>Quãng đường dự kiến (km) *</label>
            <input type="number" id="quangduong" placeholder="Nhập số km">
        </div>
        <div class="form-group">
            <label>Giá trị hàng hóa (VNĐ) *</label>
            <input type="number" id="giatri_hang" placeholder="Khai báo để bảo hiểm">
        </div>
        <div class="form-group">
            <label>Trạng thái đơn hàng (Mặc định)</label>
            <input id="trangthai_don" value="Chờ điều phối xe" disabled style="background:#e9ecef;">
        </div>
    `;
    ghiChuLabel.parentNode.insertBefore(boSungDiv, ghiChuLabel);

    // --- Thêm hộp hiển thị cước phí ---
    let buttonGroup = document.querySelector(".button-group");
    let feeBox = document.createElement("div");
    feeBox.className = "fee-box";
    feeBox.id = "hien_thi_tien_cuoc";
    feeBox.innerHTML = `💰 Dự tính cước phí: 0 VNĐ <br>
        <span style="font-size:12px; font-weight:normal; color:#666;">
            (Tự động tính dựa trên Khối lượng, Kích thước và Quãng đường)
        </span>`;
    buttonGroup.parentNode.insertBefore(feeBox, buttonGroup);

    // --- Cập nhật header bảng ---
    let tableHeader = document.querySelector("table thead tr");
    if (tableHeader) {
        tableHeader.innerHTML = `
            <th>Khách hàng</th>
            <th>SĐT</th>
            <th>Hàng hóa</th>
            <th>Phương thức VC</th>
            <th>Tạm tính cước</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        `;
    }

    // --- Lắng nghe sự kiện tính cước tự động ---
    const inputsDinhGia = ["khoiluong", "soluong", "quangduong", "kichthuoc_dai", "kichthuoc_rong", "kichthuoc_cao"];
    inputsDinhGia.forEach(id => {
        document.getElementById(id)?.addEventListener("input", tinhCuocVatTuTuDong);
    });
});
