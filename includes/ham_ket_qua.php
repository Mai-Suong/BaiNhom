<?php
/**
 * MODULE NHẬP KẾT QUẢ (TV4) - hàm truy vấn và nghiệp vụ.
 * Nhập điểm từng hiệp (line score), tự cộng ra tỉ số, ghi khán giả, chuyển trận sang "đã đấu".
 *
 * Bảng dùng: diem_hiep, tran_dau (ghi); mua_giai, doi_bong, san_thi_dau, chi_so_tran_dau (chỉ đọc)
 * Tên hàm có tiền tố kq để không trùng hàm của module khác.
 *
 * Quy tắc:
 *  - Chỉ nhập cho trận đã tới giờ đấu và không bị hoãn.
 *  - Bắt buộc 4 hiệp chính. Hiệp phụ (hiệp 5, 6...) chỉ có khi hòa sau hiệp trước đó, nhập liên tiếp.
 *  - Bóng rổ không có hòa: hòa sau hiệp 4 thì phải nhập hiệp phụ cho tới khi có đội thắng.
 *  - Tỉ số = tổng điểm các hiệp (không nhập tay, tránh lệch).
 *  - Hủy kết quả: trận về "chưa đấu", xóa điểm hiệp; chặn nếu đã có box score (TV5).
 */
require_once __DIR__ . '/../config/db.php';

const KQ_SO_HIEP_CHINH = 4;
const KQ_SO_HIEP_PHU_TOI_DA = 4;      // form cho nhập tối đa 4 hiệp phụ
const KQ_DIEM_TOI_DA_MOT_HIEP = 99;
const KQ_MOI_TRANG = 20;

function kqLayDsMua(): array
{
    return ketNoi()->query(
        'SELECT ma_mua_giai, ten_mua_giai, la_mua_hien_tai FROM mua_giai ORDER BY ngay_bat_dau DESC'
    )->fetchAll();
}

function kqChonMua(array $dsMua, int $maMua): ?array
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

function kqLayDsDoi(): array
{
    return ketNoi()->query('SELECT ma_doi, ten_doi FROM doi_bong ORDER BY ten_doi')->fetchAll();
}

/* =====================================================================
 * DANH SÁCH TRẬN
 * $loc: mua, doi, loai ('cho' = chưa có kết quả, đã tới giờ | 'da' = đã có kết quả | '' = tất cả)
 * ===================================================================== */

function kqDieuKien(array $loc): array
{
    $dk = ['td.ma_mua_giai = ?'];
    $ts = [(int) $loc['mua']];
    if (!empty($loc['doi'])) {
        $dk[] = '(td.ma_doi_nha = ? OR td.ma_doi_khach = ?)';
        $ts[] = (int) $loc['doi'];
        $ts[] = (int) $loc['doi'];
    }
    if ($loc['loai'] === 'cho') {
        $dk[] = "td.trang_thai = 'chua_dau' AND td.thoi_gian <= NOW()";
    } elseif ($loc['loai'] === 'da') {
        $dk[] = "td.trang_thai = 'da_dau'";
    }
    return [' WHERE ' . implode(' AND ', $dk), $ts];
}

function kqDemTran(array $loc): int
{
    [$where, $ts] = kqDieuKien($loc);
    $st = ketNoi()->prepare('SELECT COUNT(*) FROM tran_dau td' . $where);
    $st->execute($ts);
    return (int) $st->fetchColumn();
}

function kqLayDsTran(array $loc, int $trang): array
{
    [$where, $ts] = kqDieuKien($loc);
    $boQua = max(0, ($trang - 1) * KQ_MOI_TRANG);
    // 'cho': trận cũ nhất trước (cần nhập trước); còn lại: mới nhất trước
    $thuTu = $loc['loai'] === 'cho' ? 'td.thoi_gian, td.ma_tran' : 'td.thoi_gian DESC, td.ma_tran DESC';
    $st = ketNoi()->prepare(
        'SELECT td.*, dn.ten_viet_tat AS vt_nha, dn.ten_doi AS ten_doi_nha,
                dk.ten_viet_tat AS vt_khach, dk.ten_doi AS ten_doi_khach,
                (SELECT MAX(h.hiep) FROM diem_hiep h WHERE h.ma_tran = td.ma_tran) AS so_hiep
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach'
        . $where . " ORDER BY $thuTu LIMIT " . KQ_MOI_TRANG . ' OFFSET ' . $boQua
    );
    $st->execute($ts);
    return $st->fetchAll();
}

/* =====================================================================
 * MỘT TRẬN
 * ===================================================================== */

