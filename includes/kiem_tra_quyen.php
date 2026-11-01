<?php
/**
 * Phiên đăng nhập, phân quyền, chống CSRF, thông báo flash.
 *
 * QUAN TRỌNG: thống nhất với TV1 (người làm đăng nhập) rằng sau khi đăng nhập
 * thành công sẽ lưu vào session:
 *   $_SESSION['ma_tai_khoan'], $_SESSION['vai_tro'] ('admin' | 'cau_thu'),
 *   $_SESSION['ma_cau_thu']  (chỉ có với tài khoản cầu thủ)
 * Nếu TV1 dùng tên khác thì sửa lại 2 hàm yeuCau... bên dưới.
 */
require_once __DIR__ . '/../config/db.php';

// Số giây không thao tác thì tự đăng xuất (3 phút)
const THOI_GIAN_CHO_TOI_DA = 3 * 60;

if (session_status() === PHP_SESSION_NONE) {
    // Cookie phiên chỉ sống trong phiên trình duyệt: đóng hẳn trình duyệt là mất đăng nhập
    ini_set('session.gc_maxlifetime', (string) (THOI_GIAN_CHO_TOI_DA * 2));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Đang đăng nhập mà quá 3 phút không thao tác -> đăng xuất; ngược lại ghi lại lần hoạt động cuối
if (!empty($_SESSION['ma_tai_khoan'])) {
    $lanCuoi = $_SESSION['hoat_dong_cuoi'] ?? time();
    if (time() - $lanCuoi > THOI_GIAN_CHO_TOI_DA) {
        unset($_SESSION['ma_tai_khoan'], $_SESSION['ten_dang_nhap'], $_SESSION['vai_tro'],
              $_SESSION['ma_cau_thu'], $_SESSION['hoat_dong_cuoi']);
        session_regenerate_id(true);
        $_SESSION['thong_bao'] = ['loai' => 'loi',
            'noi_dung' => 'Phiên đăng nhập đã hết hạn do không hoạt động quá 3 phút. Vui lòng đăng nhập lại.'];
    } else {
        $_SESSION['hoat_dong_cuoi'] = time();
    }
}

// Trang đăng nhập của TV1
const TRANG_DANG_NHAP = BASE_URL . '/dang_nhap.php';

function yeuCauAdmin(): void
{
    if (($_SESSION['vai_tro'] ?? '') !== 'admin') {
        header('Location: ' . TRANG_DANG_NHAP);
        exit;
    }
}

/** Trả về mã cầu thủ của tài khoản đang đăng nhập */
function yeuCauCauThu(): int
{
    if (($_SESSION['vai_tro'] ?? '') !== 'cau_thu' || empty($_SESSION['ma_cau_thu'])) {
        header('Location: ' . TRANG_DANG_NHAP);
        exit;
    }
    return (int) $_SESSION['ma_cau_thu'];
}

/* ---------- Chống CSRF cho các form POST ---------- */
function maCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function oCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . maCsrf() . '">';
}

function kiemTraCsrf(): void
{
    if (!hash_equals(maCsrf(), $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Phiên làm việc không hợp lệ, vui lòng tải lại trang.');
    }
}

/* ---------- Thông báo hiện 1 lần sau khi chuyển trang ---------- */
function datThongBao(string $loai, string $noiDung): void
{
    $_SESSION['thong_bao'] = ['loai' => $loai, 'noi_dung' => $noiDung]; // loai: ok | loi
}

function layThongBao(): ?array
{
    $tb = $_SESSION['thong_bao'] ?? null;
    unset($_SESSION['thong_bao']);
    return $tb;
}

function chuyenTrang(string $url): void
{
    header('Location: ' . $url);
    exit;
}
