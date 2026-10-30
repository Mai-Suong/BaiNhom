<?php
/**
 * MODULE LỊCH THI ĐẤU (TV4) - hàm truy vấn và nghiệp vụ.
 * Thêm / sửa / xóa trận và phân công trọng tài. Tỉ số, điểm hiệp nhập ở module Nhập kết quả.
 *
 * Bảng dùng: tran_dau, phan_cong_trong_tai (ghi);
 *            mua_giai, doi_bong, san_thi_dau, phan_bang, trong_tai, loat_playoff,
 *            diem_hiep, chi_so_tran_dau (chỉ đọc)
 * Tên hàm có tiền tố ltd để không trùng hàm của module khác.
 *
 * Quy tắc:
 *  - Đội nhà khác đội khách; mỗi đội tối đa 1 trận / ngày.
 *  - Thời gian trận nằm trong khoảng ngày của mùa giải.
 *  - Trận mùa thường / play-in: cả 2 đội phải có phân bảng trong mùa.
 *  - Trận playoff: chọn loạt playoff của mùa (TV5 tạo), 2 đội phải đúng 2 đội của loạt,
 *    số trận trong loạt 1-7 không trùng.
 *  - Trọng tài: để trống hoặc đủ 3 người khác nhau; 1 trọng tài tối đa 1 trận / ngày.
 *  - Trận đã có kết quả / điểm hiệp / box score: không đổi mùa, đội, giai đoạn; không xóa.
 *    Trạng thái "đã đấu" chỉ đặt ở trang Nhập kết quả.
 */
require_once __DIR__ . '/../config/db.php';

const LTD_GIAI_DOAN  = ['thuong' => 'Mùa thường', 'playin' => 'Play-in', 'playoff' => 'Playoff'];
const LTD_TRANG_THAI = ['chua_dau' => 'Chưa đấu', 'da_dau' => 'Đã đấu', 'hoan' => 'Hoãn'];
const LTD_SO_TRONG_TAI = 3;
const LTD_MOI_TRANG    = 20;

/* =====================================================================
 * DANH MỤC CHO Ô CHỌN
 * ===================================================================== */

function ltdLayDsMua(): array
{
    return ketNoi()->query(
        'SELECT ma_mua_giai, ten_mua_giai, ngay_bat_dau, ngay_ket_thuc, la_mua_hien_tai
           FROM mua_giai ORDER BY ngay_bat_dau DESC'
    )->fetchAll();
}

/** Mùa theo mã nếu có trong danh sách, không thì mùa hiện tại, không thì mùa mới nhất */
function ltdChonMua(array $dsMua, int $maMua): ?array
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

function ltdLayDsDoi(): array
{
    return ketNoi()->query('SELECT ma_doi, ten_doi, ten_viet_tat, ma_san FROM doi_bong ORDER BY ten_doi')->fetchAll();
}

function ltdLayDsSan(): array
{
    return ketNoi()->query('SELECT ma_san, ten_san, thanh_pho FROM san_thi_dau ORDER BY ten_san')->fetchAll();
}

/**
 * Sân nhà của từng đội theo từng mùa: [ma_mua_giai => [ma_doi => ma_san]].
 * Lấy sân đội đá sân nhà nhiều trận nhất trong mùa đó (VD LAC mùa 2023-24 = Crypto.com Arena, mã 14);
 * mùa / đội chưa có trận thì dùng sân nhà hiện tại trong doi_bong. Dùng để tự chọn sân trong form.
 */
function ltdSanNhaTheoMua(array $dsMua, array $dsDoi): array
{
    $hienTai = [];
    foreach ($dsDoi as $doi) {
        $hienTai[(int) $doi['ma_doi']] = $doi['ma_san'] === null ? null : (int) $doi['ma_san'];
    }
    $kq = [];
    foreach ($dsMua as $m) {
        $kq[(int) $m['ma_mua_giai']] = $hienTai;
    }
    $rows = ketNoi()->query(
        'SELECT ma_mua_giai, ma_doi_nha, ma_san, COUNT(*) AS so
           FROM tran_dau WHERE ma_san IS NOT NULL
          GROUP BY ma_mua_giai, ma_doi_nha, ma_san
          ORDER BY ma_mua_giai, ma_doi_nha, so DESC, ma_san'
    )->fetchAll();
    $daCo = [];
    foreach ($rows as $r) {
        $k = $r['ma_mua_giai'] . '-' . $r['ma_doi_nha'];
        if (!isset($daCo[$k]) && isset($kq[(int) $r['ma_mua_giai']])) {
            $kq[(int) $r['ma_mua_giai']][(int) $r['ma_doi_nha']] = (int) $r['ma_san'];
            $daCo[$k] = true;
        }
    }
    return $kq;
}