/** Trận kèm tên đội, sân, sức chứa; NULL nếu không có */
function kqLayTran(int $maTran): ?array
{
    $st = ketNoi()->prepare(
        'SELECT td.*, dn.ten_doi AS ten_doi_nha, dn.ten_viet_tat AS vt_nha,
                dk.ten_doi AS ten_doi_khach, dk.ten_viet_tat AS vt_khach,
                s.ten_san, s.suc_chua, m.ten_mua_giai
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
           JOIN mua_giai m  ON m.ma_mua_giai = td.ma_mua_giai
           LEFT JOIN san_thi_dau s ON s.ma_san = td.ma_san
          WHERE td.ma_tran = ?'
    );
    $st->execute([$maTran]);
    return $st->fetch() ?: null;
}

/** Điểm hiệp đã lưu: ['nha' => [hiep => diem], 'khach' => [...]] */
function kqLayDiemHiep(array $tran): array
{
    $st = ketNoi()->prepare('SELECT ma_doi, hiep, diem FROM diem_hiep WHERE ma_tran = ? ORDER BY hiep');
    $st->execute([$tran['ma_tran']]);
    $kq = ['nha' => [], 'khach' => []];
    foreach ($st->fetchAll() as $r) {
        $ben = (int) $r['ma_doi'] === (int) $tran['ma_doi_nha'] ? 'nha' : 'khach';
        $kq[$ben][(int) $r['hiep']] = (int) $r['diem'];
    }
    return $kq;
}

function kqCoBoxScore(int $maTran): bool
{
    $st = ketNoi()->prepare('SELECT COUNT(*) FROM chi_so_tran_dau WHERE ma_tran = ?');
    $st->execute([$maTran]);
    return (int) $st->fetchColumn() > 0;
}

/** Lý do không nhập được kết quả cho trận, NULL nếu nhập được */
function kqLyDoKhongNhap(array $tran): ?string
{
    if ($tran['trang_thai'] === 'hoan') {
        return 'Trận đang bị hoãn. Hãy đổi lịch và chuyển về "Chưa đấu" ở trang Lịch thi đấu trước.';
    }
    if ($tran['trang_thai'] !== 'da_dau' && strtotime($tran['thoi_gian']) > time()) {
        return 'Trận chưa tới giờ thi đấu (' . kqHienThoiGian($tran['thoi_gian']) . '), chưa nhập kết quả được.';
    }
    return null;
}

/* =====================================================================
 * KIỂM TRA + LƯU
 * ===================================================================== */

/**
 * Đọc điểm từ form: $p['diem']['nha'][hiep], $p['diem']['khach'][hiep], $p['khan_gia'].
 * Trả về [dữ liệu, lỗi]. Dữ liệu: ['nha' => [hiep => diem], 'khach' => [...], 'khan_gia' => int|null].
 * Lỗi: [khóa => thông báo], khóa 'hiep_N' cho từng hiệp, 'chung', 'khan_gia'.
 */
