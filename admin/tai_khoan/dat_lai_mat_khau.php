<?php
/** Admin - Đặt lại mật khẩu cho tài khoản khác (khi người dùng quên mật khẩu)  */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_tai_khoan.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$tk = tkLay($id);
if (!$tk) {
    datThongBao('loi', 'Không tìm thấy tài khoản.');
    chuyenTrang(url('/admin/tai_khoan/index.php'));
}
// Tài khoản của chính mình thì đổi ở trang Đổi mật khẩu (phải nhập mật khẩu cũ)
if ($id === (int) $_SESSION['ma_tai_khoan']) {
    chuyenTrang(url('/doi_mat_khau.php'));
}

$loi = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $mkMoi = (string) ($_POST['mat_khau'] ?? '');
    $loi = tkKiemTraMatKhau($mkMoi, (string) ($_POST['nhap_lai'] ?? ''));
    if ($loi === null) {
        tkDatMatKhau($id, $mkMoi);
        datThongBao('ok', 'Đã đặt lại mật khẩu cho tài khoản ' . $tk['ten_dang_nhap'] . '.');
        chuyenTrang(url('/admin/tai_khoan/index.php'));
    }
}

$tieuDe = 'Đặt lại mật khẩu';
$menu   = 'tai_khoan';
require __DIR__ . '/../../includes/admin_header.php';
?>

<h1 class="h3 mb-3">Đặt lại mật khẩu</h1>

<div class="card shadow-sm" style="max-width: 560px">
    <div class="card-body">
        <p class="mb-3">
            Tài khoản: <strong><?= e($tk['ten_dang_nhap']) ?></strong>
            <?php if ($tk['ten_cau_thu']): ?><span class="text-muted">(<?= e($tk['ten_cau_thu']) ?>)</span><?php endif; ?>
        </p>

        <form method="post" novalidate>
            <?= oCsrf() ?>
            <input type="hidden" name="id" value="<?= (int) $tk['ma_tai_khoan'] ?>">

            <div class="mb-3">
                <label class="form-label" for="mat_khau">Mật khẩu mới</label>
                <input class="form-control <?= $loi ? 'is-invalid' : '' ?>" type="password" id="mat_khau" name="mat_khau"
                       required autocomplete="new-password">
                <?php if ($loi): ?>
                    <div class="invalid-feedback"><?= e($loi) ?></div>
                <?php else: ?>
                    <div class="form-text">Ít nhất <?= TK_MAT_KHAU_TOI_THIEU ?> ký tự. Mật khẩu cũ không xem được vì đã được mã hóa.</div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="nhap_lai">Nhập lại mật khẩu mới</label>
                <input class="form-control" type="password" id="nhap_lai" name="nhap_lai" required autocomplete="new-password">
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Lưu mật khẩu mới</button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/tai_khoan/index.php') ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
