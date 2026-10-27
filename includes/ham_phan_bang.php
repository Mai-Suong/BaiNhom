<?php
/**
 * MODULE PHÂN BẢNG (TV4) - hàm truy vấn và nghiệp vụ.
 * Xếp mỗi đội vào 1 khu vực (division) cho từng mùa. Hội nghị Đông/Tây suy ra từ khu vực.
 *
 * Bảng dùng: phan_bang (ghi); mua_giai, doi_bong, khu_vuc, hoi_nghi, tran_dau (chỉ đọc)
 * Tên hàm có tiền tố pb để không trùng hàm của module khác.
 *
 * Quy tắc:
 *  - Mỗi đội thuộc tối đa 1 khu vực trong 1 mùa (CSDL: uq_mua_doi).
 *  - Mỗi khu vực tối đa PB_TOI_DA_MOI_KHU_VUC đội (NBA: 6 khu vực x 5 đội).
 *  - Đội đã có trận mùa thường trong mùa thì không được bỏ khỏi phân bảng,
 *    vì bảng xếp hạng (view v_bang_xep_hang) chỉ tính đội có phân bảng.
 *  - Sao chép phân bảng từ mùa khác chỉ khi mùa đích chưa có phân bảng.
 */
require_once __DIR__ . '/../config/db.php';

const PB_TOI_DA_MOI_KHU_VUC = 5;

/** Danh sách mùa giải (mới nhất trước), kèm số đội đã phân bảng */
function pbLayDsMua(): array
{
    return ketNoi()->query(
        'SELECT m.ma_mua_giai, m.ten_mua_giai, m.la_mua_hien_tai,
                (SELECT COUNT(*) FROM phan_bang pb WHERE pb.ma_mua_giai = m.ma_mua_giai) AS so_doi
           FROM mua_giai m
          ORDER BY m.ngay_bat_dau DESC'
    )->fetchAll();
}

/** Mùa được chọn: theo $maMua nếu có, không thì mùa hiện tại, không thì mùa mới nhất */
function pbChonMua(array $dsMua, int $maMua): ?array
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

/** 6 khu vực kèm tên hội nghị, xếp theo hội nghị rồi khu vực */
function pbLayDsKhuVuc(): array
{
    return ketNoi()->query(
        'SELECT kv.ma_khu_vuc, kv.ten_khu_vuc, hn.ma_hoi_nghi, hn.ten_hoi_nghi
           FROM khu_vuc kv
           JOIN hoi_nghi hn ON hn.ma_hoi_nghi = kv.ma_hoi_nghi
          ORDER BY hn.ma_hoi_nghi, kv.ma_khu_vuc'
    )->fetchAll();
}

/**
 * Tất cả đội, kèm khu vực của đội trong mùa $maMua (NULL = chưa phân bảng)
 * và số trận mùa thường của đội trong mùa đó.
 */
function pbLayDsDoi(int $maMua): array
{
    $st = ketNoi()->prepare(
        "SELECT d.ma_doi, d.ten_doi, d.ten_viet_tat, pb.ma_khu_vuc,
                (SELECT COUNT(*) FROM tran_dau td
                  WHERE td.ma_mua_giai = ? AND td.giai_doan = 'thuong'
                    AND (td.ma_doi_nha = d.ma_doi OR td.ma_doi_khach = d.ma_doi)) AS so_tran
           FROM doi_bong d
           LEFT JOIN phan_bang pb ON pb.ma_doi = d.ma_doi AND pb.ma_mua_giai = ?
          ORDER BY d.ten_doi"
    );
    $st->execute([$maMua, $maMua]);
    return $st->fetchAll();
}

/**
 * Kiểm tra lựa chọn từ form. $chon = [ma_doi => ma_khu_vuc hoặc ''].
 * Trả về [mảng gán hợp lệ [ma_doi => ma_khu_vuc|null], mảng lỗi [chuỗi]].
 */
