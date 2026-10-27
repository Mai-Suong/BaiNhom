<?php
/**
 * TRANG KHÁCH: LỊCH THI ĐẤU + KẾT QUẢ (TV4) - hàm truy vấn (chỉ đọc).
 *
 * Bảng dùng (chỉ đọc): mua_giai, tran_dau, doi_bong, san_thi_dau, diem_hiep, phan_cong_trong_tai, trong_tai
 * Tên hàm có tiền tố ttd để không trùng hàm của module khác.
 */
require_once __DIR__ . '/../config/db.php';

const TTD_GIAI_DOAN  = ['thuong' => 'Mùa thường', 'playin' => 'Play-in', 'playoff' => 'Playoff'];
const TTD_KQ_MOI_TRANG = 10;

function ttdLayDsMua(): array
{
    return ketNoi()->query(
        'SELECT ma_mua_giai, ten_mua_giai, la_mua_hien_tai FROM mua_giai ORDER BY ngay_bat_dau DESC'
    )->fetchAll();
}

/** Mùa theo mã nếu có, không thì mùa hiện tại, không thì mùa mới nhất */
function ttdChonMua(array $dsMua, int $maMua): ?array
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

function ttdLayDsDoi(): array
{
    return ketNoi()->query('SELECT ma_doi, ten_doi FROM doi_bong ORDER BY ten_doi')->fetchAll();
}

/** Phần SELECT chung cho 1 trận: tên, mã, logo 2 đội và tên sân */
const TTD_SELECT_TRAN =
    'SELECT td.*, dn.ten_doi AS ten_doi_nha, dn.ten_viet_tat AS vt_nha, dn.logo AS logo_nha,
            dk.ten_doi AS ten_doi_khach, dk.ten_viet_tat AS vt_khach, dk.logo AS logo_khach,
            s.ten_san, s.thanh_pho
       FROM tran_dau td
       JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
       JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
       LEFT JOIN san_thi_dau s ON s.ma_san = td.ma_san';

/* =====================================================================
 * LỊCH THI ĐẤU (theo tháng)
 * ===================================================================== */

