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

            const orderDate = new Date(data.details.created_at).toLocaleString('es-CO', {
                year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true
            });

            let contentHtml = `
                <div class="row">
                    <div class="col-md-8">
                        <h6>Cliente: ${data.details.customer_name}</h6>
                        <p><strong>Tipo:</strong> ${data.details.customer_type}</p>
                        <p><strong>Ciudad:</strong> ${data.details.customer_city}</p>
                        <p><strong>ID:</strong> ${data.details.customer_id_number}</p>
                        ${data.details.customer_email ? `<p><strong>Email:</strong> ${data.details.customer_email}</p>` : ''}
                        ${data.details.mercaderista_supermarket ? `<p><strong>Supermercado:</strong> ${data.details.mercaderista_supermarket}</p>` : ''}
                    </div>
                    <div class="col-md-4 text-md-end">
                        <p><strong>Estado:</strong> <span class="badge bg-info">${data.details.status}</span></p>
                        <p><strong>Fecha:</strong> ${orderDate}</p>
                    </div>
                </div>
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
            
            const downloadButtonsContainer = document.getElementById('modal-download-buttons');
            downloadButtonsContainer.innerHTML = `
                <a href="../admin/export.php?format=xlsx&id=${orderId}" class="btn btn-outline-success">Exportar a XLSX</a>
                <a href="../admin/export.php?format=pdf&id=${orderId}" class="btn btn-outline-danger">Exportar a PDF</a>
            `;

            orderDetailsModal.show();
        });
    });
});
