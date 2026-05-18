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

passwordInput.addEventListener("keyup", function () {
    let password = passwordInput.value;
    
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

showbtn.onclick = function () {
    if (passwordInput.type === "password") {
        passwordInput.setAttribute("type", "text");
        showbtn.innerText = "Hide";
    } else {
        passwordInput.setAttribute("type", "password");
        showbtn.innerText = "Show";
    }
};

suggestbtn.onclick = function () {
    let chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+";
    let strongPassword = "";
    let passwordLength = 12;

    for (let i = 0; i < passwordLength; i++) {
        let randomNumber = Math.floor(Math.random() * chars.length);
        strongPassword += chars.substring(randomNumber, randomNumber + 1);
    }

    passwordInput.value = strongPassword;
    
    passwordInput.setAttribute("type", "text");
    showbtn.innerText = "Hide";
    
    strengthmetertext.className = "strong";
    strengthmetertext.innerText = "Mật khẩu mạnh";
};

window.fbAsyncInit = function() {
    FB.init({
        appId      : 'YOUR_APP_ID', 
        cookie     : true,
        xfbml      : true,
        version    : 'v18.0'
    });
      
    FB.AppEvents.logPageView();   
      
};



function testAPI() {
    FB.api('/me', function(response) {
        console.log('Successful login for: ' + response.name);
       
        window.location.href = '?fb_login=1';
    });
}


function handleCredentialResponse(response) {
    console.log("Encoded JWT ID token: " + response.credential);
    window.location.href = '?google_login=1';
}