/** Các tháng 'YYYY-MM' có trận trong mùa (lọc theo đội nếu có) */
function ttdLayDsThang(int $maMua, int $maDoi): array
{
    $sql = "SELECT DISTINCT DATE_FORMAT(thoi_gian, '%Y-%m') AS thang FROM tran_dau WHERE ma_mua_giai = ?";
    $ts = [$maMua];
    if ($maDoi) {
        $sql .= ' AND (ma_doi_nha = ? OR ma_doi_khach = ?)';
        array_push($ts, $maDoi, $maDoi);
    }
    $st = ketNoi()->prepare($sql . ' ORDER BY thang');
    $st->execute($ts);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

/** Tháng mặc định: tháng của trận chưa đấu sớm nhất, không có thì tháng cuối cùng */
function ttdThangMacDinh(int $maMua, int $maDoi, array $dsThang): ?string
{
    if (!$dsThang) {
        return null;
    }
    $sql = "SELECT DATE_FORMAT(MIN(thoi_gian), '%Y-%m') FROM tran_dau WHERE ma_mua_giai = ? AND trang_thai = 'chua_dau'";
    $ts = [$maMua];
    if ($maDoi) {
        $sql .= ' AND (ma_doi_nha = ? OR ma_doi_khach = ?)';
        array_push($ts, $maDoi, $maDoi);
    }
    $st = ketNoi()->prepare($sql);
    $st->execute($ts);
    $thang = $st->fetchColumn();
    return ($thang && in_array($thang, $dsThang, true)) ? $thang : end($dsThang);
}

/** Các trận trong tháng, gom theo ngày: ['2025-10-21' => [trận...]] */
function ttdLayLichThang(int $maMua, int $maDoi, string $thang): array
{
    $sql = TTD_SELECT_TRAN . " WHERE td.ma_mua_giai = ? AND DATE_FORMAT(td.thoi_gian, '%Y-%m') = ?";
    $ts = [$maMua, $thang];
    if ($maDoi) {
        $sql .= ' AND (td.ma_doi_nha = ? OR td.ma_doi_khach = ?)';
        array_push($ts, $maDoi, $maDoi);
    }
    $st = ketNoi()->prepare($sql . ' ORDER BY td.thoi_gian, td.ma_tran');
    $st->execute($ts);
    $theoNgay = [];
    foreach ($st->fetchAll() as $t) {
        $theoNgay[substr($t['thoi_gian'], 0, 10)][] = $t;
    }
    return $theoNgay;
}

/* =====================================================================
 * KẾT QUẢ (trận đã đấu, mới nhất trước)
 * ===================================================================== */

function ttdDieuKienKetQua(int $maMua, int $maDoi): array
{
    $dk = " WHERE td.ma_mua_giai = ? AND td.trang_thai = 'da_dau'";
    $ts = [$maMua];
    if ($maDoi) {
        $dk .= ' AND (td.ma_doi_nha = ? OR td.ma_doi_khach = ?)';
        array_push($ts, $maDoi, $maDoi);
    }
    return [$dk, $ts];
}

function ttdDemKetQua(int $maMua, int $maDoi): int
{
    [$dk, $ts] = ttdDieuKienKetQua($maMua, $maDoi);
    $st = ketNoi()->prepare('SELECT COUNT(*) FROM tran_dau td' . $dk);
    $st->execute($ts);
    return (int) $st->fetchColumn();
}

function ttdLayKetQua(int $maMua, int $maDoi, int $trang): array
{
    [$dk, $ts] = ttdDieuKienKetQua($maMua, $maDoi);
    $boQua = max(0, ($trang - 1) * TTD_KQ_MOI_TRANG);
    $st = ketNoi()->prepare(TTD_SELECT_TRAN . $dk . ' ORDER BY td.thoi_gian DESC, td.ma_tran DESC LIMIT '
        . TTD_KQ_MOI_TRANG . ' OFFSET ' . $boQua);
    $st->execute($ts);
    return ttdGanDiemHiep($st->fetchAll());
}

/** 1 trận đã đấu (kèm điểm hiệp, trọng tài), NULL nếu không có */
function ttdLayMotKetQua(int $maTran): ?array
{
    $st = ketNoi()->prepare(TTD_SELECT_TRAN . " WHERE td.ma_tran = ? AND td.trang_thai = 'da_dau'");
    $st->execute([$maTran]);
    $tran = $st->fetch();
    if (!$tran) {
        return null;
    }
    $tran = ttdGanDiemHiep([$tran])[0];
    $st = ketNoi()->prepare(
        'SELECT t.ho_ten, t.so_ao FROM phan_cong_trong_tai pc
           JOIN trong_tai t ON t.ma_trong_tai = pc.ma_trong_tai
          WHERE pc.ma_tran = ? ORDER BY t.ho_ten'
    );
    $st->execute([$maTran]);
    $tran['trong_tai'] = $st->fetchAll();
    return $tran;
}

/** Gắn điểm hiệp vào từng trận: $t['hiep'] = [hiep => ['nha' => x, 'khach' => y]] (1 câu SQL cho cả danh sách) */
function ttdGanDiemHiep(array $dsTran): array
{
    if (!$dsTran) {
        return [];
    }
    $ma = array_map(fn($t) => (int) $t['ma_tran'], $dsTran);
    $st = ketNoi()->prepare('SELECT ma_tran, ma_doi, hiep, diem FROM diem_hiep WHERE ma_tran IN ('
        . implode(',', array_fill(0, count($ma), '?')) . ') ORDER BY hiep');
    $st->execute($ma);
    $theoTran = [];
    foreach ($st->fetchAll() as $r) {
        $theoTran[(int) $r['ma_tran']][] = $r;
    }
    foreach ($dsTran as &$t) {
        $t['hiep'] = [];
        foreach ($theoTran[(int) $t['ma_tran']] ?? [] as $r) {
            $ben = (int) $r['ma_doi'] === (int) $t['ma_doi_nha'] ? 'nha' : 'khach';
            $t['hiep'][(int) $r['hiep']][$ben] = (int) $r['diem'];
        }
    }
    unset($t);
    return $dsTran;
}

/* =====================================================================
 * HIỂN THỊ
 * ===================================================================== */

/** 1-4 = Q1-Q4, 5 = OT, 6 = 2OT... */
function ttdTenHiep(int $h): string
{
    return $h <= 4 ? 'Q' . $h : (($h - 4) === 1 ? '' : ($h - 4)) . 'OT';
}

const TTD_THU = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];

/** '2025-10-21' -> 'Thứ ba, 21/10/2025' */
function ttdHienNgay(string $ngay): string
{
    $d = DateTime::createFromFormat('!Y-m-d', substr($ngay, 0, 10));
    return $d ? TTD_THU[(int) $d->format('w')] . ', ' . $d->format('d/m/Y') : $ngay;
}

/** '2025-10' -> 'Tháng 10/2025' */
function ttdHienThang(string $thang): string
{
    [$nam, $t] = explode('-', $thang) + [null, null];
    return 'Tháng ' . (int) $t . '/' . $nam;
}

function ttdGio(string $tg): string
{
    return substr($tg, 11, 5);
}
