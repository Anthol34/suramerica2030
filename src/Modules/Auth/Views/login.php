<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Acceso - Suramerica 2030 S.A.</title>
    <link rel="icon" type="image/jpeg" href="/suramerica2030/public/assets/img/icon.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php $vLoginCss = @filemtime(__DIR__ . '/../../../../public/assets/css/login.css') ?: time(); ?>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/login.css?v=<?= $vLoginCss ?>">
</head>
<body>
<div class="login-card">
    <div class="logo-placeholder">
        SURAMERICA 2030 S.A.<br>
        <small class="text-muted fs-6">Sistema Operativo</small>
    </div>

    <?php if(isset($necesitaSetup) && $necesitaSetup): ?>
        <div class="alert alert-warning text-center shadow-sm mb-3">
            <span class="fs-4">🚀</span><br>
            <strong>Primer Uso del Sistema</strong><br>
            <small>No se detectaron usuarios en la base de datos. Configura el Administrador Principal con acceso total.</small>
        </div>

        <?php if(isset($_GET['error_setup'])): ?>
            <div class="alert alert-danger text-center p-2 mb-3" style="font-size:12px;">
                ❌ <?= htmlspecialchars($_GET['error_setup']) ?>
            </div>
        <?php endif; ?>

        <form action="/suramerica2030/auth/instalar" method="POST">
            <div class="mb-2">
                <label class="form-label text-secondary fw-bold small">Nombre del Administrador</label>
                <input type="text" name="nombre" class="form-control" required value="Administrador Principal">
            </div>
            <div class="mb-2">
                <label class="form-label text-secondary fw-bold small">Usuario de Acceso</label>
                <input type="text" name="username" class="form-control fw-bold text-primary" required value="admin">
            </div>
            <div class="mb-2">
                <label class="form-label text-secondary fw-bold small">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" required value="admin@suramerica2030.com">
            </div>
            <div class="mb-3">
                <label class="form-label text-secondary fw-bold small">Contraseña</label>
                <input type="password" name="password" class="form-control fw-bold" required value="admin123" placeholder="Mínimo 4 caracteres">
                <small class="text-muted" style="font-size: 11px;">Por defecto: <code>admin123</code> (puedes cambiarla)</small>
            </div>
            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow">
                🚀 Crear Administrador y Comenzar
            </button>
        </form>

    <?php else: ?>
        <?php if(isset($_GET['instalado'])): ?>
            <div class="alert alert-success text-center p-2 mb-3 shadow-sm">
                ✅ <strong>¡Administrador Creado!</strong><br>
                <small>Inicia sesión con tus nuevas credenciales.</small>
            </div>
        <?php elseif(isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center p-2 mb-3 shadow-sm">
                ❌ Usuario o contraseña incorrectos.
            </div>
        <?php endif; ?>

        <form action="/suramerica2030/auth/login" method="POST">
            <div class="mb-3">
                <label class="form-label text-secondary fw-bold">Nombre de Usuario</label>
                <input type="text" name="username" class="form-control form-control-lg" required placeholder="ej: anthony.solorzano" autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary fw-bold">Contraseña</label>
                <input type="password" name="password" class="form-control form-control-lg" required placeholder="********">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" style="background-color: #1a437a; border: none;">
                Iniciar Sesión
            </button>
        </form>
    <?php endif; ?>

    <div class="text-center mt-4">
        <small class="text-muted">&copy; <?= date('Y') ?> Suramerica 2030 S.A.</small>
    </div>
</div>
<?php $vSanitizarJs = @filemtime(__DIR__ . '/../../../../public/assets/js/sanitizar-inputs.js') ?: time(); ?>
<script src="/suramerica2030/public/assets/js/sanitizar-inputs.js?v=<?= $vSanitizarJs ?>"></script>
</body>
</html>