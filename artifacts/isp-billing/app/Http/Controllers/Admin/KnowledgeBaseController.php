<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        $categories = KnowledgeBaseCategory::withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.knowledge-base.index', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        KnowledgeBaseCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon ?? 'fas fa-book',
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, KnowledgeBaseCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? $category->sort_order,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Category updated successfully.');
    }

    public function deleteCategory(KnowledgeBaseCategory $category)
    {
        if ($category->articles()->count() > 0) {
            return back()->with('error', 'Cannot delete category with existing articles. Please delete or move articles first.');
        }

        $category->delete();
        return back()->with('success', 'Category deleted successfully.');
    }

    public function articles(KnowledgeBaseCategory $category)
    {
        $articles = $category->articles()
            ->with('author')
            ->orderBy('is_featured', 'desc')
            ->orderBy('title')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.knowledge-base.articles', compact('category', 'articles'));
    }

    public function createArticle()
    {
        $categories = KnowledgeBaseCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.knowledge-base.create-article', compact('categories'));
    }

    public function storeArticle(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:knowledge_base_categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'audience' => 'nullable|string|in:admin,client,all',
        ]);

        KnowledgeBaseArticle::create([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'content' => $request->content,
            'audience' => $request->audience ?? 'all',
            'is_published' => $request->has('is_published'),
            'is_featured' => $request->has('is_featured'),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.knowledge-base.index')
            ->with('success', 'Article created successfully.');
    }

    public function editArticle(KnowledgeBaseArticle $article)
    {
        $categories = KnowledgeBaseCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.knowledge-base.edit-article', compact('article', 'categories'));
    }

    public function updateArticle(Request $request, KnowledgeBaseArticle $article)
    {
        $request->validate([
            'category_id' => 'required|exists:knowledge_base_categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'audience' => 'nullable|string|in:admin,client,all',
        ]);

        $article->update([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'content' => $request->content,
            'audience' => $request->audience ?? $article->audience,
            'is_published' => $request->has('is_published'),
            'is_featured' => $request->has('is_featured'),
        ]);

        return back()->with('success', 'Article updated successfully.');
    }

    public function deleteArticle(KnowledgeBaseArticle $article)
    {
        $article->delete();
        return back()->with('success', 'Article deleted successfully.');
    }
}
