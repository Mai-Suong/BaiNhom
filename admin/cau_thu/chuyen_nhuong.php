<?php
/** Admin - Chuyển nhượng cầu thủ + xem lịch sử chuyển nhượng (TV3) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/giao_dien.php';

$id = (int) ($_GET['id'] ?? 0);
$ct = layCauThu($id);
if (!$ct) {
    datThongBao('loi', 'Không tìm thấy cầu thủ.');
    chuyenTrang(url('/admin/cau_thu/index.php'));
}

$f = [
    'loai'        => $ct['ma_doi'] === null ? 'ky_hop_dong' : 'trao_doi',
    'ma_doi_moi'  => '',
    'ngay_chuyen' => date('Y-m-d'),
    'so_ao_moi'   => '',
    'ghi_chu'     => '',
];
$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    foreach ($f as $k => $_) {
        $f[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    $soAoMoi = null;
    if ($f['so_ao_moi'] !== '') {
        $soAoMoi = filter_var($f['so_ao_moi'], FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 99]]);
        if ($soAoMoi === false) {
            $loi = 'Số áo mới phải là số nguyên từ 0 đến 99.';
        }
    }

    if ($loi === '') {
        try {
            chuyenNhuong(
                $id,
                $f['loai'],
                ctype_digit($f['ma_doi_moi']) ? (int) $f['ma_doi_moi'] : null,
                $f['ngay_chuyen'],
                $soAoMoi,
                $f['ghi_chu']
            );
            datThongBao('ok', "Đã chuyển nhượng {$ct['ho_ten']} thành công.");
            chuyenTrang(url('/admin/cau_thu/chuyen_nhuong.php?id=' . $id));
        } catch (RuntimeException $ex) {
            $loi = $ex->getMessage();
        } catch (PDOException $ex) {
            $loi = $ex->getCode() === '23000'
                ? 'Số áo bị trùng ở đội mới, vui lòng nhập số áo khác.'
                : 'Lỗi CSDL: ' . $ex->getMessage();
        }
    }
}

$dsDoi  = layDanhSachDoi();
$lichSu = layLichSuChuyenNhuong($id);

$tieuDe = 'Chuyển nhượng - ' . $ct['ho_ten'];
$menu   = 'cau_thu';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/cau_thu/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách cầu thủ</a></nav>
<h1 class="h3 mb-3">Chuyển nhượng</h1>

<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <?= anhCauThu($ct, 64) ?>
        <div><div class="small text-muted">Cầu thủ</div><div class="fw-semibold"><?= e($ct['ho_ten']) ?></div></div>
        <div><div class="small text-muted">Đội hiện tại</div><div><?= e($ct['ten_doi'] ?? 'Không có đội') ?></div></div>
        <div><div class="small text-muted">Số áo</div><div><?= $ct['so_ao'] !== null ? '#' . (int) $ct['so_ao'] : '—' ?></div></div>
        <div><div class="small text-muted">Trạng thái</div><div><?= nhanTrangThai($ct['trang_thai']) ?></div></div>
    </div>
</div>

<?php if ($ct['trang_thai'] === 'giai_nghe'): ?>
    <div class="alert alert-warning">Cầu thủ đã giải nghệ, không thể chuyển nhượng.</div>
<?php else: ?>
    <?php if ($loi !== ''): ?><div class="alert alert-danger"><?= e($loi) ?></div><?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header fw-semibold"><i class="bi bi-arrow-left-right"></i> Giao dịch mới</div>
        <div class="card-body">
            <form method="post" onsubmit="return confirm('Xác nhận thực hiện chuyển nhượng?')">
                <?= oCsrf() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="loai">Loại giao dịch</label>
                        <select class="form-select" id="loai" name="loai">
                            <?php foreach (LOAI_CHUYEN_NHUONG as $ma => $ten): ?>
                                <option value="<?= $ma ?>"<?= chon($f['loai'], $ma) ?>><?= e($ten) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6" id="o-doi-moi">
                        <label class="form-label fw-semibold" for="ma_doi_moi">Đội mới</label>
                        <select class="form-select" id="ma_doi_moi" name="ma_doi_moi">
                            <option value="">-- Chọn đội --</option>
                            <?php foreach ($dsDoi as $doi): ?>
                                <?php if ((string) $doi['ma_doi'] === (string) $ct['ma_doi']) continue; ?>
                                <option value="<?= (int) $doi['ma_doi'] ?>"<?= chon($f['ma_doi_moi'], $doi['ma_doi']) ?>><?= e($doi['ten_doi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="ngay_chuyen">Ngày chuyển</label>
                        <input class="form-control" type="date" id="ngay_chuyen" name="ngay_chuyen" value="<?= e($f['ngay_chuyen']) ?>" required>
                    </div>
                    <div class="col-md-6" id="o-so-ao">
                        <label class="form-label fw-semibold" for="so_ao_moi">Số áo mới</label>
                        <input class="form-control" type="number" id="so_ao_moi" name="so_ao_moi" min="0" max="99" value="<?= e($f['so_ao_moi']) ?>">
                        <div class="form-text">Bỏ trống để giữ số áo hiện tại<?= $ct['so_ao'] !== null ? ' (#' . (int) $ct['so_ao'] . ')' : '' ?>.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="ghi_chu">Ghi chú</label>
                        <input class="form-control" type="text" id="ghi_chu" name="ghi_chu" maxlength="255" value="<?= e($f['ghi_chu']) ?>"
                               placeholder="VD: Trao đổi kèm 2 lượt chọn Draft vòng 2">
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Thực hiện</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Giải phóng (thành tự do) thì không cần chọn đội mới / số áo mới
        const chonLoai = document.getElementById('loai');
        function anHienDoiMoi() {
            const giaiPhong = chonLoai.value === 'giai_phong';
            document.getElementById('o-doi-moi').classList.toggle('d-none', giaiPhong);
            document.getElementById('o-so-ao').classList.toggle('d-none', giaiPhong);
        }
        chonLoai.addEventListener('change', anHienDoiMoi);
        anHienDoiMoi();
    </script>
<?php endif; ?>

<h2 class="h5 mt-4 mb-3">Lịch sử chuyển nhượng</h2>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Ngày</th><th>Loại</th><th>Từ đội</th><th>Đến đội</th><th>Ghi chú</th></tr></thead>
            <tbody>
            <?php if (!$lichSu): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">Chưa có giao dịch nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($lichSu as $ls): ?>
                <tr>
                    <td class="text-nowrap"><?= dinhDangNgay($ls['ngay_chuyen']) ?></td>
                    <td><?= e(LOAI_CHUYEN_NHUONG[$ls['loai']] ?? $ls['loai']) ?></td>
                    <td><?= e($ls['ten_doi_cu'] ?? 'Tự do') ?></td>
                    <td><?= e($ls['ten_doi_moi'] ?? 'Tự do') ?></td>
                    <td><?= e($ls['ghi_chu'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
