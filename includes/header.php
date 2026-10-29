<?php
/**
 * Đầu trang dùng chung cho trang Khách / Cầu thủ.
 * Trước khi include:  $tieuDe = 'Danh sách đội bóng';   $menu = 'doi_bong';  (để tô đậm mục menu)
 */
$tieuDe = $tieuDe ?? 'Trang chủ';
$menu   = $menu ?? '';

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
    <link href="<?= url('/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-nba">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('/index.php') ?>"><i class="bi bi-dribbble"></i> NBA Manager</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuChinh">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuChinh">
            <ul class="navbar-nav me-auto">
                <?php foreach ($dsMenu as [$ma, $ten, $link]): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $menu === $ma ? 'active fw-semibold' : '' ?>" href="<?= url($link) ?>"><?= e($ten) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (daDangNhap()): ?>
                    <?php if (laAdmin()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/admin/index.php') ?>"><i class="bi bi-speedometer2"></i> Quản trị</a></li>
                    <?php elseif (laCauThu()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/cau_thu/cua_toi.php') ?>"><i class="bi bi-person-badge"></i> Của tôi</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/doi_mat_khau.php') ?>" title="Đổi mật khẩu"><i class="bi bi-person-circle"></i> <?= e(tenDangNhap()) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/dang_xuat.php') ?>">Đăng xuất</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm mt-1" href="<?= url('/dang_nhap.php') ?>">Đăng nhập</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container my-4 flex-grow-1">
    <?php if ($thongBao): ?>
        <div class="alert alert-<?= $thongBao['loai'] === 'ok' ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= e($thongBao['noi_dung']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
