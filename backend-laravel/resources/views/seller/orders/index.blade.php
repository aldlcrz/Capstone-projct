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
        const variation = item.display_variation && item.display_variation !== 'Original' ? item.display_variation : '—';
        return '<tr>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;">' + (item.product?.name || 'Deleted Product') + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;">' + (item.size || '—') + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;">' + variation + '</td>'
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
        + '<th>Product</th><th>Size</th><th>Variation</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th>'
        + '</tr></thead><tbody>' + itemsHtml + '</tbody></table>'
        + '<div class="total">Total: ₱' + Number(order.totalAmount).toLocaleString() + '</div>'
        + '<h2>Payment Information</h2>'
        + '<div class="box">Method: ' + (order.paymentMethod || 'N/A') + '<br>'
        + 'Reference No: ' + (order.paymentReference || 'N/A') + '</div>'
        + '</body></html>';

    const win = window.open('', '_blank');
    win.document.write(html);
    win.document.close();
    win.focus();
    win.print();
}

function sellerOrdersManager(initialOrders) {
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
        showDeliveryConfirmModal: false,
        deliveryConfirmOrder: null,
        deliveryConfirmLoading: false,
        deliveryConfirmSuccess: false,
        deliveryConfirmError: '',
        showVerifyModal: false,
        verifyOrderTarget: null,
        showRejectModal: false,
        rejectOrderTarget: null,
        showCancelOrderModal: false,
        cancelOrderTarget: null,
        sellerCancelReason: 'Out of stock / fabric unavailable',
        sellerCustomCancelReason: '',
        sellerCancelLoading: false,
        sellerCancelError: '',
        receiptModal: false,
        receiptUrl: '',
        rejectReason: 'Reference number does not match',
        rejectCustomReason: '',
        rejectLoading: false,
        rejectError: '',
        statusUpdating: false,
        toastMessage: '',
        toastTimeout: null,

        showToast(msg) {
            this.toastMessage = msg;
            clearTimeout(this.toastTimeout);
            this.toastTimeout = setTimeout(() => { this.toastMessage = ''; }, 3500);
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
            this.showVerifyModal = true;
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

            try {
                const payload = {
                    status: 'Delivered',
                    courierName: this.courierName || target.courierName || 'J&T Express',
                    trackingNumber: (this.trackingNumber || target.trackingNumber || '').trim() || null,
                    trackingLink: this.trackingLink || target.trackingLink || null
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

        formatAddress(order) {
            return formatOrderAddress(order);
        },

        buyerPhone(order) {
            return buyerOrderPhone(order);
        },

        getCourierDefaultLink(courier) {
            const map = {
                'J&T Express': 'https://www.jtexpress.ph/track',
                'LBC Express': 'https://www.lbcexpress.com/track',
                'Flash Express': 'https://www.flashexpress.ph/tracking/',
                'Ninja Van': 'https://www.ninjavan.co/en-ph/tracking',
                '2GO Express': 'https://supplychain.2go.com.ph/customersupport/etrack.asp',
                'JRS Express': 'https://www.jrs-express.com/tracking/',
                'Lalamove': 'https://www.lalamove.com/en-ph/',
                'GrabExpress': 'https://www.grab.com/ph/express/'
            };
            return map[courier] || '';
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
            this.courierName = order.courierName || 'J&T Express';
            this.trackingNumber = order.trackingNumber || '';
            this.trackingLink = order.trackingLink || (order.courierName ? this.getCourierDefaultLink(order.courierName) : 'https://www.jtexpress.ph/track');
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
            this.courierName = order.courierName || 'J&T Express';
            this.trackingNumber = order.trackingNumber || '';
            this.trackingLink = order.trackingLink || (order.courierName ? this.getCourierDefaultLink(order.courierName) : 'https://www.jtexpress.ph/track');
            this.shippingError = '';
            this.statusModal = true;
        },

        printOrderDetails() {
            printSellerOrder(this.detailsOrder);
            this.detailsModal = false;
        },

        onPackingFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.packingPhotoFile = file;
            this.packingUploadError = '';
            const reader = new FileReader();
            reader.onload = (e) => { this.packingPhotoPreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        async uploadPackingProof() {
            if (!this.packingPhotoFile || !this.detailsOrder) return;
            this.packingUploading = true;
            this.packingUploadError = '';
            try {
                const formData = new FormData();
                formData.append('packingPhoto', this.packingPhotoFile);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                const res = await fetch(`/seller/api/orders/${this.detailsOrder.id}/packing-proof`, {
                    method: 'POST',
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Upload failed.');
                this.packingUploadSuccess = true;
                this.packingPhotoPreview = data.packingProofUrl;
                this.detailsOrder.packingProof = data.packingProof;
                const idx = this.orders.findIndex(o => o.id === this.detailsOrder.id);
                if (idx !== -1) this.orders[idx].packingProof = data.packingProof;
            } catch(e) {
                this.packingUploadError = e.message || 'Upload failed. Please try again.';
            } finally {
                this.packingUploading = false;
            }
        },

        async openCameraModal() {
            this.packingUploadError = '';
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Camera API is not supported on this browser or connection. Please upload a photo using Gallery instead.');
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
                alert('Could not access camera (' + (err.message || 'permission denied') + '). Please upload a photo using Gallery instead.');
                this.closeCameraModal();
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
                this.packingUploadSuccess = false;
                this.closeCameraModal();
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
            if (s === 'processing') return 'to ship';
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
            const s = this.normalizeStatus(order.status);
            return s === 'delivered' || s === 'completed' || s === 'cancelled';
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
                    o.id.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                    (o.customer?.name || '').toLowerCase().includes(this.searchTerm.toLowerCase());
                let s = this.normalizeStatus(o.status);
                if (s === 'processing' || s === 'ready to ship' || s === 'ready_to_ship') s = 'to ship';
                const f = this.normalizeStatus(this.statusFilter);
                const matchStatus = f === 'all' || s === f;
                return matchSearch && matchStatus;
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
                'cancellation pending': 'bg-[#FEF2F2] text-[#DC2626] border-[#FECACA]',
                'cancellation requested': 'bg-[#FEF2F2] text-[#DC2626] border-[#FECACA]',
            };
            return m[norm] || 'bg-[#FDF8EE] text-[#766C60] border-[#E8DECB]';
        },

        countForStatus(statusKey) {
            if (statusKey === 'all') return this.orders.length;
            const normKey = this.normalizeStatus(statusKey);
            return this.orders.filter(o => {
                let s = this.normalizeStatus(o.status);
                if (s === 'processing' || s === 'ready to ship' || s === 'ready_to_ship') s = 'to ship';
                return s === normKey;
            }).length;
        },

        async updateStatus(targetOrder, statusToSave) {
            const target = targetOrder || this.detailsOrder || this.activeOrder;
            const statusVal = statusToSave || this.newStatus;
            if (!target || !statusVal) return;

            this.shippingError = '';
            this.statusUpdating = true;

            try {
                const currentTracking = (this.trackingNumber || target.trackingNumber || '').trim();

                if (this.normalizeStatus(statusVal) === 'in transit' && !currentTracking) {
                    this.shippingError = 'Please enter the official courier tracking number in the field above before moving to In Transit.';
                    this.statusUpdating = false;
                    return;
                }

                let currentCourier = target.courierName || null;
                let currentLink = target.trackingLink || null;

                if (currentTracking) {
                    currentCourier = this.courierName || target.courierName || 'J&T Express';
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
                } else {
                    this.shippingError = data.message || 'Failed to update status. Please check fields and try again.';
                }
            } catch(e) {
                this.shippingError = 'Network error: ' + (e.message || 'Please try again.');
            } finally {
                this.statusUpdating = false;
            }
        }
    };
}
</script>

<div class="space-y-4 sm:space-y-6 max-w-5xl pb-28 lg:pb-12 px-2 sm:px-6" x-data="sellerOrdersManager({{ $orders->toJson() }})">

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
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 sm:gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">✦ Shop Orders</span>
                <span class="text-xs" style="color: #E8DECB;">•</span>
                <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">Fulfillment Ledger</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight" style="color: #1E1915;">
                Client <span class="italic font-normal" style="color: #766C60;">Orders</span>
            </h1>
            <p class="text-xs font-medium mt-1" style="color: #766C60;">Track bespoke commissions, customer delivery details, and fulfillment state.</p>
        </div>
        
        {{-- Search Input --}}
        <div class="relative w-full sm:w-72">
            <input type="text" x-model="searchTerm" placeholder="Search order ID or customer..."
                class="w-full h-10 sm:h-11 pl-9 pr-4 rounded-xl text-xs font-semibold shadow-xs outline-none transition-all"
                style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
                onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';"
                onblur="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
            <svg class="w-4 h-4 absolute left-3 top-3 sm:top-3.5" style="color: #766C60;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    {{-- Status Filter Tabs (Pill System) --}}
    <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar pb-2 -mx-2 px-2 sm:mx-0 sm:px-0 scroll-smooth">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'to ship' => 'To Ship', 'shipped' => 'Shipped', 'in transit' => 'In Transit', 'delivered' => 'Delivered', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
            <button @click="statusFilter = '{{ $val }}'"
                :style="statusFilter === '{{ $val }}' ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;' : 'background:#FDF8EE; color:#1E1915; border:1px solid #E8DECB;'"
                class="px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl text-[9px] sm:text-[10px] font-extrabold uppercase tracking-wider whitespace-nowrap transition-all flex items-center gap-1.5 shrink-0 active:scale-95 cursor-pointer shadow-2xs">
                <span x-show="statusFilter === '{{ $val }}'" style="color:#C49520;">✓</span>
                <span>{{ $label }}</span>
                <span class="px-1.5 py-0.5 text-[8px] sm:text-[9px] rounded-full font-bold" 
                      :style="statusFilter === '{{ $val }}' ? 'background:rgba(196,149,32,0.25); color:#FFFCF7;' : 'background:#E8DECB; color:#766C60;'"
                      x-text="countForStatus('{{ $val }}')">
                    {{ $counts[$val] ?? 0 }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Order Capsule List --}}
    <div class="space-y-2.5 sm:space-y-3">
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
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center font-bold text-xs sm:text-sm shadow-xs shrink-0 overflow-hidden group-hover:scale-105 transition-transform" style="background: #1E1915; color: #C49520; border: 1px solid rgba(196,149,32,0.4);">
                        <span x-text="(order.customer?.name || 'O')[0].toUpperCase()"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-serif text-xs sm:text-sm font-bold truncate tracking-tight transition-colors"
                                style="color: #1E1915;"
                                x-text="'#LB-' + order.id.slice(-8).toUpperCase()"></h3>
                            <span class="px-2.5 py-0.5 rounded-full border text-[8px] sm:text-[9px] font-black uppercase tracking-wider shrink-0"
                                  :class="statusColor(order.status)"
                                  x-text="normalizeStatus(order.status) === 'to ship' ? 'To Ship' : order.status"></span>
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
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase" :class="statusColor(detailsOrder?.status)" x-text="normalizeStatus(detailsOrder?.status) === 'to ship' ? 'To Ship' : detailsOrder?.status"></span>
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

                        {{-- PACKING PROOF UPLOAD CARD — shown when To Ship --}}
                        <div x-show="normalizeStatus(detailsOrder.status) === 'to ship'" x-transition class="bg-emerald-50/60 p-4 rounded-2xl border border-emerald-200/70 space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">📦</span>
                                <div>
                                    <div class="text-[9px] font-black uppercase tracking-widest text-emerald-900">
                                        Packing Proof Required <span class="text-red-500 font-bold">*</span>
                                    </div>
                                    <div class="text-[10px] text-emerald-700 mt-0.5">Please take or upload a photo of the packaged items. The customer will see this photo in their order tracking to verify their order.</div>
                                </div>
                            </div>

                            {{-- Success state: already uploaded --}}
                            <template x-if="packingUploadSuccess && packingPhotoPreview">
                                <div class="space-y-2">
                                    <div class="w-full rounded-2xl overflow-hidden border-2 border-emerald-300 bg-white max-h-52 flex items-center justify-center">
                                        <img :src="packingPhotoPreview" class="max-h-52 w-full object-contain" alt="Packing Proof">
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] font-bold text-emerald-700">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Packing proof uploaded. You can replace it by uploading again.
                                    </div>
                                    <label class="flex items-center justify-center gap-2 w-full py-2 rounded-xl border border-emerald-300 bg-white text-[10px] font-black uppercase tracking-widest text-emerald-700 hover:bg-emerald-50 cursor-pointer transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L8 8m4-4l4 4"/></svg>
                                        Replace Photo
                                        <input type="file" class="hidden" accept="image/*" capture="environment" @change="onPackingFileChange($event); packingUploadSuccess = false;">
                                    </label>
                                </div>
                            </template>

                            {{-- Upload state: no photo yet or replacing --}}
                            <template x-if="!packingUploadSuccess">
                                <div class="space-y-3">
                                    {{-- Preview if file chosen --}}
                                    <template x-if="packingPhotoPreview">
                                        <div class="w-full rounded-2xl overflow-hidden border-2 border-dashed border-emerald-300 bg-white max-h-52 flex items-center justify-center">
                                            <img :src="packingPhotoPreview" class="max-h-52 w-full object-contain" alt="Preview">
                                        </div>
                                    </template>

                                    {{-- Error --}}
                                    <template x-if="packingUploadError">
                                        <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-[10px] font-bold text-red-600" x-text="packingUploadError"></div>
                                    </template>

                                    {{-- File picker / camera buttons --}}
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" @click="openCameraModal()" class="flex flex-col items-center justify-center gap-1.5 py-4 rounded-2xl border-2 border-dashed border-emerald-300 bg-white hover:bg-emerald-50 cursor-pointer transition-all group">
                                            <svg class="w-6 h-6 text-emerald-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="text-[9px] font-black uppercase tracking-wider text-emerald-700">Open Camera</span>
                                        </button>
                                        <label class="flex flex-col items-center justify-center gap-1.5 py-4 rounded-2xl border-2 border-dashed border-emerald-300 bg-white hover:bg-emerald-50 cursor-pointer transition-all group">
                                            <svg class="w-6 h-6 text-emerald-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L8 8m4-4l4 4"/></svg>
                                            <span class="text-[9px] font-black uppercase tracking-wider text-emerald-700">Gallery / Files</span>
                                            <input type="file" class="hidden" accept="image/*" @change="onPackingFileChange($event)">
                                        </label>
                                    </div>

                                    {{-- Upload button --}}
                                    <button type="button"
                                        @click="uploadPackingProof()"
                                        :disabled="!packingPhotoFile || packingUploading"
                                        :class="(!packingPhotoFile || packingUploading) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-emerald-700'"
                                        class="w-full py-2.5 bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all flex items-center justify-center gap-2">
                                        <template x-if="packingUploading">
                                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                        </template>
                                        <template x-if="!packingUploading">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L8 8m4-4l4 4"/></svg>
                                        </template>
                                        <span x-text="packingUploading ? 'Uploading...' : 'Upload Packing Proof'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- COURIER & SHIPPING CARD — hidden when Pending or To Ship --}}
                        <div x-show="normalizeStatus(detailsOrder.status) !== 'pending' && normalizeStatus(detailsOrder.status) !== 'to ship'" x-transition class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100/70 space-y-3">
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
                                    Shipping information is locked and read-only because the order has been delivered, completed, or cancelled.
                                </p>
                            </template>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                     <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Courier / Shipping Company</label>
                                     <select x-model="courierName" @change="onCourierChange()" :disabled="isShippingLocked(detailsOrder)"
                                         class="w-full h-9 px-3 bg-white border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:border-[#C0420A] disabled:bg-gray-100 disabled:text-gray-500 cursor-pointer">
                                         <option value="J&T Express">J&T Express (Default)</option>
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
                                     <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Tracking Number <span class="text-red-500">*</span></label>
                                     <input type="text" x-model="trackingNumber" placeholder="e.g. JT-123456789PH" :disabled="isShippingLocked(detailsOrder)"
                                         class="w-full h-9 px-3 bg-white border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:border-[#C0420A] disabled:bg-gray-100 disabled:text-gray-500">
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
                            <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Buyer & Shipping Details</div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Customer Name</div>
                                    <div class="font-black text-black mt-0.5" x-text="detailsOrder.customer?.name || 'Unknown Buyer'"></div>
                                </div>
                                <div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Phone Contact</div>
                                    <div class="font-bold text-black mt-0.5" x-text="buyerPhone(detailsOrder)"></div>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-gray-200/60 text-xs">
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Delivery Address</div>
                                <div class="text-gray-700 font-medium mt-0.5 leading-relaxed" x-text="formatAddress(detailsOrder)"></div>
                            </div>
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
                                            <img :src="productImage(item.product)" class="w-full h-full object-cover object-top" x-on:error="$event.target.src='/uploads/products/default.jpg'">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-xs font-bold text-black truncate" x-text="item.product?.name || 'Product Item'"></h4>
                                            <div class="flex flex-wrap gap-2 text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                                                <span x-show="item.size" x-text="'Size: ' + item.size"></span>
                                                <span x-show="item.display_variation && item.display_variation !== 'Original'" x-text="'Variation: ' + item.display_variation"></span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="text-xs font-black text-black" x-text="item.quantity + ' × ₱' + Number(item.price).toLocaleString()"></div>
                                            <div class="text-[9px] font-bold text-[#C0420A]" x-text="'₱' + (Number(item.price) * item.quantity).toLocaleString()"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-2xl border border-gray-100">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Grand Total Amount</span>
                                <span class="text-base font-black text-[#C0420A]" x-text="'₱' + Number(detailsOrder.totalAmount).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>

                        {{-- Payment Information --}}
                        <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 space-y-2 text-xs">
                            <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Payment Details</div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Method</span>
                                <span class="font-black text-black uppercase" x-text="detailsOrder.paymentMethod || 'COD'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Reference No.</span>
                                <span class="font-mono text-xs font-bold text-gray-700" x-text="detailsOrder.paymentReference || 'N/A'"></span>
                            </div>
                            <template x-if="detailsOrder.paymentProof">
                                <div class="pt-2 border-t border-gray-200/60 flex items-center justify-between">
                                    <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Receipt File</span>
                                    <button type="button" @click="receiptUrl = '/storage/' + detailsOrder.paymentProof; receiptModal = true;" class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-[#C0420A] hover:underline">
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
                <template x-if="shippingError">
                    <div class="p-3 bg-red-50 border border-red-200 rounded-2xl text-[11px] font-bold text-red-600 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="shippingError"></span>
                    </div>
                </template>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3">
                    <button @click="detailsModal = false"
                        class="px-5 sm:px-6 py-2.5 sm:py-3 rounded-full border border-gray-300 bg-white text-[10px] font-black uppercase tracking-widest text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition-all cursor-pointer shrink-0 text-center order-last sm:order-first">
                        Close
                    </button>

                    {{-- Pending Order Actions: Reject Payment (counts as Cancel) OR Verify & Accept --}}
                    <template x-if="detailsOrder && normalizeStatus(detailsOrder.status) === 'pending'">
                        <div class="flex-1 flex flex-wrap sm:flex-nowrap items-center justify-end gap-2">
                            <button type="button" 
                                @click="openRejectPaymentModal(detailsOrder)"
                                class="px-4 py-2.5 sm:py-3 border border-red-200 hover:bg-red-50 text-red-600 rounded-full text-[10px] font-black uppercase tracking-wider whitespace-nowrap transition-all cursor-pointer shrink-0 flex items-center justify-center gap-1.5">
                                <span>✕</span> Reject & Cancel
                            </button>
                            <button type="button" 
                                @click="openVerifyPaymentModal(detailsOrder)"
                                :disabled="statusUpdating"
                                style="background-color: #059669; color: #ffffff;"
                                class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-1.5 shadow-sm cursor-pointer shrink-0">
                                <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Verify & Accept</span>
                                <span class="text-xs">➔</span>
                            </button>
                        </div>
                    </template>

                    {{-- Button for To Ship status: Upload Packing Proof & Confirm Shipment --}}
                    <template x-if="detailsOrder && normalizeStatus(detailsOrder.status) === 'to ship'">
                        <div class="flex-1 flex justify-end">
                            <button type="button"
                                @click="if (!packingPhotoFile && !packingUploadSuccess && !detailsOrder.packingProof) { packingUploadError = 'Please upload or capture a packing proof photo before confirming shipment.'; } else if (packingPhotoFile && !packingUploadSuccess && !detailsOrder.packingProof) { uploadPackingProof().then(() => updateStatus(detailsOrder, 'Shipped')); } else { updateStatus(detailsOrder, 'Shipped'); }"
                                :disabled="packingUploading || statusUpdating"
                                :style="(packingUploadSuccess || detailsOrder.packingProof) ? 'background-color: #C0420A; color: #ffffff;' : 'background-color: #000000; color: #ffffff;'"
                                class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-black hover:bg-[#C0420A] disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer">
                                <template x-if="packingUploading || statusUpdating">
                                    <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                </template>
                                <template x-if="!packingUploading && !statusUpdating">
                                    <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </template>
                                <span x-text="packingUploading ? 'Uploading Photo...' : (statusUpdating ? 'Updating Status...' : ((packingUploadSuccess || detailsOrder.packingProof) ? 'Confirm Shipment (Mark Shipped) ➔' : (packingPhotoFile ? 'Upload & Confirm Shipment ➔' : 'Upload Proof & Confirm Shipment ➔')))"></span>
                            </button>
                        </div>
                    </template>

                    {{-- Button for Shipped status: Mark In Transit (requires seller to input tracking number) --}}
                    <template x-if="detailsOrder && normalizeStatus(detailsOrder.status) === 'shipped'">
                        <div class="flex-1 flex justify-end">
                            <button type="button"
                                @click="if (!trackingNumber || !trackingNumber.trim()) { shippingError = 'Please enter the official courier tracking number in the field above before moving to In Transit.'; } else { updateStatus(detailsOrder, 'In Transit'); }"
                                :disabled="statusUpdating"
                                style="background-color: #000000; color: #ffffff;"
                                class="flex-1 sm:flex-none px-6 py-2.5 sm:py-3 bg-black hover:bg-[#C0420A] disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider whitespace-nowrap rounded-full transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer">
                                <template x-if="statusUpdating">
                                    <svg class="w-3.5 h-3.5 animate-spin text-white shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                </template>
                                <template x-if="!statusUpdating">
                                    <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </template>
                                <span x-text="statusUpdating ? 'Updating...' : 'Mark In Transit ➔'"></span>
                            </button>
                        </div>
                    </template>

                    {{-- Button for In Transit status: Mark as Delivered --}}
                    <template x-if="detailsOrder && normalizeStatus(detailsOrder.status) === 'in transit'">
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
                                <span x-text="statusUpdating ? 'Updating...' : 'Mark as Delivered ➔'"></span>
                            </button>
                        </div>
                    </template>

                    {{-- Status notice for Delivered status --}}
                    <template x-if="detailsOrder && normalizeStatus(detailsOrder.status) === 'delivered'">
                        <div class="flex-1 py-2.5 sm:py-3 px-4 bg-teal-50 border border-teal-200 text-teal-800 text-[10px] font-black uppercase tracking-wider rounded-full flex items-center justify-center gap-2 text-center">
                            <span>📦 Delivered — Awaiting Customer Receipt Confirmation</span>
                        </div>
                    </template>

                    {{-- Status notice for Completed status --}}
                    <template x-if="detailsOrder && normalizeStatus(detailsOrder.status) === 'completed'">
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
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-9999 flex items-center justify-center p-4"
         @click.self="showDeliveryConfirmModal = false"
         x-cloak>
        
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 text-center space-y-4 border border-gray-100 relative overflow-hidden">
            <div class="h-1.5 w-full bg-linear-to-r from-emerald-500 to-teal-600 absolute top-0 left-0"></div>

            <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto text-2xl shadow-inner mt-2 transition-all"
                 :class="deliveryConfirmSuccess ? 'bg-emerald-100 text-emerald-700 ring-4 ring-emerald-200' : 'bg-emerald-50 text-emerald-600'">
                <span x-text="deliveryConfirmSuccess ? '✓' : '🚚'"></span>
            </div>

            <div>
                <h3 class="text-base font-black text-black uppercase tracking-tight" x-text="deliveryConfirmSuccess ? 'Order Marked as Delivered!' : 'Confirm Order Delivery'"></h3>
                <p class="text-xs text-gray-500 font-medium mt-1" x-text="deliveryConfirmSuccess ? 'Status updated. Closing in 2 seconds...' : 'Are you sure this order has been delivered?'"></p>
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
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="deliveryConfirmSuccess">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </template>
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
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg shrink-0">
                    💳
                </div>
                <div>
                    <h3 class="text-sm font-black text-black uppercase tracking-tight">Verify Payment & Accept Order</h3>
                    <p class="text-[10px] text-gray-500 font-medium">Verify that the payment was credited to your account.</p>
                </div>
            </div>

            <template x-if="verifyOrderTarget">
                <div class="space-y-3 text-xs">
                    <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-bold text-[10px] uppercase">Payment Method</span>
                            <span class="font-black text-black uppercase" x-text="verifyOrderTarget.paymentMethod || 'GCash'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-bold text-[10px] uppercase">Reference Number</span>
                            <span class="font-mono font-bold text-indigo-700 text-xs px-2 py-0.5 bg-indigo-50 rounded-md" x-text="verifyOrderTarget.paymentReference || 'N/A'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-bold text-[10px] uppercase">Expected Amount</span>
                            <span class="font-black text-emerald-700 text-sm" x-text="'₱' + Number(verifyOrderTarget.totalAmount).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                        </div>
                    </div>

                    {{-- Mini receipt preview thumbnail --}}
                    <template x-if="verifyOrderTarget.paymentProof">
                        <div class="space-y-1">
                            <span class="text-gray-400 font-bold text-[10px] uppercase">Customer Receipt Proof</span>
                            <div class="p-2 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-between gap-3">
                                <div class="w-12 h-14 bg-black/5 rounded-xl overflow-hidden shrink-0 border border-gray-200">
                                    <img :src="'/storage/' + verifyOrderTarget.paymentProof" class="w-full h-full object-cover" alt="Proof">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold text-gray-700">Receipt Screenshot</p>
                                    <p class="text-[10px] text-gray-400">Click to view in full resolution</p>
                                </div>
                                <button type="button" @click="receiptUrl = '/storage/' + verifyOrderTarget.paymentProof; receiptModal = true;" class="px-3 py-1.5 bg-black text-white text-[9px] font-black uppercase tracking-wider rounded-xl hover:bg-[#C0420A] transition-all">
                                    Inspect ↗
                                </button>
                            </div>
                        </div>
                    </template>

                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-2xl text-[10px] text-amber-900 leading-relaxed">
                        <span class="font-black uppercase tracking-wider block mb-0.5">⚠️ Artisan Verification Check</span>
                        Please confirm in your <strong x-text="verifyOrderTarget.paymentMethod || 'GCash'"></strong> mobile app that you received <strong>₱<span x-text="Number(verifyOrderTarget.totalAmount).toLocaleString(undefined, {minimumFractionDigits:2})"></span></strong> with reference <strong x-text="verifyOrderTarget.paymentReference"></strong> before proceeding.
                    </div>
                </div>
            </template>

            <div class="flex gap-3 pt-2">
                <button type="button" 
                    @click="showVerifyModal = false"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                    @click="showVerifyModal = false; updateStatus(verifyOrderTarget, 'To Ship');"
                    style="background-color: #059669; color: #ffffff;"
                    class="flex-1 py-3 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-widest shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <span>✓ Confirm & Accept Order</span>
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
</div>
@endsection
