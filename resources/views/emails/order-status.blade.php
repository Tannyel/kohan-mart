<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kohan Mart Order Update</title>
</head>
<body style="margin:0; padding:0; background-color:#FAF7F2; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#2E2A25;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" style="max-width:560px; background:#FFFFFF; border:1px solid #E2D8C3; border-radius:20px; overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background:#3F5A45; padding:24px 28px; text-align:center; color:#FAF7F2;">
                            <div style="font-size:20px; font-weight:700; letter-spacing:1px;">湖畔マート • Kohan Mart</div>
                            <div style="font-size:12px; color:#D7E4DA; margin-top:4px;">Japanese Mini Mart • Project 8, Quezon City</div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 14px; font-size:15px;">
                                Dear <strong>{{ $order->user->name }}</strong>,
                            </p>

                            @if($order->status === 'confirmed')
                                <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#4A3B31;">
                                    @if($order->isDelivery())
                                        Your delivery order <strong>{{ $order->order_number }}</strong> is now <strong>confirmed</strong> and is being packed at our Project 8 store for Lalamove Express dispatch!
                                    @else
                                        Your reservation <strong>{{ $order->order_number }}</strong> is now <strong>confirmed</strong> and is currently being processed and prepared by our staff! Your items have been reserved for your scheduled store pickup.
                                    @endif
                                </p>
                            @elseif($order->status === 'ready_for_pickup')
                                <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#2B4230;">
                                    Great news! Your order <strong>{{ $order->order_number }}</strong> is packed and <strong>ready for pickup</strong> at our counter. Please present your order number or digital receipt upon arrival.
                                </p>
                            @elseif($order->status === 'out_for_delivery')
                                <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#2B4230;">
                                    Your order <strong>{{ $order->order_number }}</strong> is now <strong>out for delivery</strong> via Lalamove Express!
                                    @if($order->courier_driver_name)
                                        Your assigned rider is <strong>{{ $order->courier_driver_name }}</strong> (Plate: <strong>{{ $order->courier_driver_plate }}</strong>).
                                    @endif
                                </p>
                            @elseif($order->status === 'completed')
                                <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#2E2A25;">
                                    @if($order->isDelivery())
                                        Your Lalamove Express order <strong>{{ $order->order_number }}</strong> has been <strong>delivered and completed</strong>!
                                    @else
                                        Thank you for picking up your order <strong>{{ $order->order_number }}</strong>! Your reservation is now marked as <strong>completed</strong>.
                                    @endif
                                    @if($order->points_earned > 0)
                                        You earned <strong>+{{ number_format($order->points_earned, 2) }} loyalty points</strong> on this purchase!
                                    @endif
                                </p>
                            @elseif($order->status === 'cancelled')
                                <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#9E3B33;">
                                    Your order <strong>{{ $order->order_number }}</strong> has been <strong>cancelled</strong>. Any loyalty points redeemed on this order have been automatically returned to your wallet.
                                </p>
                            @endif

                            <!-- Fulfillment & Order Details Box -->
                            <div style="background:#FAF7F2; border:1px solid #E2D8C3; border-radius:14px; padding:16px; margin-bottom:20px; font-size:13px;">
                                <div style="margin-bottom:6px;">
                                    <span style="color:#7A6B5D;">Order Number:</span>
                                    <strong style="font-family:monospace;">{{ $order->order_number }}</strong>
                                </div>
                                <div style="margin-bottom:6px;">
                                    <span style="color:#7A6B5D;">Fulfillment Method:</span>
                                    <strong>{{ $order->isDelivery() ? 'Lalamove Express Delivery' : 'Store Pickup' }}</strong>
                                </div>
                                <div style="margin-bottom:6px;">
                                    <span style="color:#7A6B5D;">Order Status:</span>
                                    <strong style="text-transform:uppercase;">{{ str_replace('_', ' ', $order->status) }}</strong>
                                </div>

                                @if($order->isDelivery())
                                    @if($order->courier_order_id)
                                        <div style="margin-bottom:6px;">
                                            <span style="color:#7A6B5D;">Lalamove Waybill:</span>
                                            <strong style="font-family:monospace;">{{ $order->courier_order_id }}</strong>
                                        </div>
                                    @endif
                                    <div style="margin-bottom:6px;">
                                        <span style="color:#7A6B5D;">Drop-Off Address:</span>
                                        <strong>{{ $order->delivery_address }}</strong>
                                    </div>
                                    @if($order->delivery_fee_minor > 0)
                                        <div style="margin-bottom:6px;">
                                            <span style="color:#7A6B5D;">Delivery Fee:</span>
                                            <strong>₱{{ number_format($order->delivery_fee_minor / 100, 2) }}</strong>
                                        </div>
                                    @endif
                                @else
                                    <div style="margin-bottom:6px;">
                                        <span style="color:#7A6B5D;">Scheduled Pickup:</span>
                                        <strong>{{ $order->pickup_scheduled_at?->format('l, M d, Y \a\t h:i A') ?? 'Store Hours' }}</strong>
                                    </div>
                                @endif

                                <div style="margin-bottom:6px;">
                                    <span style="color:#7A6B5D;">Total Amount:</span>
                                    <strong>₱{{ number_format($order->total_minor / 100, 2) }}</strong>
                                    ({{ $order->is_paid ? 'Paid' : 'Pay at Store' }})
                                </div>
                                <div>
                                    <span style="color:#7A6B5D;">Origin Store:</span>
                                    <strong>41 Insurance, Project 8, Quezon City</strong>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div style="text-align:center; margin:24px 0 8px;">
                                @if($order->isDelivery())
                                    <a href="{{ route('delivery.track', $order) }}"
                                       style="display:inline-block; background:#3F5A45; color:#FAF7F2; text-decoration:none; font-size:13px; font-weight:600; padding:12px 22px; border-radius:12px; margin:4px;">
                                        Track Live Lalamove Delivery
                                    </a>
                                @endif
                                <a href="{{ route('orders.receipt', $order) }}"
                                   style="display:inline-block; background:#6B4E3D; color:#FAF7F2; text-decoration:none; font-size:13px; font-weight:600; padding:12px 22px; border-radius:12px; margin:4px;">
                                    View Official Receipt
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#3A2B22; padding:16px 24px; text-align:center; font-size:11px; color:#D4C8B4;">
                            Arigatou gozaimasu for shopping at Kohan Mart!
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>