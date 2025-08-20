<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --brisas-red: #aa182c; }
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8f9fa; }
        .login-card { max-width: 400px; width: 100%; }
        .btn-primary { background-color: var(--brisas-red); border-color: var(--brisas-red); }
        .btn-primary:hover { background-color: #861323; border-color: #861323; }
    </style>
</head>
<body>
    <div class="card login-card shadow">
        <div class="card-body p-5">
            <h3 class="text-center mb-4">Acceso de Administración</h3>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">Usuario o contraseña incorrectos.</div>
            <?php endif; ?>
            <form action="<?= url('auth/authenticate') ?>" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Ingresar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>