<?php
/**
 * Pet Friends PV - receptor del formulario de contacto.
 * Requiere PHP con la función mail() habilitada (hosting compartido estándar).
 *
 * Edita solo la sección CONFIGURACIÓN con los datos reales.
 */

// ==================== CONFIGURACIÓN ====================
require __DIR__ . '/pf-config.php';           // define $PF_SECRET

// Puedes poner varios destinatarios separados por coma: 'a@x.com, b@y.com'
$TO      = 'petfriendspv@gmail.com';          // destinatario (aquí llegan los correos)
$FROM    = 'noreply@petfriendspv.com';        // remitente DE TU DOMINIO (crear en cPanel)
$SUBJECT = 'Nueva solicitud desde petfriendspv.com';

$RATE_MAX      = 5;      // envíos máximos por IP
$RATE_WINDOW   = 3600;   // ventana del rate limit (segundos)
$TOKEN_MAX_AGE = 7200;   // validez del token (segundos)
// =======================================================

session_start();

function respond($ok, $error = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['ok' => $ok, 'error' => $error]);
    exit;
}

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Rate limit por IP guardado en un archivo fuera de la raíz web.
function rate_limit($ip, $max, $window) {
    $file = dirname(__DIR__) . '/.pf_ratelimit.json';
    if (!is_file($file) && !is_writable(dirname($file))) {
        return true; // fail-open si no se puede escribir
    }
    $now  = time();
    $data = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        if ($raw) { $data = json_decode($raw, true) ?: []; }
    }
    foreach ($data as $k => $v) {
        if (($v['t'] ?? 0) < $now - $window) { unset($data[$k]); }
    }
    $entry = $data[$ip] ?? ['t' => $now, 'n' => 0];
    $entry['n']++;
    $data[$ip] = $entry;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return $entry['n'] <= $max;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'method', 405);
}

// 1) Origin/Referer: el POST debe venir de nuestro propio dominio.
$host   = $_SERVER['HTTP_HOST'] ?? '';
$origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
if ($host === '' || $origin === '' || strpos($origin, $host) === false) {
    respond(false, 'origin', 403);
}

// 2) Honeypot: si un bot lo rellena, descartamos en silencio.
if (!empty($_POST['website'])) {
    respond(true);
}

// 3) Token firmado + ventana de tiempo (no se puede postear sin cargar el captcha).
$ts    = (int)($_POST['ts'] ?? 0);
$token = (string)($_POST['token'] ?? '');
$nonce = $_SESSION['captcha_nonce'] ?? '';
if ($ts === 0 || $nonce === '') {
    respond(false, 'token', 400);
}
$age = time() - $ts;
if ($age < 3 || $age > $TOKEN_MAX_AGE) {
    respond(false, 'fast', 400);
}
$expected = hash_hmac('sha256', $ts . '|' . $nonce, $PF_SECRET);
if (!hash_equals($expected, $token)) {
    respond(false, 'token', 400);
}

// 4) Captcha de sesión.
$answer = (int)($_POST['captcha'] ?? -1);
if (empty($_SESSION['captcha_answer']) || $answer !== (int)$_SESSION['captcha_answer']) {
    respond(false, 'captcha', 400);
}
unset($_SESSION['captcha_answer'], $_SESSION['captcha_nonce']);

// 5) Rate limit por IP + límite por sesión.
if (!rate_limit(client_ip(), $RATE_MAX, $RATE_WINDOW)) {
    respond(false, 'limit', 429);
}
$_SESSION['sent'] = ($_SESSION['sent'] ?? 0) + 1;
if ($_SESSION['sent'] > 3) {
    respond(false, 'limit', 429);
}

// 6) Campos y saneado (soporta ambos formularios: home y contacto).
$name = trim(strip_tags($_POST['name'] ?? ''));
if ($name === '') {
    $name = trim(strip_tags(($_POST['firstName'] ?? '') . ' ' . ($_POST['lastName'] ?? '')));
}
$emailRaw = trim($_POST['email'] ?? '');
$email    = $emailRaw === '' ? '' : filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
$phone    = trim(strip_tags($_POST['phone'] ?? ''));
$pet      = trim(strip_tags($_POST['pet'] ?? ''));
$message  = trim(strip_tags($_POST['message'] ?? ''));

if ($name === '' || $message === '') {
    respond(false, 'fields', 400);
}
if ($emailRaw !== '' && !$email) {
    respond(false, 'email', 400);
}

