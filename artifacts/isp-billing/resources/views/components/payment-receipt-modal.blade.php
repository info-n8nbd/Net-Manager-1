<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Payment Receipt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receiptModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading receipt...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnPrintA4">
                    <i class="fas fa-print me-1"></i>Print A4
                </button>
                <button type="button" class="btn btn-secondary" id="btnPrintThermal">
                    <i class="fas fa-receipt me-1"></i>Print Thermal
                </button>
                <a href="#" class="btn btn-success" id="btnDownloadPDF">
                    <i class="fas fa-download me-1"></i>Download PDF
                </a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
window.PaymentReceipt = {
    currentPaymentId: null,
    
    show: function(paymentId) {
        this.currentPaymentId = paymentId;
        const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        modal.show();
        this.loadReceipt(paymentId);
    },
    
    loadReceipt: function(paymentId) {
        const body = document.getElementById('receiptModalBody');
        body.innerHTML = `<div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading receipt...</p>
        </div>`;
        
        fetch(`/admin/payments/${paymentId}/receipt/data`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.renderReceipt(data);
                } else {
                    body.innerHTML = '<div class="alert alert-danger">Failed to load receipt</div>';
                }
            })
            .catch(e => {
                body.innerHTML = '<div class="alert alert-danger">Error loading receipt</div>';
            });
    },
    
    renderReceipt: function(data) {
        const r = data.receipt;
        const c = data.company;
        
        document.getElementById('btnPrintA4').onclick = () => window.open(data.print_url, '_blank');
        document.getElementById('btnPrintThermal').onclick = () => window.open(data.thermal_url, '_blank');
        document.getElementById('btnDownloadPDF').href = data.download_url;
        
        document.getElementById('receiptModalBody').innerHTML = `
            <div class="text-center mb-4">
                <h4 class="text-primary mb-1">${c.name}</h4>
                <small class="text-muted">${c.address || ''} ${c.phone ? '| ' + c.phone : ''}</small>
                <hr>
                <h5>PAYMENT RECEIPT</h5>
                <span class="badge bg-primary">${r.receipt_no}</span>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <h6 class="text-primary mb-2">Customer</h6>
                            <p class="mb-1 small"><strong>ID:</strong> ${r.client_id}</p>
                            <p class="mb-1 small"><strong>Name:</strong> ${r.client_name}</p>
                            <p class="mb-1 small"><strong>Phone:</strong> ${r.client_phone}</p>
                            <p class="mb-0 small"><strong>Package:</strong> ${r.package || '-'}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <h6 class="text-primary mb-2">Payment Details</h6>
                            <p class="mb-1 small"><strong>Date:</strong> ${r.payment_date}</p>
                            <p class="mb-1 small"><strong>Time:</strong> ${r.payment_time}</p>
                            <p class="mb-1 small"><strong>Method:</strong> ${r.payment_method}</p>
                            <p class="mb-0 small"><strong>Collected By:</strong> ${r.collected_by}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center py-4 bg-primary text-white rounded mb-4">
                <p class="mb-1 small">Amount Paid</p>
                <h2 class="mb-0">${r.amount_formatted}</h2>
            </div>
            
            <div class="row text-center">
                <div class="col-6">
                    <div class="p-2 bg-warning bg-opacity-25 rounded">
                        <small class="text-muted d-block">Remaining Due</small>
                        <strong class="text-warning">${r.due_formatted}</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-success bg-opacity-25 rounded">
                        <small class="text-muted d-block">Advance</small>
                        <strong class="text-success">${r.advance_formatted}</strong>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-muted">Generated: ${r.timestamp}</small>
            </div>
        `;
    }
};
</script>
