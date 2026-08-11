<?php
error_reporting(0);
require __DIR__ . '/config.php';

/* -------------------------
   โหลดเวอร์ชันจาก URL (version.txt)
-------------------------- */
function loadVersionFromTxt($url)
{
    $v = @file_get_contents($url);
    return $v ? trim($v) : null;
}

/* -------------------------
   โหลดเวอร์ชันจาก Node.js API
-------------------------- */
function loadVersionFromNode()
{
    $nodejs = $_ENV['IP_NODEJS'];
    $hosCode = $_ENV['HOS_CODE'];
    $apiKey = $_ENV['API_KEY'];

    $urlAPI = "{$nodejs}/query/version/{$hosCode}";

    $ch = curl_init($urlAPI);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-api-key: {$apiKey}"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $apiData = $response ? json_decode($response, true) : null;

    if (is_array($apiData) && isset($apiData[0])) {
        return [
            "client" => $apiData[0]['client_version'] ?? null,
            "server" => $apiData[0]['server_version'] ?? null,
            "telemed" => $apiData[0]['telemed_version'] ?? null
        ];
    }

    return [
        "client" => null,
        "server" => null,
        "telemed" => null
    ];
}

/* -------------------------
   โหลดเวอร์ชันทั้งหมด (รวมเป็นชุดเดียว)
-------------------------- */
function loadAllVersions()
{
    // PHP Script
    $phpCurrentUrl = "http://" . $_ENV['DB_PHP'] . "/script/version.txt";
    $phpLastUrl = "https://website.hangchathospital.com/script/version.txt";

    $phpCurrent = loadVersionFromTxt($phpCurrentUrl);
    $phpLast = loadVersionFromTxt($phpLastUrl);

    // Node.js API
    $nodeVersions = loadVersionFromNode();

    // ตรวจอัปเดต
    $hasUpdate =
        ($phpCurrent !== $phpLast) ||
        ($nodeVersions['client'] !== $phpLast) ||
        ($nodeVersions['server'] !== $phpLast) ||
        ($nodeVersions['telemed'] !== $phpLast);

    return [
        "phpCurrent" => $phpCurrent,
        "phpLast" => $phpLast,
        "clientVersion" => $nodeVersions['client'],
        "serverVersion" => $nodeVersions['server'],
        "telemedVersion" => $nodeVersions['telemed'],
        "hasUpdate" => $hasUpdate
    ];
}