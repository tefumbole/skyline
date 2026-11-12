@if(session('payment_receipt_sale') || session('payment_receipt_url'))
    @php
        $receiptUrl = session('payment_receipt_url');
        if (! $receiptUrl && session('payment_receipt_sale')) {
            $receiptUrl = route('sale.invoice', (int) session('payment_receipt_sale')).'?autoprint=1';
        }
    @endphp
    <div id="payment-receipt-choice" tabindex="-1" role="dialog" aria-labelledby="paymentReceiptTitle" class="modal fade" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:420px;">
            <div class="modal-content" style="border:0;border-radius:20px;overflow:hidden;">
                <div style="padding:18px 18px 8px;background:#0c1830;color:#fff;">
                    <div style="color:#e1b368;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">Payment saved</div>
                    <h5 id="paymentReceiptTitle" style="margin:6px 0 0;font-size:22px;font-weight:800;">Print the full receipt?</h5>
                </div>
                <div style="padding:16px 18px 18px;background:#f3f6fb;">
                    <p style="margin:0 0 14px;color:#243044;">The payment is already recorded. Print the full receipt now, or close this and keep working.</p>
                    <a class="btn btn-block" style="min-height:48px;border-radius:14px;background:#0c1830;color:#e1b368;font-weight:800;line-height:32px;" target="_blank" rel="noopener" href="{{ $receiptUrl }}">Print full receipt</a>
                    <button type="button" class="btn btn-block" data-dismiss="modal" style="min-height:48px;margin-top:8px;border-radius:14px;background:#fff;border:1px solid #d7deea;color:#0c1830;font-weight:800;">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function () {
            var box = $('#payment-receipt-choice');
            if (box.length && box.modal) box.modal('show');
        });
    </script>
@endif
