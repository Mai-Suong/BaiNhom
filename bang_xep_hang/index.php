<?php
/**
 * Bảng xếp hạng (TV5) - đọc từ VIEW v_bang_xep_hang.
 *   /bang_xep_hang/index.php                -> Miền Đông, mùa hiện tại
 *   /bang_xep_hang/index.php?hn=2           -> Miền Tây
 *   /bang_xep_hang/index.php?kieu=khuvuc    -> chia theo khu vực
 *   /bang_xep_hang/index.php?mua=3&hn=1     -> chọn mùa giải theo ma_mua_giai
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_thong_ke.php';

[$dsMua, $maMua] = tkLayMua($pdo);
$kieu = (($_GET['kieu'] ?? '') === 'khuvuc') ? 'khuvuc' : 'hoinghi';
$hn   = ((int) ($_GET['hn'] ?? 1) === 2) ? 2 : 1;

// Sắp theo tỉ lệ thắng, rồi số trận thắng, rồi hiệu số điểm
$tatCa = tkTruyVan($pdo,
    'SELECT v.ma_doi, v.ma_khu_vuc, v.ma_hoi_nghi,
            v.thang, v.thua, v.ti_le_thang, v.cach_dau_bang,
            v.thang_san_nha, v.thua_san_nha, v.thang_san_khach, v.thua_san_khach,
            v.tong_diem_ghi, v.tong_diem_bi_ghi,
            d.ten_doi, d.ten_viet_tat, kv.ten_khu_vuc, hn.ten_hoi_nghi
     FROM v_bang_xep_hang v
     JOIN doi_bong d  ON d.ma_doi = v.ma_doi
     JOIN khu_vuc kv  ON kv.ma_khu_vuc = v.ma_khu_vuc
     JOIN hoi_nghi hn ON hn.ma_hoi_nghi = v.ma_hoi_nghi
     WHERE v.ma_mua_giai = ?
     ORDER BY v.ma_hoi_nghi, v.ti_le_thang DESC, v.thang DESC,
              (v.tong_diem_ghi - v.tong_diem_bi_ghi) DESC, d.ten_doi', [$maMua]);

// Gom nhóm: theo hội nghị (có hạng Playoff / Play-in) hoặc theo khu vực
$nhom = [];
foreach ($tatCa as $r) {
    if ($kieu === 'khuvuc') {
        $nhom[$r['ten_hoi_nghi'] . ' · ' . $r['ten_khu_vuc']][] = $r;
    } elseif ((int) $r['ma_hoi_nghi'] === $hn) {
        $nhom[$r['ten_hoi_nghi']][] = $r;
    }
}

/** Hạng 1-6 vào thẳng Playoff, hạng 7-10 đá Play-in */
function tkNhomXepHang(int $hang, bool $theoKhuVuc): array
{
    if ($theoKhuVuc) { return ['', '']; }
    if ($hang <= 6)  { return ['playoff', 'Playoff']; }
    if ($hang <= 10) { return ['playin', 'Play-in']; }
    return ['', ''];
}

$tieuDe = 'Bảng xếp hạng';
$menu   = 'bxh';
require __DIR__ . '/../includes/header.php';
echo tkCss();
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Bảng xếp hạng</h1>
        <p class="text-muted mb-0">Mùa thường · chỉ tính các trận đã đấu</p>
    </div>
    <?= tkChonMua($dsMua, $maMua, ['kieu' => $kieu, 'hn' => $hn]) ?>
</div>

<div class="btn-group mb-3" role="group" aria-label="Kiểu xem">
    <a class="btn btn-outline-primary<?= ($kieu === 'hoinghi' && $hn === 1) ? ' active' : '' ?>" href="<?= e(tkUrlVoi(['kieu' => 'hoinghi', 'hn' => 1])) ?>">Miền Đông</a>
    <a class="btn btn-outline-primary<?= ($kieu === 'hoinghi' && $hn === 2) ? ' active' : '' ?>" href="<?= e(tkUrlVoi(['kieu' => 'hoinghi', 'hn' => 2])) ?>">Miền Tây</a>
    <a class="btn btn-outline-primary<?= $kieu === 'khuvuc' ? ' active' : '' ?>" href="<?= e(tkUrlVoi(['kieu' => 'khuvuc'])) ?>">Theo khu vực</a>
</div>

<?php if ($kieu === 'hoinghi'): ?>
    <div class="chu-thich d-flex flex-wrap gap-3 small mb-3">
        <span><i style="background:#1f7a4f"></i>Playoff (hạng 1–6)</span>
        <span><i style="background:#2b5fa8"></i>Play-in (hạng 7–10)</span>
    </div>
<?php endif; ?>

<?php if (!$nhom): ?>
    <div class="alert alert-info">Chưa có dữ liệu xếp hạng cho mùa giải này. Cần có dữ liệu phân bảng (<code>phan_bang</code>) và các trận đã đấu.</div>
<?php endif; ?>

<?php foreach ($nhom as $ten => $dsDoi): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold"><?= e($ten) ?></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 bang-so">
                <thead>
                    <tr>
                        <th class="giua">Hạng</th><th class="trai">Đội</th>
                        <th>Thắng</th><th>Thua</th><th>Tỉ lệ</th><th>Cách đầu</th>
                        <th>Sân nhà</th><th>Sân khách</th><th>Điểm ghi</th><th>Điểm thủng</th><th class="giua">Nhóm</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dsDoi as $i => $r):
                    [$lop, $nhan] = tkNhomXepHang($i + 1, $kieu === 'khuvuc');
                    $tiLe = $r['ti_le_thang'] === null ? '—' : ltrim(number_format((float) $r['ti_le_thang'], 3, '.', ''), '0');
                    $gb   = (float) $r['cach_dau_bang'];
                ?>
                    <tr class="<?= $lop ?>">
                        <td class="giua"><span class="hang"><?= $i + 1 ?></span></td>
                        <td class="trai"><span class="vong-tron"><?= e($r['ten_viet_tat']) ?></span><?= e($r['ten_doi']) ?></td>
                        <td><strong><?= (int) $r['thang'] ?></strong></td>
                        <td><?= (int) $r['thua'] ?></td>
                        <td><strong><?= e($tiLe) ?></strong></td>
                        <td><?= $gb == 0 ? '—' : tkSo($gb) ?></td>
                        <td><?= (int) $r['thang_san_nha'] ?>-<?= (int) $r['thua_san_nha'] ?></td>
                        <td><?= (int) $r['thang_san_khach'] ?>-<?= (int) $r['thua_san_khach'] ?></td>
                        <td><?= (int) $r['tong_diem_ghi'] ?></td>
                        <td><?= (int) $r['tong_diem_bi_ghi'] ?></td>
                        <td class="giua nhom-nhan"><?= e($nhan) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<div class="row g-3 small text-muted">
    <div class="col-md-4"><strong class="text-body">Tỉ lệ thắng</strong><br>Thắng ÷ (Thắng + Thua), làm tròn 3 chữ số.</div>
    <div class="col-md-4"><strong class="text-body">Cách đầu bảng</strong><br>((Thắng đầu − Thua đầu) − (Thắng − Thua)) ÷ 2, trong cùng hội nghị.</div>
    <div class="col-md-4"><strong class="text-body">Nguồn dữ liệu</strong><br>View <code>v_bang_xep_hang</code>, chỉ tính trận mùa thường đã đấu.</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
