<?php
/** Admin - Danh sách sân thi đấu (TV2) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_san_thi_dau.php';

$tuKhoa = trim((string) ($_GET['q'] ?? ''));
$dsSan  = sanLayDanhSach($tuKhoa);

$tieuDe = 'Quản lý sân thi đấu';
$menu   = 'san';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Sân thi đấu</h1>
    <a class="btn btn-primary" href="<?= url('/admin/san_thi_dau/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm sân</a>
</div>

<form class="d-flex gap-2 mb-3" method="get">
    <input class="form-control" style="max-width: 320px" type="search" name="q"
           value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên sân hoặc thành phố">
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Tìm</button>
    <?php if ($tuKhoa !== ''): ?>
        <a class="btn btn-link" href="<?= url('/admin/san_thi_dau/index.php') ?>">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 72px">Ảnh</th>
                <th>Tên sân</th>
                <th>Thành phố</th>
                <th>Bang / tỉnh</th>
                <th class="text-end">Sức chứa</th>
                <th class="text-end">Khánh thành</th>
                <th class="text-end">Số đội dùng</th>
                <th class="text-end" style="width: 150px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsSan): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">
                    <?= $tuKhoa !== '' ? 'Không tìm thấy sân phù hợp.' : 'Chưa có sân nào.' ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($dsSan as $san): ?>
                <tr>
                    <td>
                        <?php if ($san['hinh_anh']): ?>
                            <img src="<?= e(url('/' . $san['hinh_anh'])) ?>" alt="" width="56" height="40"
                                 class="rounded object-fit-cover">
                        <?php else: ?>
                            <span class="text-muted"><i class="bi bi-building fs-4"></i></span>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= e($san['ten_san']) ?></td>
                    <td><?= e($san['thanh_pho']) ?></td>
                    <td><?= e($san['tieu_bang']) ?></td>
                    <td class="text-end"><?= $san['suc_chua'] !== null ? number_format((int) $san['suc_chua'], 0, ',', '.') : '' ?></td>
                    <td class="text-end"><?= e($san['nam_khanh_thanh']) ?></td>
                    <td class="text-end"><?= (int) $san['so_doi'] ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= url('/admin/san_thi_dau/form.php?id=' . (int) $san['ma_san']) ?>">Sửa</a>
                        <form class="d-inline" method="post" action="<?= url('/admin/san_thi_dau/xoa.php') ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="id" value="<?= (int) $san['ma_san'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Tổng: <?= count($dsSan) ?> sân. Sân đang là sân nhà của đội hoặc đã có trận đấu sẽ không xóa được.</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
