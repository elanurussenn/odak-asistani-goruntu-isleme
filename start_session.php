<?php

require __DIR__ . "/config/db.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$mode = $_GET['mode'] ?? '';
$mode = strtolower(trim($mode));

if ($mode === '') {
  header("Location: index.php?e=" . urlencode("Mod seçilmedi."));
  exit;
}


$routes = [
  'yks'        => 'yks.php',
  'yogun'    => 'yogun.php',
  'ebeveyn'    => 'ebeveyn.php',
  'motivasyon' => 'motivasyon.php',
];

$stmt = $pdo->prepare("SELECT ad FROM modlar WHERE aktif=1 AND kod=? LIMIT 1");
$stmt->execute([$mode]);
$mod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mod) {
  header("Location: index.php?e=" . urlencode("Bu mod aktif değil veya bulunamadı."));
  exit;
}

if (!isset($routes[$mode])) {
  header("Location: index.php?e=" . urlencode("Bu mod için yönlendirme tanımlı değil."));
  exit;
}

$_SESSION['last_mode'] = $mod['ad'];

header("Location: " . $routes[$mode]);
exit;