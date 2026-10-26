<?php
/** Trang Khách - Danh sách cầu thủ, lọc theo đội / vị trí / quốc tịch (TV3) */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/giao_dien.php';

$loc = [
    'tu_khoa'   => trim((string) ($_GET['tu_khoa'] ?? '')),
    'ma_doi'    => (string) ($_GET['ma_doi'] ?? ''),
    'vi_tri'    => (string) ($_GET['vi_tri'] ?? ''),
    'quoc_tich' => (string) ($_GET['quoc_tich'] ?? ''),
];
$kq    = layDanhSachCauThu($loc, (int) ($_GET['trang'] ?? 1), 24);
$dsDoi = layDanhSachDoi();
$dsQT  = layDanhSachQuocTich();
$coLoc = implode('', $loc) !== '';

$tieuDe = 'Cầu thủ';
$menu   = 'cau_thu';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Cầu thủ</h1>

<form class="row g-2 mb-4" method="get">
    <div class="col-12 col-md-4">
        <input class="form-control" type="search" name="tu_khoa" placeholder="Tìm theo tên cầu thủ" value="<?= e($loc['tu_khoa']) ?>">
    </div>
    <div class="col-6 col-md-3">
        <select class="form-select" name="ma_doi">
            <option value="">Tất cả đội</option>
            <?php foreach ($dsDoi as $d): ?>
                <option value="<?= (int) $d['ma_doi'] ?>"<?= chon($loc['ma_doi'], $d['ma_doi']) ?>><?= e($d['ten_doi']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select class="form-select" name="vi_tri">
            <option value="">Mọi vị trí</option>
            <?php foreach (VI_TRI as $ma => $ten): ?>
                <option value="<?= $ma ?>"<?= chon($loc['vi_tri'], $ma) ?>><?= e($ten) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select class="form-select" name="quoc_tich">
            <option value="">Mọi quốc tịch</option>
            <?php foreach ($dsQT as $qt): ?>
                <option value="<?= e($qt) ?>"<?= chon($loc['quoc_tich'], $qt) ?>><?= e($qt) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-1 d-flex gap-1">
        <button class="btn btn-nba flex-grow-1" type="submit" title="Lọc"><i class="bi bi-search"></i></button>
        <?php if ($coLoc): ?>
            <a class="btn btn-outline-secondary" href="<?= url('/cau_thu/index.php') ?>" title="Xóa lọc"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<?php if (!$kq['ds']): ?>
    <div class="card card-body text-muted"><?= $coLoc ? 'Không có cầu thủ nào phù hợp.' : 'Chưa có cầu thủ nào.' ?></div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($kq['ds'] as $ct): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="card h-100 shadow-sm text-decoration-none text-reset"
               href="<?= url('/cau_thu/ho_so.php?id=' . (int) $ct['ma_cau_thu']) ?>">
                <div class="card-body text-center">
                    <div class="mb-2"><?= anhCauThu($ct, 72) ?></div>
                    <div class="fw-semibold"><?= e($ct['ho_ten']) ?></div>
                    <div class="small text-muted">
                        <?= $ct['so_ao'] !== null ? '#' . (int) $ct['so_ao'] . ' · ' : '' ?><?= e($ct['vi_tri']) ?>
                        <?= $ct['quoc_tich'] ? ' · ' . e($ct['quoc_tich']) : '' ?>
                    </div>
                    <div class="small text-muted"><?= e($ct['ten_doi'] ?? 'Không có đội') ?></div>
                    <?php if ($ct['trang_thai'] !== 'dang_thi_dau'): ?>
                        <div class="mt-1"><?= nhanTrangThai($ct['trang_thai']) ?></div>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
    <p class="text-muted small mt-3 mb-0">Tìm thấy <?= $kq['tong'] ?> cầu thủ.</p>
    <?= phanTrang($kq['trang'], $kq['so_trang'], $loc) ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
