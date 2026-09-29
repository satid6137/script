<?php
require __DIR__ . '/config.php';

// ------------------------------
// 1) รับ code จาก Health ID redirect
// ------------------------------
$code = $_GET['code'] ?? null;

if (!$code) {
    die("ไม่พบ code จาก Health ID");
}

// ------------------------------
// 2) แลก code → health_access_token
// ------------------------------
$token_url = "https://healthid.moph.go.th/api/v1/oauth/token";

$token_res = file_get_contents($token_url, false, stream_context_create([
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json",
        "content" => json_encode([
            "client_id" => getenv("HEALTH_CLIENT_ID"),
            "client_secret" => getenv("HEALTH_CLIENT_SECRET"),
            "code" => $code,
            "redirect_uri" => getenv("HEALTH_REDIRECT"),
            "grant_type" => "authorization_code"
        ])
    ]
]));

$token_data = json_decode($token_res, true);
$health_token = $token_data["access_token"] ?? null;

if (!$health_token) {
    die("ไม่สามารถแลก token จาก Health ID ได้");
}

// ------------------------------
// 3) ขอ Provider Token
// ------------------------------
$provider_token_url = getenv("PROVIDER_URL") . "/api/v1/services/token";

$provider_token_res = file_get_contents($provider_token_url, false, stream_context_create([
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json",
        "content" => json_encode([
            "client_id" => getenv("PROVIDER_CLIENT_ID"),
            "secret_key" => getenv("PROVIDER_SECRET_KEY"),
            "token_by" => "Health ID",
            "token" => $health_token
        ])
    ]
]));

$provider_token_data = json_decode($provider_token_res, true);
$provider_access_token = $provider_token_data["data"]["access_token"] ?? null;

if (!$provider_access_token) {
    die("ไม่สามารถขอ Provider Token ได้");
}

// ------------------------------
// 4) ดึงข้อมูล Provider Profile
// ------------------------------
$profile_url = getenv("PROVIDER_URL") . "/api/v1/services/profile";

$profile_res = file_get_contents($profile_url, false, stream_context_create([
    "http" => [
        "method" => "GET",
        "header" => implode("\r\n", [
            "Authorization: Bearer $provider_access_token",
            "client-id: " . getenv("PROVIDER_CLIENT_ID"),
            "secret-key: " . getenv("PROVIDER_SECRET_KEY")
        ])
    ]
]));

$profile = json_decode($profile_res, true);
$data = $profile["data"];

// ------------------------------
// 5) ดึงข้อมูลที่ต้องใช้
// ------------------------------
$provider_id = $data["provider_id"];
$fullname = $data["firstname_th"] . " " . $data["lastname_th"];
$hos_code = $data["organization"][0]["hcode"];

// ------------------------------
// 6) ตรวจสอบหน่วยงาน
// ------------------------------
if ($hos_code !== getenv("HOS_CODE")) {
    die("หน่วยงานไม่ตรงกับที่ระบบกำหนด");
}

// ------------------------------
// 7) ตรวจสอบว่ามี user อยู่แล้วหรือไม่
// ------------------------------
$stmt = $conn->prepare("SELECT id, role, active FROM user WHERE provider_id=? LIMIT 1");
$stmt->bind_param("s", $provider_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {

    // ------------------------------
    // ⭐ ครั้งแรก → สร้าง user ใหม่
    // ------------------------------
    $stmt_insert = $conn->prepare("
        INSERT INTO user (username, password, role, active, provider_id, fullname, hos_code)
        VALUES (?, '-', 'user', 0, ?, ?, ?)
    ");
    $stmt_insert->bind_param("ssss", $provider_id, $provider_id, $fullname, $hos_code);
    $stmt_insert->execute();

    echo "<h3>สร้างบัญชีใหม่แล้ว</h3>";
    echo "กรุณาให้ admin เปิดใช้งานก่อน";

    exit;
}

// ------------------------------
// ⭐ ครั้งต่อไป → อัปเดต fullname แต่ไม่แตะ role/active
// ------------------------------
$stmt_update = $conn->prepare("
    UPDATE user SET fullname=?, hos_code=? WHERE provider_id=?
");
$stmt_update->bind_param("sss", $fullname, $hos_code, $provider_id);
$stmt_update->execute();

// ดึง id เพื่อ login
$stmt->bind_result($uid, $role, $active);
$stmt->fetch();

// ------------------------------
// ⭐ Login สำเร็จ
// ------------------------------
$_SESSION["user_id"] = $uid;
$_SESSION["login_time"] = date("Y-m-d H:i:s");

header("Location: index.php");
exit;
