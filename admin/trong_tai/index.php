<?php
/** Admin - Danh sách trọng tài (TV2) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_trong_tai.php';

$tuKhoa = trim((string) ($_GET['q'] ?? ''));
$dsTt   = ttLayDanhSach($tuKhoa);

$tieuDe = 'Quản lý trọng tài';
$menu   = 'trong_tai';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Trọng tài</h1>
    <a class="btn btn-primary" href="<?= url('/admin/trong_tai/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm trọng tài</a>
</div>

<form class="d-flex gap-2 mb-3" method="get">
    <input class="form-control" style="max-width: 320px" type="search" name="q"
           value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên hoặc số áo">
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Tìm</button>
    <?php if ($tuKhoa !== ''): ?>
        <a class="btn btn-link" href="<?= url('/admin/trong_tai/index.php') ?>">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 90px">Số áo</th>
                <th>Họ tên</th>
                <th class="text-end">Bắt đầu ở NBA</th>
                <th class="text-end">Số trận phân công</th>
                <th class="text-end" style="width: 150px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsTt): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">
                    <?= $tuKhoa !== '' ? 'Không tìm thấy trọng tài phù hợp.' : 'Chưa có trọng tài nào.' ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($dsTt as $tt): ?>
                <tr>
                    <td><?= $tt['so_ao'] !== null ? '<span class="badge text-bg-dark fs-6">#' . (int) $tt['so_ao'] . '</span>' : '' ?></td>
                    <td class="fw-semibold"><?= e($tt['ho_ten']) ?></td>
                    <td class="text-end"><?= e($tt['nam_bat_dau']) ?></td>
                    <td class="text-end"><?= (int) $tt['so_tran'] ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= url('/admin/trong_tai/form.php?id=' . (int) $tt['ma_trong_tai']) ?>">Sửa</a>
                        <form class="d-inline" method="post" action="<?= url('/admin/trong_tai/xoa.php') ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="id" value="<?= (int) $tt['ma_trong_tai'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Tổng: <?= count($dsTt) ?> trọng tài. Trọng tài đã được phân công trận không xóa được.</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
