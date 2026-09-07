namespace App\Actions\Cart;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use DomainException;

class AddCartItemAction
{
    public function execute(User $user, array $data)
    {
        $maxPerProduct = config('cart.max_quantity_per_product', 10);
        $meal = Meal::findOrFail($data['meal_id']);

        if (! $meal->is_available) {
            throw new DomainException('This meal is currently unavailable');
        }

        if (! $meal->isInStock()) {
            throw new DomainException('This meal is out of stock');
        }

        if ($meal->stock_quantity < $data['quantity']) {
            throw new DomainException("Only {$meal->stock_quantity} items available in stock");
        }

        return DB::transaction(function () use ($user, $meal, $data, $maxPerProduct) {
            $cart = $user->getOrCreateCart();
            $cartItem = $cart->items()->where('meal_id', $meal->id)->first();

            if ($cartItem) {
                $newQuantity = $cartItem->quantity + $data['quantity'];
                $effectiveMax = min($maxPerProduct, $meal->stock_quantity);

                if ($newQuantity > $effectiveMax) {
                    throw new DomainException("Maximum {$maxPerProduct} units per product. You already have {$cartItem->quantity} in cart; maximum total is {$effectiveMax}.");
                }

                if ($meal->stock_quantity < $newQuantity) {
                    throw new DomainException("Only {$meal->stock_quantity} items available in stock");
                }

                $cartItem->update(['quantity' => $newQuantity]);
            } else {
                $discountAmount = 0;
                if ($meal->resolved_discount_price) {
                    $discountAmount = ($meal->price - $meal->resolved_discount_price) * $data['quantity'];
                }

                $cart->items()->create([
                    'meal_id' => $meal->id,
                    'quantity' => $data['quantity'],
                    'unit_price' => $meal->final_price,
                    'discount_amount' => $discountAmount,
                    'subtotal' => $meal->final_price * $data['quantity'],
                ]);
            }

            $cart->calculateTotals();
            return $cart->fresh(['items.meal.category', 'items.meal.subcategory']);
        });
    }
}