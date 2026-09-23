<?php
declare(strict_types=1);

$call = (int) ($_GET['call'] ?? 0);
$role = $_GET['role'] ?? '';
$token = $_GET['token'] ?? '';
if ($call < 1 || !in_array($role, ['caller', 'tablet'], true) || !is_string($token) || $token === '') {
    http_response_code(400);
    exit('Session visio invalide.');
}
?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Les repères de Roland — visio</title><link rel="stylesheet" href="assets/v5.css"></head>
<body class="visio-page"><main id="visio" data-call="<?= $call ?>" data-role="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" data-token="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"><p id="visio-status">Connexion de l’appel vidéo…</p><section class="video-grid"><video id="remote-video" autoplay playsinline></video><video id="local-video" autoplay muted playsinline></video></section></main><script src="assets/visio.js"></script></body></html>
