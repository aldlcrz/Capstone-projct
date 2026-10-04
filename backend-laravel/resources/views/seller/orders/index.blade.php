@extends('layouts.seller')

@section('content')
<script>
function parseOrderAddress(order) {
    let addr = order?.shippingAddress;
    if (!addr) return null;
    if (typeof addr === 'string') {
        try { addr = JSON.parse(addr); } catch (e) { return null; }
    }
    return addr;
}

function formatOrderAddress(order) {
    const addr = parseOrderAddress(order);
    if (!addr) return 'No shipping address provided';
    const lines = [
        addr.recipientName,
        [addr.houseNo, addr.street].filter(Boolean).join(' '),
        addr.barangay,
        [addr.city, addr.province].filter(Boolean).join(', '),
        addr.postalCode ? 'ZIP ' + addr.postalCode : null,
    ].filter(Boolean);
    return lines.join(', ');
}

function buyerOrderPhone(order) {
    const addr = parseOrderAddress(order);
    return addr?.phone || order?.customer?.mobileNumber || 'N/A';
}

function printSellerOrder(order) {
    if (!order) return;

    const orderId = '#LB-' + order.id.slice(-8).toUpperCase();
    const date = order.createdAt
        ? new Date(order.createdAt).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' })
        : '';

    const itemsHtml = (order.items || []).map(function (item) {
        const itemTitle = (item.display_variation && item.display_variation !== 'Original') ? item.display_variation : (item.product_name || item.product?.name || 'Archived Heritage Piece');
        return '<tr>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;">' + itemTitle + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;">' + (item.size || '—') + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">' + item.quantity + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">₱' + Number(item.price).toLocaleString() + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">₱' + (Number(item.price) * item.quantity).toLocaleString() + '</td>'
            + '</tr>';
    }).join('');

    const html = '<!DOCTYPE html><html><head><title>Receipt ' + orderId + '</title>'
        + '<style>'
        + 'body{font-family:Arial,sans-serif;color:#111;padding:32px;max-width:800px;margin:0 auto;}'
        + 'h1{font-size:22px;margin:0 0 4px;}'
        + 'h2{font-size:12px;text-transform:uppercase;letter-spacing:.1em;color:#666;margin:24px 0 8px;}'
        + '.meta{color:#666;font-size:13px;margin-bottom:24px;}'
        + '.box{background:#f9f9f9;border:1px solid #eee;border-radius:8px;padding:16px;margin-bottom:8px;}'
        + 'table{width:100%;border-collapse:collapse;font-size:13px;}'
        + 'th{text-align:left;padding:8px;border-bottom:2px solid #ddd;font-size:11px;text-transform:uppercase;color:#666;}'
        + '.total{text-align:right;font-size:18px;font-weight:bold;margin-top:16px;}'
        + '</style></head><body>'
        + '<h1>LumBarong — Order Receipt</h1>'
        + '<div class="meta">' + orderId + ' · ' + date + ' · Status: ' + order.status + '</div>'
        + '<h2>Buyer Information</h2>'
        + '<div class="box"><strong>' + (order.customer?.name || 'Unknown Customer') + '</strong><br>'
        + 'Email: ' + (order.customer?.email || 'N/A') + '<br>'
        + 'Phone: ' + buyerOrderPhone(order) + '</div>'
        + '<h2>Shipping Address</h2>'
        + '<div class="box">' + formatOrderAddress(order) + '</div>'
        + '<h2>Product Details</h2>'
        + '<table><thead><tr>'
        + '<th>Product / Style</th><th>Size</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th>'
        + '</tr></thead><tbody>' + itemsHtml + '</tbody></table>'
        + '<div class="total">Total: ₱' + Number(order.totalAmount).toLocaleString() + '</div>'
        + '<h2>Payment Information</h2>'
        + '<div class="box">Method: ' + (order.paymentMethod || 'N/A')
        + (['GCASH', 'MAYA'].includes((order.paymentMethod || '').toUpperCase()) && order.paymentReference && !order.paymentReference.startsWith('COD-') ? '<br>Reference No: ' + order.paymentReference : '')
        + '</div>'
        + '</body></html>';

    const win = window.open('', '_blank');
    win.document.write(html);
    win.document.close();
    win.focus();
    win.print();
}