function ltdLayDsTrongTai(): array
{
    return ketNoi()->query('SELECT ma_trong_tai, ho_ten, so_ao FROM trong_tai ORDER BY ho_ten')->fetchAll();
}

/** Các loạt playoff (bảng của TV5), kèm tên 2 đội */
function ltdLayDsLoat(): array
{
    return ketNoi()->query(
        'SELECT l.ma_loat, l.ma_mua_giai, l.vong, l.ma_doi_1, l.ma_doi_2,
                d1.ten_viet_tat AS vt_1, d2.ten_viet_tat AS vt_2
           FROM loat_playoff l
           JOIN doi_bong d1 ON d1.ma_doi = l.ma_doi_1
           JOIN doi_bong d2 ON d2.ma_doi = l.ma_doi_2
          ORDER BY l.ma_mua_giai DESC, l.ma_loat'
    )->fetchAll();
}

/* =====================================================================
 * DANH SÁCH TRẬN (lọc + phân trang)
 * ===================================================================== */

/** Tạo phần WHERE từ bộ lọc. $loc: mua, doi, trang_thai, giai_doan, ngay */
function ltdDieuKienLoc(array $loc): array
{
    $dk = ['td.ma_mua_giai = ?'];
    $ts = [(int) $loc['mua']];
    if (!empty($loc['doi'])) {
        $dk[] = '(td.ma_doi_nha = ? OR td.ma_doi_khach = ?)';
        $ts[] = (int) $loc['doi'];
        $ts[] = (int) $loc['doi'];
    }
    if (isset(LTD_TRANG_THAI[$loc['trang_thai'] ?? ''])) {
        $dk[] = 'td.trang_thai = ?';
        $ts[] = $loc['trang_thai'];
    }
    if (isset(LTD_GIAI_DOAN[$loc['giai_doan'] ?? ''])) {
        $dk[] = 'td.giai_doan = ?';
        $ts[] = $loc['giai_doan'];
    }
    if (!empty($loc['ngay']) && ltdDocNgay($loc['ngay'])) {
        $dk[] = 'DATE(td.thoi_gian) = ?';
        $ts[] = $loc['ngay'];
    }
    return [' WHERE ' . implode(' AND ', $dk), $ts];
}

function ltdDemTran(array $loc): int
{
    [$where, $ts] = ltdDieuKienLoc($loc);
    $st = ketNoi()->prepare('SELECT COUNT(*) FROM tran_dau td' . $where);
    $st->execute($ts);
    return (int) $st->fetchColumn();
}

function ltdLayDsTran(array $loc, int $trang): array
{
    [$where, $ts] = ltdDieuKienLoc($loc);
    $boQua = max(0, ($trang - 1) * LTD_MOI_TRANG);
    $st = ketNoi()->prepare(
        'SELECT td.*, dn.ten_doi AS ten_doi_nha, dn.ten_viet_tat AS vt_nha,
                dk.ten_doi AS ten_doi_khach, dk.ten_viet_tat AS vt_khach, s.ten_san,
                (SELECT COUNT(*) FROM phan_cong_trong_tai pc WHERE pc.ma_tran = td.ma_tran) AS so_trong_tai
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
           LEFT JOIN san_thi_dau s ON s.ma_san = td.ma_san'
        . $where . ' ORDER BY td.thoi_gian, td.ma_tran LIMIT ' . LTD_MOI_TRANG . ' OFFSET ' . $boQua
    );
    $st->execute($ts);
    return $st->fetchAll();
}

/* =====================================================================
 * MỘT TRẬN
 * ===================================================================== */

