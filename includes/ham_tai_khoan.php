<?php
/**
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang *
 * Bảng dùng: tai_khoan (ghi); cau_thu, doi_bong (chỉ đọc)
**/
require_once __DIR__ . '/../config/db.php';

const TK_MAT_KHAU_TOI_THIEU = 6;  
const TK_TEN_TOI_THIEU      = 3;  
const TK_TEN_TOI_DA         = 50; 


function tkLayDanhSach(string $tuKhoa = ''): array
{
    $sql = "SELECT tk.ma_tai_khoan, tk.ten_dang_nhap, tk.vai_tro, tk.ma_cau_thu, tk.bi_khoa, tk.ngay_tao,
                   ct.ho_ten AS ten_cau_thu, d.ten_viet_tat AS doi
              FROM tai_khoan tk
              LEFT JOIN cau_thu ct ON ct.ma_cau_thu = tk.ma_cau_thu
              LEFT JOIN doi_bong d ON d.ma_doi = ct.ma_doi";
    $thamSo = [];
    if ($tuKhoa !== '') {
        $sql .= ' WHERE tk.ten_dang_nhap LIKE ? OR ct.ho_ten LIKE ?';
        $thamSo = ['%' . $tuKhoa . '%', '%' . $tuKhoa . '%'];
    }

    $st = ketNoi()->prepare($sql . " ORDER BY (tk.vai_tro = 'admin') DESC, tk.ten_dang_nhap");
    $st->execute($thamSo);
    return $st->fetchAll();
}

function tkLay(int $maTaiKhoan): ?array
{
    $st = ketNoi()->prepare(
        'SELECT tk.*, ct.ho_ten AS ten_cau_thu
           FROM tai_khoan tk
           LEFT JOIN cau_thu ct ON ct.ma_cau_thu = tk.ma_cau_thu
          WHERE tk.ma_tai_khoan = ?'
    );
    $st->execute([$maTaiKhoan]);
    return $st->fetch() ?: null;
}


function tkLayCauThuChuaCoTaiKhoan(): array
{
    return ketNoi()->query(
        "SELECT ct.ma_cau_thu, ct.ho_ten, ct.so_ao, d.ten_viet_tat AS doi
           FROM cau_thu ct
           LEFT JOIN doi_bong d  ON d.ma_doi = ct.ma_doi
           LEFT JOIN tai_khoan tk ON tk.ma_cau_thu = ct.ma_cau_thu
          WHERE tk.ma_tai_khoan IS NULL
            AND ct.trang_thai <> 'giai_nghe'
          ORDER BY d.ten_viet_tat, ct.ho_ten"
    )->fetchAll();
}

function tkTenDaTonTai(string $tenDangNhap, int $boQuaMa = 0): bool
{
    $st = ketNoi()->prepare('SELECT 1 FROM tai_khoan WHERE ten_dang_nhap = ? AND ma_tai_khoan <> ?');
    $st->execute([$tenDangNhap, $boQuaMa]);
    return (bool) $st->fetchColumn();
}


function tkKiemTraMatKhau(string $matKhau, string $nhapLai): ?string
{
    if (mb_strlen($matKhau) < TK_MAT_KHAU_TOI_THIEU) {
        return 'Mật khẩu phải có ít nhất ' . TK_MAT_KHAU_TOI_THIEU . ' ký tự.';
    }
    if (strlen($matKhau) > 72) { 
        return 'Mật khẩu tối đa 72 ký tự.';
    }
    if ($matKhau !== $nhapLai) {
        return 'Hai lần nhập mật khẩu không giống nhau.';
    }
    return null;
}


function tkKiemTraTenDangNhap(string $ten): ?string
{
    if ($ten === '') {
        return 'Vui lòng nhập tên đăng nhập.';
    }
    if (strlen($ten) < TK_TEN_TOI_THIEU || strlen($ten) > TK_TEN_TOI_DA) {
        return 'Tên đăng nhập dài từ ' . TK_TEN_TOI_THIEU . ' đến ' . TK_TEN_TOI_DA . ' ký tự.';
    }
    if (!preg_match('/^[a-z0-9._]+$/', $ten)) {
        return 'Tên đăng nhập chỉ gồm chữ thường không dấu (a-z), số, dấu chấm và gạch dưới.';
    }
    if (tkTenDaTonTai($ten)) {
        return 'Tên đăng nhập "' . $ten . '" đã có người dùng.';
    }
    return null;
}



function tkKiemTraTao(array $d): array
{
    $loi = [];
    if ($l = tkKiemTraTenDangNhap($d['ten_dang_nhap'])) {
        $loi['ten_dang_nhap'] = $l;
    }
    if ($l = tkKiemTraMatKhau($d['mat_khau'], $d['nhap_lai'])) {
        $loi['mat_khau'] = $l;
    }

    $maCauThu = (int) $d['ma_cau_thu'];
    $hopLe = false;
    foreach (tkLayCauThuChuaCoTaiKhoan() as $ct) {
        if ((int) $ct['ma_cau_thu'] === $maCauThu) {
            $hopLe = true;
            break;
        }
    }
    if (!$hopLe) {
        $loi['ma_cau_thu'] = 'Vui lòng chọn cầu thủ (cầu thủ đã có tài khoản sẽ không hiện trong danh sách).';
    }
    return $loi;
}


function tkKiemTraMatKhauCu(int $maTaiKhoan, string $matKhauCu): bool
{
    $st = ketNoi()->prepare('SELECT mat_khau FROM tai_khoan WHERE ma_tai_khoan = ?');
    $st->execute([$maTaiKhoan]);
    $bam = $st->fetchColumn();
    return $bam !== false && password_verify($matKhauCu, $bam);
}


function tkTao(string $tenDangNhap, string $matKhau, int $maCauThu): int
{
    $pdo = ketNoi();
    $st = $pdo->prepare(
        "INSERT INTO tai_khoan (ten_dang_nhap, mat_khau, vai_tro, ma_cau_thu)
         VALUES (?, ?, 'cau_thu', ?)"
    );
    $st->execute([$tenDangNhap, password_hash($matKhau, PASSWORD_DEFAULT), $maCauThu]);
    return (int) $pdo->lastInsertId();
}


function tkDatMatKhau(int $maTaiKhoan, string $matKhauMoi): void
{
    ketNoi()->prepare('UPDATE tai_khoan SET mat_khau = ? WHERE ma_tai_khoan = ?')
            ->execute([password_hash($matKhauMoi, PASSWORD_DEFAULT), $maTaiKhoan]);
}


function tkDoiKhoa(int $maTaiKhoan, bool $khoa, int $maNguoiDangDung): ?string
{
    $tk = tkLay($maTaiKhoan);
    if (!$tk) {
        return 'Không tìm thấy tài khoản.';
    }
    if ($khoa && $maTaiKhoan === $maNguoiDangDung) {
        return 'Bạn không thể tự khóa tài khoản đang đăng nhập.';
    }
    if ($khoa && $tk['vai_tro'] === 'admin') {
        return 'Không khóa tài khoản quản trị viên.';
    }
    ketNoi()->prepare('UPDATE tai_khoan SET bi_khoa = ? WHERE ma_tai_khoan = ?')
            ->execute([$khoa ? 1 : 0, $maTaiKhoan]);
    return null;
}
