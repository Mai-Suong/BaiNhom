<?php
/**
 * MODULE HUẤN LUYỆN VIÊN (TV2) - hàm truy vấn và nghiệp vụ.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: huan_luyen_vien, nhiem_ky_hlv (ghi); doi_bong, trao_giai, mua_giai (chỉ đọc)
 *
 * Mỗi module của TV2 nằm trong 1 file riêng để các Pull Request độc lập, merge thứ tự nào cũng được.
 * Tên hàm có tiền tố riêng của từng module, không trùng hàm của TV3 (ham_cau_thu.php).
 */
require_once __DIR__ . '/tv2_chung.php';

/* =====================================================================
 * HUẤN LUYỆN VIÊN
 * ===================================================================== */

const THU_MUC_ANH_HLV = 'hlv';

/**
 * Danh sách HLV, tìm theo tên.
 * $locDoi: '' = tất cả, 'tu_do' = chưa có đội, số = mã đội.
 */
function hlvLayDanhSach(string $tuKhoa = '', string $locDoi = ''): array
{
    $sql = 'SELECT h.*, d.ten_doi, d.ten_viet_tat,
                   (SELECT COUNT(*) FROM nhiem_ky_hlv nk WHERE nk.ma_hlv = h.ma_hlv) AS so_nhiem_ky
              FROM huan_luyen_vien h
              LEFT JOIN doi_bong d ON d.ma_doi = h.ma_doi
             WHERE 1 = 1';
    $thamSo = [];
    if ($tuKhoa !== '') {
        $sql .= ' AND h.ho_ten LIKE ?';
        $thamSo[] = '%' . $tuKhoa . '%';
    }
    if ($locDoi === 'tu_do') {
        $sql .= ' AND h.ma_doi IS NULL';
    } elseif (ctype_digit($locDoi)) {
        $sql .= ' AND h.ma_doi = ?';
        $thamSo[] = (int) $locDoi;
    }
    $st = ketNoi()->prepare($sql . " ORDER BY d.ten_doi IS NULL, d.ten_doi, h.vai_tro = 'pho', h.ho_ten");
    $st->execute($thamSo);
    return $st->fetchAll();
}

/**
 * Danh sách HLV khi xem một mùa đã qua: đội / vai trò lấy từ nhiem_ky_hlv của mùa đó.
 * Mỗi HLV có 'ds_nhiem_ky' (các đội dẫn dắt trong mùa, theo thời gian; rỗng = không dẫn dắt).
 * $locDoi: '' tất cả, 'tu_do' = không dẫn dắt đội nào trong mùa, số = mã đội.
 */
