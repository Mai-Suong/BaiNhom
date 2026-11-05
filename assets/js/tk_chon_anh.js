/**
 * TV5 - Ô chọn có ảnh (logo đội, ảnh cầu thủ) cho thẻ <select data-chon-anh="tran|cau_thu|doi|san">.
 * Dùng thư viện Tom Select (nạp từ CDN trong tkChonCoAnh() - includes/ham_thong_ke.php).
 * Mỗi <option> mang thêm data-logo1, data-vt1, data-mau1... do PHP tạo (tkDuLieuOpt), Tom Select đọc vào dữ liệu lựa chọn.
 * Không tải được Tom Select (mất mạng) thì giữ nguyên <select> thường, trang vẫn chạy.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.TomSelect) {
        return;
    }

    var thoat = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    /** Vòng tròn chữ (tên viết tắt đội / chữ đầu họ tên), màu nền theo đội */
    var vongChu = function (chu, mau, co, an) {
        return '<span class="tk-chon-chu' + (an ? ' d-none' : '') + '" style="width:' + co + 'px;height:' + co + 'px;'
            + 'font-size:' + Math.max(8, Math.round(co / 3)) + 'px;background:' + thoat(mau || '#17408b') + '">'
            + thoat(chu) + '</span>';
    };

    /** Ảnh (logo / chân dung); lỗi tải thì hiện vòng tròn chữ */
    var anh = function (url, chu, mau, co, lop) {
        if (!url) {
            return vongChu(chu, mau, co, false);
        }
        return '<img class="' + lop + '" src="' + thoat(url) + '" alt="" width="' + co + '" height="' + co + '" loading="lazy"'
            + ' onerror="this.hidden=true;this.nextElementSibling.classList.remove(\'d-none\')">'
            + vongChu(chu, mau, co, true);
    };

    var mau = {
        // Trận: [logo khách] KHÁCH @ [logo nhà] NHÀ · ngày giờ (ghi chú)
        tran: function (d) {
            if (!d.vt1) {
                return '<div class="tk-chon-dong text-muted">' + thoat(d.text) + '</div>';
            }
            return '<div class="tk-chon-dong">'
                + anh(d.logo1, d.vt1, d.mau1, 24, 'tk-chon-logo') + '<strong>' + thoat(d.vt1) + '</strong>'
                + '<span class="text-muted">@</span>'
                + anh(d.logo2, d.vt2, d.mau2, 24, 'tk-chon-logo') + '<strong>' + thoat(d.vt2) + '</strong>'
                + '<span class="tk-chon-phu">' + thoat(d.ngay) + (d.ghichu ? ' · ' + thoat(d.ghichu) : '') + '</span>'
                + '</div>';
        },
        // Đội: [logo] Tên đội ........ MÃ
        doi: function (d) {
            if (!d.ten) {
                return '<div class="tk-chon-dong text-muted">' + thoat(d.text) + '</div>';
            }
            return '<div class="tk-chon-dong">'
                + anh(d.logo, d.vt, d.mau, 26, 'tk-chon-logo')
                + '<span class="tk-chon-ten">' + thoat(d.ten) + '</span>'
                + '<span class="tk-chon-phu">' + thoat(d.vt) + '</span>'
                + '</div>';
        },
        // Sân: [ảnh chữ nhật] Tên sân ........ Thành phố
        san: function (d) {
            if (!d.ten) {
                return '<div class="tk-chon-dong text-muted">' + thoat(d.text) + '</div>';
            }
            var o = '<span class="tk-chon-san-o%s"><i class="bi bi-building"></i></span>';
            var hinh = d.anh
                ? '<img class="tk-chon-san" src="' + thoat(d.anh) + '" alt="" loading="lazy"'
                  + ' onerror="this.hidden=true;this.nextElementSibling.classList.remove(\'d-none\')">' + o.replace('%s', ' d-none')
                : o.replace('%s', '');
            return '<div class="tk-chon-dong">' + hinh
                + '<span class="tk-chon-ten">' + thoat(d.ten) + '</span>'
                + '<span class="tk-chon-phu">' + thoat(d.tp) + '</span>'
                + '</div>';
        },
        // Cầu thủ: [ảnh] Họ tên · [logo] ĐỘI
        cau_thu: function (d) {
            if (!d.ten) {
                return '<div class="tk-chon-dong text-muted">' + thoat(d.text) + '</div>';
            }
            return '<div class="tk-chon-dong">'
                + anh(d.anh, d.chu, d.mau, 30, 'tk-chon-anh')
                + '<span class="tk-chon-ten">' + thoat(d.ten) + '</span>'
                + (d.vt ? '<span class="tk-chon-phu">' + anh(d.logo, d.vt, d.mau, 18, 'tk-chon-logo') + thoat(d.vt) + '</span>' : '')
                + '</div>';
        }
    };

    document.querySelectorAll('select[data-chon-anh]').forEach(function (sel) {
        var hien = mau[sel.getAttribute('data-chon-anh')] || mau.cau_thu;
        new TomSelect(sel, {
            maxOptions: null,            // hiện đủ mọi lựa chọn, không cắt ở 50
            searchField: ['text'],       // gõ để tìm theo chữ của option (tên, mã đội, ngày)
            render: {
                option: hien,
                item: hien,
                no_results: function () { return '<div class="no-results">Không tìm thấy</div>'; }
            }
        });
    });
});
