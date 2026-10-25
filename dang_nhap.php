<?php
/**
 * Đăng nhập (TV1). Sau khi đăng nhập lưu vào session đúng như TV3 đã thống nhất:
 *   ma_tai_khoan, ten_dang_nhap, vai_tro ('admin' | 'cau_thu'), ma_cau_thu
 */
require_once __DIR__ . '/includes/init.php';

if (daDangNhap()) {
    chuyenTrang(url(laAdmin() ? '/admin/index.php' : '/index.php'));
}

$loi = '';
$tenNhap = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $tenNhap = trim($_POST['ten_dang_nhap'] ?? '');
    $matKhau = $_POST['mat_khau'] ?? '';

    if ($tenNhap === '' || $matKhau === '') {
        $loi = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM tai_khoan WHERE ten_dang_nhap = ?');
        $stmt->execute([$tenNhap]);
        $tk = $stmt->fetch();

        if (!$tk || !password_verify($matKhau, $tk['mat_khau'])) {
            $loi = 'Sai tên đăng nhập hoặc mật khẩu.';
        } elseif ($tk['bi_khoa']) {
            $loi = 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';
        } else {
            session_regenerate_id(true); // chống chiếm phiên đăng nhập
            $_SESSION['ma_tai_khoan']  = (int) $tk['ma_tai_khoan'];
            $_SESSION['ten_dang_nhap'] = $tk['ten_dang_nhap'];
            $_SESSION['vai_tro']       = $tk['vai_tro'];
            $_SESSION['ma_cau_thu']    = $tk['ma_cau_thu'] !== null ? (int) $tk['ma_cau_thu'] : null;

            datThongBao('ok', 'Đăng nhập thành công. Xin chào ' . $tk['ten_dang_nhap'] . '!');
            chuyenTrang(url($tk['vai_tro'] === 'admin' ? '/admin/index.php' : '/index.php'));
        }
    }
}

$tieuDe = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-sm-9 col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-4"><i class="bi bi-person-circle"></i> Đăng nhập</h1>

                <?php if ($loi): ?>
                    <div class="alert alert-danger py-2"><?= e($loi) ?></div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <?= oCsrf() ?>
                    <div class="mb-3">
                        <label class="form-label" for="ten_dang_nhap">Tên đăng nhập</label>
                        <input class="form-control" id="ten_dang_nhap" name="ten_dang_nhap"
                               value="<?= e($tenNhap) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mat_khau">Mật khẩu</label>
                        <input class="form-control" type="password" id="mat_khau" name="mat_khau" required>
                    </div>
                    <button class="btn btn-nba w-100" type="submit">Đăng nhập</button>
                </form>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">Khách không cần đăng nhập vẫn xem được thông tin giải đấu.</p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
