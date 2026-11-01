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

/* ---------- ẢNH (cầu thủ, HLV, sân, logo) ----------
 * Cột ảnh trong CSDL có thể là file tải lên (uploads/...) hoặc link ảnh trên mạng
 * (https://..., VD ảnh Wikimedia Commons trong database/08_du_lieu_anh.sql). */
function urlAnh(?string $duongDan): string
{
    $duongDan = trim((string) $duongDan);
    if (preg_match('#^https?://#i', $duongDan)) {
        return $duongDan;
    }
    return url('/' . ltrim($duongDan, '/'));
}

/** Chữ viết tắt của tên, VD "LeBron James" -> "LJ" */
function chuVietTat(string $ten): string
{
    $tu = preg_split('/\s+/u', trim($ten), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
    $chu = mb_substr($tu[0], 0, 1);
    if (count($tu) > 1) {
        $chu .= mb_substr(end($tu), 0, 1);
    }
    return mb_strtoupper($chu);
}

/**
 * Ảnh tròn của một người (cầu thủ, HLV). Không có ảnh, hoặc ảnh trên mạng không tải được
 * (mất Internet), thì hiện vòng tròn chữ viết tắt tên.
 */
function anhTron(?string $anh, string $ten, int $co = 44): string
{
    $kieu = 'width:' . $co . 'px;height:' . $co . 'px;font-size:' . (int) ($co / 2.6) . 'px';
    $thayThe = '<span class="rounded-circle bg-nba text-white fw-bold align-items-center justify-content-center flex-shrink-0 %s"'
             . ' style="' . $kieu . '" title="' . e($ten) . '">' . e(chuVietTat($ten)) . '</span>';
    if (trim((string) $anh) === '') {
        return sprintf($thayThe, 'd-inline-flex');
    }
    return '<img class="rounded-circle object-fit-cover bg-light flex-shrink-0" style="object-position:top"'
         . ' src="' . e(urlAnh($anh)) . '" alt="' . e($ten) . '" width="' . $co . '" height="' . $co . '" loading="lazy"'
         . ' onerror="this.style.display=\'none\';this.nextElementSibling.classList.replace(\'d-none\',\'d-inline-flex\')">'
         . sprintf($thayThe, 'd-none');
}

/**
 * Ảnh sân thi đấu. Không có ảnh hoặc ảnh không tải được thì hiện khung xám có biểu tượng sân.
 * $lop: class cho ảnh, VD 'img-fluid rounded mb-3'; $cao: chiều cao khung thay thế (px).
 */
function anhSan(?string $anh, string $ten, string $lop = 'img-fluid rounded', int $cao = 160): string
{
    $thayThe = '<div class="%s align-items-center justify-content-center bg-body-secondary text-secondary rounded mb-3"'
             . ' style="height:' . $cao . 'px"><i class="bi bi-building" style="font-size:' . (int) ($cao / 3) . 'px"></i></div>';
    if (trim((string) $anh) === '') {
        return sprintf($thayThe, 'd-flex');
    }
    return '<img src="' . e(urlAnh($anh)) . '" alt="' . e($ten) . '" class="' . e($lop) . '" loading="lazy"'
         . ' onerror="this.style.display=\'none\';this.nextElementSibling.classList.replace(\'d-none\',\'d-flex\')">'
         . sprintf($thayThe, 'd-none');
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
