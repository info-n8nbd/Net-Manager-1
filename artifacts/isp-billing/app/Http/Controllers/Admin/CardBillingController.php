<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardDistributor;
use App\Models\Client;
use App\Models\Package;
use App\Models\ScratchCard;
use App\Models\ScratchCardBatch;
use App\Services\CardBillingService;
use Illuminate\Http\Request;

class CardBillingController extends Controller
{
    protected CardBillingService $cardBillingService;

    public function __construct(CardBillingService $cardBillingService)
    {
        $this->cardBillingService = $cardBillingService;
    }

    public function batches()
    {
        $batches = ScratchCardBatch::with(['package', 'creator'])
            ->withCount('cards')
            ->latest()
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.card-billing.batches', compact('batches'));
    }

    public function createBatch()
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get();
        return view('admin.card-billing.create-batch', compact('packages'));
    }

    public function storeBatch(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'total_cards' => 'required|integer|min:1|max:10000',
            'validity_days' => 'required|integer|min:1|max:365',
            'package_id' => 'nullable|exists:packages,id',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $batch = $this->cardBillingService->createBatch($request->only([
            'name', 'amount', 'total_cards', 'validity_days', 'package_id', 'expires_at',
        ]));

        return redirect()->route('admin.card-billing.batches.show', $batch)
            ->with('success', "Batch '{$batch->batch_no}' created with {$batch->total_cards} cards.");
    }

    public function showBatch(ScratchCardBatch $batch)
    {
        $batch->load(['package', 'creator']);
        $cards = $batch->cards()->with(['distributor', 'usedByClient'])->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);
        $stats = $this->cardBillingService->getBatchStats($batch);

        return view('admin.card-billing.show-batch', compact('batch', 'cards', 'stats'));
    }

    public function cards(Request $request)
    {
        $query = ScratchCard::with(['batch', 'distributor', 'usedByClient', 'package']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('card_number', 'ILIKE', "%{$search}%")
                  ->orWhere('serial_number', 'ILIKE', "%{$search}%");
            });
        }

        $cards = $query->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);
        $batches = ScratchCardBatch::orderBy('batch_no', 'desc')->get();

        return view('admin.card-billing.cards', compact('cards', 'batches'));
    }

    public function redeemForm()
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get();
        return view('admin.card-billing.redeem', compact('clients'));
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'card_number' => 'required|string',
            'pin' => 'required|string',
            'client_id' => 'required|exists:clients,id',
        ]);

        $client = Client::findOrFail($request->client_id);
        $result = $this->cardBillingService->redeemCard($request->card_number, $request->pin, $client);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['error'])->withInput();
    }

    public function distributors()
    {
        $distributors = CardDistributor::withCount('cards')
            ->orderBy('name')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.card-billing.distributors', compact('distributors'));
    }

    public function storeDistributor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        CardDistributor::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'commission_rate' => $request->commission_rate,
            'is_active' => true,
        ]);

        return back()->with('success', 'Distributor created successfully.');
    }

    public function updateDistributor(Request $request, CardDistributor $distributor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $distributor->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'commission_rate' => $request->commission_rate,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Distributor updated successfully.');
    }

    public function destroyDistributor(CardDistributor $distributor)
    {
        $distributor->delete();
        return back()->with('success', 'Distributor deleted successfully.');
    }

    public function distributeCards(Request $request)
    {
        $request->validate([
            'distributor_id' => 'required|exists:card_distributors,id',
            'card_ids' => 'required|array|min:1',
            'card_ids.*' => 'exists:scratch_cards,id',
        ]);

        $count = $this->cardBillingService->distributeCards($request->card_ids, $request->distributor_id);

        return back()->with('success', "{$count} cards distributed successfully.");
    }

    public function disableCard(ScratchCard $card)
    {
        if ($card->status !== 'active') {
            return back()->with('error', 'Only active cards can be disabled.');
        }

        $this->cardBillingService->disableCard($card, 'Disabled by admin');

        return back()->with('success', 'Card has been disabled.');
    }

    public function destroyBatch($batchId)
    {
        $batch = ScratchCardBatch::findOrFail($batchId);
        ScratchCard::where('batch_id', $batch->id)->delete();
        $batch->delete();
        return redirect()->route('admin.card-billing.batches')->with('success', 'Batch and all its cards deleted successfully.');
    }
}
