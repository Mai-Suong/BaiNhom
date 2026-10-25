<?php
/** Admin - Danh sách cầu thủ, lọc theo đội / vị trí / quốc tịch / trạng thái */
require_once __DIR__ . '/../../includes/giao_dien.php';
yeuCauAdmin();

$loc = [
    'tu_khoa'    => trim($_GET['tu_khoa'] ?? ''),
    'ma_doi'     => (string) ($_GET['ma_doi'] ?? ''),
    'vi_tri'     => (string) ($_GET['vi_tri'] ?? ''),
    'quoc_tich'  => (string) ($_GET['quoc_tich'] ?? ''),
    'trang_thai' => (string) ($_GET['trang_thai'] ?? ''),
];
$kq    = layDanhSachCauThu($loc, (int) ($_GET['trang'] ?? 1));
$dsDoi = layDanhSachDoi();
$dsQT  = layDanhSachQuocTich();

dauTrang('Quản lý cầu thủ');
?>
<div class="dau">
    <h1>Quản lý cầu thủ</h1>
    <a class="nut" href="form.php">+ Thêm cầu thủ</a>
</div>

<form class="bo-loc" method="get">
    <input type="search" name="tu_khoa" placeholder="Tìm theo tên..." value="<?= e($loc['tu_khoa']) ?>">
    <select name="ma_doi">
        <option value="">Tất cả đội</option>
        <option value="tu_do"<?= chon($loc['ma_doi'], 'tu_do') ?>>— Cầu thủ tự do —</option>
        <?php foreach ($dsDoi as $d): ?>
            <option value="<?= $d['ma_doi'] ?>"<?= chon($loc['ma_doi'], $d['ma_doi']) ?>><?= e($d['ten_doi']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="vi_tri">
        <option value="">Tất cả vị trí</option>
        <?php foreach (VI_TRI as $ma => $ten): ?>
            <option value="<?= $ma ?>"<?= chon($loc['vi_tri'], $ma) ?>><?= e($ten) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="quoc_tich">
        <option value="">Tất cả quốc tịch</option>
        <?php foreach ($dsQT as $qt): ?>
            <option value="<?= e($qt) ?>"<?= chon($loc['quoc_tich'], $qt) ?>><?= e($qt) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="trang_thai">
        <option value="">Tất cả trạng thái</option>
        <?php foreach (TRANG_THAI_CT as $ma => $ten): ?>
            <option value="<?= $ma ?>"<?= chon($loc['trang_thai'], $ma) ?>><?= e($ten) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="nut" type="submit">Lọc</button>
    <a class="nut phu" href="index.php">Xóa lọc</a>
</form>

<p class="mo">Tìm thấy <?= $kq['tong'] ?> cầu thủ</p>

<div class="cuon-ngang">
<table>
    <thead>
    <tr>
        <th></th><th class="so">Số</th><th>Họ tên</th><th>Vị trí</th><th>Đội</th>
        <th>Quốc tịch</th><th>Trạng thái</th><th>Thao tác</th>
    </tr>
    </thead>
    <tbody>
    <?php if (!$kq['ds']): ?>
        <tr><td colspan="8" class="mo" style="text-align:center;padding:28px">Không có cầu thủ nào phù hợp.</td></tr>
    <?php endif; ?>
    <?php foreach ($kq['ds'] as $ct): ?>
        <tr>
            <td><?= anhCauThu($ct, 38) ?></td>
            <td class="so"><?= e($ct['so_ao'] ?? '—') ?></td>
            <td><a href="<?= BASE_URL ?>/cau_thu/ho_so.php?id=<?= $ct['ma_cau_thu'] ?>"><?= e($ct['ho_ten']) ?></a></td>
            <td><?= e($ct['vi_tri']) ?></td>
            <td><?= $ct['ten_doi'] ? e($ct['ten_doi']) : '<span class="mo">Tự do</span>' ?></td>
            <td><?= e($ct['quoc_tich'] ?? '—') ?></td>
            <td><?= nhanTrangThai($ct['trang_thai']) ?></td>
            <td style="white-space:nowrap">
                <a class="nut phu nho" href="form.php?id=<?= $ct['ma_cau_thu'] ?>">Sửa</a>
                <a class="nut phu nho" href="chuyen_nhuong.php?id=<?= $ct['ma_cau_thu'] ?>">Chuyển nhượng</a>
                <a class="nut phu nho" href="chan_thuong.php?id=<?= $ct['ma_cau_thu'] ?>">Chấn thương</a>
                <form class="inline" method="post" action="xoa.php"
                      onsubmit="return confirm('Xóa cầu thủ <?= e(addslashes($ct['ho_ten'])) ?>?')">
                    <?= oCsrf() ?>
                    <input type="hidden" name="id" value="<?= $ct['ma_cau_thu'] ?>">
                    <button class="nut do nho" type="submit">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php if ($kq['so_trang'] > 1): ?>
<div class="phan-trang">
    <?php for ($i = 1; $i <= $kq['so_trang']; $i++): ?>
        <?php if ($i === $kq['trang']): ?>
            <span><?= $i ?></span>
        <?php else: ?>
            <a href="?<?= e(http_build_query($loc + ['trang' => $i])) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php cuoiTrang();
