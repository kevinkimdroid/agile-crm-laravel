<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FaqService
{
    /**
     * @return Collection<int, object>
     */
    public function getCategories(): Collection
    {
        try {
            return DB::connection('vtiger')
                ->table('vtiger_faqcategories')
                ->orderBy('sortorderid')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('FaqService::getCategories: ' . $e->getMessage());

            return collect();
        }
    }

    /**
     * @return Collection<int, object>
     */
    public function getFaqs(?string $search = null, ?string $category = null, bool $staffView = true): Collection
    {
        try {
            $query = DB::connection('vtiger')
                ->table('vtiger_faq as f')
                ->join('vtiger_crmentity as e', 'f.id', '=', 'e.crmid')
                ->where('e.deleted', 0)
                ->select(
                    'f.id',
                    'f.faq_no',
                    'f.question',
                    'f.answer',
                    'f.category',
                    'f.status',
                    'f.tags',
                    'e.createdtime',
                    'e.modifiedtime'
                );

            if ($staffView) {
                $query->whereNotIn('f.status', ['Obsolete']);
            } else {
                $query->where('f.status', 'Published');
            }

            if ($category !== null && $category !== '' && $category !== 'all') {
                $query->where('f.category', $category);
            }

            $search = trim((string) $search);
            if ($search !== '') {
                $term = '%' . $search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('f.question', 'like', $term)
                        ->orWhere('f.answer', 'like', $term)
                        ->orWhere('f.tags', 'like', $term)
                        ->orWhere('f.faq_no', 'like', $term);
                });
            }

            return $query
                ->orderBy('f.category')
                ->orderBy('f.question')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('FaqService::getFaqs: ' . $e->getMessage());

            return collect();
        }
    }

    /**
     * @return array<string, int>
     */
    public function getCategoryCounts(bool $staffView = true): array
    {
        try {
            $query = DB::connection('vtiger')
                ->table('vtiger_faq as f')
                ->join('vtiger_crmentity as e', 'f.id', '=', 'e.crmid')
                ->where('e.deleted', 0);

            if ($staffView) {
                $query->whereNotIn('f.status', ['Obsolete']);
            } else {
                $query->where('f.status', 'Published');
            }

            return $query
                ->selectRaw('f.category, count(*) as cnt')
                ->groupBy('f.category')
                ->pluck('cnt', 'category')
                ->map(fn ($c) => (int) $c)
                ->all();
        } catch (\Throwable $e) {
            Log::warning('FaqService::getCategoryCounts: ' . $e->getMessage());

            return [];
        }
    }

    public function getTotalCount(bool $staffView = true): int
    {
        return $this->getFaqs(null, null, $staffView)->count();
    }
}
