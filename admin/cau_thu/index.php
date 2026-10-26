<?php
/** Admin - Danh sách cầu thủ, lọc theo đội / vị trí / quốc tịch / trạng thái (TV3) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/giao_dien.php';

$loc = [
    'tu_khoa'    => trim((string) ($_GET['tu_khoa'] ?? '')),
    'ma_doi'     => (string) ($_GET['ma_doi'] ?? ''),
    'vi_tri'     => (string) ($_GET['vi_tri'] ?? ''),
    'quoc_tich'  => (string) ($_GET['quoc_tich'] ?? ''),
    'trang_thai' => (string) ($_GET['trang_thai'] ?? ''),
];
$kq    = layDanhSachCauThu($loc, (int) ($_GET['trang'] ?? 1));
$dsDoi = layDanhSachDoi();
$dsQT  = layDanhSachQuocTich();
$coLoc = implode('', $loc) !== '';

$tieuDe = 'Quản lý cầu thủ';
$menu   = 'cau_thu';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Cầu thủ</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= url('/admin/trao_giai/index.php') ?>"><i class="bi bi-trophy"></i> Trao giải</a>
        <a class="btn btn-primary" href="<?= url('/admin/cau_thu/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm cầu thủ</a>
    </div>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-md-6 col-xl-3">
        <input class="form-control" type="search" name="tu_khoa" placeholder="Tìm theo tên" value="<?= e($loc['tu_khoa']) ?>">
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <select class="form-select" name="ma_doi">
            <option value="">Tất cả đội</option>
            <option value="tu_do"<?= chon($loc['ma_doi'], 'tu_do') ?>>— Không có đội —</option>
            <?php foreach ($dsDoi as $d): ?>
                <option value="<?= (int) $d['ma_doi'] ?>"<?= chon($loc['ma_doi'], $d['ma_doi']) ?>><?= e($d['ten_doi']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <select class="form-select" name="vi_tri">
            <option value="">Tất cả vị trí</option>
            <?php foreach (VI_TRI as $ma => $ten): ?>
                <option value="<?= $ma ?>"<?= chon($loc['vi_tri'], $ma) ?>><?= e($ten) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <select class="form-select" name="quoc_tich">
            <option value="">Tất cả quốc tịch</option>
            <?php foreach ($dsQT as $qt): ?>
                <option value="<?= e($qt) ?>"<?= chon($loc['quoc_tich'], $qt) ?>><?= e($qt) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <select class="form-select" name="trang_thai">
            <option value="">Tất cả trạng thái</option>
            <?php foreach (TRANG_THAI_CT as $ma => $ten): ?>
                <option value="<?= $ma ?>"<?= chon($loc['trang_thai'], $ma) ?>><?= e($ten) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-6 col-xl-1 d-flex gap-2">
        <button class="btn btn-outline-secondary flex-grow-1" type="submit"><i class="bi bi-funnel"></i> Lọc</button>
        <?php if ($coLoc): ?>
            <a class="btn btn-link px-1" href="<?= url('/admin/cau_thu/index.php') ?>" title="Xóa lọc"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 56px"></th>
                <th class="text-end" style="width: 60px">Số áo</th>
                <th>Họ tên</th>
                <th>Vị trí</th>
                <th>Đội</th>
                <th>Quốc tịch</th>
                <th>Trạng thái</th>
                <th class="text-end" style="width: 300px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$kq['ds']): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">
                    <?= $coLoc ? 'Không có cầu thủ nào phù hợp.' : 'Chưa có cầu thủ nào.' ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($kq['ds'] as $ct): ?>
                <tr>
                    <td><?= anhCauThu($ct, 38) ?></td>
                    <td class="text-end fw-semibold"><?= $ct['so_ao'] !== null ? '#' . (int) $ct['so_ao'] : '' ?></td>
                    <td><a class="fw-semibold" href="<?= url('/cau_thu/ho_so.php?id=' . (int) $ct['ma_cau_thu']) ?>"><?= e($ct['ho_ten']) ?></a></td>
                    <td><?= e($ct['vi_tri']) ?></td>
                    <td><?= $ct['ten_doi'] ? e($ct['ten_doi']) : '<span class="text-muted">Không có đội</span>' ?></td>
                    <td><?= e($ct['quoc_tich'] ?? '') ?></td>
                    <td><?= nhanTrangThai($ct['trang_thai']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/cau_thu/form.php?id=' . (int) $ct['ma_cau_thu']) ?>">Sửa</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/admin/cau_thu/chuyen_nhuong.php?id=' . (int) $ct['ma_cau_thu']) ?>">Chuyển nhượng</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/admin/cau_thu/chan_thuong.php?id=' . (int) $ct['ma_cau_thu']) ?>">Chấn thương</a>
                        <form class="d-inline" method="post" action="<?= url('/admin/cau_thu/xoa.php') ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="id" value="<?= (int) $ct['ma_cau_thu'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <p class="text-muted small mt-3 mb-0">
        Tổng: <?= $kq['tong'] ?> cầu thủ. Cầu thủ đã có thống kê trận đấu hoặc giải thưởng sẽ không xóa được.
    </p>
    <?= phanTrang($kq['trang'], $kq['so_trang'], $loc) ?>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
