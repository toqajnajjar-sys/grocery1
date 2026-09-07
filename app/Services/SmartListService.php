<?php

namespace App\Services;

use App\Factories\SmartListMealStrategyFactory;
use App\Factories\UploadStrategyFactory;
use App\Models\SmartList;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class SmartListService
{
    public function __construct(private SmartListMealStrategyFactory $meals, private UploadStrategyFactory $uploads) {}

    public function all(int $userId): Collection
    {
        return SmartList::where('user_id', $userId)->with('meals')->get();
    }

    public function find(int $userId, string $id): SmartList
    {
        return SmartList::where('user_id', $userId)->with('meals')->findOrFail($id);
    }

    public function create(int $userId, array $data): SmartList
    {
        $data['user_id'] = $userId;
        $data['description'] = $data['description'] ?? '';
        $mealIds = $data['meal_ids'] ?? [];
        unset($data['meal_ids']);
        $data = $this->storeImage($data);

        return DB::transaction(function () use ($data, $mealIds) {
            $list = SmartList::create($data);
            $this->meals->make('add')->apply($list, $mealIds);

            return $list->load('meals');
        });
    }

    public function update(int $userId, string $id, array $data): SmartList
    {
        $list = $this->find($userId, $id);
        if (array_key_exists('description', $data) && $data['description'] === null) {
            $data['description'] = '';
        }
        $mealIds = $data['meal_ids'] ?? null;
        unset($data['meal_ids']);
        $data = $this->storeImage($data);

        return DB::transaction(function () use ($list, $data, $mealIds) {
            $list->update($data);
            if ($mealIds !== null) {
                $this->meals->make('replace')->apply($list, $mealIds);
            }

            return $list->load('meals');
        });
    }

    public function delete(int $userId, string $id): void
    {
        $list = $this->find($userId, $id);
        DB::transaction(function () use ($list) {
            $list->meals()->detach();
            $list->delete();
        });
    }

    public function changeMeals(int $userId, string $id, string $operation, array $mealIds): SmartList
    {
        $list = $this->find($userId, $id);
        $this->meals->make($operation)->apply($list, $mealIds);

        return $list->load('meals');
    }

    private function storeImage(array $data): array
    {
        if (isset($data['image'])) {
            $data['image'] = $this->uploads->make('smart_lists')->store($data['image']);
        }

        return $data;
    }
}
