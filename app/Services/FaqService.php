<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class FaqService
{
    public function getFaqs(
        ?string $category,
        bool $activeOnly,
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        $query = Faq::query();

        if ($category !== null) {
            $query->where('category', $category);
        }

        if ($activeOnly) {
            $query->active();
        }

        if ($search !== null) {
            $query->where(function ($query) use ($search) {
                $query->where('question', 'LIKE', "%{$search}%")
                    ->orWhere('answer', 'LIKE', "%{$search}%");
            });
        }

        return $query
            ->ordered()
            ->paginate($perPage);
    }

    public function getCategories(): Collection
    {
        return Faq::active()
            ->distinct('category')
            ->pluck('category')
            ->filter()
            ->values();
    }

    public function getByCategory(string $category): Collection
    {
        return Faq::active()
            ->category($category)
            ->ordered()
            ->get();
    }

    public function create(array $data): Faq
    {
        return Faq::create($data);
    }

    public function update(Faq $faq, array $data): Faq
    {
        $faq->update($data);

        return $faq->fresh();
    }

    public function delete(Faq $faq): void
    {
        $faq->delete();
    }
}