function sellerOrdersManager() {
    let initialOrders = [];
    try {
        const jsonEl = document.getElementById('seller-orders-json');
        if (jsonEl && jsonEl.textContent) {
            initialOrders = JSON.parse(jsonEl.textContent);
        }
    } catch(e) {
        initialOrders = [];
    }
    return {
        orders: initialOrders || [],
        searchTerm: '',
        statusFilter: 'all',
        activeOrder: null,
        statusModal: false,
        newStatus: '',
        receiptModal: false,
        receiptUrl: '',
        detailsModal: false,
        detailsOrder: null,

        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const statusParam = urlParams.get('status');
            if (statusParam) {
                const normParam = this.normalizeStatus(statusParam);
                if (normParam === 'cancellation pending' || normParam === 'cancellation requests') {
                    this.statusFilter = 'cancellation pending';
                } else if (normParam === 'return requests' || normParam === 'return requested' || normParam === 'returns') {
                    this.statusFilter = 'return requests';
                } else if (['all', 'pending', 'to ship', 'shipped', 'in transit', 'delivered', 'completed', 'cancelled'].includes(normParam)) {
                    this.statusFilter = normParam;
                }
            }
            const orderId = urlParams.get('order_id') || urlParams.get('orderId') || urlParams.get('order');
            if (orderId) {
                const target = this.orders.find(o => String(o.id) === String(orderId) || String(o.id).toLowerCase().endsWith(String(orderId).toLowerCase()));
                if (target) {
                    this.openDetails(target);
                } else {
                    this.searchTerm = orderId;
                }
            }
        },

        courierName: '',
        trackingNumber: '',
        trackingLink: '',
        shippingError: '',
        packingPhotoFile: null,
        packingPhotoPreview: null,
        packingUploading: false,
        packingUploadSuccess: false,
        packingUploadError: '',
        showCameraModal: false,
        cameraStream: null,
        showStatusConfirmModal: false,
        statusConfirmTarget: null,
        statusConfirmNewStatus: '',
        statusConfirmLoading: false,
        statusConfirmError: '',
        showDeliveryConfirmModal: false,
        deliveryConfirmOrder: null,
        deliveryConfirmLoading: false,
        deliveryConfirmSuccess: false,
        deliveryConfirmError: '',
        showVerifyModal: false,
        verifyOrderTarget: null,
        verifyingPayment: false,
        showRejectModal: false,
        rejectOrderTarget: null,
        showCancelOrderModal: false,
        cancelOrderTarget: null,
        sellerCancelReason: 'Out of stock / fabric unavailable',
        sellerCustomCancelReason: '',
        sellerCancelLoading: false,
        sellerCancelError: '',
        showApproveCancellationModal: false,
        approveCancellationTarget: null,
        approveCancellationLoading: false,
        approveCancellationError: '',
        showDeclineCancellationModal: false,
        declineCancellationTarget: null,
        declineCancellationReason: 'Order has already been prepared / fabric cut',
        declineCancellationCustomReason: '',
        declineCancellationLoading: false,
        declineCancellationError: '',
        showApproveReturnModal: false,
        approveReturnTarget: null,
        approveReturnInstructions: '',
        approveReturnLoading: false,
        approveReturnError: '',
        showRejectReturnModal: false,
        rejectReturnTarget: null,
        rejectReturnReason: 'Item is not in original condition / beyond return window',
        rejectReturnCustomReason: '',
        rejectReturnLoading: false,
        rejectReturnError: '',
        showProofLightboxModal: false,
        activeProofImage: '',
        receiptModal: false,
        receiptUrl: '',
        rejectReason: 'Reference number does not match',
        rejectCustomReason: '',
        rejectLoading: false,
        rejectError: '',
        statusUpdating: false,
        shippingUpdating: false,
        toastMessage: '',
        toastTimeout: null,

        showToast(msg) {
            this.toastMessage = msg;
            clearTimeout(this.toastTimeout);
            this.toastTimeout = setTimeout(() => { this.toastMessage = ''; }, 3500);
        },

        formatPaymentMethod(order) {
            if (!order) return 'Cash on Delivery';
            const method = (order.paymentMethod || '').trim().toUpperCase();
            if (method === 'COD' || method === '' || method === 'CASH ON DELIVERY' || method === 'PAY ON CLAIM' || method === 'PAY IN SHOP') {
                if (this.isStorePickup(order)) {
                    return 'Pay in Shop';
                }
                if (this.isSpecialDelivery(order)) {
                    return 'Special Delivery (COD)';
                }
                return 'Cash on Delivery';
            }
            if (method === 'GCASH') return 'GCash';
            if (method === 'MAYA' || method === 'PAYMAYA') return 'Maya';
            return order.paymentMethod;
        },

        paymentBadge(order) {
            const ps = String(order?.paymentStatus || '').toLowerCase();
            if (ps.includes('rejected')) return { text: '✕ Payment Rejected', class: 'bg-red-50 text-red-700 border-red-200' };
            if (ps.includes('submitted') || (order?.paymentProof && !ps.includes('verified') && !ps.includes('paid'))) return { text: '⏳ Verify Payment', class: 'bg-amber-50 text-amber-800 border-amber-300' };
            if (ps.includes('verified') || ps.includes('paid') || (order?.status && order.status !== 'Pending')) return { text: '✓ Verified', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
            return { text: 'Pending Submission', class: 'bg-gray-50 text-gray-600 border-gray-200' };
        },

        openVerifyPaymentModal(order) {
            this.verifyOrderTarget = order || this.detailsOrder;
            this.verifyingPayment = false;
            this.showVerifyModal = true;
        },

        async executeVerifyPayment() {
            if (!this.verifyOrderTarget || this.verifyingPayment || this.statusUpdating) return;
            this.verifyingPayment = true;
            try {
                await this.updateStatus(this.verifyOrderTarget, 'To Ship');
                this.showVerifyModal = false;
            } finally {
                this.verifyingPayment = false;
            }
        },

        openRejectPaymentModal(order) {
            this.rejectOrderTarget = order || this.detailsOrder;
            this.rejectReason = 'Reference number does not match';
            this.rejectCustomReason = '';
            this.rejectError = '';
            this.rejectLoading = false;
            this.showRejectModal = true;
        },

        async executeRejectPayment() {
            if (!this.rejectOrderTarget || this.rejectLoading) return;
            const finalReason = this.rejectReason === 'Other' ? this.rejectCustomReason.trim() : this.rejectReason;
            if (!finalReason) {
                this.rejectError = 'Please provide a reason for rejecting the payment.';
                return;
            }
            this.rejectLoading = true;
            this.rejectError = '';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
                const res = await fetch('/seller/api/orders/' + this.rejectOrderTarget.id + '/reject-payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ reason: finalReason })
                });
                const data = await res.json();
                if (res.ok) {
                    const idx = this.orders.findIndex(o => o.id === this.rejectOrderTarget.id);
                    if (idx !== -1) {
                        this.orders.splice(idx, 1, data.order || data);
                        this.orders = [...this.orders];
                        if (this.detailsOrder && this.detailsOrder.id === this.rejectOrderTarget.id) {
                            this.detailsOrder = data.order || data;
                        }
                    }
                    this.showToast('Payment rejected. Customer has been notified.');
                    this.showRejectModal = false;
                    this.detailsModal = false;
                } else {
                    this.rejectError = data.message || 'Failed to reject payment.';
                }
            } catch(e) {
                this.rejectError = 'Network error while rejecting payment.';
            } finally {
                this.rejectLoading = false;
            }
        },

        openCancelOrderModal(order) {
            this.cancelOrderTarget = order || this.detailsOrder;
            this.sellerCancelReason = 'Out of stock / fabric unavailable';
            this.sellerCustomCancelReason = '';
            this.sellerCancelError = '';
            this.sellerCancelLoading = false;
            this.showCancelOrderModal = true;
        },

        async executeSellerCancelOrder() {
            if (!this.cancelOrderTarget || this.sellerCancelLoading) return;
            const finalReason = this.sellerCancelReason === 'Other' ? this.sellerCustomCancelReason.trim() : this.sellerCancelReason;
            if (!finalReason) {
                this.sellerCancelError = 'Please specify a reason for cancellation.';
                return;
            }
            this.sellerCancelLoading = true;
            this.sellerCancelError = '';

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
                const res = await fetch('/seller/api/orders/' + this.cancelOrderTarget.id + '/cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ cancellationReason: finalReason })
                });

                const data = await res.json();
                if (res.ok) {
                    const idx = this.orders.findIndex(o => o.id === this.cancelOrderTarget.id);
                    if (idx !== -1) {
                        this.orders.splice(idx, 1, data.order || data);
                        this.orders = [...this.orders];
                        if (this.detailsOrder && this.detailsOrder.id === this.cancelOrderTarget.id) {
                            this.detailsOrder = data.order || data;
                        }
                    }
                    this.showToast('✓ Order cancelled successfully. Stock restored.');
                    this.showCancelOrderModal = false;
                    this.detailsModal = false;
                } else {
                    this.sellerCancelError = data.message || 'Failed to cancel order.';
                }
            } catch(e) {
                this.sellerCancelError = 'Network error while cancelling order.';
            } finally {
                this.sellerCancelLoading = false;
            }
        },

        openApproveCancellationModal(order) {
            this.approveCancellationTarget = order || this.detailsOrder;
            this.approveCancellationError = '';
            this.approveCancellationLoading = false;
            this.showApproveCancellationModal = true;
        },

        async executeApproveCancellation() {
            if (!this.approveCancellationTarget || this.approveCancellationLoading) return;
            this.approveCancellationLoading = true;
            this.approveCancellationError = '';
            const target = this.approveCancellationTarget;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
                const res = await fetch('/seller/api/orders/' + target.id + '/approve-cancellation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                });
                const data = await res.json();
                if (res.ok) {
                    const idx = this.orders.findIndex(o => o.id === target.id);
                    if (idx !== -1) {
                        this.orders.splice(idx, 1, data.order || data);
                        this.orders = [...this.orders];
                        if (this.detailsOrder && this.detailsOrder.id === target.id) {
                            this.detailsOrder = data.order || data;
                        }
                    }
                    this.showToast('✓ Cancellation approved. Order cancelled and stock restored.');
                    this.showApproveCancellationModal = false;
                    this.detailsModal = false;
                } else {
                    this.approveCancellationError = data.message || 'Failed to approve cancellation.';
                }
            } catch(e) {
                this.approveCancellationError = 'Network error while approving cancellation.';
            } finally {
                this.approveCancellationLoading = false;
            }
        },

        openDeclineCancellationModal(order) {
            this.declineCancellationTarget = order || this.detailsOrder;
            this.declineCancellationReason = 'Order has already been prepared / fabric cut';
            this.declineCancellationCustomReason = '';
            this.declineCancellationError = '';
            this.declineCancellationLoading = false;
            this.showDeclineCancellationModal = true;
        },

        async executeDeclineCancellation() {
            if (!this.declineCancellationTarget || this.declineCancellationLoading) return;
            const finalReason = this.declineCancellationReason === 'Other' ? this.declineCancellationCustomReason.trim() : this.declineCancellationReason;
            if (!finalReason) {
                this.declineCancellationError = 'Please provide a reason for declining the cancellation.';
                return;
            }
            this.declineCancellationLoading = true;
            this.declineCancellationError = '';
            const target = this.declineCancellationTarget;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
                const res = await fetch('/seller/api/orders/' + target.id + '/reject-cancellation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ reason: finalReason })
                });
                const data = await res.json();
                if (res.ok) {
                    const idx = this.orders.findIndex(o => o.id === target.id);
                    if (idx !== -1) {
                        this.orders.splice(idx, 1, data.order || data);
                        this.orders = [...this.orders];
                        if (this.detailsOrder && this.detailsOrder.id === target.id) {
                            this.detailsOrder = data.order || data;
                        }
                    }
                    this.showToast('✓ Cancellation request declined. Order returned to Pending.');
                    this.showDeclineCancellationModal = false;
                    this.detailsModal = false;
                } else {
                    this.declineCancellationError = data.message || 'Failed to decline cancellation.';
                }
            } catch(e) {
                this.declineCancellationError = 'Network error while declining cancellation.';
            } finally {
                this.declineCancellationLoading = false;
            }
        },

        getReturnRequest(order) {
            if (!order) return null;
            const reqs = order.return_requests || order.returnRequests || [];
            if (Array.isArray(reqs) && reqs.length > 0) {
                return reqs[0];
            }
            if (reqs && typeof reqs === 'object' && reqs.id) {
                return reqs;
            }
            return null;
        },

        hasPendingReturn(order) {
            const req = this.getReturnRequest(order);
            if (!req) return false;
            const s = String(req.status || '').toLowerCase().trim();
            return s === 'pending' || s === 'requested';
        },

        getProofImages(returnReq) {
            if (!returnReq || !returnReq.proofImages) return [];
            let images = returnReq.proofImages;
            if (typeof images === 'string') {
                try {
                    images = JSON.parse(images);
                } catch (e) {
                    images = [images];
                }
            }
            if (!Array.isArray(images)) {
                images = [images];
            }
            return images.filter(Boolean).map(img => {
                if (typeof img !== 'string') return '';
                const clean = img.trim();
                if (clean.startsWith('http://') || clean.startsWith('https://') || clean.startsWith('/')) {
                    return clean;
                }
                return '/storage/' + clean;
            }).filter(Boolean);
        },

        openApproveReturnModal(order) {
            this.approveReturnTarget = order || this.detailsOrder;
            this.approveReturnInstructions = '';
            this.approveReturnLoading = false;
            this.approveReturnError = '';
            this.showApproveReturnModal = true;
        },

        async executeApproveReturn() {
            if (!this.approveReturnTarget || this.approveReturnLoading) return;
            const returnReq = this.getReturnRequest(this.approveReturnTarget);
            if (!returnReq) {
                this.approveReturnError = 'No return request found for this order.';
                return;
            }
            this.approveReturnLoading = true;
            this.approveReturnError = '';

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
                const res = await fetch('/seller/orders/' + this.approveReturnTarget.id + '/returns/' + returnReq.id + '/approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        comment: this.approveReturnInstructions.trim(),
                        seller_notes: this.approveReturnInstructions.trim()
                    })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.returnRequest)) {
                    const updatedReturn = data.returnRequest || data.return_request;
                    const targetId = this.approveReturnTarget.id;
                    const idx = this.orders.findIndex(o => o.id === targetId);
                    if (idx !== -1) {
                        const updatedOrder = { ...this.orders[idx] };
                        const reqs = [...(updatedOrder.return_requests || updatedOrder.returnRequests || [])];
                        const rIdx = reqs.findIndex(r => String(r.id) === String(returnReq.id));
                        if (rIdx !== -1) {
                            reqs[rIdx] = updatedReturn;
                        } else {
                            reqs.unshift(updatedReturn);
                        }
                        updatedOrder.return_requests = reqs;
                        updatedOrder.returnRequests = reqs;
                        this.orders.splice(idx, 1, updatedOrder);
                        this.orders = [...this.orders];
                        if (this.detailsOrder && this.detailsOrder.id === targetId) {
                            this.detailsOrder = updatedOrder;
                        }
                    }
                    this.showToast('✓ Return request approved. Customer has been notified.');
                    this.showApproveReturnModal = false;
                } else {
                    this.approveReturnError = data.message || 'Failed to approve return request.';
                }
            } catch(e) {
                this.approveReturnError = 'Network error while approving return request.';
            } finally {
                this.approveReturnLoading = false;
            }
        },

        openRejectReturnModal(order) {
            this.rejectReturnTarget = order || this.detailsOrder;
            this.rejectReturnReason = 'Item is not in original condition / beyond return window';
            this.rejectReturnCustomReason = '';
            this.rejectReturnLoading = false;
            this.rejectReturnError = '';
            this.showRejectReturnModal = true;
        },

        async executeRejectReturn() {
            if (!this.rejectReturnTarget || this.rejectReturnLoading) return;
            const returnReq = this.getReturnRequest(this.rejectReturnTarget);
            if (!returnReq) {
                this.rejectReturnError = 'No return request found for this order.';
                return;
            }
            const finalReason = this.rejectReturnReason === 'Other' ? this.rejectReturnCustomReason.trim() : this.rejectReturnReason;
            if (!finalReason) {
                this.rejectReturnError = 'Please provide an explanation for declining the return request.';
                return;
            }
            this.rejectReturnLoading = true;
            this.rejectReturnError = '';

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
                const res = await fetch('/seller/orders/' + this.rejectReturnTarget.id + '/returns/' + returnReq.id + '/reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        reason: finalReason
                    })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.returnRequest)) {
                    const updatedReturn = data.returnRequest || data.return_request;
                    const targetId = this.rejectReturnTarget.id;
                    const idx = this.orders.findIndex(o => o.id === targetId);
                    if (idx !== -1) {
                        const updatedOrder = { ...this.orders[idx] };
                        const reqs = [...(updatedOrder.return_requests || updatedOrder.returnRequests || [])];
                        const rIdx = reqs.findIndex(r => String(r.id) === String(returnReq.id));
                        if (rIdx !== -1) {
                            reqs[rIdx] = updatedReturn;
                        } else {
                            reqs.unshift(updatedReturn);
                        }
                        updatedOrder.return_requests = reqs;
                        updatedOrder.returnRequests = reqs;
                        this.orders.splice(idx, 1, updatedOrder);
                        this.orders = [...this.orders];
                        if (this.detailsOrder && this.detailsOrder.id === targetId) {
                            this.detailsOrder = updatedOrder;
                        }
                    }
                    this.showToast('Return request declined. Customer has been notified.');
                    this.showRejectReturnModal = false;
                } else {
                    this.rejectReturnError = data.message || 'Failed to decline return request.';
                }
            } catch(e) {
                this.rejectReturnError = 'Network error while declining return request.';
            } finally {
                this.rejectReturnLoading = false;
            }
        },

        openProofLightbox(imgUrl) {
            this.activeProofImage = imgUrl;
            this.showProofLightboxModal = true;
        },

        closeProofLightbox() {
            this.showProofLightboxModal = false;
            this.activeProofImage = '';
        },

        confirmMarkAsDelivered(order) {
            this.deliveryConfirmOrder = order || this.detailsOrder;
            this.deliveryConfirmLoading = false;
            this.deliveryConfirmSuccess = false;
            this.deliveryConfirmError = '';
            this.showDeliveryConfirmModal = true;
        },

        async executeMarkAsDelivered() {
            if (!this.deliveryConfirmOrder || this.deliveryConfirmLoading) return;
            this.deliveryConfirmLoading = true;
            this.deliveryConfirmError = '';

            const target = this.deliveryConfirmOrder;
            const isPickup = this.isStorePickup(target);
            const isSpecial = this.isSpecialDelivery(target);

            try {
                const payload = {
                    status: 'Delivered',
                    courierName: isPickup ? 'Store Pickup' : (isSpecial ? 'Special Delivery (Local Artisan Rider)' : (this.courierName || target.courierName || 'J&T Express')),
                    trackingNumber: (isPickup || isSpecial) ? null : ((this.trackingNumber || target.trackingNumber || '').trim() || null),
                    trackingLink: (isPickup || isSpecial) ? null : (this.trackingLink || target.trackingLink || null)
                };

                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';

                const res = await fetch('/seller/api/orders/' + target.id + '/status', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (res.ok) {
                    const idx = this.orders.findIndex(o => o.id === target.id);
                    if (idx !== -1) {
                        this.orders.splice(idx, 1, data);
                        this.orders = [...this.orders];
                        if (this.detailsOrder && this.detailsOrder.id === target.id) {
                            this.detailsOrder = data;
                        }
                        if (this.activeOrder && this.activeOrder.id === target.id) {
                            this.activeOrder = data;
                        }
                    }
                    this.deliveryConfirmSuccess = true;
                    this.showToast('✓ Order successfully marked as Delivered!');
                    setTimeout(() => {
                        this.showDeliveryConfirmModal = false;
                        this.detailsModal = false;
                        this.deliveryConfirmOrder = null;
                        this.deliveryConfirmLoading = false;
                        this.deliveryConfirmSuccess = false;
                    }, 2000);
                } else {
                    this.deliveryConfirmError = data.message || 'Failed to update status to Delivered. Please try again.';
                    this.deliveryConfirmLoading = false;
                }
            } catch(e) {
                this.deliveryConfirmError = 'Network error. Please try again.';
                this.deliveryConfirmLoading = false;
            }
        },

        courierFormats: {
            'J&T Express': {
                name: 'J&T Express',
                regex: /^(JT)?[0-9]{10,14}$/i,
                placeholder: 'e.g. 781234567890 or JT123456789012',
                hint: '10–14 numeric digits (e.g. 781234567890 or JT123456789012)',
                link: 'https://www.jtexpress.ph/track'
            },
            'SPX Express': {
                name: 'SPX Express',
                regex: /^SPX(PH)?[0-9A-Z]{6,16}$/i,
                placeholder: 'e.g. SPXPH0123456789',
                hint: 'Starts with SPXPH or SPX followed by 6–16 characters',
                link: 'https://spx.ph/#/track'
            },
            'LBC Express': {
                name: 'LBC Express',
                regex: /^(LBC)?[0-9]{5,15}$/i,
                placeholder: 'e.g. 123456789012 (12-digit waybill)',
                hint: '5–15 digit waybill number (e.g. 123456789012)',
                link: 'https://www.lbcexpress.com/track'
            },
            'Flash Express': {
                name: 'Flash Express',
                regex: /^(PH|FPH|TH|FL|FLASH)?[0-9A-Z\-]{6,20}$/i,
                placeholder: 'e.g. PH012345678901',
                hint: 'Starts with PH / FPH / TH / FLASH (e.g. PH012345678901)',
                link: 'https://www.flashexpress.ph/tracking/'
            },
            'Ninja Van': {
                name: 'Ninja Van',
                regex: /^(NVPH|NVP|NVA|SHP|NINJA)?[0-9A-Z\-]{6,18}$/i,
                placeholder: 'e.g. NVPH0123456789',
                hint: 'Starts with NVPH / NVP (e.g. NVPH0123456789)',
                link: 'https://www.ninjavan.co/en-ph/tracking'
            },
            '2GO Express': {
                name: '2GO Express',
                regex: /^(2GO)?[0-9]{7,14}$/i,
                placeholder: 'e.g. 12345678 (8–12 digits)',
                hint: '7–12 numeric digits (e.g. 12345678)',
                link: 'https://supplychain.2go.com.ph/customersupport/etrack.asp'
            },
            'JRS Express': {
                name: 'JRS Express',
                regex: /^(JRS)?[0-9]{6,14}$/i,
                placeholder: 'e.g. 1234567 (7–12 digits)',
                hint: '6–12 numeric digits (e.g. 1234567)',
                link: 'https://www.jrs-express.com/tracking/'
            },
            'Lalamove': {
                name: 'Lalamove',
                regex: /^(LLM)?[0-9A-Z\-]{6,18}$/i,
                placeholder: 'e.g. LLM12345678',
                hint: 'Starts with LLM or 6–18 alphanumeric characters',
                link: 'https://www.lalamove.com/en-ph/'
            },
            'GrabExpress': {
                name: 'GrabExpress',
                regex: /^(GRAB|DLV|A-)?[0-9A-Z\-]{6,22}$/i,
                placeholder: 'e.g. A-123456789',
                hint: 'Starts with A- / GRAB / DLV or 6–22 characters',
                link: 'https://www.grab.com/ph/express/'
            },
            'Other Courier': {
                name: 'Other Courier',
                regex: /^[0-9A-Z\-]{6,30}$/i,
                placeholder: 'e.g. TRK-123456789',
                hint: '6–30 alphanumeric tracking characters',
                link: 'https://www.google.com/search?q=track+package'
            }
        },

        getCourierConfig(courier) {
            const raw = (courier || '').trim();
            if (this.courierFormats[raw]) return this.courierFormats[raw];
            const lower = raw.toLowerCase();
            for (const [key, cfg] of Object.entries(this.courierFormats)) {
                if (lower.includes(key.toLowerCase()) || key.toLowerCase().includes(lower)) {
                    return cfg;
                }
            }
            if (lower.includes('j&t') || lower.includes('jnt')) return this.courierFormats['J&T Express'];
            if (lower.includes('spx') || lower.includes('shopee')) return this.courierFormats['SPX Express'];
            if (lower.includes('lbc')) return this.courierFormats['LBC Express'];
            if (lower.includes('flash')) return this.courierFormats['Flash Express'];
            if (lower.includes('ninja')) return this.courierFormats['Ninja Van'];
            if (lower.includes('2go')) return this.courierFormats['2GO Express'];
            if (lower.includes('jrs')) return this.courierFormats['JRS Express'];
            if (lower.includes('lalamove')) return this.courierFormats['Lalamove'];
            if (lower.includes('grab')) return this.courierFormats['GrabExpress'];
            return this.courierFormats['Other Courier'];
        },

        getCourierDefaultLink(courier) {
            return this.getCourierConfig(courier).link || 'https://www.jtexpress.ph/track';
        },

        validateTrackingNumber(courier, tracking) {
            const val = (tracking || '').trim().toUpperCase();
            if (!val) {
                return { valid: false, message: 'Please enter the official courier tracking number.' };
            }
            if (!/^[0-9A-Z\-]+$/.test(val)) {
                return { valid: false, message: 'Tracking number can only contain letters, numbers, and hyphens.' };
            }
            const cleanNoDashes = val.replace(/-/g, '');
            if (cleanNoDashes.length >= 4 && /^(.)\1+$/.test(cleanNoDashes)) {
                return { valid: false, message: 'Invalid tracking number. Repeated single-character sequences are not allowed.' };
            }
            const cfg = this.getCourierConfig(courier);
            if (cfg && cfg.regex && !cfg.regex.test(cleanNoDashes) && !cfg.regex.test(val)) {
                return {
                    valid: false,
                    message: `Invalid format for ${cfg.name || courier}. Expected: ${cfg.hint}`
                };
            }
            return { valid: true, message: `✓ Valid ${cfg.name || courier} format` };
        },

        get trackingValidationStatus() {
            return this.validateTrackingNumber(this.courierName, this.trackingNumber);
        },

        async saveShippingDetails(order) {
            const target = order || this.detailsOrder;
            if (!target || this.shippingUpdating) return;
            
            const valRes = this.validateTrackingNumber(this.courierName, this.trackingNumber);
            if (!valRes.valid) {
                this.shippingError = valRes.message;
                return;
            }

            this.shippingUpdating = true;
            this.shippingError = '';

            try {
                const payload = {
                    status: target.status,
                    courierName: this.courierName || 'J&T Express',
                    trackingNumber: this.trackingNumber.trim().toUpperCase(),
                    trackingLink: this.trackingLink || null,
                    notes: 'Artisan updated shipping/tracking information.'
                };
                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
                const res = await fetch('/seller/api/orders/' + target.id + '/status', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) {
                    const idx = this.orders.findIndex(o => o.id === target.id);
                    if (idx !== -1) {
                        this.orders.splice(idx, 1, data);
                        this.orders = [...this.orders];
                    }
                    if (this.detailsOrder && this.detailsOrder.id === target.id) {
                        this.detailsOrder = data;
                    }
                    this.showToast('✓ Courier and tracking details saved successfully!');
                } else {
                    this.shippingError = data.message || 'Failed to update shipping info.';
                }
            } catch(e) {
                this.shippingError = 'Network error while updating shipping details.';
            } finally {
                this.shippingUpdating = false;
            }
        },

        formatAddress(order) {
            return formatOrderAddress(order);
        },

        buyerPhone(order) {
            return buyerOrderPhone(order);
        },

        onCourierChange() {
            const defaultLink = this.getCourierDefaultLink(this.courierName);
            if (defaultLink) {
                this.trackingLink = defaultLink;
            }
        },

        openDetails(order) {
            this.detailsOrder = order;
            this.newStatus = order.status;
            if (this.isStorePickup(order)) {
                this.courierName = 'Store Pickup';
                this.trackingNumber = '';
                this.trackingLink = '';
            } else if (this.isSpecialDelivery(order)) {
                this.courierName = 'Special Delivery (Local Artisan Rider)';
                this.trackingNumber = '';
                this.trackingLink = '';
            } else {
                this.courierName = order.shipping?.fulfillment_provider_name || order.courierName || order.shipping?.pricing_provider_name || order.shipping?.provider_name || 'J&T Express';
                this.trackingNumber = order.shipping?.tracking_number || order.trackingNumber || '';
                this.trackingLink = order.trackingLink || (this.courierName ? this.getCourierDefaultLink(this.courierName) : 'https://www.jtexpress.ph/track');
            }
            this.shippingError = '';
            this.packingPhotoFile = null;
            let proof = order.packingProofUrl || order.packingProof || null;
            if (proof && !proof.startsWith('http') && !proof.startsWith('/')) {
                proof = '/' + proof;
            }
            this.packingPhotoPreview = proof;
            this.packingUploading = false;
            this.packingUploadSuccess = !!order.packingProof;
            this.packingUploadError = '';
            this.closeCameraModal();
            this.detailsModal = true;
        },

        openStatus(order) {
            this.activeOrder = order;
            this.newStatus = order.status;
            if (this.isStorePickup(order)) {
                this.courierName = 'Store Pickup';
                this.trackingNumber = '';
                this.trackingLink = '';
            } else if (this.isSpecialDelivery(order)) {
                this.courierName = 'Special Delivery (Local Artisan Rider)';
                this.trackingNumber = '';
                this.trackingLink = '';
            } else {
                this.courierName = order.shipping?.fulfillment_provider_name || order.courierName || order.shipping?.pricing_provider_name || order.shipping?.provider_name || 'J&T Express';
                this.trackingNumber = order.shipping?.tracking_number || order.trackingNumber || '';
                this.trackingLink = order.trackingLink || (this.courierName ? this.getCourierDefaultLink(this.courierName) : 'https://www.jtexpress.ph/track');
            }
            this.shippingError = '';
            this.statusModal = true;
        },

        printOrderDetails() {
            printSellerOrder(this.detailsOrder);
            this.detailsModal = false;
        },

        onPackingFileChange(event) {
            const file = event.target.files ? event.target.files[0] : null;
            if (!file) return;
            this.packingPhotoFile = file;
            this.packingUploadError = '';
            this.shippingError = '';
            const reader = new FileReader();
            reader.onload = (e) => { this.packingPhotoPreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        removeSelectedPackingPhoto() {
            this.packingPhotoFile = null;
            this.packingPhotoPreview = this.detailsOrder?.packingProof ? ('/' + this.detailsOrder.packingProof) : null;
            this.packingUploadError = '';
            this.shippingError = '';
            const inputEl = document.getElementById('packing-file-input');
            if (inputEl) inputEl.value = '';
            const camEl = document.getElementById('packing-camera-input');
            if (camEl) camEl.value = '';
        },

        async uploadPackingProof() {
            if (!this.packingPhotoFile || !this.detailsOrder) return false;
            this.packingUploading = true;
            this.packingUploadError = '';
            this.shippingError = '';
            try {
                const formData = new FormData();
                formData.append('packingPhoto', this.packingPhotoFile);
                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
                formData.append('_token', token);
                const res = await fetch(`/seller/api/orders/${this.detailsOrder.id}/packing-proof`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Upload failed.');
                this.packingUploadSuccess = true;
                this.packingPhotoPreview = data.packingProofUrl || ('/' + data.packingProof);
                this.detailsOrder.packingProof = data.packingProof;
                this.detailsOrder.packingProofUrl = data.packingProofUrl;
                const idx = this.orders.findIndex(o => o.id === this.detailsOrder.id);
                if (idx !== -1) {
                    this.orders[idx].packingProof = data.packingProof;
                    this.orders[idx].packingProofUrl = data.packingProofUrl;
                }
                this.showToast('✓ Packing proof uploaded successfully!');
                return true;
            } catch(e) {
                this.packingUploadError = e.message || 'Upload failed. Please try again.';
                this.shippingError = this.packingUploadError;
                this.showToast('✕ ' + this.packingUploadError);
                return false;
            } finally {
                this.packingUploading = false;
            }
        },

        async confirmShipmentWithProof() {
            if (!this.detailsOrder || this.statusUpdating || this.packingUploading || this.statusConfirmLoading) return;
            this.shippingError = '';
            this.packingUploadError = '';

            // 1. If proof already recorded or successfully uploaded, prompt confirmation before updating status to Shipped
            if (this.packingUploadSuccess || this.detailsOrder.packingProof) {
                this.requestStatusUpdate(this.detailsOrder, 'Shipped');
                return;
            }

            // 2. If photo is selected, upload it first then prompt confirmation to mark as Shipped
            if (this.packingPhotoFile) {
                const uploaded = await this.uploadPackingProof();
                if (uploaded) {
                    this.requestStatusUpdate(this.detailsOrder, 'Shipped');
                }
                return;
            }

            // 3. No photo selected yet: alert seller and trigger file picker
            this.packingUploadError = this.isStorePickup(this.detailsOrder)
                ? 'Please upload or capture a packing proof photo before marking as Ready for Pickup.'
                : (this.isSpecialDelivery(this.detailsOrder)
                    ? 'Please upload or capture a packing proof photo before processing Special Delivery.'
                    : 'Please upload or capture a packing proof photo before confirming shipment.');
            this.shippingError = this.packingUploadError;
            this.showToast('⚠️ Please select or take a packing proof photo first.');

            const card = document.getElementById('packing-proof-card');
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            const inputEl = document.getElementById('packing-file-input');
            if (inputEl) {
                inputEl.click();
            }
        },

        isStorePickup(currentOrder) {
            const order = currentOrder || this.detailsOrder || this.activeOrder;
            if (!order) return false;
            if (order.is_store_pickup === true || order.isStorePickup === true) return true;
            const pCode = (order.shipping?.provider?.code || order.shipping?.provider_code || '').toLowerCase();
            const pName = (order.shipping?.provider_name || order.shipping?.pricing_provider_name || order.shipping?.fulfillment_provider_name || '').toLowerCase();
            const courier = (order.courierName || '').toLowerCase();
            return pCode === 'store_pickup' || 
                   pName.includes('store pickup') || 
                   pName.includes('in-shop') || 
                   courier.includes('store pickup') || 
                   courier.includes('in-shop');
        },

        isSpecialDelivery(currentOrder) {
            const order = currentOrder || this.detailsOrder || this.activeOrder;
            if (!order) return false;
            if (order.is_special_delivery === true || order.isSpecialDelivery === true) return true;
            const pCode = (order.shipping?.provider?.code || order.shipping?.provider_code || '').toLowerCase();
            const pName = (order.shipping?.provider_name || order.shipping?.pricing_provider_name || order.shipping?.fulfillment_provider_name || '').toLowerCase();
            const courier = (order.courierName || '').toLowerCase();
            return pCode === 'seller_direct' ||
                   pCode === 'special_delivery' ||
                   pName.includes('special delivery') ||
                   pName.includes('artisan rider') ||
                   pName.includes('local direct') ||
                   courier.includes('special delivery') ||
                   courier.includes('artisan rider') ||
                   courier.includes('local direct');
        },

        async openCameraModal() {
            this.packingUploadError = '';
            this.shippingError = '';
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                const inputEl = document.getElementById('packing-camera-input');
                if (inputEl) {
                    inputEl.click();
                } else {
                    alert('Camera API is not supported on this browser. Please upload a photo using Gallery instead.');
                }
                return;
            }
            try {
                this.showCameraModal = true;
                await this.$nextTick();
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.cameraStream = stream;
                if (this.$refs.cameraVideo) {
                    this.$refs.cameraVideo.srcObject = stream;
                }
            } catch(err) {
                console.error('Camera error:', err);
                this.closeCameraModal();
                const inputEl = document.getElementById('packing-camera-input');
                if (inputEl) {
                    inputEl.click();
                } else {
                    this.showToast('Could not access camera (' + (err.message || 'permission denied') + '). Please use Gallery / Files instead.');
                }
            }
        },

        takePhoto() {
            const video = this.$refs.cameraVideo;
            if (!video) return;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            canvas.toBlob((blob) => {
                if (!blob) {
                    alert('Failed to capture photo.');
                    return;
                }
                const file = new File([blob], 'packing_proof_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                this.packingPhotoFile = file;
                this.packingPhotoPreview = canvas.toDataURL('image/jpeg');
                this.packingUploadError = '';
                this.shippingError = '';
                this.packingUploadSuccess = false;
                this.closeCameraModal();
                this.showToast('✓ Packing photo captured! Click Upload or Confirm Shipment.');
            }, 'image/jpeg', 0.9);
        },

        closeCameraModal() {
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach(track => track.stop());
                this.cameraStream = null;
            }
            this.showCameraModal = false;
        },

        normalizeStatus(statusStr) {
            if (!statusStr) return '';
            let s = String(statusStr).toLowerCase().trim().replace(/_/g, ' ');
            if (s === 'processing' || s === 'ready to ship' || s === 'confirmed' || s === 'packed') return 'to ship';
            if (s === 'to receive') return 'shipped';
            if (s === 'out for delivery') return 'in transit';
            if (s === 'received by buyer') return 'completed';
            if (s === 'canceled') return 'cancelled';
            if (s === 'cancellation requested' || s === 'cancellation_pending') return 'cancellation pending';
            return s;
        },

        isStatusDisabled(target, currentOrder) {
            const order = currentOrder || this.activeOrder || this.detailsOrder;
            if (!order) return true;
            const current = this.normalizeStatus(order.status);
            const t = this.normalizeStatus(target);
            if (current === t) return false;
            if (current === 'completed' || current === 'cancelled') return true;
            if (t === 'completed') return true;
            
            const states = ['pending', 'to ship', 'shipped', 'in transit', 'delivered'];
            const currentIdx = states.indexOf(current);
            const targetIdx = states.indexOf(t);
            
            if (currentIdx === -1 || targetIdx === -1) return true;
            return targetIdx < currentIdx;
        },

        isShippingLocked(currentOrder) {
            const order = currentOrder || this.detailsOrder || this.activeOrder;
            if (!order) return false;
            if (this.isStorePickup(order) || this.isSpecialDelivery(order)) return true;
            const s = this.normalizeStatus(order.status);
            return s === 'in transit' || s === 'delivered' || s === 'completed' || s === 'cancelled';
        },

        productImage(product) {
            if (!product) return '/uploads/products/default.jpg';
            if (window.getAppProductImage) {
                return window.getAppProductImage(product.image_url || product.image);
            }
            let rawImg = product.image_url || product.image;
            if (Array.isArray(rawImg)) {
                rawImg = rawImg[0] ?? '';
            }
            if (typeof rawImg === 'string' && (rawImg.startsWith('[') || rawImg.startsWith('{'))) {
                try {
                    const parsed = JSON.parse(rawImg);
                    rawImg = Array.isArray(parsed) ? (parsed[0] ?? '') : parsed;
                } catch(e) {}
            }
            if (!rawImg || typeof rawImg !== 'string') return '/uploads/products/default.jpg';
            if (rawImg.startsWith('http://') || rawImg.startsWith('https://')) return rawImg;
            if (rawImg.startsWith('/')) return rawImg;
            if (rawImg.startsWith('products/')) return '/storage/' + rawImg;
            if (rawImg.startsWith('uploads/')) return '/' + rawImg;
            return '/uploads/products/' + rawImg;
        },

        get filtered() {
            return this.orders.filter(o => {
                const matchSearch = !this.searchTerm ||
                    (o.id && String(o.id).toLowerCase().includes(this.searchTerm.toLowerCase())) ||
                    (o.customer?.name || '').toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                    (o.trackingNumber || '').toLowerCase().includes(this.searchTerm.toLowerCase());
                const s = this.normalizeStatus(o.status);
                const f = this.normalizeStatus(this.statusFilter);
                if (f === 'all') return matchSearch;
                if (f === 'cancellation pending' || f === 'cancellation requests') {
                    return matchSearch && (s === 'cancellation pending' || s === 'cancellation requested');
                }
                if (f === 'return requests' || f === 'return requested' || f === 'return pending' || f === 'returns') {
                    return matchSearch && this.hasPendingReturn(o);
                }
                if (f === 'pending') {
                    return matchSearch && (s === 'pending' || s === 'cancellation pending' || s === 'cancellation requested');
                }
                return matchSearch && (s === f);
            });
        },

        statusColor(s) {
            if (!s) return 'bg-[#FDF8EE] text-[#766C60] border-[#E8DECB]';
            const norm = this.normalizeStatus(s);
            const m = {
                'pending': 'bg-[#FDF8EE] text-[#A16D19] border-[#E8DECB]',
                'to ship': 'bg-[#FDF8EE] text-[#1E1915] border-[#E8DECB]',
                'shipped': 'bg-[#FDF8EE] text-[#766C60] border-[#E8DECB]',
                'to receive': 'bg-[#FDF8EE] text-[#766C60] border-[#E8DECB]',
                'in transit': 'bg-[#FDF8EE] text-[#766C60] border-[#E8DECB]',
                'out for delivery': 'bg-[#FDF8EE] text-[#A16D19] border-[#E8DECB]',
                'delivered': 'bg-[#F0F4EF] text-[#4A6741] border-[#C5D9B8]',
                'completed': 'bg-[#F0F4EF] text-[#4A6741] border-[#C5D9B8]',
                'cancelled': 'bg-[#FEF2F2] text-[#DC2626] border-[#FECACA]',
                'cancellation pending': 'bg-[#FFF7ED] text-[#C2410C] border-[#FFEDD5]',
                'cancellation requested': 'bg-[#FFF7ED] text-[#C2410C] border-[#FFEDD5]',
                'return requested': 'bg-[#FFFBEB] text-[#B45309] border-[#FDE68A]',
                'return requests': 'bg-[#FFFBEB] text-[#B45309] border-[#FDE68A]',
            };
            return m[norm] || 'bg-[#FDF8EE] text-[#766C60] border-[#E8DECB]';
        },

        getCustomerAvatar(order) {
            if (!order || !order.customer) return null;
            const photo = order.customer.profilePhoto || order.customer.profile_photo_url;
            if (!photo || typeof photo !== 'string') return null;
            const p = photo.trim();
            if (!p || p === '' || p.toLowerCase().includes('default.jpg') || p === 'null' || p === 'undefined') return null;
            if (p.startsWith('http://') || p.startsWith('https://') || p.startsWith('/')) {
                return p;
            }
            if (p.startsWith('storage/')) {
                return '/' + p;
            }
            if (p.startsWith('uploads/')) {
                return '/' + p;
            }
            return '/storage/' + p;
        },

        countForStatus(statusKey) {
            if (statusKey === 'all') return this.orders.length;
            const normKey = this.normalizeStatus(statusKey);
            return this.orders.filter(o => {
                const s = this.normalizeStatus(o.status);
                if (normKey === 'cancellation pending' || normKey === 'cancellation requests') {
                    return s === 'cancellation pending' || s === 'cancellation requested';
                }
                if (normKey === 'return requests' || normKey === 'return requested' || normKey === 'return pending' || normKey === 'returns') {
                    return this.hasPendingReturn(o);
                }
                if (normKey === 'pending') {
                    return s === 'pending' || s === 'cancellation pending' || s === 'cancellation requested';
                }
                return s === normKey;
            }).length;
        },

        getStatusDisplayName(order, statusKey) {
            const target = order || this.detailsOrder || this.activeOrder;
            const norm = this.normalizeStatus(statusKey || target?.status || '');
            if (this.isStorePickup(target)) {
                if (norm === 'shipped') return 'Ready for In-Shop Pickup';
                if (norm === 'delivered') return 'Picked Up / Claimed';
            }
            if (this.isSpecialDelivery(target)) {
                if (norm === 'shipped') return 'Special Delivery Processing';
                if (norm === 'in transit') return 'Out for Special Delivery';
            }
            if (norm === 'pending') return 'Pending Acceptance';
            if (norm === 'to ship') return 'To Ship';
            if (norm === 'shipped') return 'Shipped';
            if (norm === 'in transit') return 'In Transit';
            if (norm === 'delivered') return 'Delivered';
            if (norm === 'completed') return 'Completed';
            if (norm === 'cancelled') return 'Cancelled';
            return statusKey || target?.status || 'Unknown';
        },

        requestStatusUpdate(targetOrder, statusToSave) {
            const target = targetOrder || this.detailsOrder || this.activeOrder;
            const statusVal = statusToSave || this.newStatus;
            if (!target || !statusVal) return;

            // Pre-validate standard courier tracking if transitioning to In Transit
            const isPickup = this.isStorePickup(target);
            const isSpecial = this.isSpecialDelivery(target);
            const isLocal = isPickup || isSpecial;
            if (!isLocal && this.normalizeStatus(statusVal) === 'in transit') {
                const currentCourier = this.courierName || target.courierName || 'J&T Express';
                const currentTracking = (this.trackingNumber || target.trackingNumber || '').trim().toUpperCase();
                const valRes = this.validateTrackingNumber(currentCourier, currentTracking);
                if (!valRes.valid) {
                    this.shippingError = valRes.message;
                    return;
                }
            }

            this.shippingError = '';
            this.statusConfirmError = '';
            this.statusConfirmTarget = target;
            this.statusConfirmNewStatus = statusVal;
            this.statusConfirmLoading = false;
            this.showStatusConfirmModal = true;
        },

        cancelStatusConfirm() {
            this.showStatusConfirmModal = false;
            this.statusConfirmTarget = null;
            this.statusConfirmNewStatus = '';
            this.statusConfirmError = '';
            this.statusConfirmLoading = false;
        },

        async executeConfirmedStatusUpdate() {
            if (!this.statusConfirmTarget || !this.statusConfirmNewStatus || this.statusConfirmLoading || this.statusUpdating) return;
            this.statusConfirmLoading = true;
            this.statusConfirmError = '';
            try {
                const success = await this.updateStatus(this.statusConfirmTarget, this.statusConfirmNewStatus);
                if (success) {
                    this.showStatusConfirmModal = false;
                    this.statusConfirmTarget = null;
                    this.statusConfirmNewStatus = '';
                } else {
                    this.statusConfirmError = this.shippingError || 'Failed to update order status. Please try again.';
                }
            } catch(e) {
                this.statusConfirmError = e.message || 'An error occurred while updating status.';
            } finally {
                this.statusConfirmLoading = false;
            }
        },

        async updateStatus(targetOrder, statusToSave) {
            const target = targetOrder || this.detailsOrder || this.activeOrder;
            const statusVal = statusToSave || this.newStatus;
            if (!target || !statusVal) return false;

            this.shippingError = '';
            this.statusUpdating = true;

            try {
                const isPickup = this.isStorePickup(target);
                const isSpecial = this.isSpecialDelivery(target);
                const isLocal = isPickup || isSpecial;
                const currentTracking = isLocal ? null : (this.trackingNumber || target.trackingNumber || '').trim().toUpperCase();
                const currentCourier = isPickup ? 'Store Pickup' : (isSpecial ? 'Special Delivery (Local Artisan Rider)' : (this.courierName || target.courierName || 'J&T Express'));

                if (!isLocal) {
                    if (this.normalizeStatus(statusVal) === 'in transit') {
                        const valRes = this.validateTrackingNumber(currentCourier, currentTracking);
                        if (!valRes.valid) {
                            this.shippingError = valRes.message;
                            this.statusUpdating = false;
                            return false;
                        }
                    } else if (currentTracking) {
                        const valRes = this.validateTrackingNumber(currentCourier, currentTracking);
                        if (!valRes.valid) {
                            this.shippingError = valRes.message;
                            this.statusUpdating = false;
                            return false;
                        }
                    }
                }

                let currentLink = null;
                if (!isLocal && currentTracking) {
                    currentLink = this.trackingLink || target.trackingLink || this.getCourierDefaultLink(currentCourier) || '';
                }

                const payload = {
                    status: statusVal,
                    courierName: currentCourier,
                    trackingNumber: currentTracking || null,
                    trackingLink: currentLink
                };

                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';

                const res = await fetch('/seller/api/orders/' + target.id + '/status', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (res.ok) {
                    const idx = this.orders.findIndex(o => o.id === target.id);
                    if (idx !== -1) {
                        this.orders.splice(idx, 1, data);
                        this.orders = [...this.orders];
                        if (this.detailsOrder && this.detailsOrder.id === target.id) {
                            this.detailsOrder = data;
                        }
                        if (this.activeOrder && this.activeOrder.id === target.id) {
                            this.activeOrder = data;
                        }
                    }
                    this.statusModal = false;
                    this.detailsModal = false;
                    this.activeOrder = null;
                    this.shippingError = '';
                    this.showToast('✓ Order status updated to ' + (data.status || statusVal));
                    return true;
                } else {
                    this.shippingError = data.message || 'Failed to update status. Please check fields and try again.';
                    return false;
                }
            } catch(e) {
                this.shippingError = 'Network error: ' + (e.message || 'Please try again.');
                return false;
            } finally {
                this.statusUpdating = false;
            }
        }
    };
}
</script>

<script id="seller-orders-json" type="application/json">
    {!! json_encode($orders, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>

<div class="space-y-4 sm:space-y-6 w-full max-w-7xl pb-28 lg:pb-12" x-data="sellerOrdersManager()">

    {{-- Floating Toast Notification --}}
    <div x-show="toastMessage" x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         class="fixed top-6 right-6 z-9999 bg-black text-white text-xs font-bold px-4 py-3 rounded-2xl shadow-2xl flex items-center gap-2 border border-white/10" 
         style="display: none;">
        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span x-text="toastMessage"></span>
    </div>

    {{-- Header --}}
    <div id="tour-orders-header" class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 sm:gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">✦ Shop Orders</span>
                <span class="text-xs" style="color: #E8DECB;">•</span>
                <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">Fulfillment Ledger</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight" style="color: #1E1915;">
                    Client <span class="italic font-normal" style="color: #766C60;">Orders</span>
                </h1>
                {{-- Guide Button (Dynamic per active status tab) --}}
                <button type="button" 
                        @click="
                            const slugMap = {
                                'all': 'all',
                                'pending': 'pending',
                                'to ship': 'to-ship',
                                'shipped': 'shipped',
                                'in transit': 'in-transit',
                                'delivered': 'delivered',
                                'completed': 'completed',
                                'cancelled': 'cancelled',
                                'cancellation pending': 'cancellation-pending',
                                'return requests': 'return-requests'
                            };
                            const activeSlug = slugMap[statusFilter] || 'all';
                            window.startSpotlightTour('seller-orders-' + activeSlug);
                        "
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider transition-all shadow-xs cursor-pointer group shrink-0"
                        style="background-color: #FDF8EE; color: #C49520; border: 1.5px solid #C49520;"
                        onmouseover="this.style.backgroundColor='#C49520'; this.style.color='#FFFFFF';"
                        onmouseout="this.style.backgroundColor='#FDF8EE'; this.style.color='#C49520';"
                        title="Start Interactive Guide for this Status">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span x-text="
                        const labels = {
                            'all': 'Orders Guide',
                            'pending': 'Pending Guide',
                            'to ship': 'To Ship Guide',
                            'shipped': 'Shipped Guide',
                            'in transit': 'In Transit Guide',
                            'delivered': 'Delivered Guide',
                            'completed': 'Completed Guide',
                            'cancelled': 'Cancelled Guide',
                            'cancellation pending': 'Cancellations Guide',
                            'return requests': 'Returns Guide'
                        };
                        return labels[statusFilter] || 'Orders Guide';
                    ">Orders Guide</span>
                </button>
            </div>
            <p class="text-xs font-medium mt-1" style="color: #766C60;">Track bespoke commissions, customer delivery details, and fulfillment state.</p>
        </div>
        
        {{-- Actions: Search Input --}}
        <div id="tour-orders-search" class="relative w-full sm:w-72 shrink-0">
            <input type="text" x-model="searchTerm" placeholder="Search order ID or customer..."
                class="w-full h-10 sm:h-11 pl-9 pr-4 rounded-xl text-xs font-semibold shadow-xs outline-none transition-all"
                style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
                onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';"
                onblur="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
            <svg class="w-4 h-4 absolute left-3 top-3 sm:top-3.5" style="color: #766C60;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    {{-- Status Filter Tabs (Responsive: Single swipe track on mobile, graceful wrap on desktop so all 10 statuses are fully visible) --}}
    <style>
        .seller-order-tabs-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .seller-order-tabs-track {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: max-content;
            flex-wrap: nowrap;
        }
        @media (min-width: 1024px) {
            .seller-order-tabs-container {
                overflow: visible !important;
                overflow-x: visible !important;
            }
            .seller-order-tabs-track {
                min-width: 0 !important;
                width: 100% !important;
                flex-wrap: wrap !important;
                row-gap: 0.625rem !important;
                column-gap: 0.5rem !important;
            }
        }
    </style>
    <div id="tour-orders-tabs" class="seller-order-tabs-container no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
        <div class="seller-order-tabs-track border-b pb-3" style="border-color: #E8DECB;">
            @php
                $statusTabs = [
                    'all' => ['label' => 'All Orders', 'icon' => '📋'],
                    'pending' => ['label' => 'Pending', 'icon' => '⏳'],
                    'to ship' => ['label' => 'To Ship', 'icon' => '📦'],
                    'shipped' => ['label' => 'Shipped', 'icon' => '🚚'],
                    'in transit' => ['label' => 'In Transit', 'icon' => '🛣️'],
                    'delivered' => ['label' => 'Delivered', 'icon' => '📬'],
                    'completed' => ['label' => 'Completed', 'icon' => '✅'],
                    'cancelled' => ['label' => 'Cancelled', 'icon' => '❌'],
                    'cancellation pending' => ['label' => 'Cancellation Requests', 'icon' => '⚠️'],
                    'return requests' => ['label' => 'Return Requests', 'icon' => '↩️'],
                ];
            @endphp
            @foreach($statusTabs as $val => $tab)
                <button type="button"
                        @click="statusFilter = '{{ $val }}'"
                        class="px-3.5 py-2 lg:px-3 lg:py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 sm:gap-2 cursor-pointer font-sans shrink-0 active:scale-95 hover:border-[#C49520]"
                        :style="statusFilter === '{{ $val }}' 
                            ? 'background: #1E1915; color: #FFFCF7; box-shadow: 0 2px 8px rgba(30,25,21,0.12); border: 1px solid #1E1915;' 
                            : 'background: #FFFFFF; color: #6C6256; border: 1px solid #ECE3D2;'">
                    <span>{{ $tab['icon'] }} {{ $tab['label'] }}</span>
                    <span class="px-2 py-0.5 text-[10px] rounded-full font-bold transition-colors ml-0.5" 
                          :style="statusFilter === '{{ $val }}' ? 'background: rgba(196,149,32,0.28); color: #DFC97A;' : 'background: #F4EFE6; color: #766C60;'"
                          x-text="countForStatus('{{ $val }}')">
                        {{ $counts[$val] ?? 0 }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Order Capsule List --}}
    <div id="tour-orders-list" class="space-y-2.5 sm:space-y-3">
        <template x-if="filtered.length === 0">
            <div class="rounded-3xl p-10 text-center space-y-2 shadow-xs" style="background: #FFFCF7; border: 1px solid #E8DECB;">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto text-xl" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">🛍️</div>
                <h3 class="font-serif text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">No Orders Found</h3>
                <p class="text-[11px]" style="color: #766C60;">When customer orders match this filter state, they will be catalogued here.</p>
            </div>
        </template>

        <template x-for="order in filtered" :key="order.id">
            <div @click="openDetails(order)"
                 class="group rounded-2xl p-2.5 sm:p-3.5 px-4 sm:px-6 shadow-xs hover:shadow-md transition-all duration-300 cursor-pointer flex items-center justify-between gap-3 active:scale-[0.99]"
                 style="background: #FFFCF7; border: 1px solid #E8DECB;"
                 onmouseover="this.style.borderColor='#C49520';"
                 onmouseout="this.style.borderColor='#E8DECB';">
                
                {{-- Left: Avatar & Order Info --}}
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center font-bold text-xs sm:text-sm shadow-xs shrink-0 overflow-hidden group-hover:scale-105 transition-transform relative" style="background: #1E1915; color: #C49520; border: 1px solid rgba(196,149,32,0.4);">
                        <template x-if="getCustomerAvatar(order)">
                            <img :src="getCustomerAvatar(order)" 
                                 :alt="order.customer?.name || 'Customer'"
                                 class="w-full h-full object-cover"
                                 x-on:error="$el.style.display='none'; if ($el.nextElementSibling) $el.nextElementSibling.style.display='inline-flex';">
                        </template>
                        <span :style="getCustomerAvatar(order) ? 'display:none;' : ''" 
                              x-text="(order.customer?.name || 'O')[0].toUpperCase()"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-sans text-xs sm:text-sm font-extrabold tracking-tight transition-colors"
                                style="color: #1E1915;"
                                x-text="'#LB-' + order.id.slice(-8).toUpperCase()"></h3>
                            <span class="px-2.5 py-0.5 rounded-full border text-[8px] sm:text-[9px] font-black uppercase tracking-wider shrink-0"
                                  :class="statusColor(order.status)"
                                  x-text="isStorePickup(order) && normalizeStatus(order.status) === 'shipped' ? 'Ready for Pickup' : (isSpecialDelivery(order) && normalizeStatus(order.status) === 'shipped' ? 'Special Delivery Processing' : (isSpecialDelivery(order) && normalizeStatus(order.status) === 'in transit' ? 'Out for Special Delivery' : (normalizeStatus(order.status) === 'to ship' ? 'To Ship' : order.status)))"></span>
                            <template x-if="isStorePickup(order)">
                                <span class="px-2 py-0.5 rounded-full text-[8px] sm:text-[9px] font-extrabold uppercase tracking-wider flex items-center gap-1 shrink-0 bg-amber-50 text-amber-900 border border-amber-200 shadow-2xs">
                                    <span>🏬</span>
                                    <span>Store Pickup</span>
                                </span>
                            </template>
                            <template x-if="isSpecialDelivery(order)">
                                <span class="px-2 py-0.5 rounded-full text-[8px] sm:text-[9px] font-extrabold uppercase tracking-wider flex items-center gap-1 shrink-0 bg-blue-50 text-blue-900 border border-blue-200 shadow-2xs">
                                    <span>🏍️</span>
                                    <span>Special Delivery</span>
                                </span>
                            </template>
                            <template x-if="hasPendingReturn(order)">
                                <span class="px-2.5 py-0.5 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-wider flex items-center gap-1 shrink-0 animate-pulse"
                                      style="background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A;">
                                    <span>↩️</span>
                                    <span>Return Requested</span>
                                </span>
                            </template>
                            <template x-if="!hasPendingReturn(order) && getReturnRequest(order) && getReturnRequest(order).status === 'Approved'">
                                <span class="px-2.5 py-0.5 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-wider flex items-center gap-1 shrink-0"
                                      style="background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;">
                                    <span>✓</span>
                                    <span>Return Approved</span>
                                </span>
                            </template>
                            <template x-if="!hasPendingReturn(order) && getReturnRequest(order) && getReturnRequest(order).status === 'Rejected'">
                                <span class="px-2.5 py-0.5 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-wider flex items-center gap-1 shrink-0"
                                      style="background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA;">
                                    <span>✕</span>
                                    <span>Return Declined</span>
                                </span>
                            </template>
                            <template x-if="order.reviews && order.reviews.length > 0">
                                <span class="px-2 py-0.5 rounded-full text-[8px] sm:text-[9px] font-bold uppercase tracking-wider flex items-center gap-1 shrink-0" style="background: #FDF8EE; color: #A16D19; border: 1px solid #E8DECB;">
                                    <span style="color: #C49520;">★</span>
                                    <span x-text="Number(order.reviews[0].rating).toFixed(1) + ' Rated'"></span>
                                </span>
                            </template>
                        </div>
                        <p class="text-[10px] sm:text-[11px] truncate font-medium mt-0.5" style="color: #766C60;">
                            <span class="font-bold" style="color: #1E1915;" x-text="order.customer?.name || 'Customer'"></span>
                            <span style="color: #E8DECB;"> • </span>
                            <span x-text="(order.items ? order.items.length : 0) + ' item' + (order.items && order.items.length !== 1 ? 's' : '')"></span>
                        </p>
                    </div>
                </div>

                {{-- Right: Total Price & Navigation Arrow Pill --}}
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <div class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest"
                             style="color: #766C60;"
                             x-text="order.createdAt ? new Date(order.createdAt).toLocaleDateString('en-PH', {month:'short', day:'numeric'}) : ''"></div>
                        <div class="text-xs sm:text-sm font-black font-sans" style="color: #C49520;" x-text="'₱' + Number(order.totalAmount).toLocaleString()"></div>
                    </div>
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;" onmouseover="this.style.background='#C49520'; this.style.color='#FFF'; this.style.borderColor='#C49520';" onmouseout="this.style.background='#FDF8EE'; this.style.color='#766C60'; this.style.borderColor='#E8DECB';">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Order Detail Modal (Pill Details Bottom Sheet / Modal) --}}
    <div x-show="detailsModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
         @click.self="detailsModal = false">
        
        <div class="w-full sm:max-w-xl bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            {{-- Modal Header Banner --}}
            <div class="relative bg-linear-to-br from-[#2A2A28] to-black p-6 text-white text-center shrink-0">
                <button @click="detailsModal = false" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                
                {{-- Order Icon Circle --}}
                <div class="w-14 h-14 rounded-full bg-linear-to-tr from-[#3D2B1F] to-[#C0420A] flex items-center justify-center text-white font-black text-xl shadow-lg border-2 border-white/20 mx-auto mb-2 overflow-hidden">
                    🛍️
                </div>
                <h2 class="text-base sm:text-lg font-black uppercase tracking-tight" x-text="detailsOrder ? '#LB-' + detailsOrder.id.slice(-8).toUpperCase() : 'Order Details'"></h2>
                <p class="text-xs text-gray-300 font-medium mt-0.5" x-text="detailsOrder &amp;&amp; detailsOrder.createdAt ? new Date(detailsOrder.createdAt).toLocaleDateString('en-PH', {month:'long', day:'numeric', year:'numeric'}) : ''"></p>
                
                <div class="mt-2.5 inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-[10px] font-bold uppercase tracking-widest">
                    <span>Status:</span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase" :class="statusColor(detailsOrder?.status)" x-text="isStorePickup(detailsOrder) && normalizeStatus(detailsOrder?.status) === 'shipped' ? 'Ready for Pickup' : (isSpecialDelivery(detailsOrder) && normalizeStatus(detailsOrder?.status) === 'shipped' ? 'Special Delivery Processing' : (isSpecialDelivery(detailsOrder) && normalizeStatus(detailsOrder?.status) === 'in transit' ? 'Out for Special Delivery' : (normalizeStatus(detailsOrder?.status) === 'to ship' ? 'To Ship' : detailsOrder?.status)))"></span>
                </div>
            </div>

            {{-- Modal Body Content --}}
            <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-5">
                <template x-if="detailsOrder">
                    <div class="space-y-5">
                        
                        {{-- Shipping Error Alert --}}
                        <template x-if="shippingError">
                            <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-[10px] font-bold text-red-600 leading-tight" x-text="shippingError"></div>
                        </template>

                        {{-- Cancellation Request Banner --}}
                        <template x-if="detailsOrder && (normalizeStatus(detailsOrder.status) === 'cancellation pending' || normalizeStatus(detailsOrder.status) === 'cancellation requested')">
                            <div class="p-4 bg-orange-50 border border-orange-200 rounded-2xl space-y-1.5 shadow-2xs">
                                <div class="flex items-center gap-2 text-orange-950 font-black text-xs uppercase tracking-wider">
                                    <span>⏳ Buyer Requested Order Cancellation</span>
                                </div>
                                <p class="text-xs text-orange-900 leading-relaxed">
                                    <span class="font-bold">Reason: </span>
                                    <span x-text="detailsOrder.cancellationReason || 'No explanation provided by buyer.'"></span>
                                </p>
                                <p class="text-[11px] text-orange-800">
                                    Please review and choose to either approve (which cancels the order and replenishes inventory) or decline this request to proceed with crafting/fulfillment.
                                </p>
                            </div>
                        </template>

                        {{-- Return Request Details Banner --}}
                        <template x-if="detailsOrder && getReturnRequest(detailsOrder)">
                            <div class="rounded-2xl border p-4 space-y-3 shadow-2xs transition-all"
                                 :class="{
                                     'bg-amber-50/90 border-amber-200': hasPendingReturn(detailsOrder),
                                     'bg-blue-50/90 border-blue-200': !hasPendingReturn(detailsOrder) && getReturnRequest(detailsOrder).status === 'Approved',
                                     'bg-red-50/90 border-red-200': !hasPendingReturn(detailsOrder) && getReturnRequest(detailsOrder).status === 'Rejected'
                                 }">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base" x-text="hasPendingReturn(detailsOrder) ? '↩️' : (getReturnRequest(detailsOrder).status === 'Approved' ? '✓' : '✕')"></span>
                                        <span class="font-black text-xs uppercase tracking-wider"
                                              :class="{
                                                  'text-amber-950': hasPendingReturn(detailsOrder),
                                                  'text-blue-950': !hasPendingReturn(detailsOrder) && getReturnRequest(detailsOrder).status === 'Approved',
                                                  'text-red-950': !hasPendingReturn(detailsOrder) && getReturnRequest(detailsOrder).status === 'Rejected'
                                              }"
                                              x-text="hasPendingReturn(detailsOrder) ? 'Buyer Requested Return / Refund' : ('Return Request ' + getReturnRequest(detailsOrder).status)">
                                        </span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider"
                                          :class="{
                                              'bg-amber-100 text-amber-800 border border-amber-200': hasPendingReturn(detailsOrder),
                                              'bg-blue-100 text-blue-800 border border-blue-200': !hasPendingReturn(detailsOrder) && getReturnRequest(detailsOrder).status === 'Approved',
                                              'bg-red-100 text-red-800 border border-red-200': !hasPendingReturn(detailsOrder) && getReturnRequest(detailsOrder).status === 'Rejected'
                                          }"
                                          x-text="getReturnRequest(detailsOrder).status">
                                    </span>
                                </div>

                                {{-- Customer Reason --}}
                                <div class="text-xs space-y-1"
                                     :class="{
                                         'text-amber-900': hasPendingReturn(detailsOrder),
                                         'text-blue-900': !hasPendingReturn(detailsOrder) && getReturnRequest(detailsOrder).status === 'Approved',
                                         'text-red-900': !hasPendingReturn(detailsOrder) && getReturnRequest(detailsOrder).status === 'Rejected'
                                     }">
                                    <div class="flex items-start gap-1">
                                        <span class="font-bold shrink-0">Reason:</span>
                                        <span class="font-medium" x-text="getReturnRequest(detailsOrder).reason || 'No description provided.'"></span>
                                    </div>
                                </div>

                                {{-- Seller Instructions / Note (if approved or rejected) --}}
                                <template x-if="getReturnRequest(detailsOrder).adminComment">
                                    <div class="p-2.5 rounded-xl bg-white/80 border border-black/5 text-[11px] space-y-0.5">
                                        <span class="font-bold text-[10px] uppercase tracking-wider text-gray-600 block">Artisan Note:</span>
                                        <span class="text-gray-800 font-medium" x-text="getReturnRequest(detailsOrder).adminComment"></span>
                                    </div>
                                </template>

                                {{-- Attached Proof Photos --}}
                                <template x-if="getProofImages(getReturnRequest(detailsOrder)).length > 0">
                                    <div class="space-y-1.5 pt-1">
                                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-600 flex items-center gap-1.5">
                                            <span>📷 Attached Proof Photos</span>
                                            <span class="text-[9px] font-medium text-gray-500">(Click photo to zoom)</span>
                                        </div>
                                        <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                            <template x-for="(img, pIdx) in getProofImages(getReturnRequest(detailsOrder))" :key="pIdx">
                                                <button type="button" 
                                                        @click="openProofLightbox(img)"
                                                        class="w-16 h-16 rounded-xl overflow-hidden border-2 border-white shadow-xs hover:scale-105 transition-all shrink-0 bg-gray-100 cursor-pointer">
                                                    <img :src="img" class="w-full h-full object-cover" alt="Proof Photo">
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                {{-- Quick action buttons inside banner when pending --}}
                                <template x-if="hasPendingReturn(detailsOrder)">
                                    <div class="pt-2 border-t border-amber-200/80 flex items-center justify-end gap-2">
                                        <button type="button" 
                                                @click="openRejectReturnModal(detailsOrder)"
                                                :disabled="approveReturnLoading || rejectReturnLoading"
                                                class="px-3.5 py-1.5 rounded-full border border-red-200 text-red-700 bg-white hover:bg-red-50 text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                                            ✕ Decline Return
                                        </button>
                                        <button type="button" 
                                                @click="openApproveReturnModal(detailsOrder)"
                                                :disabled="approveReturnLoading || rejectReturnLoading"
                                                class="px-4 py-1.5 rounded-full bg-[#C49520] hover:bg-[#B38519] text-white text-[10px] font-black uppercase tracking-wider shadow-xs transition-all cursor-pointer flex items-center gap-1">
                                            <span>✓</span> Approve Return
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- PACKING PROOF UPLOAD CARD — shown when To Ship --}}
                        <div id="packing-proof-card" x-show="normalizeStatus(detailsOrder.status) === 'to ship'" x-transition class="bg-emerald-50/70 p-4 sm:p-5 rounded-2xl border border-emerald-200 space-y-3 shadow-xs">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">📦</span>
                                <div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-emerald-900">
                                        Packing Proof Required <span class="text-red-500 font-bold">*</span>
                                    </div>
                                    <div class="text-[10px] text-emerald-700 mt-0.5">Please take or upload a photo of the packaged garments before marking this order as shipped.</div>
                                </div>
                            </div>

                            {{-- Hidden file inputs for direct camera and file gallery --}}
                            <input type="file" id="packing-file-input" class="hidden" accept="image/*" @change="onPackingFileChange($event)">
                            <input type="file" id="packing-camera-input" class="hidden" accept="image/*" capture="environment" @change="onPackingFileChange($event)">

                            {{-- Success state: already uploaded in database or session --}}
                            <template x-if="packingUploadSuccess || detailsOrder.packingProof">
                                <div class="space-y-2">
                                    <div class="w-full rounded-2xl overflow-hidden border-2 border-emerald-300 bg-white max-h-60 flex items-center justify-center p-1">
                                        <img :src="packingPhotoPreview || ('/' + detailsOrder.packingProof)" class="max-h-56 w-full object-contain rounded-xl" alt="Packing Proof">
                                    </div>
                                    <div class="flex items-center justify-between text-[10px] font-bold text-emerald-700 pt-1">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Packing proof attached and verified.
                                        </span>
                                        <button type="button" @click="document.getElementById('packing-file-input').click()" class="text-emerald-800 hover:text-emerald-950 underline font-black uppercase tracking-wider text-[9px] cursor-pointer">
                                            Replace Photo ↻
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Upload state: no photo uploaded yet --}}
                            <template x-if="!packingUploadSuccess && !detailsOrder.packingProof">
                                <div class="space-y-3">
                                    {{-- Preview if file chosen locally --}}
                                    <template x-if="packingPhotoPreview">
                                        <div class="space-y-2">
                                            <div class="w-full rounded-2xl overflow-hidden border-2 border-dashed border-emerald-400 bg-white max-h-60 flex items-center justify-center p-1 relative group">
                                                <img :src="packingPhotoPreview" class="max-h-56 w-full object-contain rounded-xl" alt="Preview">
                                                <button type="button" @click="removeSelectedPackingPhoto()" class="absolute top-3 right-3 bg-red-600 hover:bg-red-700 text-white rounded-full p-1.5 shadow-md text-xs font-bold transition-all cursor-pointer" title="Remove photo">
                                                    ✕
                                                </button>
                                            </div>
                                            <div class="flex items-center justify-between text-[10px] text-emerald-800 font-bold px-1">
                                                <span>✓ Photo ready to upload</span>
                                                <button type="button" @click="removeSelectedPackingPhoto()" class="text-red-600 hover:text-red-700 font-black uppercase text-[9px]">Remove</button>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Error alert --}}
                                    <template x-if="packingUploadError">
                                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-[11px] font-bold text-red-600 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span x-text="packingUploadError"></span>
                                        </div>
                                    </template>

                                    {{-- File picker / camera buttons --}}
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <button type="button" @click="openCameraModal()" class="flex flex-col items-center justify-center gap-1.5 py-3.5 rounded-2xl border-2 border-dashed border-emerald-300 bg-white hover:bg-emerald-50 cursor-pointer transition-all group shadow-2xs">
                                            <svg class="w-6 h-6 text-emerald-600 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="text-[9px] font-black uppercase tracking-wider text-emerald-800">Open Camera</span>
                                        </button>
                                        <button type="button" @click="document.getElementById('packing-file-input').click()" class="flex flex-col items-center justify-center gap-1.5 py-3.5 rounded-2xl border-2 border-dashed border-emerald-300 bg-white hover:bg-emerald-50 cursor-pointer transition-all group shadow-2xs">
                                            <svg class="w-6 h-6 text-emerald-600 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L8 8m4-4l4 4"/></svg>
                                            <span class="text-[9px] font-black uppercase tracking-wider text-emerald-800">Gallery / Choose File</span>
                                        </button>
                                    </div>

                                    {{-- Direct standalone upload button when file is selected --}}
                                    <template x-if="packingPhotoFile">
                                        <button type="button"
                                            @click="uploadPackingProof()"
                                            :disabled="packingUploading"
                                            class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                                            <template x-if="packingUploading">
                                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                            </template>
                                            <template x-if="!packingUploading">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L8 8m4-4l4 4"/></svg>
                                            </template>
                                            <span x-text="packingUploading ? 'Uploading Photo...' : 'Upload Packing Proof Photo'"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- COURIER & SHIPPING CARD — hidden when Store Pickup, Special Delivery, Pending, To Ship, Cancellation Pending, or Return Request --}}
                        <div x-show="!isStorePickup(detailsOrder) && !isSpecialDelivery(detailsOrder) && normalizeStatus(detailsOrder.status) !== 'pending' && normalizeStatus(detailsOrder.status) !== 'to ship' && normalizeStatus(detailsOrder.status) !== 'cancellation pending' && normalizeStatus(detailsOrder.status) !== 'cancellation requested' && !getReturnRequest(detailsOrder) && !normalizeStatus(detailsOrder.status).includes('return')" x-transition class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100/70 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-[9px] font-black uppercase tracking-widest text-indigo-900 flex items-center gap-1.5">
                                    <span>🚚 Courier & Shipping Information</span>
                                </div>
                                <template x-if="isShippingLocked(detailsOrder)">
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md text-[9px] font-black uppercase tracking-widest flex items-center gap-1">
                                        🔒 Read Only / Locked
                                    </span>
                                </template>
                            </div>

                            <template x-if="isShippingLocked(detailsOrder)">
                                <p class="text-[10px] text-amber-700 font-medium italic leading-relaxed">
                                    Shipping information is locked and read-only because the order is in transit, delivered, completed, or cancelled.
                                </p>
                            </template>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                     <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Courier / Shipping Company</label>
                                     <select x-model="courierName" @change="onCourierChange()" :disabled="isShippingLocked(detailsOrder)"
                                         class="w-full h-9 px-3 bg-white border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:border-[#C0420A] disabled:bg-gray-100 disabled:text-gray-500 cursor-pointer">
                                         <option value="J&T Express">J&T Express (Default)</option>
                                         <option value="SPX Express">SPX Express</option>
                                         <option value="LBC Express">LBC Express</option>
                                         <option value="Flash Express">Flash Express</option>
                                         <option value="Ninja Van">Ninja Van</option>
                                         <option value="2GO Express">2GO Express</option>
                                         <option value="JRS Express">JRS Express</option>
                                         <option value="Lalamove">Lalamove</option>
                                         <option value="GrabExpress">GrabExpress</option>
                                         <option value="Other Courier">Other Courier</option>
                                     </select>
                                </div>
                                <div>
                                     <div class="flex items-center justify-between mb-1">
                                         <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block">Tracking Number <span class="text-red-500">*</span></label>
                                         <span class="text-[8px] font-mono text-gray-400" x-text="trackingNumber ? (trackingNumber.trim().length + ' chars') : ''"></span>
                                     </div>
                                     <input type="text" 
                                         x-model="trackingNumber" 
                                         @input="shippingError = ''"
                                         :placeholder="getCourierConfig(courierName).placeholder" 
                                         :disabled="isShippingLocked(detailsOrder)"
                                         :class="{
                                             'border-emerald-500 focus:border-emerald-600 bg-emerald-50/20 text-emerald-950': trackingNumber && trackingValidationStatus.valid,
                                             'border-red-400 focus:border-red-500 bg-red-50/20 text-red-950': trackingNumber && !trackingValidationStatus.valid,
                                             'border-gray-200 focus:border-[#C0420A] bg-white text-gray-900': !trackingNumber
                                         }"
                                         class="w-full h-9 px-3 border rounded-xl text-xs font-mono font-bold outline-none uppercase transition-colors disabled:bg-gray-100 disabled:text-gray-500">
                                </div>
                            </div>

                            {{-- Live Tracking Format Feedback & Example Guide --}}
                            <div class="space-y-1.5 pt-0.5">
                                <div class="flex items-center justify-between flex-wrap gap-1 text-[10px]">
                                    <div class="text-gray-500 flex items-center gap-1">
                                        <span class="font-bold text-gray-700">Format Guide:</span>
                                        <span class="italic text-gray-600" x-text="getCourierConfig(courierName).hint"></span>
                                    </div>
                                    <template x-if="trackingNumber">
                                        <span :class="trackingValidationStatus.valid ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-red-700 bg-red-50 border-red-200'"
                                              class="px-2 py-0.5 rounded-md border font-bold text-[9px] inline-flex items-center gap-1 shadow-2xs"
                                              x-text="trackingValidationStatus.message"></span>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Official Courier Tracking URL</label>
                                <input type="url" x-model="trackingLink" placeholder="https://www.jtexpress.ph/track" :disabled="isShippingLocked(detailsOrder)"
                                    class="w-full h-9 px-3 bg-white border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:border-[#C0420A] disabled:bg-gray-100 disabled:text-gray-500">
                                <p class="text-[9px] text-gray-400 mt-1">Automatically updated based on chosen courier, or enter a direct package tracking link.</p>
                            </div>
                        </div>




                        {{-- Order Status History Timeline Audit Trail --}}
                        <template x-if="detailsOrder.status_histories && detailsOrder.status_histories.length > 0">
                            <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 space-y-3">
                                <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A] flex items-center justify-between">
                                    <span>Order Status History Audit Trail</span>
                                    <span class="text-gray-400" x-text="detailsOrder.status_histories.length + ' entry(ies)'"></span>
                                </div>
                                <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                    <template x-for="hist in detailsOrder.status_histories" :key="hist.id">
                                        <div class="flex items-start justify-between text-xs py-1.5 border-b border-gray-200/50 last:border-0">
                                            <div>
                                                <span class="font-black text-black text-[11px]" x-text="hist.newStatus"></span>
                                                <span class="text-[9px] text-gray-400 block" x-text="'Updated by ' + (hist.userRole || 'system')"></span>
                                            </div>
                                            <div class="text-right text-[10px] font-medium text-gray-500" x-text="hist.createdAt ? new Date(hist.createdAt).toLocaleString('en-PH', {month:'short', day:'numeric', hour:'numeric', minute:'2-digit'}) : ''"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Customer Rating & Feedback Card --}}
                        <template x-if="detailsOrder.reviews && detailsOrder.reviews.length > 0">
                            <div class="bg-amber-50/60 p-4 rounded-2xl border border-amber-200/80 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="text-[9px] font-black uppercase tracking-widest text-amber-900 flex items-center gap-1.5">
                                        <span>⭐ Customer Rating & Feedback</span>
                                    </div>
                                    <span class="text-[9px] font-bold text-amber-800" x-text="detailsOrder.reviews.length + ' Review(s)'"></span>
                                </div>
                                
                                <div class="space-y-2.5">
                                    <template x-for="rev in detailsOrder.reviews" :key="rev.id">
                                        <div class="bg-white p-3.5 rounded-xl border border-amber-100 shadow-xs space-y-2">
                                            <div class="flex items-center justify-between flex-wrap gap-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex items-center text-amber-400 text-sm">
                                                        <template x-for="star in 5" :key="star">
                                                             <span :class="star <= rev.rating ? 'text-amber-400' : 'text-gray-200'">★</span>
                                                        </template>
                                                    </div>
                                                    <span class="text-xs font-black text-black" x-text="rev.rating + '.0'"></span>
                                                </div>
                                                <span class="text-[9px] font-medium text-gray-400" x-text="rev.createdAt ? new Date(rev.createdAt).toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'}) : ''"></span>
                                            </div>

                                            <p class="text-xs text-gray-700 font-medium leading-relaxed" x-text="rev.comment || 'No written comment provided.'"></p>

                                            {{-- Review Images if any --}}
                                            <template x-if="rev.images">
                                                <div class="flex flex-wrap gap-2 pt-1">
                                                    <template x-for="(img, idx) in (typeof rev.images === 'string' ? JSON.parse(rev.images || '[]') : (rev.images || []))" :key="idx">
                                                        <a :href="img.startsWith('http') || img.startsWith('/') ? img : '/storage/' + img" target="_blank" class="w-12 h-12 rounded-lg overflow-hidden border border-gray-200 shrink-0 shadow-xs hover:opacity-80 transition-opacity">
                                                            <img :src="img.startsWith('http') || img.startsWith('/') ? img : '/storage/' + img" class="w-full h-full object-cover">
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Buyer & Shipping Info Card --}}
                        <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 space-y-3">
                            <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]" x-text="isStorePickup(detailsOrder) ? 'Buyer & In-Shop Pickup Details' : (isSpecialDelivery(detailsOrder) ? 'Buyer & Special Delivery Details' : 'Buyer & Shipping Details')"></div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Customer Name</div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <div class="w-5 h-5 rounded-md overflow-hidden bg-gray-100 flex items-center justify-center text-[9px] font-bold text-gray-700 shrink-0">
                                            <template x-if="getCustomerAvatar(detailsOrder)">
                                                <img :src="getCustomerAvatar(detailsOrder)" class="w-full h-full object-cover" x-on:error="$el.style.display='none'">
                                            </template>
                                            <span :style="getCustomerAvatar(detailsOrder) ? 'display:none;' : ''" x-text="(detailsOrder.customer?.name || 'O')[0].toUpperCase()"></span>
                                        </div>
                                        <div class="font-black text-black" x-text="detailsOrder.customer?.name || 'Unknown Buyer'"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Phone Contact</div>
                                    <div class="font-bold text-black mt-0.5" x-text="buyerPhone(detailsOrder)"></div>
                                </div>
                            </div>

                            <template x-if="isStorePickup(detailsOrder)">
                                <div class="pt-2 border-t border-gray-200/60 text-xs space-y-1">
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Fulfillment Method & Location</div>
                                    <div class="text-gray-800 font-medium mt-0.5 leading-relaxed flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-900 rounded font-bold text-[10px]">🏬 In-Shop Store Pickup</span>
                                        <span>· Lumban, Laguna Workshop</span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="isSpecialDelivery(detailsOrder)">
                                <div class="pt-2 border-t border-gray-200/60 text-xs space-y-1">
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Fulfillment Method & Destination</div>
                                    <div class="text-gray-800 font-medium mt-0.5 leading-relaxed flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-900 rounded font-bold text-[10px]">🏍️ Special Delivery (Local Artisan Rider)</span>
                                    </div>
                                    <div class="text-gray-700 font-medium mt-0.5 leading-relaxed" x-text="formatAddress(detailsOrder)"></div>
                                </div>
                            </template>

                            <template x-if="!isStorePickup(detailsOrder) && !isSpecialDelivery(detailsOrder)">
                                <div class="pt-2 border-t border-gray-200/60 text-xs">
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Delivery Address</div>
                                    <div class="text-gray-700 font-medium mt-0.5 leading-relaxed" x-text="formatAddress(detailsOrder)"></div>
                                </div>
                            </template>
                        </div>

                        {{-- Purchased Product Items --}}
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Purchased Items</div>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest"
                                      x-text="(detailsOrder.items ? detailsOrder.items.length : 0) + ' item(s)'"></span>
                            </div>

                            <div class="space-y-2">
                                <template x-for="item in detailsOrder.items || []" :key="item.id">
                                    <div class="flex items-center gap-3 bg-white border border-gray-100 rounded-2xl p-3 shadow-xs">
                                        <div class="w-12 h-14 bg-gray-50 rounded-xl overflow-hidden shrink-0 border border-gray-100">
                                            <img :src="item.image_url || productImage(item.product)" class="w-full h-full object-cover object-top" x-on:error="$event.target.src='/uploads/products/default.jpg'">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-xs font-bold text-black truncate" x-text="(item.display_variation && item.display_variation !== 'Original') ? item.display_variation : (item.product?.name || item.product_name || 'Product Item')"></h4>
                                            <div class="flex flex-wrap gap-2 text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                                                <span x-show="item.size" x-text="'Size: ' + item.size"></span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="text-xs font-black text-black" x-text="item.quantity + ' × ₱' + Number(item.price).toLocaleString()"></div>
                                            <div class="text-[9px] font-bold text-[#C0420A]" x-text="'₱' + (Number(item.price) * item.quantity).toLocaleString()"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between text-gray-500">
                                    <span>Items Subtotal</span>
                                    <span class="font-bold text-black" x-text="'₱' + (Number(detailsOrder.totalAmount) - Number(detailsOrder.shipping ? detailsOrder.shipping.shipping_fee : (detailsOrder.shippingFee || 0))).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                </div>
                                <div class="flex items-center justify-between text-gray-500">
                                    <span class="flex items-center gap-1.5">
                                        <span x-text="isStorePickup(detailsOrder) ? 'Fulfillment' : 'Shipping Logistics'"></span>
                                        <template x-if="isStorePickup(detailsOrder)">
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 bg-amber-50 border border-amber-200 rounded text-amber-800">
                                                Store Pickup (Free)
                                            </span>
                                        </template>
                                        <template x-if="!isStorePickup(detailsOrder) && detailsOrder.shipping">
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 bg-white border border-gray-200 rounded text-gray-700" 
                                                  x-text="detailsOrder.shipping.provider_name + ' (' + Number(detailsOrder.shipping.chargeable_weight).toFixed(2) + ' kg)'"></span>
                                        </template>
                                    </span>
                                    <span class="font-bold text-black" x-text="isStorePickup(detailsOrder) ? '₱0.00' : ('₱' + Number(detailsOrder.shipping ? detailsOrder.shipping.shipping_fee : (detailsOrder.shippingFee || 0)).toLocaleString(undefined, {minimumFractionDigits: 2}))"></span>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-dashed border-gray-200">
                                    <span class="font-bold text-gray-700 uppercase tracking-wider text-[11px]">Grand Total Amount</span>
                                    <span class="text-base font-black text-[#C0420A]" x-text="'₱' + Number(detailsOrder.totalAmount).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Payment Information --}}
                        <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 space-y-2 text-xs">
                            <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Payment Details</div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Method</span>
                                <span class="font-black text-black" x-text="formatPaymentMethod(detailsOrder)"></span>
                            </div>
                            <template x-if="['GCASH', 'MAYA'].includes((detailsOrder.paymentMethod || '').toUpperCase()) && detailsOrder.paymentReference && !detailsOrder.paymentReference.startsWith('COD-')">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Reference No.</span>
                                    <span class="font-mono text-xs font-bold text-gray-700" x-text="detailsOrder.paymentReference"></span>
                                </div>
                            </template>
                            <template x-if="detailsOrder.paymentProof">
                                <div class="pt-2 border-t border-gray-200/60 flex items-center justify-between">
                                    <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Receipt File</span>
                                    <button type="button" @click="receiptUrl = detailsOrder.payment_proof_url || ('/orders/' + detailsOrder.id + '/payment-proof'); receiptModal = true;" class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-[#C0420A] hover:underline">
                                        View Proof ↗
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer Actions --}}
            <div class="p-4 sm:p-5 bg-gray-50 border-t border-gray-100 flex flex-col gap-3 shrink-0">
                <template x-if="shippingError || packingUploadError">
                    <div class="p-3 bg-red-50 border border-red-200 rounded-2xl text-[11px] font-bold text-red-600 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="shippingError || packingUploadError"></span>
                    </div>
                </template>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3">
                    <button @click="detailsModal = false"
                        class="px-5 sm:px-6 py-2.5 sm:py-3 rounded-full border border-gray-300 bg-white text-[10px] font-black uppercase tracking-widest text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition-all cursor-pointer shrink-0 text-center order-last sm:order-first">
                        Close
                    </button>

                    {{-- Return Request Actions: Decline Return OR Approve Return --}}
                    <template x-if="detailsOrder && hasPendingReturn(detailsOrder)">
                        <div class="flex-1 flex flex-wrap sm:flex-nowrap items-center justify-end gap-2">
                            <button type="button" 
                                @click="openRejectReturnModal(detailsOrder)"
                                :disabled="approveReturnLoading || rejectReturnLoading"
                                class="px-4 py-2.5 sm:py-3 border border-red-200 hover:bg-red-50 text-red-600 rounded-full text-[10px] font-black uppercase tracking-wider whitespace-nowrap transition-all cursor-pointer shrink-0 flex items-center justify-center gap-1.5">
                                <span>✕</span> Decline Return
                            </button>
                            <button type="button" 
                                @click="openApproveReturnModal(detailsOrder)"
                                :disabled="approveReturnLoading || rejectReturnLoading"
                                style="background-color: #C49520; color: #ffffff;"
                                class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-[#C49520] hover:bg-[#B38519] disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-1.5 shadow-sm cursor-pointer shrink-0">
                                <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Approve Return</span>
                            </button>
                        </div>
                    </template>

                    {{-- Cancellation Pending Actions: Decline OR Approve --}}
                    <template x-if="detailsOrder && !hasPendingReturn(detailsOrder) && (normalizeStatus(detailsOrder.status) === 'cancellation pending' || normalizeStatus(detailsOrder.status) === 'cancellation requested')">
                        <div class="flex-1 flex flex-wrap sm:flex-nowrap items-center justify-end gap-2">
                            <button type="button" 
                                @click="openDeclineCancellationModal(detailsOrder)"
                                :disabled="approveCancellationLoading || declineCancellationLoading"
                                class="px-4 py-2.5 sm:py-3 border border-gray-300 hover:bg-gray-100 text-gray-700 rounded-full text-[10px] font-black uppercase tracking-wider whitespace-nowrap transition-all cursor-pointer shrink-0 flex items-center justify-center gap-1.5">
                                <span>✕</span> Decline Cancellation
                            </button>
                            <button type="button" 
                                @click="openApproveCancellationModal(detailsOrder)"
                                :disabled="approveCancellationLoading || declineCancellationLoading"
                                style="background-color: #DC2626; color: #ffffff;"
                                class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-1.5 shadow-sm cursor-pointer shrink-0">
                                <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Approve Cancellation</span>
                            </button>
                        </div>
                    </template>

                    {{-- Pending Order Actions: Reject Payment (counts as Cancel) OR Verify & Accept --}}
                    <template x-if="detailsOrder && !hasPendingReturn(detailsOrder) && normalizeStatus(detailsOrder.status) === 'pending'">
                        <div class="flex-1 flex flex-wrap sm:flex-nowrap items-center justify-end gap-2">
                            <button type="button" 
                                @click="openRejectPaymentModal(detailsOrder)"
                                class="px-4 py-2.5 sm:py-3 border border-red-200 hover:bg-red-50 text-red-600 rounded-full text-[10px] font-black uppercase tracking-wider whitespace-nowrap transition-all cursor-pointer shrink-0 flex items-center justify-center gap-1.5">
                                <span>✕</span> Reject & Cancel
                            </button>
                            <button type="button" 
                                @click="openVerifyPaymentModal(detailsOrder)"
                                :disabled="statusUpdating || verifyingPayment"
                                style="background-color: #059669; color: #ffffff;"
                                class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-1.5 shadow-sm cursor-pointer shrink-0">
                                <template x-if="statusUpdating || verifyingPayment">
                                    <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                </template>
                                <template x-if="!statusUpdating && !verifyingPayment">
                                    <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </template>
                                <span x-text="(statusUpdating || verifyingPayment) ? (['GCASH', 'MAYA'].includes((detailsOrder?.paymentMethod || '').toUpperCase()) ? 'Verifying Payment...' : 'Accepting Order...') : (['GCASH', 'MAYA'].includes((detailsOrder?.paymentMethod || '').toUpperCase()) ? 'Verify & Accept' : 'Accept Order')"></span>
                                <span x-show="!statusUpdating && !verifyingPayment" class="text-xs">➔</span>
                            </button>
                        </div>
                    </template>

                    {{-- Button for To Ship status: Upload Packing Proof & Confirm Shipment / Ready for Pickup --}}
                    <template x-if="detailsOrder && !hasPendingReturn(detailsOrder) && normalizeStatus(detailsOrder.status) === 'to ship'">
                        <div class="flex-1 flex justify-end">
                            <button type="button"
                                @click="confirmShipmentWithProof()"
                                :disabled="packingUploading || statusUpdating"
                                :style="(packingUploadSuccess || detailsOrder.packingProof) ? 'background-color: #059669; color: #ffffff;' : (packingPhotoFile ? 'background-color: #C0420A; color: #ffffff;' : 'background-color: #000000; color: #ffffff;')"
                                class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 hover:opacity-90 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer">
                                <template x-if="packingUploading || statusUpdating">
                                    <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                </template>
                                <template x-if="!packingUploading && !statusUpdating">
                                    <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </template>
                                <span x-text="packingUploading ? 'Uploading Photo...' : (statusUpdating ? 'Updating Status...' : ((packingUploadSuccess || detailsOrder.packingProof) ? (isStorePickup(detailsOrder) ? 'Mark Ready for In-Shop Pickup ➔' : (isSpecialDelivery(detailsOrder) ? 'Process Special Delivery ➔' : 'Confirm Shipment (Mark Shipped) ➔')) : (packingPhotoFile ? (isStorePickup(detailsOrder) ? 'Upload & Mark Ready for Pickup ➔' : (isSpecialDelivery(detailsOrder) ? 'Upload & Process Special Delivery ➔' : 'Upload & Confirm Shipment ➔')) : (isStorePickup(detailsOrder) ? 'Upload Proof & Mark Ready ➔' : (isSpecialDelivery(detailsOrder) ? 'Upload Proof & Process ➔' : 'Upload Proof & Confirm Shipment ➔')))))"></span>
                            </button>
                        </div>
                    </template>

                    {{-- Button for Shipped status: For Store Pickup (Mark as Picked Up / Claimed) vs Special Delivery (Out for Special Delivery) vs Courier (Mark In Transit) --}}
                    <template x-if="detailsOrder && !hasPendingReturn(detailsOrder) && normalizeStatus(detailsOrder.status) === 'shipped'">
                        <div class="flex-1 flex justify-end">
                            <template x-if="isStorePickup(detailsOrder)">
                                <button type="button"
                                    @click="confirmMarkAsDelivered(detailsOrder)"
                                    :disabled="statusUpdating"
                                    style="background-color: #059669; color: #ffffff;"
                                    class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer">
                                    <template x-if="statusUpdating">
                                        <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                    </template>
                                    <template x-if="!statusUpdating">
                                        <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <span x-text="statusUpdating ? 'Updating...' : 'Mark as Picked Up / Claimed ➔'"></span>
                                </button>
                            </template>
                            <template x-if="!isStorePickup(detailsOrder) && isSpecialDelivery(detailsOrder)">
                                <button type="button"
                                    @click="requestStatusUpdate(detailsOrder, 'In Transit')"
                                    :disabled="statusUpdating || statusConfirmLoading"
                                    style="background-color: #1D4ED8; color: #ffffff;"
                                    class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-blue-700 hover:bg-blue-800 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer">
                                    <template x-if="statusUpdating || statusConfirmLoading">
                                        <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                    </template>
                                    <template x-if="!statusUpdating && !statusConfirmLoading">
                                        <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </template>
                                    <span x-text="(statusUpdating || statusConfirmLoading) ? 'Updating...' : 'Out for Special Delivery ➔'"></span>
                                </button>
                            </template>
                            <template x-if="!isStorePickup(detailsOrder) && !isSpecialDelivery(detailsOrder)">
                                <button type="button"
                                    @click="(() => {
                                        const valRes = validateTrackingNumber(courierName, trackingNumber);
                                        if (!valRes.valid) {
                                            shippingError = valRes.message;
                                        } else {
                                            shippingError = '';
                                            requestStatusUpdate(detailsOrder, 'In Transit');
                                        }
                                    })()"
                                    :disabled="statusUpdating || statusConfirmLoading"
                                    style="background-color: #000000; color: #ffffff;"
                                    class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-black hover:bg-[#C0420A] disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer">
                                    <template x-if="statusUpdating || statusConfirmLoading">
                                        <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                    </template>
                                    <template x-if="!statusUpdating && !statusConfirmLoading">
                                        <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </template>
                                    <span x-text="(statusUpdating || statusConfirmLoading) ? 'Updating...' : 'Mark In Transit ➔'"></span>
                                </button>
                            </template>
                        </div>
                    </template>

                    {{-- Button for In Transit status: Mark as Delivered --}}
                    <template x-if="detailsOrder && !hasPendingReturn(detailsOrder) && normalizeStatus(detailsOrder.status) === 'in transit'">
                        <div class="flex-1 flex justify-end">
                            <button type="button"
                                @click="confirmMarkAsDelivered(detailsOrder)"
                                :disabled="statusUpdating"
                                style="background-color: #059669; color: #ffffff;"
                                class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                                <template x-if="statusUpdating">
                                    <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                </template>
                                <template x-if="!statusUpdating">
                                    <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </template>
                                <span x-text="statusUpdating ? 'Mark as Delivered ➔' : 'Mark as Delivered ➔'"></span>
                            </button>
                        </div>
                    </template>

                    {{-- Status notice for Delivered status --}}
                    <template x-if="detailsOrder && !hasPendingReturn(detailsOrder) && normalizeStatus(detailsOrder.status) === 'delivered'">
                        <div class="flex-1 py-2.5 sm:py-3 px-4 bg-teal-50 border border-teal-200 text-teal-800 text-[10px] font-black uppercase tracking-wider rounded-full flex items-center justify-center gap-2 text-center">
                            <span>📦 Delivered — Awaiting Customer Receipt Confirmation</span>
                        </div>
                    </template>

                    {{-- Status notice for Completed status --}}
                    <template x-if="detailsOrder && !hasPendingReturn(detailsOrder) && normalizeStatus(detailsOrder.status) === 'completed'">
                        <div class="flex-1 py-2.5 sm:py-3 px-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-[10px] font-black uppercase tracking-wider rounded-full flex items-center justify-center gap-2 text-center">
                            <span>✓ Order Completed by Customer</span>
                        </div>
                    </template>

                    {{-- Status notice for Cancelled status --}}
                    <template x-if="detailsOrder && normalizeStatus(detailsOrder.status) === 'cancelled'">
                        <div class="flex-1 py-2.5 sm:py-3 px-4 bg-red-50 border border-red-200 text-red-800 text-[10px] font-black uppercase tracking-wider rounded-full flex items-center justify-center gap-2 text-center">
                            <span>✕ Order Cancelled</span>
                            <span class="font-normal text-[10px] text-red-600 truncate max-w-xs" x-text="detailsOrder.cancellationReason ? '(' + detailsOrder.cancellationReason + ')' : ''"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Floating Receipt Image Modal --}}
    <div x-show="receiptModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md" x-cloak>
        <div @click.away="receiptModal = false" class="relative max-w-lg w-full bg-white rounded-3xl overflow-hidden shadow-2xl p-6 flex flex-col items-center">
            <div class="w-full flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
                <h3 class="font-serif text-lg font-bold text-black">Proof of Payment</h3>
                <button type="button" @click="receiptModal = false"
                    class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="w-full bg-gray-50 rounded-2xl overflow-hidden flex items-center justify-center border border-gray-100 max-h-[70vh]">
                <img :src="receiptUrl" class="max-w-full max-h-[60vh] object-contain" alt="Payment Proof">
            </div>
            
            <div class="w-full mt-4 flex gap-3">
                <a :href="receiptUrl" download="receipt" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                    Download
                </a>
                <button type="button" @click="receiptModal = false" class="flex-1 py-3 bg-black hover:bg-[#C0420A] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Live Camera Overlay Modal --}}
    <div x-show="showCameraModal" @click.self="closeCameraModal()" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" x-cloak style="display: none;">
        <div class="relative max-w-md w-full bg-black rounded-3xl overflow-hidden shadow-2xl flex flex-col items-center border border-white/20 p-5 space-y-4">
            <div class="w-full flex items-center justify-between text-white">
                <h3 class="text-xs font-black uppercase tracking-widest flex items-center gap-2">
                    <span>📷 Capture Packing Proof</span>
                </h3>
                <button type="button" @click="closeCameraModal()" class="w-8 h-8 rounded-full bg-white/20 text-white flex items-center justify-center hover:bg-white/30 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Live Video Stream Container --}}
            <div class="w-full bg-gray-900 rounded-2xl overflow-hidden aspect-4/3 relative flex items-center justify-center border border-white/10 shadow-inner">
                <video x-ref="cameraVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                <div class="absolute inset-0 border-2 border-emerald-500/30 rounded-2xl pointer-events-none"></div>
            </div>

            {{-- Capture & Cancel Actions --}}
            <div class="w-full flex items-center justify-center gap-3 pt-1">
                <button type="button" @click="closeCameraModal()" class="flex-1 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider transition-all text-center">
                    Cancel
                </button>
                <button type="button" @click="takePhoto()" class="flex-2 py-3 bg-emerald-500 hover:bg-emerald-400 text-black font-black text-xs uppercase tracking-widest rounded-xl shadow-lg flex items-center justify-center gap-2 transition-all active:scale-95">
                    <span class="w-3 h-3 rounded-full bg-black"></span>
                    Snap Photo
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Scroll Navigator -->
    <div 
        x-data="{
            showScrollTop: false,
            showScrollBottom: true,
            container: null,
            initNavigator() {
                this.container = document.querySelector('main');
                if (this.container) {
                    this.container.addEventListener('scroll', () => this.checkScroll());
                    this.$nextTick(() => this.checkScroll());
                }
            },
            checkScroll() {
                if (!this.container) return;
                const scrolled = this.container.scrollTop;
                const maxScroll = this.container.scrollHeight - this.container.clientHeight;
                this.showScrollTop = scrolled > 100;
                this.showScrollBottom = maxScroll > 0 && scrolled < maxScroll - 100;
            },
            scrollToTop() {
                if (this.container) {
                    this.container.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },
            scrollToBottom() {
                if (this.container) {
                    this.container.scrollTo({ top: this.container.scrollHeight, behavior: 'smooth' });
                }
            }
        }"
        x-init="initNavigator()"
        class="fixed bottom-20 sm:bottom-6 right-4 sm:right-6 z-50 flex flex-col gap-2"
    >
        <!-- Scroll to Top Button -->
        <button 
            x-show="showScrollTop"
            @click="scrollToTop()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="w-12 h-12 bg-black hover:bg-[#C0420A] text-white rounded-full flex items-center justify-center shadow-lg transition-all"
            title="Scroll to Top"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path>
            </svg>
        </button>

        <!-- Scroll to Bottom Button -->
        <button 
            x-show="showScrollBottom"
            @click="scrollToBottom()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            class="w-12 h-12 bg-black hover:bg-[#C0420A] text-white rounded-full flex items-center justify-center shadow-lg transition-all"
            title="Scroll to Bottom"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
    </div>

    {{-- Delivery Confirmation Modal --}}
    <div x-show="showDeliveryConfirmModal" 
         x-transition:enter="transition ease-out duration-300"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="showDeliveryConfirmModal = false"
         x-cloak>
        
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 text-center space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="h-1.5 w-full bg-linear-to-r from-emerald-500 to-teal-600 absolute top-0 left-0"></div>

            <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto text-2xl shadow-inner mt-2 transition-all"
                 :class="deliveryConfirmSuccess ? 'bg-emerald-100 text-emerald-700 ring-4 ring-emerald-200' : 'bg-emerald-50 text-emerald-600'">
                <span x-text="deliveryConfirmSuccess ? '✓' : (isStorePickup(deliveryConfirmOrder) ? '🏬' : (isSpecialDelivery(deliveryConfirmOrder) ? '🏍️' : '🚚'))"></span>
            </div>

            <div>
                <h3 class="text-base font-black text-black uppercase tracking-tight" 
                    x-text="deliveryConfirmSuccess 
                        ? (isStorePickup(deliveryConfirmOrder) ? 'Order Marked as Picked Up / Claimed!' : (isSpecialDelivery(deliveryConfirmOrder) ? 'Special Delivery Completed!' : 'Order Marked as Delivered!')) 
                        : (isStorePickup(deliveryConfirmOrder) ? 'Confirm Order Claim / Pickup' : (isSpecialDelivery(deliveryConfirmOrder) ? 'Confirm Special Delivery Completion' : 'Confirm Order Delivery'))"></h3>
                <p class="text-xs text-gray-500 font-medium mt-1" 
                   x-text="deliveryConfirmSuccess 
                        ? 'Status updated. Closing in 2 seconds...' 
                        : (isStorePickup(deliveryConfirmOrder) ? 'Are you sure this order has been claimed by the customer at your workshop?' : (isSpecialDelivery(deliveryConfirmOrder) ? 'Are you sure this order has been delivered by your artisan rider?' : 'Are you sure this order has been delivered?'))"></p>
                <template x-if="deliveryConfirmOrder">
                    <div class="mt-2 py-1.5 px-3 bg-gray-50 rounded-xl text-[11px] font-bold text-gray-700 inline-block border border-gray-100">
                        <span x-text="'#LB-' + deliveryConfirmOrder.id.slice(-8).toUpperCase()"></span>
                        <span class="text-gray-400"> • </span>
                        <span x-text="deliveryConfirmOrder.customer?.name || 'Customer'"></span>
                    </div>
                </template>
            </div>

            <template x-if="deliveryConfirmError">
                <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-bold text-red-600" x-text="deliveryConfirmError"></div>
            </template>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="showDeliveryConfirmModal = false"
                    :disabled="deliveryConfirmLoading || deliveryConfirmSuccess"
                    class="flex-1 py-2.5 rounded-full border border-gray-200 bg-white text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                    Cancel
                </button>
                <button type="button" 
                    @click="executeMarkAsDelivered()"
                    :disabled="deliveryConfirmLoading || deliveryConfirmSuccess"
                    :class="deliveryConfirmSuccess ? 'bg-emerald-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                    class="flex-1 py-2.5 rounded-full text-white text-[10px] font-black uppercase tracking-widest transition-all shadow-md flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed">
                    <template x-if="deliveryConfirmLoading">
                        <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <template x-if="!deliveryConfirmLoading && !deliveryConfirmSuccess">
                        <svg class="w-3.5 h-3.5 fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="deliveryConfirmSuccess">
                        <svg class="w-3.5 h-3.5 fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <span x-text="deliveryConfirmSuccess ? 'Saved' : (isStorePickup(deliveryConfirmOrder) ? 'Confirm Picked Up / Claimed' : (isSpecialDelivery(deliveryConfirmOrder) ? 'Confirm Special Delivery' : 'Confirm Delivered'))"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Universal Order Status Update Confirmation Modal --}}
    <div x-show="showStatusConfirmModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="cancelStatusConfirm()"
         x-cloak>
        
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-4 border border-gray-100 relative overflow-hidden"
             @click.stop>
            <div class="h-1.5 w-full bg-linear-to-r from-[#C49520] via-amber-500 to-emerald-600 absolute top-0 left-0"></div>

            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-lg shrink-0 shadow-xs"
                     style="background: #1E1915; color: #C49520; border: 1px solid rgba(196,149,32,0.4);">
                    <span>⚡</span>
                </div>
                <div>
                    <h3 class="text-sm font-black text-[#1E1915] uppercase tracking-tight">Confirm Order Status Update</h3>
                    <p class="text-[10px] text-gray-500 font-medium">Please review the details before proceeding.</p>
                </div>
            </div>

            <template x-if="statusConfirmTarget">
                <div class="space-y-3">
                    {{-- Order & Customer Capsule --}}
                    <div class="p-3.5 bg-[#FFFCF7] rounded-2xl border border-[#E8DECB] space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-bold text-[10px] uppercase tracking-wider">Order</span>
                            <span class="font-extrabold text-xs text-[#1E1915]" x-text="'#LB-' + statusConfirmTarget.id.slice(-8).toUpperCase()"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-bold text-[10px] uppercase tracking-wider">Customer</span>
                            <span class="font-bold text-xs text-gray-800 truncate max-w-50" x-text="statusConfirmTarget.customer?.name || 'Customer'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-bold text-[10px] uppercase tracking-wider">Fulfillment</span>
                            <span class="font-bold text-[11px] px-2 py-0.5 rounded-full"
                                  :class="isStorePickup(statusConfirmTarget) ? 'bg-amber-50 text-amber-900 border border-amber-200' : (isSpecialDelivery(statusConfirmTarget) ? 'bg-blue-50 text-blue-900 border border-blue-200' : 'bg-gray-100 text-gray-800')">
                                <span x-text="isStorePickup(statusConfirmTarget) ? '🏬 Store Pickup' : (isSpecialDelivery(statusConfirmTarget) ? '🏍️ Special Delivery' : ('🚚 ' + (courierName || statusConfirmTarget.courierName || 'Standard Courier')))"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Status Transition Visual --}}
                    <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100 space-y-2">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">Status Progression</div>
                        <div class="flex items-center justify-center gap-3">
                            <div class="flex-1 text-center py-2 px-2.5 rounded-xl border border-gray-200 bg-white">
                                <div class="text-[9px] text-gray-400 font-bold uppercase">Current Status</div>
                                <div class="text-xs font-black text-gray-700 mt-0.5" x-text="getStatusDisplayName(statusConfirmTarget, statusConfirmTarget.status)"></div>
                            </div>
                            <div class="text-gray-400 font-bold text-sm shrink-0">➔</div>
                            <div class="flex-1 text-center py-2 px-2.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800">
                                <div class="text-[9px] text-emerald-600 font-bold uppercase">New Status</div>
                                <div class="text-xs font-black text-emerald-900 mt-0.5" x-text="getStatusDisplayName(statusConfirmTarget, statusConfirmNewStatus)"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Contextual Prompt / Notice --}}
                    <div class="text-center px-2">
                        <p class="text-xs font-bold text-gray-800">Are you sure you want to update this order?</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">This action will update the buyer's fulfillment tracker in real time.</p>
                    </div>

                    <template x-if="statusConfirmError">
                        <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs font-bold text-red-600 text-center" x-text="statusConfirmError"></div>
                    </template>
                </div>
            </template>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="cancelStatusConfirm()"
                    :disabled="statusConfirmLoading || statusUpdating"
                    class="flex-1 py-3 rounded-full border border-gray-200 bg-white text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                    @click="executeConfirmedStatusUpdate()"
                    :disabled="statusConfirmLoading || statusUpdating"
                    style="background-color: #059669; color: #ffffff;"
                    class="flex-1 py-3 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="statusConfirmLoading || statusUpdating">
                        <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <span x-text="(statusConfirmLoading || statusUpdating) ? 'Updating...' : 'Confirm Update'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Verify Payment Confirmation Modal --}}
    <div x-show="showVerifyModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="showVerifyModal = false"
         x-cloak
         style="display: none;">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="h-1.5 w-full bg-linear-to-r from-emerald-500 to-teal-600 absolute top-0 left-0"></div>

            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg shrink-0"
                     x-text="['GCASH', 'MAYA'].includes((verifyOrderTarget?.paymentMethod || '').toUpperCase()) ? '💳' : (isStorePickup(verifyOrderTarget) ? '🏬' : '📦')">
                </div>
                <div>
                    <h3 class="text-sm font-black text-black uppercase tracking-tight"
                        x-text="['GCASH', 'MAYA'].includes((verifyOrderTarget?.paymentMethod || '').toUpperCase()) ? 'Verify Payment & Accept Order' : (isStorePickup(verifyOrderTarget) ? 'Accept Order (Store Pickup)' : (isSpecialDelivery(verifyOrderTarget) ? 'Accept Order (Special Delivery)' : 'Accept Order (Cash on Delivery)'))"></h3>
                    <p class="text-[10px] text-gray-500 font-medium"
                       x-text="['GCASH', 'MAYA'].includes((verifyOrderTarget?.paymentMethod || '').toUpperCase()) ? 'Verify that the payment was credited to your account.' : (isStorePickup(verifyOrderTarget) ? 'Confirm and accept this In-Shop Pickup order for workshop claim.' : (isSpecialDelivery(verifyOrderTarget) ? 'Confirm and accept this Special Delivery order for fulfillment.' : 'Confirm and accept this order for fulfillment.'))"></p>
                </div>
            </div>

            <template x-if="verifyOrderTarget">
                <div class="space-y-3 text-xs">
                    <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-bold text-[10px] uppercase">Payment Method</span>
                            <span class="font-black text-black uppercase" x-text="formatPaymentMethod(verifyOrderTarget)"></span>
                        </div>
                        <template x-if="['GCASH', 'MAYA'].includes((verifyOrderTarget.paymentMethod || '').toUpperCase()) && verifyOrderTarget.paymentReference && !verifyOrderTarget.paymentReference.startsWith('COD-')">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400 font-bold text-[10px] uppercase">Reference Number</span>
                                <span class="font-mono font-bold text-indigo-700 text-xs px-2 py-0.5 bg-indigo-50 rounded-md select-all" x-text="verifyOrderTarget.paymentReference"></span>
                            </div>
                        </template>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-bold text-[10px] uppercase" x-text="['GCASH', 'MAYA'].includes((verifyOrderTarget.paymentMethod || '').toUpperCase()) ? 'Expected Amount' : 'Amount to Collect'"></span>
                            <span class="font-black text-emerald-700 text-sm" x-text="'₱' + Number(verifyOrderTarget.totalAmount).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                        </div>
                    </div>

                    {{-- Mini receipt preview thumbnail (only for electronic payment methods) --}}
                    <template x-if="['GCASH', 'MAYA'].includes((verifyOrderTarget.paymentMethod || '').toUpperCase()) && verifyOrderTarget.paymentProof">
                        <div class="space-y-1">
                            <span class="text-gray-400 font-bold text-[10px] uppercase">Customer Receipt Proof</span>
                            <div class="p-2 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-between gap-3">
                                <div class="w-12 h-14 bg-black/5 rounded-xl overflow-hidden shrink-0 border border-gray-200">
                                    <img :src="verifyOrderTarget.payment_proof_url || ('/orders/' + verifyOrderTarget.id + '/payment-proof')" class="w-full h-full object-cover" alt="Proof">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold text-gray-700">Receipt Screenshot</p>
                                    <p class="text-[10px] text-gray-400">Click to view in full resolution</p>
                                </div>
                                <button type="button" @click="receiptUrl = verifyOrderTarget.payment_proof_url || ('/orders/' + verifyOrderTarget.id + '/payment-proof'); receiptModal = true;" class="px-3 py-1.5 bg-black text-white text-[9px] font-black uppercase tracking-wider rounded-xl hover:bg-[#C0420A] transition-all">
                                    Inspect ↗
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Electronic Payment Verification Alert --}}
                    <template x-if="['GCASH', 'MAYA'].includes((verifyOrderTarget.paymentMethod || '').toUpperCase())">
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-2xl text-[10px] text-amber-900 leading-relaxed">
                            <span class="font-black uppercase tracking-wider block mb-0.5">⚠️ Artisan Verification Check</span>
                            Please confirm in your <strong x-text="verifyOrderTarget.paymentMethod || 'GCash'"></strong> mobile app that you received <strong>₱<span x-text="Number(verifyOrderTarget.totalAmount).toLocaleString(undefined, {minimumFractionDigits:2})"></span></strong> with reference <strong x-text="verifyOrderTarget.paymentReference || 'N/A'"></strong> before proceeding.
                        </div>
                    </template>

                    {{-- Non-electronic Payment Order Acceptance Info --}}
                    <template x-if="!['GCASH', 'MAYA'].includes((verifyOrderTarget.paymentMethod || '').toUpperCase())">
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-2xl text-[10px] text-blue-900 leading-relaxed">
                            <span class="font-black uppercase tracking-wider block mb-0.5" x-text="isStorePickup(verifyOrderTarget) ? 'ℹ️ In-Shop Store Pickup' : (isSpecialDelivery(verifyOrderTarget) ? 'ℹ️ Special Delivery Order' : 'ℹ️ Cash on Delivery Order')"></span>
                            <span x-text="isStorePickup(verifyOrderTarget) ? 'Payment of ₱' + Number(verifyOrderTarget.totalAmount).toLocaleString(undefined, {minimumFractionDigits:2}) + ' will be collected directly in cash when the customer claims the order at the workshop.' : 'Payment of ₱' + Number(verifyOrderTarget.totalAmount).toLocaleString(undefined, {minimumFractionDigits:2}) + ' will be collected from the customer upon delivery. No online payment reference verification is required. Please proceed to accept and prepare the order.'"></span>
                        </div>
                    </template>
                </div>
            </template>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="showVerifyModal = false"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                    @click="executeVerifyPayment()"
                    :disabled="statusUpdating || verifyingPayment"
                    style="background-color: #059669; color: #ffffff;"
                    class="flex-1 py-3 rounded-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <template x-if="statusUpdating || verifyingPayment">
                        <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <span x-text="(statusUpdating || verifyingPayment) 
                        ? (['GCASH', 'MAYA'].includes((verifyOrderTarget?.paymentMethod || '').toUpperCase()) ? 'Verifying & Accepting...' : 'Accepting Order...') 
                        : (['GCASH', 'MAYA'].includes((verifyOrderTarget?.paymentMethod || '').toUpperCase()) ? '✓ Confirm & Accept Order' : '✓ Accept & Prepare Order')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Reject Payment Reason Modal --}}
    <div x-show="showRejectModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="showRejectModal = false"
         x-cloak
         style="display: none;">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="h-1.5 w-full bg-red-600 absolute top-0 left-0"></div>

            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-lg shrink-0">
                    ✕
                </div>
                <div>
                    <h3 class="text-sm font-black text-black uppercase tracking-tight">Reject Payment & Cancel Order</h3>
                    <p class="text-[10px] text-gray-500 font-medium">Rejecting this payment will cancel the order and return stock to inventory.</p>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <template x-if="rejectError">
                    <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-[10px] font-bold text-red-600" x-text="rejectError"></div>
                </template>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Rejection Reason <span class="text-red-500">*</span></label>
                    <select x-model="rejectReason" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-800 outline-none focus:border-red-500 focus:bg-white transition-all">
                        <option value="Reference number does not match">Reference number does not match receipt</option>
                        <option value="Amount does not match">Amount paid does not match order total</option>
                        <option value="Receipt unclear or unreadable">Receipt screenshot is blurry or cropped</option>
                        <option value="Payment not received in wallet">Transaction not received in artisan account/wallet</option>
                        <option value="Duplicate reference number">Duplicate or recycled transaction reference</option>
                        <option value="Sent to wrong account / QR">Payment sent to wrong recipient/QR</option>
                        <option value="Other">Other / Custom reason</option>
                    </select>
                </div>

                <template x-if="rejectReason === 'Other'">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Custom Explanation <span class="text-red-500">*</span></label>
                        <textarea x-model="rejectCustomReason" rows="3" placeholder="Provide specific feedback for the customer..." class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-medium outline-none focus:border-red-500 focus:bg-white resize-none"></textarea>
                    </div>
                </template>

                <div class="p-3 bg-red-50 border border-red-200 rounded-2xl text-[10px] text-red-700 leading-relaxed">
                    <strong>Notice:</strong> Rejecting will automatically cancel the order, restore stock to your inventory, and notify the customer.
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="showRejectModal = false"
                    :disabled="rejectLoading"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Back
                </button>
                <button type="button" 
                    @click="executeRejectPayment()"
                    :disabled="rejectLoading"
                    class="flex-1 py-3 rounded-full bg-red-600 hover:bg-red-700 text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <template x-if="rejectLoading">
                        <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <span x-text="rejectLoading ? 'Submitting...' : '✕ Reject & Cancel Order'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Fullscreen Receipt Lightbox Modal (High z-index to overlay verify modal) --}}
    <div x-show="receiptModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 bg-black/90 backdrop-blur-md z-100000 flex flex-col items-center justify-center p-4"
         @click.self="receiptModal = false"
         @keydown.escape.window="receiptModal = false"
         x-cloak
         style="display: none;">
        
        {{-- Lightbox Header --}}
        <div class="w-full max-w-2xl flex items-center justify-between text-white pb-3 px-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">📄 Customer Receipt Proof</span>
            </div>
            <div class="flex items-center gap-3">
                <a :href="receiptUrl" target="_blank" download class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-[10px] font-bold rounded-full transition-all flex items-center gap-1">
                    <span>Open in new tab</span> ↗
                </a>
                <button type="button" @click="receiptModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center font-bold text-sm transition-all cursor-pointer">
                    ✕
                </button>
            </div>
        </div>

        {{-- Lightbox Image Box --}}
        <div class="max-w-2xl max-h-[80vh] bg-black/40 rounded-2xl overflow-hidden border border-white/10 flex items-center justify-center shadow-2xl p-2">
            <img :src="receiptUrl" class="max-w-full max-h-[75vh] object-contain rounded-xl" alt="Payment Receipt">
        </div>
    </div>

    {{-- Cancel Order (Artisan) Modal --}}
    <div x-show="showCancelOrderModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="showCancelOrderModal = false"
         x-cloak
         style="display: none;">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="h-1.5 w-full bg-red-600 absolute top-0 left-0"></div>

            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-lg shrink-0">
                    ✕
                </div>
                <div>
                    <h3 class="text-sm font-black text-black uppercase tracking-tight">Cancel Order</h3>
                    <p class="text-[10px] text-gray-500 font-medium">Please specify why this order is being cancelled.</p>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <template x-if="sellerCancelError">
                    <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-[10px] font-bold text-red-600" x-text="sellerCancelError"></div>
                </template>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Cancellation Reason <span class="text-red-500">*</span></label>
                    <select x-model="sellerCancelReason" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-800 outline-none focus:border-red-500 focus:bg-white transition-all">
                        <option value="Out of stock / fabric unavailable">Out of stock / fabric unavailable</option>
                        <option value="Customization cannot be fulfilled">Customization cannot be fulfilled</option>
                        <option value="Buyer requested cancellation">Buyer requested cancellation</option>
                        <option value="Unreachable / unresponsive buyer">Unreachable / unresponsive buyer</option>
                        <option value="Incorrect pricing or listing error">Incorrect pricing or listing error</option>
                        <option value="Other">Other / Custom reason</option>
                    </select>
                </div>

                <template x-if="sellerCancelReason === 'Other'">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Custom Explanation <span class="text-red-500">*</span></label>
                        <textarea x-model="sellerCustomCancelReason" rows="3" placeholder="Provide specific reason for cancellation..." class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-medium outline-none focus:border-red-500 focus:bg-white resize-none"></textarea>
                    </div>
                </template>

                <div class="p-3 bg-red-50 border border-red-200 rounded-2xl text-[10px] text-red-700 leading-relaxed">
                    <strong>Notice:</strong> Cancelling will automatically replenish product stock in your inventory and notify the customer.
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="showCancelOrderModal = false"
                    :disabled="sellerCancelLoading"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Back
                </button>
                <button type="button" 
                    @click="executeSellerCancelOrder()"
                    :disabled="sellerCancelLoading"
                    class="flex-1 py-3 rounded-full bg-red-600 hover:bg-red-700 text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <template x-if="sellerCancelLoading">
                        <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <span x-text="sellerCancelLoading ? 'Cancelling...' : 'Confirm Cancellation'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Approve Order Cancellation Modal --}}
    <div x-show="showApproveCancellationModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="showApproveCancellationModal = false"
         x-cloak
         style="display: none;">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="h-1.5 w-full bg-red-600 absolute top-0 left-0"></div>

            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-lg shrink-0">
                    ✕
                </div>
                <div>
                    <h3 class="text-sm font-black text-black uppercase tracking-tight">Approve Order Cancellation</h3>
                    <p class="text-[10px] text-gray-500 font-medium">Cancel this order as requested by the buyer.</p>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <template x-if="approveCancellationError">
                    <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-[10px] font-bold text-red-600" x-text="approveCancellationError"></div>
                </template>

                <div class="p-3 bg-gray-50 rounded-2xl border border-gray-100 space-y-1">
                    <span class="text-[9px] font-black uppercase tracking-wider text-gray-400">Buyer Cancellation Reason</span>
                    <p class="text-xs font-semibold text-gray-800" x-text="approveCancellationTarget?.cancellationReason || 'No specific explanation provided.'"></p>
                </div>

                <div class="p-3 bg-red-50 border border-red-200 rounded-2xl text-[10px] text-red-700 leading-relaxed">
                    <strong>Notice:</strong> Approving will immediately cancel order <span class="font-bold font-mono" x-text="approveCancellationTarget ? '#LB-' + approveCancellationTarget.id.slice(-8).toUpperCase() : ''"></span>, restore product and size stock back into your active inventory, and notify the customer.
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="showApproveCancellationModal = false"
                    :disabled="approveCancellationLoading"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Keep Request
                </button>
                <button type="button" 
                    @click="executeApproveCancellation()"
                    :disabled="approveCancellationLoading"
                    class="flex-1 py-3 rounded-full bg-red-600 hover:bg-red-700 text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <template x-if="approveCancellationLoading">
                        <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <span x-text="approveCancellationLoading ? 'Approving...' : '✓ Approve Cancellation'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Decline Order Cancellation Modal --}}
    <div x-show="showDeclineCancellationModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="showDeclineCancellationModal = false"
         x-cloak
         style="display: none;">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="h-1.5 w-full bg-amber-500 absolute top-0 left-0"></div>

            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-lg shrink-0">
                    !
                </div>
                <div>
                    <h3 class="text-sm font-black text-black uppercase tracking-tight">Decline Cancellation Request</h3>
                    <p class="text-[10px] text-gray-500 font-medium">Return this order to active pending status for fulfillment.</p>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <template x-if="declineCancellationError">
                    <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-[10px] font-bold text-red-600" x-text="declineCancellationError"></div>
                </template>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Reason for Declining <span class="text-red-500">*</span></label>
                    <select x-model="declineCancellationReason" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-800 outline-none focus:border-amber-500 focus:bg-white transition-all">
                        <option value="Order has already been prepared / fabric cut">Order has already been prepared / fabric cut</option>
                        <option value="Bespoke customization in progress">Bespoke customization in progress</option>
                        <option value="Item already packed and queued for courier">Item already packed and queued for courier</option>
                        <option value="Cancellation policy period expired">Cancellation policy period expired</option>
                        <option value="Other">Other / Custom explanation</option>
                    </select>
                </div>

                <template x-if="declineCancellationReason === 'Other'">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Custom Explanation <span class="text-red-500">*</span></label>
                        <textarea x-model="declineCancellationCustomReason" rows="3" placeholder="Provide reason for declining to the buyer..." class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-medium outline-none focus:border-amber-500 focus:bg-white resize-none"></textarea>
                    </div>
                </template>

                <div class="p-3 bg-amber-50 border border-amber-200 rounded-2xl text-[10px] text-amber-800 leading-relaxed">
                    <strong>Notice:</strong> Declining will revert the order to <span class="font-bold">Pending</span> status. Inventory stock remains reserved and the customer will be notified of your reason.
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="showDeclineCancellationModal = false"
                    :disabled="declineCancellationLoading"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Back
                </button>
                <button type="button" 
                    @click="executeDeclineCancellation()"
                    :disabled="declineCancellationLoading"
                    class="flex-1 py-3 rounded-full bg-[#1E1915] hover:bg-black text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <template x-if="declineCancellationLoading">
                        <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <span x-text="declineCancellationLoading ? 'Submitting...' : '✕ Confirm Decline'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Approve Return Modal --}}
    <div x-show="showApproveReturnModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="showApproveReturnModal = false"
         x-cloak
         style="display: none;">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="h-1.5 w-full bg-[#C49520] absolute top-0 left-0"></div>

            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <div class="w-10 h-10 rounded-full bg-amber-100 text-[#C49520] flex items-center justify-center font-bold text-lg shrink-0">
                    ↩️
                </div>
                <div>
                    <h3 class="text-sm font-black text-black uppercase tracking-tight">Approve Return Request</h3>
                    <p class="text-[10px] text-gray-500 font-medium" x-text="approveReturnTarget ? '#LB-' + approveReturnTarget.id.slice(-8).toUpperCase() + ' · ' + (approveReturnTarget.customer?.name || 'Customer') : ''"></p>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <template x-if="approveReturnError">
                    <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-[10px] font-bold text-red-600" x-text="approveReturnError"></div>
                </template>

                <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-2xl space-y-1 text-amber-900">
                    <span class="font-bold text-[10px] uppercase tracking-wider text-amber-950 block">Customer's Stated Reason:</span>
                    <p class="text-xs font-medium" x-text="getReturnRequest(approveReturnTarget)?.reason || 'No description provided.'"></p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">
                        Return Instructions / Return Address <span class="text-gray-400 font-normal">(Optional)</span>
                    </label>
                    <textarea x-model="approveReturnInstructions" rows="3" 
                              placeholder="e.g. Please ship back via J&T to [Your Studio Address]. Ensure item is carefully packed in original dust bag."
                              class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-medium outline-none focus:border-[#C49520] focus:bg-white resize-none"></textarea>
                    <p class="text-[9px] text-gray-400">These instructions will be emailed and displayed in the customer's order tracker.</p>
                </div>

                <div class="p-3 bg-blue-50 border border-blue-200 rounded-2xl text-[10px] text-blue-900 leading-relaxed">
                    <strong>Notice:</strong> Once approved, the customer will receive step-by-step instructions to dispatch the item back to your workshop or drop-off location.
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="showApproveReturnModal = false"
                    :disabled="approveReturnLoading"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                    @click="executeApproveReturn()"
                    :disabled="approveReturnLoading"
                    class="flex-1 py-3 rounded-full bg-[#C49520] hover:bg-[#B38519] text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <template x-if="approveReturnLoading">
                        <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <span x-text="approveReturnLoading ? 'Approving...' : '✓ Confirm Approval'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Reject / Decline Return Modal --}}
    <div x-show="showRejectReturnModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="showRejectReturnModal = false"
         x-cloak
         style="display: none;">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="h-1.5 w-full bg-red-500 absolute top-0 left-0"></div>

            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <div class="w-10 h-10 rounded-full bg-red-100 text-red-700 flex items-center justify-center font-bold text-lg shrink-0">
                    ✕
                </div>
                <div>
                    <h3 class="text-sm font-black text-black uppercase tracking-tight">Decline Return Request</h3>
                    <p class="text-[10px] text-gray-500 font-medium" x-text="rejectReturnTarget ? '#LB-' + rejectReturnTarget.id.slice(-8).toUpperCase() + ' · ' + (rejectReturnTarget.customer?.name || 'Customer') : ''"></p>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <template x-if="rejectReturnError">
                    <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-[10px] font-bold text-red-600" x-text="rejectReturnError"></div>
                </template>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Reason for Declining <span class="text-red-500">*</span></label>
                    <select x-model="rejectReturnReason" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-800 outline-none focus:border-red-500 focus:bg-white transition-all">
                        <option value="Item is not in original condition / beyond return window">Item is not in original condition / beyond return window</option>
                        <option value="Product matches specifications and description">Product matches specifications and description</option>
                        <option value="Damage appears caused by misuse or mishandling">Damage appears caused by misuse or mishandling</option>
                        <option value="Missing original tags, packaging, or accessories">Missing original tags, packaging, or accessories</option>
                        <option value="Custom tailored / bespoke sizing non-returnable">Custom tailored / bespoke sizing non-returnable</option>
                        <option value="Other">Other / Custom explanation</option>
                    </select>
                </div>

                <template x-if="rejectReturnReason === 'Other'">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Custom Explanation <span class="text-red-500">*</span></label>
                        <textarea x-model="rejectReturnCustomReason" rows="3" placeholder="Provide clear explanation for declining the return to the buyer..." class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-medium outline-none focus:border-red-500 focus:bg-white resize-none"></textarea>
                    </div>
                </template>

                <div class="p-3 bg-red-50 border border-red-200 rounded-2xl text-[10px] text-red-800 leading-relaxed">
                    <strong>Notice:</strong> Declining will notify the customer with your stated explanation. The return request will be marked as Declined.
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="showRejectReturnModal = false"
                    :disabled="rejectReturnLoading"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Back
                </button>
                <button type="button" 
                    @click="executeRejectReturn()"
                    :disabled="rejectReturnLoading"
                    class="flex-1 py-3 rounded-full bg-red-600 hover:bg-red-700 text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <template x-if="rejectReturnLoading">
                        <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <span x-text="rejectReturnLoading ? 'Declining...' : '✕ Confirm Decline'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Proof Image Zoom Lightbox Modal --}}
    <div x-show="showProofLightboxModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 bg-black/90 backdrop-blur-md z-9999 flex items-center justify-center p-4"
         @click.self="closeProofLightbox()"
         @keydown.escape.window="closeProofLightbox()"
         x-cloak
         style="display: none;">
        <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center">
            <button type="button" 
                    @click="closeProofLightbox()" 
                    class="absolute -top-12 right-0 w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="rounded-2xl overflow-hidden bg-black shadow-2xl border border-white/10 max-h-[82vh] flex items-center justify-center">
                <img :src="activeProofImage" class="max-h-[82vh] w-auto object-contain" alt="Enlarged Proof Photo">
            </div>
            <div class="mt-3 flex items-center gap-3">
                <a :href="activeProofImage" target="_blank" class="px-4 py-2 rounded-full bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold flex items-center gap-1.5 transition-all">
                    <span>↗ Open Original</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Camera Live Capture Modal --}}
    <div x-show="showCameraModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/80 backdrop-blur-sm z-99999 flex items-center justify-center p-4"
         @click.self="closeCameraModal()"
         @keydown.escape.window="closeCameraModal()"
         x-cloak
         style="display: none;">
        <div class="w-full max-w-lg bg-white rounded-3xl shadow-2xl p-5 sm:p-6 space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📷</span>
                    <div>
                        <h3 class="text-sm font-black text-black uppercase tracking-tight">Capture Packing Proof</h3>
                        <p class="text-[10px] text-gray-500 font-medium">Position the packed garment clearly in view</p>
                    </div>
                </div>
                <button type="button" @click="closeCameraModal()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center font-bold text-sm transition-all cursor-pointer">
                    ✕
                </button>
            </div>

            <div class="relative w-full aspect-4/3 bg-black rounded-2xl overflow-hidden flex items-center justify-center border border-gray-200">
                <video x-ref="cameraVideo" autoplay playsinline class="w-full h-full object-cover"></video>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="button" 
                    @click="closeCameraModal()"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                    @click="takePhoto()"
                    class="flex-1 py-3 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <span>📸 Capture Photo</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Contextual Spotlight Tours for Order Statuses --}}
    @php
        $statusTourSteps = [
            'all' => [
                [
                    'selector' => '#tour-orders-header',
                    'title' => '📋 All Orders Master Ledger',
                    'text' => 'Welcome to your Client Orders ledger! Here you oversee all customer orders across every fulfillment stage from initial placement to completion.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-search',
                    'title' => '🔍 Instant Order Search',
                    'text' => 'Rapidly find orders by typing the Order ID (e.g. #LB-XXXX), buyer name, or shipping courier tracking number.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '🏷️ Order Lifecycle Filter Tabs',
                    'text' => 'Click any status tab to filter orders by stage: Pending, To Ship, Shipped, In Transit, Delivered, Completed, or Customer Requests.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '📦 Order Dossiers & Tracking',
                    'text' => 'Each capsule displays the order code, status badge, customer avatar, items summary, and total amount. Click any order row to open its full management dossier!',
                    'position' => 'top'
                ]
            ],
            'pending' => [
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '⏳ Pending Orders Stage',
                    'text' => 'These are newly placed orders awaiting your verification, custom measurements review, or initial payment confirmation before production starts.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '✂️ Review & Accept Commission',
                    'text' => 'Click on any pending order to review bespoke measurements, verify buyer notes, and advance the order into the "To Ship" (In Production) stage.',
                    'position' => 'top'
                ],
                [
                    'selector' => '#tour-orders-header',
                    'title' => '⚠️ Cancellation Grace Window',
                    'text' => 'Buyers may request cancellation while an order is still in Pending. Once you advance to tailoring, custom made-to-order policies take effect.',
                    'position' => 'bottom'
                ]
            ],
            'to-ship' => [
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '📦 To Ship & Production Stage',
                    'text' => 'Orders in this stage have verified payment and are actively undergoing bespoke tailoring, embroidery, quality inspection, and packaging.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '📸 Live Packaging Photo Proof',
                    'text' => 'Inside the order dossier, capture or upload photo proof of the packaged garment and waybill before courier dispatch or store pickup notification.',
                    'position' => 'top'
                ],
                [
                    'selector' => '#tour-orders-search',
                    'title' => '🚚 Logistics Courier / Special Delivery / Store Pickup',
                    'text' => 'Select an accredited courier (J&T, LBC), dispatch via Special Delivery (local rider), or mark ready for in-shop Store Pickup collection.',
                    'position' => 'bottom'
                ]
            ],
            'shipped' => [
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '🚚 Shipped & Ready Orders Stage',
                    'text' => 'Parcels dispatched via courier/rider or awaiting buyer claim at your Lumban artisan workshop.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '📲 Live Buyer Notification & GPS Map',
                    'text' => 'The customer receives real-time SMS and dashboard notifications with tracking details or workshop map directions upon dispatch.',
                    'position' => 'top'
                ],
                [
                    'selector' => '#tour-orders-header',
                    'title' => '🛣️ Transitioning to In Transit / Claimed',
                    'text' => 'Track packages as they move through regional sorting hubs to the buyer\'s doorstep or are picked up in-shop.',
                    'position' => 'bottom'
                ]
            ],
            'in-transit' => [
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '🛣️ In Transit Logistics Tracking',
                    'text' => 'Packages actively moving through courier delivery routes and regional hubs traveling to the buyer\'s destination address.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '🔎 Courier Tracking & Delivery ETA',
                    'text' => 'Click on any order to view the direct courier tracking link and monitor live transit milestones and estimated delivery date.',
                    'position' => 'top'
                ],
                [
                    'selector' => '#tour-orders-header',
                    'title' => '📬 Doorstep Delivery Confirmation',
                    'text' => 'When the logistics courier completes delivery at the recipient\'s doorstep, transition the order state to "Delivered".',
                    'position' => 'bottom'
                ]
            ],
            'delivered' => [
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '📬 Delivered Orders Stage',
                    'text' => 'Handcrafted garments that have successfully reached the buyer\'s delivery address.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '🕒 Customer Inspection Grace Window',
                    'text' => 'Buyers have a standard period to inspect the garment, verify custom sizing measurements, and confirm satisfaction.',
                    'position' => 'top'
                ],
                [
                    'selector' => '#tour-orders-header',
                    'title' => '✅ Order Completion & Acceptance',
                    'text' => 'Once the customer clicks "Order Received" or the inspection timer completes with no disputes, the order moves to "Completed".',
                    'position' => 'bottom'
                ]
            ],
            'completed' => [
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '✅ Completed Orders Ledger',
                    'text' => 'Successfully fulfilled orders where the buyer confirmed receipt and satisfaction with their authentic Lumban creation.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '💵 Net Payout & Commission Calculation',
                    'text' => 'Earnings from completed orders are finalized and credited toward your net seller balance, with the 10% platform commission itemized.',
                    'position' => 'top'
                ],
                [
                    'selector' => '#tour-orders-header',
                    'title' => '⭐ Verified Buyer Reviews & Ratings',
                    'text' => 'Completed orders allow customers to submit verified 5-star reviews and photos that showcase on your public artisan profile.',
                    'position' => 'bottom'
                ]
            ],
            'cancelled' => [
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '❌ Cancelled Orders History',
                    'text' => 'Orders that were terminated prior to completion due to mutual cancellation, buyer request, or inventory adjustments.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '🔄 Automatic Inventory Restock',
                    'text' => 'When an order is cancelled, reserved pieces are automatically restored into your live catalog inventory.',
                    'position' => 'top'
                ],
                [
                    'selector' => '#tour-orders-search',
                    'title' => '📝 Audit History & Reason Logs',
                    'text' => 'Click any cancelled order to review the complete audit trail, timestamps, and recorded cancellation explanation.',
                    'position' => 'bottom'
                ]
            ],
            'cancellation-pending' => [
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '⚠️ Cancellation Requests Hub',
                    'text' => 'Buyers have submitted a cancellation request for an order that is currently in preparation.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '⚖️ Reviewing & Approving Requests',
                    'text' => 'Open the order to review the buyer\'s reason. If fabric cutting and custom embroidery have not begun, you may Approve the cancellation.',
                    'position' => 'top'
                ],
                [
                    'selector' => '#tour-orders-header',
                    'title' => '🛡️ Custom Made-to-Order Policy',
                    'text' => 'If custom tailoring is already actively in progress, you may Decline with a polite explanation referencing your Shop Cancellation Policy.',
                    'position' => 'bottom'
                ]
            ],
            'return-requests' => [
                [
                    'selector' => '#tour-orders-tabs',
                    'title' => '↩️ Return & Sizing Requests',
                    'text' => 'Customer inquiries requesting a return, sizing adjustment, or refund after receiving their order.',
                    'position' => 'bottom'
                ],
                [
                    'selector' => '#tour-orders-list',
                    'title' => '📸 Unboxing Proof & Evidence Inspection',
                    'text' => 'Review the customer\'s uploaded photos, video proof, and reason description directly in the return review panel.',
                    'position' => 'top'
                ],
                [
                    'selector' => '#tour-orders-header',
                    'title' => '🤝 Fair Dispute Resolution',
                    'text' => 'Coordinate sizing alterations or return shipping according to your Shop Refund & Return Policies to protect artisan craftsmanship.',
                    'position' => 'bottom'
                ]
            ],
        ];
    @endphp

    @foreach($statusTourSteps as $slug => $steps)
        <x-spotlight-tour tourId="seller-orders-{{ $slug }}" :steps="$steps" :autoStart="false" />
    @endforeach

    {{-- Fallback default alias --}}
    <x-spotlight-tour tourId="seller-orders-guide" :steps="$statusTourSteps['all']" :autoStart="false" />
</div>
@endsection
