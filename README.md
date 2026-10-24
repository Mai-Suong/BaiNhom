# Website Quản lý Giải Bóng rổ Nhà nghề Mỹ (NBA)

Đồ án môn Phát triển Phần mềm Mã nguồn mở. Viết bằng PHP + MySQL (MariaDB), chạy trên XAMPP.

## Thành viên nhóm

| Thành viên | MSSV | Phụ trách |
|---|---|---|
| Sầm Thị Mai Sương (Trưởng nhóm) | 66133236 | TV1: Database, khung dự án, đăng nhập & phân quyền, quản lý tài khoản |
| Đặng Ngọc Nhật Duy | 66130649 | TV2: Đội bóng, sân thi đấu, huấn luyện viên, trọng tài, trang chủ |
| Nguyễn Đỗ Thảo Nguyên | 66132371 | TV3: Cầu thủ, chuyển nhượng, chấn thương, trao giải, trang "Của tôi" |
| Ngô Quốc Huy | 66131349 | TV4: Mùa giải, phân bảng, lịch thi đấu, kết quả & điểm hiệp, phân công trọng tài, dashboard |
| Nguyễn Tiến Đạt | 66130475 | TV5: Box score, thống kê & biểu đồ, bảng xếp hạng, playoff |

### Bảng database và file dữ liệu mẫu của từng người

| Thành viên | Bảng phụ trách | File dữ liệu mẫu |
|---|---|---|
| TV1 Sương | `tai_khoan` (+ tạo toàn bộ cấu trúc) | `qlbongro.sql` |
| TV2 Duy | `san_thi_dau`, `doi_bong`, `huan_luyen_vien`, `trong_tai` | `02_du_lieu_doi_bong.sql` |
| TV3 Nguyên | `cau_thu`, `chuyen_nhuong`, `chan_thuong`, `trao_giai` | `03_du_lieu_cau_thu.sql` |
| TV4 Huy | `mua_giai`, `phan_bang`, `tran_dau`, `diem_hiep`, `phan_cong_trong_tai` | `04_du_lieu_tran_dau.sql` |
| TV5 Đạt | `chi_so_tran_dau`, `loat_playoff` | `05_du_lieu_thong_ke.sql` |

## Cài đặt và chạy

1. Cài XAMPP, mở XAMPP Control Panel, bấm **Start** cho **Apache** và **MySQL**.
2. Clone repo vào thư mục `htdocs` của XAMPP:
   ```
   cd /d D:\xampp\htdocs
   git clone https://github.com/Mai-Suong/BaiNhom.git
   ```
   (Đổi `D:` thành ổ đĩa cài XAMPP trên máy bạn.)
3. Mở `http://localhost/phpmyadmin` → tab **Import** → chọn file `database/qlbongro.sql` → **Import**.
   File tự tạo database `qlbongro`, không cần tạo trước.
4. Mở trình duyệt: `http://localhost/BaiNhom`

**Tài khoản admin mặc định:** `admin` / `admin123`

**Kết nối database** (mặc định của XAMPP): host `localhost`, user `root`, mật khẩu để trống, database `qlbongro`.
Máy nào đặt mật khẩu MySQL khác thì tạo file `config/db.local.php` (đã được bỏ qua trong `.gitignore`).

## Thứ tự import database

1. `database/qlbongro.sql` (cấu trúc bảng + dữ liệu danh mục) **luôn import đầu tiên**
2. Các file dữ liệu mẫu theo đúng số thứ tự: `02_du_lieu_doi_bong.sql` → `03_du_lieu_cau_thu.sql` → `04_du_lieu_tran_dau.sql` → `05_du_lieu_thong_ke.sql`

Khi có người sửa cấu trúc bảng: xóa database `qlbongro` rồi import lại tất cả theo đúng thứ tự trên.

## Quy ước làm việc nhóm

**Repo:** mọi người làm trực tiếp trên repo `Mai-Suong/BaiNhom` (đã được mời làm Collaborator), không dùng fork.

**Mỗi lần làm một chức năng:**
1. Lấy code mới nhất: `git checkout main` rồi `git pull`
2. Tạo nhánh mới: `git checkout -b feature/tvX-ten-chuc-nang`
   (VD: `feature/tv2-doi-bong`, `feature/tv3-cau-thu`, `fix/tv1-loi-dang-nhap`)
3. Code, commit thường xuyên với nội dung rõ ràng (VD: `Them chuc nang sua cau thu`)
4. Push nhánh lên: `git push -u origin feature/tvX-ten-chuc-nang`
5. Tạo Pull Request vào `main`. **Mỗi PR chỉ chứa một chức năng.**
6. Trưởng nhóm review rồi merge. Không push thẳng vào `main`.

**Lưu ý:**
- Mỗi người commit bằng tài khoản GitHub của mình, `git config user.email` phải trùng email GitHub.
- Không tự sửa cấu trúc bảng trong phpMyAdmin. Muốn sửa: sửa file `database/qlbongro.sql`, tạo PR và báo cả nhóm.
- Không đưa ảnh upload, file mật khẩu lên GitHub (đã cấu hình trong `.gitignore`).
