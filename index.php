<?php
require_once __DIR__ . '/includes/init.php';

$tieuDe = 'Trang chủ';
$menu   = 'trang_chu';
require __DIR__ . '/includes/header.php';
?>

<div class="p-5 mb-4 rounded-3 hero">
    <h1 class="display-5 fw-bold">Giải Bóng rổ Nhà nghề Mỹ</h1>
    <p class="fs-5 mb-0">Thông tin đội bóng, cầu thủ, lịch thi đấu, kết quả và bảng xếp hạng NBA.</p>
</div>

<!-- TV2 (Duy) sẽ thay các ô dưới đây bằng dữ liệu thật -->
<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-calendar-event"></i> Trận sắp diễn ra</div>
            <div class="card-body text-muted">Đang cập nhật…</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-check2-square"></i> Kết quả mới nhất</div>
            <div class="card-body text-muted">Đang cập nhật…</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-star"></i> Top cầu thủ</div>
            <div class="card-body text-muted">Đang cập nhật…</div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
