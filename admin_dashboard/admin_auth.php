<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    strtolower($_SESSION['role']) !== 'admin'
) {
    echo "<script>
        alert('กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบ (admin) ก่อนใช้งานหน้านี้');
        window.location.href = '../auth/login.php';
    </script>";
    exit;
}
?>