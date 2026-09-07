namespace App\Actions\Cart;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClearCartAction
{
    public function execute(User $user)
    {
        $cart = $user->getOrCreateCart();

        return DB::transaction(function () use ($cart) {
            $cart->items()->delete();
            $cart->calculateTotals();

            return $cart->fresh();
        });
    }
}