<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OltDevice;
use App\Models\Splitter;
use App\Models\Client;
use App\Models\Zone;
use App\Models\Area;
use Illuminate\Http\Request;

class NetworkTopologyController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;
        $olts  = OltDevice::where('is_active', true)->get();
        $zones = Zone::where('company_id', $companyId)
                    ->whereNull('reseller_id')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']);
        $areas = Area::where('company_id', $companyId)
                    ->whereNull('reseller_id')
                    ->where('is_active', true)
                    ->orderBy('zone_id')
                    ->orderBy('name')
                    ->get(['id', 'name', 'zone_id']);
        return view('admin.network.topology', compact('olts', 'zones', 'areas'));
    }

    public function getHierarchy(Request $request)
    {
        $oltId  = $request->get('olt_id');
        $zoneId = $request->get('zone_id');
        $areaId = $request->get('area_id');

        $olts = OltDevice::where('is_active', true)
            ->when($oltId, fn($q) => $q->where('id', $oltId))
            ->get();

        $data = [
            'name'     => 'Network',
            'type'     => 'root',
            'children' => []
        ];

        foreach ($olts as $olt) {
            $oltNode = [
                'id'       => 'olt_' . $olt->id,
                'name'     => $olt->name,
                'type'     => 'olt',
                'ip'       => $olt->ip_address,
                'vendor'   => $olt->vendor,
                'ports'    => $olt->total_ports,
                'children' => $this->getSplitterHierarchy($olt->id, null, $zoneId, $areaId)
            ];

            $directClients = Client::where('olt_device_id', $olt->id)
                ->whereNull('splitter_id')
                ->where('status', '!=', 'deleted')
                ->whereNull('deleted_at')
                ->when($zoneId, fn($q) => $q->where('zone_id', $zoneId))
                ->when($areaId, fn($q) => $q->where('area_id', $areaId))
                ->get();

            foreach ($directClients as $client) {
                $oltNode['children'][] = $this->formatClientNode($client);
            }

            // Only include OLT node if it has children (when filtering by zone/area)
            if (($zoneId || $areaId) && empty($oltNode['children'])) {
                continue;
            }

            $data['children'][] = $oltNode;
        }

        // Unassigned splitters (null olt_device_id) at root level
        $unassignedSplitters = Splitter::whereNull('olt_device_id')
            ->whereNull('parent_splitter_id')
            ->where('is_active', true)
            ->get();

        foreach ($unassignedSplitters as $splitter) {
            $splitterNode = $this->buildSplitterNode($splitter, null, $zoneId, $areaId);
            if (($zoneId || $areaId) && empty($splitterNode['children'])) {
                continue;
            }
            $data['children'][] = $splitterNode;
        }

        return response()->json($data);
    }

    private function getSplitterHierarchy($oltId, $parentId, $zoneId = null, $areaId = null)
    {
        $splitters = Splitter::where('olt_device_id', $oltId)
            ->where('parent_splitter_id', $parentId)
            ->where('is_active', true)
            ->get();

        $nodes = [];
        foreach ($splitters as $splitter) {
            $node = $this->buildSplitterNode($splitter, $oltId, $zoneId, $areaId);
            if (($zoneId || $areaId) && empty($node['children'])) {
                continue;
            }
            $nodes[] = $node;
        }
        return $nodes;
    }

    private function buildSplitterNode($splitter, $oltId = null, $zoneId = null, $areaId = null)
    {
        $node = [
            'id'            => 'splitter_' . $splitter->id,
            'name'          => $splitter->name,
            'type'          => 'splitter',
            'splitter_type' => $splitter->type,
            'location'      => $splitter->location,
            'port_number'   => $splitter->port_number,
            'children'      => []
        ];

        // Sub-splitters
        $children = Splitter::where('parent_splitter_id', $splitter->id)
            ->where('is_active', true)
            ->get();
        foreach ($children as $child) {
            $childNode = $this->buildSplitterNode($child, $oltId, $zoneId, $areaId);
            if (($zoneId || $areaId) && empty($childNode['children'])) {
                continue;
            }
            $node['children'][] = $childNode;
        }

        // Clients under this splitter
        $clients = Client::where('splitter_id', $splitter->id)
            ->where('status', '!=', 'deleted')
            ->whereNull('deleted_at')
            ->when($zoneId, fn($q) => $q->where('zone_id', $zoneId))
            ->when($areaId, fn($q) => $q->where('area_id', $areaId))
            ->get();

        foreach ($clients as $client) {
            $node['children'][] = $this->formatClientNode($client);
        }

        return $node;
    }

    private function formatClientNode($client)
    {
        return [
            'id'         => 'client_' . $client->id,
            'name'       => $client->name,
            'type'       => 'client',
            'client_id'  => $client->client_id,
            'status'     => $client->status,
            'package'    => $client->package?->name,
            'zone'       => $client->zone?->name,
            'area'       => $client->area?->name,
            'onu_serial' => $client->onu_serial,
            'phone'      => $client->phone
        ];
    }

    public function splitters()
    {
        $splitters = Splitter::with(['oltDevice', 'parentSplitter'])
            ->orderBy('olt_device_id')
            ->orderBy('name')
            ->paginate(in_array((int)request()->per_page, [20, 50, 100, 200]) ? (int)request()->per_page : 20);
        $olts         = OltDevice::where('is_active', true)->get();
        $allSplitters = Splitter::where('is_active', true)->get();

        return view('admin.network.splitters', compact('splitters', 'olts', 'allSplitters'));
    }

    public function storeSplitter(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'type'               => 'required|in:1:2,1:4,1:8,1:16,1:32,1:64',
            'olt_device_id'      => 'nullable|exists:olt_devices,id',
            'parent_splitter_id' => 'nullable|exists:splitters,id',
            'port_number'        => 'nullable|integer|min:1',
            'location'           => 'nullable|string|max:500',
            'notes'              => 'nullable|string'
        ]);

        Splitter::create($validated);

        return back()->with('success', 'Splitter added successfully.');
    }

    public function updateSplitter(Request $request, Splitter $splitter)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'type'               => 'required|in:1:2,1:4,1:8,1:16,1:32,1:64',
            'olt_device_id'      => 'nullable|exists:olt_devices,id',
            'parent_splitter_id' => 'nullable|exists:splitters,id',
            'port_number'        => 'nullable|integer|min:1',
            'location'           => 'nullable|string|max:500',
            'notes'              => 'nullable|string'
        ]);

        if (isset($validated['parent_splitter_id']) && $validated['parent_splitter_id'] == $splitter->id) {
            return back()->with('error', 'Splitter cannot be its own parent.');
        }

        $splitter->update($validated);

        return back()->with('success', 'Splitter updated successfully.');
    }

    public function destroySplitter(Splitter $splitter)
    {
        if ($splitter->childSplitters()->count() > 0) {
            return back()->with('error', 'Cannot delete splitter with connected sub-splitters.');
        }

        if ($splitter->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete splitter with connected clients.');
        }

        $splitter->delete();

        return back()->with('success', 'Splitter deleted successfully.');
    }
}
