<?php

namespace App\Actions;

use App\Factories\SmartListMealStrategyFactory;
use App\Factories\UploadStrategyFactory;
use App\Models\SmartList;
use Illuminate\Support\Facades\DB;

final class CreateSmartListAction
{
    public function __construct(private SmartListMealStrategyFactory $meals, private UploadStrategyFactory $uploads) {}

    public function execute(int $userId, array $data): SmartList
    {
        $data['user_id'] = $userId;
        $data['description'] = $data['description'] ?? '';
        $mealIds = $data['meal_ids'] ?? [];
        unset($data['meal_ids']);
        if (isset($data['image'])) {
            $data['image'] = $this->uploads->make('smart_lists')->store($data['image']);
        }

        return DB::transaction(function () use ($data, $mealIds) {
            $list = SmartList::create($data);
            $this->meals->make('add')->apply($list, $mealIds);

            return $list->load('meals');
        });
    }
}
