<?php
/** Admin - Nhập / sửa kết quả 1 trận: điểm từng hiệp, khán giả (nhap.php?id=5) - TV4 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_ket_qua.php';

$id   = (int) ($_GET['id'] ?? 0);
$tran = $id ? kqLayTran($id) : null;
if (!$tran) {
    datThongBao('loi', 'Không tìm thấy trận đấu.');
    chuyenTrang(url('/admin/ket_qua/index.php'));
}
$urlDs = url('/admin/ket_qua/index.php?mua=' . (int) $tran['ma_mua_giai']);
$lyDo = kqLyDoKhongNhap($tran);
if ($lyDo !== null) {
    datThongBao('loi', $lyDo);
    chuyenTrang($urlDs);
}
$daCo = $tran['trang_thai'] === 'da_dau';
$coBoxScore = kqCoBoxScore($id);

$d = kqLayDiemHiep($tran);
$d['khan_gia'] = $tran['khan_gia'];
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    [$d, $loi] = kqDocVaKiemTra($_POST, $tran);
    if (!$loi) {
        try {
            kqLuu($tran, $d);
            datThongBao('ok', 'Đã lưu kết quả ' . $tran['vt_khach'] . ' ' . array_sum($d['khach'])
                . ' - ' . array_sum($d['nha']) . ' ' . $tran['vt_nha'] . '.');
            chuyenTrang($urlDs);
        } catch (PDOException $ex) {
            $loi['chung'] = 'Không lưu được dữ liệu: ' . $ex->getMessage();
        }
    }
    // Giữ lại cả ô đã nhập nhưng sai để người dùng sửa
    foreach (['nha', 'khach'] as $ben) {
        foreach ((array) ($_POST['diem'][$ben] ?? []) as $h => $v) {
            if (!isset($d[$ben][(int) $h]) && trim((string) $v) !== '') {
                $d[$ben][(int) $h] = trim((string) $v);
            }
        }
    }
    $d['khan_gia'] = trim((string) ($_POST['khan_gia'] ?? ''));
}

$soHiep = KQ_SO_HIEP_CHINH + KQ_SO_HIEP_PHU_TOI_DA;
$loiHiep = array_filter($loi, fn($k) => strpos($k, 'hiep_') === 0, ARRAY_FILTER_USE_KEY);

$tieuDe = 'Nhập kết quả';
$menu   = 'ket_qua';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= e($urlDs) ?>"><i class="bi bi-arrow-left"></i> Danh sách trận</a></nav>
<h1 class="h3 mb-1"><?= e($tran['ten_doi_khach']) ?> <span class="text-muted">@</span> <?= e($tran['ten_doi_nha']) ?></h1>
<p class="text-muted mb-3">
    <?= e(kqHienThoiGian($tran['thoi_gian'])) ?> · <?= e($tran['ten_san'] ?? 'Chưa có sân') ?> · Mùa <?= e($tran['ten_mua_giai']) ?>
    <?php if ($daCo): ?><span class="badge text-bg-success ms-1">Đã có kết quả</span><?php endif; ?>
</p>

<?php if ($coBoxScore): ?>
    <div class="alert alert-warning">Trận đã có box score (TV5). Nếu sửa điểm, nhớ báo TV5 kiểm tra lại box score cho khớp tỉ số.</div>
<?php endif; ?>
<?php if (isset($loi['chung'])): ?>
    <div class="alert alert-danger"><?= e($loi['chung']) ?></div>
<?php endif; ?>
<?php if ($loiHiep): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($loiHiep as $k => $l): ?><li><?= e(kqTenHiep((int) substr($k, 5))) ?>: <?= e($l) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" novalidate>
            <?= oCsrf() ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center mb-1" style="min-width: 720px">
                    <thead class="table-light">
                    <tr>
                        <th class="text-start">Đội</th>
                        <?php for ($h = 1; $h <= $soHiep; $h++): ?>
                            <th class="<?= $h > KQ_SO_HIEP_CHINH ? 'text-muted' : '' ?><?= isset($loi["hiep_$h"]) ? ' text-danger' : '' ?>"><?= e(kqTenHiep($h)) ?></th>
                        <?php endfor; ?>
                        <th>Tổng</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (['khach' => $tran['ten_doi_khach'], 'nha' => $tran['ten_doi_nha']] as $ben => $tenDoi): ?>
                        <tr>
                            <td class="text-start fw-semibold text-nowrap">
                                <?= e($tenDoi) ?> <span class="text-muted small">(<?= $ben === 'nha' ? 'nhà' : 'khách' ?>)</span>
                            </td>
                            <?php for ($h = 1; $h <= $soHiep; $h++): ?>
                                <td style="width: 72px">
                                    <input class="form-control form-control-sm text-center o-diem<?= isset($loi["hiep_$h"]) ? ' is-invalid' : '' ?>"
                                           type="number" min="0" max="<?= KQ_DIEM_TOI_DA_MOT_HIEP ?>" inputmode="numeric"
                                           name="diem[<?= $ben ?>][<?= $h ?>]" data-ben="<?= $ben ?>"
                                           value="<?= e($d[$ben][$h] ?? '') ?>" aria-label="<?= e($tenDoi . ' ' . kqTenHiep($h)) ?>"
                                           <?= $h <= KQ_SO_HIEP_CHINH ? 'required' : '' ?>>
                                </td>
                            <?php endfor; ?>
                            <td class="fw-bold fs-5" id="tong_<?= $ben ?>">0</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-text mb-3">
                Q1-Q4 bắt buộc. Chỉ nhập hiệp phụ (OT, 2OT...) khi 2 đội hòa sau hiệp trước. Tổng tự cộng, không nhập tay.
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="khan_gia">Khán giả</label>
                    <input class="form-control<?= isset($loi['khan_gia']) ? ' is-invalid' : '' ?>" type="number" min="0" max="100000"
                           id="khan_gia" name="khan_gia" value="<?= e($d['khan_gia'] ?? '') ?>">
                    <?php if (isset($loi['khan_gia'])): ?><div class="invalid-feedback"><?= e($loi['khan_gia']) ?></div><?php endif; ?>
                    <?php if (!empty($tran['suc_chua'])): ?>
                        <div class="form-text">Sức chứa sân: <?= number_format((int) $tran['suc_chua'], 0, ',', '.') ?> chỗ.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Lưu kết quả</button>
                <a class="btn btn-outline-secondary" href="<?= e($urlDs) ?>">Hủy</a>
            </div>
        </form>

        <?php if ($daCo && !$coBoxScore): ?>
            <hr>
            <form method="post" action="<?= url('/admin/ket_qua/huy.php') ?>">
                <?= oCsrf() ?>
                <input type="hidden" name="id" value="<?= (int) $tran['ma_tran'] ?>">
                <button class="btn btn-outline-danger btn-sm btn-xoa" type="submit">
                    <i class="bi bi-x-circle"></i> Hủy kết quả (trận về "Chưa đấu")
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Tự cộng tổng điểm khi gõ
(function () {
    function capNhat() {
        ['nha', 'khach'].forEach(function (ben) {
            var tong = 0;
            document.querySelectorAll('.o-diem[data-ben="' + ben + '"]').forEach(function (o) {
                var v = parseInt(o.value, 10);
                if (!isNaN(v)) { tong += v; }
            });
            document.getElementById('tong_' + ben).textContent = tong;
        });
    }
    document.querySelectorAll('.o-diem').forEach(function (o) { o.addEventListener('input', capNhat); });
    capNhat();
})();
</script>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
