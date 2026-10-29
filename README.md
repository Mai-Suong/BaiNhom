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
| TV2 Duy | `san_thi_dau`, `doi_bong`, `huan_luyen_vien`, `trong_tai`, `nhiem_ky_hlv` | `02_du_lieu_doi_bong.sql` |
| TV3 Nguyên | `cau_thu`, `chuyen_nhuong`, `chan_thuong`, `trao_giai` | `03_du_lieu_cau_thu.sql` , `06_du_lieu_trao_giai.sql`|
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
Máy nào đặt mật khẩu MySQL khác thì tạo file `config/db.local.php` chứa `<?php define('DB_PASS', 'matkhau');` (file này không đưa lên GitHub).

## Thứ tự import database

1. `database/qlbongro.sql` (cấu trúc bảng + dữ liệu danh mục) **luôn import đầu tiên**
2. Các file dữ liệu mẫu theo đúng số thứ tự: `02_du_lieu_doi_bong.sql` → `03_du_lieu_cau_thu.sql` → `04_du_lieu_tran_dau.sql` → `05_du_lieu_thong_ke.sql` → `06_du_lieu_trao_giai.sql`

Khi có người sửa cấu trúc bảng: xóa database `qlbongro` rồi import lại tất cả theo đúng thứ tự trên.

## Cấu trúc thư mục

Mỗi chức năng là **một thư mục riêng**: trang quản trị nằm trong `admin/ten_module/`, trang cho Khách nằm trong `ten_module/` ở thư mục gốc.

```
BaiNhom/
├── config/db.php              Kết nối database: hàm ketNoi(), hằng BASE_URL
├── includes/
│   ├── init.php               File khởi tạo chung: trang mới require file này đầu tiên
│   ├── kiem_tra_quyen.php     Session, yeuCauAdmin(), yeuCauCauThu(), CSRF, thông báo, chuyenTrang()
│   ├── ham_chung.php          e(), url(), daDangNhap(), laAdmin(), laCauThu(), tenDangNhap()
│   ├── header.php / footer.php              Giao diện chung trang Khách (menu trên)
│   ├── admin_header.php / admin_footer.php  Giao diện chung trang Quản trị (menu trái)
│   └── ham_<module>.php       Hàm SQL riêng của từng module (VD: ham_cau_thu.php của TV3)
├── admin/
│   ├── index.php              Dashboard
│   └── <module>/index.php     Trang quản trị từng module (VD: admin/cau_thu/, admin/doi_bong/)
├── <module>/                  Trang cho Khách (VD: cau_thu/ho_so.php, doi_bong/index.php)
├── khu_vuc/index.php          TRANG MẪU dùng giao diện chung: xem để làm theo
├── assets/css/style.css       CSS chung (màu NBA)
├── assets/js/main.js          JS chung (nút có class "btn-xoa" tự hỏi xác nhận)
├── uploads/                   Ảnh tải lên (không đưa lên GitHub)
├── database/qlbongro.sql
├── index.php                  Trang chủ
└── dang_nhap.php / dang_xuat.php
```

Menu đã có sẵn link tới trang của từng người. Tạo đúng file là link chạy:

| Thành viên | Trang Khách | Trang Quản trị |
|---|---|---|
| TV2 Duy | `doi_bong/index.php` | `admin/doi_bong/`, `admin/san_thi_dau/`, `admin/huan_luyen_vien/`, `admin/trong_tai/` |
| TV3 Nguyên | `cau_thu/index.php`, `cau_thu/ho_so.php`, `cau_thu/cua_toi.php` | `admin/cau_thu/`, `admin/trao_giai/` |
| TV4 Huy | `lich_thi_dau/index.php`, `ket_qua/index.php` | `admin/mua_giai/`, `admin/phan_bang/`, `admin/lich_thi_dau/`, `admin/ket_qua/` |
| TV5 Đạt | `bang_xep_hang/index.php`, `thong_ke/index.php` | `admin/box_score/`, `admin/playoff/` |
| TV1 Sương | | `admin/tai_khoan/` |

## Cách tạo một trang mới

**Trang cho Khách** (VD: `doi_bong/index.php`):
```php
<?php
require_once __DIR__ . '/../includes/init.php';

$ds = $pdo->query('SELECT * FROM doi_bong ORDER BY ten_doi')->fetchAll();

$tieuDe = 'Danh sách đội bóng';
$menu   = 'doi_bong';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3">Danh sách đội bóng</h1>
<?php foreach ($ds as $doi): ?>
    <p><?= e($doi['ten_doi']) ?></p>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
```

**Trang Quản trị** (VD: `admin/doi_bong/index.php`): require `__DIR__ . '/../../includes/init.php'`, gọi `yeuCauAdmin();` ngay sau đó,
và dùng `admin_header.php` / `admin_footer.php` thay cho `header.php` / `footer.php`.

**Quy tắc bắt buộc:**
- In dữ liệu ra HTML luôn dùng `e(...)` (chống XSS).
- SQL có dữ liệu người dùng nhập luôn dùng `prepare` + dấu `?` (chống SQL Injection).
- Form POST luôn có `<?= oCsrf() ?>` trong form và gọi `kiemTraCsrf();` khi xử lý.
- Lưu xong thì `datThongBao('ok', 'Đã lưu');` (lỗi thì `'loi'`) rồi `chuyenTrang(url('/admin/doi_bong/index.php'));`. Thông báo tự hiện ở trang kế tiếp.
- Link nội bộ luôn dùng `url('/...')`, không ghi cứng `/BaiNhom/...`.
- Session khi đăng nhập: `$_SESSION['ma_tai_khoan']`, `['ten_dang_nhap']`, `['vai_tro']` (`admin` | `cau_thu`), `['ma_cau_thu']`.

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
