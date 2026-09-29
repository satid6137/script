<?php
require __DIR__ . '/config.php';

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$auth_url = "https://moph.id.th/oauth/redirect?" . http_build_query([
    "client_id" => $_ENV["HEALTH_CLIENT_ID"],
    "redirect_uri" => $_ENV["HEALTH_REDIRECT"],
    "response_type" => "code",
    "state" => $state
]);

header("Location: $auth_url");
exit;
