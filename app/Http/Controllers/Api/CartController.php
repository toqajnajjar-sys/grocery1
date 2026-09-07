namespace App\Http\Controllers\Api;

use App\Actions\Cart\AddCartItemAction;
use App\Actions\Cart\ClearCartAction;
use App\Actions\Cart\GetCartAction;
use App\Actions\Cart\RemoveCartItemAction;
use App\Actions\Cart\UpdateCartItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Traits\V1\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    /**
     * Get user's cart
     */
    public function index(Request $request, GetCartAction $action): JsonResponse
    {
        $result = $action->execute($request->user(), $request->query('delivery_type'));

        return self::successResponse(
            'Cart retrieved successfully',
            new CartResource($result['cart'], $result['shippingFee'], $result['totalWithShipping'])
        );
    }

    /**
     * Add item to cart
     */
    public function addItem(AddCartItemRequest $request, AddCartItemAction $action): JsonResponse
    {
        try {
            $cart = $action->execute($request->user(), $request->validated());

            return self::successResponse('Item added to cart successfully', new CartResource($cart));
        } catch (DomainException $e) {
            return self::errorResponse($e->getMessage(), null, 400);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateItem(UpdateCartItemRequest $request, string $itemId, UpdateCartItemAction $action): JsonResponse
    {
        try {
            $cart = $action->execute($request->user(), $itemId, $request->validated()['quantity']);

            return self::successResponse('Cart item updated successfully', new CartResource($cart));
        } catch (DomainException $e) {
            return self::errorResponse($e->getMessage(), null, 400);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Request $request, string $itemId, RemoveCartItemAction $action): JsonResponse
    {
        $cart = $action->execute($request->user(), $itemId);

        return self::successResponse('Item removed from cart successfully', new CartResource($cart));
    }

    /**
     * Clear cart
     */
    public function clear(Request $request, ClearCartAction $action): JsonResponse
    {
        $cart = $action->execute($request->user());

        return self::successResponse('Cart cleared successfully', new CartResource($cart));
    }
}