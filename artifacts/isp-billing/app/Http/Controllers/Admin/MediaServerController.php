<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaServer;
use Illuminate\Http\Request;

class MediaServerController extends Controller
{
    private function cid(): ?int
    {
        if (!auth()->check()) return null;
        return auth()->user()->isSuperAdmin() ? null : auth()->user()->company_id;
    }

    public function index()
    {
        $cid = $this->cid();
        $servers = MediaServer::when($cid, fn($q) => $q->where("company_id", $cid))
            ->orderBy("sort_order")->orderBy("name")->get();
        return view("admin.media-servers.index", compact("servers"));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "name"        => "required|string|max:100",
            "type"        => "required|in:ftp,live_tv,other",
            "url"         => "required|string|max:500",
            "logo_url"    => "nullable|string|max:500",
            "description" => "nullable|string|max:255",
            "sort_order"  => "nullable|integer|min:0",
            "is_active"   => "nullable|boolean",
        ]);
        $data["company_id"] = auth()->user()->company_id ?? 1;
        $data["is_active"]  = $request->boolean("is_active", true);
        $data["sort_order"] = $data["sort_order"] ?? 0;
        MediaServer::create($data);
        return back()->with("success", "Media server added successfully.");
    }

    public function update(Request $request, MediaServer $mediaServer)
    {
        $data = $request->validate([
            "name"        => "required|string|max:100",
            "type"        => "required|in:ftp,live_tv,other",
            "url"         => "required|string|max:500",
            "logo_url"    => "nullable|string|max:500",
            "description" => "nullable|string|max:255",
            "sort_order"  => "nullable|integer|min:0",
            "is_active"   => "nullable|boolean",
        ]);
        $data["is_active"]  = $request->boolean("is_active", true);
        $data["sort_order"] = $data["sort_order"] ?? 0;
        $mediaServer->update($data);
        return back()->with("success", "Media server updated successfully.");
    }

    public function destroy(MediaServer $mediaServer)
    {
        $mediaServer->delete();
        return back()->with("success", "Media server deleted.");
    }
}
