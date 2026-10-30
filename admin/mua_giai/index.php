<?php
/** Admin - Danh sách mùa giải (TV4) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_mua_giai.php';

$dsMua = mgLayDanhSach();

$tieuDe = 'Quản lý mùa giải';
$menu   = 'mua_giai';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Mùa giải</h1>
    <a class="btn btn-primary" href="<?= url('/admin/mua_giai/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm mùa giải</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Mùa giải</th>
                <th>Bắt đầu</th>
                <th>Kết thúc</th>
                <th class="text-end">Đội đã phân bảng</th>
                <th class="text-end">Trận (đã đấu / tổng)</th>
                <th class="text-end" style="width: 300px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsMua): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có mùa giải nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($dsMua as $mua): ?>
                <?php $laHienTai = (int) $mua['la_mua_hien_tai'] === 1; ?>
                <tr<?= $laHienTai ? ' class="table-primary"' : '' ?>>
                    <td class="fw-semibold">
                        <?= e($mua['ten_mua_giai']) ?>
                        <?php if ($laHienTai): ?><span class="badge text-bg-success ms-1">Mùa hiện tại</span><?php endif; ?>
                    </td>
                    <td><?= e(mgHienNgay($mua['ngay_bat_dau'])) ?></td>
                    <td><?= e(mgHienNgay($mua['ngay_ket_thuc'])) ?></td>
                    <td class="text-end"><?= (int) $mua['so_doi'] ?></td>
                    <td class="text-end"><?= (int) $mua['so_tran_da_dau'] ?> / <?= (int) $mua['so_tran'] ?></td>
                    <td class="text-end text-nowrap">
                        <?php if (!$laHienTai): ?>
                            <form class="d-inline" method="post" action="<?= url('/admin/mua_giai/hien_tai.php') ?>">
                                <?= oCsrf() ?>
                                <input type="hidden" name="id" value="<?= (int) $mua['ma_mua_giai'] ?>">
                                <button class="btn btn-sm btn-outline-success" type="submit">Đặt làm mùa hiện tại</button>
                            </form>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= url('/admin/mua_giai/form.php?id=' . (int) $mua['ma_mua_giai']) ?>">Sửa</a>
                        <form class="d-inline" method="post" action="<?= url('/admin/mua_giai/xoa.php') ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="id" value="<?= (int) $mua['ma_mua_giai'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">
    Tổng: <?= count($dsMua) ?> mùa. Chỉ có 1 mùa hiện tại; trang chủ, đội bóng, trao giải dùng mùa này.
    Mùa hiện tại hoặc mùa đã có trận, phân bảng, playoff, giải thưởng, nhiệm kỳ HLV sẽ không xóa được.
</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
