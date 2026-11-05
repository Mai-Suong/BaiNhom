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
            $_SESSION['hoat_dong_cuoi'] = time();

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
                        <div class="position-relative">
                            <input class="form-control pe-5" type="password" id="mat_khau" name="mat_khau" required>
                            <button class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary border-0 shadow-none px-3"
                                    type="button" id="nut-xem-mat-khau" title="Hiện mật khẩu" aria-label="Hiện mật khẩu">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-nba w-100" type="submit">Đăng nhập</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Bấm con mắt: hiện / ẩn mật khẩu đang nhập
    (function () {
        const o = document.getElementById('mat_khau');
        const nut = document.getElementById('nut-xem-mat-khau');
        nut.addEventListener('click', function () {
            const dangAn = o.type === 'password';
            o.type = dangAn ? 'text' : 'password';
            nut.querySelector('i').className = dangAn ? 'bi bi-eye-slash' : 'bi bi-eye';
            nut.title = dangAn ? 'Ẩn mật khẩu' : 'Hiện mật khẩu';
            nut.setAttribute('aria-label', nut.title);
            o.focus();
        });
    })();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
