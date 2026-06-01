<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuickSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $clients = Client::with(['zone', 'area', 'package'])
            ->where(function($q) use ($query) {
                $q->where('client_id', 'ILIKE', "%{$query}%")
                  ->orWhere('name', 'ILIKE', "%{$query}%")
                  ->orWhere('phone', 'ILIKE', "%{$query}%")
                  ->orWhere('username', 'ILIKE', "%{$query}%")
                  ->orWhere('address', 'ILIKE', "%{$query}%")
                  ->orWhereHas('zone', function($zq) use ($query) {
                      $zq->where('name', 'ILIKE', "%{$query}%");
                  })
                  ->orWhereHas('area', function($sq) use ($query) {
                      $sq->where('name', 'ILIKE', "%{$query}%");
                  });
            })
            ->limit(10)
            ->get();

        $results = $clients->map(function($client) {
            $location = $client->zone->name ?? 'N/A';
            if ($client->area) {
                $location .= ' - ' . $client->area->name;
            }
            return [
                'id' => $client->id,
                'client_id' => $client->client_id,
                'name' => $client->name,
                'phone' => $client->phone,
                'username' => $client->username,
                'address' => $client->address,
                'zone' => $client->zone->name ?? 'N/A',
                'area' => $client->area->name ?? null,
                'location' => $location,
                'package' => $client->package->name ?? 'N/A',
                'status' => $client->status,
                'connection_status' => $client->connection_status ?? 'offline',
                'url' => route('technician.clients.show', $client->id),
            ];
        });

        return response()->json($results);
    }
}
