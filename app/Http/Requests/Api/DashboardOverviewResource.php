namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardOverviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tracking_order' => $this['tracking_order'] ? [
                'id'                 => $this['tracking_order']->id,
                'order_number'       => $this['tracking_order']->order_number,
                'status'             => $this['tracking_order']->status,
                'status_description' => $this['tracking_order']->status_description,
                'status_position'    => $this['tracking_order']->status_position,
            ] : null,
            'loyalty_points' => (int) ($this['user']->loyalty_points ?? 0),
            'store_credits'  => (float) ($this['user']->store_credits ?? 0),
            'current_cart'   => $this['current_cart'],
            'upcoming_delivery' => $this['upcoming_delivery'] ? [
                'order_id'                => $this['upcoming_delivery']->id,
                'order_number'            => $this['upcoming_delivery']->order_number,
                'date'                    => $this['upcoming_delivery']->estimated_delivery_time?->format('Y-m-d'),
                'time'                    => $this['upcoming_delivery']->estimated_delivery_time?->format('H:i'),
                'estimated_delivery_time' => $this['upcoming_delivery']->estimated_delivery_time,
            ] : null,
        ];
    }
}