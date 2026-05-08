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

    // --- 2. UI HELPERS (ĐỒNG HỒ & TABS) ---
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

    function handleTabSwitch(tabBtn) {
        const target = tabBtn.dataset.approvalTab;
        document.querySelectorAll("[data-approval-tab]").forEach(b => b.classList.toggle("is-active", b === tabBtn));
        document.querySelectorAll("[data-approval-panel]").forEach(p => p.classList.toggle("is-active", p.dataset.approvalPanel === target));
    }

    // --- 3. XỬ LÝ CHO NHÂN VIÊN (CHẤM CÔNG & CÁ NHÂN) ---
    async function loadAttendanceData() {
        if (!token || !document.querySelector("[data-attendance-clock]")) return;
        try {
            const res = await callApi('/attendance');
            if (res.status === "success") {
                const { stats, history, today } = res.data;
                const elements = {
                    'js-stat-total': stats.total_days, 'js-stat-ontime': stats.on_time,
                    'js-stat-late': stats.late, 'js-stat-missing': stats.missing_out
                };
                for (let id in elements) {
                    const el = document.getElementById(id);
                    if (el) el.innerText = elements[id];
                }

                if (today) {
                    const btnIn = document.querySelector('[data-payroll-action="check-in"]');
                    const btnOut = document.querySelector('[data-payroll-action="check-out"]');
                    if (btnIn && today.check_in_time) btnIn.disabled = true;
                    if (btnOut && today.check_out_time) btnOut.disabled = true;
                }

                const tableBody = document.getElementById('js-attendance-history');
                if (tableBody) {
                    tableBody.innerHTML = history.length === 0 ? '<tr><td colspan="5">Chưa có dữ liệu</td></tr>' : 
                    history.map(row => `
                        <tr>
                            <td>${new Date(row.work_date).toLocaleDateString('vi-VN')}</td>
                            <td><strong>${row.check_in_time ? row.check_in_time.substring(11, 16) : '--'}</strong></td>
                            <td><strong>${row.check_out_time ? row.check_out_time.substring(11, 16) : '--'}</strong></td>
                            <td><span class="badge badge-${row.status === 'Present' ? 'success' : 'warning'}">${row.status === 'Present' ? 'Đúng giờ' : 'Đi muộn'}</span></td>
                            <td>Ghi nhận Web</td>
                        </tr>
                    `).join('');
                }
            }
        } catch (e) { console.error("Lỗi tải chấm công:", e); }
    }

    async function loadPersonalLeaveData() {
        const historyWrap = document.getElementById('js-leave-history');
        if (!historyWrap) return;
        try {
            const res = await callApi('/leaves');
            if (res.status === 'success') {
                const balanceEl = document.getElementById('js-leave-balance');
                if (balanceEl) balanceEl.innerText = res.data.balance;
                historyWrap.innerHTML = res.data.history.length === 0 ? '<p style="text-align:center; padding:20px;">Bạn chưa có đơn nào.</p>' :
                res.data.history.map(item => `
                    <div class="leave-history-item">
                        <div><h3>${item.leave_type === 'annual' ? 'Nghỉ phép năm' : 'Việc riêng'}</h3><p>${item.start_date} (${item.duration} ngày)</p></div>
                        <span class="badge badge-${item.status === 'Approved' ? 'success' : 'warning'}">${item.status}</span>
                    </div>
                `).join('');
            }
        } catch (e) { console.error("Lỗi tải đơn cá nhân:", e); }
    }

    // --- 4. XỬ LÝ CHO QUẢN LÝ (PHÊ DUYỆT THẬT) ---
    window.loadManagerApprovals = async function() {
        const leaveList = document.getElementById('js-leave-list');
        const taskList = document.getElementById('js-task-list');
        if (!leaveList && !taskList) return;

        try {
            // A. Tải Đơn Nghỉ Phép
            if (leaveList) {
                const resLeaves = await callApi('/admin/leaves');
                if (resLeaves.status === 'success') {
                    const leaves = resLeaves.data || [];
                    const countEl = document.getElementById('js-count-leaves');
                    if (countEl) countEl.innerText = leaves.length;

                    leaveList.innerHTML = leaves.length === 0 ? '<p class="empty-msg" style="text-align:center; padding:20px; color:#999;">Bạn đã xử lý hết đơn nghỉ phép.</p>' : 
                    leaves.map(item => `
                        <article class="approval-card">
                            <div class="approval-avatar">${item.employee_name ? item.employee_name.substring(0,2).toUpperCase() : '??'}</div>
                            <div class="approval-content">
                                <h3>Đơn nghỉ phép: ${item.employee_name}</h3>
                                <p>${item.reason}</p>
                                <div class="approval-meta">
                                    <span class="badge badge-primary">${item.leave_type || 'Nghỉ phép'}</span>
                                    <span class="badge badge-info">${item.duration} ngày</span>
                                    <span class="badge badge-success">Từ: ${item.start_date}</span>
                                </div>
                            </div>
                            <div class="approval-actions">
                                <button class="btn btn-danger-soft" onclick="processApproval('leave', ${item.id || item.leave_id}, 'Rejected')">Từ chối</button>
                                <button class="btn btn-primary" onclick="processApproval('leave', ${item.id || item.leave_id}, 'Approved')">Duyệt phép</button>
                            </div>
                        </article>
                    `).join('');
                } else { leaveList.innerHTML = `<p style="color:red; text-align:center; padding:20px;">Lỗi: ${resLeaves.message}</p>`; }
            }

            // B. Tải Task Chờ Duyệt
            if (taskList) {
                const resTasks = await callApi('/tasks/submit');
                if (resTasks.status === 'success') {
                    const tasks = resTasks.data || [];
                    const countEl = document.getElementById('js-count-tasks');
                    if (countEl) countEl.innerText = tasks.length;

                    taskList.innerHTML = tasks.length === 0 ? '<p class="empty-msg" style="text-align:center; padding:20px; color:#999;">Mọi công việc đã được xử lý xong.</p>' : 
                    tasks.map(item => {
                        const taskId = item.id || item.task_id || 0;
                        const projectId = item.project_id || item.project || 'N/A';
                        const taskTitle = item.title || item.task_name || 'Không có tên';
                        
                        return `
                        <article class="approval-card">
                            <div class="approval-avatar">TK</div>
                            <div class="approval-content">
                                <h3>Duyệt hoàn thành: ${taskTitle}</h3>
                                <p>${item.description || 'Không có mô tả'}</p>
                                <div class="approval-meta">
                                    <span class="badge badge-primary">Dự án ID: #${projectId}</span>
                                </div>
                            </div>
                            <div class="approval-actions">
                                <button class="btn btn-danger-soft" onclick="processApproval('task', ${taskId}, 'Rejected')">Từ chối</button>
                                <button class="btn btn-primary" onclick="processApproval('task', ${taskId}, 'Approved')">Duyệt Task</button>
                            </div>
                        </article>
                        `;
                    }).join('');
                }
            }
        } catch (e) { console.error("Lỗi tải phê duyệt:", e); }
    };

    /**
     * HÀM XỬ LÝ PHÊ DUYỆT ĐÃ ĐƯỢC CHUẨN HÓA
     * Phân tách Method (PATCH/POST) và Endpoint dựa trên cấu hình routes/api.php
     */
    window.processApproval = async (type, id, action) => {
        if (!id || id === 0) {
            if (window.CAHToast) CAHToast.error("Lỗi Dữ Liệu", "Không tìm thấy ID để phê duyệt.");
            return;
        }

        try {
            let endpoint = '';
            let method = '';
            let body = null;

            if (type === 'leave') {
                // Tuyến đường cho Nghỉ Phép: PATCH /api/leaves/:id/approve
                endpoint = `/leaves/${id}/approve`;
                method = 'PATCH';
                body = { action }; // Gửi trạng thái Approved/Rejected trong body
            } else if (type === 'task') {
                // Tuyến đường cho Task: POST /api/tasks/:id/approve HOẶC POST /api/tasks/:id/reject
                const taskAction = action === 'Approved' ? 'approve' : 'reject';
                endpoint = `/tasks/${id}/${taskAction}`;
                method = 'POST';
                body = {}; // Backend có thể cần body trống cho POST requests
            }

            const res = await callApi(endpoint, method, body);
            
            if (res.status === 'success') {
                if (window.CAHToast) CAHToast.success("Thành công", res.message);
                loadManagerApprovals(); // Reload lại danh sách sau khi duyệt
            } else { 
                if (window.CAHToast) CAHToast.error("Thất bại", res.message); 
            }
        } catch (err) { 
            if (window.CAHToast) CAHToast.error("Lỗi hệ thống", err.message); 
        }
    };

    // --- 5. KHỞI TẠO & LẮNG NGHE ---
    document.addEventListener("DOMContentLoaded", () => {
        updateClock();
        setInterval(updateClock, 1000);
        loadAttendanceData();
        loadPersonalLeaveData();
        loadManagerApprovals();
    });

    document.addEventListener("click", async (e) => {
        const payrollBtn = e.target.closest("[data-payroll-action]");
        if (payrollBtn) {
            const action = payrollBtn.dataset.payrollAction;
            if (action === "check-in" || action === "check-out") {
                const endpoint = action === "check-in" ? "/attendance/checkin" : "/attendance/checkout";
                const res = await callApi(endpoint, 'POST');
                if (res.status === 'success') {
                    if (window.CAHToast) CAHToast.success("Thành công", res.message);
                    loadAttendanceData();
                } else { if (window.CAHToast) CAHToast.error("Lỗi", res.message); }
            }
        }
        const tabBtn = e.target.closest("[data-approval-tab]");
        if (tabBtn) handleTabSwitch(tabBtn);
    });

    document.addEventListener("submit", async (e) => {
        const form = e.target.closest("[data-leave-form]");
        if (!form) return;
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
            const res = await callApi('/leaves', 'POST', Object.fromEntries(new FormData(form).entries()));
            if (res.status === 'success') {
                if (window.CAHToast) CAHToast.success("Thành công", res.message);
                form.reset();
                loadPersonalLeaveData();
            } else { if (window.CAHToast) CAHToast.error("Lỗi", res.message); }
        } finally { btn.disabled = false; }
    });
})();