namespace App\Http\Controllers\Api\V1;

use App\actions\address\CreateAddressAction;
use App\actions\address\DeleteAddressAction;
use App\actions\address\UpdateAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return self::successResponse(
            'Addresses retrieved successfully',
            [
                'addresses'   => AddressResource::collection($addresses),
                'total_count' => $addresses->count(),
            ]
        );
    }

    public function show(Address $address): JsonResponse
    {
        $this->authorize('view', $address);

        return self::successResponse(
            'Address retrieved successfully',
            new AddressResource($address)
        );
    }

    public function store(AddressRequest $request, CreateAddressAction $action): JsonResponse
    {
        $address = $action->execute($request->user(), $request->validated());

        return self::successResponse(
            'Address created successfully',
            new AddressResource($address),
            201
        );
    }

    public function update(AddressRequest $request, Address $address, UpdateAddressAction $action): JsonResponse
    {
        $this->authorize('update', $address);

        $updatedAddress = $action->execute($address, $request->validated());

        return self::successResponse(
            'Address updated successfully',
            new AddressResource($updatedAddress)
        );
    }

    public function destroy(Request $request, Address $address, DeleteAddressAction $action): JsonResponse
    {
        $this->authorize('delete', $address);

        $action->execute($request->user(), $address);

        return self::successResponse('Address deleted successfully');
    }
}