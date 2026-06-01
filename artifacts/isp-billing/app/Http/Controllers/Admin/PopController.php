<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pop;
use App\Models\Reseller;
use App\Models\Branch;
use App\Models\Zone;
use App\Models\MikrotikDevice;
use Illuminate\Http\Request;

class PopController extends Controller
{
    public function index()
    {
        $pops = Pop::with(["branch", "zone", "mikrotikDevice", "reseller"])
            ->withCount("clients")
            ->latest()
            ->get();
        return view("admin.pops.index", compact("pops"));
    }

    public function create()
    {
        $branches  = Branch::where("is_active", true)->get();
        $zones     = Zone::all();
        $devices   = MikrotikDevice::all();
        $resellers = Reseller::select("id","name","company_name","reseller_id","type","phone","address","area_name_en","raw_bandwidth")
            ->whereDoesntHave("pop")
            ->orderBy("name")
            ->get();
        return view("admin.pops.create", compact("branches", "zones", "devices", "resellers"));
    }

    public function store(Request $request)
    {
        $request->validate([
            "name"                 => "required|string|max:255",
            "code"                 => "required|string|max:20|unique:pops",
            "total_bandwidth_mbps" => "nullable|integer|min:0",
            "pop_source"           => "required|in:standalone,mac_reseller,bandwidth_reseller",
            "reseller_id"          => "nullable|exists:resellers,id",
        ]);

        $data = $request->only([
            "name","code","branch_id","zone_id","address","contact_person",
            "contact_phone","total_bandwidth_mbps","uplink_provider","uplink_type",
            "mikrotik_device_id","status","pop_source","reseller_id",
        ]);

        if (($data["pop_source"] ?? "standalone") === "standalone") {
            $data["reseller_id"] = null;
        }

        Pop::create($data);
        return redirect()->route("admin.pops.index")->with("success", "POP created successfully.");
    }

    public function show(Pop $pop)
    {
        $pop->load(["branch", "zone", "mikrotikDevice", "clients", "ipPools", "reseller"]);
        return view("admin.pops.show", compact("pop"));
    }

    public function edit(Pop $pop)
    {
        $branches  = Branch::where("is_active", true)->get();
        $zones     = Zone::all();
        $devices   = MikrotikDevice::all();
        $resellers = Reseller::select("id","name","company_name","reseller_id","type","phone","address","area_name_en","raw_bandwidth")
            ->where(function($q) use ($pop) {
                $q->whereDoesntHave("pop")->orWhere("id", $pop->reseller_id);
            })
            ->orderBy("name")
            ->get();
        return view("admin.pops.edit", compact("pop", "branches", "zones", "devices", "resellers"));
    }

    public function update(Request $request, Pop $pop)
    {
        $request->validate([
            "name"        => "required|string|max:255",
            "code"        => "required|string|max:20|unique:pops,code," . $pop->id,
            "pop_source"  => "required|in:standalone,mac_reseller,bandwidth_reseller",
            "reseller_id" => "nullable|exists:resellers,id",
        ]);

        $data = $request->only([
            "name","code","branch_id","zone_id","address","contact_person",
            "contact_phone","total_bandwidth_mbps","used_bandwidth_mbps","uplink_provider",
            "uplink_type","mikrotik_device_id","status","pop_source","reseller_id",
        ]);

        if (($data["pop_source"] ?? "standalone") === "standalone") {
            $data["reseller_id"] = null;
        }

        $pop->update($data);
        return redirect()->route("admin.pops.index")->with("success", "POP updated successfully.");
    }

    public function destroy(Pop $pop)
    {
        $pop->delete();
        return redirect()->route("admin.pops.index")->with("success", "POP deleted successfully.");
    }

    public function resellerInfo(Reseller $reseller)
    {
        return response()->json([
            "name"       => $reseller->company_name ?? $reseller->name,
            "phone"      => $reseller->phone ?? "",
            "address"    => $reseller->address ?? "",
            "area"       => $reseller->area_name_en ?? "",
            "type"       => $reseller->type,
            "pop_source" => $reseller->type === "bandwidth" ? "bandwidth_reseller" : "mac_reseller",
            "bandwidth"  => $reseller->raw_bandwidth ?? 0,
        ]);
    }
}