function hlvLayDanhSachTheoMua(string $tuKhoa, string $locDoi, int $maMuaGiai): array
{
    $sql = 'SELECT h.*, nk.ma_doi AS nk_ma_doi, nk.vai_tro AS nk_vai_tro, nk.tam_quyen, nk.tu_ngay, nk.den_ngay,
                   d.ten_doi AS nk_ten_doi,
                   (SELECT COUNT(*) FROM nhiem_ky_hlv x WHERE x.ma_hlv = h.ma_hlv) AS so_nhiem_ky
              FROM huan_luyen_vien h
              LEFT JOIN nhiem_ky_hlv nk ON nk.ma_hlv = h.ma_hlv AND nk.ma_mua_giai = ?
              LEFT JOIN doi_bong d      ON d.ma_doi = nk.ma_doi';
    $thamSo = [$maMuaGiai];
    if ($tuKhoa !== '') {
        $sql .= ' WHERE h.ho_ten LIKE ?';
        $thamSo[] = '%' . $tuKhoa . '%';
    }
    $st = ketNoi()->prepare($sql . ' ORDER BY h.ma_hlv, nk.tu_ngay');
    $st->execute($thamSo);

    $ds = [];
    foreach ($st->fetchAll() as $r) {
        $ma = (int) $r['ma_hlv'];
        $ds[$ma] ??= $r + ['ds_nhiem_ky' => []];
        if ($r['nk_ma_doi'] !== null) {
            $ds[$ma]['ds_nhiem_ky'][] = [
                'ma_doi' => (int) $r['nk_ma_doi'], 'ten_doi' => $r['nk_ten_doi'], 'vai_tro' => $r['nk_vai_tro'],
                'tam_quyen' => (int) $r['tam_quyen'], 'tu_ngay' => $r['tu_ngay'], 'den_ngay' => $r['den_ngay'],
            ];
        }
    }
    $ds = array_filter($ds, function ($h) use ($locDoi) {
        if ($locDoi === 'tu_do') {
            return !$h['ds_nhiem_ky'];
        }
        return !ctype_digit($locDoi) || in_array((int) $locDoi, array_column($h['ds_nhiem_ky'], 'ma_doi'), true);
    });
    // Theo tên đội (HLV không dẫn dắt xuống cuối), HLV trưởng trước, rồi theo thời gian dẫn dắt, rồi tên
    $khoa = fn($h) => [$h['ds_nhiem_ky'] ? 0 : 1, $h['ds_nhiem_ky'][0]['ten_doi'] ?? '',
                       ($h['ds_nhiem_ky'][0]['vai_tro'] ?? '') === 'pho', $h['ds_nhiem_ky'][0]['tu_ngay'] ?? '', $h['ho_ten']];
    usort($ds, fn($a, $b) => $khoa($a) <=> $khoa($b));
    return $ds;
}

/** Danh sách đội cho ô lọc / ô chọn đội, kèm tên HLV trưởng hiện tại */
function hlvLayDoiLuaChon(): array
{
    return ketNoi()->query(
        "SELECT d.ma_doi, d.ten_doi,
                (SELECT h.ho_ten FROM huan_luyen_vien h
                  WHERE h.ma_doi = d.ma_doi AND h.vai_tro = 'truong' LIMIT 1) AS hlv_truong
           FROM doi_bong d
          ORDER BY d.ten_doi"
    )->fetchAll();
}

function hlvLay(int $maHlv): ?array
{
    $st = ketNoi()->prepare('SELECT * FROM huan_luyen_vien WHERE ma_hlv = ?');
    $st->execute([$maHlv]);
    return $st->fetch() ?: null;
}

/** Lấy dữ liệu từ form: cắt khoảng trắng, ô tùy chọn để trống thành NULL */
function hlvChuanHoa(array $p): array
{
    $hoacNull = function ($v) {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : $v;
    };
    return [
        'ho_ten'       => trim((string) ($p['ho_ten'] ?? '')),
        'ngay_sinh'    => $hoacNull($p['ngay_sinh'] ?? null),
        'quoc_tich'    => $hoacNull($p['quoc_tich'] ?? null),
        'ma_doi'       => $hoacNull($p['ma_doi'] ?? null),
        'vai_tro'      => (string) ($p['vai_tro'] ?? ''),
        'ngay_bat_dau' => $hoacNull($p['ngay_bat_dau'] ?? null),
    ];
}

/**
 * Trả về mảng lỗi [tên ô => thông báo]; rỗng = hợp lệ.
 * Quy tắc "mỗi đội chỉ một HLV trưởng" kiểm tra ở đây (CSDL không ràng buộc).
 * $boQuaMa: mã HLV đang sửa (không báo trùng với chính mình).
 */