function kqDocVaKiemTra(array $p, array $tran): array
{
    $loi = [];
    $tho = ['nha' => (array) ($p['diem']['nha'] ?? []), 'khach' => (array) ($p['diem']['khach'] ?? [])];
    $d = ['nha' => [], 'khach' => [], 'khan_gia' => null];
    $soHiepToiDa = KQ_SO_HIEP_CHINH + KQ_SO_HIEP_PHU_TOI_DA;

    // Đọc từng ô
    $daNhap = []; // hiep => true nếu ít nhất 1 ô có nhập
    for ($h = 1; $h <= $soHiepToiDa; $h++) {
        foreach (['nha', 'khach'] as $ben) {
            $v = trim((string) ($tho[$ben][$h] ?? ''));
            if ($v === '') {
                continue;
            }
            $daNhap[$h] = true;
            if (!ctype_digit($v) || (int) $v > KQ_DIEM_TOI_DA_MOT_HIEP) {
                $loi["hiep_$h"] = 'Điểm mỗi hiệp là số nguyên từ 0 đến ' . KQ_DIEM_TOI_DA_MOT_HIEP . '.';
                continue;
            }
            $d[$ben][$h] = (int) $v;
        }
    }
    if ($loi) {
        return [$d, $loi];
    }

    // 4 hiệp chính bắt buộc đủ 2 đội
    for ($h = 1; $h <= KQ_SO_HIEP_CHINH; $h++) {
        if (!isset($d['nha'][$h], $d['khach'][$h])) {
            $loi["hiep_$h"] = "Nhập đủ điểm hiệp $h cho cả 2 đội.";
        }
    }
    if ($loi) {
        return [$d, $loi];
    }

    // Hiệp phụ: liên tiếp, đủ 2 đội, chỉ có khi hòa sau hiệp trước
    $tongNha = array_sum(array_slice($d['nha'], 0, KQ_SO_HIEP_CHINH, true));
    $tongKhach = array_sum(array_slice($d['khach'], 0, KQ_SO_HIEP_CHINH, true));
    for ($h = KQ_SO_HIEP_CHINH + 1; $h <= $soHiepToiDa; $h++) {
        $coHiep = isset($daNhap[$h]);
        $hoa = $tongNha === $tongKhach;
        if (!$coHiep) {
            // Không nhập hiệp này thì không được nhập hiệp sau
            for ($sau = $h + 1; $sau <= $soHiepToiDa; $sau++) {
                if (isset($daNhap[$sau])) {
                    $loi["hiep_$sau"] = 'Hiệp phụ phải nhập liên tiếp (chưa nhập hiệp phụ ' . ($h - KQ_SO_HIEP_CHINH) . ').';
                }
            }
            break;
        }
        if (!$hoa) {
            $loi["hiep_$h"] = 'Chỉ đá hiệp phụ khi 2 đội hòa sau hiệp trước. Hãy xóa điểm hiệp này.';
            break;
        }
        if (!isset($d['nha'][$h], $d['khach'][$h])) {
            $loi["hiep_$h"] = 'Nhập đủ điểm hiệp phụ ' . ($h - KQ_SO_HIEP_CHINH) . ' cho cả 2 đội.';
            break;
        }
        $tongNha += $d['nha'][$h];
        $tongKhach += $d['khach'][$h];
    }
    if (!$loi && $tongNha === $tongKhach) {
        $loi['chung'] = "Tỉ số đang hòa $tongKhach - $tongNha. Bóng rổ không có hòa, hãy nhập hiệp phụ.";
    }

    // Khán giả (không bắt buộc)
    $kg = trim((string) ($p['khan_gia'] ?? ''));
    if ($kg !== '') {
        if (!ctype_digit($kg) || (int) $kg > 100000) {
            $loi['khan_gia'] = 'Khán giả là số nguyên từ 0 đến 100.000.';
        } else {
            $d['khan_gia'] = (int) $kg;
        }
    }
    return [$d, $loi];
}

/** Lưu kết quả trong 1 transaction: ghi lại điểm hiệp, cập nhật tỉ số + khán giả + trạng thái */
function kqLuu(array $tran, array $d): void
{
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $maTran = (int) $tran['ma_tran'];
        $pdo->prepare('DELETE FROM diem_hiep WHERE ma_tran = ?')->execute([$maTran]);
        $st = $pdo->prepare('INSERT INTO diem_hiep (ma_tran, ma_doi, hiep, diem) VALUES (?, ?, ?, ?)');
        foreach (['nha' => $tran['ma_doi_nha'], 'khach' => $tran['ma_doi_khach']] as $ben => $maDoi) {
            foreach ($d[$ben] as $hiep => $diem) {
                $st->execute([$maTran, $maDoi, $hiep, $diem]);
            }
        }
        $pdo->prepare(
            "UPDATE tran_dau SET diem_doi_nha = ?, diem_doi_khach = ?, khan_gia = ?, trang_thai = 'da_dau'
              WHERE ma_tran = ?"
        )->execute([array_sum($d['nha']), array_sum($d['khach']), $d['khan_gia'], $maTran]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/** Hủy kết quả. Trả về NULL nếu được, hoặc câu báo lỗi. */
function kqHuy(int $maTran): ?string
{
    $tran = kqLayTran($maTran);
    if (!$tran) {
        return 'Không tìm thấy trận đấu.';
    }
    if ($tran['trang_thai'] !== 'da_dau') {
        return 'Trận này chưa có kết quả.';
    }
    if (kqCoBoxScore($maTran)) {
        return 'Trận đã có box score (TV5). Xóa box score trước rồi mới hủy kết quả được.';
    }
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM diem_hiep WHERE ma_tran = ?')->execute([$maTran]);
        $pdo->prepare(
            "UPDATE tran_dau SET diem_doi_nha = NULL, diem_doi_khach = NULL, khan_gia = NULL, trang_thai = 'chua_dau'
              WHERE ma_tran = ?"
        )->execute([$maTran]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return null;
}

/* =====================================================================
 * HIỂN THỊ
 * ===================================================================== */

/** Tên cột hiệp: 1-4 = Q1-Q4, 5 = OT, 6 = 2OT... */
function kqTenHiep(int $h): string
{
    if ($h <= KQ_SO_HIEP_CHINH) {
        return 'Q' . $h;
    }
    $phu = $h - KQ_SO_HIEP_CHINH;
    return ($phu === 1 ? '' : $phu) . 'OT';
}

function kqHienThoiGian(?string $tg): string
{
    if (!$tg) {
        return '';
    }
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $tg);
    return $d ? $d->format('d/m/Y H:i') : $tg;
}
