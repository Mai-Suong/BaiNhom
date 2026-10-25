# Module Cầu thủ (TV3)

## Cài đặt trên XAMPP

1. Clone repo nhóm vào `htdocs/BaiNhom` và import `database/qlbongro.sql` (xem README).
2. `config/db.php` mặc định dùng thư mục `/BaiNhom` và database `qlbongro`. Máy nào đặt mật khẩu MySQL thì tạo `config/db.local.php` chứa `<?php define('DB_PASS', 'matkhau');`. Khi TV1 push file `db.php` chung, giữ file của TV1 nhưng đảm bảo có hàm `ketNoi()` trả về PDO và hằng `BASE_URL`.
3. Thư mục `uploads/cau_thu/` không có trên GitHub (bị `.gitignore`), code tự tạo khi upload ảnh lần đầu, kèm file `.htaccess` chặn chạy PHP.
4. `dang_nhap_thu.php` không có trong repo. Khi TV1 chưa làm xong đăng nhập, tự tạo file này ở máy mình (KHÔNG commit) để test:
   - `http://localhost/BaiNhom/dang_nhap_thu.php?vai_tro=admin`
   - `http://localhost/BaiNhom/dang_nhap_thu.php?vai_tro=cau_thu&id=1`
   - **Xóa `dang_nhap_thu.php` trước khi nộp.**

## Các file và chức năng

| File | Chức năng trong bảng phân công |
|---|---|
| `includes/ham_cau_thu.php` | Toàn bộ SQL và nghiệp vụ (kiểm tra trùng số áo, upload ảnh, chuyển nhượng dùng transaction, chấn thương, lấy chỉ số từ `v_chi_so_trung_binh`) |
| `admin/cau_thu/index.php` | Danh sách, lọc theo đội / vị trí / quốc tịch / trạng thái, tìm theo tên, phân trang |
| `admin/cau_thu/form.php` | Thêm / sửa cầu thủ, upload ảnh, kiểm tra trùng số áo |
| `admin/cau_thu/xoa.php` | Xóa (chặn nếu đã có thống kê trận hoặc giải thưởng) |
| `admin/cau_thu/chuyen_nhuong.php` | Chuyển nhượng (đổi đội + lưu lịch sử) |
| `admin/cau_thu/chan_thuong.php` | Quản lý chấn thương |
| `includes/ham_trao_giai.php` | Hàm trao giải, lấy danh hiệu cầu thủ (`layDanhHieuCauThu`) và HLV (`layDanhHieuHLV`, TV2 dùng được) |
| `admin/trao_giai/index.php` | Trao giải cuối mùa (MVP, ROY… cho cầu thủ, COY cho HLV), thay hoặc hủy người nhận |
| `cau_thu/ho_so.php?id=` | Trang hồ sơ cầu thủ cho Khách (có mục Danh hiệu) |
| `cau_thu/cua_toi.php` | Trang "Của tôi" khi cầu thủ đăng nhập |

## Cần thống nhất với nhóm

- **TV1**: sau khi đăng nhập lưu `$_SESSION['vai_tro']` và `$_SESSION['ma_cau_thu']`; trang đăng nhập ở `/dang_nhap.php`. Khác thì sửa `includes/kiem_tra_quyen.php`.
- **TV2**: trang chi tiết đội có thể link sang `cau_thu/ho_so.php?id=...` để xem từng cầu thủ.
- **TV5**: chỉ số trung bình lấy từ view `v_chi_so_trung_binh` có sẵn trong CSDL.
- Header/footer: khi nhóm có giao diện chung, thay `dauTrang()` / `cuoiTrang()` trong `includes/giao_dien.php`.

## Quy tắc nghiệp vụ đã cài

- Không trùng số áo trong cùng đội (kiểm tra bằng PHP + ràng buộc `uq_doi_so_ao` trong CSDL). Khi sửa không báo trùng với chính mình.
- Form sửa **không cho đổi đội**; đổi đội bắt buộc qua Chuyển nhượng để có lịch sử.
- Chuyển nhượng: đội mới phải khác đội cũ; nếu số áo trùng ở đội mới thì yêu cầu nhập số khác; "Giải phóng" đưa cầu thủ về tự do; cầu thủ giải nghệ không chuyển nhượng được. Cập nhật cầu thủ và ghi lịch sử nằm trong một transaction.
- Trạng thái tự tính lại: còn chấn thương chưa hồi phục thì "Chấn thương", không có đội thì "Tự do", còn lại "Đang thi đấu". "Giải nghệ" chỉ đổi bằng tay ở form sửa.
- Ảnh: chỉ JPG/PNG/WEBP, tối đa 2MB, kiểm tra nội dung thật của file, đổi tên ngẫu nhiên, xóa ảnh cũ khi thay; thư mục `uploads/cau_thu/` chặn chạy file PHP.
- Trang "Của tôi" lấy mã cầu thủ từ session, cầu thủ chỉ được đổi ảnh đại diện.
- Mọi form POST có mã CSRF; mọi câu SQL dùng prepared statement.

## Danh sách tự test

- [ ] Thêm cầu thủ trùng số áo cùng đội → báo lỗi; khác đội → thêm được
- [ ] Sửa cầu thủ giữ nguyên số áo → không báo trùng
- [ ] Upload file .txt đổi đuôi thành .jpg → bị từ chối; ảnh > 2MB → bị từ chối
- [ ] Lọc kết hợp đội + vị trí + quốc tịch, phân trang giữ nguyên bộ lọc
- [ ] Chuyển nhượng sang đội có người cùng số áo → yêu cầu đổi số
- [ ] Giải phóng → cầu thủ thành "Tự do", lịch sử ghi "Đến đội: Tự do"
- [ ] Thêm chấn thương → trạng thái "Chấn thương"; đánh dấu hồi phục → trở lại "Đang thi đấu"
- [ ] Xóa cầu thủ đã có box score → bị chặn kèm thông báo
- [ ] Cầu thủ chưa đá trận nào → hồ sơ hiện "Chưa có dữ liệu thi đấu"
- [ ] Đăng nhập cầu thủ A, sửa URL không xem/sửa được cầu thủ B
- [ ] Trao MVP cho cầu thủ → hiện trong bảng kết quả và mục Danh hiệu ở hồ sơ
- [ ] Trao MVP lần 2 cho người khác cùng mùa → thay người nhận, không báo lỗi trùng
- [ ] Chọn giải COY → ô chọn đổi sang danh sách HLV
- [ ] Hủy trao giải → mất khỏi bảng và hồ sơ
- [ ] Cầu thủ đã nhận giải → không xóa được
