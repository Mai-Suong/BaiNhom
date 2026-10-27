<?php
/**
 * Đổi mật khẩu - dùng cho mọi tài khoản đã đăng nhập (admin, cầu thủ,..).
 * Phải nhập đúng mật khẩu cũ mới được đổi. Mật khẩu mới được băm trước khi lưu.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ham_tai_khoan.php';
yeuCauDangNhap();

$maTaiKhoan = (int) $_SESSION['ma_tai_khoan'];
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $mkCu    = $_POST['mat_khau_cu'] ?? '';
    $mkMoi   = $_POST['mat_khau_moi'] ?? '';
    $nhapLai = $_POST['nhap_lai'] ?? '';

    if (!tkKiemTraMatKhauCu($maTaiKhoan, $mkCu)) {
        $loi['mat_khau_cu'] = 'Mật khẩu hiện tại không đúng.';
    }
    if ($l = tkKiemTraMatKhau($mkMoi, $nhapLai)) {
        $loi['mat_khau_moi'] = $l;
    } elseif ($mkMoi === $mkCu) {
        $loi['mat_khau_moi'] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
    }

    if (!$loi) {
        tkDatMatKhau($maTaiKhoan, $mkMoi);
        session_regenerate_id(true);
        datThongBao('ok', 'Đổi mật khẩu thành công.');
        chuyenTrang(url(laAdmin() ? '/admin/index.php' : '/index.php'));
    }
}

$tieuDe = 'Đổi mật khẩu';
if (laAdmin()) {
    $menu = '';
    require __DIR__ . '/includes/admin_header.php';
} else {
    require __DIR__ . '/includes/header.php';
}

/** In một ô nhập mật khẩu kèm thông báo lỗi (nếu có) */
function oMatKhau(string $ten, string $nhan, array $loi, string $goiY = ''): void
{
    $coLoi = isset($loi[$ten]);
    ?>
    <div class="mb-3">
        <label class="form-label" for="<?= e($ten) ?>"><?= e($nhan) ?></label>
        <input class="form-control <?= $coLoi ? 'is-invalid' : '' ?>" type="password"
               id="<?= e($ten) ?>" name="<?= e($ten) ?>" required autocomplete="<?= $ten === 'mat_khau_cu' ? 'current-password' : 'new-password' ?>">
        <?php if ($coLoi): ?>
            <div class="invalid-feedback"><?= e($loi[$ten]) ?></div>
        <?php elseif ($goiY !== ''): ?>
            <div class="form-text"><?= e($goiY) ?></div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<div class="row justify-content-center">
    <div class="col-sm-10 col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-1"><i class="bi bi-key"></i> Đổi mật khẩu</h1>
                <p class="text-muted small mb-4">Tài khoản: <strong><?= e(tenDangNhap()) ?></strong></p>

                <form method="post" novalidate>
                    <?= oCsrf() ?>
                    <?php oMatKhau('mat_khau_cu', 'Mật khẩu hiện tại', $loi); ?>
                    <?php oMatKhau('mat_khau_moi', 'Mật khẩu mới', $loi, 'Ít nhất ' . TK_MAT_KHAU_TOI_THIEU . ' ký tự.'); ?>
                    <?php oMatKhau('nhap_lai', 'Nhập lại mật khẩu mới', $loi); ?>
                    <div class="d-flex gap-2">
                        <button class="btn btn-nba" type="submit">Lưu mật khẩu mới</button>
                        <a class="btn btn-outline-secondary" href="<?= url(laAdmin() ? '/admin/index.php' : '/index.php') ?>">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . (laAdmin() ? '/includes/admin_footer.php' : '/includes/footer.php'); ?>