function hlvKiemTra(array &$d, int $boQuaMa = 0): array
{
    $loi = [];
    $homNay = date('Y-m-d');

    if ($d['ho_ten'] === '') {
        $loi['ho_ten'] = 'Vui lòng nhập họ tên.';
    } elseif (mb_strlen($d['ho_ten']) > 100) {
        $loi['ho_ten'] = 'Họ tên tối đa 100 ký tự.';
    }

    if ($d['ngay_sinh'] !== null && (!tv2NgayHopLe($d['ngay_sinh']) || $d['ngay_sinh'] >= $homNay)) {
        $loi['ngay_sinh'] = 'Ngày sinh không hợp lệ.';
    }

    if ($d['quoc_tich'] !== null && mb_strlen($d['quoc_tich']) > 50) {
        $loi['quoc_tich'] = 'Quốc tịch tối đa 50 ký tự.';
    }

    if (!isset(VAI_TRO_HLV[$d['vai_tro']])) {
        $loi['vai_tro'] = 'Vui lòng chọn vai trò.';
    }

    $doi = null;
    if ($d['ma_doi'] !== null) {
        $doi = null;
        if (ctype_digit($d['ma_doi'])) {
            $st = ketNoi()->prepare('SELECT ma_doi, ten_doi FROM doi_bong WHERE ma_doi = ?');
            $st->execute([(int) $d['ma_doi']]);
            $doi = $st->fetch() ?: null;
        }
        if (!$doi) {
            $loi['ma_doi'] = 'Đội không hợp lệ.';
        } else {
            $d['ma_doi'] = (int) $d['ma_doi'];
        }
    }

    if ($doi && $d['vai_tro'] === 'truong') {
        $st = ketNoi()->prepare(
            "SELECT ho_ten FROM huan_luyen_vien WHERE ma_doi = ? AND vai_tro = 'truong' AND ma_hlv <> ?"
        );
        $st->execute([$d['ma_doi'], $boQuaMa]);
        if ($ten = $st->fetchColumn()) {
            $loi['vai_tro'] = "{$doi['ten_doi']} đã có HLV trưởng $ten. Mỗi đội chỉ một HLV trưởng: "
                            . 'hãy chuyển người cũ sang HLV phó hoặc cho rời đội trước.';
        }
    }

    if ($d['ngay_bat_dau'] !== null) {
        if (!tv2NgayHopLe($d['ngay_bat_dau'])) {
            $loi['ngay_bat_dau'] = 'Ngày bắt đầu không hợp lệ.';
        } elseif ($d['ngay_sinh'] !== null && !isset($loi['ngay_sinh']) && $d['ngay_bat_dau'] <= $d['ngay_sinh']) {
            $loi['ngay_bat_dau'] = 'Ngày bắt đầu phải sau ngày sinh.';
        }
    }
    return $loi;
}

