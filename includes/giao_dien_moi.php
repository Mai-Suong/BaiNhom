<?php
/**
 * Giao diện mới (kiểu nba.com) - hàm dùng chung cho trang chủ, danh sách đội, danh sách cầu thủ.
 * Trang nào dùng thì require SAU includes/init.php:
 *     require_once __DIR__ . '/includes/giao_dien_moi.php';
 * Tên hàm bắt đầu bằng "gd" để không trùng hàm của module khác.
 * CSS đi kèm: assets/css/style.css (phần "GIAO DIỆN MỚI").
 */

/* =====================================================================
 * MÀU ĐẶC TRƯNG CỦA 30 ĐỘI (theo ten_viet_tat): [màu nền, màu phụ]
 * Đội không có trong bảng (VD đội thêm mới) dùng màu xanh NBA.
 * ===================================================================== */
const GD_MAU_DOI = [
    'ATL' => ['#c8102e', '#fdb927'], 'BOS' => ['#007a33', '#ffffff'], 'BKN' => ['#111111', '#ffffff'],
    'BRK' => ['#111111', '#ffffff'], 'CHA' => ['#1d1160', '#00788c'], 'CHO' => ['#1d1160', '#00788c'],
    'CHI' => ['#ce1141', '#111111'], 'CLE' => ['#860038', '#fdbb30'], 'DAL' => ['#00538c', '#b8c4ca'],
    'DEN' => ['#0e2240', '#fec524'], 'DET' => ['#1d42ba', '#c8102e'], 'GSW' => ['#1d428a', '#ffc72c'],
    'HOU' => ['#ce1141', '#111111'], 'IND' => ['#002d62', '#fdbb30'], 'LAC' => ['#c8102e', '#1d428a'],
    'LAL' => ['#552583', '#fdb927'], 'MEM' => ['#5d76a9', '#12173f'], 'MIA' => ['#98002e', '#f9a01b'],
    'MIL' => ['#00471b', '#eee1c6'], 'MIN' => ['#0c2340', '#78be20'], 'NOP' => ['#0c2340', '#c8102e'],
    'NYK' => ['#006bb6', '#f58426'], 'OKC' => ['#007ac1', '#ef3b24'], 'ORL' => ['#0077c0', '#c4ced4'],
    'PHI' => ['#006bb6', '#ed174c'], 'PHX' => ['#1d1160', '#e56020'], 'PHO' => ['#1d1160', '#e56020'],
    'POR' => ['#e03a3e', '#111111'], 'SAC' => ['#5a2d81', '#c4ced4'], 'SAS' => ['#111111', '#c4ced4'],
    'TOR' => ['#ce1141', '#111111'], 'UTA' => ['#002b5c', '#f9a01b'], 'WAS' => ['#002b5c', '#e31837'],
];

/** Màu nền của đội, VD gdMauDoi('LAL') -> '#552583' */
function gdMauDoi(?string $vietTat): string
{
    return GD_MAU_DOI[strtoupper((string) $vietTat)][0] ?? '#17408b';
}

/** Màu phụ của đội (dùng cho chữ mờ trang trí) */
function gdMauPhu(?string $vietTat): string
{
    return GD_MAU_DOI[strtoupper((string) $vietTat)][1] ?? '#ffffff';
}

/** Đường dẫn ảnh: link http(s) giữ nguyên, file tải lên thì thêm BASE_URL */
function gdUrlAnh(string $duongDan): string
{
    if (function_exists('urlAnh')) {
        return urlAnh($duongDan);
    }
    $duongDan = trim($duongDan);
    return preg_match('#^https?://#i', $duongDan) ? $duongDan : url('/' . ltrim($duongDan, '/'));
}