function pbKiemTra(int $maMua, array $chon, array $dsDoi, array $dsKhuVuc): array
{
    $tenKhuVuc = array_column($dsKhuVuc, 'ten_khu_vuc', 'ma_khu_vuc');
    $gan = [];
    $loi = [];
    $dem = [];
    foreach ($dsDoi as $doi) {
        $maDoi = (int) $doi['ma_doi'];
        $v = trim((string) ($chon[$maDoi] ?? ''));
        if ($v === '') {
            $gan[$maDoi] = null;
            if ((int) $doi['so_tran'] > 0) {
                $loi[] = "{$doi['ten_doi']} đã có {$doi['so_tran']} trận mùa thường trong mùa này, không được bỏ khỏi phân bảng.";
            }
            continue;
        }
        if (!ctype_digit($v) || !isset($tenKhuVuc[(int) $v])) {
            $loi[] = "Khu vực chọn cho {$doi['ten_doi']} không hợp lệ.";
            continue;
        }
        $gan[$maDoi] = (int) $v;
        $dem[(int) $v] = ($dem[(int) $v] ?? 0) + 1;
    }
    foreach ($dem as $maKv => $so) {
        if ($so > PB_TOI_DA_MOI_KHU_VUC) {
            $loi[] = "Khu vực {$tenKhuVuc[$maKv]} có $so đội, tối đa " . PB_TOI_DA_MOI_KHU_VUC . ' đội.';
        }
    }
    return [$gan, $loi];
}

/** Lưu phân bảng của 1 mùa: xóa hết rồi thêm lại trong 1 transaction */
function pbLuu(int $maMua, array $gan): void
{
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM phan_bang WHERE ma_mua_giai = ?')->execute([$maMua]);
        $st = $pdo->prepare('INSERT INTO phan_bang (ma_mua_giai, ma_doi, ma_khu_vuc) VALUES (?, ?, ?)');
        foreach ($gan as $maDoi => $maKv) {
            if ($maKv !== null) {
                $st->execute([$maMua, $maDoi, $maKv]);
            }
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/** Số đội đã phân bảng trong mùa */
function pbDemDoi(int $maMua): int
{
    $st = ketNoi()->prepare('SELECT COUNT(*) FROM phan_bang WHERE ma_mua_giai = ?');
    $st->execute([$maMua]);
    return (int) $st->fetchColumn();
}

function pbMuaTonTai(int $maMua): bool
{
    $st = ketNoi()->prepare('SELECT COUNT(*) FROM mua_giai WHERE ma_mua_giai = ?');
    $st->execute([$maMua]);
    return (int) $st->fetchColumn() > 0;
}

/**
 * Sao chép phân bảng từ mùa $tuMua sang mùa $denMua.
 * Trả về [số đội đã chép, NULL] hoặc [0, câu báo lỗi].
 */
function pbSaoChep(int $tuMua, int $denMua): array
{
    if (!pbMuaTonTai($tuMua) || !pbMuaTonTai($denMua)) {
        return [0, 'Không tìm thấy mùa giải.'];
    }
    if ($tuMua === $denMua) {
        return [0, 'Hãy chọn một mùa khác để sao chép.'];
    }
    if (pbDemDoi($denMua) > 0) {
        return [0, 'Mùa này đã có phân bảng. Chỉ sao chép được khi mùa chưa có đội nào.'];
    }
    if (pbDemDoi($tuMua) === 0) {
        return [0, 'Mùa được chọn để sao chép chưa có phân bảng.'];
    }
    $st = ketNoi()->prepare(
        'INSERT INTO phan_bang (ma_mua_giai, ma_doi, ma_khu_vuc)
         SELECT ?, ma_doi, ma_khu_vuc FROM phan_bang WHERE ma_mua_giai = ?'
    );
    $st->execute([$denMua, $tuMua]);
    return [$st->rowCount(), null];
}
