<?php
/**
 * Khung giao diện tạm cho module Cầu thủ.
 * Khi nhóm có header/footer chung thì thay dauTrang()/cuoiTrang() bằng include của nhóm.
 */
require_once __DIR__ . '/ham_cau_thu.php';
require_once __DIR__ . '/kiem_tra_quyen.php';

function dauTrang(string $tieuDe): void
{
    $tb = layThongBao();
    ?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($tieuDe) ?></title>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif;
           background: #f4f5f7; color: #1f2933; font-size: 15px; }
    .khung { max-width: 1150px; margin: 0 auto; padding: 24px 16px 48px; }
    h1 { font-size: 24px; margin: 0; } h2 { font-size: 18px; margin: 28px 0 12px; }
    a { color: #c2410c; text-decoration: none; } a:hover { text-decoration: underline; }
    .dau { display: flex; justify-content: space-between; align-items: center;
           gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .the { background: #fff; border: 1px solid #e3e6ea; border-radius: 10px; padding: 18px; }
    .nut { display: inline-block; background: #c2410c; color: #fff; border: 0; border-radius: 6px;
           padding: 8px 14px; font-size: 14px; cursor: pointer; text-decoration: none; }
    .nut:hover { background: #9a3412; text-decoration: none; }
    .nut.phu { background: #fff; color: #374151; border: 1px solid #cbd2d9; }
    .nut.do { background: #b91c1c; } .nut.nho { padding: 4px 9px; font-size: 13px; }
    .bo-loc { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 14px; }
    input, select, textarea { font: inherit; padding: 7px 9px; border: 1px solid #cbd2d9;
           border-radius: 6px; background: #fff; max-width: 100%; }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { padding: 9px 10px; border-bottom: 1px solid #eceff2; text-align: left; vertical-align: middle; }
    th { background: #f8f9fa; font-size: 13px; color: #52606d; font-weight: 600; }
    .cuon-ngang { overflow-x: auto; border: 1px solid #e3e6ea; border-radius: 10px; }
    .so { text-align: right; font-variant-numeric: tabular-nums; }
    .anh { border-radius: 50%; object-fit: cover; background: #e4e7eb; display: inline-flex;
           align-items: center; justify-content: center; color: #52606d; font-weight: 600; }
    .nhan { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 12px; font-weight: 600; }
    .nhan.dang_thi_dau { background: #dcfce7; color: #166534; }
    .nhan.chan_thuong  { background: #fee2e2; color: #991b1b; }
    .nhan.tu_do        { background: #e0e7ff; color: #3730a3; }
    .nhan.giai_nghe    { background: #e5e7eb; color: #374151; }
    .thong-bao { padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
    .thong-bao.ok  { background: #dcfce7; color: #166534; }
    .thong-bao.loi { background: #fee2e2; color: #991b1b; }
    .luoi-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px 18px; }
    .truong label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
    .truong input, .truong select, .truong textarea { width: 100%; }
    .truong .loi { color: #b91c1c; font-size: 13px; margin-top: 3px; }
    .phan-trang { display: flex; gap: 4px; margin-top: 14px; flex-wrap: wrap; }
    .phan-trang a, .phan-trang span { padding: 5px 10px; border: 1px solid #cbd2d9; border-radius: 6px; background: #fff; }
    .phan-trang span { background: #c2410c; color: #fff; border-color: #c2410c; }
    .ho-so { display: flex; gap: 22px; align-items: flex-start; flex-wrap: wrap; }
    .thong-tin { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px 20px; flex: 1; }
    .thong-tin div small { display: block; color: #7b8794; font-size: 12px; }
    .mo { color: #7b8794; }
    form.inline { display: inline; }
</style>
</head>
<body>
<div class="khung">
<?php if ($tb): ?>
    <div class="thong-bao <?= e($tb['loai']) ?>"><?= e($tb['noi_dung']) ?></div>
<?php endif;
}

function cuoiTrang(): void
{
    echo "</div>\n</body>\n</html>";
}

/** Ảnh cầu thủ, không có ảnh thì hiện chữ cái đầu */
function anhCauThu(array $ct, int $co = 44): string
{
    if (!empty($ct['anh'])) {
        return '<img class="anh" src="' . e(BASE_URL . '/' . $ct['anh']) . '" alt="' . e($ct['ho_ten'])
             . '" width="' . $co . '" height="' . $co . '">';
    }
    $chu = mb_strtoupper(mb_substr(trim($ct['ho_ten'] ?? '?'), 0, 1));
    return '<span class="anh" style="width:' . $co . 'px;height:' . $co . 'px;font-size:'
         . (int) ($co / 2.4) . 'px">' . e($chu) . '</span>';
}

function nhanTrangThai(string $tt): string
{
    return '<span class="nhan ' . e($tt) . '">' . e(TRANG_THAI_CT[$tt] ?? $tt) . '</span>';
}

function chon($a, $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

/** Ô nhập liệu có nhãn và thông báo lỗi */
function oNhap(string $ten, string $nhan, array $d, array $loi, string $kieu = 'text', string $them = ''): void
{
    ?>
    <div class="truong">
        <label for="<?= e($ten) ?>"><?= e($nhan) ?></label>
        <input type="<?= e($kieu) ?>" id="<?= e($ten) ?>" name="<?= e($ten) ?>"
               value="<?= e($d[$ten] ?? '') ?>" <?= $them ?>>
        <?php if (isset($loi[$ten])): ?><div class="loi"><?= e($loi[$ten]) ?></div><?php endif; ?>
    </div>
    <?php
}
