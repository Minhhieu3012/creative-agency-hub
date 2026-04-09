<!-- Form dùng chung - Cách dùng: thay action, thêm/bớt các field tùy ý -->
<div class="card shadow mb-4">

    <!-- Tiêu đề form -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">FORM_TITLE</h6>
    </div>

    <!-- Nội dung form -->
    <div class="card-body">
        <form action="YOUR_ACTION.php" method="POST">

            <!-- Hàng 1: 2 cột -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" 
                           name="fullname" placeholder="Nhập họ tên..." required>
                </div>
                <div class="form-group col-md-6">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" 
                           name="email" placeholder="Nhập email..." required>
                </div>
            </div>

            <!-- Hàng 2: 2 cột -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Số điện thoại</label>
                    <input type="text" class="form-control" 
                           name="phone" placeholder="Nhập số điện thoại...">
                </div>
                <div class="form-group col-md-6">
                    <label>Trạng thái</label>
                    <select class="form-control" name="status">
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Ngừng hoạt động</option>
                    </select>
                </div>
            </div>

            <!-- Hàng 3: full width -->
            <div class="form-group">
                <label>Ghi chú</label>
                <textarea class="form-control" name="note" 
                          rows="3" placeholder="Nhập ghi chú..."></textarea>
            </div>

            <!-- Nút bấm -->
            <div class="text-right">
                <button type="button" class="btn btn-secondary mr-2">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Lưu lại
                </button>
            </div>

        </form>
    </div>

</div>