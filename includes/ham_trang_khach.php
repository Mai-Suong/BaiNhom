<?php
/**
 * TRANG KHÁCH ĐỘI BÓNG (TV2) - danh sách đội, chi tiết đội theo từng mùa (thư mục doi_bong/).
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng (chỉ đọc): doi_bong, san_thi_dau, huan_luyen_vien, nhiem_ky_hlv, cau_thu, mua_giai,
 *   phan_bang, khu_vuc, hoi_nghi, tran_dau, chi_so_tran_dau, view v_bang_xep_hang
 *
 * Mỗi module của TV2 nằm trong 1 file riêng để các Pull Request độc lập, merge thứ tự nào cũng được.
 * Tiền tố tkh... (trang khách) - không trùng tk... của TV1 (ham_tai_khoan) và TV5 (ham_thong_ke).
 */
require_once __DIR__ . '/tv2_chung.php';

/* =====================================================================
 * MÙA GIẢI (?mua=) - chọn giống trang của TV4: theo mã nếu có, không thì
 * mùa hiện tại, không thì mùa mới nhất.
 * ===================================================================== */

function tkhLayDsMua(): array
{
    return ketNoi()->query(
        'SELECT ma_mua_giai, ten_mua_giai, ngay_bat_dau, ngay_ket_thuc, la_mua_hien_tai
           FROM mua_giai ORDER BY ngay_bat_dau DESC'
    )->fetchAll();
}

function tkhChonMua(array $dsMua, int $maMua): ?array
{
    foreach ($dsMua as $m) {
        if ((int) $m['ma_mua_giai'] === $maMua) {
            return $m;
        }
    }
    foreach ($dsMua as $m) {
        if ((int) $m['la_mua_hien_tai'] === 1) {
            return $m;
        }
    }
    return $dsMua[0] ?? null;
}

function tkhLaMuaHienTai(?array $mua): bool
{
    return $mua !== null && (int) $mua['la_mua_hien_tai'] === 1;
}

/* =====================================================================
 * DANH SÁCH ĐỘI
 * ===================================================================== */

function tkhLayHoiNghi(): array
{
    return ketNoi()->query('SELECT ma_hoi_nghi, ten_hoi_nghi FROM hoi_nghi ORDER BY ma_hoi_nghi')->fetchAll();
}

/** Số đội đã được phân bảng trong mùa */
function tkhDemPhanBang(int $maMuaGiai): int
{
    $st = ketNoi()->prepare('SELECT COUNT(*) FROM phan_bang WHERE ma_mua_giai = ?');
    $st->execute([$maMuaGiai]);
    return (int) $st->fetchColumn();
}

/**
 * Danh sách đội cho Khách, kèm sân nhà (hiện tại) và khu vực / hội nghị trong mùa $maMuaGiai.
 * $maHoiNghi = NULL: tất cả đội; có giá trị: chỉ đội thuộc hội nghị đó (cần có phân bảng).
 */
function tkhLayDanhSachDoi(?int $maMuaGiai, ?int $maHoiNghi = null): array
{
    $sql = 'SELECT d.ma_doi, d.ten_doi, d.ten_viet_tat, d.thanh_pho, d.logo, d.ma_san,
                   s.ten_san, kv.ma_khu_vuc, kv.ten_khu_vuc, hn.ma_hoi_nghi, hn.ten_hoi_nghi
              FROM doi_bong d
              LEFT JOIN san_thi_dau s ON s.ma_san = d.ma_san
              LEFT JOIN phan_bang pb  ON pb.ma_doi = d.ma_doi AND pb.ma_mua_giai = ?
              LEFT JOIN khu_vuc kv    ON kv.ma_khu_vuc = pb.ma_khu_vuc
              LEFT JOIN hoi_nghi hn   ON hn.ma_hoi_nghi = kv.ma_hoi_nghi';
    $thamSo = [$maMuaGiai ?? 0];
    if ($maHoiNghi !== null) {
        $sql .= ' WHERE kv.ma_hoi_nghi = ?';
        $thamSo[] = $maHoiNghi;
    }
    $st = ketNoi()->prepare($sql . ' ORDER BY kv.ma_khu_vuc IS NULL, kv.ma_khu_vuc, d.ten_doi');
    $st->execute($thamSo);
    return $st->fetchAll();
}

/* =====================================================================
 * CHI TIẾT ĐỘI THEO MÙA
 * ===================================================================== */

/** Thông tin đội + khu vực / hội nghị trong mùa $maMuaGiai */
function tkhLayChiTietDoi(int $maDoi, ?int $maMuaGiai): ?array
{
    $st = ketNoi()->prepare(
        'SELECT d.*, kv.ten_khu_vuc, hn.ten_hoi_nghi
           FROM doi_bong d
           LEFT JOIN phan_bang pb  ON pb.ma_doi = d.ma_doi AND pb.ma_mua_giai = ?
           LEFT JOIN khu_vuc kv    ON kv.ma_khu_vuc = pb.ma_khu_vuc
           LEFT JOIN hoi_nghi hn   ON hn.ma_hoi_nghi = kv.ma_hoi_nghi
          WHERE d.ma_doi = ?'
    );
    $st->execute([$maMuaGiai ?? 0, $maDoi]);
    return $st->fetch() ?: null;
}

