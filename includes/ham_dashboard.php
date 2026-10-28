<?php
/**
 * DASHBOARD QUẢN TRỊ (TV4) - hàm truy vấn (chỉ đọc).
 * Số liệu mùa hiện tại, trận chờ nhập kết quả, kết quả mới nhất, top đội Đông / Tây, số trận theo ngày.
 *
 * Bảng dùng (chỉ đọc): mua_giai, tran_dau, doi_bong, phan_cong_trong_tai, hoi_nghi,
 *                      view v_bang_xep_hang (có sẵn trong qlbongro.sql)
 * Tên hàm có tiền tố dsb để không trùng hàm của module khác.
 */
require_once __DIR__ . '/../config/db.php';

const DSB_SO_TRONG_TAI = 3;

/** Mùa hiện tại (la_mua_hien_tai = 1), NULL nếu chưa có */
function dsbMuaHienTai(): ?array
{
    return ketNoi()->query(
        'SELECT * FROM mua_giai WHERE la_mua_hien_tai = 1 ORDER BY ngay_bat_dau DESC LIMIT 1'
    )->fetch() ?: null;
}

/** Số liệu nhanh của 1 mùa */
function dsbSoLieuMua(int $maMua): array
{
    $st = ketNoi()->prepare(
        "SELECT COUNT(*) AS tong,
                COALESCE(SUM(trang_thai = 'da_dau'), 0) AS da_dau,
                COALESCE(SUM(trang_thai = 'chua_dau' AND thoi_gian <= NOW()), 0) AS cho_ket_qua,
                COALESCE(SUM(trang_thai = 'hoan'), 0) AS hoan,
                COALESCE(SUM(trang_thai <> 'da_dau' AND
                    (SELECT COUNT(*) FROM phan_cong_trong_tai pc WHERE pc.ma_tran = tran_dau.ma_tran) < " . DSB_SO_TRONG_TAI . "), 0) AS thieu_trong_tai
           FROM tran_dau WHERE ma_mua_giai = ?"
    );
    $st->execute([$maMua]);
    return array_map('intval', $st->fetch());
}

/** Phần SELECT chung: trận kèm mã 2 đội */
const DSB_SELECT_TRAN =
    'SELECT td.ma_tran, td.thoi_gian, td.diem_doi_nha, td.diem_doi_khach, td.trang_thai,
            dn.ten_viet_tat AS vt_nha, dn.ten_doi AS ten_doi_nha,
            dk.ten_viet_tat AS vt_khach, dk.ten_doi AS ten_doi_khach
       FROM tran_dau td
       JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
       JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach';

/** Trận đã tới giờ nhưng chưa có kết quả (cũ nhất trước) */
function dsbTranChoKetQua(int $maMua, int $so = 5): array
{
    $st = ketNoi()->prepare(DSB_SELECT_TRAN .
        " WHERE td.ma_mua_giai = ? AND td.trang_thai = 'chua_dau' AND td.thoi_gian <= NOW()
          ORDER BY td.thoi_gian, td.ma_tran LIMIT " . max(1, $so));
    $st->execute([$maMua]);
    return $st->fetchAll();
}

/** Kết quả mới nhất */
function dsbKetQuaMoi(int $maMua, int $so = 5): array
{
    $st = ketNoi()->prepare(DSB_SELECT_TRAN .
        " WHERE td.ma_mua_giai = ? AND td.trang_thai = 'da_dau'
          ORDER BY td.thoi_gian DESC, td.ma_tran DESC LIMIT " . max(1, $so));
    $st->execute([$maMua]);
    return $st->fetchAll();
}

/** Top đội mỗi hội nghị theo tỉ lệ thắng: ['Miền Đông' => [...], 'Miền Tây' => [...]] */
function dsbTopDoi(int $maMua, int $so = 5): array
{
    $st = ketNoi()->prepare(
        'SELECT v.ma_hoi_nghi, hn.ten_hoi_nghi, v.thang, v.thua, v.ti_le_thang,
                d.ma_doi, d.ten_doi, d.ten_viet_tat
           FROM v_bang_xep_hang v
           JOIN doi_bong d  ON d.ma_doi = v.ma_doi
           JOIN hoi_nghi hn ON hn.ma_hoi_nghi = v.ma_hoi_nghi
          WHERE v.ma_mua_giai = ?
          ORDER BY v.ma_hoi_nghi, v.ti_le_thang DESC, v.thang DESC,
                   (v.tong_diem_ghi - v.tong_diem_bi_ghi) DESC, d.ten_doi'
    );
    $st->execute([$maMua]);
    $kq = [];
    foreach ($st->fetchAll() as $r) {
        $nhom = &$kq[$r['ten_hoi_nghi']];
        if (count($nhom ?? []) < $so) {
            $nhom[] = $r;
        }
        unset($nhom);
    }
    return $kq;
}

/** Số trận mỗi ngày trong mùa: [['ngay' => '2025-10-21', 'da_dau' => 3, 'chua_dau' => 0], ...] */
function dsbTranTheoNgay(int $maMua): array
{
    $st = ketNoi()->prepare(
        "SELECT DATE(thoi_gian) AS ngay,
                SUM(trang_thai = 'da_dau') AS da_dau,
                SUM(trang_thai <> 'da_dau') AS chua_dau
           FROM tran_dau WHERE ma_mua_giai = ?
          GROUP BY DATE(thoi_gian) ORDER BY ngay"
    );
    $st->execute([$maMua]);
    return $st->fetchAll();
}

/** '2025-10-21 19:30:00' -> '21/10 19:30' */
function dsbThoiGian(string $tg): string
{
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $tg);
    return $d ? $d->format('d/m H:i') : $tg;
}
