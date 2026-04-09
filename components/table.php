<!-- Table dùng chung - Cách dùng: thay nội dung thead và tbody -->
<div class="card shadow mb-4">

    <!-- Tiêu đề bảng -->
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">TABLE_TITLE</h6>
        <button class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Thêm mới
        </button>
    </div>

    <!-- Nội dung bảng -->
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%">

                <!-- Tiêu đề cột -->
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Cột 1</th>
                        <th>Cột 2</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <!-- Dữ liệu -->
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Dữ liệu 1</td>
                        <td>Dữ liệu 2</td>
                        <td>
                            <span class="badge badge-success">Hoạt động</span>
                        </td>
                        <td>
                            <button class="btn btn-info btn-sm">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>
    </div>

</div>