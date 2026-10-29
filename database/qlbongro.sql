-- =====================================================================
--  ĐỒ ÁN: QUẢN LÝ GIẢI BÓNG RỔ NHÀ NGHỀ MỸ (NBA)
--  File: database/qlbongro.sql - tạo database, 20 bảng, dữ liệu danh mục, 2 VIEW
--  Chạy trên: XAMPP (MariaDB 10.4) - đã import thử thành công
--  Cách dùng: phpMyAdmin -> tab Import -> chọn file này -> Import
--             (không cần tạo database trước, file tự tạo)
--  Nếu muốn làm lại từ đầu: xóa database qlbongro rồi import lại.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS qlbongro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE qlbongro;

--  CẤP 1: Các bảng KHÔNG phụ thuộc bảng nào 
-- 1. Hội nghị (Conference): Eastern / Western
CREATE TABLE hoi_nghi (
    ma_hoi_nghi   INT          NOT NULL AUTO_INCREMENT,
    ten_hoi_nghi  VARCHAR(50)  NOT NULL,              -- 'Miền Đông'
    PRIMARY KEY (ma_hoi_nghi),
    UNIQUE KEY uq_ten_hoi_nghi (ten_hoi_nghi)
) ENGINE=InnoDB;
 
-- 2. Sân thi đấu (Arena)
CREATE TABLE san_thi_dau (
    ma_san           INT           NOT NULL AUTO_INCREMENT,
    ten_san          VARCHAR(100)  NOT NULL,          -- 'Madison Square Garden (IV)'
    thanh_pho        VARCHAR(100)  NOT NULL,
    tieu_bang        VARCHAR(50),
    suc_chua         INT           CHECK (suc_chua > 0),
    nam_khanh_thanh  YEAR,
    hinh_anh         VARCHAR(255),
    PRIMARY KEY (ma_san)
) ENGINE=InnoDB;
 
-- 3. Mùa giải (Season) - đặt tên theo kiểu Basketball-Reference: '2025-26'
CREATE TABLE mua_giai (
    ma_mua_giai       INT          NOT NULL AUTO_INCREMENT,
    ten_mua_giai      VARCHAR(20)  NOT NULL,          -- '2025-26'
    ngay_bat_dau      DATE         NOT NULL,
    ngay_ket_thuc     DATE         NOT NULL,
    la_mua_hien_tai   TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (ma_mua_giai),
    UNIQUE KEY uq_ten_mua_giai (ten_mua_giai),
    CHECK (ngay_ket_thuc > ngay_bat_dau)
) ENGINE=InnoDB;
 
-- 4. Trọng tài (Officials) - box score của Basketball-Reference ghi tên trọng tài từng trận
CREATE TABLE trong_tai (
    ma_trong_tai        INT           NOT NULL AUTO_INCREMENT,
    ho_ten              VARCHAR(100)  NOT NULL,
    so_ao               TINYINT,
    nam_bat_dau         YEAR,                         -- năm bắt đầu bắt ở NBA
    PRIMARY KEY (ma_trong_tai)
) ENGINE=InnoDB;
 
-- 5. Danh mục giải thưởng cá nhân (MVP, ROY, DPOY...)
CREATE TABLE giai_thuong (
    ma_giai_thuong  INT           NOT NULL AUTO_INCREMENT,
    ten_viet_tat    VARCHAR(10)   NOT NULL,           -- 'MVP'
    ten_giai        VARCHAR(100)  NOT NULL,           -- 'Cầu thủ xuất sắc nhất mùa giải'
    doi_tuong       ENUM('cau_thu','hlv') NOT NULL DEFAULT 'cau_thu',
    PRIMARY KEY (ma_giai_thuong),
    UNIQUE KEY uq_giai_viet_tat (ten_viet_tat)
) ENGINE=InnoDB;
 
