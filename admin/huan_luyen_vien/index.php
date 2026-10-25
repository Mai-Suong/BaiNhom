<?php
/** Admin - Danh sách huấn luyện viên (TV2) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_huan_luyen_vien.php';

$tuKhoa = trim((string) ($_GET['q'] ?? ''));
$locDoi = (string) ($_GET['doi'] ?? '');
$dsHlv  = hlvLayDanhSach($tuKhoa, $locDoi);
$dsDoi  = hlvLayDoiLuaChon();

$tieuDe = 'Quản lý huấn luyện viên';
$menu   = 'hlv';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Huấn luyện viên</h1>
    <a class="btn btn-primary" href="<?= url('/admin/huan_luyen_vien/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm HLV</a>
</div>

<form class="d-flex flex-wrap gap-2 mb-3" method="get">
    <input class="form-control" style="max-width: 260px" type="search" name="q"
           value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên HLV">
    <select class="form-select" style="max-width: 240px" name="doi">
        <option value="">Tất cả đội</option>
        <option value="tu_do"<?= $locDoi === 'tu_do' ? ' selected' : '' ?>>Đang tự do</option>
        <?php foreach ($dsDoi as $doi): ?>
            <option value="<?= (int) $doi['ma_doi'] ?>"<?= $locDoi === (string) $doi['ma_doi'] ? ' selected' : '' ?>>
                <?= e($doi['ten_doi']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel"></i> Lọc</button>
    <?php if ($tuKhoa !== '' || $locDoi !== ''): ?>
        <a class="btn btn-link" href="<?= url('/admin/huan_luyen_vien/index.php') ?>">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 56px">Ảnh</th>
                <th>Họ tên</th>
                <th>Đội</th>
                <th>Vai trò</th>
                <th>Quốc tịch</th>
                <th>Ngày sinh</th>
                <th>Dẫn dắt từ</th>
                <th class="text-end" style="width: 150px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsHlv): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Không có HLV phù hợp.</td></tr>
            <?php endif; ?>
            <?php foreach ($dsHlv as $hlv): ?>
                <tr>
                    <td>
                        <?php if ($hlv['anh']): ?>
                            <img src="<?= e(url('/' . $hlv['anh'])) ?>" alt="" width="40" height="40" class="rounded-circle object-fit-cover">
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-3 text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= e($hlv['ho_ten']) ?></td>
                    <td><?= $hlv['ten_doi'] !== null ? e($hlv['ten_doi']) : '<span class="badge text-bg-light border">Tự do</span>' ?></td>
                    <td>
                        <span class="badge <?= $hlv['vai_tro'] === 'truong' ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                            <?= e(VAI_TRO_HLV[$hlv['vai_tro']] ?? $hlv['vai_tro']) ?>
                        </span>
                    </td>
                    <td><?= e($hlv['quoc_tich']) ?></td>
                    <td><?= $hlv['ngay_sinh'] ? e(date('d/m/Y', strtotime($hlv['ngay_sinh']))) : '' ?></td>
                    <td><?= $hlv['ngay_bat_dau'] ? e(date('d/m/Y', strtotime($hlv['ngay_bat_dau']))) : '' ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= url('/admin/huan_luyen_vien/form.php?id=' . (int) $hlv['ma_hlv']) ?>">Sửa</a>
                        <form class="d-inline" method="post" action="<?= url('/admin/huan_luyen_vien/xoa.php') ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="id" value="<?= (int) $hlv['ma_hlv'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Tổng: <?= count($dsHlv) ?> HLV. Mỗi đội chỉ có một HLV trưởng; HLV đã nhận giải thưởng không xóa được.</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
