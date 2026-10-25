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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
