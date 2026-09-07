namespace App\Actions\Address;

use App\Models\Address;
use Illuminate\Support\Facades\DB;

class UpdateAddressAction
{
    public function execute(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data) {
            if (isset($data['phone'])) {
                $countryCode = $data['country_code'] ?? $address->country_code ?? '';
                $data['phone'] = $this->sanitizePhone($data['phone'], $countryCode);
            }

            $address->update($data);
            return $address->fresh();
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