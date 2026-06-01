<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Package;
use App\Models\MinuteSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientApiController extends Controller
{
    public function profile()
    {
        $client = Client::where('user_id', Auth::id())
            ->with('package')
            ->first();

        if (!$client) {
            return response()->json(['error' => 'Client profile not found'], 404);
        }

        return response()->json([
            'id' => $client->id,
            'name' => $client->name,
            'username' => $client->username,
            'phone' => $client->phone,
            'status' => $client->status,
            'package' => $client->package ? [
                'name' => $client->package->name,
                'speed' => $client->package->speed ?? null,
                'price' => $client->package->price,
            ] : null,
            'connection_type' => $client->connection_type,
            'due_amount' => $client->due_amount ?? 0,
            'expire_date' => $client->expire_date,
        ]);
    }

    public function bills()
    {
        $client = Client::where('user_id', Auth::id())->first();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        $bills = Bill::where('client_id', $client->id)
            ->select('id', 'amount', 'due_date', 'status', 'created_at')
            ->latest()
            ->paginate(20);

        return response()->json($bills);
    }

    public function payments()
    {
        $client = Client::where('user_id', Auth::id())->first();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        $payments = Payment::where('client_id', $client->id)
            ->select('id', 'amount', 'payment_date', 'notes')
            ->latest('payment_date')
            ->paginate(20);

        return response()->json($payments);
    }

    public function packages()
    {
        $packages = Package::where('is_active', true)
            ->select('id', 'name', 'price', 'speed', 'description')
            ->get();

        return response()->json($packages);
    }

    public function minuteBalance()
    {
        $client = Client::where('user_id', Auth::id())->first();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        $activeSales = MinuteSale::where('client_id', $client->id)
            ->where('status', 'active')
            ->where('remaining_minutes', '>', 0)
            ->get(['id', 'total_minutes', 'used_minutes', 'remaining_minutes', 'purchased_at', 'expires_at']);

        return response()->json([
            'total_remaining_minutes' => $activeSales->sum('remaining_minutes'),
            'packages' => $activeSales,
        ]);
    }

    public function status()
    {
        $client = Client::where('user_id', Auth::id())->first();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        return response()->json([
            'status' => $client->status,
            'connection_type' => $client->connection_type,
            'expire_date' => $client->expire_date,
            'due_amount' => $client->due_amount ?? 0,
            'is_online' => $client->is_online ?? false,
        ]);
    }
}
