namespace App\Actions\Chatbot;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetChatbotHistoryAction
{
    public function execute(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->chatbotMessages()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}