<?php
/**
 * TRANG KHÁCH ĐỘI BÓNG (TV2) - danh sách đội, chi tiết đội (thư mục doi_bong/).
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: doi_bong, san_thi_dau, huan_luyen_vien, cau_thu, mua_giai, phan_bang, khu_vuc, hoi_nghi (chỉ đọc)
 *
 * Mỗi module của TV2 nằm trong 1 file riêng để các Pull Request độc lập, merge thứ tự nào cũng được.
 * Tên hàm có tiền tố riêng của từng module, không trùng hàm của TV3 (ham_cau_thu.php).
 */
require_once __DIR__ . '/tv2_chung.php';

/* =====================================================================
 * TRANG KHÁCH: danh sách đội, chi tiết đội
 * Lọc Đông/Tây dựa vào phan_bang của mùa hiện tại (dữ liệu của TV4).
 * Khi mùa hiện tại chưa phân bảng, các hàm vẫn trả về đầy đủ đội (khu vực = NULL).
 * ===================================================================== */


function tkLayHoiNghi(): array
{
    return ketNoi()->query('SELECT ma_hoi_nghi, ten_hoi_nghi FROM hoi_nghi ORDER BY ma_hoi_nghi')->fetchAll();
}

/** Số đội đã được phân bảng trong mùa */
function tkDemPhanBang(int $maMuaGiai): int
{
    $st = ketNoi()->prepare('SELECT COUNT(*) FROM phan_bang WHERE ma_mua_giai = ?');
    $st->execute([$maMuaGiai]);
    return (int) $st->fetchColumn();
}

/**
 * Danh sách đội cho Khách, kèm sân nhà và khu vực / hội nghị trong mùa $maMuaGiai.
 * $maHoiNghi = NULL: tất cả đội; có giá trị: chỉ đội thuộc hội nghị đó (cần có phân bảng).
 */
function tkLayDanhSachDoi(?int $maMuaGiai, ?int $maHoiNghi = null): array
{
    $sql = 'SELECT d.ma_doi, d.ten_doi, d.ten_viet_tat, d.thanh_pho, d.logo,
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

/** Chi tiết 1 đội: thông tin đội + sân nhà + khu vực / hội nghị trong mùa $maMuaGiai */
function tkLayChiTietDoi(int $maDoi, ?int $maMuaGiai): ?array
{
    $st = ketNoi()->prepare(
        'SELECT d.*, s.ten_san, s.thanh_pho AS thanh_pho_san, s.tieu_bang, s.suc_chua,
                s.nam_khanh_thanh, s.hinh_anh AS hinh_anh_san, kv.ten_khu_vuc, hn.ten_hoi_nghi
           FROM doi_bong d
           LEFT JOIN san_thi_dau s ON s.ma_san = d.ma_san
           LEFT JOIN phan_bang pb  ON pb.ma_doi = d.ma_doi AND pb.ma_mua_giai = ?
           LEFT JOIN khu_vuc kv    ON kv.ma_khu_vuc = pb.ma_khu_vuc
           LEFT JOIN hoi_nghi hn   ON hn.ma_hoi_nghi = kv.ma_hoi_nghi
          WHERE d.ma_doi = ?'
    );
    $st->execute([$maMuaGiai ?? 0, $maDoi]);
    return $st->fetch() ?: null;
}

/** Đội hình hiện tại (cầu thủ có ma_doi = đội, trừ người đã giải nghệ), xếp theo số áo */
function tkLayDoiHinh(int $maDoi): array
{
    $st = ketNoi()->prepare(
        "SELECT ma_cau_thu, ho_ten, so_ao, vi_tri, chieu_cao_cm, can_nang_kg, quoc_tich, anh, trang_thai
           FROM cau_thu
          WHERE ma_doi = ? AND trang_thai <> 'giai_nghe'
          ORDER BY so_ao IS NULL, so_ao, ho_ten"
    );
    $st->execute([$maDoi]);
    return $st->fetchAll();
}

/** Ban huấn luyện của đội: HLV trưởng trước, rồi HLV phó */
function tkLayBanHuanLuyen(int $maDoi): array
{
    $st = ketNoi()->prepare(
        "SELECT ma_hlv, ho_ten, quoc_tich, vai_tro, ngay_bat_dau, anh
           FROM huan_luyen_vien
          WHERE ma_doi = ?
          ORDER BY vai_tro = 'pho', ho_ten"
    );
    $st->execute([$maDoi]);
    return $st->fetchAll();
}