function ltdLay(int $maTran): ?array
{
    $st = ketNoi()->prepare('SELECT * FROM tran_dau WHERE ma_tran = ?');
    $st->execute([$maTran]);
    return $st->fetch() ?: null;
}

/** Mã các trọng tài của trận */
function ltdLayTrongTaiCuaTran(int $maTran): array
{
    $st = ketNoi()->prepare('SELECT ma_trong_tai FROM phan_cong_trong_tai WHERE ma_tran = ? ORDER BY ma_trong_tai');
    $st->execute([$maTran]);
    return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

/** Trận đã "khóa" (không đổi mùa / đội / giai đoạn, không xóa): đã đấu hoặc đã có điểm hiệp / box score */
function ltdDaKhoa(int $maTran, string $trangThai): bool
{
    if ($trangThai === 'da_dau') {
        return true;
    }
    $pdo = ketNoi();
    foreach (['diem_hiep', 'chi_so_tran_dau'] as $bang) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM $bang WHERE ma_tran = ?");
        $st->execute([$maTran]);
        if ((int) $st->fetchColumn() > 0) {
            return true;
        }
    }
    return false;
}

/* =====================================================================
 * KIỂM TRA + LƯU
 * ===================================================================== */

function ltdDocNgay(string $s): ?DateTime
{
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $d : null;
}

/** '2025-10-21T19:30' (ô datetime-local) -> '2025-10-21 19:30:00', NULL nếu sai */
function ltdDocThoiGian(string $s): ?string
{
    foreach (['!Y-m-d\TH:i', '!Y-m-d H:i', '!Y-m-d\TH:i:s', '!Y-m-d H:i:s'] as $dang) {
        $d = DateTime::createFromFormat($dang, $s);
        $loiDoc = DateTime::getLastErrors();
        if ($d && (!$loiDoc || ($loiDoc['warning_count'] === 0 && $loiDoc['error_count'] === 0))) {
            return $d->format('Y-m-d H:i:00');
        }
    }
    return null;
}

/** Lấy dữ liệu từ form */
function ltdChuanHoa(array $p): array
{
    $so = fn($k) => ctype_digit(trim((string) ($p[$k] ?? ''))) ? (int) $p[$k] : null;
    $trongTai = [];
    foreach ((array) ($p['trong_tai'] ?? []) as $v) {
        $v = trim((string) $v);
        if ($v !== '') {
            $trongTai[] = ctype_digit($v) ? (int) $v : -1;
        }
    }
    return [
        'ma_mua_giai'        => $so('ma_mua_giai'),
        'ma_doi_nha'         => $so('ma_doi_nha'),
        'ma_doi_khach'       => $so('ma_doi_khach'),
        'ma_san'             => $so('ma_san'),
        'thoi_gian'          => trim((string) ($p['thoi_gian'] ?? '')),
        'giai_doan'          => (string) ($p['giai_doan'] ?? 'thuong'),
        'ma_loat'            => $so('ma_loat'),
        'tran_so_trong_loat' => $so('tran_so_trong_loat'),
        'trang_thai'         => (string) ($p['trang_thai'] ?? 'chua_dau'),
        'trong_tai'          => $trongTai,
    ];
}

/** Có giá trị $ma trong cột $cot của danh sách $ds không */
function ltdCo(array $ds, string $cot, ?int $ma): bool
{
    if ($ma === null) {
        return false;
    }
    foreach ($ds as $r) {
        if ((int) $r[$cot] === $ma) {
            return true;
        }
    }
    return false;
}

/**
 * Kiểm tra dữ liệu trận. $cu = trận cũ khi sửa (NULL khi thêm).
 * Trả về mảng lỗi [tên ô => thông báo]; rỗng = hợp lệ. Chuẩn hóa $d['thoi_gian'] khi hợp lệ.
 */
