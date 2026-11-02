<?php
/**
 * Đầu trang dùng chung cho trang Khách / Cầu thủ.
 * Trước khi include:  $tieuDe = 'Danh sách đội bóng';   $menu = 'doi_bong';  (để tô đậm mục menu)
 * Tùy chọn: $daiTiSo = '<html>' (dải tỉ số hiện ngay dưới menu, trang chủ dùng).
 */
$tieuDe  = $tieuDe ?? 'Trang chủ';
$menu    = $menu ?? '';
$daiTiSo = $daiTiSo ?? '';

// Menu chính: [mã, tên, đường dẫn]. Mỗi người tạo đúng file này là link chạy.
$dsMenu = [
    ['trang_chu', 'Trang chủ',     '/index.php'],
    ['doi_bong',  'Đội bóng',      '/doi_bong/index.php'],       // TV2 Duy
    ['cau_thu',   'Cầu thủ',       '/cau_thu/index.php'],        // TV3 Nguyên
    ['lich',      'Lịch thi đấu',  '/lich_thi_dau/index.php'],   // TV4 Huy
    ['ket_qua',   'Kết quả',       '/ket_qua/index.php'],        // TV4 Huy
    ['bxh',       'Bảng xếp hạng', '/bang_xep_hang/index.php'],  // TV5 Đạt
    ['thong_ke',  'Thống kê',      '/thong_ke/index.php'],       // TV5 Đạt
];
$thongBao = layThongBao();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tieuDe) ?> | NBA Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php // ?v= đổi mỗi khi sửa style.css -> trình duyệt luôn tải bản CSS mới, không dùng bản cũ đã lưu ?>
    <link href="<?= url('/assets/css/style.css') ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 trang-khach">

<!-- Thanh trên cùng: logo + tài khoản -->
<header class="thanh-tren">
    <div class="container d-flex align-items-center justify-content-between gap-3">
        <a class="thuong-hieu" href="<?= url('/index.php') ?>">
            <span class="thuong-hieu-bong"><i class="bi bi-dribbble"></i></span>
            <span>NBA MANAGER</span>
        </a>
        <nav class="tai-khoan" aria-label="Tài khoản">
            <?php if (daDangNhap()): ?>
                <?php if (laAdmin()): ?>
                    <a href="<?= url('/admin/index.php') ?>"><i class="bi bi-speedometer2"></i> <span class="d-none d-sm-inline">Quản trị</span></a>
                <?php elseif (laCauThu()): ?>
                    <a href="<?= url('/cau_thu/cua_toi.php') ?>"><i class="bi bi-person-badge"></i> <span class="d-none d-sm-inline">Của tôi</span></a>
                <?php endif; ?>
                <a href="<?= url('/doi_mat_khau.php') ?>" title="Đổi mật khẩu"><i class="bi bi-person-circle"></i> <span class="d-none d-sm-inline"><?= e(tenDangNhap()) ?></span></a>
                <a class="nut-vien" href="<?= url('/dang_xuat.php') ?>">Đăng xuất</a>
            <?php else: ?>
                <a class="nut-vien" href="<?= url('/dang_nhap.php') ?>">Đăng nhập</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?= $daiTiSo ?>

<!-- Menu chính: cuộn ngang trên điện thoại -->
<nav class="menu-chinh" aria-label="Menu chính">
    <div class="container">
        <?php foreach ($dsMenu as [$ma, $ten, $link]): ?>
            <a class="<?= $menu === $ma ? 'dang-chon' : '' ?>" href="<?= url($link) ?>"<?= $menu === $ma ? ' aria-current="page"' : '' ?>><?= e($ten) ?></a>
        <?php endforeach; ?>
    </div>
</nav>

<main class="container my-4 flex-grow-1">
    <?php if ($thongBao): ?>
        <div class="alert alert-<?= $thongBao['loai'] === 'ok' ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= e($thongBao['noi_dung']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
