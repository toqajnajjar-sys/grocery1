namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAddressAction
{
    public function execute(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data) {
            $isFirstAddress = $user->addresses()->count() === 0;
            $data['is_default'] = ($data['is_default'] ?? false) || $isFirstAddress;
            $data['phone'] = $this->sanitizePhone($data['phone'] ?? '', $data['country_code'] ?? '');

            return $user->addresses()->create($data);
        });
    }

    private function sanitizePhone(string $phone, string $code): string
    {
        $phone = trim($phone);
        $code = trim($code);

        if ($code !== '' && str_starts_with($phone, $code)) {
            return substr($phone, strlen($code));
        }

        return $phone;
    }
}