namespace App\Http\Controllers\Api;

use App\Actions\Chatbot\GetChatbotHistoryAction;
use App\Actions\Chatbot\GetChatbotSuggestionsAction;
use App\Actions\Chatbot\SendChatbotMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChatbotRequest;
use App\Http\Resources\ChatbotMessageResource;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    use ApiResponse;

    /**
     * Send a message to the AI assistant.
     */
    public function chat(ChatbotRequest $request, SendChatbotMessageAction $action): JsonResponse
    {
        $result = $action->execute($request->user(), $request->validated());

        return self::successResponse(
            'Chat response generated successfully',
            new ChatbotMessageResource($result)
        );
    }

    /**
     * Get current user's chatbot conversation history (paginated).
     */
    public function history(Request $request, GetChatbotHistoryAction $action): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $paginator = $action->execute($request->user(), $perPage);

        $data = [
            'items' => ChatbotMessageResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ];

        return self::successResponse('Chat history retrieved successfully', $data);
    }

    /**
     * Get suggested quick-reply questions (localised).
     */
    public function suggestions(Request $request, GetChatbotSuggestionsAction $action): JsonResponse
    {
        $suggestions = $action->execute($request->input('locale', 'en'));

        return self::successResponse(
            'Suggestions retrieved successfully',
            ['suggestions' => $suggestions]
        );
    }
}