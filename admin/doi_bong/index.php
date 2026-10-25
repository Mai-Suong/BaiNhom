<?php
/** Admin - Danh sách đội bóng (TV2) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_doi_bong.php';

$tuKhoa = trim((string) ($_GET['q'] ?? ''));
$dsDoi  = dbLayDanhSach($tuKhoa);

$tieuDe = 'Quản lý đội bóng';
$menu   = 'doi_bong';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Đội bóng</h1>
    <a class="btn btn-primary" href="<?= url('/admin/doi_bong/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm đội</a>
</div>

<form class="d-flex gap-2 mb-3" method="get">
    <input class="form-control" style="max-width: 320px" type="search" name="q"
           value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên, tên viết tắt, thành phố">
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Tìm</button>
    <?php if ($tuKhoa !== ''): ?>
        <a class="btn btn-link" href="<?= url('/admin/doi_bong/index.php') ?>">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 64px">Logo</th>
                <th>Tên đội</th>
                <th>Viết tắt</th>
                <th>Thành phố</th>
                <th>Sân nhà</th>
                <th>HLV trưởng</th>
                <th class="text-end">Cầu thủ</th>
                <th class="text-end">Thành lập</th>
                <th class="text-end" style="width: 150px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsDoi): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">
                    <?= $tuKhoa !== '' ? 'Không tìm thấy đội phù hợp.' : 'Chưa có đội nào.' ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($dsDoi as $doi): ?>
                <tr>
                    <td>
                        <?php if ($doi['logo']): ?>
                            <img src="<?= e(url('/' . $doi['logo'])) ?>" alt="" width="44" height="44" class="object-fit-contain">
                        <?php else: ?>
                            <span class="badge text-bg-secondary"><?= e($doi['ten_viet_tat']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= e($doi['ten_doi']) ?></td>
                    <td><?= e($doi['ten_viet_tat']) ?></td>
                    <td><?= e($doi['thanh_pho']) ?></td>
                    <td><?= $doi['ten_san'] !== null ? e($doi['ten_san']) : '<span class="text-muted">Chưa có</span>' ?></td>
                    <td><?= $doi['hlv_truong'] !== null ? e($doi['hlv_truong']) : '<span class="text-muted">Chưa có</span>' ?></td>
                    <td class="text-end"><?= (int) $doi['so_cau_thu'] ?></td>
                    <td class="text-end"><?= e($doi['nam_thanh_lap']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= url('/admin/doi_bong/form.php?id=' . (int) $doi['ma_doi']) ?>">Sửa</a>
                        <form class="d-inline" method="post" action="<?= url('/admin/doi_bong/xoa.php') ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="id" value="<?= (int) $doi['ma_doi'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Tổng: <?= count($dsDoi) ?> đội. Đội còn cầu thủ hoặc đã có trận đấu sẽ không xóa được.</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