/**
 * Tên cũ của các sân đã đổi tên trong 3 mùa nhóm xem: ma_san => [[ngày cuối dùng tên cũ, tên cũ], ...]
 * (xếp theo thời gian). Nguồn: Wikipedia "2024-25 NBA season", "2025-26 NBA season".
 */
const TKH_TEN_SAN_CU = [
    6  => [['2025-02-17', 'Rocket Mortgage FieldHouse']],                          // CLE -> Rocket Arena 18/02/2025
    23 => [['2025-08-13', 'Wells Fargo Center']],                                  // PHI -> Xfinity Mobile Arena 14/08/2025
    24 => [['2025-02-17', 'Footprint Center'], ['2025-10-01', 'PHX Arena']],       // PHO -> PHX Arena 18/02/2025 -> Mortgage Matchup Center 02/10/2025
];

/** Các tên sân đã dùng trong khoảng [$tu, $den] (theo thứ tự thời gian) */
function tkhTenSanTrongKhoang(int $maSan, string $tenHienTai, string $tu, string $den): array
{
    $ds = [];
    $batDau = '0000-00-00';
    foreach (TKH_TEN_SAN_CU[$maSan] ?? [] as [$ngayCuoi, $tenCu]) {
        if ($batDau <= $den && $ngayCuoi >= $tu) {
            $ds[] = $tenCu;
        }
        $batDau = date('Y-m-d', strtotime($ngayCuoi . ' +1 day'));
    }
    if ($batDau <= $den) {
        $ds[] = $tenHienTai;
    }
    return $ds ?: [$tenHienTai];
}

/** Sân nhà cũ khi đội đổi sân: ma_doi => [[ngày cuối dùng sân cũ, ma_san cũ], ...] (LAC chuyển sang Intuit Dome mùa 2024-25) */
const TKH_SAN_NHA_CU = [
    13 => [['2024-06-30', 14]],   // LAC: Crypto.com Arena đến hết mùa 2023-24
];

/**
 * Sân nhà của đội trong mùa: sân đội tổ chức nhiều trận sân nhà nhất trong mùa đó;
 * mùa chưa có trận thì theo TKH_SAN_NHA_CU, không có thì lấy sân nhà hiện tại.
 * Trả về thông tin sân + 'ten_trong_mua' (mảng tên sân dùng trong mùa).
 */
function tkhLaySanNhaMua(array $doi, ?array $mua): ?array
{
    $pdo = ketNoi();
    $maSan = $doi['ma_san'] !== null ? (int) $doi['ma_san'] : null;
    if ($mua) {
        $st = $pdo->prepare(
            'SELECT ma_san FROM tran_dau
              WHERE ma_doi_nha = ? AND ma_mua_giai = ? AND ma_san IS NOT NULL
              GROUP BY ma_san ORDER BY COUNT(*) DESC, ma_san LIMIT 1'
        );
        $st->execute([(int) $doi['ma_doi'], (int) $mua['ma_mua_giai']]);
        $tuTran = $st->fetchColumn();
        if ($tuTran !== false) {
            $maSan = (int) $tuTran;
        } else {
            foreach (TKH_SAN_NHA_CU[(int) $doi['ma_doi']] ?? [] as [$ngayCuoi, $maSanCu]) {
                if ($mua['ngay_bat_dau'] <= $ngayCuoi) {
                    $maSan = $maSanCu;
                    break;
                }
            }
        }
    }
    if ($maSan === null) {
        return null;
    }
    $st = $pdo->prepare('SELECT * FROM san_thi_dau WHERE ma_san = ?');
    $st->execute([$maSan]);
    $san = $st->fetch();
    if (!$san) {
        return null;
    }
    $san['ten_trong_mua'] = $mua
        ? tkhTenSanTrongKhoang($maSan, $san['ten_san'], $mua['ngay_bat_dau'], $mua['ngay_ket_thuc'])
        : [$san['ten_san']];
    return $san;
}

/**
 * Ban huấn luyện của đội trong mùa (bảng nhiem_ky_hlv), HLV trưởng trước, theo thời gian.
 * Mùa hiện tại chưa nhập nhiệm kỳ thì lấy HLV hiện tại của đội (huan_luyen_vien.ma_doi).
 */
