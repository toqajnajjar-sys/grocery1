<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OperationRejected;
use App\Http\Controllers\Controller;
use App\Http\Responses\OperationResponder;
use App\Models\Order;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $service, private OperationResponder $responses) {}

    public function paymentHistory(Request $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->paymentHistory($request->user()), 'Failed to retrieve payment history');
    }

    public function receipt(Request $request, Order $order): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->receipt($request->user(), $order), 'Failed to retrieve receipt');
    }

    public function invoice(Request $request, Order $order): Response
    {
        try {
            $order = $this->service->invoiceOrder($request->user(), $order);

            return Pdf::loadView('invoices.show', ['order' => $order])
                ->download('invoice.pdf'.$order->id.'.pdf');
        } catch (OperationRejected $e) {
            return response()->json($e->details, 404);
        }
    }
}
