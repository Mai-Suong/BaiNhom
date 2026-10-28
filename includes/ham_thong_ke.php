<?php
/**
 * Hàm dùng chung của TV5: bảng xếp hạng, thống kê, box score, playoff.
 * Trang nào dùng thì require SAU includes/init.php:
 *     require_once __DIR__ . '/../includes/init.php';
 *     require_once __DIR__ . '/../includes/ham_thong_ke.php';
 * Tất cả hàm đều bắt đầu bằng "tk" để không trùng tên hàm của module khác.
 */

/* ======================= Tiện ích chung ======================= */

/** Chạy câu SELECT có tham số (dấu ?), trả về mảng các dòng (rỗng nếu không có) */
function tkTruyVan(PDO $pdo, string $sql, array $thamSo = []): array
{
    $st = $pdo->prepare($sql);
    $st->execute($thamSo);
    return $st->fetchAll();
}

/** Số thập phân dùng dấu chấm, ví dụ 27.4 */
function tkSo($v, int $le = 1): string
{
    return number_format((float) $v, $le, '.', '');
}

/** Phần trăm làm tròn 1 chữ số; chia cho 0 thì hiện "—" */
function tkTiLe($a, $b): string
{
    return $b > 0 ? number_format(100 * $a / $b, 1, '.', '') . '%' : '—';
}

/** +5, −3, 0 */
function tkDauSo($n): string
{
    $n = (int) $n;
    return $n > 0 ? '+' . $n : ($n < 0 ? '−' . abs($n) : '0');
}

/** Giữ nguyên các tham số GET hiện tại, chỉ đổi những tham số truyền vào */
function tkUrlVoi(array $thamSo): string
{
    return '?' . http_build_query(array_merge($_GET, $thamSo));
}

/** Danh sách mùa giải và mùa đang chọn (?mua=; mặc định mùa hiện tại, rồi mùa mới nhất) */
function tkLayMua(PDO $pdo): array
{
    $ds = tkTruyVan($pdo, 'SELECT ma_mua_giai, ten_mua_giai, la_mua_hien_tai FROM mua_giai ORDER BY ngay_bat_dau DESC');
    $ma = isset($_GET['mua']) ? (int) $_GET['mua'] : 0;
    if ($ma === 0) {
        foreach ($ds as $m) {
            if ((int) $m['la_mua_hien_tai'] === 1) {
                $ma = (int) $m['ma_mua_giai'];
                break;
            }
        }
        if ($ma === 0 && $ds) {
            $ma = (int) $ds[0]['ma_mua_giai'];
        }
    }
    return [$ds, $ma];
}

