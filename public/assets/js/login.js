/* public/assets/js/login.js */
"use strict";

(function () {
  const form = document.getElementById("loginForm");
  const emailInput = document.getElementById("email");
  const passInput = document.getElementById("password");
  const btnSubmit = document.getElementById("btnSubmit");
  const toast = document.getElementById("toast");
  const toggleBtn = document.getElementById("togglePassword");
  const toggleIcon = document.getElementById("toggleIcon");
  const emailError = document.getElementById("emailError");
  const emailMsg = document.getElementById("emailMsg");
  const passError = document.getElementById("passError");
  const passMsg = document.getElementById("passMsg");

  /* Password toggle */
  toggleBtn.addEventListener("click", () => {
    const isPass = passInput.type === "password";
    passInput.type = isPass ? "text" : "password";
    toggleIcon.textContent = isPass ? "visibility_off" : "visibility";
  });

  /* Clear errors on input */
  emailInput.addEventListener("input", () => clearErr(emailInput, emailError));
  passInput.addEventListener("input", () => clearErr(passInput, passError));

  /* Blur validation */
  emailInput.addEventListener("blur", () => validateEmail(emailInput.value));
  passInput.addEventListener("blur", () => validatePass(passInput.value));

  /* Form submit */
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideToast();

    const email = emailInput.value.trim();
    const password = passInput.value.trim();

    const ok = validateEmail(email) & validatePass(password);
    if (!ok) return;

    setBusy(true);

    try {
      // GỌI THẲNG TỚI API CỦA HIẾU (POST 1. Auth - Login)
      const res = await fetch("/api/auth/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await res.json();

      if (res.status === 200 && data.status === "success") {
        showToast(`Đăng nhập thành công! Đang chuyển hướng...`, "success");
        // Lưu Token vào LocalStorage để dùng cho các API khác
        localStorage.setItem("auth_token", data.data.token);
        // Chuyển hướng vào trang Dashboard
        setTimeout(() => (window.location.href = "/dashboard"), 1400);
      } else {
        showToast(data.message || "Lỗi hệ thống, vui lòng thử lại.", "error");
        shake();
      }
    } catch (error) {
      showToast("Không thể kết nối đến máy chủ API.", "error");
      shake();
    } finally {
      setBusy(false);
    }
  });

  /* Validators */
  function validateEmail(v) {
    if (!v) return setErr(emailInput, emailError, emailMsg, "Vui lòng nhập địa chỉ email.");
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v))
      return setErr(emailInput, emailError, emailMsg, "Địa chỉ email không hợp lệ.");
    clearErr(emailInput, emailError);
    return true;
  }

  function validatePass(v) {
    if (!v) return setErr(passInput, passError, passMsg, "Vui lòng nhập mật khẩu.");
    if (v.length < 6) return setErr(passInput, passError, passMsg, "Mật khẩu phải có ít nhất 6 ký tự.");
    clearErr(passInput, passError);
    return true;
  }

  function setErr(input, errEl, msgEl, msg) {
    input.classList.add("is-error");
    msgEl.textContent = msg;
    errEl.classList.add("visible");
    return false;
  }

  function clearErr(input, errEl) {
    input.classList.remove("is-error");
    errEl.classList.remove("visible");
  }

  /* Toast & UI logic */
  function showToast(msg, type) {
    const icon = type === "success" ? "check_circle" : "error";
    toast.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px">${icon}</span>${msg}`;
    toast.className = `toast ${type} visible`;
  }

  function hideToast() {
    toast.classList.remove("visible");
  }

  function shake() {
    form.style.animation = "none";
    void form.offsetHeight;
    form.style.animation = "shake .4s cubic-bezier(.36,.07,.19,.97)";
  }

  function setBusy(on) {
    btnSubmit.classList.toggle("loading", on);
    btnSubmit.disabled = on;
  }
})();
