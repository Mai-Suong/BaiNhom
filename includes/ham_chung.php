<?php
/**
 * Hàm dùng chung cho cả nhóm (TV1).
 * Được nạp qua includes/init.php. Không khai báo lại các hàm đã có trong
 * includes/kiem_tra_quyen.php (TV3): yeuCauAdmin(), yeuCauCauThu(), oCsrf(), kiemTraCsrf(),
 * datThongBao(), layThongBao(), chuyenTrang().
 */

// In dữ liệu ra HTML an toàn (chống XSS). VD: echo e($doi['ten_doi']);
if (!function_exists('e')) {
    function e($s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

// Tạo link từ gốc website. VD: url('/doi_bong/index.php') -> /BaiNhom/doi_bong/index.php
function url(string $duongDan): string
{
    return BASE_URL . $duongDan;
}

// Thông tin người đang đăng nhập (lưu trong session khi đăng nhập ở dang_nhap.php):
//   $_SESSION['ma_tai_khoan'], ['ten_dang_nhap'], ['vai_tro'] ('admin' | 'cau_thu'), ['ma_cau_thu']
function daDangNhap(): bool
{
    return !empty($_SESSION['ma_tai_khoan']);
}

function laAdmin(): bool
{
    return ($_SESSION['vai_tro'] ?? '') === 'admin';
}

function laCauThu(): bool
{
    return ($_SESSION['vai_tro'] ?? '') === 'cau_thu';
}

function tenDangNhap(): string
{
    return $_SESSION['ten_dang_nhap'] ?? '';
}

// Bắt buộc đăng nhập (dùng cho trang chỉ người đã đăng nhập mới xem được)
function yeuCauDangNhap(): void
{
    if (!daDangNhap()) {
        chuyenTrang(TRANG_DANG_NHAP);
    }
}