--  CẤP 2 
-- 6. Khu vực (Division) - thuộc một hội nghị
CREATE TABLE khu_vuc (
    ma_khu_vuc    INT          NOT NULL AUTO_INCREMENT,
    ten_khu_vuc   VARCHAR(50)  NOT NULL,              -- 'Đại Tây Dương'
    ma_hoi_nghi   INT          NOT NULL,
    PRIMARY KEY (ma_khu_vuc),
    UNIQUE KEY uq_ten_khu_vuc (ten_khu_vuc),
    CONSTRAINT fk_khuvuc_hoinghi FOREIGN KEY (ma_hoi_nghi)
        REFERENCES hoi_nghi (ma_hoi_nghi)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
 
-- 7. Đội bóng (Franchise)
CREATE TABLE doi_bong (
    ma_doi          INT           NOT NULL AUTO_INCREMENT,
    ten_doi         VARCHAR(100)  NOT NULL,           -- 'New York Knicks'
    ten_viet_tat    VARCHAR(5)    NOT NULL,           -- 'NYK' (mã đội giống Basketball-Reference)
    thanh_pho       VARCHAR(100)  NOT NULL,
    ma_san          INT,                              -- sân nhà
    nam_thanh_lap   YEAR,
    logo            VARCHAR(255),
    mo_ta           TEXT,
    PRIMARY KEY (ma_doi),
    UNIQUE KEY uq_ten_doi (ten_doi),
    UNIQUE KEY uq_ten_viet_tat (ten_viet_tat),
    CONSTRAINT fk_doibong_san FOREIGN KEY (ma_san)
        REFERENCES san_thi_dau (ma_san)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
 
--  CẤP 3 
-- 8. Huấn luyện viên (Coach)
CREATE TABLE huan_luyen_vien (
    ma_hlv         INT           NOT NULL AUTO_INCREMENT,
    ho_ten         VARCHAR(100)  NOT NULL,
    ngay_sinh      DATE,
    quoc_tich      VARCHAR(50),
    ma_doi         INT,                               -- NULL = đang tự do
    vai_tro        ENUM('truong','pho') NOT NULL DEFAULT 'pho',
    ngay_bat_dau   DATE,                              -- ngày bắt đầu dẫn dắt đội hiện tại
    anh            VARCHAR(255),
    PRIMARY KEY (ma_hlv),
    CONSTRAINT fk_hlv_doibong FOREIGN KEY (ma_doi)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
-- "Mỗi đội chỉ 1 HLV trưởng" kiểm tra bằng PHP (TV2).
-- Thành tích W-L của HLV tính từ bảng tran_dau, không lưu.
 
-- 9. Cầu thủ (Player) - các cột theo bảng Roster của Basketball-Reference:
--    No., Player, Pos, Ht, Wt, Birth Date, Birth, Exp, College
CREATE TABLE cau_thu (
    ma_cau_thu       INT           NOT NULL AUTO_INCREMENT,
    ho_ten           VARCHAR(100)  NOT NULL,
    ngay_sinh        DATE,
    noi_sinh         VARCHAR(100),                    -- thành phố / bang
    quoc_tich        VARCHAR(50),                     -- cột "Birth" (quốc gia)
    chieu_cao_cm     SMALLINT      CHECK (chieu_cao_cm BETWEEN 150 AND 250),
    can_nang_kg      SMALLINT      CHECK (can_nang_kg BETWEEN 50 AND 200),
    vi_tri           ENUM('PG','SG','SF','PF','C') NOT NULL,
    tay_nem          ENUM('phai','trai') DEFAULT 'phai', -- "Shoots: Right/Left"
    so_ao            TINYINT       CHECK (so_ao BETWEEN 0 AND 99),
    truong_dai_hoc   VARCHAR(100),                    -- "College" (NULL nếu không học ĐH)
    nam_ra_mat_nba   YEAR,                            -- dùng để tính "Exp" (số năm kinh nghiệm)
    ma_doi           INT,                             -- đội hiện tại, NULL = tự do
    anh              VARCHAR(255),
    trang_thai       ENUM('dang_thi_dau','chan_thuong','tu_do','giai_nghe')
                     NOT NULL DEFAULT 'dang_thi_dau',
    PRIMARY KEY (ma_cau_thu),
    UNIQUE KEY uq_doi_so_ao (ma_doi, so_ao),          -- không trùng số áo trong 1 đội
    CONSTRAINT fk_cauthu_doibong FOREIGN KEY (ma_doi)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
 
-- 10. Phân bảng: đội nào thuộc khu vực nào trong từng mùa
CREATE TABLE phan_bang (
    ma_phan_bang   INT  NOT NULL AUTO_INCREMENT,
    ma_mua_giai    INT  NOT NULL,
    ma_doi         INT  NOT NULL,
    ma_khu_vuc     INT  NOT NULL,
    PRIMARY KEY (ma_phan_bang),
    UNIQUE KEY uq_mua_doi (ma_mua_giai, ma_doi),
    CONSTRAINT fk_phanbang_muagiai FOREIGN KEY (ma_mua_giai)
        REFERENCES mua_giai (ma_mua_giai)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_phanbang_doibong FOREIGN KEY (ma_doi)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_phanbang_khuvuc FOREIGN KEY (ma_khu_vuc)
        REFERENCES khu_vuc (ma_khu_vuc)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
 
-- 11. Loạt đấu Playoff (Series) - VD: "2026 NBA Finals: Knicks vs Spurs"
--     Số trận thắng mỗi đội đếm từ bảng tran_dau, không lưu.
CREATE TABLE loat_playoff (
    ma_loat        INT      NOT NULL AUTO_INCREMENT,
    ma_mua_giai    INT      NOT NULL,
    vong           ENUM('vong_1','ban_ket_mien','chung_ket_mien','chung_ket') NOT NULL,
    ma_doi_1       INT      NOT NULL,                 -- đội hạt giống cao hơn (có lợi thế sân nhà)
    ma_doi_2       INT      NOT NULL,
    hat_giong_1    TINYINT  CHECK (hat_giong_1 BETWEEN 1 AND 8),
    hat_giong_2    TINYINT  CHECK (hat_giong_2 BETWEEN 1 AND 8),
    ma_doi_thang   INT,                               -- NULL khi loạt chưa kết thúc
    PRIMARY KEY (ma_loat),
    CHECK (ma_doi_1 <> ma_doi_2),
    CONSTRAINT fk_loat_muagiai FOREIGN KEY (ma_mua_giai)
        REFERENCES mua_giai (ma_mua_giai)
        ON UPDATE CASCADE ON DELETE CASCADE,
    -- 2 khóa ngoại dưới dùng RESTRICT: không cho xóa / đổi mã đội đã có loạt playoff
    CONSTRAINT fk_loat_doi1 FOREIGN KEY (ma_doi_1)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_loat_doi2 FOREIGN KEY (ma_doi_2)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_loat_doithang FOREIGN KEY (ma_doi_thang)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
 
-- 12. Trận đấu (Game) - thông tin đầu trang box score:
--     ngày giờ, sân, khán giả, giai đoạn, trận thứ mấy của loạt playoff
CREATE TABLE tran_dau (
    ma_tran            INT       NOT NULL AUTO_INCREMENT,
    ma_mua_giai        INT       NOT NULL,
    ma_doi_nha         INT       NOT NULL,
    ma_doi_khach       INT       NOT NULL,
    ma_san             INT,
    thoi_gian          DATETIME  NOT NULL,
    giai_doan          ENUM('thuong','playin','playoff') NOT NULL DEFAULT 'thuong',
    ma_loat            INT,                           -- chỉ có khi giai_doan = 'playoff'
    tran_so_trong_loat TINYINT   CHECK (tran_so_trong_loat BETWEEN 1 AND 7),
    diem_doi_nha       SMALLINT  CHECK (diem_doi_nha >= 0),   -- NULL khi chưa đấu
    diem_doi_khach     SMALLINT  CHECK (diem_doi_khach >= 0),
    khan_gia           INT       CHECK (khan_gia >= 0),       -- "Attendance"
    trang_thai         ENUM('chua_dau','da_dau','hoan') NOT NULL DEFAULT 'chua_dau',
    PRIMARY KEY (ma_tran),
    UNIQUE KEY uq_loat_tran_so (ma_loat, tran_so_trong_loat), -- không trùng "Game 3" trong 1 loạt
    CHECK (ma_doi_nha <> ma_doi_khach),
    CHECK (trang_thai <> 'da_dau' OR diem_doi_nha <> diem_doi_khach), -- bóng rổ không có hòa
    CONSTRAINT fk_trandau_muagiai FOREIGN KEY (ma_mua_giai)
        REFERENCES mua_giai (ma_mua_giai)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_trandau_doinha FOREIGN KEY (ma_doi_nha)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE RESTRICT ON DELETE RESTRICT,    -- chặn xóa đội đã có trận
    CONSTRAINT fk_trandau_doikhach FOREIGN KEY (ma_doi_khach)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_trandau_san FOREIGN KEY (ma_san)
        REFERENCES san_thi_dau (ma_san)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_trandau_loat FOREIGN KEY (ma_loat)
        REFERENCES loat_playoff (ma_loat)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
 
--  CẤP 4 
-- 13. Phân công trọng tài cho trận (mỗi trận NBA có 3 trọng tài)
CREATE TABLE phan_cong_trong_tai (
    ma_tran        INT  NOT NULL,
    ma_trong_tai   INT  NOT NULL,
    PRIMARY KEY (ma_tran, ma_trong_tai),              -- khóa chính gồm 2 cột
    CONSTRAINT fk_pctt_trandau FOREIGN KEY (ma_tran)
        REFERENCES tran_dau (ma_tran)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pctt_trongtai FOREIGN KEY (ma_trong_tai)
        REFERENCES trong_tai (ma_trong_tai)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
 
-- 14. Điểm theo hiệp (Line score): hiệp 1-4 = Q1-Q4, hiệp 5, 6... = OT1, OT2...
CREATE TABLE diem_hiep (
    ma_tran   INT       NOT NULL,
    ma_doi    INT       NOT NULL,
    hiep      TINYINT   NOT NULL CHECK (hiep >= 1),
    diem      SMALLINT  NOT NULL DEFAULT 0 CHECK (diem >= 0),
    PRIMARY KEY (ma_tran, ma_doi, hiep),              -- khóa chính gồm 3 cột
    CONSTRAINT fk_diemhiep_trandau FOREIGN KEY (ma_tran)
        REFERENCES tran_dau (ma_tran)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_diemhiep_doibong FOREIGN KEY (ma_doi)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
 
-- 15. Chỉ số cầu thủ từng trận (Basic Box Score Stats của Basketball-Reference)
--     MP, FG, FGA, 3P, 3PA, FT, FTA, ORB, DRB, AST, STL, BLK, TOV, PF, PTS, +/-
--     Các cột tính được (FG%, 3P%, FT%, TRB, GmSc...) KHÔNG lưu, tính khi hiển thị.
CREATE TABLE chi_so_tran_dau (
    ma_chi_so            INT       NOT NULL AUTO_INCREMENT,
    ma_tran              INT       NOT NULL,
    ma_cau_thu           INT       NOT NULL,
    ma_doi               INT       NOT NULL,          -- đội cầu thủ khoác áo trong trận đó
    xuat_phat            TINYINT(1) NOT NULL DEFAULT 0,     -- 1 = Starters, 0 = Reserves
    trang_thai           ENUM('thi_dau','khong_thi_dau','vang_mat')
                         NOT NULL DEFAULT 'thi_dau',        -- 'Did Not Play' / Inactive
    so_phut              TINYINT   NOT NULL DEFAULT 0 CHECK (so_phut <= 70),  -- MP
    nem_trung            TINYINT   NOT NULL DEFAULT 0,      -- FG  (gồm cả cú 3 điểm)
    nem_thu              TINYINT   NOT NULL DEFAULT 0,      -- FGA
    ba_diem_trung        TINYINT   NOT NULL DEFAULT 0,      -- 3P
    ba_diem_thu          TINYINT   NOT NULL DEFAULT 0,      -- 3PA
    phat_trung           TINYINT   NOT NULL DEFAULT 0,      -- FT
    phat_thu             TINYINT   NOT NULL DEFAULT 0,      -- FTA
    bat_bong_tan_cong    TINYINT   NOT NULL DEFAULT 0,      -- ORB
    bat_bong_phong_thu   TINYINT   NOT NULL DEFAULT 0,      -- DRB
    kien_tao             TINYINT   NOT NULL DEFAULT 0,      -- AST
    cuop_bong            TINYINT   NOT NULL DEFAULT 0,      -- STL
    chan_bong            TINYINT   NOT NULL DEFAULT 0,      -- BLK
    mat_bong             TINYINT   NOT NULL DEFAULT 0,      -- TOV
    pham_loi             TINYINT   NOT NULL DEFAULT 0,      -- PF
    diem                 SMALLINT  NOT NULL DEFAULT 0,      -- PTS
    cong_tru             SMALLINT  NOT NULL DEFAULT 0,      -- +/- (có thể âm)
    PRIMARY KEY (ma_chi_so),
    UNIQUE KEY uq_tran_cauthu (ma_tran, ma_cau_thu),        -- 1 dòng / cầu thủ / trận
    CHECK (nem_trung <= nem_thu),
    CHECK (ba_diem_trung <= ba_diem_thu),
    CHECK (ba_diem_trung <= nem_trung),
    CHECK (ba_diem_thu <= nem_thu),
    CHECK (phat_trung <= phat_thu),
    CHECK (pham_loi <= 6),
    CHECK (diem = 2 * nem_trung + ba_diem_trung + phat_trung), -- PTS = 2*FG + 3P + FT
    CONSTRAINT fk_chiso_trandau FOREIGN KEY (ma_tran)
        REFERENCES tran_dau (ma_tran)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_chiso_cauthu FOREIGN KEY (ma_cau_thu)
        REFERENCES cau_thu (ma_cau_thu)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_chiso_doibong FOREIGN KEY (ma_doi)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
 
-- 16. Giao dịch / chuyển nhượng (Transactions)
CREATE TABLE chuyen_nhuong (
    ma_chuyen_nhuong  INT   NOT NULL AUTO_INCREMENT,
    ma_cau_thu        INT   NOT NULL,
    loai              ENUM('trao_doi','ky_hop_dong','giai_phong','tuyen_chon_draft')
                      NOT NULL,                       -- traded / signed / waived / drafted
    ma_doi_cu         INT,                            -- NULL = trước đó tự do
    ma_doi_moi        INT,                            -- NULL = trở thành tự do
    ngay_chuyen       DATE  NOT NULL,
    ghi_chu           VARCHAR(255),
    PRIMARY KEY (ma_chuyen_nhuong),
    CONSTRAINT fk_chuyennhuong_cauthu FOREIGN KEY (ma_cau_thu)
        REFERENCES cau_thu (ma_cau_thu)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_chuyennhuong_doicu FOREIGN KEY (ma_doi_cu)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_chuyennhuong_doimoi FOREIGN KEY (ma_doi_moi)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
 
-- 17. Chấn thương
CREATE TABLE chan_thuong (
    ma_chan_thuong         INT           NOT NULL AUTO_INCREMENT,
    ma_cau_thu             INT           NOT NULL,
    loai_chan_thuong       VARCHAR(100)  NOT NULL,    -- 'Bong gân cổ chân'
    ngay_bi                DATE          NOT NULL,
    ngay_du_kien_tro_lai   DATE,
    da_hoi_phuc            TINYINT(1)    NOT NULL DEFAULT 0,
    ghi_chu                VARCHAR(255),
    PRIMARY KEY (ma_chan_thuong),
    CONSTRAINT fk_chanthuong_cauthu FOREIGN KEY (ma_cau_thu)
        REFERENCES cau_thu (ma_cau_thu)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
 
-- 18. Trao giải: giải thưởng của mùa nào, trao cho cầu thủ hoặc HLV nào
CREATE TABLE trao_giai (
    ma_trao_giai     INT  NOT NULL AUTO_INCREMENT,
    ma_mua_giai      INT  NOT NULL,
    ma_giai_thuong   INT  NOT NULL,
    ma_cau_thu       INT,                             -- điền 1 trong 2 cột này
    ma_hlv           INT,
    PRIMARY KEY (ma_trao_giai),
    UNIQUE KEY uq_mua_giai_thuong (ma_mua_giai, ma_giai_thuong), -- mỗi giải 1 người / mùa
    CHECK ((ma_cau_thu IS NULL) <> (ma_hlv IS NULL)),
    CONSTRAINT fk_traogiai_muagiai FOREIGN KEY (ma_mua_giai)
        REFERENCES mua_giai (ma_mua_giai)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_traogiai_giaithuong FOREIGN KEY (ma_giai_thuong)
        REFERENCES giai_thuong (ma_giai_thuong)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_traogiai_cauthu FOREIGN KEY (ma_cau_thu)
        REFERENCES cau_thu (ma_cau_thu)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_traogiai_hlv FOREIGN KEY (ma_hlv)
        REFERENCES huan_luyen_vien (ma_hlv)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;
 
-- 19. Tài khoản đăng nhập
CREATE TABLE tai_khoan (
    ma_tai_khoan    INT           NOT NULL AUTO_INCREMENT,
    ten_dang_nhap   VARCHAR(50)   NOT NULL,
    mat_khau        VARCHAR(255)  NOT NULL,           -- chuỗi password_hash()
    vai_tro         ENUM('admin','cau_thu') NOT NULL DEFAULT 'cau_thu',
    ma_cau_thu      INT,                              -- chỉ có khi vai_tro = 'cau_thu'
    bi_khoa         TINYINT(1)    NOT NULL DEFAULT 0,
    ngay_tao        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ma_tai_khoan),
    UNIQUE KEY uq_ten_dang_nhap (ten_dang_nhap),
    UNIQUE KEY uq_tk_cauthu (ma_cau_thu),
    CONSTRAINT fk_taikhoan_cauthu FOREIGN KEY (ma_cau_thu)
        REFERENCES cau_thu (ma_cau_thu)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
 
-- 20. Nhiệm kỳ HLV theo mùa (TV2) - để xem được nhiều mùa giải:
--     HLV nào dẫn dắt đội nào, trong mùa nào, từ ngày nào đến ngày nào.
--     Mỗi mùa 1 dòng: HLV dẫn dắt 3 mùa liên tiếp = 3 dòng.
--     Thay HLV giữa mùa = 2 dòng cùng mùa (người cũ có den_ngay, người mới có tu_ngay).
--     huan_luyen_vien.ma_doi / vai_tro vẫn là "hiện tại"; bảng này lưu lịch sử.
--     "Mỗi đội chỉ 1 HLV trưởng tại một thời điểm" kiểm tra bằng PHP (TV2).
CREATE TABLE nhiem_ky_hlv (
    ma_nhiem_ky    INT         NOT NULL AUTO_INCREMENT,
    ma_hlv         INT         NOT NULL,
    ma_doi         INT         NOT NULL,
    ma_mua_giai    INT         NOT NULL,
    vai_tro        ENUM('truong','pho') NOT NULL DEFAULT 'truong',
    tam_quyen      TINYINT(1)  NOT NULL DEFAULT 0,   -- 1 = HLV tạm quyền (interim)
    tu_ngay        DATE        NOT NULL,             -- ngày bắt đầu dẫn dắt trong mùa này
    den_ngay       DATE,                             -- NULL = dẫn dắt đến hết mùa
    PRIMARY KEY (ma_nhiem_ky),
    UNIQUE KEY uq_nhiem_ky (ma_hlv, ma_doi, ma_mua_giai, tu_ngay),
    CHECK (den_ngay IS NULL OR den_ngay >= tu_ngay),
    CONSTRAINT fk_nhiemky_hlv FOREIGN KEY (ma_hlv)
        REFERENCES huan_luyen_vien (ma_hlv)
        ON UPDATE CASCADE ON DELETE RESTRICT,        -- chặn xóa HLV đã có lịch sử
    CONSTRAINT fk_nhiemky_doibong FOREIGN KEY (ma_doi)
        REFERENCES doi_bong (ma_doi)
        ON UPDATE CASCADE ON DELETE RESTRICT,        -- chặn xóa đội đã có lịch sử HLV
    CONSTRAINT fk_nhiemky_muagiai FOREIGN KEY (ma_mua_giai)
        REFERENCES mua_giai (ma_mua_giai)
        ON UPDATE CASCADE ON DELETE CASCADE          -- xóa mùa thì xóa nhiệm kỳ của mùa đó
) ENGINE=InnoDB;
 
--  DỮ LIỆU DANH MỤC CỐ ĐỊNH (không thay đổi giữa các mùa) 
INSERT INTO hoi_nghi (ma_hoi_nghi, ten_hoi_nghi) VALUES
    (1, 'Miền Đông'),
    (2, 'Miền Tây');
   
INSERT INTO khu_vuc (ten_khu_vuc, ma_hoi_nghi) VALUES
    ('Đại Tây Dương',   1),
    ('Trung Tâm',       1),
    ('Đông Nam',        1),
    ('Tây Bắc',         2),
    ('Thái Bình Dương', 2),
    ('Tây Nam',         2);
 
INSERT INTO giai_thuong (ten_viet_tat, ten_giai, doi_tuong) VALUES
    ('MVP',  'Cầu thủ xuất sắc nhất mùa giải',             'cau_thu'),
    ('ROY',  'Tân binh xuất sắc nhất',                     'cau_thu'),
    ('DPOY', 'Cầu thủ phòng ngự xuất sắc nhất',            'cau_thu'),
    ('SMOY', 'Cầu thủ dự bị xuất sắc nhất',                'cau_thu'),
    ('MIP',  'Cầu thủ tiến bộ nhất',                       'cau_thu'),
    ('CPOY', 'Cầu thủ xuất sắc nhất thời khắc quyết định', 'cau_thu'),
    ('FMVP', 'Cầu thủ xuất sắc nhất loạt Chung kết',       'cau_thu'),
    ('COY',  'Huấn luyện viên xuất sắc nhất',              'hlv');
 
-- Tài khoản admin mặc định: admin / admin123  (nhớ đổi mật khẩu khi demo xong)
-- Chuỗi bên dưới là kết quả password_hash('admin123', PASSWORD_DEFAULT).
-- Muốn đổi mật khẩu: tạo chuỗi mới bằng PHP: password_hash('matkhaumoi', PASSWORD_DEFAULT)
INSERT INTO tai_khoan (ten_dang_nhap, mat_khau, vai_tro) VALUES
    ('admin', '$2y$10$MNoWc9mSrMio1xpemtHVLuuSgpPkd5kjuLmjTIr7IaqnfYCZZC94u', 'admin');
 
--  VIEW: các bảng "ảo" tính sẵn số liệu, PHP chỉ cần SELECT

-- V1. Chỉ số trung bình mỗi trận của cầu thủ theo mùa (giống bảng Per Game)
--     Dùng cho: hồ sơ cầu thủ, trang Top cầu thủ (Leaders)
CREATE VIEW v_chi_so_trung_binh AS
SELECT
    td.ma_mua_giai,
    td.giai_doan,
    cs.ma_cau_thu,
    COUNT(*)                                                    AS so_tran,          -- G
    SUM(cs.xuat_phat)                                           AS so_tran_xuat_phat,-- GS
    ROUND(AVG(cs.so_phut), 1)                                   AS phut_tb,          -- MP
    ROUND(AVG(cs.diem), 1)                                      AS diem_tb,          -- PTS
    ROUND(AVG(cs.bat_bong_tan_cong + cs.bat_bong_phong_thu), 1) AS bat_bong_tb,      -- TRB
    ROUND(AVG(cs.kien_tao), 1)                                  AS kien_tao_tb,      -- AST
    ROUND(AVG(cs.cuop_bong), 1)                                 AS cuop_bong_tb,     -- STL
    ROUND(AVG(cs.chan_bong), 1)                                 AS chan_bong_tb,     -- BLK
    ROUND(AVG(cs.mat_bong), 1)                                  AS mat_bong_tb,      -- TOV
    ROUND(100 * SUM(cs.nem_trung)     / NULLIF(SUM(cs.nem_thu), 0), 1)     AS ti_le_nem,    -- FG%
    ROUND(100 * SUM(cs.ba_diem_trung) / NULLIF(SUM(cs.ba_diem_thu), 0), 1) AS ti_le_3_diem, -- 3P%
    ROUND(100 * SUM(cs.phat_trung)    / NULLIF(SUM(cs.phat_thu), 0), 1)    AS ti_le_phat    -- FT%
FROM chi_so_tran_dau cs
JOIN tran_dau td ON td.ma_tran = cs.ma_tran
WHERE cs.trang_thai = 'thi_dau'
  AND td.trang_thai = 'da_dau'
GROUP BY td.ma_mua_giai, td.giai_doan, cs.ma_cau_thu;
 
-- V2. Bảng xếp hạng mùa thường (W, L, W/L%, GB, sân nhà, sân khách, điểm ghi / bị ghi)
--     GB (cách đội đầu hội nghị) = ((W_dau - L_dau) - (W - L)) / 2
CREATE VIEW v_bang_xep_hang AS
SELECT
    pb.ma_mua_giai,
    pb.ma_doi,
    pb.ma_khu_vuc,
    kv.ma_hoi_nghi,
    COALESCE(k.thang, 0)            AS thang,
    COALESCE(k.thua, 0)             AS thua,
    ROUND(COALESCE(k.thang, 0) / NULLIF(COALESCE(k.thang, 0) + COALESCE(k.thua, 0), 0), 3)
                                    AS ti_le_thang,
    ( MAX(COALESCE(k.thang, 0) - COALESCE(k.thua, 0))
          OVER (PARTITION BY pb.ma_mua_giai, kv.ma_hoi_nghi)
      - (COALESCE(k.thang, 0) - COALESCE(k.thua, 0)) ) / 2
                                    AS cach_dau_bang,
    COALESCE(k.thang_nha, 0)        AS thang_san_nha,
    COALESCE(k.thua_nha, 0)         AS thua_san_nha,
    COALESCE(k.thang_khach, 0)      AS thang_san_khach,
    COALESCE(k.thua_khach, 0)       AS thua_san_khach,
    COALESCE(k.diem_ghi, 0)         AS tong_diem_ghi,
    COALESCE(k.diem_bi_ghi, 0)      AS tong_diem_bi_ghi
FROM phan_bang pb
JOIN khu_vuc kv ON kv.ma_khu_vuc = pb.ma_khu_vuc
LEFT JOIN (
    SELECT ma_mua_giai, ma_doi,
           SUM(thang) AS thang, SUM(thua) AS thua,
           SUM(thang_nha) AS thang_nha, SUM(thua_nha) AS thua_nha,
           SUM(thang_khach) AS thang_khach, SUM(thua_khach) AS thua_khach,
           SUM(diem_ghi) AS diem_ghi, SUM(diem_bi_ghi) AS diem_bi_ghi
    FROM (
        -- Kết quả nhìn từ phía đội chủ nhà
        SELECT ma_mua_giai, ma_doi_nha AS ma_doi,
               (diem_doi_nha > diem_doi_khach) AS thang,
               (diem_doi_nha < diem_doi_khach) AS thua,
               (diem_doi_nha > diem_doi_khach) AS thang_nha,
               (diem_doi_nha < diem_doi_khach) AS thua_nha,
               0 AS thang_khach, 0 AS thua_khach,
               diem_doi_nha AS diem_ghi, diem_doi_khach AS diem_bi_ghi
        FROM tran_dau
        WHERE trang_thai = 'da_dau' AND giai_doan = 'thuong'
        UNION ALL
        -- Kết quả nhìn từ phía đội khách
        SELECT ma_mua_giai, ma_doi_khach AS ma_doi,
               (diem_doi_khach > diem_doi_nha) AS thang,
               (diem_doi_khach < diem_doi_nha) AS thua,
               0 AS thang_nha, 0 AS thua_nha,
               (diem_doi_khach > diem_doi_nha) AS thang_khach,
               (diem_doi_khach < diem_doi_nha) AS thua_khach,
               diem_doi_khach AS diem_ghi, diem_doi_nha AS diem_bi_ghi
        FROM tran_dau
        WHERE trang_thai = 'da_dau' AND giai_doan = 'thuong'
    ) ket_qua
    GROUP BY ma_mua_giai, ma_doi
) k ON k.ma_mua_giai = pb.ma_mua_giai AND k.ma_doi = pb.ma_doi;