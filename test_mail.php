<?php
require __DIR__ . '/includes/config.php';
$r = send_otp_email('yassen74mostafa@gmail.com', '654321', 'login');
var_dump($r);
echo $r ? "SUCCESS" : "FAILED";
