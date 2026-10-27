<?php
/**
 * MODULE MÙA GIẢI (TV4) - hàm truy vấn và nghiệp vụ.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: mua_giai (ghi); phan_bang, tran_dau, loat_playoff, trao_giai (chỉ đọc, để chặn xóa)
 * Tên hàm có tiền tố mg để không trùng hàm của module khác.
 *
 * Quy tắc:
 *  - Tên mùa dạng "2025-26": năm sau = năm trước + 1. Không trùng tên.
 *  - Ngày bắt đầu thuộc năm đầu, ngày kết thúc thuộc năm sau, kết thúc > bắt đầu.
 *  - Mùa đã có trận thì khoảng ngày mới phải chứa hết các trận.
 *  - Chỉ 1 mùa có la_mua_hien_tai = 1 (đặt mùa mới thì tự bỏ cờ mùa cũ).
 *  - Không xóa mùa hiện tại, mùa đã có trận / phân bảng / playoff / trao giải.
 */
require_once __DIR__ . '/../config/db.php';

/** Danh sách mùa (mới nhất trước), kèm số đội đã phân bảng và số trận */
function mgLayDanhSach(): array
{
    return ketNoi()->query(
        "SELECT m.*,
                (SELECT COUNT(*) FROM phan_bang pb WHERE pb.ma_mua_giai = m.ma_mua_giai) AS so_doi,
                (SELECT COUNT(*) FROM tran_dau td WHERE td.ma_mua_giai = m.ma_mua_giai) AS so_tran,
                (SELECT COUNT(*) FROM tran_dau td
                  WHERE td.ma_mua_giai = m.ma_mua_giai AND td.trang_thai = 'da_dau') AS so_tran_da_dau
           FROM mua_giai m
          ORDER BY m.ngay_bat_dau DESC"
    )->fetchAll();
}

function mgLay(int $maMua): ?array
{
    $st = ketNoi()->prepare('SELECT * FROM mua_giai WHERE ma_mua_giai = ?');
    $st->execute([$maMua]);
    return $st->fetch() ?: null;
}

/** Lấy dữ liệu từ form */
function mgChuanHoa(array $p): array
{
    return [
        'ten_mua_giai'    => trim((string) ($p['ten_mua_giai'] ?? '')),
        'ngay_bat_dau'    => trim((string) ($p['ngay_bat_dau'] ?? '')),
        'ngay_ket_thuc'   => trim((string) ($p['ngay_ket_thuc'] ?? '')),
        'la_mua_hien_tai' => empty($p['la_mua_hien_tai']) ? 0 : 1,
    ];
}

/** Đổi chuỗi 'Y-m-d' thành DateTime, NULL nếu sai định dạng hoặc ngày không có thật (VD 2025-02-30) */
function mgDocNgay(string $s): ?DateTime
{
    $ngay = DateTime::createFromFormat('!Y-m-d', $s);
    return ($ngay && $ngay->format('Y-m-d') === $s) ? $ngay : null;
}

/**
 * Kiểm tra dữ liệu. Trả về mảng lỗi [tên ô => thông báo]; rỗng = hợp lệ.
 * $maMua = mã mùa đang sửa (NULL khi thêm mới).
 */
