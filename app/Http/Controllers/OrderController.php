<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\OrderItem;
use Midtrans\Snap;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function process()
    {
        return view('frontend.order.checkout');
    }

    // ========== Tambahan ========== //
    private function getShippingCost($destination, $totalWeight)
    {
        // Ambil ongkir dari OngkirController
        $ongkir = new \App\Http\Controllers\OngkirController();
        return $ongkir->shippingCostManual($destination, $totalWeight);
    }

    private function getSelectedShipping($destination, $totalWeight, $shippingService)
    {
        $shippingOptions = $this->getShippingCost($destination, $totalWeight);

        $selectedShipping = null;
        if ($shippingOptions['results']) {
            foreach ($shippingOptions['results'] as $shippingOption) {
                if (str_replace(' ', '', $shippingOption['service']) == $shippingService) {
                    $selectedShipping = $shippingOption;
                    break;
                }
            }
        }

        return $selectedShipping;
    }

    // ========== Checkout Logic ========== //
    public function checkout(Request $request)
    {
        $params = $request->except('_token');

        $order = \DB::transaction(function () use ($params) {
            $destination = $params['city'];
            $items = \Cart::getContent();

            $totalWeight = 0;
            foreach ($items as $item) {
                $totalWeight += ($item->quantity * $item->associatedModel->weight);
            }

            $selectedShipping = $this->getSelectedShipping($destination, $totalWeight, $params['shippingService']);

            $baseTotalPrice = \Cart::getSubTotal();
            $shippingCost = $selectedShipping['cost'];
            $discountAmount = 0;
            $discountPercent = 0;
            $grandTotal = ($baseTotalPrice + $shippingCost) - $discountAmount;

            $orderDate = date('Y-m-d H:i:s');
            $paymentDue = (new \DateTime($orderDate))->modify('+3 day')->format('Y-m-d H:i:s');

            // Simpan ke user
            $user_profile = [
                'username' => $params['fullName'],
                'address' => $params['address'],
                'address2' => $params['address2'],
                'province_id' => $params['province'],
                'city_id' => $params['city'],
                'postcode' => $params['postcode'],
                'phone' => $params['phone'],
                'email' => $params['email'],
            ];
            auth()->user()->update($user_profile);

            // Simpan ke order
            $orderParams = [
                'user_id' => auth()->id(),
                'code' => Order::generateCode(),
                'status' => Order::CREATED,
                'order_date' => $orderDate,
                'payment_due' => $paymentDue,
                'payment_status' => Order::UNPAID,
                'base_total_price' => $baseTotalPrice,
                'discount_amount' => $discountAmount,
                'discount_percent' => $discountPercent,
                'shipping_cost' => $shippingCost,
                'grand_total' => $grandTotal,
                'customer_first_name' => $params['fullName'],
                'customer_address' => $params['address'],
                'customer_address2' => $params['address2'],
                'customer_phone' => $params['phone'],
                'customer_email' => $params['email'],
                'customer_city_id' => $params['city'],
                'customer_province_id' => $params['province'],
                'customer_postcode' => $params['postcode'],
                'notes' => $params['notes'],
                'shipping_courier' => $selectedShipping['courier'],
                'shipping_service_name' => $selectedShipping['service'],
            ];

            $order = Order::create($orderParams);

            // Order item
            foreach (\Cart::getContent() as $item) {
                $itemDiscountAmount = 0;
                $itemDiscountPercent = 0;
                $itemBaseTotal = $item->quantity * $item->price;
                $itemSubTotal = $itemBaseTotal - $itemDiscountAmount;

                $product = $item->associatedModel;

                $orderItemParams = [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $item->quantity,
                    'base_price' => $item->price,
                    'base_total' => $itemBaseTotal,
                    'discount_amount' => $itemDiscountAmount,
                    'discount_percent' => $itemDiscountPercent,
                    'sub_total' => $itemSubTotal,
                    'name' => $item->name,
                    'weight' => $product->weight,
                ];

                OrderItem::create($orderItemParams);

                // Update stok
                $product->quantity -= $item->quantity;
                $product->save();
            }

            // Simpan pengiriman
            $shipmentParams = [
                'user_id' => auth()->id(),
                'order_id' => $order->id,
                'status' => Shipment::PENDING,
                'total_qty' => \Cart::getTotalQuantity(),
                'total_weight' => $totalWeight,
                'first_name' => $params['fullName'],
                'address1' => $params['address'],
                'address2' => $params['address2'],
                'phone' => $params['phone'],
                'email' => $params['email'],
                'city_id' => $params['city'],
                'province_id' => $params['province'],
                'postcode' => $params['postcode'],
            ];
            Shipment::create($shipmentParams);

            return $order;
        });

        if (!isset($order)) {
            return redirect()->back()->with([
                'message' => 'Terjadi kesalahan saat membuat pesanan!',
                'alert-type' => 'danger'
            ]);
        }

        \Cart::clear();
        \Cart::clearCartConditions();

        $this->initPaymentGateway();

        $customerDetails = [
            'first_name' => $order->customer_first_name,
            'email' => $order->customer_email,
            'phone' => $order->customer_phone,
        ];

        $transaction_details = [
            'enable_payments' => Payment::PAYMENT_CHANNELS,
            'transaction_details' => [
                'order_id' => $order->code,
                'gross_amount' => $order->grand_total,
            ],
            'customer_details' => $customerDetails,
            'expiry' => [
                'start_time' => date('Y-m-d H:i:s T'),
                'unit' => Payment::EXPIRY_UNIT,
                'duration' => Payment::EXPIRY_DURATION,
            ]
        ];

        try {
            $snap = Snap::createTransaction($transaction_details);

            $order->payment_token = $snap->token;
            $order->payment_url = $snap->redirect_url;
            $order->save();

            return redirect($order->payment_url);
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message' => 'Gagal menghubungkan ke Midtrans: ' . $e->getMessage(),
                'alert-type' => 'danger'
            ]);
        }
    }

    // ========== Tambahan Midtrans ========== //
    private function initPaymentGateway()
    {
        \Midtrans\Config::$serverKey = config('midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('midtrans.isProduction');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }
}