function ltdKiemTra(array &$d, ?array $cu = null): array
{
    $loi = [];
    $pdo = ketNoi();
    $maTran = $cu ? (int) $cu['ma_tran'] : 0;
    $khoa = $cu && ltdDaKhoa($maTran, $cu['trang_thai']);

    if ($khoa) {
        // Trận đã khóa: giữ nguyên mùa, đội, giai đoạn, loạt, trạng thái
        foreach (['ma_mua_giai', 'ma_doi_nha', 'ma_doi_khach', 'giai_doan', 'ma_loat', 'tran_so_trong_loat', 'trang_thai'] as $k) {
            $d[$k] = $cu[$k] === null ? null : (is_numeric($cu[$k]) ? (int) $cu[$k] : $cu[$k]);
        }
    }

    // Mùa
    $st = $pdo->prepare('SELECT * FROM mua_giai WHERE ma_mua_giai = ?');
    $st->execute([$d['ma_mua_giai'] ?? 0]);
    $mua = $st->fetch();
    if (!$mua) {
        $loi['ma_mua_giai'] = 'Vui lòng chọn mùa giải.';
    }

    // Đội
    $dsDoi = ltdLayDsDoi();
    if (!ltdCo($dsDoi, 'ma_doi', $d['ma_doi_nha'])) {
        $loi['ma_doi_nha'] = 'Vui lòng chọn đội nhà.';
    }
    if (!ltdCo($dsDoi, 'ma_doi', $d['ma_doi_khach'])) {
        $loi['ma_doi_khach'] = 'Vui lòng chọn đội khách.';
    } elseif ($d['ma_doi_nha'] === $d['ma_doi_khach']) {
        $loi['ma_doi_khach'] = 'Đội khách phải khác đội nhà.';
    }

    // Sân (không bắt buộc)
    if ($d['ma_san'] !== null && !ltdCo(ltdLayDsSan(), 'ma_san', $d['ma_san'])) {
        $loi['ma_san'] = 'Sân không hợp lệ.';
    }

    // Giai đoạn, trạng thái
    if (!isset(LTD_GIAI_DOAN[$d['giai_doan']])) {
        $loi['giai_doan'] = 'Giai đoạn không hợp lệ.';
    }
    if (!$khoa && !in_array($d['trang_thai'], ['chua_dau', 'hoan'], true)) {
        $loi['trang_thai'] = 'Chỉ chọn "Chưa đấu" hoặc "Hoãn". Kết quả nhập ở trang Nhập kết quả.';
    }

    // Thời gian
    $tg = ltdDocThoiGian($d['thoi_gian']);
    if ($tg === null) {
        $loi['thoi_gian'] = 'Vui lòng chọn ngày giờ thi đấu hợp lệ.';
    } else {
        $d['thoi_gian'] = $tg;
        $ngay = substr($tg, 0, 10);
        if ($mua && ($ngay < $mua['ngay_bat_dau'] || $ngay > $mua['ngay_ket_thuc'])) {
            $loi['thoi_gian'] = 'Ngày thi đấu phải trong mùa ' . $mua['ten_mua_giai'] . ' ('
                . ltdHienNgay($mua['ngay_bat_dau']) . ' - ' . ltdHienNgay($mua['ngay_ket_thuc']) . ').';
        }
    }

    $doiHopLe = !isset($loi['ma_doi_nha']) && !isset($loi['ma_doi_khach']);

    // Phân bảng (mùa thường, play-in)
    if ($mua && $doiHopLe && !$khoa && in_array($d['giai_doan'], ['thuong', 'playin'], true)) {
        $st = $pdo->prepare('SELECT ma_doi FROM phan_bang WHERE ma_mua_giai = ? AND ma_doi IN (?, ?)');
        $st->execute([$mua['ma_mua_giai'], $d['ma_doi_nha'], $d['ma_doi_khach']]);
        $coPhanBang = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        foreach (['ma_doi_nha' => 'Đội nhà', 'ma_doi_khach' => 'Đội khách'] as $k => $ten) {
            if (!in_array($d[$k], $coPhanBang, true)) {
                $loi[$k] = "$ten chưa được phân bảng trong mùa {$mua['ten_mua_giai']}.";
            }
        }
    }

    // Playoff
    if ($d['giai_doan'] === 'playoff') {
        $loat = null;
        foreach (ltdLayDsLoat() as $l) {
            if ((int) $l['ma_loat'] === $d['ma_loat']) {
                $loat = $l;
            }
        }
        if (!$loat || ($mua && (int) $loat['ma_mua_giai'] !== (int) $mua['ma_mua_giai'])) {
            $loi['ma_loat'] = 'Trận playoff phải chọn loạt playoff của mùa này (TV5 tạo ở trang Playoff).';
        } elseif ($doiHopLe) {
            $haiDoi = [(int) $loat['ma_doi_1'], (int) $loat['ma_doi_2']];
            sort($haiDoi);
            $chon = [$d['ma_doi_nha'], $d['ma_doi_khach']];
            sort($chon);
            if ($haiDoi !== $chon) {
                $loi['ma_doi_nha'] = "Trận playoff phải là 2 đội của loạt ({$loat['vt_1']} và {$loat['vt_2']}).";
            }
        }
        if ($d['tran_so_trong_loat'] === null || $d['tran_so_trong_loat'] < 1 || $d['tran_so_trong_loat'] > 7) {
            $loi['tran_so_trong_loat'] = 'Số trận trong loạt phải từ 1 đến 7.';
        } elseif (!isset($loi['ma_loat'])) {
            $st = $pdo->prepare('SELECT COUNT(*) FROM tran_dau WHERE ma_loat = ? AND tran_so_trong_loat = ? AND ma_tran <> ?');
            $st->execute([$d['ma_loat'], $d['tran_so_trong_loat'], $maTran]);
            if ((int) $st->fetchColumn() > 0) {
                $loi['tran_so_trong_loat'] = 'Loạt này đã có trận số ' . $d['tran_so_trong_loat'] . '.';
            }
        }
    } else {
        $d['ma_loat'] = null;
        $d['tran_so_trong_loat'] = null;
    }

    // Mỗi đội tối đa 1 trận / ngày
    if ($doiHopLe && $tg !== null) {
        $st = $pdo->prepare(
            'SELECT td.ma_tran, dn.ten_viet_tat AS vt_nha, dk.ten_viet_tat AS vt_khach
               FROM tran_dau td
               JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
               JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
              WHERE DATE(td.thoi_gian) = ? AND td.ma_tran <> ? AND td.trang_thai <> ?
                AND (td.ma_doi_nha IN (?, ?) OR td.ma_doi_khach IN (?, ?))
              LIMIT 1'
        );
        $st->execute([substr($tg, 0, 10), $maTran, 'hoan',
                      $d['ma_doi_nha'], $d['ma_doi_khach'], $d['ma_doi_nha'], $d['ma_doi_khach']]);
        if ($trung = $st->fetch()) {
            $loi['thoi_gian'] = 'Một trong hai đội đã có trận trong ngày này (' . $trung['vt_khach'] . ' @ ' . $trung['vt_nha'] . ').';
        }
    }

    // Trọng tài: để trống hoặc đủ 3 người khác nhau, mỗi người tối đa 1 trận / ngày
    $tt = $d['trong_tai'];
    if ($tt) {
        $dsTt = ltdLayDsTrongTai();
        if (count($tt) !== LTD_SO_TRONG_TAI) {
            $loi['trong_tai'] = 'Chọn đủ ' . LTD_SO_TRONG_TAI . ' trọng tài hoặc để trống cả ' . LTD_SO_TRONG_TAI . ' ô.';
        } elseif (count(array_unique($tt)) !== count($tt)) {
            $loi['trong_tai'] = 'Ba trọng tài phải là 3 người khác nhau.';
        } else {
            foreach ($tt as $ma) {
                if (!ltdCo($dsTt, 'ma_trong_tai', $ma)) {
                    $loi['trong_tai'] = 'Trọng tài không hợp lệ.';
                    break;
                }
            }
        }
        if (!isset($loi['trong_tai']) && $tg !== null) {
            $dauHoi = implode(',', array_fill(0, count($tt), '?'));
            $st = $pdo->prepare(
                "SELECT t.ho_ten FROM phan_cong_trong_tai pc
                   JOIN tran_dau td ON td.ma_tran = pc.ma_tran
                   JOIN trong_tai t ON t.ma_trong_tai = pc.ma_trong_tai
                  WHERE DATE(td.thoi_gian) = ? AND td.ma_tran <> ? AND td.trang_thai <> 'hoan'
                    AND pc.ma_trong_tai IN ($dauHoi)
                  LIMIT 1"
            );
            $st->execute(array_merge([substr($tg, 0, 10), $maTran], $tt));
            if ($ten = $st->fetchColumn()) {
                $loi['trong_tai'] = "Trọng tài $ten đã được phân công một trận khác trong ngày này.";
            }
        }
    }
    return $loi;
}

