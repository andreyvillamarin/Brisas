<?php $baseUrl = rtrim(APP_URL, '/'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin' ?> - Brisas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/admin-style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <nav class="sidebar bg-dark text-white">
        <div class="sidebar-header">
            <a href="<?= $baseUrl ?>/admin/" class="text-white text-decoration-none d-flex align-items-center">
                <img src="<?= $baseUrl ?>/<?= $settingsForHeader['logo_backend_url'] ?? '' ?>" style="max-height: 40px;" class="me-2">
                <h4 class="mb-0">Admin Brisas</h4>
            </a>
        </div>
        <ul class="list-unstyled">
            <li><a href="<?= $baseUrl ?>/admin/"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
            <li><a href="<?= $baseUrl ?>/admin/categories.php"><i class="bi bi-tags-fill"></i> Categorías</a></li>
            <li><a href="<?= $baseUrl ?>/admin/products.php"><i class="bi bi-box-seam-fill"></i> Productos</a></li>
            <li><a href="<?= $baseUrl ?>/admin/promotions.php"><i class="bi bi-megaphone-fill"></i> Promociones</a></li>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <li><a href="<?= $baseUrl ?>/admin/users.php"><i class="bi bi-people-fill"></i> Usuarios</a></li>
            <?php endif; ?>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <li><a href="<?= $baseUrl ?>/admin/analytics.php"><i class="bi bi-bar-chart-fill"></i> Analítica</a></li>
            <li><a href="<?= $baseUrl ?>/admin/event_log.php"><i class="bi bi-archive-fill"></i> Log de Eventos</a></li>
            <li><a href="<?= $baseUrl ?>/admin/settings.php"><i class="bi bi-gear-fill"></i> Configuración</a></li>
            <?php endif; ?>
            <li><a href="<?= $baseUrl ?>/admin/security.php"><i class="bi bi-shield-lock-fill"></i> Seguridad</a></li>
            <li><a href="<?= $baseUrl ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
        </ul>
    </nav>
    <main class="main-content flex-grow-1 p-4">
        <h1 class="mb-4"><?= $title ?? 'Página' ?></h1>