function tkhLayBanHuanLuyen(int $maDoi, ?array $mua): array
{
    $pdo = ketNoi();
    if ($mua) {
        $st = $pdo->prepare(
            "SELECT h.ma_hlv, h.ho_ten, h.quoc_tich, h.anh, nk.vai_tro, nk.tam_quyen, nk.tu_ngay, nk.den_ngay
               FROM nhiem_ky_hlv nk
               JOIN huan_luyen_vien h ON h.ma_hlv = nk.ma_hlv
              WHERE nk.ma_doi = ? AND nk.ma_mua_giai = ?
              ORDER BY nk.vai_tro = 'pho', nk.tu_ngay, h.ho_ten"
        );
        $st->execute([$maDoi, (int) $mua['ma_mua_giai']]);
        $ds = $st->fetchAll();
        if ($ds || !tkhLaMuaHienTai($mua)) {
            return $ds;
        }
    }
    $st = $pdo->prepare(
        "SELECT ma_hlv, ho_ten, quoc_tich, anh, vai_tro, 0 AS tam_quyen, ngay_bat_dau AS tu_ngay, NULL AS den_ngay
           FROM huan_luyen_vien
          WHERE ma_doi = ?
          ORDER BY vai_tro = 'pho', ho_ten"
    );
    $st->execute([$maDoi]);
    return $st->fetchAll();
}

/**
 * Đội hình trong mùa.
 *  - Mùa hiện tại: cầu thủ đang thuộc đội (cau_thu.ma_doi), trừ người đã giải nghệ.
 *  - Mùa cũ: cầu thủ đã ra sân cho đội trong mùa đó (theo box score chi_so_tran_dau), kèm số trận.
 */
function tkhLayDoiHinh(int $maDoi, ?array $mua): array
{
    if ($mua === null || tkhLaMuaHienTai($mua)) {
        $st = ketNoi()->prepare(
            "SELECT ma_cau_thu, ho_ten, so_ao, vi_tri, chieu_cao_cm, can_nang_kg, quoc_tich, anh, trang_thai,
                    NULL AS so_tran
               FROM cau_thu
              WHERE ma_doi = ? AND trang_thai <> 'giai_nghe'
              ORDER BY so_ao IS NULL, so_ao, ho_ten"
        );
        $st->execute([$maDoi]);
        return $st->fetchAll();
    }
    $st = ketNoi()->prepare(
        "SELECT ct.ma_cau_thu, ct.ho_ten, ct.so_ao, ct.vi_tri, ct.chieu_cao_cm, ct.can_nang_kg, ct.quoc_tich,
                ct.anh, ct.trang_thai, COUNT(*) AS so_tran
           FROM chi_so_tran_dau cs
           JOIN tran_dau td ON td.ma_tran = cs.ma_tran
           JOIN cau_thu ct  ON ct.ma_cau_thu = cs.ma_cau_thu
          WHERE cs.ma_doi = ? AND td.ma_mua_giai = ? AND cs.trang_thai = 'thi_dau'
          GROUP BY ct.ma_cau_thu, ct.ho_ten, ct.so_ao, ct.vi_tri, ct.chieu_cao_cm, ct.can_nang_kg, ct.quoc_tich,
                   ct.anh, ct.trang_thai
          ORDER BY so_tran DESC, ct.ho_ten"
    );
    $st->execute([$maDoi, (int) $mua['ma_mua_giai']]);
    return $st->fetchAll();
}

/** Thành tích mùa thường (view v_bang_xep_hang); NULL nếu đội chưa đấu trận nào trong mùa */
function tkhLayThanhTich(int $maDoi, int $maMuaGiai): ?array
{
    $st = ketNoi()->prepare('SELECT * FROM v_bang_xep_hang WHERE ma_doi = ? AND ma_mua_giai = ?');
    $st->execute([$maDoi, $maMuaGiai]);
    $r = $st->fetch();
    return ($r && (int) $r['thang'] + (int) $r['thua'] > 0) ? $r : null;
}

/** Các trận chưa đấu / hoãn tiếp theo của đội trong mùa (xếp theo thời gian, không so với ngày hôm nay) */
function tkhLayLichMua(int $maDoi, int $maMuaGiai, int $soTran = 5): array
{
    $soTran = max(1, $soTran);
    $st = ketNoi()->prepare(
        "SELECT td.*, dn.ten_doi AS ten_doi_nha, dk.ten_doi AS ten_doi_khach, s.ten_san
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
           LEFT JOIN san_thi_dau s ON s.ma_san = td.ma_san
          WHERE (td.ma_doi_nha = ? OR td.ma_doi_khach = ?) AND td.ma_mua_giai = ?
            AND td.trang_thai IN ('chua_dau', 'hoan')
          ORDER BY td.thoi_gian
          LIMIT $soTran"
    );
    $st->execute([$maDoi, $maDoi, $maMuaGiai]);
    return $st->fetchAll();
}

/** Các trận đã đấu gần nhất của đội trong mùa */
function tkhLayKetQuaMua(int $maDoi, int $maMuaGiai, int $soTran = 5): array
{
    $soTran = max(1, $soTran);
    $st = ketNoi()->prepare(
        "SELECT td.*, dn.ten_doi AS ten_doi_nha, dk.ten_doi AS ten_doi_khach
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
          WHERE (td.ma_doi_nha = ? OR td.ma_doi_khach = ?) AND td.ma_mua_giai = ?
            AND td.trang_thai = 'da_dau'
          ORDER BY td.thoi_gian DESC
          LIMIT $soTran"
    );
    $st->execute([$maDoi, $maDoi, $maMuaGiai]);
    return $st->fetchAll();
}
