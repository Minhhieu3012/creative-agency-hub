# 🚀 Creative Agency Hub

> A modern work management platform for creative agencies. Built with PHP & MySQL. It streamlines task collaboration, content approval workflows, and client communication through Kanban-style tracking and a dedicated client portal.

---

## 🎯 Project Overview
**Creative Agency Hub** is a specialized management system for creative agencies. The project provides comprehensive digital solutions ranging from human resource management (HRM) and workflow tracking (Kanban/Gantt) to an interactive client portal (Client Portal).

## 🛠 Tech Stack
### Frontend:
Core:
  - HTML5, CSS3, Vanilla JavaScript
  - UI Framework: Bootstrap 5 (Customized Base Template)
Features:
  - Dynamic Kanban Board (Drag & Drop UI)
  - Gantt Charts
  - Advanced Data Filtering (project, assignee, status, deadline)

### Backend:


---

## 👥 Đối tượng & Phân quyền (RBAC - Role Based Access Control)
Hệ thống vận hành với 4 nhóm vai trò chính:
1. **Admin:** Quản lý tài khoản, danh mục phòng ban, chức vụ và thiết lập cấu trúc tổ chức.
2. **Manager:** Tạo dự án, giao việc, phê duyệt đơn từ (nghỉ phép, công tác) và theo dõi Dashboard.
3. **Employee:** Cập nhật hồ sơ, chấm công, nhận task, báo cáo tiến độ và gửi đơn từ.
4. **Client:** Truy cập Client Portal riêng biệt để theo dõi tiến độ dự án.

---

## 🌟 Các chức năng cốt lõi (Core Features)

### 1. Quản lý Nhân sự (HRM)
- **Hồ sơ điện tử:** Quản lý (CRUD) thông tin nhân viên, hợp đồng lao động, bảo hiểm.
- **Chấm công & Tính lương:** Web-checkin hằng ngày; hệ thống tự động tính lương dựa trên ngày công và KPI.
- **Quản lý nghỉ phép:** Gửi đơn, phê duyệt và tự động trừ quỹ phép.

### 2. Quản lý Công việc & Dự án (Work Management)
- **Bảng điều khiển (Board):** Theo dõi công việc trực quan qua giao diện Kanban và Gantt Chart.
- **Quản lý Task:** CRUD nhiệm vụ, gán người thực hiện (assignee) và deadline.
- **Quy trình tương tác:** Chuyển đổi trạng thái (To do -> Doing -> Done), bình luận (comment thread) và đính kèm tài liệu trong từng đầu việc.
- **Quy trình phê duyệt (Approval Flow):** Submit -> Review -> Approve/Reject.

---

## ⚙️ Quy ước kỹ thuật (Technical Guidelines) - Dành cho Dev Team

Để đảm bảo tính nhất quán và hạn chế conflict, toàn bộ thành viên trong nhóm vui lòng tuân thủ các quy tắc sau:

### 1. Database
- Sử dụng chung file `database/schema.sql`. Cập nhật file này mỗi khi có thay đổi cấu trúc bảng.
- Bắt buộc sử dụng `charset: utf8mb4` và `collation: utf8mb4_unicode_ci` cho toàn bộ database.

### 2. API & Backend
- Tham khảo kỹ file `API_CONTRACTS.md` trước khi tạo endpoint mới.
- Thiết kế API theo chuẩn RESTful.
- Xử lý triệt để các vấn đề bảo mật: SQL Injection, XSS, CSRF (Sử dụng Middleware/Auth protection).

---

*Dự án được phát triển nhằm mục đích báo cáo học thuật. Vui lòng tham khảo mã nguồn và tài liệu đính kèm.*
