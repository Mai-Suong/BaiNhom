<?php
/**
 * Đầu trang dùng chung cho TRANG QUẢN TRỊ.
 * Trang admin gọi yeuCauAdmin() TRƯỚC khi include file này.
 * Trước khi include:  $tieuDe = 'Quản lý đội bóng';   $menu = 'doi_bong';
 */
$tieuDe = $tieuDe ?? 'Quản trị';
$menu   = $menu ?? '';

// Menu trái: nhóm => [[mã, tên, đường dẫn, icon]]. Mỗi module là 1 thư mục trong admin/.
$dsMenuAdmin = [
    'Tổng quan' => [
        ['dashboard', 'Dashboard', '/admin/index.php', 'speedometer2'],
    ],
    'Đội bóng (TV2 Duy)' => [
        ['doi_bong',  'Đội bóng',        '/admin/doi_bong/index.php',        'shield'],
        ['san',       'Sân thi đấu',     '/admin/san_thi_dau/index.php',     'building'],
        ['hlv',       'Huấn luyện viên', '/admin/huan_luyen_vien/index.php', 'person-workspace'],
        ['trong_tai', 'Trọng tài',       '/admin/trong_tai/index.php',       'flag'],
    ],
    'Cầu thủ (TV3 Nguyên)' => [
        ['cau_thu',   'Cầu thủ',   '/admin/cau_thu/index.php',   'people'],
        ['trao_giai', 'Trao giải', '/admin/trao_giai/index.php', 'trophy'],
    ],
    'Thi đấu (TV4 Huy)' => [
        ['mua_giai',  'Mùa giải',     '/admin/mua_giai/index.php',     'calendar3'],
        ['phan_bang', 'Phân bảng',    '/admin/phan_bang/index.php',    'diagram-3'],
        ['lich',      'Lịch thi đấu', '/admin/lich_thi_dau/index.php', 'calendar-week'],
        ['ket_qua',   'Nhập kết quả', '/admin/ket_qua/index.php',      'clipboard-check'],
    ],
    'Thống kê (TV5 Đạt)' => [
        ['box_score', 'Box score', '/admin/box_score/index.php', 'table'],
        ['playoff',   'Playoff',   '/admin/playoff/index.php',   'diagram-2'],
    ],
    'Hệ thống (TV1 Sương)' => [
        ['tai_khoan', 'Tài khoản', '/admin/tai_khoan/index.php', 'person-lock'],
    ],
];
$thongBao = layThongBao();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tieuDe) ?> | Quản trị NBA Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-nba sticky-top">
    <div class="container-fluid">
        <button class="btn btn-outline-light btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarAdmin">
            <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand fw-bold" href="<?= url('/admin/index.php') ?>"><i class="bi bi-dribbble"></i> Quản trị</a>
        <div class="d-flex align-items-center gap-3">
            <a class="text-white-50 small text-decoration-none d-none d-sm-inline" href="<?= url('/index.php') ?>"><i class="bi bi-box-arrow-up-right"></i> Xem trang web</a>
            <span class="text-white small"><i class="bi bi-person-circle"></i> <?= e(tenDangNhap()) ?></span>
            <a class="btn btn-outline-light btn-sm" href="<?= url('/dang_xuat.php') ?>">Đăng xuất</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside class="col-lg-2 p-0">
            <div class="offcanvas-lg offcanvas-start sidebar-admin" tabindex="-1" id="sidebarAdmin">
                <div class="offcanvas-header d-lg-none">
                    <h5 class="offcanvas-title">Menu quản trị</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarAdmin"></button>
                </div>
                <div class="offcanvas-body d-block p-0">
                    <?php foreach ($dsMenuAdmin as $nhom => $dsMuc): ?>
                        <div class="sidebar-nhom"><?= e($nhom) ?></div>
                        <nav class="nav flex-column">
                            <?php foreach ($dsMuc as [$ma, $ten, $link, $icon]): ?>
                                <a class="nav-link <?= $menu === $ma ? 'active' : '' ?>" href="<?= url($link) ?>">
                                    <i class="bi bi-<?= e($icon) ?>"></i> <?= e($ten) ?>
                                </a>
                            <?php endforeach; ?>
                        </nav>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <main class="col-lg-10 py-4 px-4">
            <?php if ($thongBao): ?>
                <div class="alert alert-<?= $thongBao['loai'] === 'ok' ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?= e($thongBao['noi_dung']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
