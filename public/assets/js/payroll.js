(function () {
    "use strict";

    const token = localStorage.getItem('cah_token');
    const baseUrl = '/creative-agency-hub/public'; 

    /**
     * 1. HÀM TẢI DỮ LIỆU (MỚI): Lấy lịch sử và thống kê từ Database
     */
    async function loadAttendanceData() {
        if (!token) return;

        try {
            const response = await fetch(`${baseUrl}/api/attendance`, {
                method: 'GET',
                headers: { 
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json' 
                }
            });

            const result = await response.json();

            if (result.status === "success") {
                const { stats, history, today } = result.data;

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
                    // Khóa nút nếu đã check-in/out
                    if (today.check_in_time) document.querySelector('[data-payroll-action="check-in"]').disabled = true;
                    if (today.check_out_time) document.querySelector('[data-payroll-action="check-out"]').disabled = true;
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
        } catch (error) {
            console.error("Lỗi tải lịch sử:", error);
        }
    }

    /**
     * 2. ĐỒNG HỒ THỜI GIAN THỰC[cite: 4]
     */
    function updateClock() {
        const clock = document.querySelector("[data-attendance-clock]");
        const dateEl = document.querySelector("[data-attendance-date]");
        if (!clock) return;
        const now = new Date();
        clock.textContent = new Intl.DateTimeFormat("vi-VN", { hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: false }).format(now);
        if (dateEl) dateEl.textContent = new Intl.DateTimeFormat("vi-VN", { weekday: "long", day: "2-digit", month: "2-digit", year: "numeric" }).format(now);
    }

    /**
     * 3. XỬ LÝ CLICK CHECK-IN/OUT
     */
    async function handleAttendanceAction(action, button) {
        const endpoint = action === "check-in" ? "/api/attendance/checkin" : "/api/attendance/checkout";
        try {
            const response = await fetch(`${baseUrl}${endpoint}`, {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            if (result.status === "success") {
                if (window.CAHToast) CAHToast.success("Thành công", result.message);
                loadAttendanceData(); // Tải lại dữ liệu ngay sau khi bấm nút thành công
            } else {
                if (window.CAHToast) CAHToast.error("Thất bại", result.message);
            }
        } catch (error) {
            if (window.CAHToast) CAHToast.error("Lỗi kết nối", error.message);
        }
    }

    // KHỞI CHẠY KHI TẢI TRANG
    document.addEventListener("DOMContentLoaded", () => {
        if (document.querySelector("[data-attendance-clock]")) {
            updateClock();
            setInterval(updateClock, 1000);
        }
        loadAttendanceData(); // Tự động lấy lịch sử khi vào trang
    });

    document.addEventListener("click", function (event) {
        const btn = event.target.closest("[data-payroll-action]");
        if (btn) {
            const action = btn.dataset.payrollAction;
            if (action === "check-in" || action === "check-out") handleAttendanceAction(action, btn);
        }
    });
})();