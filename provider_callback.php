<?php
require __DIR__ . '/config.php';

function http_json($method, $url, $data = [], $headers = [])
{
    $ch = curl_init();
    $method = strtoupper($method);

    if ($method === 'GET' && !empty($data)) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => json_decode($body, true),
    ];
}

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code || !$state || !hash_equals($_SESSION['oauth_state'], $state)) {
    die("OAuth state mismatch");
}
unset($_SESSION['oauth_state']);

/* 1) code → Health ID token */
$tokenRes = http_json('POST', 'https://moph.id.th/api/v1/token', [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $_ENV['HEALTH_REDIRECT'],
    'client_id' => $_ENV['HEALTH_CLIENT_ID'],
    'client_secret' => $_ENV['HEALTH_CLIENT_SECRET'],
]);

$healthToken = $tokenRes['data']['data']['access_token'] ?? null;
if (!$healthToken)
    die("Health ID token error");

/* 2) Health token → Provider token */
$providerTokenRes = http_json('POST', 'https://provider.id.th/api/v1/services/token', [
    'client_id' => $_ENV['PROVIDER_CLIENT_ID'],
    'secret_key' => $_ENV['PROVIDER_SECRET_KEY'],
    'token_by' => 'Health ID',
    'token' => $healthToken,
]);

$providerToken = $providerTokenRes['data']['data']['access_token'] ?? null;
if (!$providerToken)
    die("Provider token error");

/* 3) Provider token → profile */
$profileRes = http_json('GET', 'https://provider.id.th/api/v1/services/profile', [], [
    'client-id: ' . $_ENV['PROVIDER_CLIENT_ID'],
    'secret-key: ' . $_ENV['PROVIDER_SECRET_KEY'],
    'Authorization: Bearer ' . $providerToken,
]);

$profile = $profileRes['data']['data'] ?? null;
if (!$profile)
    die("Provider profile error");

/* Extract data */
$provider_id = $profile['provider_id'];
$fullname = trim(($profile['firstname_th'] ?? '') . ' ' . ($profile['lastname_th'] ?? ''));
$hcode = $profile['organization'][0]['hcode'] ?? null;

/* Check HOS_CODE */
if ($hcode !== $_ENV['HOS_CODE']) {
    $_SESSION['provider_error'] = "❌ คุณไม่ใช่บุคลากรในหน่วยงาน<?= $hospital ?>";
    header("Location: index.php");
    exit;
}

/* Check user */
$stmt = $conn->prepare("SELECT id, role, active FROM user WHERE provider_id=? LIMIT 1");
$stmt->bind_param("s", $provider_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {

    /* First login → create user */
    $stmt2 = $conn->prepare("
    INSERT INTO user (username, password, role, active, provider_id, fullname, hos_code)
    VALUES (?, '-', 'user', 0, ?, ?, ?)
");
    $stmt2->bind_param("ssss", $provider_id, $provider_id, $fullname, $hcode);
    $stmt2->execute();

    /* ⭐ ส่งข้อความไปหน้า index.php ผ่าน session */
    $_SESSION['provider_new_user'] = "สร้างบัญชีใหม่แล้ว กรุณาให้ Admin เปิดการใช้งาน";

    header("Location: index.php");
    exit;

}

$stmt->bind_result($uid, $role, $active);
$stmt->fetch();

/* Update fullname only */
$stmt2 = $conn->prepare("UPDATE user SET fullname=?, hos_code=? WHERE provider_id=?");
$stmt2->bind_param("sss", $fullname, $hcode, $provider_id);
$stmt2->execute();

/* Check active */
if ($active == 0) {
    die("บัญชีของคุณยังไม่เปิดใช้งาน");
}

/* Login */
$_SESSION['user_id'] = $uid;
$_SESSION['login_time'] = date("Y-m-d H:i:s");

header("Location: index.php");
exit;
