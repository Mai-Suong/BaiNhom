<?php
/**
 * MODULE CẦU THỦ (TV3) - toàn bộ hàm truy vấn và nghiệp vụ.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: cau_thu, chuyen_nhuong, chan_thuong, doi_bong, tran_dau,
 *            san_thi_dau, mua_giai, chi_so_tran_dau, trao_giai, view v_chi_so_trung_binh
 */
require_once __DIR__ . '/../config/db.php';

const VI_TRI = [
    'PG' => 'Hậu vệ dẫn bóng (PG)',
    'SG' => 'Hậu vệ ghi điểm (SG)',
    'SF' => 'Tiền phong phụ (SF)',
    'PF' => 'Tiền phong chính (PF)',
    'C'  => 'Trung phong (C)',
];

const TRANG_THAI_CT = [
    'dang_thi_dau' => 'Đang thi đấu',
    'chan_thuong'  => 'Chấn thương',
    'tu_do'        => 'Tự do',
    'giai_nghe'    => 'Giải nghệ',
];

const LOAI_CHUYEN_NHUONG = [
    'trao_doi'         => 'Trao đổi',
    'ky_hop_dong'      => 'Ký hợp đồng',
    'giai_phong'       => 'Giải phóng (thành tự do)',
    'tuyen_chon_draft' => 'Tuyển chọn Draft',
];

const GIAI_DOAN = [
    'thuong'  => 'Mùa thường',
    'playin'  => 'Play-in',
    'playoff' => 'Playoff',
];

const THU_MUC_ANH = 'uploads/cau_thu/';   // tính từ thư mục gốc dự án
const ANH_TOI_DA  = 2 * 1024 * 1024;      // 2MB

/* =====================================================================
 * TIỆN ÍCH
 * ===================================================================== */