/** Ô chọn mùa giải, tự gửi form khi đổi. $giuLai: các tham số GET cần giữ lại. */
function tkChonMua(array $dsMua, int $maMua, array $giuLai = []): string
{
    $h = '<form method="get" class="d-flex align-items-center gap-2">';
    $h .= '<label for="mua" class="fw-semibold mb-0">Mùa giải</label>';
    $h .= '<select id="mua" name="mua" class="form-select form-select-sm w-auto" onchange="this.form.submit()">';
    foreach ($dsMua as $m) {
        $h .= '<option value="' . (int) $m['ma_mua_giai'] . '"' . ((int) $m['ma_mua_giai'] === $maMua ? ' selected' : '') . '>'
            . e($m['ten_mua_giai']) . '</option>';
    }
    $h .= '</select>';
    foreach ($giuLai as $k => $v) {
        $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $h . '</form>';
}

/** Thẻ <link> nạp CSS riêng của TV5 (đặt ngay sau khi include header) */
function tkCss(): string
{
    return '<link href="' . url('/assets/css/thong_ke.css') . '" rel="stylesheet">';
}

/** Thanh chuyển giữa các trang thống kê. $dang: top | so_sanh | box_score | playoff */
function tkMenu(string $dang): string
{
    $muc = [
        'top'       => ['Top cầu thủ',  '/thong_ke/index.php'],
        'so_sanh'   => ['So sánh',      '/thong_ke/so_sanh.php'],
        'box_score' => ['Box score',    '/thong_ke/box_score.php'],
        'playoff'   => ['Playoff',      '/thong_ke/playoff.php'],
    ];
    $h = '<ul class="nav nav-pills mb-4">';
    foreach ($muc as $ma => [$ten, $link]) {
        $h .= '<li class="nav-item"><a class="nav-link' . ($ma === $dang ? ' active bg-nba' : '') . '" href="' . url($link) . '">' . e($ten) . '</a></li>';
    }
    return $h . '</ul>';
}

/* ======================= Box score ======================= */

const TK_COT_CONG = ['so_phut', 'nem_trung', 'nem_thu', 'ba_diem_trung', 'ba_diem_thu', 'phat_trung', 'phat_thu',
                     'bat_bong_tan_cong', 'bat_bong_phong_thu', 'kien_tao', 'cuop_bong', 'chan_bong', 'mat_bong', 'pham_loi', 'diem'];

/** Tách cầu thủ một đội thành: xuất phát, dự bị, không thi đấu, và dòng tổng */
function tkTinhDoi(array $dong): array
{
    $xp = $du = $khong = [];
    foreach ($dong as $r) {
        if ($r['trang_thai'] === 'thi_dau') {
            if ((int) $r['xuat_phat'] === 1) {
                $xp[] = $r;
            } else {
                $du[] = $r;
            }
        } else {
            $khong[] = $r;
        }
    }
    $tong = array_fill_keys(TK_COT_CONG, 0);
    foreach (array_merge($xp, $du) as $r) {
        foreach (TK_COT_CONG as $c) {
            $tong[$c] += (int) $r[$c];
        }
    }
    return [$xp, $du, $khong, $tong];
}

/** In một dòng cầu thủ trong bảng box score */
function tkDongCauThu(array $r): string
{
    $pm = (int) $r['cong_tru'];
    $h  = '<tr><td class="trai"><a class="text-decoration-none" href="' . url('/cau_thu/ho_so.php?id=' . (int) $r['ma_cau_thu']) . '">'
        . e($r['ho_ten']) . '</a> <small class="text-muted">' . e($r['vi_tri']) . '</small></td>';
    $h .= '<td>' . (int) $r['so_phut'] . '</td>';
    $h .= '<td>' . (int) $r['nem_trung'] . '-' . (int) $r['nem_thu'] . '</td>';
    $h .= '<td>' . (int) $r['ba_diem_trung'] . '-' . (int) $r['ba_diem_thu'] . '</td>';
    $h .= '<td>' . (int) $r['phat_trung'] . '-' . (int) $r['phat_thu'] . '</td>';
    $h .= '<td>' . (int) $r['bat_bong_tan_cong'] . '</td><td>' . (int) $r['bat_bong_phong_thu'] . '</td>';
    $h .= '<td>' . ((int) $r['bat_bong_tan_cong'] + (int) $r['bat_bong_phong_thu']) . '</td>';
    $h .= '<td>' . (int) $r['kien_tao'] . '</td><td>' . (int) $r['cuop_bong'] . '</td><td>' . (int) $r['chan_bong'] . '</td>';
    $h .= '<td>' . (int) $r['mat_bong'] . '</td><td>' . (int) $r['pham_loi'] . '</td>';
    $h .= '<td><strong>' . (int) $r['diem'] . '</strong></td>';
    $h .= '<td class="' . ($pm > 0 ? 'duong' : ($pm < 0 ? 'am' : '')) . '">' . tkDauSo($pm) . '</td></tr>';
    return $h;
}

/** Bảng box score của một đội (thẻ card Bootstrap) */
function tkBangDoi(string $tenDoi, string $vietTat, array $dong): string
{
    [$xp, $du, $khong, $t] = tkTinhDoi($dong);
    ob_start();
    ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header fw-semibold"><span class="vong-tron"><?= e($vietTat) ?></span><?= e($tenDoi) ?></div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 bang-so">
          <thead><tr>
            <th class="trai">Cầu thủ</th><th>Phút</th><th>Ném</th><th>3 điểm</th><th>Phạt</th>
            <th title="Bắt bóng tấn công">BBTC</th><th title="Bắt bóng phòng thủ">BBPT</th><th>Bóng</th>
            <th title="Kiến tạo">KT</th><th>Cướp</th><th>Chặn</th><th>Mất</th><th>Lỗi</th><th>Điểm</th><th>+/−</th>
          </tr></thead>
          <tbody>
            <tr class="nhom-dong"><td colspan="15">Xuất phát</td></tr>
            <?php foreach ($xp as $r) { echo tkDongCauThu($r); } ?>
            <tr class="nhom-dong"><td colspan="15">Dự bị</td></tr>
            <?php foreach ($du as $r) { echo tkDongCauThu($r); } ?>
            <?php foreach ($khong as $r): ?>
              <tr class="khong-dau">
                <td class="trai" colspan="14"><?= e($r['ho_ten']) ?> <small><?= e($r['vi_tri']) ?></small></td>
                <td><?= $r['trang_thai'] === 'vang_mat' ? 'Vắng mặt' : 'Không thi đấu' ?></td>
              </tr>
            <?php endforeach; ?>
            <tr class="tong-dong">
              <td class="trai">Tổng đội</td><td><?= $t['so_phut'] ?></td>
              <td><?= $t['nem_trung'] ?>-<?= $t['nem_thu'] ?></td><td><?= $t['ba_diem_trung'] ?>-<?= $t['ba_diem_thu'] ?></td><td><?= $t['phat_trung'] ?>-<?= $t['phat_thu'] ?></td>
              <td><?= $t['bat_bong_tan_cong'] ?></td><td><?= $t['bat_bong_phong_thu'] ?></td><td><?= $t['bat_bong_tan_cong'] + $t['bat_bong_phong_thu'] ?></td>
              <td><?= $t['kien_tao'] ?></td><td><?= $t['cuop_bong'] ?></td><td><?= $t['chan_bong'] ?></td><td><?= $t['mat_bong'] ?></td><td><?= $t['pham_loi'] ?></td>
              <td><?= $t['diem'] ?></td><td></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="card-footer small text-muted">
        Ném <?= tkTiLe($t['nem_trung'], $t['nem_thu']) ?> · 3 điểm <?= tkTiLe($t['ba_diem_trung'], $t['ba_diem_thu']) ?> · Phạt <?= tkTiLe($t['phat_trung'], $t['phat_thu']) ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

/* ======================= Playoff ======================= */

const TK_SO_TRAN_THANG = 4;   // thắng 4 trận là thắng loạt (bo7)

/** Mọi loạt của mùa, kèm số trận thắng của hai đội (đếm từ tran_dau) và hội nghị */
function tkLayLoat(PDO $pdo, int $maMua): array
{
    $dem = function (string $cotDoi): string {
        return "(SELECT COUNT(*) FROM tran_dau t
                 WHERE t.ma_loat = l.ma_loat AND t.trang_thai = 'da_dau'
                   AND ((t.ma_doi_nha = l.$cotDoi AND t.diem_doi_nha > t.diem_doi_khach)
                     OR (t.ma_doi_khach = l.$cotDoi AND t.diem_doi_khach > t.diem_doi_nha)))";
    };
    return tkTruyVan($pdo,
        'SELECT l.*, d1.ten_viet_tat vt1, d2.ten_viet_tat vt2, kv.ma_hoi_nghi,
                ' . $dem('ma_doi_1') . ' AS thang1, ' . $dem('ma_doi_2') . ' AS thang2
         FROM loat_playoff l
         JOIN doi_bong d1 ON d1.ma_doi = l.ma_doi_1
         JOIN doi_bong d2 ON d2.ma_doi = l.ma_doi_2
         LEFT JOIN phan_bang pb ON pb.ma_mua_giai = l.ma_mua_giai AND pb.ma_doi = l.ma_doi_1
         LEFT JOIN khu_vuc kv ON kv.ma_khu_vuc = pb.ma_khu_vuc
         WHERE l.ma_mua_giai = ?
         ORDER BY l.ma_loat', [$maMua]);
}

/** Vị trí (ô) của loạt trong cột của nó, để các ô thẳng hàng trong sơ đồ */
function tkOTrongVong(string $vong, array $r): int
{
    $hat = (int) $r['hat_giong_1'];
    if ($vong === 'vong_1') {
        $map = [1 => 0, 8 => 0, 4 => 1, 5 => 1, 3 => 2, 6 => 2, 2 => 3, 7 => 3];
        return $map[$hat] ?? 0;
    }
    if ($vong === 'ban_ket_mien') {
        return in_array($hat, [1, 8, 4, 5], true) ? 0 : 1;
    }
    return 0;
}

function tkSoO(string $vong): int
{
    return ['vong_1' => 4, 'ban_ket_mien' => 2, 'chung_ket_mien' => 1, 'chung_ket' => 1][$vong];
}

/** Xếp các loạt của một vòng vào đúng ô; ô chưa có loạt = null */
function tkXepO(array $dong, string $vong): array
{
    $o = array_fill(0, tkSoO($vong), null);
    foreach ($dong as $r) {
        $i = tkOTrongVong($vong, $r);
        if ($o[$i] !== null) {
            foreach ($o as $k => $x) {
                if ($x === null) {
                    $i = $k;
                    break;
                }
            }
        }
        $o[$i] = $r;
    }
    return $o;
}

function tkThemLoat(PDO $pdo, int $maMua, string $vong, int $doi1, int $doi2, ?int $hat1, ?int $hat2): void
{
    $st = $pdo->prepare('INSERT INTO loat_playoff (ma_mua_giai, vong, ma_doi_1, ma_doi_2, hat_giong_1, hat_giong_2) VALUES (?,?,?,?,?,?)');
    $st->execute([$maMua, $vong, $doi1, $doi2, $hat1, $hat2]);
}

function tkDoiThangCua(array $r): array
{
    $doi = (int) $r['ma_doi_thang'];
    $hat = ($doi === (int) $r['ma_doi_1']) ? $r['hat_giong_1'] : $r['hat_giong_2'];
    return ['doi' => $doi, 'hat' => $hat === null ? null : (int) $hat];
}

/** Đội hạt giống cao hơn (số nhỏ hơn) đứng trước */
function tkGhep(array $x, array $y): array
{
    return ((int) $x['hat'] <= (int) $y['hat']) ? [$x, $y] : [$y, $x];
}

/** Điền ma_doi_thang cho các loạt đã có đội thắng đủ 4 trận. Trả về số loạt được cập nhật. */
function tkCapNhatDoiThang(PDO $pdo, int $maMua): int
{
    $n  = 0;
    $st = $pdo->prepare('UPDATE loat_playoff SET ma_doi_thang = ? WHERE ma_loat = ?');
    foreach (tkLayLoat($pdo, $maMua) as $r) {
        if ($r['ma_doi_thang'] !== null) {
            continue;
        }
        $w = null;
        if ((int) $r['thang1'] >= TK_SO_TRAN_THANG) {
            $w = (int) $r['ma_doi_1'];
        } elseif ((int) $r['thang2'] >= TK_SO_TRAN_THANG) {
            $w = (int) $r['ma_doi_2'];
        }
        if ($w !== null) {
            $st->execute([$w, (int) $r['ma_loat']]);
            $n++;
        }
    }
    return $n;
}

/** Tạo 8 loạt vòng 1 từ 8 đội đầu bảng xếp hạng mỗi miền (1-8, 4-5, 3-6, 2-7) */
function tkTaoVong1(PDO $pdo, int $maMua): string
{
    foreach (tkLayLoat($pdo, $maMua) as $r) {
        if ($r['vong'] === 'vong_1') {
            throw new RuntimeException('Vòng 1 của mùa này đã được tạo.');
        }
    }
    $top = [];
    foreach ([1, 2] as $hn) {
        $top[$hn] = tkTruyVan($pdo,
            'SELECT v.ma_doi FROM v_bang_xep_hang v JOIN doi_bong d ON d.ma_doi = v.ma_doi
             WHERE v.ma_mua_giai = ? AND v.ma_hoi_nghi = ?
             ORDER BY v.ti_le_thang DESC, v.thang DESC, (v.tong_diem_ghi - v.tong_diem_bi_ghi) DESC, d.ten_doi
             LIMIT 8', [$maMua, $hn]);
        if (count($top[$hn]) < 8) {
            throw new RuntimeException('Miền ' . ($hn === 1 ? 'Đông' : 'Tây')
                . ' chưa đủ 8 đội trong bảng xếp hạng (cần có dữ liệu phân bảng và các trận đã đấu).');
        }
    }
    foreach ([1, 2] as $hn) {
        foreach ([[1, 8], [4, 5], [3, 6], [2, 7]] as [$s1, $s2]) {
            tkThemLoat($pdo, $maMua, 'vong_1', (int) $top[$hn][$s1 - 1]['ma_doi'], (int) $top[$hn][$s2 - 1]['ma_doi'], $s1, $s2);
        }
    }
    return 'Đã tạo 8 loạt vòng 1 từ bảng xếp hạng.';
}

/** Tạo vòng kế tiếp từ các đội thắng của vòng trước */
function tkTaoVongKe(PDO $pdo, int $maMua, string $vong): string
{
    $truoc = ['ban_ket_mien' => 'vong_1', 'chung_ket_mien' => 'ban_ket_mien', 'chung_ket' => 'chung_ket_mien'][$vong];
    $tatCa = tkLayLoat($pdo, $maMua);
    foreach ($tatCa as $r) {
        if ($r['vong'] === $vong) {
            throw new RuntimeException('Vòng này đã được tạo.');
        }
    }
    $thang = [];   // [hội nghị] => danh sách đội thắng theo thứ tự ô
    foreach ([1, 2] as $hn) {
        $dong = array_values(array_filter($tatCa, function ($r) use ($truoc, $hn) {
            return $r['vong'] === $truoc && (int) $r['ma_hoi_nghi'] === $hn;
        }));
        foreach (tkXepO($dong, $truoc) as $r) {
            if ($r === null || $r['ma_doi_thang'] === null) {
                throw new RuntimeException('Vòng trước chưa đủ loạt đã có đội thắng. Hãy bấm "Cập nhật đội thắng loạt" sau khi các trận đã nhập kết quả.');
            }
            $thang[$hn][] = tkDoiThangCua($r);
        }
    }
    $cap = [];
    if ($vong === 'ban_ket_mien') {
        foreach ([1, 2] as $hn) {
            $cap[] = tkGhep($thang[$hn][0], $thang[$hn][1]);
            $cap[] = tkGhep($thang[$hn][2], $thang[$hn][3]);
        }
    } elseif ($vong === 'chung_ket_mien') {
        foreach ([1, 2] as $hn) {
            $cap[] = tkGhep($thang[$hn][0], $thang[$hn][1]);
        }
    } else {
        $cap[] = tkGhep($thang[1][0], $thang[2][0]);
    }
    foreach ($cap as [$x, $y]) {
        tkThemLoat($pdo, $maMua, $vong, $x['doi'], $y['doi'], $x['hat'], $y['hat']);
    }
    return 'Đã tạo ' . count($cap) . ' loạt.';
}

/** Thẻ một loạt trong sơ đồ nhánh; $r = null là ô chờ vòng trước */
function tkTheLoat(?array $r): string
{
    if ($r === null) {
        $doi = '<div class="doi"><span class="hat-giong"></span><span class="ten">—</span><span class="so-thang"></span></div>';
        return '<div class="the-loat">' . $doi . $doi . '<div class="trang-thai-loat">Chờ vòng trước</div></div>';
    }
    $w1   = (int) $r['thang1'];
    $w2   = (int) $r['thang2'];
    $xong = $r['ma_doi_thang'] !== null;
    $lop1 = $lop2 = '';
    if ($xong) {
        if ((int) $r['ma_doi_thang'] === (int) $r['ma_doi_1']) {
            $lop1 = ' thang';
        } else {
            $lop2 = ' thang';
        }
    } elseif ($w1 > $w2) {
        $lop1 = ' dan';
    } elseif ($w2 > $w1) {
        $lop2 = ' dan';
    }
    $tt = $xong ? 'Kết thúc' : (($w1 + $w2) > 0 ? 'Đang diễn ra' : 'Chưa đấu');
    $h  = '<div class="the-loat">';
    $h .= '<div class="doi' . $lop1 . '"><span class="hat-giong">' . e($r['hat_giong_1']) . '</span><span class="ten">' . e($r['vt1']) . '</span><span class="so-thang">' . $w1 . '</span></div>';
    $h .= '<div class="doi' . $lop2 . '"><span class="hat-giong">' . e($r['hat_giong_2']) . '</span><span class="ten">' . e($r['vt2']) . '</span><span class="so-thang">' . $w2 . '</span></div>';
    $h .= '<div class="trang-thai-loat' . ((!$xong && ($w1 + $w2) > 0) ? ' dang' : '') . '">' . e($tt) . '</div></div>';
    return $h;
}

/** Toàn bộ sơ đồ nhánh Playoff (7 cột: Đông 3 vòng, Chung kết, Tây 3 vòng) */
function tkSoDoNhanh(array $loat): string
{
    $theo = [];   // [vòng][hội nghị] => danh sách loạt
    foreach ($loat as $r) {
        $hn = $r['vong'] === 'chung_ket' ? 0 : (int) $r['ma_hoi_nghi'];
        $theo[$r['vong']][$hn][] = $r;
    }
    $cot = function (string $vong, int $hn) use ($theo): array {
        return tkXepO($theo[$vong][$hn] ?? [], $vong);
    };
    $cacCot = [
        ['Vòng 1',         $cot('vong_1', 1)],
        ['Bán kết miền',   $cot('ban_ket_mien', 1)],
        ['Chung kết miền', $cot('chung_ket_mien', 1)],
        ['Chung kết NBA',  $cot('chung_ket', 0)],
        ['Chung kết miền', $cot('chung_ket_mien', 2)],
        ['Bán kết miền',   $cot('ban_ket_mien', 2)],
        ['Vòng 1',         $cot('vong_1', 2)],
    ];
    $h = '<div class="card shadow-sm mb-3"><div class="card-body nhanh-cuon" aria-label="Nhánh đấu Playoff">';
    $h .= '<div class="nhanh-tieu-de"><div class="dong">Miền Đông</div><div class="chung-ket">Chung kết</div><div class="tay">Miền Tây</div></div>';
    $h .= '<div class="nhanh-luoi">';
    foreach ($cacCot as [$tenVong, $oLoat]) {
        $h .= '<div class="cot-nhanh"><div class="ten-vong">' . e($tenVong) . '</div><div class="noi-dung">';
        foreach ($oLoat as $r) {
            $h .= tkTheLoat($r);
        }
        $h .= '</div></div>';
    }
    $h .= '</div></div></div>';
    $h .= '<div class="chu-thich d-flex flex-wrap gap-3 small mb-2">'
        . '<span><i style="background:#0f1b2d"></i>Thắng loạt</span>'
        . '<span><i style="background:#fff1e8; border:1px solid #c2410c"></i>Đang dẫn khi loạt chưa kết thúc</span>'
        . '<span><i style="background:#e9ecef"></i>Thua hoặc hòa tỉ số</span></div>';
    $h .= '<p class="small text-muted">Số trận thắng của mỗi đội đếm từ các trận trong bảng <code>tran_dau</code> theo mã loạt, '
        . 'không lưu trong <code>loat_playoff</code>. Số nhỏ bên trái là hạt giống.</p>';
    return $h;
}
