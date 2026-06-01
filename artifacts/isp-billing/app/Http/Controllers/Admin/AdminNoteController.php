<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNoteController extends Controller
{
    private function companyId(): ?int
    {
        return Auth::user()->company_id;
    }

    private function noteQuery()
    {
        return AdminNote::where("user_id", Auth::id())
                        ->where("company_id", $this->companyId());
    }

    private function authorizeNote(AdminNote $note): void
    {
        abort_if(
            $note->user_id !== Auth::id() || $note->company_id !== $this->companyId(),
            403
        );
    }

    public function index(Request $request)
    {
        $query = $this->noteQuery();

        if ($request->filled("search")) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where("title", "ilike", "%{$s}%")
                  ->orWhere("content", "ilike", "%{$s}%")
                  ->orWhere("label", "ilike", "%{$s}%");
            });
        }

        if ($request->filled("label")) {
            $query->where("label", $request->label);
        }

        if ($request->filled("color")) {
            $query->where("color", $request->color);
        }

        $notes  = $query->orderByDesc("is_pinned")->orderByDesc("updated_at")->get();
        $labels = $this->noteQuery()->whereNotNull("label")->distinct()->pluck("label");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(["notes" => $notes, "labels" => $labels]);
        }

        return view("admin.notes.index", compact("notes", "labels"));
    }

    public function store(Request $request)
    {
        $request->validate([
            "content" => "required|string",
            "title"   => "nullable|string|max:255",
            "color"   => "nullable|string|max:20",
            "label"   => "nullable|string|max:100",
        ]);

        $note = AdminNote::create([
            "user_id"    => Auth::id(),
            "company_id" => $this->companyId(),
            "title"      => $request->title,
            "content"    => $request->content,
            "color"      => $request->color ?? "yellow",
            "label"      => $request->label,
            "is_pinned"  => false,
        ]);

        return response()->json(["success" => true, "note" => $note]);
    }

    public function show(AdminNote $adminNote)
    {
        $this->authorizeNote($adminNote);
        return response()->json(["note" => $adminNote]);
    }

    public function update(Request $request, AdminNote $adminNote)
    {
        $this->authorizeNote($adminNote);

        $request->validate([
            "content" => "required|string",
            "title"   => "nullable|string|max:255",
            "color"   => "nullable|string|max:20",
            "label"   => "nullable|string|max:100",
        ]);

        $adminNote->update([
            "title"   => $request->title,
            "content" => $request->content,
            "color"   => $request->color ?? $adminNote->color,
            "label"   => $request->label,
        ]);

        return response()->json(["success" => true, "note" => $adminNote->fresh()]);
    }

    public function destroy(AdminNote $adminNote)
    {
        $this->authorizeNote($adminNote);
        $adminNote->delete();
        return response()->json(["success" => true]);
    }

    public function togglePin(AdminNote $adminNote)
    {
        $this->authorizeNote($adminNote);
        $adminNote->update(["is_pinned" => !$adminNote->is_pinned]);
        return response()->json(["success" => true, "is_pinned" => $adminNote->is_pinned]);
    }
}