function mgKiemTra(array $d, ?int $maMua = null): array
{
    $loi = [];
    $pdo = ketNoi();

    // Tên mùa
    $namDau = null;
    if ($d['ten_mua_giai'] === '') {
        $loi['ten_mua_giai'] = 'Vui lòng nhập tên mùa giải.';
    } elseif (!preg_match('/^(\d{4})-(\d{2})$/', $d['ten_mua_giai'], $m)) {
        $loi['ten_mua_giai'] = 'Tên mùa phải có dạng NNNN-NN, ví dụ 2025-26.';
    } elseif ((((int) $m[1] + 1) % 100) !== (int) $m[2]) {
        $namSau = sprintf('%02d', ((int) $m[1] + 1) % 100);
        $loi['ten_mua_giai'] = "Năm sau phải liền kề năm trước, ví dụ {$m[1]}-{$namSau}.";
    } else {
        $namDau = (int) $m[1];
        $st = $pdo->prepare('SELECT COUNT(*) FROM mua_giai WHERE ten_mua_giai = ? AND ma_mua_giai <> ?');
        $st->execute([$d['ten_mua_giai'], $maMua ?? 0]);
        if ((int) $st->fetchColumn() > 0) {
            $loi['ten_mua_giai'] = 'Mùa giải ' . $d['ten_mua_giai'] . ' đã có.';
        }
    }

    // Ngày
    $batDau  = mgDocNgay($d['ngay_bat_dau']);
    $ketThuc = mgDocNgay($d['ngay_ket_thuc']);
    if (!$batDau) {
        $loi['ngay_bat_dau'] = 'Vui lòng chọn ngày bắt đầu hợp lệ.';
    } elseif ($namDau !== null && (int) $batDau->format('Y') !== $namDau) {
        $loi['ngay_bat_dau'] = "Mùa {$d['ten_mua_giai']} phải bắt đầu trong năm $namDau.";
    }
    if (!$ketThuc) {
        $loi['ngay_ket_thuc'] = 'Vui lòng chọn ngày kết thúc hợp lệ.';
    } elseif ($batDau && $ketThuc <= $batDau) {
        $loi['ngay_ket_thuc'] = 'Ngày kết thúc phải sau ngày bắt đầu.';
    } elseif ($namDau !== null && (int) $ketThuc->format('Y') !== $namDau + 1) {
        $loi['ngay_ket_thuc'] = "Mùa {$d['ten_mua_giai']} phải kết thúc trong năm " . ($namDau + 1) . '.';
    }

    // Khi sửa: các trận đã có phải nằm trong khoảng ngày mới
    if ($maMua !== null && $batDau && $ketThuc && !isset($loi['ngay_bat_dau']) && !isset($loi['ngay_ket_thuc'])) {
        $st = $pdo->prepare('SELECT MIN(DATE(thoi_gian)) AS dau, MAX(DATE(thoi_gian)) AS cuoi
                               FROM tran_dau WHERE ma_mua_giai = ?');
        $st->execute([$maMua]);
        $tran = $st->fetch();
        if ($tran && $tran['dau'] !== null) {
            if ($tran['dau'] < $d['ngay_bat_dau']) {
                $loi['ngay_bat_dau'] = 'Mùa đã có trận ngày ' . mgHienNgay($tran['dau'])
                    . ', ngày bắt đầu không được sau ngày đó.';
            }
            if ($tran['cuoi'] > $d['ngay_ket_thuc']) {
                $loi['ngay_ket_thuc'] = 'Mùa đã có trận ngày ' . mgHienNgay($tran['cuoi'])
                    . ', ngày kết thúc không được trước ngày đó.';
            }
        }
    }

    // Khi sửa: không cho tắt cờ của mùa đang là mùa hiện tại
    if ($maMua !== null && $d['la_mua_hien_tai'] === 0) {
        $cu = mgLay($maMua);
        if ($cu && (int) $cu['la_mua_hien_tai'] === 1) {
            $loi['la_mua_hien_tai'] = 'Đây đang là mùa hiện tại. Muốn đổi, hãy đặt một mùa khác làm mùa hiện tại.';
        }
    }
    return $loi;
}

/** Bỏ cờ mùa hiện tại của mọi mùa (gọi bên trong transaction) */
function mgBoCoHienTai(PDO $pdo): void
{
    $pdo->exec('UPDATE mua_giai SET la_mua_hien_tai = 0 WHERE la_mua_hien_tai = 1');
}

function mgThem(array $d): int
{
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        if ($d['la_mua_hien_tai'] === 1) {
            mgBoCoHienTai($pdo);
        }
        $st = $pdo->prepare('INSERT INTO mua_giai (ten_mua_giai, ngay_bat_dau, ngay_ket_thuc, la_mua_hien_tai)
                             VALUES (?, ?, ?, ?)');
        $st->execute([$d['ten_mua_giai'], $d['ngay_bat_dau'], $d['ngay_ket_thuc'], $d['la_mua_hien_tai']]);
        $ma = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $ma;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

function mgSua(int $maMua, array $d): void
{
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        if ($d['la_mua_hien_tai'] === 1) {
            mgBoCoHienTai($pdo);
        }
        $st = $pdo->prepare('UPDATE mua_giai
                                SET ten_mua_giai = ?, ngay_bat_dau = ?, ngay_ket_thuc = ?, la_mua_hien_tai = ?
                              WHERE ma_mua_giai = ?');
        $st->execute([$d['ten_mua_giai'], $d['ngay_bat_dau'], $d['ngay_ket_thuc'], $d['la_mua_hien_tai'], $maMua]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/** Đặt 1 mùa làm mùa hiện tại. Trả về NULL nếu thành công, hoặc câu báo lỗi. */
function mgDatHienTai(int $maMua): ?string
{
    $mua = mgLay($maMua);
    if (!$mua) {
        return 'Không tìm thấy mùa giải.';
    }
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        mgBoCoHienTai($pdo);
        $pdo->prepare('UPDATE mua_giai SET la_mua_hien_tai = 1 WHERE ma_mua_giai = ?')->execute([$maMua]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return null;
}

/** Đếm số dòng của bảng $bang thuộc mùa $maMua (tên bảng chỉ lấy từ danh sách cố định bên dưới) */
function mgDem(string $bang, int $maMua): int
{
    $st = ketNoi()->prepare("SELECT COUNT(*) FROM $bang WHERE ma_mua_giai = ?");
    $st->execute([$maMua]);
    return (int) $st->fetchColumn();
}

/**
 * Xóa mùa. Trả về NULL nếu xóa được, hoặc câu thông báo lý do bị chặn.
 * CSDL đặt ON DELETE CASCADE cho phan_bang, loat_playoff, trao_giai: nếu không chặn ở đây,
 * xóa mùa sẽ âm thầm xóa luôn các dữ liệu đó.
 */
function mgXoa(int $maMua): ?string
{
    $mua = mgLay($maMua);
    if (!$mua) {
        return 'Không tìm thấy mùa giải.';
    }
    if ((int) $mua['la_mua_hien_tai'] === 1) {
        return 'Không thể xóa mùa ' . $mua['ten_mua_giai'] . ' vì đây là mùa hiện tại. Hãy đặt mùa khác làm mùa hiện tại trước.';
    }
    $lyDo = [];
    foreach (['tran_dau' => 'trận đấu', 'phan_bang' => 'đội đã phân bảng',
              'loat_playoff' => 'loạt playoff', 'trao_giai' => 'giải thưởng đã trao'] as $bang => $ten) {
        $so = mgDem($bang, $maMua);
        if ($so > 0) {
            $lyDo[] = "$so $ten";
        }
    }
    if ($lyDo) {
        return 'Không thể xóa mùa ' . $mua['ten_mua_giai'] . ': mùa này đã có ' . implode(', ', $lyDo) . '.';
    }
    ketNoi()->prepare('DELETE FROM mua_giai WHERE ma_mua_giai = ?')->execute([$maMua]);
    return null;
}

/** '2025-10-21' -> '21/10/2025' */
function mgHienNgay(?string $ngay): string
{
    if (!$ngay) {
        return '';
    }
    $d = DateTime::createFromFormat('!Y-m-d', substr($ngay, 0, 10));
    return $d ? $d->format('d/m/Y') : $ngay;
}