if (!function_exists('e')) { // includes/ham_chung.php cũng có hàm này, tránh khai báo trùng
    function e($s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

function ngayHopLe(?string $s): bool
{
    if ($s === null || $s === '') {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}

function dinhDangNgay(?string $s): string
{
    return $s ? date('d/m/Y', strtotime($s)) : '—';
}

function tinhTuoi(?string $ngaySinh): string
{
    if (!$ngaySinh) {
        return '—';
    }
    return (string) (new DateTime($ngaySinh))->diff(new DateTime())->y;
}

/** Số năm kinh nghiệm (cột "Exp" của Basketball-Reference) */
function kinhNghiem($namRaMat): string
{
    if (!$namRaMat) {
        return '—';
    }
    $n = (int) date('Y') - (int) $namRaMat;
    return $n <= 0 ? 'Tân binh' : $n . ' năm';
}

/**
 * Tính lại trạng thái cầu thủ theo dữ liệu thực tế:
 *   còn chấn thương chưa hồi phục -> chan_thuong
 *   không có đội                 -> tu_do
 *   ngược lại                    -> dang_thi_dau
 * Cầu thủ đã giải nghệ thì giữ nguyên.
 */
function capNhatTrangThai(PDO $pdo, int $maCauThu): void
{
    $pdo->prepare(
        "UPDATE cau_thu ct
            SET ct.trang_thai = CASE
                WHEN EXISTS (SELECT 1 FROM chan_thuong c
                              WHERE c.ma_cau_thu = ct.ma_cau_thu AND c.da_hoi_phuc = 0)
                     THEN 'chan_thuong'
                WHEN ct.ma_doi IS NULL THEN 'tu_do'
                ELSE 'dang_thi_dau'
            END
          WHERE ct.ma_cau_thu = ? AND ct.trang_thai <> 'giai_nghe'"
    )->execute([$maCauThu]);
}

/* =====================================================================
 * DANH MỤC CHO BỘ LỌC
 * ===================================================================== */

function layDanhSachDoi(): array
{
    return ketNoi()->query(
        'SELECT ma_doi, ten_doi, ten_viet_tat FROM doi_bong ORDER BY ten_doi'
    )->fetchAll();
}

function doiTonTai(int $maDoi): bool
{
    $st = ketNoi()->prepare('SELECT 1 FROM doi_bong WHERE ma_doi = ?');
    $st->execute([$maDoi]);
    return (bool) $st->fetchColumn();
}

function layDanhSachQuocTich(): array
{
    return ketNoi()->query(
        "SELECT DISTINCT quoc_tich FROM cau_thu
          WHERE quoc_tich IS NOT NULL AND quoc_tich <> ''
          ORDER BY quoc_tich"
    )->fetchAll(PDO::FETCH_COLUMN);
}

/* =====================================================================
 * ĐỌC DỮ LIỆU CẦU THỦ
 * ===================================================================== */

/**
 * Danh sách cầu thủ có lọc + phân trang.
 * $loc: tu_khoa, ma_doi (số hoặc 'tu_do'), vi_tri, quoc_tich, trang_thai
 */
function layDanhSachCauThu(array $loc, int $trang = 1, int $soDong = 15): array
{
    $dk = [];
    $ts = [];

    $maDoi = $loc['ma_doi'] ?? '';
    if ($maDoi === 'tu_do') {
        $dk[] = 'ct.ma_doi IS NULL';
    } elseif ($maDoi !== '' && ctype_digit((string) $maDoi)) {
        $dk[] = 'ct.ma_doi = ?';
        $ts[] = (int) $maDoi;
    }
    if (!empty($loc['vi_tri']) && isset(VI_TRI[$loc['vi_tri']])) {
        $dk[] = 'ct.vi_tri = ?';
        $ts[] = $loc['vi_tri'];
    }
    if (!empty($loc['quoc_tich'])) {
        $dk[] = 'ct.quoc_tich = ?';
        $ts[] = $loc['quoc_tich'];
    }
    if (!empty($loc['trang_thai']) && isset(TRANG_THAI_CT[$loc['trang_thai']])) {
        $dk[] = 'ct.trang_thai = ?';
        $ts[] = $loc['trang_thai'];
    }
    $tuKhoa = trim($loc['tu_khoa'] ?? '');
    if ($tuKhoa !== '') {
        $dk[] = 'ct.ho_ten LIKE ?';
        $ts[] = '%' . $tuKhoa . '%';
    }
    $where = $dk ? 'WHERE ' . implode(' AND ', $dk) : '';

    $pdo = ketNoi();
    $st = $pdo->prepare("SELECT COUNT(*) FROM cau_thu ct $where");
    $st->execute($ts);
    $tong = (int) $st->fetchColumn();

    $soTrang = max(1, (int) ceil($tong / $soDong));
    $trang   = min(max(1, $trang), $soTrang);
    $offset  = ($trang - 1) * $soDong;

    $st = $pdo->prepare(
        "SELECT ct.*, d.ten_doi, d.ten_viet_tat
           FROM cau_thu ct
           LEFT JOIN doi_bong d ON d.ma_doi = ct.ma_doi
           $where
          ORDER BY d.ten_doi IS NULL, d.ten_doi, ct.so_ao, ct.ho_ten
          LIMIT $soDong OFFSET $offset"
    );
    $st->execute($ts);

    return [
        'ds'       => $st->fetchAll(),
        'tong'     => $tong,
        'trang'    => $trang,
        'so_trang' => $soTrang,
    ];
}

function layCauThu(int $maCauThu): ?array
{
    $st = ketNoi()->prepare(
        'SELECT ct.*, d.ten_doi, d.ten_viet_tat, d.logo AS logo_doi
           FROM cau_thu ct
           LEFT JOIN doi_bong d ON d.ma_doi = ct.ma_doi
          WHERE ct.ma_cau_thu = ?'
    );
    $st->execute([$maCauThu]);
    return $st->fetch() ?: null;
}

/* =====================================================================
 * KIỂM TRA DỮ LIỆU
 * ===================================================================== */

/** Lấy dữ liệu từ form, chuỗi rỗng -> NULL, số -> int */
function chuanHoaDuLieuCauThu(array $p): array
{
    $chuoi = function (string $k) use ($p) {
        $v = trim((string) ($p[$k] ?? ''));
        return $v === '' ? null : $v;
    };
    $so = function (string $k) use ($p) {
        $v = trim((string) ($p[$k] ?? ''));
        if ($v === '') {
            return null;
        }
        $n = filter_var($v, FILTER_VALIDATE_INT);
        return $n === false ? $v : $n;   // giữ chuỗi sai để báo lỗi
    };

    return [
        'ho_ten'         => $chuoi('ho_ten'),
        'ngay_sinh'      => $chuoi('ngay_sinh'),
        'noi_sinh'       => $chuoi('noi_sinh'),
        'quoc_tich'      => $chuoi('quoc_tich'),
        'chieu_cao_cm'   => $so('chieu_cao_cm'),
        'can_nang_kg'    => $so('can_nang_kg'),
        'vi_tri'         => $chuoi('vi_tri'),
        'tay_nem'        => $chuoi('tay_nem') ?? 'phai',
        'so_ao'          => $so('so_ao'),
        'truong_dai_hoc' => $chuoi('truong_dai_hoc'),
        'nam_ra_mat_nba' => $so('nam_ra_mat_nba'),
        'ma_doi'         => $so('ma_doi'),
    ];
}

/** Trả về mảng lỗi [tên_trường => thông báo]. Rỗng = hợp lệ. */
function kiemTraDuLieuCauThu(array $d): array
{
    $loi = [];
    $soTrongKhoang = fn($v, $min, $max) => $v === null || (is_int($v) && $v >= $min && $v <= $max);

    if ($d['ho_ten'] === null) {
        $loi['ho_ten'] = 'Vui lòng nhập họ tên.';
    } elseif (mb_strlen($d['ho_ten']) > 100) {
        $loi['ho_ten'] = 'Họ tên tối đa 100 ký tự.';
    }

    if (!isset(VI_TRI[$d['vi_tri'] ?? ''])) {
        $loi['vi_tri'] = 'Vui lòng chọn vị trí.';
    }

    if ($d['ngay_sinh'] !== null) {
        if (!ngayHopLe($d['ngay_sinh'])) {
            $loi['ngay_sinh'] = 'Ngày sinh không hợp lệ.';
        } elseif ($d['ngay_sinh'] > date('Y-m-d', strtotime('-15 years'))) {
            $loi['ngay_sinh'] = 'Cầu thủ phải từ 15 tuổi trở lên.';
        }
    }

    if (!$soTrongKhoang($d['chieu_cao_cm'], 150, 250)) {
        $loi['chieu_cao_cm'] = 'Chiều cao phải từ 150 đến 250 cm.';
    }
    if (!$soTrongKhoang($d['can_nang_kg'], 50, 200)) {
        $loi['can_nang_kg'] = 'Cân nặng phải từ 50 đến 200 kg.';
    }
    if (!$soTrongKhoang($d['so_ao'], 0, 99)) {
        $loi['so_ao'] = 'Số áo phải là số nguyên từ 0 đến 99.';
    }
    if (!$soTrongKhoang($d['nam_ra_mat_nba'], 1946, (int) date('Y'))) {
        $loi['nam_ra_mat_nba'] = 'Năm ra mắt phải từ 1946 đến ' . date('Y') . '.';
    }
    if (!in_array($d['tay_nem'], ['phai', 'trai'], true)) {
        $loi['tay_nem'] = 'Tay ném không hợp lệ.';
    }

    foreach (['noi_sinh' => 'Nơi sinh', 'truong_dai_hoc' => 'Trường đại học'] as $k => $ten) {
        if ($d[$k] !== null && mb_strlen($d[$k]) > 100) {
            $loi[$k] = "$ten tối đa 100 ký tự.";
        }
    }
    if ($d['quoc_tich'] !== null && mb_strlen($d['quoc_tich']) > 50) {
        $loi['quoc_tich'] = 'Quốc tịch tối đa 50 ký tự.';
    }

    if ($d['ma_doi'] !== null && (!is_int($d['ma_doi']) || !doiTonTai($d['ma_doi']))) {
        $loi['ma_doi'] = 'Đội bóng không tồn tại.';
    }

    return $loi;
}

/**
 * Kiểm tra trùng số áo trong cùng đội.
 * Trả về cầu thủ đang giữ số áo đó (nếu có), ngược lại NULL.
 * $boQuaMa: mã cầu thủ đang sửa (để không tự báo trùng với chính mình).
 */
function kiemTraTrungSoAo(?int $maDoi, ?int $soAo, int $boQuaMa = 0): ?array
{
    if ($maDoi === null || $soAo === null) {
        return null;   // cầu thủ tự do hoặc chưa có số áo thì không cần kiểm tra
    }
    $st = ketNoi()->prepare(
        'SELECT ma_cau_thu, ho_ten FROM cau_thu
          WHERE ma_doi = ? AND so_ao = ? AND ma_cau_thu <> ?
          LIMIT 1'
    );
    $st->execute([$maDoi, $soAo, $boQuaMa]);
    return $st->fetch() ?: null;
}

/* =====================================================================
 * ẢNH CẦU THỦ
 * ===================================================================== */

/**
 * Lưu ảnh tải lên. Trả về đường dẫn tương đối (lưu vào cột anh),
 * NULL nếu người dùng không chọn ảnh. Ném RuntimeException nếu ảnh không hợp lệ.
 */
function xuLyUploadAnh(?array $file): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('Ảnh vượt quá dung lượng cho phép (2MB).');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Tải ảnh thất bại (mã lỗi ' . $file['error'] . ').');
    }
    if ($file['size'] > ANH_TOI_DA) {
        throw new RuntimeException('Ảnh vượt quá dung lượng cho phép (2MB).');
    }

    // Kiểm tra nội dung thật của file, không tin vào đuôi file
    $info = @getimagesize($file['tmp_name']);
    $duoi = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ][$info['mime'] ?? ''] ?? null;
    if ($duoi === null) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
    }

    $thuMuc = __DIR__ . '/../' . THU_MUC_ANH;
    if (!is_dir($thuMuc) && !mkdir($thuMuc, 0775, true)) {
        throw new RuntimeException('Không tạo được thư mục lưu ảnh.');
    }
    // Thư mục uploads/ không đưa lên GitHub (.gitignore), nên tự tạo file chặn chạy PHP
    if (!is_file($thuMuc . '.htaccess')) {
        @file_put_contents(
            $thuMuc . '.htaccess',
            "<FilesMatch \"\\.(php|phtml|phar|php\\d)$\">\n    Require all denied\n</FilesMatch>\nOptions -Indexes\n"
        );
    }

    $tenFile = 'ct_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $duoi;
    if (!move_uploaded_file($file['tmp_name'], $thuMuc . $tenFile)) {
        throw new RuntimeException('Không lưu được ảnh.');
    }
    return THU_MUC_ANH . $tenFile;
}

