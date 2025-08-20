<?php
require_once __DIR__ . '/config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Category.php';
require_once APP_ROOT . '/app/models/Promotion.php';
require_once APP_ROOT . '/app/models/Setting.php';

$categoryModel = new Category();
$promotionModel = new Promotion();
$settingModel = new Setting();

$categories = $categoryModel->getAllCategories();
$promotions = $promotionModel->getActive();
$settings = $settingModel->getAllAsAssoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Las Brisas - Catálogo de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (!empty($settings['logo_backend_url'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($settings['logo_backend_url']) ?>">
    <?php endif; ?>
</head>
<body>

    <header class="shadow-sm">
        <div class="container py-3 text-center">
            <img src="<?= htmlspecialchars($settings['logo_frontend_url'] ?? 'https://via.placeholder.com/200x60.png?text=Logo+Brisas') ?>" alt="Logo Brisas" id="logo-frontend" style="max-height: 80px;">
        </div>
    </header>

    <main class="container my-5">
        <?php if (($settings['store_status'] ?? 'open') === 'closed'): ?>
            <div class="alert alert-warning text-center"><h2>Tienda Cerrada</h2><div><?= $settings['store_message'] ?? 'Estamos cerrados por el momento. Intenta de nuevo más tarde.' ?></div></div>
        <?php else: ?>
        <section id="store-status" class="text-center mb-5">
            <!-- Mensaje de tienda abierta/cerrada se insertará aquí -->
        </section>

        <?php if (!empty($promotions)): ?>
        <section id="promociones" class="mb-5">
            <h2 class="section-title"><span>Promociones</span></h2>
            <div id="promo-cards-container" class="row row-cols-2 row-cols-md-4 g-4">
                <?php foreach ($promotions as $promo): ?>
                    <div class="col">
                        <div class="card product-card h-100">
                            <img src="<?= htmlspecialchars($promo['product_image'] ?? 'assets/img/placeholder.png') ?>" class="card-img-top" alt="<?= htmlspecialchars($promo['product_name']) ?>">
                            <div class="card-body text-center">
                                <h6 class="card-title"><?= htmlspecialchars($promo['product_name']) ?></h6>
                                <p class="card-text text-danger fw-bold"><?= htmlspecialchars($promo['promo_description']) ?></p>
                                <div class="input-group" data-promo-description="<?= htmlspecialchars($promo['promo_description']) ?>">
                                    <input type="number" class="form-control quantity-input" 
                                           value="<?= (int)($promo['min_quantity'] ?? 1) > 0 ? (int)$promo['min_quantity'] : 1 ?>" 
                                           min="<?= (int)($promo['min_quantity'] ?? 1) > 0 ? (int)$promo['min_quantity'] : 1 ?>"
                                           <?php if (!empty($promo['max_quantity']) && (int)$promo['max_quantity'] > 0): ?>
                                           max="<?= (int)$promo['max_quantity'] ?>"
                                           <?php endif; ?>
                                           data-id="<?= htmlspecialchars($promo['product_id']) ?>"
                                           data-name="<?= htmlspecialchars($promo['product_name']) ?>"
                                           aria-label="Cantidad">
                                    <button class="btn btn-primary add-promo-btn" type="button">Agregar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section id="categorias">
            <h2 class="section-title"><span>Categorías</span></h2>
            <div class="row row-cols-2 row-cols-md-4 g-4">
                <?php foreach ($categories as $category): ?>
                    <div class="col">
                        <div class="card category-card" data-id="<?= $category['id'] ?>" data-name="<?= htmlspecialchars($category['name']) ?>">
                            <img src="<?= htmlspecialchars($category['image_url'] ?? 'assets/img/placeholder.png') ?>" class="card-img-top" alt="<?= htmlspecialchars($category['name']) ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="resumen-pedido" class="mt-5">
            <div class="card">
                <div class="card-header bg-white">
                    <h3 class="mb-0">Tu Pedido</h3>
                </div>
                <div class="card-body">
                    <div id="cart-items-container" class="table-responsive">
                        <p class="text-center text-muted">Aún no has agregado productos.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="checkout-form" class="mt-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h3 class="mb-0">Completa tus Datos para Enviar</h3>
                </div>
                <div class="card-body">
                    <form id="order-form" data-recaptcha-key="<?= htmlspecialchars($settings['google_recaptcha_key'] ?? '') ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_type" class="form-label">Tipo de cliente <span class="text-danger">*</span></label>
                                <select class="form-select" id="customer_type" name="customer_type" required>
                                    <option value="">Selecciona una opción...</option>
                                    <option value="Distribuidor o Salsamentaria">Distribuidor o Salsamentaria</option>
                                    <option value="Mercaderista">Mercaderista</option>
                                </select>
                            </div>
                        </div>
                        <div id="dynamic-fields-container">
                            <!-- Los campos dinámicos se insertarán aquí -->
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="submit-order-btn" disabled>Enviar Pedido</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    <?php endif; ?>
    </main>

    <footer class="bg-dark text-white text-center p-4 mt-5">
        <p class="mb-0">&copy; <?= date('Y'); ?> Brisas. Todos los derechos reservados.</p>
    </footer>

    <!-- Modal para mostrar productos -->
    <div class="modal fade" id="products-modal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="products-modal-title">Productos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="products-container" class="row row-cols-2 row-cols-md-4 g-4">
                        <!-- Los productos de la categoría se cargarán aquí -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="add-to-cart-btn">Agregar Productos al Pedido</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de éxito -->
    <div class="modal fade" id="success-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <h2 class="mb-3">¡Pedido Enviado!</h2>
                    <p>Tu pedido ha sido enviado exitosamente. ¡Gracias por tu compra!</p>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($settings['google_recaptcha_key'] ?? '') ?>"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
