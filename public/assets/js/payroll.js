(function () {
    "use strict";

    const token = localStorage.getItem('cah_token');
    const baseUrl = '/creative-agency-hub/public'; 

    // --- 1. LOGIC CHUNG (HELPER) ---
    const callApi = async (endpoint, method = 'GET', body = null) => {
        const options = {
            method,
            headers: { 
                'Authorization': 'Bearer ' + token, 
                'Content-Type': 'application/json' 
            }
        };
        if (body) options.body = JSON.stringify(body);
        const res = await fetch(`${baseUrl}/api${endpoint}`, options);
        return await res.json();
    };

    // --- 2. ĐỒNG HỒ THỜI GIAN THỰC ---
    function updateClock() {
        const clock = document.querySelector("[data-attendance-clock]");
        const dateEl = document.querySelector("[data-attendance-date]");
        if (!clock) return;
        const now = new Date();
        clock.textContent = new Intl.DateTimeFormat("vi-VN", { 
            hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: false 
        }).format(now);
        if (dateEl) dateEl.textContent = new Intl.DateTimeFormat("vi-VN", { 
            weekday: "long", day: "2-digit", month: "2-digit", year: "numeric" 
        }).format(now);
    }

    // --- 3. XỬ LÝ CHẤM CÔNG (ATTENDANCE) ---
    async function loadAttendanceData() {
        if (!token) return;
        try {
            const res = await callApi('/attendance');
            if (res.status === "success") {
                const { stats, history, today } = res.data;

                // A. Đổ dữ liệu vào các thẻ thống kê
                const elements = {
                    'js-stat-total': stats.total_days,
                    'js-stat-ontime': stats.on_time,
                    'js-stat-late': stats.late,
                    'js-stat-missing': stats.missing_out
                };
                for (let id in elements) {
                    const el = document.getElementById(id);
                    if (el) el.innerText = elements[id];
                }

                // B. Tính toán tỷ lệ đúng giờ
                const rateEl = document.getElementById('js-stat-rate');
                if (rateEl) {
                    const rate = stats.total_days > 0 ? Math.round((stats.on_time / stats.total_days) * 100) : 0;
                    rateEl.innerText = `Tỷ lệ ${rate}%`;
                }

                // C. Cập nhật trạng thái nút bấm hôm nay
                const statusLabel = document.querySelector("[data-checkin-status]");
                if (today) {
                    if (statusLabel) {
                        statusLabel.innerText = today.check_out_time ? "Đã hoàn thành" : "Đã vào làm";
                        statusLabel.className = "badge-success";
                    }
                    if (today.check_in_time) {
                        const btnIn = document.querySelector('[data-payroll-action="check-in"]');
                        if (btnIn) btnIn.disabled = true;
                    }
                    if (today.check_out_time) {
                        const btnOut = document.querySelector('[data-payroll-action="check-out"]');
                        if (btnOut) btnOut.disabled = true;
                    }
                }

                // D. Vẽ bảng lịch sử chấm công
                const tableBody = document.getElementById('js-attendance-history');
                if (tableBody) {
                    if (history.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center">Chưa có dữ liệu tháng này</td></tr>';
                    } else {
                        tableBody.innerHTML = history.map(row => `
                            <tr>
                                <td>${new Date(row.work_date).toLocaleDateString('vi-VN')}</td>
                                <td><strong>${row.check_in_time ? row.check_in_time.substring(11, 16) : '--'}</strong></td>
                                <td><strong>${row.check_out_time ? row.check_out_time.substring(11, 16) : '--'}</strong></td>
                                <td>
                                    <span class="badge badge-${row.status === 'Present' ? 'success' : (row.status === 'Late' ? 'warning' : 'info')}">
                                        ${row.status === 'Present' ? 'Đúng giờ' : (row.status === 'Late' ? 'Đi muộn' : 'Nghỉ')}
                                    </span>
                                </td>
                                <td>Ghi nhận từ Web Check-in</td>
                            </tr>
                        `).join('');
                    }
                }
            }
        } catch (error) { console.error("Lỗi tải lịch sử chấm công:", error); }
    }

    async function handleAttendanceAction(action, button) {
        const endpoint = action === "check-in" ? "/attendance/checkin" : "/attendance/checkout";
        try {
            const result = await callApi(endpoint, 'POST');
            if (result.status === "success") {
                if (window.CAHToast) CAHToast.success("Thành công", result.message);
                loadAttendanceData(); 
            } else {
                if (window.CAHToast) CAHToast.error("Thất bại", result.message);
            }
        } catch (error) {
            if (window.CAHToast) CAHToast.error("Lỗi kết nối", error.message);
        }
    }

    // --- 4. XỬ LÝ NGHỈ PHÉP (LEAVE) ---
    async function loadLeaveData() {
        if (!token) return;
        const balanceEl = document.getElementById('js-leave-balance');
        const historyWrap = document.getElementById('js-leave-history');

        try {
            const res = await callApi('/leaves');
            if (res.status === 'success') {
                if (balanceEl) balanceEl.innerText = res.data.balance;
                document.getElementById('js-leave-summary').innerText = `Quỹ phép năm hiện có: ${res.data.balance} ngày.`;

                if (historyWrap) {
                    if (res.data.history.length === 0) {
                        historyWrap.innerHTML = '<p style="text-align:center; padding:20px;">Bạn chưa có đơn nghỉ nào.</p>';
                    } else {
                        historyWrap.innerHTML = res.data.history.map(item => `
                            <div class="leave-history-item">
                                <div>
                                    <h3>${item.leave_type === 'annual' ? 'Nghỉ phép năm' : 'Nghỉ việc riêng'}</h3>
                                    <p>${item.start_date} - ${item.end_date} (${item.duration} ngày)</p>
                                </div>
                                <span class="badge badge-${item.status === 'Approved' ? 'success' : (item.status === 'Pending' ? 'warning' : 'danger')}">
                                    ${item.status === 'Approved' ? 'ĐÃ DUYỆT' : (item.status === 'Pending' ? 'CHỜ DUYỆT' : 'TỪ CHỐI')}
                                </span>
                            </div>
                        `).join('');
                    }
                }
            } else {
                throw new Error(res.message);
            }
        } catch (error) {
            console.error("Lỗi tải lịch sử nghỉ phép:", error);
            if (historyWrap) historyWrap.innerHTML = `<p style="text-align:center; color:red; padding:20px;">Lỗi: ${error.message}</p>`;
        }
    }

    // --- 5. KHỞI TẠO & LẮNG NGHE SỰ KIỆN ---
    document.addEventListener("DOMContentLoaded", () => {
        // Trang Chấm công
        if (document.querySelector("[data-attendance-clock]")) {
            updateClock();
            setInterval(updateClock, 1000);
            loadAttendanceData();
        }
        
        // Trang Nghỉ phép
        if (document.getElementById('js-leave-history')) {
            loadLeaveData();
        }
    });

    document.addEventListener("click", function (event) {
        const btn = event.target.closest("[data-payroll-action]");
        if (btn) {
            const action = btn.dataset.payrollAction;
            if (action === "check-in" || action === "check-out") {
                handleAttendanceAction(action, btn);
            }
        }
    });

    document.addEventListener("submit", async function (event) {
        const form = event.target.closest("[data-leave-form]");
        if (!form) return;
        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        try {
            const res = await callApi('/leaves', 'POST', payload);
            if (res.status === 'success') {
                if (window.CAHToast) CAHToast.success("Thành công", res.message);
                form.reset();
                loadLeaveData(); 
            } else {
                throw new Error(res.message);
            }
        } catch (error) {
            if (window.CAHToast) CAHToast.error("Thất bại", error.message);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
})();