/** Xóa file ảnh cũ (chỉ xóa trong thư mục ảnh cầu thủ) */
function xoaAnh(?string $duongDan): void
{
    if (!$duongDan || strpos($duongDan, THU_MUC_ANH) !== 0 || strpos($duongDan, '..') !== false) {
        return;
    }
    $file = __DIR__ . '/../' . $duongDan;
    if (is_file($file)) {
        @unlink($file);
    }
}

/* =====================================================================
 * THÊM / SỬA / XÓA
 * ===================================================================== */

function themCauThu(array $d, ?string $anh): int
{
    $pdo = ketNoi();
    $pdo->prepare(
        'INSERT INTO cau_thu
            (ho_ten, ngay_sinh, noi_sinh, quoc_tich, chieu_cao_cm, can_nang_kg, vi_tri,
             tay_nem, so_ao, truong_dai_hoc, nam_ra_mat_nba, ma_doi, anh, trang_thai)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $d['ho_ten'], $d['ngay_sinh'], $d['noi_sinh'], $d['quoc_tich'],
        $d['chieu_cao_cm'], $d['can_nang_kg'], $d['vi_tri'], $d['tay_nem'],
        $d['so_ao'], $d['truong_dai_hoc'], $d['nam_ra_mat_nba'], $d['ma_doi'], $anh,
        $d['ma_doi'] === null ? 'tu_do' : 'dang_thi_dau',
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Sửa thông tin cầu thủ. KHÔNG đổi đội ở đây:
 * đổi đội phải đi qua chức năng Chuyển nhượng để lưu lịch sử.
 */
function suaCauThu(int $maCauThu, array $d, bool $giaiNghe, ?string $anhMoi): void
{
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $sql = 'UPDATE cau_thu SET
                    ho_ten = ?, ngay_sinh = ?, noi_sinh = ?, quoc_tich = ?,
                    chieu_cao_cm = ?, can_nang_kg = ?, vi_tri = ?, tay_nem = ?,
                    so_ao = ?, truong_dai_hoc = ?, nam_ra_mat_nba = ?,
                    trang_thai = IF(?, \'giai_nghe\',
                                    IF(trang_thai = \'giai_nghe\', \'dang_thi_dau\', trang_thai))'
             . ($anhMoi !== null ? ', anh = ?' : '')
             . ' WHERE ma_cau_thu = ?';
        $ts = [
            $d['ho_ten'], $d['ngay_sinh'], $d['noi_sinh'], $d['quoc_tich'],
            $d['chieu_cao_cm'], $d['can_nang_kg'], $d['vi_tri'], $d['tay_nem'],
            $d['so_ao'], $d['truong_dai_hoc'], $d['nam_ra_mat_nba'],
            $giaiNghe ? 1 : 0,
        ];
        if ($anhMoi !== null) {
            $ts[] = $anhMoi;
        }
        $ts[] = $maCauThu;
        $pdo->prepare($sql)->execute($ts);

        capNhatTrangThai($pdo, $maCauThu);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/**
 * Xóa cầu thủ. Trả về NULL nếu xóa được, ngược lại trả về lý do không xóa được.
 * Không cho xóa cầu thủ đã có thống kê trận đấu hoặc giải thưởng
 * (CSDL cũng chặn bằng ON DELETE RESTRICT).
 */
function xoaCauThu(int $maCauThu): ?string
{
    $ct = layCauThu($maCauThu);
    if (!$ct) {
        return 'Không tìm thấy cầu thủ.';
    }
    $pdo = ketNoi();

    $st = $pdo->prepare('SELECT COUNT(*) FROM chi_so_tran_dau WHERE ma_cau_thu = ?');
    $st->execute([$maCauThu]);
    if (($n = (int) $st->fetchColumn()) > 0) {
        return "Không thể xóa {$ct['ho_ten']}: đã có thống kê ở $n trận. "
             . 'Hãy đánh dấu "Giải nghệ" thay vì xóa.';
    }

    $st = $pdo->prepare('SELECT COUNT(*) FROM trao_giai WHERE ma_cau_thu = ?');
    $st->execute([$maCauThu]);
    if ((int) $st->fetchColumn() > 0) {
        return "Không thể xóa {$ct['ho_ten']}: cầu thủ đã nhận giải thưởng.";
    }

    // chuyen_nhuong, chan_thuong, tai_khoan tự xóa theo (ON DELETE CASCADE)
    $pdo->prepare('DELETE FROM cau_thu WHERE ma_cau_thu = ?')->execute([$maCauThu]);
    xoaAnh($ct['anh']);
    return null;
}

/* =====================================================================
 * CHUYỂN NHƯỢNG
 * ===================================================================== */

/**
 * Thực hiện chuyển nhượng trong 1 transaction:
 *   1. Khóa dòng cầu thủ (FOR UPDATE)
 *   2. Kiểm tra hợp lệ (đội mới khác đội cũ, số áo không trùng ở đội mới...)
 *   3. Cập nhật cau_thu.ma_doi (+ số áo mới nếu có)
 *   4. Ghi 1 dòng vào chuyen_nhuong
 * Lỗi bất kỳ bước nào -> rollback toàn bộ. Lỗi nghiệp vụ ném RuntimeException.
 */
function chuyenNhuong(
    int $maCauThu,
    string $loai,
    ?int $maDoiMoi,
    string $ngayChuyen,
    ?int $soAoMoi,
    ?string $ghiChu
): void {
    if (!isset(LOAI_CHUYEN_NHUONG[$loai])) {
        throw new RuntimeException('Loại chuyển nhượng không hợp lệ.');
    }
    if ($loai === 'giai_phong') {
        $maDoiMoi = null;          // giải phóng = trở thành tự do
    } elseif ($maDoiMoi === null) {
        throw new RuntimeException('Vui lòng chọn đội mới.');
    }
    if (!ngayHopLe($ngayChuyen)) {
        throw new RuntimeException('Ngày chuyển nhượng không hợp lệ.');
    }
    if ($soAoMoi !== null && ($soAoMoi < 0 || $soAoMoi > 99)) {
        throw new RuntimeException('Số áo mới phải từ 0 đến 99.');
    }
    $ghiChu = ($ghiChu !== null && trim($ghiChu) !== '') ? mb_substr(trim($ghiChu), 0, 255) : null;

    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM cau_thu WHERE ma_cau_thu = ? FOR UPDATE');
        $st->execute([$maCauThu]);
        $ct = $st->fetch();
        if (!$ct) {
            throw new RuntimeException('Không tìm thấy cầu thủ.');
        }
        if ($ct['trang_thai'] === 'giai_nghe') {
            throw new RuntimeException('Cầu thủ đã giải nghệ, không thể chuyển nhượng.');
        }

        $maDoiCu = $ct['ma_doi'] === null ? null : (int) $ct['ma_doi'];

        if ($loai === 'giai_phong' && $maDoiCu === null) {
            throw new RuntimeException('Cầu thủ đang tự do, không thể giải phóng.');
        }
        if ($maDoiMoi !== null && $maDoiMoi === $maDoiCu) {
            throw new RuntimeException('Đội mới phải khác đội hiện tại.');
        }
        if ($maDoiMoi !== null && !doiTonTai($maDoiMoi)) {
            throw new RuntimeException('Đội mới không tồn tại.');
        }

        $soAo = $soAoMoi ?? ($ct['so_ao'] === null ? null : (int) $ct['so_ao']);
        if ($trung = kiemTraTrungSoAo($maDoiMoi, $soAo, $maCauThu)) {
            throw new RuntimeException(
                "Số áo $soAo đã thuộc về {$trung['ho_ten']} ở đội mới. Vui lòng nhập số áo khác."
            );
        }

        $pdo->prepare('UPDATE cau_thu SET ma_doi = ?, so_ao = ? WHERE ma_cau_thu = ?')
            ->execute([$maDoiMoi, $soAo, $maCauThu]);

        $pdo->prepare(
            'INSERT INTO chuyen_nhuong (ma_cau_thu, loai, ma_doi_cu, ma_doi_moi, ngay_chuyen, ghi_chu)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$maCauThu, $loai, $maDoiCu, $maDoiMoi, $ngayChuyen, $ghiChu]);

        capNhatTrangThai($pdo, $maCauThu);   // tự do <-> đang thi đấu
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

function layLichSuChuyenNhuong(int $maCauThu): array
{
    $st = ketNoi()->prepare(
        'SELECT cn.*, dc.ten_doi AS ten_doi_cu, dm.ten_doi AS ten_doi_moi
           FROM chuyen_nhuong cn
           LEFT JOIN doi_bong dc ON dc.ma_doi = cn.ma_doi_cu
           LEFT JOIN doi_bong dm ON dm.ma_doi = cn.ma_doi_moi
          WHERE cn.ma_cau_thu = ?
          ORDER BY cn.ngay_chuyen DESC, cn.ma_chuyen_nhuong DESC'
    );
    $st->execute([$maCauThu]);
    return $st->fetchAll();
}

/* =====================================================================
 * CHẤN THƯƠNG
 * ===================================================================== */

function layDanhSachChanThuong(int $maCauThu, bool $chiDangBi = false): array
{
    $st = ketNoi()->prepare(
        'SELECT * FROM chan_thuong WHERE ma_cau_thu = ?'
        . ($chiDangBi ? ' AND da_hoi_phuc = 0' : '')
        . ' ORDER BY da_hoi_phuc, ngay_bi DESC'
    );
    $st->execute([$maCauThu]);
    return $st->fetchAll();
}

function themChanThuong(
    int $maCauThu,
    string $loai,
    string $ngayBi,
    ?string $ngayDuKien,
    ?string $ghiChu
): void {
    $loai = trim($loai);
    $ngayDuKien = ($ngayDuKien === null || $ngayDuKien === '') ? null : $ngayDuKien;
    $ghiChu = ($ghiChu !== null && trim($ghiChu) !== '') ? mb_substr(trim($ghiChu), 0, 255) : null;

    if ($loai === '' || mb_strlen($loai) > 100) {
        throw new RuntimeException('Loại chấn thương bắt buộc, tối đa 100 ký tự.');
    }
    if (!ngayHopLe($ngayBi) || $ngayBi > date('Y-m-d')) {
        throw new RuntimeException('Ngày bị chấn thương không hợp lệ (không được ở tương lai).');
    }
    if ($ngayDuKien !== null && (!ngayHopLe($ngayDuKien) || $ngayDuKien < $ngayBi)) {
        throw new RuntimeException('Ngày dự kiến trở lại phải sau ngày bị chấn thương.');
    }
    $ct = layCauThu($maCauThu);
    if (!$ct) {
        throw new RuntimeException('Không tìm thấy cầu thủ.');
    }
    if ($ct['trang_thai'] === 'giai_nghe') {
        throw new RuntimeException('Cầu thủ đã giải nghệ.');
    }

    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'INSERT INTO chan_thuong (ma_cau_thu, loai_chan_thuong, ngay_bi, ngay_du_kien_tro_lai, ghi_chu)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$maCauThu, $loai, $ngayBi, $ngayDuKien, $ghiChu]);
        capNhatTrangThai($pdo, $maCauThu);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

function danhDauHoiPhuc(int $maCauThu, int $maChanThuong): void
{
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'UPDATE chan_thuong SET da_hoi_phuc = 1 WHERE ma_chan_thuong = ? AND ma_cau_thu = ?'
        )->execute([$maChanThuong, $maCauThu]);
        capNhatTrangThai($pdo, $maCauThu);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

function xoaChanThuong(int $maCauThu, int $maChanThuong): void
{
    $pdo = ketNoi();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM chan_thuong WHERE ma_chan_thuong = ? AND ma_cau_thu = ?')
            ->execute([$maChanThuong, $maCauThu]);
        capNhatTrangThai($pdo, $maCauThu);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/* =====================================================================
 * CHỈ SỐ TRUNG BÌNH (lấy từ view v_chi_so_trung_binh do nhóm trưởng tạo)
 * ===================================================================== */

function layChiSoTrungBinh(int $maCauThu): array
{
    $st = ketNoi()->prepare(
        "SELECT v.*, mg.ten_mua_giai
           FROM v_chi_so_trung_binh v
           JOIN mua_giai mg ON mg.ma_mua_giai = v.ma_mua_giai
          WHERE v.ma_cau_thu = ?
          ORDER BY mg.ngay_bat_dau DESC, FIELD(v.giai_doan, 'thuong', 'playin', 'playoff')"
    );
    $st->execute([$maCauThu]);
    return $st->fetchAll();
}

/* =====================================================================
 * TRANG "CỦA TÔI"
 * ===================================================================== */

function layLichThiDauDoi(int $maDoi, int $gioiHan = 10): array
{
    $gioiHan = max(1, $gioiHan);
    $st = ketNoi()->prepare(
        "SELECT td.*, dn.ten_doi AS ten_doi_nha, dk.ten_doi AS ten_doi_khach, s.ten_san
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
           LEFT JOIN san_thi_dau s ON s.ma_san = td.ma_san
          WHERE (td.ma_doi_nha = ? OR td.ma_doi_khach = ?)
            AND td.trang_thai IN ('chua_dau', 'hoan')
            AND td.thoi_gian >= NOW()
          ORDER BY td.thoi_gian
          LIMIT $gioiHan"
    );
    $st->execute([$maDoi, $maDoi]);
    return $st->fetchAll();
}

function layKetQuaGanDayDoi(int $maDoi, int $gioiHan = 5): array
{
    $gioiHan = max(1, $gioiHan);
    $st = ketNoi()->prepare(
        "SELECT td.*, dn.ten_doi AS ten_doi_nha, dk.ten_doi AS ten_doi_khach
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
          WHERE (td.ma_doi_nha = ? OR td.ma_doi_khach = ?)
            AND td.trang_thai = 'da_dau'
          ORDER BY td.thoi_gian DESC
          LIMIT $gioiHan"
    );
    $st->execute([$maDoi, $maDoi]);
    return $st->fetchAll();
}

/** Cầu thủ tự đổi ảnh đại diện (chỉ được sửa ảnh, không sửa thông tin khác) */
function capNhatAnhDaiDien(int $maCauThu, ?array $file): void
{
    $ct = layCauThu($maCauThu);
    if (!$ct) {
        throw new RuntimeException('Không tìm thấy cầu thủ.');
    }
    $anhMoi = xuLyUploadAnh($file);
    if ($anhMoi === null) {
        throw new RuntimeException('Vui lòng chọn ảnh.');
    }
    try {
        ketNoi()->prepare('UPDATE cau_thu SET anh = ? WHERE ma_cau_thu = ?')
            ->execute([$anhMoi, $maCauThu]);
    } catch (Throwable $ex) {
        xoaAnh($anhMoi);
        throw $ex;
    }
    xoaAnh($ct['anh']);
}
