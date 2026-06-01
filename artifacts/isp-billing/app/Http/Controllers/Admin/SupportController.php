<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Models\TicketSlaConfig;
use App\Models\TechnicianAssignment;
use App\Models\TicketFeedback;
use App\Models\User;
use App\Models\Client;
use App\Models\Department;
use App\Services\SmsService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    protected SmsService $smsService;
    protected NotificationService $notificationService;

    public function __construct(SmsService $smsService, NotificationService $notificationService)
    {
        $this->smsService = $smsService;
        $this->notificationService = $notificationService;
    }

    public function tickets(Request $request)
    {
        $query = Ticket::with(['client', 'category', 'assignedTo', 'technician']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        if ($request->sla_breached) {
            $query->where('sla_breached', true);
        }
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $tickets = $query->latest()->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 20);
        $categories = TicketCategory::where('is_active', true)->get();
        $clients = Client::where('status', 'active')->orderBy('name')->get();
        $departments = $this->getEmployeesByDepartment();
        $packages = \App\Models\Package::where('is_active', true)->orderBy('name')->get();
        
        return view('admin.support.tickets', compact('tickets', 'categories', 'clients', 'departments', 'packages'));
    }

    public function createTicket(Request $request)
    {
        $category = TicketCategory::find($request->category_id);
        $isNewConnection = $category && (
            stripos($category->name, 'new connection') !== false || 
            stripos($category->name, 'installation') !== false
        );

        if ($isNewConnection) {
            $validated = $request->validate([
                'category_id' => 'required|exists:ticket_categories,id',
                'priority' => 'required|in:low,medium,high,urgent',
                'technician_ids' => 'nullable|array',
                'technician_ids.*' => 'exists:users,id',
                'new_customer_name' => 'nullable|string|max:255',
                'new_customer_phone' => 'nullable|string|max:20',
                'new_customer_address' => 'nullable|string',
                'package_id' => 'nullable|exists:packages,id',
                'installation_charge' => 'nullable|string|max:100',
                'connection_time' => 'nullable|date',
            ]);
        } else {
            $validated = $request->validate([
                'client_id' => 'required|exists:clients,id',
                'category_id' => 'required|exists:ticket_categories,id',
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'technician_ids' => 'nullable|array',
                'technician_ids.*' => 'exists:users,id',
                'package_id' => 'nullable|exists:packages,id',
                'installation_charge' => 'nullable|string|max:100',
                'connection_time' => 'nullable|date',
            ]);
        }

        if ($isNewConnection) {
            $packageName = null;
            $packageInfo = null;
            if (!empty($validated['package_id'])) {
                $package = \App\Models\Package::find($validated['package_id']);
                $packageName = $package ? $package->name : null;
                $packageInfo = $package ? $package->name . ' - ৳' . number_format($package->price) : null;
            }
            
            $metadata = [
                'customer_name' => $validated['new_customer_name'] ?? null,
                'customer_phone' => $validated['new_customer_phone'] ?? null,
                'customer_address' => $validated['new_customer_address'] ?? null,
                'package_id' => $validated['package_id'] ?? null,
                'package' => $packageName,
                'installation_charge' => $validated['installation_charge'] ?? null,
                'connection_time' => $validated['connection_time'] ?? null,
            ];
            
            $ticket = Ticket::create([
                'client_id' => null,
                'category_id' => $validated['category_id'],
                'subject' => 'New Connection',
                'description' => 'New connection request for: ' . ($validated['new_customer_name'] ?? 'N/A'),
                'priority' => $validated['priority'],
                'status' => 'open',
                'metadata' => json_encode($metadata),
            ]);
            
            $clientName = $validated['new_customer_name'] ?? 'N/A';
            $clientPhone = $validated['new_customer_phone'] ?? 'N/A';
            $clientAddress = $validated['new_customer_address'] ?? 'N/A';
            $clientId = null;
        } else {
            $ticket = Ticket::create([
                'client_id' => $validated['client_id'],
                'category_id' => $validated['category_id'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'status' => 'open',
            ]);
            
            $client = Client::find($validated['client_id']);
            $clientName = $client->name ?? 'N/A';
            $clientPhone = $client->phone ?? 'N/A';
            $clientAddress = $client->address ?? 'N/A';
            $clientId = $client->client_id ?? 'N/A';
        }

        $ticket->setSlaDeadline();

        $technicianIds = $validated['technician_ids'] ?? [];
        foreach ($technicianIds as $techId) {
            $this->assignTechnicianToTicketMulti($ticket, $techId);
        }

        $packageName = null;
        if (!empty($validated['package_id'])) {
            $package = \App\Models\Package::find($validated['package_id']);
            $packageName = $package ? $package->name . ' - ৳' . number_format($package->price) : null;
        }

        $ticketData = [
            'ticket_no' => $ticket->ticket_no,
            'client_name' => $clientName,
            'user_id' => $clientId,
            'pppoe_user' => isset($client) ? ($client->username ?? '') : '',
            'subject' => $isNewConnection ? 'New Connection' : $validated['subject'],
            'description' => $isNewConnection ? '' : ($validated['description'] ?? ''),
            'address' => $clientAddress,
            'phone' => $clientPhone,
            'priority' => $validated['priority'],
            'opened_by' => auth()->user()->name ?? 'System',
            'installation_charge' => $validated['installation_charge'] ?? 0,
            'package' => $packageName,
            'connection_time' => !empty($validated['connection_time']) 
                ? \Carbon\Carbon::parse($validated['connection_time'])->timezone('Asia/Dhaka')->format('d-m-y h:i A') 
                : null,
        ];

        if ($isNewConnection) {
            $this->notificationService->sendNewConnectionTicketNotification($technicianIds, $ticketData);
        } else {
            $this->notificationService->sendMultiTechnicianTicketNotification($technicianIds, $ticketData);
            if (isset($client)) {
                $this->smsService->sendTicketUpdate($client, $ticket->ticket_no, 'created');
            }
        }

        return redirect()->back()->with('success', 'Ticket created successfully.');
    }

    public function showTicket(Ticket $ticket)
    {
        $ticket->load(['client', 'category', 'assignedTo', 'technician', 'messages.user', 'technicianAssignments.technician', 'feedback']);
        $staff = User::where('user_type', 'admin')->orWhere('user_type', 'employee')->get();
        $departments = $this->getEmployeesByDepartment();
        return view('admin.support.ticket-show', compact('ticket', 'staff', 'departments'));
    }

    public function editTicket(Ticket $ticket)
    {
        $ticket->load(['client', 'category']);
        $categories = TicketCategory::where('is_active', true)->get();
        $clients = Client::where('status', 'active')->orderBy('name')->get();
        return view('admin.support.ticket-edit', compact('ticket', 'categories', 'clients'));
    }

    public function updateTicket(Request $request, Ticket $ticket)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,in_progress,resolved,closed',
            'category_id' => 'required|exists:ticket_categories,id',
        ]);

        $oldStatus = $ticket->status;
        
        $ticket->update([
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->status,
            'category_id' => $request->category_id,
        ]);

        if ($request->status === 'resolved' && $oldStatus !== 'resolved') {
            $ticket->resolved_at = now();
            $ticket->save();
        } elseif ($request->status === 'closed' && $oldStatus !== 'closed') {
            $ticket->closed_at = now();
            $ticket->save();
        }

        return redirect()->route('admin.support.tickets.show', $ticket)->with('success', 'Ticket updated successfully.');
    }

    public function destroyTicket(Ticket $ticket)
    {
        \DB::transaction(function () use ($ticket) {
            $ticket->messages()->delete();
            $ticket->technicianAssignments()->delete();
            $ticket->feedback()->delete();
            $ticket->delete();
        });
        return redirect()->route('admin.support.tickets')->with('success', 'Ticket deleted successfully.');
    }

    public function replyTicket(Request $request, Ticket $ticket)
    {
        $request->validate(['message' => 'required|string']);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_staff_reply' => true,
        ]);

        if ($request->status && $request->status !== $ticket->status) {
            $ticket->status = $request->status;
            if ($request->status === 'resolved') {
                $ticket->resolved_at = now();
            } elseif ($request->status === 'closed') {
                $ticket->closed_at = now();
            }
            $ticket->save();
            
            if ($ticket->client) {
                $this->smsService->sendTicketUpdate($ticket->client, $ticket->ticket_no, $request->status);
            }
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function assignTechnician(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
            'notes' => 'nullable|string'
        ]);

        TechnicianAssignment::where('ticket_id', $ticket->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->update(['status' => 'removed']);

        $newTechIds = [];
        foreach ($validated['technician_ids'] as $techId) {
            $this->assignTechnicianToTicketMulti($ticket, $techId, $validated['notes'] ?? null);
            $newTechIds[] = $techId;
        }

        $client = $ticket->client;
        $ticketData = [
            'ticket_no' => $ticket->ticket_no,
            'client_name' => $client->name ?? 'N/A',
            'user_id' => $client->client_id ?? 'N/A',
            'pppoe_user' => $client->username ?? '',
            'subject' => $ticket->subject,
            'description' => $ticket->description ?? '',
            'address' => $client->address ?? 'N/A',
            'phone' => $client->phone ?? 'N/A',
            'priority' => $ticket->priority,
            'opened_by' => auth()->user()->name ?? 'System',
        ];
        $this->notificationService->sendMultiTechnicianTicketNotification($newTechIds, $ticketData);

        if ($ticket->client) {
            $this->smsService->sendTicketUpdate($ticket->client, $ticket->ticket_no, 'assigned to technician');
        }

        return redirect()->back()->with('success', 'Technicians assigned successfully.');
    }

    protected function assignTechnicianToTicketMulti(Ticket $ticket, int $technicianId, ?string $notes = null): void
    {
        $exists = TechnicianAssignment::where('ticket_id', $ticket->id)
            ->where('technician_id', $technicianId)
            ->where('status', 'assigned')
            ->exists();

        if (!$exists) {
            TechnicianAssignment::create([
                'ticket_id' => $ticket->id,
                'technician_id' => $technicianId,
                'assigned_at' => now(),
                'status' => 'assigned',
                'notes' => $notes
            ]);
        }

        if (!$ticket->technician_id) {
            $ticket->technician_id = $technicianId;
        }
        $ticket->status = 'in_progress';
        $ticket->save();
    }

    protected function assignTechnicianToTicket(Ticket $ticket, int $technicianId, ?string $notes = null): void
    {
        if ($ticket->technician_id) {
            TechnicianAssignment::where('ticket_id', $ticket->id)
                ->where('status', 'assigned')
                ->update(['status' => 'reassigned']);
        }

        TechnicianAssignment::create([
            'ticket_id' => $ticket->id,
            'technician_id' => $technicianId,
            'assigned_at' => now(),
            'status' => 'assigned',
            'notes' => $notes
        ]);

        $ticket->technician_id = $technicianId;
        $ticket->status = 'in_progress';
        $ticket->save();

        $client = $ticket->client;
        $openedBy = auth()->user()->name ?? 'System';
        $this->notificationService->sendTicketAssignmentNotification($technicianId, [
            'ticket_no' => $ticket->ticket_no,
            'user_id' => $client->username ?? 'N/A',
            'client_name' => $client->name ?? 'Unknown',
            'subject' => $ticket->subject,
            'priority' => $ticket->priority,
            'address' => $client->address ?? 'N/A',
            'phone' => $client->phone ?? 'N/A',
            'opened_by' => $openedBy,
        ]);
    }

    public function startWork(Ticket $ticket)
    {
        $assignment = TechnicianAssignment::where('ticket_id', $ticket->id)
            ->where('technician_id', auth()->id())
            ->where('status', 'assigned')
            ->first();

        if ($assignment) {
            $assignment->started_at = now();
            $assignment->status = 'in_progress';
            $assignment->save();
        }

        return redirect()->back()->with('success', 'Work started on ticket.');
    }

    public function resolve(Request $request, Ticket $ticket)
    {
        $ticket->status = 'resolved';
        $ticket->resolved_at = now();
        $ticket->save();

        $assignment = TechnicianAssignment::where('ticket_id', $ticket->id)
            ->where('status', 'in_progress')
            ->first();

        if ($assignment) {
            $assignment->completed_at = now();
            $assignment->status = 'completed';
            $assignment->save();
        }

        if ($ticket->client) {
            $this->smsService->sendTicketUpdate($ticket->client, $ticket->ticket_no, 'resolved');
        }

        return redirect()->back()->with('success', 'Ticket marked as resolved.');
    }

    public function reopen(Ticket $ticket)
    {
        $ticket->reopen();
        if ($ticket->client) {
            $this->smsService->sendTicketUpdate($ticket->client, $ticket->ticket_no, 'reopened');
        }
        return redirect()->back()->with('success', 'Ticket reopened.');
    }

    public function close(Ticket $ticket)
    {
        $ticket->status = 'closed';
        $ticket->closed_at = now();
        $ticket->save();

        if ($ticket->client) {
            $this->smsService->sendTicketUpdate($ticket->client, $ticket->ticket_no, 'closed');
        }

        return redirect()->back()->with('success', 'Ticket closed.');
    }

    public function submitFeedback(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        TicketFeedback::updateOrCreate(
            ['ticket_id' => $ticket->id],
            $validated
        );

        $ticket->satisfaction_rating = $validated['rating'];
        $ticket->satisfaction_feedback = $validated['comment'];
        $ticket->save();

        return redirect()->back()->with('success', 'Feedback submitted.');
    }

    public function categories()
    {
        $categories = TicketCategory::orderBy('name')->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 20);
        return view('admin.support.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subjects' => 'nullable|array',
            'subjects.*' => 'nullable|string|max:255',
        ]);

        $subjects = null;
        if ($request->subjects) {
            $subjects = array_values(array_filter(array_map('trim', $request->subjects)));
            if (empty($subjects)) $subjects = null;
        }

        TicketCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'subjects' => $subjects,
            'priority' => $request->priority ?? 0,
            'is_active' => true,
        ]);
        return back()->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, TicketCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subjects' => 'nullable|array',
            'subjects.*' => 'nullable|string|max:255',
        ]);

        $subjects = null;
        if ($request->subjects) {
            $subjects = array_values(array_filter(array_map('trim', $request->subjects)));
            if (empty($subjects)) $subjects = null;
        }

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'subjects' => $subjects,
        ]);
        return back()->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(TicketCategory $category)
    {
        // Prevent deletion if tickets exist under this category
        if ($category->tickets()->count() > 0) {
            return back()->with('error', 'Cannot delete category — it has associated tickets.');
        }
        $name = $category->name;
        $category->delete();
        return back()->with('success', 'Category deleted successfully.');
    }

    public function getCategorySubjects(TicketCategory $category)
    {
        return response()->json([
            'subjects' => $category->subjects ?? [],
        ]);
    }

    public function slaConfig()
    {
        $configs = TicketSlaConfig::all();
        return view('admin.support.sla-config', compact('configs'));
    }

    public function saveSlaConfig(Request $request)
    {
        $validated = $request->validate([
            'configs' => 'required|array',
            'configs.*.priority' => 'required|string',
            'configs.*.response_hours' => 'required|integer|min:1',
            'configs.*.resolution_hours' => 'required|integer|min:1'
        ]);

        foreach ($validated['configs'] as $config) {
            TicketSlaConfig::updateOrCreate(
                ['priority' => $config['priority']],
                [
                    'response_hours' => $config['response_hours'],
                    'resolution_hours' => $config['resolution_hours'],
                    'is_active' => true
                ]
            );
        }

        return redirect()->back()->with('success', 'SLA configuration saved.');
    }

    public function technicianEfficiency()
    {
        $technicians = User::where(function($q) {
            $q->whereIn('user_type', ['technician', 'employee'])
              ->orWhereHas('roles', function($r) {
                  $r->whereIn('name', ['technician', 'employee']);
              });
        })->where('is_active', true)
            ->withCount([
                'technicianAssignments as total_assigned',
                'technicianAssignments as completed' => function ($q) {
                    $q->where('status', 'completed');
                }
            ])
            ->get()
            ->map(function ($tech) {
                $avgTime = TechnicianAssignment::where('technician_id', $tech->id)
                    ->where('status', 'completed')
                    ->whereNotNull('started_at')
                    ->whereNotNull('completed_at')
                    ->get()
                    ->avg(function ($a) {
                        return $a->started_at->diffInMinutes($a->completed_at);
                    });

                $tech->avg_resolution_minutes = round($avgTime ?? 0);
                $tech->efficiency_rate = $tech->total_assigned > 0
                    ? round(($tech->completed / $tech->total_assigned) * 100, 1)
                    : 0;

                return $tech;
            });

        return view('admin.support.technician-efficiency', compact('technicians'));
    }

    private function getEmployeesByDepartment()
    {
        $companyId = auth()->user()?->company_id;

        return Department::withoutGlobalScopes()
            ->when($companyId, fn($q) => $q->where(function ($q) use ($companyId) {
                $q->where('departments.company_id', $companyId)
                  ->orWhereNull('departments.company_id');
            }))
            ->where('is_active', true)
            ->with(['employees' => function ($q) use ($companyId) {
                $q->withoutGlobalScopes()
                  ->whereNotNull('user_id')
                  ->where('status', 'active')
                  ->when($companyId, fn($eq) => $eq->where(function ($eq) use ($companyId) {
                      $eq->where('employees.company_id', $companyId)
                         ->orWhereNull('employees.company_id');
                  }))
                  ->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
    }


    public function newTicketsPoll(\Illuminate\Http\Request $request)
    {
        $since = $request->query('since');
        $query = Ticket::with(['client'])->orderBy('created_at', 'desc');
        if ($since) {
            $sinceTime = \Carbon\Carbon::createFromTimestampMs((int)$since);
            $query->where('created_at', '>', $sinceTime);
        } else {
            $query->where('created_at', '>', now()->subSeconds(35));
        }
        $tickets = $query->get()->map(function ($t) {
            return [
                'id'         => $t->id,
                'ticket_no'  => $t->ticket_no,
                'subject'    => $t->subject,
                'priority'   => $t->priority,
                'client'     => optional($t->client)->name ?? 'Unknown',
                'url'        => route('admin.support.tickets.show', $t->id),
                'created_at' => $t->created_at->toIso8601String(),
            ];
        });
        return response()->json([
            'tickets'     => $tickets,
            'server_time' => now()->getTimestampMs(),
        ]);
    }
}