// 7) Filtro anti-spam: demasiados enlaces o palabras clave típicas.
if (preg_match_all('/https?:\/\//i', $message) > 2) {
    respond(true); // se descarta silenciosamente
}
$spamWords = ['viagra', 'cialis', 'casino', 'crypto', 'bitcoin', 'forex', 'payday',
              'backlink', 'seo services', 'porn', 'xxx', 'loan offer'];
$haystack = strtolower($name . ' ' . $message);
foreach ($spamWords as $w) {
    if (strpos($haystack, $w) !== false) {
        respond(true); // se descarta silenciosamente
    }
}

// 8) Datos para la plantilla.
$siteUrl = 'https://petfriendspv.com';
$logoUrl = $siteUrl . '/logo.png';

$fields = [['label' => 'Nombre', 'value' => $name]];
if ($pet !== '')   { $fields[] = ['label' => 'Mascota', 'value' => $pet]; }
if ($email !== '') { $fields[] = ['label' => 'Email', 'value' => $email, 'href' => 'mailto:' . $email]; }
if ($phone !== '') { $fields[] = ['label' => 'Teléfono', 'value' => $phone, 'href' => 'tel:' . $phone]; }

// --- Versión texto plano (fallback) ---
$plain  = "Nueva solicitud desde $siteUrl\n\n";
foreach ($fields as $f) {
    $plain .= $f['label'] . ': ' . $f['value'] . "\n";
}
$plain .= "\nMensaje:\n$message\n";

// --- Versión HTML ---
$rowsHtml = '';
foreach ($fields as $f) {
    $label = htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8');
    $val   = htmlspecialchars($f['value'], ENT_QUOTES, 'UTF-8');
    if (!empty($f['href'])) {
        $href = htmlspecialchars($f['href'], ENT_QUOTES, 'UTF-8');
        $val  = '<a href="' . $href . '" style="color:#0091a1;text-decoration:none;">' . $val . '</a>';
    }
    $rowsHtml .= '<tr>'
        . '<td style="padding:5px 0;color:#6b7280;width:90px;vertical-align:top;">' . $label . '</td>'
        . '<td style="padding:5px 0;color:#0a0e35;">' . $val . '</td>'
        . '</tr>';
}

$messageHtml = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

$html = <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nueva solicitud de contacto</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a0e35;">
  <div style="max-width:560px;margin:0 auto;">
    <div style="background:#eef6fb;padding:22px 24px;border-bottom:1px solid #e2edf3;">
      <img src="$logoUrl" alt="Pet Friends Puerto Vallarta" width="120" style="display:block;">
    </div>
    <div style="padding:26px 24px 30px;">
      <h1 style="margin:0 0 2px;font-size:18px;font-weight:bold;color:#0a0e35;">Nueva solicitud de contacto</h1>
      <p style="margin:0 0 24px;font-size:14px;color:#6b7280;">Recibida desde petfriendspv.com</p>
      <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-size:15px;">
        $rowsHtml
      </table>
      <p style="margin:24px 0 6px;font-size:14px;color:#6b7280;">Mensaje</p>
      <p style="margin:0;color:#0a0e35;">$messageHtml</p>
      <div style="border-top:1px solid #e5e7eb;margin:28px 0 14px;"></div>
      <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">
        Pet Friends Veterinary Hospital<br>
        Púlpito 196, Zona Romántica, Puerto Vallarta<br>
        <a href="tel:+523222232760" style="color:#0091a1;text-decoration:none;">+52 322 223 2760</a>
        &nbsp;·&nbsp;
        <a href="$siteUrl" style="color:#0091a1;text-decoration:none;">petfriendspv.com</a>
      </p>
    </div>
  </div>
</body>
</html>
HTML;

// 9) Envío multipart (texto + HTML).
$boundary = '=_pf_' . md5(uniqid((string)time(), true));

$headers  = "From: Pet Friends <$FROM>\r\n";
if ($email !== '') {
    $headers .= "Reply-To: $email\r\n";
}
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";

$body  = "--$boundary\r\n";
$body .= "Content-Type: text/plain; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $plain . "\r\n";
$body .= "--$boundary\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $html . "\r\n";
$body .= "--$boundary--";

$sent = @mail($TO, $SUBJECT, $body, $headers, '-f' . $FROM);
respond((bool)$sent, $sent ? null : 'mail', $sent ? 200 : 500);
