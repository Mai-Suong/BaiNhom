// JavaScript dùng chung cho cả website.

// Nút xóa: thêm class "btn-xoa" vào nút/link xóa để hỏi xác nhận trước khi xóa.
// VD: <a href="..." class="btn btn-danger btn-sm btn-xoa">Xóa</a>
document.addEventListener('click', function (e) {
    const nut = e.target.closest('.btn-xoa');
    if (nut && !confirm('Bạn có chắc muốn xóa?')) {
        e.preventDefault();
    }
});