function hlvThem(array $d, ?string $anh): int
{
    $pdo = ketNoi();
    $st = $pdo->prepare(
        'INSERT INTO huan_luyen_vien (ho_ten, ngay_sinh, quoc_tich, ma_doi, vai_tro, ngay_bat_dau, anh)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute([$d['ho_ten'], $d['ngay_sinh'], $d['quoc_tich'], $d['ma_doi'], $d['vai_tro'], $d['ngay_bat_dau'], $anh]);
    return (int) $pdo->lastInsertId();
}

/** Sửa HLV. $anhMoi = NULL nghĩa là giữ ảnh cũ. */
function hlvSua(int $maHlv, array $d, ?string $anhMoi): void
{
    $sql = 'UPDATE huan_luyen_vien SET ho_ten = ?, ngay_sinh = ?, quoc_tich = ?, ma_doi = ?, vai_tro = ?, ngay_bat_dau = ?';
    $thamSo = [$d['ho_ten'], $d['ngay_sinh'], $d['quoc_tich'], $d['ma_doi'], $d['vai_tro'], $d['ngay_bat_dau']];
    if ($anhMoi !== null) {
        $sql .= ', anh = ?';
        $thamSo[] = $anhMoi;
    }
    $thamSo[] = $maHlv;
    ketNoi()->prepare($sql . ' WHERE ma_hlv = ?')->execute($thamSo);
}

/**
 * Xóa HLV. Trả về NULL nếu xóa được, hoặc câu thông báo lý do bị chặn.
 * HLV đã nhận giải (trao_giai) hoặc đã có lịch sử dẫn dắt (nhiem_ky_hlv) thì không xóa được
 * (cả 2 bảng đặt ON DELETE RESTRICT).
 */
function hlvXoa(int $maHlv): ?string
{
    $hlv = hlvLay($maHlv);
    if (!$hlv) {
        return 'Không tìm thấy HLV.';
    }
    $pdo = ketNoi();
    $st = $pdo->prepare('SELECT COUNT(*) FROM trao_giai WHERE ma_hlv = ?');
    $st->execute([$maHlv]);
    if ($soGiai = (int) $st->fetchColumn()) {
        return "Không thể xóa HLV {$hlv['ho_ten']}: đã nhận $soGiai giải thưởng.";
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM nhiem_ky_hlv WHERE ma_hlv = ?');
    $st->execute([$maHlv]);
    if ($soNhiemKy = (int) $st->fetchColumn()) {
        return "Không thể xóa HLV {$hlv['ho_ten']}: đã có $soNhiemKy nhiệm kỳ trong lịch sử dẫn dắt.";
    }
    $pdo->prepare('DELETE FROM huan_luyen_vien WHERE ma_hlv = ?')->execute([$maHlv]);
    tv2XoaAnh($hlv['anh'], THU_MUC_ANH_HLV);
    return null;
}

/* =====================================================================
 * NHIỆM KỲ HLV THEO MÙA (bảng nhiem_ky_hlv) - để xem nhiều mùa giải.
 * huan_luyen_vien.ma_doi / vai_tro vẫn là "hiện tại" và sửa ở form HLV;
 * phần này chỉ quản lý lịch sử dẫn dắt từng mùa.
 * ===================================================================== */

/** Danh sách mùa giải (mới nhất trước) cho ô chọn */
function nkLayDsMua(): array
{
    return ketNoi()->query(
        'SELECT ma_mua_giai, ten_mua_giai, ngay_bat_dau, ngay_ket_thuc FROM mua_giai ORDER BY ngay_bat_dau DESC'
    )->fetchAll();
}

/** Các nhiệm kỳ của 1 HLV, mùa mới nhất trước */
function nkLayCuaHlv(int $maHlv): array
{
    $st = ketNoi()->prepare(
        'SELECT nk.*, mg.ten_mua_giai, mg.ngay_ket_thuc AS het_mua, d.ten_doi, d.ten_viet_tat
           FROM nhiem_ky_hlv nk
           JOIN mua_giai mg ON mg.ma_mua_giai = nk.ma_mua_giai
           JOIN doi_bong d  ON d.ma_doi = nk.ma_doi
          WHERE nk.ma_hlv = ?
          ORDER BY mg.ngay_bat_dau DESC, nk.tu_ngay DESC'
    );
    $st->execute([$maHlv]);
    return $st->fetchAll();
}

function nkLay(int $maNhiemKy): ?array
{
    $st = ketNoi()->prepare('SELECT * FROM nhiem_ky_hlv WHERE ma_nhiem_ky = ?');
    $st->execute([$maNhiemKy]);
    return $st->fetch() ?: null;
}

function nkChuanHoa(array $p): array
{
    $hoacNull = function ($v) {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : $v;
    };
    return [
        'ma_mua_giai' => $hoacNull($p['ma_mua_giai'] ?? null),
        'ma_doi'      => $hoacNull($p['ma_doi'] ?? null),
        'vai_tro'     => (string) ($p['vai_tro'] ?? ''),
        'tam_quyen'   => empty($p['tam_quyen']) ? 0 : 1,
        'tu_ngay'     => $hoacNull($p['tu_ngay'] ?? null),
        'den_ngay'    => $hoacNull($p['den_ngay'] ?? null),
    ];
}

/**
 * Kiểm tra 1 nhiệm kỳ của HLV $maHlv. Trả về mảng lỗi [tên ô => thông báo]; rỗng = hợp lệ.
 * Quy tắc:
 *  - Ngày nằm trong mùa giải; để trống "từ ngày" = ngày bắt đầu mùa; "đến ngày" trống = hết mùa.
 *  - Mỗi đội chỉ 1 HLV trưởng tại một thời điểm (không trùng thời gian với HLV trưởng khác của đội).
 *  - Một HLV không dẫn dắt 2 đội cùng lúc (không trùng thời gian với nhiệm kỳ khác của chính HLV đó).
 * $boQuaMa: mã nhiệm kỳ đang sửa (không so với chính nó).
 */
function nkKiemTra(int $maHlv, array &$d, int $boQuaMa = 0): array
{
    $loi = [];
    $pdo = ketNoi();

    $mua = null;
    if ($d['ma_mua_giai'] === null || !ctype_digit($d['ma_mua_giai'])) {
        $loi['ma_mua_giai'] = 'Vui lòng chọn mùa giải.';
    } else {
        $st = $pdo->prepare('SELECT * FROM mua_giai WHERE ma_mua_giai = ?');
        $st->execute([(int) $d['ma_mua_giai']]);
        $mua = $st->fetch() ?: null;
        if (!$mua) {
            $loi['ma_mua_giai'] = 'Mùa giải không hợp lệ.';
        } else {
            $d['ma_mua_giai'] = (int) $d['ma_mua_giai'];
        }
    }

    $doi = null;
    if ($d['ma_doi'] === null || !ctype_digit($d['ma_doi'])) {
        $loi['ma_doi'] = 'Vui lòng chọn đội.';
    } else {
        $st = $pdo->prepare('SELECT ma_doi, ten_doi FROM doi_bong WHERE ma_doi = ?');
        $st->execute([(int) $d['ma_doi']]);
        $doi = $st->fetch() ?: null;
        if (!$doi) {
            $loi['ma_doi'] = 'Đội không hợp lệ.';
        } else {
            $d['ma_doi'] = (int) $d['ma_doi'];
        }
    }

    if (!isset(VAI_TRO_HLV[$d['vai_tro']])) {
        $loi['vai_tro'] = 'Vui lòng chọn vai trò.';
    }

    if ($d['tu_ngay'] === null && $mua) {
        $d['tu_ngay'] = $mua['ngay_bat_dau'];
    }
    if ($d['tu_ngay'] === null || !tv2NgayHopLe($d['tu_ngay'])) {
        $loi['tu_ngay'] = 'Ngày bắt đầu không hợp lệ.';
    }
    if ($d['den_ngay'] !== null && !tv2NgayHopLe($d['den_ngay'])) {
        $loi['den_ngay'] = 'Ngày kết thúc không hợp lệ.';
    }
    if ($loi || !$mua || !$doi) {
        return $loi;
    }

    if ($d['den_ngay'] !== null && $d['den_ngay'] < $d['tu_ngay']) {
        $loi['den_ngay'] = 'Ngày kết thúc phải từ ngày bắt đầu trở đi.';
        return $loi;
    }
    $bd = $mua['ngay_bat_dau'];
    $kt = $mua['ngay_ket_thuc'];
    if ($d['tu_ngay'] < $bd || $d['tu_ngay'] > $kt) {
        $loi['tu_ngay'] = "Ngày bắt đầu phải nằm trong mùa {$mua['ten_mua_giai']} ("
                        . date('d/m/Y', strtotime($bd)) . ' - ' . date('d/m/Y', strtotime($kt)) . ').';
    }
    if ($d['den_ngay'] !== null && $d['den_ngay'] > $kt) {
        $loi['den_ngay'] = 'Ngày kết thúc không được sau ngày kết thúc mùa (' . date('d/m/Y', strtotime($kt)) . ').';
    }
    if ($loi) {
        return $loi;
    }

    // Khoảng [tu, den] của nhiệm kỳ mới; den trống = hết mùa
    $tu  = $d['tu_ngay'];
    $den = $d['den_ngay'] ?? $kt;
    $trungThoiGian = "nk.ma_mua_giai = ? AND nk.ma_nhiem_ky <> ?
                      AND nk.tu_ngay <= ? AND COALESCE(nk.den_ngay, mg.ngay_ket_thuc) >= ?";
    $hienNgay = fn($r) => date('d/m/Y', strtotime($r['tu_ngay'])) . ' - '
                        . ($r['den_ngay'] ? date('d/m/Y', strtotime($r['den_ngay'])) : 'hết mùa');

    if ($d['vai_tro'] === 'truong') {
        $st = $pdo->prepare(
            "SELECT nk.tu_ngay, nk.den_ngay, h.ho_ten
               FROM nhiem_ky_hlv nk
               JOIN mua_giai mg ON mg.ma_mua_giai = nk.ma_mua_giai
               JOIN huan_luyen_vien h ON h.ma_hlv = nk.ma_hlv
              WHERE nk.ma_doi = ? AND nk.vai_tro = 'truong' AND nk.ma_hlv <> ? AND $trungThoiGian
              LIMIT 1"
        );
        $st->execute([$d['ma_doi'], $maHlv, $d['ma_mua_giai'], $boQuaMa, $den, $tu]);
        if ($r = $st->fetch()) {
            $loi['tu_ngay'] = "Trùng thời gian với HLV trưởng {$r['ho_ten']} của {$doi['ten_doi']} ("
                            . $hienNgay($r) . '). Mỗi đội chỉ 1 HLV trưởng tại một thời điểm: '
                            . 'hãy cho nhiệm kỳ kia kết thúc trước ngày bắt đầu của nhiệm kỳ này.';
            return $loi;
        }
    }

    $st = $pdo->prepare(
        "SELECT nk.tu_ngay, nk.den_ngay, d.ten_doi
           FROM nhiem_ky_hlv nk
           JOIN mua_giai mg ON mg.ma_mua_giai = nk.ma_mua_giai
           JOIN doi_bong d  ON d.ma_doi = nk.ma_doi
          WHERE nk.ma_hlv = ? AND $trungThoiGian
          LIMIT 1"
    );
    $st->execute([$maHlv, $d['ma_mua_giai'], $boQuaMa, $den, $tu]);
    if ($r = $st->fetch()) {
        $loi['tu_ngay'] = "Trùng thời gian với nhiệm kỳ khác của HLV này ở {$r['ten_doi']} ("
                        . $hienNgay($r) . '). Một HLV không dẫn dắt 2 đội cùng lúc.';
    }
    return $loi;
}

function nkThem(int $maHlv, array $d): int
{
    $pdo = ketNoi();
    $pdo->prepare(
        'INSERT INTO nhiem_ky_hlv (ma_hlv, ma_doi, ma_mua_giai, vai_tro, tam_quyen, tu_ngay, den_ngay)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([$maHlv, $d['ma_doi'], $d['ma_mua_giai'], $d['vai_tro'], $d['tam_quyen'], $d['tu_ngay'], $d['den_ngay']]);
    return (int) $pdo->lastInsertId();
}

function nkSua(int $maNhiemKy, array $d): void
{
    ketNoi()->prepare(
        'UPDATE nhiem_ky_hlv SET ma_doi = ?, ma_mua_giai = ?, vai_tro = ?, tam_quyen = ?, tu_ngay = ?, den_ngay = ?
          WHERE ma_nhiem_ky = ?'
    )->execute([$d['ma_doi'], $d['ma_mua_giai'], $d['vai_tro'], $d['tam_quyen'], $d['tu_ngay'], $d['den_ngay'], $maNhiemKy]);
}

/** Xóa nhiệm kỳ của đúng HLV $maHlv (không xóa nhầm nhiệm kỳ của HLV khác). Trả về true nếu đã xóa. */
function nkXoa(int $maHlv, int $maNhiemKy): bool
{
    $st = ketNoi()->prepare('DELETE FROM nhiem_ky_hlv WHERE ma_nhiem_ky = ? AND ma_hlv = ?');
    $st->execute([$maNhiemKy, $maHlv]);
    return $st->rowCount() > 0;
}