/** Ghi lại danh sách trọng tài của trận (gọi trong transaction) */
function ltdGhiTrongTai(PDO $pdo, int $maTran, array $tt): void
{
    $pdo->prepare('DELETE FROM phan_cong_trong_tai WHERE ma_tran = ?')->execute([$maTran]);
    $st = $pdo->prepare('INSERT INTO phan_cong_trong_tai (ma_tran, ma_trong_tai) VALUES (?, ?)');
    foreach ($tt as $ma) {
        $st->execute([$maTran, $ma]);
    }
}

function ltdThem(array $d): int
{
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'INSERT INTO tran_dau (ma_mua_giai, ma_doi_nha, ma_doi_khach, ma_san, thoi_gian,
                                   giai_doan, ma_loat, tran_so_trong_loat, trang_thai)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$d['ma_mua_giai'], $d['ma_doi_nha'], $d['ma_doi_khach'], $d['ma_san'], $d['thoi_gian'],
                      $d['giai_doan'], $d['ma_loat'], $d['tran_so_trong_loat'], $d['trang_thai']]);
        $maTran = (int) $pdo->lastInsertId();
        ltdGhiTrongTai($pdo, $maTran, $d['trong_tai']);
        $pdo->commit();
        return $maTran;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

function ltdSua(int $maTran, array $d): void
{
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'UPDATE tran_dau SET ma_mua_giai = ?, ma_doi_nha = ?, ma_doi_khach = ?, ma_san = ?, thoi_gian = ?,
                                 giai_doan = ?, ma_loat = ?, tran_so_trong_loat = ?, trang_thai = ?
              WHERE ma_tran = ?'
        );
        $st->execute([$d['ma_mua_giai'], $d['ma_doi_nha'], $d['ma_doi_khach'], $d['ma_san'], $d['thoi_gian'],
                      $d['giai_doan'], $d['ma_loat'], $d['tran_so_trong_loat'], $d['trang_thai'], $maTran]);
        ltdGhiTrongTai($pdo, $maTran, $d['trong_tai']);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/**
 * Xóa trận. Trả về NULL nếu xóa được, hoặc câu báo lý do bị chặn.
 * CSDL đặt CASCADE cho diem_hiep, chi_so_tran_dau: nếu không chặn, xóa trận sẽ mất luôn kết quả và box score.
 */
