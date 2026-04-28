# Creative Agency Hub - API Contracts

Tài liệu này quy định chuẩn giao tiếp API giữa Frontend và Backend cho toàn bộ các module trong dự án.

> Tất cả các thành viên **BẮT BUỘC** tuân thủ định dạng này.

---

## 1. Cấu trúc Response Chuẩn

### ✅ Thành công (`200 OK`, `201 Created`)

```json
{
  "status": "success",
  "message": "Mô tả kết quả",
  "data": {}
}
```

### ❌ Thất bại (`400`, `401`, `403`, `404`, `500`)

```json
{
  "status": "error",
  "message": "Mô tả lỗi cụ thể",
  "errors": {
    "email": "Email đã tồn tại"
  }
}
```

> `errors` là tùy chọn, chỉ dùng khi có lỗi validation chi tiết.

---

## 2. Xác thực (Authentication)

Tất cả route yêu cầu đăng nhập phải gửi kèm JWT Token trong header:
Authorization: Bearer <your_jwt_token>

---

## 3. Endpoints

### Auth & Tài khoản

> Nhánh: `core-auth-security` — **Hiếu**

| Method | Endpoint             | Mô tả                             |
| ------ | -------------------- | --------------------------------- |
| `POST` | `/api/auth/login`    | Đăng nhập, trả về JWT Token       |
| `POST` | `/api/auth/register` | Đăng ký tài khoản nhân viên mới   |
| `GET`  | `/api/auth/me`       | Lấy thông tin user đang đăng nhập |

---

### Quản lý Nhân sự (HRM)

> Nhánh: `hrm-employee-crud` — **Thành**

| Method   | Endpoint             | Mô tả                   |
| -------- | -------------------- | ----------------------- |
| `GET`    | `/api/employees`     | Lấy danh sách nhân viên |
| `POST`   | `/api/employees`     | Tạo nhân viên mới       |
| `GET`    | `/api/employees/:id` | Xem chi tiết nhân viên  |
| `PUT`    | `/api/employees/:id` | Cập nhật hồ sơ          |
| `DELETE` | `/api/employees/:id` | Xóa nhân viên           |

---

### Quản lý Công việc (Task)

> Nhánh: `task-kanban-board` — **Huy & Bảo**

| Method  | Endpoint                  | Mô tả                                                                                               |
| ------- | ------------------------- | --------------------------------------------------------------------------------------------------- |
| `GET`   | `/api/tasks`              | Lấy danh sách task (filter: `?status=...&project_id=...`). Trạng thái: `To do, Doing, Review, Done` |
| `POST`  | `/api/tasks`              | Tạo task mới                                                                                        |
| `PATCH` | `/api/tasks/:id/status`   | Cập nhật trạng thái Kanban. Các giá trị hợp lệ: `To do, Doing, Review, Done`                        |
| `POST`  | `/api/tasks/:id/comments` | Thêm bình luận vào task                                                                             |

---

#### Field `status` - Allowed Values

| Giá trị  | Mô tả                  |
| -------- | ---------------------- |
| `To do`  | Task mới, chưa bắt đầu |
| `Doing`  | Đang thực hiện         |
| `Review` | Chờ review/kiểm tra    |
| `Done`   | Hoàn thành             |

> Áp dụng cho cả `POST /api/tasks` (field `status` khi tạo) và `PATCH /api/tasks/:id/status` (field `status` khi cập nhật).

#### Ví dụ Request — `PATCH /api/tasks/:id/status`

```json
{
  "status": "Review"
}
```

#### Ví dụ Request — `GET /api/tasks` với filter

```
GET /api/tasks?status=Review&project_id=1
```

---

### Chấm công & Nghỉ phép

> Nhánh: `attendance-payroll` — **Tiến**

| Method  | Endpoint                  | Mô tả                     |
| ------- | ------------------------- | ------------------------- |
| `POST`  | `/api/attendance/checkin` | Chấm công (Web check-in)  |
| `POST`  | `/api/leaves`             | Gửi đơn xin nghỉ phép     |
| `PATCH` | `/api/leaves/:id/approve` | Manager duyệt/từ chối đơn |
