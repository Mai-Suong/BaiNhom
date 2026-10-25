<?php
require_once __DIR__ . '/../includes/init.php';
yeuCauAdmin(); // BẮT BUỘC ở đầu mọi trang admin

// Số liệu nhanh (TV4 Huy sẽ làm Dashboard đầy đủ hơn)
$soLieu = [
    ['Đội bóng',      'SELECT COUNT(*) FROM doi_bong',                               'shield',      'primary'],
    ['Cầu thủ',       'SELECT COUNT(*) FROM cau_thu',                                'people',      'success'],
    ['Trận chưa đấu', "SELECT COUNT(*) FROM tran_dau WHERE trang_thai = 'chua_dau'", 'calendar3',   'warning'],
    ['Tài khoản',     'SELECT COUNT(*) FROM tai_khoan',                              'person-lock', 'secondary'],
];

$tieuDe = 'Dashboard';
$menu   = 'dashboard';
require __DIR__ . '/../includes/admin_header.php';
?>

<h1 class="h3 mb-4">Dashboard</h1>

<div class="row g-3">
    <?php foreach ($soLieu as [$ten, $sql, $icon, $mau]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-start border-4 border-<?= $mau ?>">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small"><?= e($ten) ?></div>
                        <div class="fs-3 fw-bold"><?= (int) $pdo->query($sql)->fetchColumn() ?></div>
                    </div>
                    <i class="bi bi-<?= $icon ?> fs-1 text-<?= $mau ?>"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
