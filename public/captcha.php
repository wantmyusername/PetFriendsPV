<?php
require __DIR__ . '/pf-config.php';
session_start();

// Suma o multiplicación aleatoria.
$a  = random_int(2, 9);
$b  = random_int(2, 9);
$op = random_int(0, 1) ? '×' : '+';
$_SESSION['captcha_answer'] = $op === '×' ? $a * $b : $a + $b;

// Token firmado (HMAC) ligado a la sesión y al tiempo.
$ts    = time();
$nonce = bin2hex(random_bytes(8));
$_SESSION['captcha_nonce'] = $nonce;
$token = hash_hmac('sha256', $ts . '|' . $nonce, $PF_SECRET);

header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(['q' => "$a $op $b", 'ts' => $ts, 'token' => $token]);
