<?php
/**
 * Hàm hiển thị dùng riêng cho các trang TV3 (cầu thủ, trao giải).
 * Khung trang (header/footer, menu, thông báo) dùng chung của TV1:
 *   includes/admin_header.php + admin_footer.php (trang quản trị)
 *   includes/header.php + footer.php           (trang Khách / Cầu thủ)
 */
require_once __DIR__ . '/ham_cau_thu.php';

/** Ảnh đại diện cầu thủ, không có ảnh thì hiện chữ cái đầu trong vòng tròn */
function anhCauThu(array $ct, int $co = 44): string
{
    if (!empty($ct['anh'])) {
        return '<img class="rounded-circle object-fit-cover bg-light" src="' . e(url('/' . $ct['anh'])) . '" alt="'
             . e($ct['ho_ten']) . '" width="' . $co . '" height="' . $co . '">';
    }
    $chu = mb_strtoupper(mb_substr(trim($ct['ho_ten'] ?? '?'), 0, 1));
    return '<span class="rounded-circle bg-nba text-white fw-bold d-inline-flex align-items-center justify-content-center flex-shrink-0"'
         . ' style="width:' . $co . 'px;height:' . $co . 'px;font-size:' . (int) ($co / 2.4) . 'px">' . e($chu) . '</span>';
}

/** Nhãn trạng thái cầu thủ (Bootstrap badge) */
function nhanTrangThai(string $tt): string
{
    $mau = [
        'dang_thi_dau' => 'text-bg-success',
        'chan_thuong'  => 'text-bg-danger',
        'tu_do'        => 'text-bg-primary',
        'giai_nghe'    => 'text-bg-secondary',
    ][$tt] ?? 'text-bg-light';
    return '<span class="badge ' . $mau . '">' . e(TRANG_THAI_CT[$tt] ?? $tt) . '</span>';
}

/** Danh sách danh hiệu dạng thẻ, VD: "MVP · 2024-25" */
function hienThiDanhHieu(array $ds): string
{
    if (!$ds) {
        return '<div class="card card-body text-muted">Chưa có danh hiệu nào.</div>';
    }
    $html = '<div class="d-flex flex-wrap gap-2">';
    foreach ($ds as $dh) {
        $html .= '<div class="border border-warning-subtle bg-warning-subtle rounded px-3 py-2" title="' . e($dh['ten_giai']) . '">'
               . '<div class="fw-bold"><i class="bi bi-trophy-fill text-warning"></i> '
               . e($dh['ten_viet_tat']) . ' · ' . e($dh['ten_mua_giai']) . '</div>'
               . '<div class="small text-muted">' . e($dh['ten_giai']) . '</div></div>';
    }
    return $html . '</div>';
}

/** Thuộc tính selected cho <option> */
function chon($a, $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

/** Ô nhập liệu Bootstrap có nhãn và thông báo lỗi (cột rộng 1/2 trên màn hình vừa) */
function oNhap(string $ten, string $nhan, array $d, array $loi, string $kieu = 'text', string $them = ''): void
{
    ?>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="<?= e($ten) ?>"><?= e($nhan) ?></label>
        <input class="form-control<?= isset($loi[$ten]) ? ' is-invalid' : '' ?>" type="<?= e($kieu) ?>"
               id="<?= e($ten) ?>" name="<?= e($ten) ?>" value="<?= e($d[$ten] ?? '') ?>" <?= $them ?>>
        <?php if (isset($loi[$ten])): ?><div class="invalid-feedback"><?= e($loi[$ten]) ?></div><?php endif; ?>
    </div>
    <?php
}

/** Thanh phân trang Bootstrap, giữ nguyên các tham số lọc trong $thamSo */
function phanTrang(int $trang, int $soTrang, array $thamSo): string
{
    if ($soTrang <= 1) {
        return '';
    }
    $html = '<nav class="mt-3"><ul class="pagination pagination-sm flex-wrap mb-0">';
    for ($i = 1; $i <= $soTrang; $i++) {
        $link = '?' . http_build_query(array_filter($thamSo, fn($v) => $v !== '') + ['trang' => $i]);
        $html .= '<li class="page-item' . ($i === $trang ? ' active' : '') . '"><a class="page-link" href="'
               . e($link) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}
