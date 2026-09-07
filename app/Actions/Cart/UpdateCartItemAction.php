namespace App\Actions\Cart;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use DomainException;

class UpdateCartItemAction
{
    public function execute(User $user, string $itemId, int $quantity)
    {
        $cart = $user->getOrCreateCart();
        $cartItem = $cart->items()->findOrFail($itemId);
        $meal = $cartItem->meal;

        if ($meal->stock_quantity < $quantity) {
            throw new DomainException("Only {$meal->stock_quantity} items available in stock");
        }

        return DB::transaction(function () use ($cart, $cartItem, $quantity) {
            $cartItem->update(['quantity' => $quantity]);
            $cart->calculateTotals();

            return $cart->fresh(['items.meal.category', 'items.meal.subcategory']);
        });
    }
}