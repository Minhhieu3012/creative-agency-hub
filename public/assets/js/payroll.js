(function () {
    "use strict";

    const baseUrl = '/creative-agency-hub/public';

    const getToken = () => {
        return localStorage.getItem('cah_token') || localStorage.getItem('cah_auth_token') || '';
    };

    const escapeHtml = (value) => {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const normalizeStatus = (status) => {
        return String(status || '').trim().toLowerCase();
    };

    const isApprovalTask = (task) => {
        const status = normalizeStatus(task.status);
        return status === 'pending approval' || status === 'review';
    };

    const getTaskApprovalMeta = (task) => {
        const status = normalizeStatus(task.status);

        if (status === 'pending approval') {
            return {
                titlePrefix: 'Duyệt task mới',
                badge: 'Chờ duyệt',
                badgeClass: 'badge-warning',
                approveText: 'Duyệt vào Cần làm'
            };
        }

        return {
            titlePrefix: 'Duyệt hoàn thành',
            badge: 'Đang kiểm tra',
            badgeClass: 'badge-info',
            approveText: 'Duyệt hoàn thành'
        };
    };

    const getLeaveTypeLabel = (value) => {
        const map = {
            annual: 'Nghỉ phép năm',
            sick: 'Nghỉ ốm',
            personal: 'Nghỉ việc cá nhân',
            half_day: 'Nghỉ nửa ngày'
        };

        return map[value] || 'Nghỉ phép';
    };

    const callApi = async (endpoint, method = 'GET', body = null) => {
        const token = getToken();

        let url = `${baseUrl}/api${endpoint}`;

        if (method.toUpperCase() === 'GET') {
            url += `${url.includes('?') ? '&' : '?'}_=${Date.now()}`;
        }

        const options = {
            method,
            cache: 'no-store',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        };

        if (body !== null && body !== undefined) {
            options.body = JSON.stringify(body);
        }

        const response = await fetch(url, options);
        const contentType = response.headers.get('content-type') || '';

        let payload;

        if (contentType.includes('application/json')) {
            payload = await response.json();
        } else {
            payload = {
                status: response.ok ? 'success' : 'error',
                message: await response.text()
            };
        }

        if (!response.ok || payload.status === 'error') {
            throw new Error(payload.message || 'Không thể xử lý yêu cầu.');
        }

        return payload;
    };

    function updateClock() {
        const clock = document.querySelector("[data-attendance-clock]");
        const dateEl = document.querySelector("[data-attendance-date]");

        if (!clock) return;

        const now = new Date();

        clock.textContent = new Intl.DateTimeFormat("vi-VN", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: false
        }).format(now);

        if (dateEl) {
            dateEl.textContent = new Intl.DateTimeFormat("vi-VN", {
                weekday: "long",
                day: "2-digit",
                month: "2-digit",
                year: "numeric"
            }).format(now);
        }
    }

    function handleTabSwitch(tabBtn) {
        const target = tabBtn.dataset.approvalTab;

        document.querySelectorAll("[data-approval-tab]").forEach((button) => {
            button.classList.toggle("is-active", button === tabBtn);
        });

        document.querySelectorAll("[data-approval-panel]").forEach((panel) => {
            panel.classList.toggle("is-active", panel.dataset.approvalPanel === target);
        });
    }

    function calculateInclusiveDays(startDate, endDate) {
        if (!startDate || !endDate) return '';

        const start = new Date(`${startDate}T00:00:00`);
        const end = new Date(`${endDate}T00:00:00`);

        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return '';

        const days = Math.floor((end - start) / 86400000) + 1;

        return days > 0 ? days : '';
    }

    function syncLeaveDuration(form) {
        const startInput = form.querySelector('[name="start_date"]');
        const endInput = form.querySelector('[name="end_date"]');
        const durationInput = form.querySelector('[name="duration"]');

        if (!startInput || !endInput || !durationInput) return;

        const days = calculateInclusiveDays(startInput.value, endInput.value);

        if (days !== '') {
            durationInput.value = days;
        }
    }

    function getCleanReason(reason) {
        return String(reason || '').replace(/^\[[^\]]+\]\s*/u, '');
    }

    function getReasonLeaveType(reason, fallback) {
        const match = String(reason || '').match(/^\[([^\]]+)\]\s*/u);
        return match ? match[1] : (fallback || 'Nghỉ phép');
    }

    async function loadAttendanceData() {
        if (!getToken() || !document.querySelector("[data-attendance-clock]")) return;

        try {
            const res = await callApi('/attendance');

            if (res.status === "success") {
                const { stats, history, today } = res.data;

                const elements = {
                    'js-stat-total': stats.total_days,
                    'js-stat-ontime': stats.on_time,
                    'js-stat-late': stats.late,
                    'js-stat-missing': stats.missing_out
                };

                Object.keys(elements).forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) el.innerText = elements[id];
                });

                if (today) {
                    const btnIn = document.querySelector('[data-payroll-action="check-in"]');
                    const btnOut = document.querySelector('[data-payroll-action="check-out"]');

                    if (btnIn && today.check_in_time) btnIn.disabled = true;
                    if (btnOut && today.check_out_time) btnOut.disabled = true;
                }

                const tableBody = document.getElementById('js-attendance-history');

                if (tableBody) {
                    tableBody.innerHTML = history.length === 0
                        ? '<tr><td colspan="5">Chưa có dữ liệu</td></tr>'
                        : history.map((row) => `
                            <tr>
                                <td>${escapeHtml(new Date(row.work_date).toLocaleDateString('vi-VN'))}</td>
                                <td><strong>${escapeHtml(row.check_in_time ? row.check_in_time.substring(11, 16) : '--')}</strong></td>
                                <td><strong>${escapeHtml(row.check_out_time ? row.check_out_time.substring(11, 16) : '--')}</strong></td>
                                <td>
                                    <span class="badge badge-${row.status === 'Present' ? 'success' : 'warning'}">
                                        ${row.status === 'Present' ? 'Đúng giờ' : 'Đi muộn'}
                                    </span>
                                </td>
                                <td>Ghi nhận Web</td>
                            </tr>
                        `).join('');
                }
            }
        } catch (e) {
            console.error("Lỗi tải chấm công:", e);
        }
    }

    async function loadPersonalLeaveData() {
        const historyWrap = document.getElementById('js-leave-history');

        if (!historyWrap) return;

        try {
            const res = await callApi('/leaves');

            if (res.status === 'success') {
                const balanceEl = document.getElementById('js-leave-balance');

                if (balanceEl) {
                    balanceEl.innerText = res.data.balance;
                }

                const summaryEl = document.getElementById('js-leave-summary');

                if (summaryEl) {
                    summaryEl.innerText = 'Dữ liệu quỹ phép được đồng bộ từ hồ sơ nhân viên.';
                }

                const history = Array.isArray(res.data.history) ? res.data.history : [];

                historyWrap.innerHTML = history.length === 0
                    ? '<p style="text-align:center; padding:20px;">Bạn chưa có đơn nào.</p>'
                    : history.map((item) => {
                        const leaveTitle = getReasonLeaveType(item.reason, item.leave_type);
                        const reason = getCleanReason(item.reason);
                        const duration = item.duration || calculateInclusiveDays(item.start_date, item.end_date) || 0;

                        return `
                            <div class="leave-history-item">
                                <div>
                                    <h3>${escapeHtml(leaveTitle)}</h3>
                                    <p>${escapeHtml(item.start_date)} → ${escapeHtml(item.end_date)} (${escapeHtml(duration)} ngày)</p>
                                    <small>${escapeHtml(reason || 'Không có lý do')}</small>
                                </div>
                                <span class="badge badge-${item.status === 'Approved' ? 'success' : item.status === 'Rejected' ? 'danger' : 'warning'}">
                                    ${escapeHtml(item.status)}
                                </span>
                            </div>
                        `;
                    }).join('');
            }
        } catch (e) {
            console.error("Lỗi tải đơn cá nhân:", e);
        }
    }

    window.loadManagerApprovals = async function () {
        const leaveList = document.getElementById('js-leave-list');
        const taskList = document.getElementById('js-task-list');

        if (!leaveList && !taskList) return;

        try {
            if (leaveList) {
                leaveList.innerHTML = '<p class="loading-msg">Đang truy vấn đơn nghỉ phép chờ duyệt...</p>';

                const resLeaves = await callApi('/admin/leaves');

                if (resLeaves.status === 'success') {
                    const leaves = Array.isArray(resLeaves.data) ? resLeaves.data : [];
                    const countEl = document.getElementById('js-count-leaves');

                    if (countEl) {
                        countEl.innerText = leaves.length;
                    }

                    leaveList.innerHTML = leaves.length === 0
                        ? '<p class="empty-msg" style="text-align:center; padding:20px; color:#999;">Bạn đã xử lý hết đơn nghỉ phép.</p>'
                        : leaves.map((item) => {
                            const leaveId = item.id || item.leave_id;
                            const employeeName = item.employee_name || 'Nhân viên';
                            const leaveTitle = getReasonLeaveType(item.reason, item.leave_type);
                            const reason = getCleanReason(item.reason);
                            const duration = item.duration || calculateInclusiveDays(item.start_date, item.end_date) || 0;

                            return `
                                <article class="approval-card" data-approval-card="leave" data-approval-id="${escapeHtml(leaveId)}">
                                    <div class="approval-avatar">${escapeHtml(employeeName.substring(0, 2).toUpperCase())}</div>

                                    <div class="approval-content">
                                        <h3>Đơn nghỉ phép: ${escapeHtml(employeeName)}</h3>
                                        <p>${escapeHtml(reason || 'Không có lý do')}</p>

                                        <div class="approval-meta">
                                            <span class="badge badge-primary">${escapeHtml(leaveTitle)}</span>
                                            <span class="badge badge-info">${escapeHtml(duration)} ngày</span>
                                            <span class="badge badge-success">Từ: ${escapeHtml(item.start_date || 'N/A')}</span>
                                        </div>
                                    </div>

                                    <div class="approval-actions">
                                        <button class="btn btn-danger-soft" onclick="processApproval('leave', ${Number(leaveId)}, 'Rejected')">Từ chối</button>
                                        <button class="btn btn-primary" onclick="processApproval('leave', ${Number(leaveId)}, 'Approved')">Duyệt phép</button>
                                    </div>
                                </article>
                            `;
                        }).join('');
                } else {
                    leaveList.innerHTML = `<p style="color:red; text-align:center; padding:20px;">Lỗi: ${escapeHtml(resLeaves.message)}</p>`;
                }
            }

            if (taskList) {
                taskList.innerHTML = '<p class="loading-msg">Đang tải danh sách công việc từ hệ thống...</p>';

                const resTasks = await callApi('/tasks/submit');

                if (resTasks.status === 'success') {
                    const allTasks = Array.isArray(resTasks.data) ? resTasks.data : [];
                    const tasks = allTasks.filter(isApprovalTask);
                    const countEl = document.getElementById('js-count-tasks');

                    if (countEl) {
                        countEl.innerText = tasks.length;
                    }

                    taskList.innerHTML = tasks.length === 0
                        ? '<p class="empty-msg" style="text-align:center; padding:20px; color:#999;">Mọi công việc đã được xử lý xong.</p>'
                        : tasks.map((item) => {
                            const taskId = item.id || item.task_id || 0;
                            const projectId = item.project_id || item.project || 'N/A';
                            const taskTitle = item.title || item.task_name || 'Không có tên';
                            const meta = getTaskApprovalMeta(item);

                            return `
                                <article class="approval-card" data-approval-card="task" data-approval-id="${escapeHtml(taskId)}">
                                    <div class="approval-avatar">
                                        ${escapeHtml((item.assignee_name || item.assigner_name || 'TK').substring(0, 2).toUpperCase())}
                                    </div>

                                    <div class="approval-content">
                                        <h3>${escapeHtml(meta.titlePrefix)}: ${escapeHtml(taskTitle)}</h3>
                                        <p>${escapeHtml(item.description || 'Không có mô tả')}</p>

                                        <div class="approval-meta">
                                            <span class="badge ${escapeHtml(meta.badgeClass)}">${escapeHtml(meta.badge)}</span>
                                            <span class="badge badge-primary">Dự án ID: #${escapeHtml(projectId)}</span>
                                            <span class="badge badge-info">Trạng thái DB: ${escapeHtml(item.status)}</span>
                                        </div>
                                    </div>

                                    <div class="approval-actions">
                                        <button class="btn btn-danger-soft" onclick="processApproval('task', ${Number(taskId)}, 'Rejected')">Từ chối</button>
                                        <button class="btn btn-primary" onclick="processApproval('task', ${Number(taskId)}, 'Approved')">${escapeHtml(meta.approveText)}</button>
                                    </div>
                                </article>
                            `;
                        }).join('');
                } else {
                    taskList.innerHTML = `<p style="color:red; text-align:center; padding:20px;">Lỗi: ${escapeHtml(resTasks.message)}</p>`;
                }
            }
        } catch (e) {
            console.error("Lỗi tải phê duyệt:", e);

            if (taskList) {
                taskList.innerHTML = `<p style="color:red; text-align:center; padding:20px;">Lỗi tải task phê duyệt: ${escapeHtml(e.message)}</p>`;
            }
        }
    };

    window.processApproval = async (type, id, action) => {
        if (!id || id === 0) {
            if (window.CAHToast) {
                CAHToast.error("Lỗi Dữ Liệu", "Không tìm thấy ID để phê duyệt.");
            }

            return;
        }

        try {
            let endpoint = '';
            let method = '';
            let body = null;

            if (type === 'leave') {
                endpoint = `/leaves/${id}/approve`;
                method = 'PATCH';
                body = { action };
            } else if (type === 'task') {
                const taskAction = action === 'Approved' ? 'approve' : 'reject';
                endpoint = `/tasks/${id}/${taskAction}`;
                method = 'POST';
                body = {};
            }

            const res = await callApi(endpoint, method, body);

            if (res.status === 'success') {
                const card = document.querySelector(`[data-approval-card="${type}"][data-approval-id="${id}"]`);

                if (card) {
                    card.remove();
                }

                if (window.CAHToast) {
                    CAHToast.success("Thành công", res.message);
                }

                await window.loadManagerApprovals();
                await loadPersonalLeaveData();
            } else {
                if (window.CAHToast) {
                    CAHToast.error("Thất bại", res.message);
                }
            }
        } catch (err) {
            if (window.CAHToast) {
                CAHToast.error("Lỗi hệ thống", err.message);
            }
        }
    };

    let approvalReloadTimer = null;

    function scheduleApprovalReload() {
        const taskList = document.getElementById('js-task-list');
        const leaveList = document.getElementById('js-leave-list');

        if (!taskList && !leaveList) return;

        window.clearTimeout(approvalReloadTimer);

        approvalReloadTimer = window.setTimeout(() => {
            window.loadManagerApprovals();
        }, 180);
    }

    document.addEventListener("DOMContentLoaded", () => {
        updateClock();
        setInterval(updateClock, 1000);

        document.querySelectorAll('[data-leave-form]').forEach((form) => {
            const startInput = form.querySelector('[name="start_date"]');
            const endInput = form.querySelector('[name="end_date"]');

            startInput?.addEventListener('change', () => syncLeaveDuration(form));
            endInput?.addEventListener('change', () => syncLeaveDuration(form));
        });

        loadAttendanceData();
        loadPersonalLeaveData();
        loadManagerApprovals();
    });

    window.addEventListener("focus", scheduleApprovalReload);
    window.addEventListener("pageshow", scheduleApprovalReload);

    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) {
            scheduleApprovalReload();
        }
    });

    document.addEventListener("click", async (e) => {
        const payrollBtn = e.target.closest("[data-payroll-action]");

        if (payrollBtn) {
            const action = payrollBtn.dataset.payrollAction;

            if (action === "check-in" || action === "check-out") {
                const endpoint = action === "check-in" ? "/attendance/checkin" : "/attendance/checkout";

                try {
                    const res = await callApi(endpoint, 'POST');

                    if (res.status === 'success') {
                        if (window.CAHToast) {
                            CAHToast.success("Thành công", res.message);
                        }

                        loadAttendanceData();
                    } else {
                        if (window.CAHToast) {
                            CAHToast.error("Lỗi", res.message);
                        }
                    }
                } catch (err) {
                    if (window.CAHToast) {
                        CAHToast.error("Lỗi", err.message);
                    }
                }
            }
        }

        const tabBtn = e.target.closest("[data-approval-tab]");

        if (tabBtn) {
            handleTabSwitch(tabBtn);
        }
    });

    document.addEventListener("submit", async (e) => {
        const form = e.target.closest("[data-leave-form]");

        if (!form) return;

        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');

        if (btn) {
            btn.disabled = true;
        }

        try {
            syncLeaveDuration(form);

            const formData = new FormData(form);
            const payload = {
                leave_type: formData.get('leave_type') || 'annual',
                duration: formData.get('duration') || '',
                start_date: formData.get('start_date') || '',
                end_date: formData.get('end_date') || '',
                reason: formData.get('reason') || ''
            };

            /*
             * Hiện chưa gửi file attachment vì API /api/leaves đang nhận JSON.
             * File upload cần endpoint riêng hoặc bảng lưu attachment riêng để production-ready.
             */
            const res = await callApi('/leaves', 'POST', payload);

            if (res.status === 'success') {
                if (window.CAHToast) {
                    CAHToast.success("Thành công", res.message);
                }

                form.reset();
                loadPersonalLeaveData();
            } else {
                if (window.CAHToast) {
                    CAHToast.error("Lỗi", res.message);
                }
            }
        } catch (err) {
            if (window.CAHToast) {
                CAHToast.error("Lỗi", err.message);
            }
        } finally {
            if (btn) {
                btn.disabled = false;
            }
        }
    });
})();