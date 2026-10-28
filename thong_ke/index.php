<?php
/**
 * Top cầu thủ (TV5) - đọc từ VIEW v_chi_so_trung_binh.
 *   /thong_ke/index.php                       -> mùa hiện tại, mùa thường, xếp theo điểm
 *   /thong_ke/index.php?gd=playoff            -> chỉ tính các trận playoff
 *   /thong_ke/index.php?sx=kien_tao&mua=2     -> xếp theo kiến tạo, mùa có ma_mua_giai = 2
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_thong_ke.php';

[$dsMua, $maMua] = tkLayMua($pdo);
$giaiDoan = (($_GET['gd'] ?? '') === 'playoff') ? 'playoff' : 'thuong';

// khóa trên URL => [cột trong view, nhãn]
$cot = [
    'diem'      => ['diem_tb', 'Điểm'],
    'bat_bong'  => ['bat_bong_tb', 'Bắt bóng'],
    'kien_tao'  => ['kien_tao_tb', 'Kiến tạo'],
    'cuop_bong' => ['cuop_bong_tb', 'Cướp bóng'],
    'chan_bong' => ['chan_bong_tb', 'Chặn bóng'],
];
$sx = isset($cot[$_GET['sx'] ?? '']) ? $_GET['sx'] : 'diem';

$dong = tkTruyVan($pdo,
    'SELECT v.*, c.ho_ten, c.vi_tri, d.ten_viet_tat
     FROM v_chi_so_trung_binh v
     JOIN cau_thu c ON c.ma_cau_thu = v.ma_cau_thu
     LEFT JOIN doi_bong d ON d.ma_doi = c.ma_doi
     WHERE v.ma_mua_giai = ? AND v.giai_doan = ?', [$maMua, $giaiDoan]);

// Người dẫn đầu từng chỉ số
$danDau = [];
foreach ($cot as [$tenCot, $nhan]) {
    $tot = null;
    foreach ($dong as $r) {
        if ($tot === null || (float) $r[$tenCot] > (float) $tot[$tenCot]) {
            $tot = $r;
        }
    }
    if ($tot !== null) {
        $danDau[] = ['nhan' => $nhan, 'gia_tri' => tkSo($tot[$tenCot]), 'ma' => (int) $tot['ma_cau_thu'], 'ten' => $tot['ho_ten'],
                     'doi' => trim(($tot['ten_viet_tat'] ?? '') . ' · ' . $tot['vi_tri'], ' ·')];
    }
}

// Bảng top 10 theo tiêu chí đang chọn
$cotXep = $cot[$sx][0];
usort($dong, function ($a, $b) use ($cotXep) {
    $so = (float) $b[$cotXep] <=> (float) $a[$cotXep];
    return $so !== 0 ? $so : strcmp($a['ho_ten'], $b['ho_ten']);
});
$top = array_slice($dong, 0, 10);

$tieuDe = 'Top cầu thủ';
$menu   = 'thong_ke';
require __DIR__ . '/../includes/header.php';
echo tkCss();
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Thống kê</h1>
        <p class="text-muted mb-0">Top cầu thủ · chỉ số trung bình mỗi trận</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-3">
        <?= tkChonMua($dsMua, $maMua, ['gd' => $giaiDoan, 'sx' => $sx]) ?>
        <div class="btn-group" role="group" aria-label="Giai đoạn">
            <a class="btn btn-sm btn-outline-primary<?= $giaiDoan === 'thuong' ? ' active' : '' ?>" href="<?= e(tkUrlVoi(['gd' => 'thuong'])) ?>">Mùa thường</a>
            <a class="btn btn-sm btn-outline-primary<?= $giaiDoan === 'playoff' ? ' active' : '' ?>" href="<?= e(tkUrlVoi(['gd' => 'playoff'])) ?>">Playoff</a>
        </div>
    </div>
</div>

<?= tkMenu('top') ?>

<?php if (!$dong): ?>
    <div class="alert alert-info">Chưa có số liệu. Cần có box score của các trận đã đấu (bảng <code>chi_so_tran_dau</code>).</div>
<?php else: ?>

    <div class="row g-3 mb-4">
        <?php foreach ($danDau as $o): ?>
            <div class="col-6 col-md-4 col-xl">
                <div class="card shadow-sm h-100 o-dan-dau">
                    <div class="card-body">
                        <div class="nhan-nho"><?= e($o['nhan']) ?></div>
                        <div class="so-lon"><?= e($o['gia_tri']) ?></div>
                        <a class="fw-semibold d-block text-truncate text-decoration-none" href="<?= url('/cau_thu/ho_so.php?id=' . $o['ma']) ?>"><?= e($o['ten']) ?></a>
                        <div class="small text-muted"><?= e($o['doi']) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="fw-semibold">Xếp theo</span>
        <div class="btn-group" role="group" aria-label="Tiêu chí xếp hạng">
            <?php foreach ($cot as $khoa => [$tenCot, $nhan]): ?>
                <a class="btn btn-sm btn-outline-secondary<?= $khoa === $sx ? ' active' : '' ?>" href="<?= e(tkUrlVoi(['sx' => $khoa])) ?>"><?= e($nhan) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-2">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 bang-so">
                <thead>
                    <tr>
                        <th class="giua">Hạng</th><th class="trai">Cầu thủ</th><th class="giua">Đội</th>
                        <th>Trận</th><th>Phút</th>
                        <?php foreach ($cot as $khoa => [$tenCot, $nhan]): ?>
                            <th<?= $khoa === $sx ? ' class="cot-chinh"' : '' ?>><?= e($nhan) ?></th>
                        <?php endforeach; ?>
                        <th>Mất bóng</th><th>% ném</th><th>% 3 điểm</th><th>% phạt</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($top as $i => $r): ?>
                    <tr>
                        <td class="giua"><strong><?= $i + 1 ?></strong></td>
                        <td class="trai"><a class="text-decoration-none" href="<?= url('/cau_thu/ho_so.php?id=' . (int) $r['ma_cau_thu']) ?>"><?= e($r['ho_ten']) ?></a> <small class="text-muted"><?= e($r['vi_tri']) ?></small></td>
                        <td class="giua"><?= e($r['ten_viet_tat'] ?? '—') ?></td>
                        <td><?= (int) $r['so_tran'] ?></td>
                        <td><?= tkSo($r['phut_tb']) ?></td>
                        <?php foreach ($cot as $khoa => [$tenCot, $nhan]): ?>
                            <td<?= $khoa === $sx ? ' class="cot-chinh"' : '' ?>><?= tkSo($r[$tenCot]) ?></td>
                        <?php endforeach; ?>
                        <td><?= tkSo($r['mat_bong_tb']) ?></td>
                        <td><?= $r['ti_le_nem'] === null ? '—' : tkSo($r['ti_le_nem']) ?></td>
                        <td><?= $r['ti_le_3_diem'] === null ? '—' : tkSo($r['ti_le_3_diem']) ?></td>
                        <td><?= $r['ti_le_phat'] === null ? '—' : tkSo($r['ti_le_phat']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="small text-muted">Chỉ tính các trận đã đấu và cầu thủ có trạng thái Thi đấu. Nguồn: view <code>v_chi_so_trung_binh</code>.</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
