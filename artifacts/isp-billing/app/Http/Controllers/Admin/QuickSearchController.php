<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class QuickSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $clients = Client::with(['zone', 'package'])
            ->where(function($q) use ($query) {
                $q->where('client_id', 'ILIKE', "%{$query}%")
                  ->orWhere('name', 'ILIKE', "%{$query}%")
                  ->orWhere('phone', 'ILIKE', "%{$query}%")
                  ->orWhere('username', 'ILIKE', "%{$query}%")
                  ->orWhere('address', 'ILIKE', "%{$query}%")
                  ->orWhere('nid', 'ILIKE', "%{$query}%")
                  ->orWhereHas('zone', function($zq) use ($query) {
                      $zq->where('name', 'ILIKE', "%{$query}%");
                  });
            })
            ->limit(10)
            ->get();

        $results = $clients->map(function($client) {
            return [
                'id' => $client->id,
                'client_id' => $client->client_id,
                'name' => $client->name,
                'phone' => $client->phone,
                'username' => $client->username,
                'zone' => $client->zone->name ?? 'N/A',
                'package' => $client->package->name ?? 'N/A',
                'status' => $client->status,
                'connection_status' => $client->connection_status ?? 'offline',
                'url' => route('admin.clients.show', $client->id),
            ];
        });

        return response()->json($results);
    }
}
