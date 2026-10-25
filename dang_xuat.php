<?php
require_once __DIR__ . '/includes/init.php';

unset($_SESSION['ma_tai_khoan'], $_SESSION['ten_dang_nhap'], $_SESSION['vai_tro'], $_SESSION['ma_cau_thu']);
session_regenerate_id(true);

datThongBao('ok', 'Bạn đã đăng xuất.');
chuyenTrang(url('/index.php'));
