<?php
/** Admin - Tạo tài khoản cho cầu thủ (TV1). Chỉ chọn được cầu thủ chưa có tài khoản. */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_tai_khoan.php';

$dsCauThu = tkLayCauThuChuaCoTaiKhoan();
$d   = ['ten_dang_nhap' => '', 'mat_khau' => '', 'nhap_lai' => '', 'ma_cau_thu' => ''];
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $d = [
        'ten_dang_nhap' => strtolower(trim((string) ($_POST['ten_dang_nhap'] ?? ''))),
        'mat_khau'      => (string) ($_POST['mat_khau'] ?? ''),
        'nhap_lai'      => (string) ($_POST['nhap_lai'] ?? ''),
        'ma_cau_thu'    => (string) ($_POST['ma_cau_thu'] ?? ''),
    ];
    $loi = tkKiemTraTao($d);
    if (!$loi) {
        tkTao($d['ten_dang_nhap'], $d['mat_khau'], (int) $d['ma_cau_thu']);
        datThongBao('ok', 'Đã tạo tài khoản ' . $d['ten_dang_nhap'] . '.');
        chuyenTrang(url('/admin/tai_khoan/index.php'));
    }
}

$tieuDe = 'Tạo tài khoản cầu thủ';
$menu   = 'tai_khoan';
require __DIR__ . '/../../includes/admin_header.php';
?>

<h1 class="h3 mb-3">Tạo tài khoản cầu thủ</h1>

<?php if (!$dsCauThu): ?>
    <div class="alert alert-warning">Tất cả cầu thủ đều đã có tài khoản (hoặc chưa có cầu thủ nào).</div>
    <a class="btn btn-outline-secondary" href="<?= url('/admin/tai_khoan/index.php') ?>">Quay lại</a>
<?php else: ?>
<div class="card shadow-sm" style="max-width: 560px">
    <div class="card-body">
        <form method="post" novalidate>
            <?= oCsrf() ?>

            <div class="mb-3">
                <label class="form-label" for="ma_cau_thu">Cầu thủ</label>
                <select class="form-select <?= isset($loi['ma_cau_thu']) ? 'is-invalid' : '' ?>" id="ma_cau_thu" name="ma_cau_thu" required>
                    <option value="">— Chọn cầu thủ chưa có tài khoản —</option>
                    <?php foreach ($dsCauThu as $ct): ?>
                        <option value="<?= (int) $ct['ma_cau_thu'] ?>"<?= (string) $ct['ma_cau_thu'] === $d['ma_cau_thu'] ? ' selected' : '' ?>>
                            <?= e(($ct['doi'] ?? 'Tự do') . ' · ' . $ct['ho_ten'] . ($ct['so_ao'] !== null ? ' (#' . $ct['so_ao'] . ')' : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($loi['ma_cau_thu'])): ?><div class="invalid-feedback"><?= e($loi['ma_cau_thu']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="ten_dang_nhap">Tên đăng nhập</label>
                <input class="form-control <?= isset($loi['ten_dang_nhap']) ? 'is-invalid' : '' ?>" id="ten_dang_nhap" name="ten_dang_nhap"
                       value="<?= e($d['ten_dang_nhap']) ?>" maxlength="<?= TK_TEN_TOI_DA ?>" required autocomplete="off">
                <?php if (isset($loi['ten_dang_nhap'])): ?>
                    <div class="invalid-feedback"><?= e($loi['ten_dang_nhap']) ?></div>
                <?php else: ?>
                    <div class="form-text">Chữ thường không dấu, số, dấu chấm, gạch dưới. VD: jalen.brunson</div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="mat_khau">Mật khẩu ban đầu</label>
                <input class="form-control <?= isset($loi['mat_khau']) ? 'is-invalid' : '' ?>" type="password" id="mat_khau" name="mat_khau"
                       required autocomplete="new-password">
                <?php if (isset($loi['mat_khau'])): ?>
                    <div class="invalid-feedback"><?= e($loi['mat_khau']) ?></div>
                <?php else: ?>
                    <div class="form-text">Ít nhất <?= TK_MAT_KHAU_TOI_THIEU ?> ký tự. Báo cho cầu thủ để họ tự đổi sau khi đăng nhập.</div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="nhap_lai">Nhập lại mật khẩu</label>
                <input class="form-control" type="password" id="nhap_lai" name="nhap_lai" required autocomplete="new-password">
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Tạo tài khoản</button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/tai_khoan/index.php') ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
