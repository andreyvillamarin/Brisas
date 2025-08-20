document.addEventListener('DOMContentLoaded', function() {
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('order-details-modal'));

    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const orderId = button.dataset.id;
            const response = await fetch(`../api/get_order_details.php?id=${orderId}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            let contentHtml = `
                <h6>Cliente: ${data.details.customer_name}</h6>
                <p><strong>Tipo:</strong> ${data.details.customer_type}</p>
                <p><strong>Ciudad:</strong> ${data.details.customer_city}</p>
                <p><strong>ID:</strong> ${data.details.customer_id_number}</p>
                ${data.details.customer_email ? `<p><strong>Email:</strong> ${data.details.customer_email}</p>` : ''}
                ${data.details.mercaderista_supermarket ? `<p><strong>Supermercado:</strong> ${data.details.mercaderista_supermarket}</p>` : ''}
                <hr>
                <h6>Productos del Pedido:</h6>
                <table class="table">
                    <thead><tr><th>Producto</th><th>Cantidad</th></tr></thead>
                    <tbody>
            `;

            data.items.forEach(item => {
                contentHtml += `<tr><td>${item.name}</td><td>${item.quantity}</td></tr>`;
            });

            contentHtml += '</tbody></table>';

            document.getElementById('order-details-content').innerHTML = contentHtml;
            orderDetailsModal.show();
        });
    });
});
