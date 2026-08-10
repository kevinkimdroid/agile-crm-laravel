<?php

namespace App\Http\Controllers;

use App\Models\FaqArticle;
use App\Models\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FaqController extends Controller
{
    protected function kbReady(): bool
    {
        return Schema::hasTable('faq_categories') && Schema::hasTable('faq_articles');
    }

    protected function isManager(): bool
    {
        $user = Auth::guard('vtiger')->user();

        return (bool) ($user?->isAdministrator());
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));
        $categorySlug = (string) $request->get('category', 'all');

        if (! $this->kbReady()) {
            return view('support.faq', [
                'groupedFaqs' => collect(),
                'categories' => collect(),
                'categoryCounts' => [],
                'totalFaqs' => 0,
                'search' => $search,
                'activeCategory' => $categorySlug,
                'canManage' => $this->isManager(),
                'kbReady' => false,
            ]);
        }

        $categories = FaqCategory::orderBy('sort_order')->orderBy('name')->get();

        $query = FaqArticle::with('category')->where('status', 'published');

        if ($categorySlug !== '' && $categorySlug !== 'all') {
            $cat = $categories->firstWhere('slug', $categorySlug);
            if ($cat) {
                $query->where('faq_category_id', $cat->id);
            }
        }

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('question', 'like', $term)
                    ->orWhere('answer', 'like', $term)
                    ->orWhere('tags', 'like', $term);
            });
        }

        $faqs = $query->orderBy('question')->get();
        $groupedFaqs = $faqs->groupBy(fn ($a) => $a->category->name ?? 'General');

        $categoryCounts = FaqArticle::where('status', 'published')
            ->selectRaw('faq_category_id, count(*) as cnt')
            ->groupBy('faq_category_id')
            ->pluck('cnt', 'faq_category_id')
            ->all();

        return view('support.faq', [
            'groupedFaqs' => $groupedFaqs,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'totalFaqs' => $faqs->count(),
            'search' => $search,
            'activeCategory' => $categorySlug,
            'canManage' => $this->isManager(),
            'kbReady' => true,
        ]);
    }

    /** Management dashboard: categories + all articles. */
    public function manage(): View|RedirectResponse
    {
        if (! $this->isManager()) {
            return redirect()->route('support.faq')->with('error', 'You do not have permission to manage the knowledge base.');
        }
        if (! $this->kbReady()) {
            return redirect()->route('support.faq')->with('error', 'Knowledge base tables are missing. Run database migrations.');
        }

        return view('support.faq-manage', [
            'categories' => FaqCategory::withCount('articles')->orderBy('sort_order')->orderBy('name')->get(),
            'articles' => FaqArticle::with('category')->orderByDesc('updated_at')->get(),
            'statuses' => FaqArticle::STATUSES,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        if (! $this->isManager()) {
            abort(403);
        }
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = FaqCategory::uniqueSlug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        FaqCategory::create($data);

        return redirect()->route('support.faq.manage')->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, FaqCategory $category): RedirectResponse
    {
        if (! $this->isManager()) {
            abort(403);
        }
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['sort_order'] = $data['sort_order'] ?? $category->sort_order;
        $category->update($data);

        return redirect()->route('support.faq.manage')->with('success', 'Category updated.');
    }

    public function destroyCategory(FaqCategory $category): RedirectResponse
    {
        if (! $this->isManager()) {
            abort(403);
        }
        $category->delete();

        return redirect()->route('support.faq.manage')->with('success', 'Category deleted. Its FAQs were kept and uncategorised.');
    }

    public function createArticle(): View|RedirectResponse
    {
        if (! $this->isManager()) {
            return redirect()->route('support.faq')->with('error', 'You do not have permission.');
        }

        return view('support.faq-article-form', [
            'article' => new FaqArticle(['status' => 'published']),
            'categories' => FaqCategory::orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => FaqArticle::STATUSES,
        ]);
    }

    public function storeArticle(Request $request): RedirectResponse
    {
        if (! $this->isManager()) {
            abort(403);
        }
        $data = $this->validateArticle($request);
        $user = Auth::guard('vtiger')->user();
        $data['created_by'] = $user?->id;
        $data['created_by_name'] = $user
            ? (trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->user_name)
            : 'Staff';
        FaqArticle::create($data);

        return redirect()->route('support.faq.manage')->with('success', 'FAQ added.');
    }

    public function editArticle(FaqArticle $article): View|RedirectResponse
    {
        if (! $this->isManager()) {
            return redirect()->route('support.faq')->with('error', 'You do not have permission.');
        }

        return view('support.faq-article-form', [
            'article' => $article,
            'categories' => FaqCategory::orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => FaqArticle::STATUSES,
        ]);
    }

    public function updateArticle(Request $request, FaqArticle $article): RedirectResponse
    {
        if (! $this->isManager()) {
            abort(403);
        }
        $article->update($this->validateArticle($request));

        return redirect()->route('support.faq.manage')->with('success', 'FAQ updated.');
    }

    public function destroyArticle(FaqArticle $article): RedirectResponse
    {
        if (! $this->isManager()) {
            abort(403);
        }
        $article->delete();

        return redirect()->route('support.faq.manage')->with('success', 'FAQ deleted.');
    }

    /**
     * JSON search over published articles — used by the ticket resolution panel.
     */
    public function search(Request $request): JsonResponse
    {
        if (! $this->kbReady()) {
            return response()->json(['results' => []]);
        }

        $q = trim((string) $request->get('q', ''));
        $query = FaqArticle::with('category')->where('status', 'published');
        if ($q !== '') {
            // Rank loosely by matching any of the significant words in question/answer/tags.
            $words = array_filter(preg_split('/\s+/', $q), fn ($w) => mb_strlen($w) >= 3);
            $query->where(function ($outer) use ($q, $words) {
                $outer->where('question', 'like', '%'.$q.'%')
                    ->orWhere('tags', 'like', '%'.$q.'%');
                foreach ($words as $w) {
                    $outer->orWhere('question', 'like', '%'.$w.'%')
                        ->orWhere('answer', 'like', '%'.$w.'%')
                        ->orWhere('tags', 'like', '%'.$w.'%');
                }
            });
        }

        $results = $query->orderByDesc('helpful_count')->limit(8)->get()->map(fn ($a) => [
            'id' => $a->id,
            'question' => $a->question,
            'answer' => $a->answer,
            'category' => $a->category->name ?? 'General',
        ]);

        return response()->json(['results' => $results]);
    }

    /**
     * Increment helpful count when an FAQ is used to resolve a ticket.
     */
    public function markHelpful(Request $request): JsonResponse
    {
        if (! $this->kbReady()) {
            return response()->json(['ok' => false], 404);
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['ok' => false], 422);
        }

        $article = FaqArticle::query()->where('id', $id)->where('status', 'published')->first();
        if (! $article) {
            return response()->json(['ok' => false], 404);
        }

        $article->increment('helpful_count');

        return response()->json(['ok' => true, 'helpful_count' => (int) $article->fresh()->helpful_count]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateArticle(Request $request): array
    {
        $data = $request->validate([
            'faq_category_id' => 'nullable|exists:faq_categories,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:20000',
            'tags' => 'nullable|string|max:255',
            'status' => 'required|in:'.implode(',', array_keys(FaqArticle::STATUSES)),
        ]);
        $data['faq_category_id'] = $data['faq_category_id'] ?: null;

        return $data;
    }
}
