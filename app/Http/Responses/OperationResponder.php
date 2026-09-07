<?php

namespace App\Http\Responses;

use App\Exceptions\OperationRejected;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

final class OperationResponder
{
    public function respond(Closure $operation, string $failureMessage, int $successStatus = 200): JsonResponse
    {
        try {
            return response()->json($operation(), $successStatus);
        } catch (OperationRejected $e) {
            $status = match ($e->reason) {
                'not_found' => 404,
                'forbidden' => 403,
                'invalid_input' => 422,
                default => 400,
            };

            return response()->json($e->details, $status);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $failureMessage, 'error' => $e->getMessage()], 500);
        }
    }
}
