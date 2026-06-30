<?php

namespace App\Http\Controllers;

use App\Services\FaqService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request, FaqService $faq): View
    {
        $search = trim((string) $request->get('search', ''));
        $category = (string) $request->get('category', 'all');

        $faqs = $faq->getFaqs($search !== '' ? $search : null, $category, true);
        $groupedFaqs = $faqs->groupBy(fn ($row) => trim((string) ($row->category ?? '')) ?: 'General');

        return view('support.faq', [
            'faqs' => $faqs,
            'groupedFaqs' => $groupedFaqs,
            'categories' => $faq->getCategories(),
            'categoryCounts' => $faq->getCategoryCounts(true),
            'totalFaqs' => $faqs->count(),
            'search' => $search,
            'activeCategory' => $category,
        ]);
    }
}
