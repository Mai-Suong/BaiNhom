/**
 * Trang chủ (TV2) - băng chuyền "Điểm nhấn": nút trái / phải, tự trượt mỗi 5 giây,
 * dừng khi rê chuột / chạm / đang chọn bằng bàn phím, hết thẻ thì quay về đầu.
 * Người dùng bật "giảm chuyển động" trong hệ điều hành thì không tự trượt.
 */
(function () {
    var bang = document.querySelector('.tc-bang-chuyen');
    if (!bang) {
        return;
    }
    var khu = bang.closest('.tc-diem-nhan');
    var giamChuyenDong = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var dangDung = false;

    /** Độ rộng 1 bước trượt = 1 thẻ + khoảng cách giữa các thẻ */
    function buoc() {
        var the = bang.querySelector('.tc-the');
        if (!the) {
            return bang.clientWidth;
        }
        var khoangCach = parseFloat(getComputedStyle(bang).columnGap || getComputedStyle(bang).gap) || 0;
        return the.getBoundingClientRect().width + khoangCach;
    }

    function truot(huong) {
        var saiSo = 12;                                               // lệch vài px do làm tròn / padding
        var cuoi = bang.scrollWidth - bang.clientWidth - saiSo;
        if (huong > 0 && bang.scrollLeft >= cuoi) {
            bang.scrollTo({ left: 0, behavior: 'smooth' });          // hết thẻ: quay về đầu
        } else if (huong < 0 && bang.scrollLeft <= saiSo) {
            bang.scrollTo({ left: bang.scrollWidth, behavior: 'smooth' });
        } else {
            bang.scrollBy({ left: huong * buoc(), behavior: 'smooth' });
        }
    }

    khu.querySelectorAll('.tc-nut-truot').forEach(function (nut) {
        nut.addEventListener('click', function () {
            truot(parseInt(nut.getAttribute('data-truot'), 10));
        });
    });
    bang.addEventListener('keydown', function (ev) {
        if (ev.key === 'ArrowRight' || ev.key === 'ArrowLeft') {
            ev.preventDefault();
            truot(ev.key === 'ArrowRight' ? 1 : -1);
        }
    });

    ['mouseenter', 'focusin', 'touchstart'].forEach(function (ten) {
        khu.addEventListener(ten, function () { dangDung = true; }, { passive: true });
    });
    ['mouseleave', 'focusout'].forEach(function (ten) {
        khu.addEventListener(ten, function () { dangDung = false; });
    });

    if (!giamChuyenDong) {
        setInterval(function () {
            if (!dangDung && !document.hidden) {
                truot(1);
            }
        }, 5000);
    }
})();
