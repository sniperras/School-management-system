<?php
// includes/header.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$base = ''; // adjust if pages are in subfolders
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>University Portal | School Management System</title>
  <link rel="stylesheet" href="<?php echo $base; ?>index-Dcy6qitJ.css">
</head>
<body class="min-h-screen bg-gray-100">