/** Chữ viết tắt họ tên, VD "LeBron James" -> "LJ" */
function gdVietTatTen(string $ten): string
{
    $tu = preg_split('/\s+/u', trim($ten), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
    return mb_strtoupper(mb_substr($tu[0], 0, 1) . (count($tu) > 1 ? mb_substr(end($tu), 0, 1) : ''));
}

/**
 * Huy hiệu đội: logo nếu có, không có / lỗi tải thì vòng tròn màu đội ghi tên viết tắt.
 * VD gdHuyHieu($doi['logo'], 'LAL', 'Los Angeles Lakers', 28)
 */
function gdHuyHieu(?string $logo, string $vietTat, string $tenDoi = '', int $co = 28): string
{
    $tieuDe = $tenDoi !== '' ? $tenDoi : $vietTat;
    $o = '<span class="gd-huy-hieu%s" style="width:' . $co . 'px;height:' . $co . 'px;font-size:' . max(8, (int) ($co / 3.2)) . 'px;'
       . 'background:' . gdMauDoi($vietTat) . '" title="' . e($tieuDe) . '">' . e($vietTat) . '</span>';
    if (trim((string) $logo) === '') {
        return sprintf($o, '');
    }
    return '<img class="gd-logo" src="' . e(gdUrlAnh($logo)) . '" alt="' . e($tieuDe) . '" title="' . e($tieuDe) . '"'
         . ' width="' . $co . '" height="' . $co . '" loading="lazy"'
         . ' onerror="this.hidden=true;this.nextElementSibling.classList.remove(\'d-none\')">'
         . sprintf($o, ' d-none');
}

/**
 * Ảnh cầu thủ đứng trong khung (thẻ cầu thủ, khung nổi bật).
 * Không có ảnh / lỗi tải thì hiện chữ viết tắt tên.
 */
function gdAnhCauThu(?string $anh, string $hoTen, string $lop = 'gd-anh-dung'): string
{
    $chu = '<span class="gd-anh-chu%s" aria-hidden="true">' . e(gdVietTatTen($hoTen)) . '</span>';
    if (trim((string) $anh) === '') {
        return sprintf($chu, '');
    }
    return '<img class="' . e($lop) . '" src="' . e(gdUrlAnh($anh)) . '" alt="' . e($hoTen) . '" loading="lazy"'
         . ' onerror="this.hidden=true;this.nextElementSibling.classList.remove(\'d-none\')">'
         . sprintf($chu, ' d-none');
}

/* =====================================================================
 * DỮ LIỆU TRANG CHỦ (chỉ đọc)
 * ===================================================================== */

/**
 * Cầu thủ dẫn đầu một chỉ số trung bình ở mùa thường.
 * $cot: diem_tb | bat_bong_tb | kien_tao_tb | cuop_bong_tb | chan_bong_tb
 */
function gdDanDau(?int $maMua, string $cot, int $soNguoi = 1): array
{
    $hopLe = ['diem_tb', 'bat_bong_tb', 'kien_tao_tb', 'cuop_bong_tb', 'chan_bong_tb'];
    if ($maMua === null || !in_array($cot, $hopLe, true)) {
        return [];
    }
    $soNguoi = max(1, $soNguoi);
    $st = ketNoi()->prepare(
        "SELECT v.ma_cau_thu, v.so_tran, v.diem_tb, v.bat_bong_tb, v.kien_tao_tb, v.$cot AS gia_tri,
                ct.ho_ten, ct.anh, ct.vi_tri, ct.so_ao,
                d.ma_doi, d.ten_doi, d.ten_viet_tat, d.logo
           FROM v_chi_so_trung_binh v
           JOIN cau_thu ct      ON ct.ma_cau_thu = v.ma_cau_thu
           LEFT JOIN doi_bong d ON d.ma_doi = ct.ma_doi
          WHERE v.ma_mua_giai = ? AND v.giai_doan = 'thuong'
          ORDER BY v.$cot DESC, v.so_tran DESC, ct.ho_ten
          LIMIT $soNguoi"
    );
    $st->execute([$maMua]);
    return $st->fetchAll();
}

/** Bảng xếp hạng thu gọn: mỗi hội nghị lấy $soDoi đội đầu. Trả về [tên hội nghị => danh sách đội] */
function gdBxhGon(?int $maMua, int $soDoi = 5): array
{
    if ($maMua === null) {
        return [];
    }
    $st = ketNoi()->prepare(
        'SELECT v.ma_hoi_nghi, hn.ten_hoi_nghi, v.thang, v.thua, v.ti_le_thang,
                d.ma_doi, d.ten_doi, d.ten_viet_tat, d.logo
           FROM v_bang_xep_hang v
           JOIN doi_bong d  ON d.ma_doi = v.ma_doi
           JOIN hoi_nghi hn ON hn.ma_hoi_nghi = v.ma_hoi_nghi
          WHERE v.ma_mua_giai = ?
          ORDER BY v.ma_hoi_nghi, v.ti_le_thang DESC, v.thang DESC, d.ten_doi'
    );
    $st->execute([$maMua]);
    $kq = [];
    foreach ($st->fetchAll() as $r) {
        if (count($kq[$r['ten_hoi_nghi']] ?? []) < $soDoi) {
            $kq[$r['ten_hoi_nghi']][] = $r;
        }
    }
    return $kq;
}
