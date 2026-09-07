namespace App\Http\Controllers\Api;

use App\Actions\Contact\GetContactMessagesAction;
use App\Actions\Contact\GetContactStatisticsAction;
use App\Actions\Contact\SubmitContactMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitContactRequest;
use App\Http\Requests\Api\UpdateContactStatusRequest;
use App\Http\Resources\ContactMessageCollection;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    use ApiResponse;

    /**
     * Submit a contact message.
     */
    public function submit(SubmitContactRequest $request, SubmitContactMessageAction $action): JsonResponse
    {
        $contactMessage = $action->execute(
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        if (! $contactMessage) {
            return self::errorResponse('Your message appears to be spam', 400);
        }

        return self::successResponse(
            'Thank you for your message. We will get back to you soon.',
            new ContactMessageResource($contactMessage),
            201
        );
    }

    /**
     * Get all contact messages (admin only).
     */
    public function index(Request $request, GetContactMessagesAction $action)
    {
        $this->authorize('viewAny', ContactMessage::class);

        $messages = $action->execute($request);

        return new ContactMessageCollection($messages);
    }

    /**
     * Show specific contact message (admin only).
     */
    public function show(ContactMessage $contactMessage): JsonResponse
    {
        $this->authorize('view', $contactMessage);

        if ($contactMessage->status === 'new') {
            $contactMessage->markAsRead();
        }

        return self::successResponse(
            'Contact message retrieved successfully',
            new ContactMessageResource($contactMessage)
        );
    }

    /**
     * Update contact message status (admin only).
     */
    public function updateStatus(UpdateContactStatusRequest $request, ContactMessage $contactMessage): JsonResponse
    {
        $this->authorize('update', $contactMessage);

        $contactMessage->update($request->validated());

        return self::successResponse(
            'Status updated successfully',
            new ContactMessageResource($contactMessage)
        );
    }

    /**
     * Delete contact message (admin only).
     */
    public function destroy(ContactMessage $contactMessage): JsonResponse
    {
        $this->authorize('delete', $contactMessage);

        $contactMessage->delete();

        return self::successResponse('Message deleted successfully');
    }

    /**
     * Get contact statistics (admin only).
     */
    public function statistics(GetContactStatisticsAction $action): JsonResponse
    {
        $this->authorize('viewAny', ContactMessage::class);

        $stats = $action->execute();

        return self::successResponse('Statistics retrieved successfully', $stats);
    }
}