function ltdXoa(int $maTran): ?string
{
    $tran = ltdLay($maTran);
    if (!$tran) {
        return 'Không tìm thấy trận đấu.';
    }
    if (ltdDaKhoa($maTran, $tran['trang_thai'])) {
        return 'Không thể xóa trận đã có kết quả, điểm hiệp hoặc box score.';
    }
    ketNoi()->prepare('DELETE FROM tran_dau WHERE ma_tran = ?')->execute([$maTran]); // phân công trọng tài xóa theo (CASCADE)
    return null;
}

/* =====================================================================
 * HIỂN THỊ
 * ===================================================================== */

function ltdHienNgay(?string $ngay): string
{
    if (!$ngay) {
        return '';
    }
    $d = DateTime::createFromFormat('!Y-m-d', substr($ngay, 0, 10));
    return $d ? $d->format('d/m/Y') : $ngay;
}

/** '2025-10-21 19:30:00' -> '21/10/2025 19:30' */
function ltdHienThoiGian(?string $tg): string
{
    if (!$tg) {
        return '';
    }
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $tg);
    return $d ? $d->format('d/m/Y H:i') : $tg;
}

/** Giá trị cho ô datetime-local: '2025-10-21 19:30:00' -> '2025-10-21T19:30' */
function ltdGiaTriO(?string $tg): string
{
    if (!$tg) {
        return '';
    }
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $tg);
    return $d ? $d->format('Y-m-d\TH:i') : $tg;
}

function ltdMauTrangThai(string $tt): string
{
    return ['chua_dau' => 'secondary', 'da_dau' => 'success', 'hoan' => 'warning'][$tt] ?? 'light';
}
