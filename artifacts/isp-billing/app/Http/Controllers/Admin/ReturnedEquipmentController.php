<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnedEquipment;
use App\Models\Client;
use App\Models\Instrument;
use App\Models\FiberStock;
use App\Models\FiberHistory;
use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnedEquipmentController extends Controller
{
    private function checkAdminAccess()
    {
        $user = auth()->user();
        $isAdminOk = $user && (
            $user->user_type === "admin" ||
            $user->user_type === "super_admin" ||
            $user->hasRole("Super Admin") ||
            $user->hasRole("Admin") ||
            $user->hasRole("Administrator")
        );
        if (!$isAdminOk) {
        }
    }

    public function index(Request $request)
    {
        $this->checkAdminAccess();
        $type     = $request->type;
        $status   = $request->status; // pending | restocked | assigned
        $search   = $request->search;

        $query = ReturnedEquipment::with(['returnedByUser', 'receivedByUser', 'restockedByUser', 'assignedClient'])
            ->orderBy('return_date', 'desc')
            ->orderBy('id', 'desc');

        if ($type)   { $query->where('equipment_type', $type); }
        if ($status === 'restocked')  { $query->where('is_restocked', true); }
        if ($status === 'pending')    { $query->where('is_restocked', false); }
        if ($status === 'assigned')   { $query->whereNotNull('assigned_to_client_id'); }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'ilike', "%{$search}%")
                  ->orWhere('client_client_id', 'ilike', "%{$search}%")
                  ->orWhere('equipment_name', 'ilike', "%{$search}%")
                  ->orWhere('serial_number', 'ilike', "%{$search}%")
                  ->orWhere('brand', 'ilike', "%{$search}%")
                  ->orWhere('model', 'ilike', "%{$search}%");
            });
        }

        $perPage   = in_array((int)$request->per_page, [20,50,100,200]) ? (int)$request->per_page : 25;
        $equipments = $query->paginate($perPage);

        $typeCounts = ReturnedEquipment::select('equipment_type', DB::raw('COUNT(*) as count'))
            ->groupBy('equipment_type')->pluck('count', 'equipment_type');

        $totalCount     = ReturnedEquipment::count();
        $pendingCount   = ReturnedEquipment::where('is_restocked', false)->count();
        $restockedCount = ReturnedEquipment::where('is_restocked', true)->count();
        $assignedCount  = ReturnedEquipment::whereNotNull('assigned_to_client_id')->count();

        $fiberStocks = FiberStock::where('status', 'active')->orderBy('fiber_code')->get(['id', 'fiber_code', 'fiber_type', 'remaining_meter']);
        $clients     = Client::where('status', 'active')->orderBy('name')->get(['id', 'name', 'client_id', 'username']);
        $instrumentCategories = \App\Models\InstrumentCategory::with('children')->whereNull('parent_id')->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Recovered fiber from inactive clients (fiber_in entries with 'Recovered:' in details)
        $recoveredFibers = \Illuminate\Support\Facades\DB::table('fiber_histories')
            ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
            ->where('fiber_histories.type', 'fiber_in')
            ->where(function($q) {
                $q->where('fiber_histories.details', 'LIKE', 'Recovered:%')
                  ->orWhere('fiber_histories.note', 'LIKE', '%inactive client%')
                  ->orWhere('fiber_histories.note', 'LIKE', '%Recovered from%');
            })
            ->select(
                'fiber_histories.*',
                'fiber_stocks.fiber_code',
                'fiber_stocks.fiber_type',
                'fiber_stocks.core',
                'fiber_stocks.brand_name'
            )
            ->latest('fiber_histories.created_at')
            ->get();

        // Fiber recovery stats
        $fiberQuery = function($q) {
            $q->where('fiber_histories.type', 'fiber_in')
              ->where(function($inner) {
                  $inner->where('details', 'LIKE', 'Recovered:%')
                        ->orWhere('note', 'LIKE', '%inactive client%')
                        ->orWhere('note', 'LIKE', '%Recovered from%')
                        ->orWhere('note', 'LIKE', 'Collected by:%');
              });
        };
        $recoveredFiberCount = \DB::table('fiber_histories')->where('type', 'fiber_in')
            ->where(function($q) { $q->where('details', 'LIKE', 'Recovered:%')->orWhere('note', 'LIKE', '%inactive client%')->orWhere('note', 'LIKE', '%Collected by%'); })
            ->count();
        $recoveredFiberMeter = \DB::table('fiber_histories')->where('type', 'fiber_in')
            ->where(function($q) { $q->where('details', 'LIKE', 'Recovered:%')->orWhere('note', 'LIKE', '%inactive client%')->orWhere('note', 'LIKE', '%Collected by%'); })
            ->sum('meter');
        $productReturnedCount = ReturnedEquipment::whereNotIn('equipment_type', ['Fiber/Cable', 'Fiber', 'Cable'])->count();

        $companyId2 = auth()->user()?->company_id;
        $__gtDepts = \App\Models\Department::withoutGlobalScopes()
            ->when($companyId2, fn($q) => $q->where(function ($q) use ($companyId2) {
                $q->where('departments.company_id', $companyId2)
                  ->orWhereNull('departments.company_id');
            }))
            ->where('is_active', true)
            ->with(['employees' => function ($q) use ($companyId2) {
                $q->withoutGlobalScopes()
                  ->where('status', 'active')
                  ->when($companyId2, fn($eq) => $eq->where(function ($eq) use ($companyId2) {
                      $eq->where('employees.company_id', $companyId2)
                         ->orWhereNull('employees.company_id');
                  }))
                  ->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return view('admin.inventory.returned-equipments', compact(
            'equipments', 'typeCounts', 'type', 'status', 'search',
            'totalCount', 'pendingCount', 'restockedCount', 'assignedCount',
            'fiberStocks', 'clients', 'instrumentCategories', 'recoveredFibers',
            'recoveredFiberCount', 'recoveredFiberMeter', 'productReturnedCount', '__gtDepts'
        ));
    }

    public function show(ReturnedEquipment $returnedEquipment)
    {
        $this->checkAdminAccess();
        $returnedEquipment->load(['returnedByUser', 'receivedByUser', 'client', 'restockedByUser', 'assignedClient']);
        return view('admin.inventory.returned-equipment-detail', compact('returnedEquipment'));
    }

    public function getClientEquipment(Client $client)
    {
        $equipment = [];

        $onu = DB::table('onus')->where('client_id', $client->id)->first();
        if ($onu) {
            $equipment[] = ['type'=>'ONU','name'=>($onu->vendor??'').(' ').($onu->model??'ONU Device'),'serial'=>$onu->serial_number??$onu->mac_address??'N/A','mac'=>$onu->mac_address??'N/A','model'=>$onu->model??'N/A','brand'=>$onu->vendor??'N/A','pon_port'=>$onu->pon_port??'N/A','onu_index'=>$onu->onu_index??'N/A','source'=>'onus_table','source_id'=>$onu->id];
        }
        if ($client->onu_serial) {
            $alreadyHasOnu = collect($equipment)->where('type','ONU')->where('serial',$client->onu_serial)->isNotEmpty();
            if (!$alreadyHasOnu) {
                $equipment[] = ['type'=>'ONU','name'=>($client->onu_brand??'').(' ').($client->onu_model??'ONU'),'serial'=>$client->onu_serial,'mac'=>$client->onu_mac??'N/A','model'=>$client->onu_model??'N/A','brand'=>$client->onu_brand??'N/A','pon_port'=>$client->pon_port??'N/A','onu_index'=>'N/A','source'=>'client_record','source_id'=>$client->id];
            }
        }
        $serials = DB::table('product_serials')->where('client_id',$client->id)->leftJoin('products','product_serials.product_id','=','products.id')->select('product_serials.*','products.name as product_name','products.category_id')->get();
        foreach ($serials as $serial) {
            $equipment[] = ['type'=>'Device','name'=>$serial->product_name??'Product','serial'=>$serial->serial_number??'N/A','mac'=>$serial->mac_address??'N/A','model'=>$serial->model??'N/A','brand'=>$serial->brand??'N/A','pon_port'=>'N/A','onu_index'=>'N/A','source'=>'product_serials','source_id'=>$serial->id];
        }
        if ($client->connection_type) {
            $equipment[] = ['type'=>'Fiber/Cable','name'=>'Fiber Connection ('.ucfirst($client->connection_type).')','serial'=>'N/A','mac'=>'N/A','model'=>$client->connection_type,'brand'=>'N/A','pon_port'=>'N/A','onu_index'=>'N/A','source'=>'client_record','source_id'=>$client->id];
        }
        $equipment[] = ['type'=>'Patchcord','name'=>'Patchcord','serial'=>'N/A','mac'=>'N/A','model'=>'Standard','brand'=>'N/A','pon_port'=>'N/A','onu_index'=>'N/A','source'=>'assumed','source_id'=>null];

        return response()->json(['equipment'=>$equipment,'client'=>['id'=>$client->id,'name'=>$client->name,'client_id'=>$client->client_id,'phone'=>$client->phone]]);
    }

    public function returnEquipment(Request $request, Client $client)
    {
        $user = auth()->user();
        $isAdminOk = $user && (
            $user->user_type === "admin" ||
            $user->user_type === "super_admin" ||
            $user->hasRole("Super Admin") ||
            $user->hasRole("Admin") ||
            $user->hasRole("Administrator")
        );
        if (!$isAdminOk) {
        }
        $request->validate(['equipment'=>'required|array|min:1','equipment.*.type'=>'required|string','equipment.*.name'=>'required|string','equipment.*.condition'=>'required|in:good,damaged,faulty,unknown']);
        $returnedItems = [];
        DB::beginTransaction();
        try {
            foreach ($request->equipment as $item) {
                $returned = ReturnedEquipment::create(['client_id'=>$client->id,'client_name'=>$client->name,'client_client_id'=>$client->client_id,'equipment_type'=>$item['type'],'equipment_name'=>$item['name'],'serial_number'=>$item['serial']??null,'mac_address'=>$item['mac']??null,'model'=>$item['model']??null,'brand'=>$item['brand']??null,'pon_port'=>$item['pon_port']??null,'onu_index'=>$item['onu_index']??null,'condition'=>$item['condition'],'remarks'=>$item['remarks']??null,'returned_by'=>$user->id,'received_by'=>$user->id,'return_date'=>today(),'company_id'=>$user->company_id??1]);
                $returnedItems[] = $returned;
                if ($item['type']==='ONU' && isset($item['source']) && $item['source']==='onus_table' && isset($item['source_id'])) {
                    DB::table('onus')->where('id',$item['source_id'])->update(['client_id'=>null]);
                }
                if (isset($item['source']) && $item['source']==='product_serials' && isset($item['source_id'])) {
                    DB::table('product_serials')->where('id',$item['source_id'])->update(['client_id'=>null,'status'=>'in_stock']);
                }
            }
            DB::commit();
            ActivityLog::log('create','returned_equipments',"Returned ".count($returnedItems)." equipment(s) from client {$client->name} ({$client->client_id})");
            return response()->json(['success'=>true,'message'=>count($returnedItems).' equipment(s) returned to inventory successfully.','returned'=>$returnedItems]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error'=>'Failed to return equipment: '.$e->getMessage()],500);
        }
    }

    public function restockToInventory(Request $request, ReturnedEquipment $returnedEquipment)
    {
        $this->checkAdminAccess();

        if ($returnedEquipment->is_restocked) {
            return redirect()->back()->with('error', 'This item has already been restocked.');
        }

        $condition  = $request->input('condition', $returnedEquipment->condition ?? 'good');
        $notes      = $request->input('notes', '');
        $restock_type = null;
        $restock_ref_id = null;
        $msg = '';

        DB::beginTransaction();
        try {
            // ── FIBER / CABLE ──
            if (in_array($returnedEquipment->equipment_type, ['Fiber/Cable', 'Fiber', 'Cable'])) {
                $fiberStockId = $request->input('fiber_stock_id');
                $fiberMeter   = (float)$request->input('fiber_meter', 0);

                if (!$fiberStockId || $fiberMeter <= 0) {
                    return redirect()->back()->with('error', 'Please select a fiber stock and enter meter amount.');
                }

                $stock = FiberStock::findOrFail($fiberStockId);
                $stock->increment('remaining_meter', $fiberMeter);
                if ($stock->status === 'finished' && $stock->remaining_meter > 0) {
                    $stock->update(['status' => 'active']);
                }

                FiberHistory::create([
                    'type'           => 'fiber_in',
                    'fiber_stock_id' => $stock->id,
                    'meter'          => $fiberMeter,
                    'note'           => $notes ?: 'Restocked from returned equipment #' . $returnedEquipment->id,
                    'date'           => today(),
                    'details'        => 'Returned by client ' . $returnedEquipment->client_name . ' (' . $returnedEquipment->client_client_id . ') — Condition: ' . $condition,
                ]);

                $restock_type   = 'fiber';
                $restock_ref_id = $stock->id;
                $msg = "Fiber {$fiberMeter}m restocked to [{$stock->fiber_code}] stock. In-history updated.";

                $returnedEquipment->update([
                    'is_restocked'   => true,
                    'restocked_at'   => now(),
                    'restocked_by'   => auth()->id(),
                    'restock_notes'  => $notes,
                    'restock_type'   => $restock_type,
                    'restock_ref_id' => $restock_ref_id,
                    'fiber_stock_id' => $stock->id,
                    'fiber_meter'    => $fiberMeter,
                    'condition'      => $condition,
                ]);

            } else {
                // ── INSTRUMENT / PRODUCT ──
                $instrument = null;
                if ($returnedEquipment->serial_number && $returnedEquipment->serial_number !== 'N/A') {
                    $instrument = Instrument::withoutGlobalScopes()
                        ->whereRaw('LOWER(serial_number) = ?', [strtolower($returnedEquipment->serial_number)])
                        ->first();
                }

                // category / sub-category from restock form
                $instrCategory    = $request->input('instrument_category', '');
                $instrSubCategory = $request->input('instrument_sub_category', '');

                if ($instrument) {
                    $instrument->update([
                        'status'      => 'available',
                        'client_id'   => null,
                        'assigned_to'  => null,
                        'condition'    => $condition,
                        'category'     => $instrCategory ?: $instrument->category,
                        'sub_category' => $instrSubCategory ?: $instrument->sub_category,
                        'notes'        => ($instrument->notes ? $instrument->notes . "\n" : '') . 'Restocked on ' . now()->format('d-m-Y') . ($notes ? ': ' . $notes : '') . ' (from returned equipment #' . $returnedEquipment->id . ')',
                    ]);
                    $restock_ref_id = $instrument->id;
                    $msg = 'Equipment restocked — existing inventory record updated.';
                } else {
                    $instrument = Instrument::create([
                        'name'          => $returnedEquipment->equipment_name,
                        'category'      => $instrCategory ?: $returnedEquipment->equipment_type,
                        'sub_category'  => $instrSubCategory ?: null,
                        'brand'         => $returnedEquipment->brand,
                        'model'         => $returnedEquipment->model,
                        'serial_number' => ($returnedEquipment->serial_number !== 'N/A') ? $returnedEquipment->serial_number : null,
                        'status'        => 'available',
                        'condition'     => $condition,
                        'notes'         => 'Returned from client ' . $returnedEquipment->client_name . ' (' . $returnedEquipment->client_client_id . ') on ' . $returnedEquipment->return_date->format('d-m-Y') . ($notes ? '. ' . $notes : ''),
                        'quantity'      => 1,
                        'company_id'    => $returnedEquipment->company_id,
                    ]);
                    $restock_ref_id = $instrument->id;
                    $msg = 'Equipment added to inventory as new record.';
                }
                $restock_type = 'instrument';

                $returnedEquipment->update([
                    'is_restocked'   => true,
                    'restocked_at'   => now(),
                    'restocked_by'   => auth()->id(),
                    'restock_notes'  => $notes,
                    'restock_type'   => $restock_type,
                    'restock_ref_id' => $restock_ref_id,
                    'condition'      => $condition,
                ]);
            }

            DB::commit();
            ActivityLog::log('update', 'returned_equipments', 'Restocked "' . $returnedEquipment->equipment_name . '" back to inventory.');
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Restock failed: ' . $e->getMessage());
        }
    }

    public function assignToClient(Request $request, ReturnedEquipment $returnedEquipment)
    {
        $this->checkAdminAccess();

        $request->validate([
            'client_id' => 'required|exists:clients,id',
        ]);

        if (!$returnedEquipment->is_restocked) {
            return redirect()->back()->with('error', 'Please restock the item to inventory first before assigning.');
        }

        if ($returnedEquipment->assigned_to_client_id) {
            return redirect()->back()->with('error', 'This item has already been assigned to a client.');
        }

        $client = Client::findOrFail($request->client_id);

        DB::beginTransaction();
        try {
            // If instrument type — assign the instrument record to the client
            if ($returnedEquipment->restock_type === 'instrument' && $returnedEquipment->restock_ref_id) {
                $instrument = Instrument::withoutGlobalScopes()->find($returnedEquipment->restock_ref_id);
                if ($instrument) {
                    $instrument->update([
                        'status'      => 'in_use',
                        'client_id'   => $client->id,
                        'assigned_to' => $client->name . ' (' . $client->username . ')',
                    ]);
                }
            }

            $assignedThrough = $request->input('assigned_through', '');
            $assignNotes     = $request->input('notes', '');
            $noteText = 'Assigned to ' . $client->name . ' (' . $client->client_id . ')';
            if ($assignedThrough) $noteText .= ' — via ' . $assignedThrough;
            if ($assignNotes)     $noteText .= '. Notes: ' . $assignNotes;

            $returnedEquipment->update([
                'assigned_to_client_id' => $client->id,
                'assigned_at'           => now(),
                'restock_notes'         => ($returnedEquipment->restock_notes ? $returnedEquipment->restock_notes . ' | ' : '') . $noteText,
            ]);

            DB::commit();
            $successMsg = '"' . $returnedEquipment->equipment_name . '" assigned to client ' . $client->name . ' (' . $client->client_id . ')';
            if ($assignedThrough) $successMsg .= ' via ' . $assignedThrough;
            ActivityLog::log('update', 'returned_equipments', $successMsg);
            return redirect()->back()->with('success', $successMsg . ' successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Assignment failed: ' . $e->getMessage());
        }
    }
}
