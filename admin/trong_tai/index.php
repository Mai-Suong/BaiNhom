<?php
/** Admin - Danh sách trọng tài (TV2). ?mua= : xem số trận phân công và số năm ở NBA tính đến mùa đó */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_trong_tai.php';
require_once __DIR__ . '/../../includes/ham_trang_khach.php'; // chọn mùa (tkh...)

$tuKhoa = trim((string) ($_GET['q'] ?? ''));
$dsMua  = tkhLayDsMua();
$mua    = tkhChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$maMua  = $mua ? (int) $mua['ma_mua_giai'] : null;
$laHienTai = $mua === null || tkhLaMuaHienTai($mua);
$dsTt   = ttLayDanhSach($tuKhoa, $maMua);
$namMua = $mua ? (int) substr($mua['ngay_bat_dau'], 0, 4) : null; // mùa 2023-24 -> 2023

/** Mùa thứ mấy của trọng tài ở NBA tính đến mùa đang xem */
$kinhNghiem = function (?string $namBatDau) use ($namMua): string {
    if ($namBatDau === null || $namBatDau === '' || $namMua === null) {
        return '';
    }
    return (int) $namBatDau > $namMua ? 'Chưa vào NBA' : 'Mùa thứ ' . ($namMua - (int) $namBatDau + 1);
};

$tieuDe = 'Quản lý trọng tài';
$menu   = 'trong_tai';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Trọng tài<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?>
        <?php if ($mua && $laHienTai): ?><span class="badge text-bg-success fs-6 align-middle">Mùa hiện tại</span><?php endif; ?>
    </h1>
    <a class="btn btn-primary" href="<?= url('/admin/trong_tai/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm trọng tài</a>
</div>

<?php if (!$laHienTai): ?>
    <div class="alert alert-info py-2">
        <i class="bi bi-clock-history"></i> Đang xem mùa <?= e($mua['ten_mua_giai']) ?>: số trận và số năm ở NBA tính theo mùa này.
        Nút Sửa / Xóa vẫn sửa thông tin hiện tại của trọng tài.
    </div>
<?php endif; ?>

<form class="d-flex flex-wrap gap-2 mb-3" method="get">
    <?php if ($dsMua): ?>
        <select class="form-select w-auto" name="mua" aria-label="Mùa giải" onchange="this.form.submit()">
            <?php foreach ($dsMua as $m): ?>
                <option value="<?= (int) $m['ma_mua_giai'] ?>"<?= (int) $m['ma_mua_giai'] === $maMua ? ' selected' : '' ?>>
                    Mùa <?= e($m['ten_mua_giai']) ?><?= (int) $m['la_mua_hien_tai'] === 1 ? ' (hiện tại)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <input class="form-control" style="max-width: 320px" type="search" name="q"
           value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên hoặc số áo">
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Tìm</button>
    <?php if ($tuKhoa !== ''): ?>
        <a class="btn btn-link" href="<?= url('/admin/trong_tai/index.php') . ($maMua !== null ? '?mua=' . $maMua : '') ?>">Xóa lọc</a>
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
                <?php if ($mua): ?>
                    <th class="text-end">Kinh nghiệm mùa <?= e($mua['ten_mua_giai']) ?></th>
                    <th class="text-end">Trận mùa <?= e($mua['ten_mua_giai']) ?></th>
                <?php endif; ?>
                <th class="text-end">Tổng trận phân công</th>
                <th class="text-end" style="width: 150px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsTt): ?>
                <tr><td colspan="<?= $mua ? 7 : 5 ?>" class="text-center text-muted py-4">
                    <?= $tuKhoa !== '' ? 'Không tìm thấy trọng tài phù hợp.' : 'Chưa có trọng tài nào.' ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($dsTt as $tt): ?>
                <tr>
                    <td><?= $tt['so_ao'] !== null ? '<span class="badge text-bg-dark fs-6">#' . (int) $tt['so_ao'] . '</span>' : '' ?></td>
                    <td class="fw-semibold"><?= e($tt['ho_ten']) ?></td>
                    <td class="text-end"><?= e($tt['nam_bat_dau']) ?></td>
                    <?php if ($mua): ?>
                        <td class="text-end"><?= e($kinhNghiem($tt['nam_bat_dau'] !== null ? (string) $tt['nam_bat_dau'] : null)) ?></td>
                        <td class="text-end"><?= (int) $tt['so_tran_mua'] ?></td>
                    <?php endif; ?>
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
