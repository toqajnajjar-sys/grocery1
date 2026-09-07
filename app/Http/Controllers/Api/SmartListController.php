<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateSmartListAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddSmartListMealRequest;
use App\Http\Requests\Api\SmartListRequest;
use App\Http\Resources\Api\SmartListResource;
use App\Services\SmartListService;
use Illuminate\Http\Request;

class SmartListController extends Controller
{
    public function __construct(private SmartListService $lists, private CreateSmartListAction $createList) {}

    public function index(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Smart lists retrieved successfully',
            'data' => SmartListResource::collection($this->lists->all($request->user()->id))]);
    }

    public function store(SmartListRequest $request)
    {
        return response()->json(['success' => true, 'message' => 'Wish list created successfully',
            'data' => new SmartListResource($this->createList->execute($request->user()->id, $request->validated()))]);
    }

    public function show(Request $request, string $id)
    {
        return response()->json(['success' => true, 'message' => 'Smart list retrieved successfully',
            'data' => new SmartListResource($this->lists->find($request->user()->id, $id))]);
    }

    public function update(SmartListRequest $request, string $id)
    {
        return response()->json(['success' => true, 'message' => 'Wish list updated successfully',
            'data' => new SmartListResource($this->lists->update($request->user()->id, $id, $request->validated()))]);
    }

    public function destroy(Request $request, string $id)
    {
        $this->lists->delete($request->user()->id, $id);

        return response()->json(['success' => true, 'message' => 'Wish list deleted successfully']);
    }

    public function addMeal(AddSmartListMealRequest $request, string $id)
    {
        return response()->json(['success' => true, 'message' => 'Item added to wish list successfully',
            'data' => new SmartListResource($this->lists->changeMeals($request->user()->id, $id, 'add', [$request->validated('meal_id')]))]);
    }

    public function removeMeal(Request $request, string $id, string $mealId)
    {
        return response()->json(['success' => true, 'message' => 'Item removed from wish list successfully',
            'data' => new SmartListResource($this->lists->changeMeals($request->user()->id, $id, 'remove', [$mealId]))]);
    }
}
