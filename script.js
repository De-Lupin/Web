function Strength(password) {
    let i = 0;
    if (password.length > 6) i++;
    if (password.length >= 10) i++;
    if (/[A-Z]/.test(password)) i++;
    if (/[0-9]/.test(password)) i++;
    if (/[A-Za-z0-8]/.test(password)) i++;
    return i;
}

let strengthmetertext = document.querySelector("#strengthmetertext");
let passwordInput = document.querySelector("#password");
let showbtn = document.querySelector("#showbtn");
let suggestbtn = document.querySelector("#suggestbtn");

// Kiểm tra độ mạnh khi người dùng tự gõ phím
passwordInput.addEventListener("keyup", function () {
    let password = passwordInput.value;
    
    // Xóa định dạng màu cũ
    strengthmetertext.className = "";

    if (password.length === 0) {
        strengthmetertext.innerText = "";
        return;
    }

    let strength = Strength(password);
    if (strength <= 2) {
        strengthmetertext.innerText = "Mật khẩu yếu";
        strengthmetertext.classList.add("weak");
    } else if (strength >= 2 && strength <= 4) {
        strengthmetertext.innerText = "Mật khẩu trung bình";
        strengthmetertext.classList.add("moderate");
    } else {
        strengthmetertext.innerText = "Mật khẩu mạnh";
        strengthmetertext.classList.add("strong");
    }
});

// Chức năng nút Ẩn/Hiện
showbtn.onclick = function () {
    if (passwordInput.type === "password") {
        passwordInput.setAttribute("type", "text");
        showbtn.innerText = "Hide";
    } else {
        passwordInput.setAttribute("type", "password");
        showbtn.innerText = "Show";
    }
};

// Chức năng Đề xuất mật khẩu
suggestbtn.onclick = function () {
    let chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+";
    let strongPassword = "";
    let passwordLength = 12;

    for (let i = 0; i < passwordLength; i++) {
        let randomNumber = Math.floor(Math.random() * chars.length);
        strongPassword += chars.substring(randomNumber, randomNumber + 1);
    }

    // Gán mật khẩu vào ô
    passwordInput.value = strongPassword;
    
    // Đổi sang dạng text để dễ nhìn thấy
    passwordInput.setAttribute("type", "text");
    showbtn.innerText = "Hide";
    
    // Cập nhật chữ hiển thị
    strengthmetertext.className = "strong";
    strengthmetertext.innerText = "Mật khẩu mạnh";
};

// Facebook Login
window.fbAsyncInit = function() {
    FB.init({
        appId      : 'YOUR_APP_ID', // Thay bằng App ID thực tế
        cookie     : true,
        xfbml      : true,
        version    : 'v18.0'
    });
      
    FB.AppEvents.logPageView();   
      
};

(function(d, s, id){
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {return;}
    js = d.createElement(s); js.id = id;
    js.src = "https://connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

function checkLoginState() {
    FB.getLoginStatus(function(response) {
        statusChangeCallback(response);
    });
}

function statusChangeCallback(response) {
    if (response.status === 'connected') {
        // Người dùng đã đăng nhập Facebook và ứng dụng
        testAPI();
    } else {
        // Người dùng chưa đăng nhập hoặc chưa ủy quyền ứng dụng
        console.log('Please log into this app.');
    }
}

function testAPI() {
    FB.api('/me', function(response) {
        console.log('Successful login for: ' + response.name);
        // Chuyển hướng hoặc xử lý đăng nhập
        window.location.href = '?fb_login=1';
    });
}

// Google Sign-In
function handleCredentialResponse(response) {
    console.log("Encoded JWT ID token: " + response.credential);
    // Gửi token đến server để xác minh
    // Ở đây giả lập chuyển hướng
    window.location.href = '?google_login=1';
}