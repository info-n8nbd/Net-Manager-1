<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MinutePackage;
use App\Models\MinuteSale;
use App\Models\Client;
use App\Services\MinuteBillingService;
use Illuminate\Http\Request;

class MinuteSalesController extends Controller
{
    protected MinuteBillingService $minuteBillingService;

    public function __construct(MinuteBillingService $minuteBillingService)
    {
        $this->minuteBillingService = $minuteBillingService;
    }

    public function packages()
    {
        $packages = MinutePackage::latest()->get();
        return view('admin.minute-sales.packages', compact('packages'));
    }

    public function createPackage()
    {
        return view('admin.minute-sales.create-package');
    }

    public function storePackage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        MinutePackage::create($request->only([
            'name', 'duration_minutes', 'price', 'bandwidth_up_mbps',
            'bandwidth_down_mbps', 'connection_type', 'description',
        ]));

        return redirect()->route('admin.minute-sales.packages')->with('success', 'Minute package created');
    }

    public function editPackage(MinutePackage $package)
    {
        return view('admin.minute-sales.edit-package', compact('package'));
    }

    public function updatePackage(Request $request, MinutePackage $package)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $package->update($request->only([
            'name', 'duration_minutes', 'price', 'bandwidth_up_mbps',
            'bandwidth_down_mbps', 'connection_type', 'description', 'is_active',
        ]));

        return redirect()->route('admin.minute-sales.packages')->with('success', 'Minute package updated');
    }

    public function sales(Request $request)
    {
        $report = $this->minuteBillingService->getSalesReport($request->only(['date_from', 'date_to', 'status', 'reseller_id']));
        return view('admin.minute-sales.sales', compact('report'));
    }

    public function sellMinutes()
    {
        $packages = MinutePackage::where('is_active', true)->get();
        $clients = Client::where('status', 'active')->select('id', 'name', 'username', 'phone')->get();
        return view('admin.minute-sales.sell', compact('packages', 'clients'));
    }

    public function processSale(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'minute_package_id' => 'required|exists:minute_packages,id',
        ]);

        $sale = $this->minuteBillingService->purchaseMinutes($request->client_id, $request->minute_package_id);

        return redirect()->route('admin.minute-sales.sales')->with('success', "Minutes sold successfully. Sale ID: {$sale->id}");
    }

    public function destroyPackage(MinutePackage $package)
    {
        $package->delete();
        return redirect()->route('admin.minute-sales.packages')->with('success', 'Package deleted successfully.');
    }
}
