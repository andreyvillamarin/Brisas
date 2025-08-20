document.addEventListener('DOMContentLoaded', function() {
    const productsModal = new bootstrap.Modal(document.getElementById('products-modal'));
    const successModal = new bootstrap.Modal(document.getElementById('success-modal'));
    
    let tempCart = {}; // { productId: { name, quantity } }
    let mainCart = {};

    // Abrir modal de productos al hacer clic en categoría
    document.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', async () => {
            const categoryId = card.dataset.id;
            const categoryName = card.dataset.name;
            document.getElementById('products-modal-title').textContent = `Productos - ${categoryName}`;
            
            const response = await fetch(`api/get_products.php?category_id=${categoryId}`);
            const products = await response.json();
            
            const productsContainer = document.getElementById('products-container');
            productsContainer.innerHTML = ''; // Limpiar
            
            if (products.length > 0) {
                products.forEach(product => {
                    const currentQuantity = tempCart[product.id]?.quantity || 0;
                    productsContainer.innerHTML += `
                        <div class="col">
                            <div class="card product-card h-100">
                                <img src="${product.image_url || 'assets/img/placeholder.png'}" class="card-img-top" alt="${product.name}">
                                <div class="card-body text-center">
                                    <h6 class="card-title">${product.name}</h6>
                                    <div class="d-flex justify-content-center">
                                        <input type="number" class="form-control quantity-input" value="${currentQuantity}" min="0" data-id="${product.id}" data-name="${product.name}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                productsContainer.innerHTML = '<p class="text-center col-12">No hay productos en esta categoría.</p>';
            }
            productsModal.show();
        });
    });

    // Actualizar carrito temporal al cambiar cantidad en el modal
    document.getElementById('products-container').addEventListener('change', e => {
        if (e.target.classList.contains('quantity-input')) {
            const productId = e.target.dataset.id;
            const productName = e.target.dataset.name;
            const quantity = parseInt(e.target.value, 10);

            if (quantity > 0) {
                tempCart[productId] = { name: productName, quantity: quantity };
            } else {
                delete tempCart[productId];
            }
        }
    });

    // Botón "Agregar Productos al Pedido" del modal
    document.getElementById('add-to-cart-btn').addEventListener('click', () => {
        mainCart = { ...mainCart, ...tempCart };
        tempCart = {};
        renderMainCart();
        productsModal.hide();
    });

    // Renderizar la tabla principal del pedido
    function renderMainCart() {
        const container = document.getElementById('cart-items-container');
        if (Object.keys(mainCart).length === 0) {
            container.innerHTML = '<p class="text-center text-muted">Aún no has agregado productos.</p>';
            document.getElementById('submit-order-btn').disabled = true;
            return;
        }

        let tableHtml = `
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;
        for (const [id, item] of Object.entries(mainCart)) {
            tableHtml += `
                <tr>
                    <td>${item.name}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-danger remove-item-btn" data-id="${id}">Eliminar</button>
                    </td>
                </tr>
            `;
        }
        tableHtml += '</tbody></table>';
        container.innerHTML = tableHtml;
        document.getElementById('submit-order-btn').disabled = false;
    }
    
    // Eliminar item del carrito principal
    document.getElementById('cart-items-container').addEventListener('click', e => {
        if (e.target.classList.contains('remove-item-btn')) {
            const productId = e.target.dataset.id;
            delete mainCart[productId];
            renderMainCart();
        }
    });

    // Cambiar campos del formulario según tipo de cliente
    document.getElementById('customer_type').addEventListener('change', e => {
        const type = e.target.value;
        const container = document.getElementById('dynamic-fields-container');
        let fieldsHtml = '';
        if (type === 'Distribuidor o Salsamentaria') {
            fieldsHtml = `
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nombre del cliente o establecimiento <span class="text-danger">*</span></label><input type="text" name="customer_name" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Cédula o NIT <span class="text-danger">*</span></label><input type="text" name="customer_id_number" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Ciudad <span class="text-danger">*</span></label><input type="text" name="customer_city" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Correo electrónico</label><input type="email" name="customer_email" class="form-control"></div>
                </div>
            `;
        } else if (type === 'Mercaderista') {
            fieldsHtml = `
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nombre del mercaderista <span class="text-danger">*</span></label><input type="text" name="mercaderista_name" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Establecimiento o supermercado <span class="text-danger">*</span></label><input type="text" name="mercaderista_supermarket" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Ciudad <span class="text-danger">*</span></label><input type="text" name="customer_city" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Correo electrónico</label><input type="email" name="customer_email" class="form-control"></div>
                </div>
            `;
        }
        container.innerHTML = fieldsHtml;
    });

    // Enviar el formulario
    document.getElementById('order-form').addEventListener('submit', async e => {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.cart = mainCart;
        if (token) {
            data.recaptcha_token = token;
        }

        const submitBtn = document.getElementById('submit-order-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        try {
            const response = await fetch('api/submit_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                // Resetear todo
                mainCart = {};
                e.target.reset();
                document.getElementById('dynamic-fields-container').innerHTML = '';
                renderMainCart();
                successModal.show();
            } else {
                alert('Hubo un error al enviar el pedido: ' + result.message);
            }
        } catch (error) {
            alert('Hubo un error de conexión. Por favor, inténtalo de nuevo.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar Pedido';
        }
    });
});