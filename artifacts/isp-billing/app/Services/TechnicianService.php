<?php

namespace App\Services;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TechnicianService
{
    public function getTodayWorkPriorityList(int $technicianId): array
    {
        $jobs = DB::table('technician_jobs')
            ->where('technician_id', $technicianId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('created_at')
            ->get();

        $tickets = Ticket::whereHas('assignee', fn($q) => $q->where('users.id', $technicianId))
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->with('client')
            ->get();

        $priorityList = [];

        $losTickets = $tickets->filter(fn($t) => 
            stripos($t->subject, 'los') !== false || 
            stripos($t->subject, 'fiber cut') !== false ||
            stripos($t->subject, 'no internet') !== false
        );

        foreach ($losTickets as $ticket) {
            $priorityList[] = [
                'type' => 'ticket',
                'id' => $ticket->id,
                'priority' => 'urgent',
                'title' => $ticket->subject,
                'client' => $ticket->client?->name,
                'address' => $ticket->client?->address,
                'is_los' => true,
            ];
        }

        foreach ($jobs as $job) {
            $client = DB::table('clients')->find($job->client_id);
            $priorityList[] = [
                'type' => 'job',
                'id' => $job->id,
                'priority' => $job->priority,
                'title' => $job->job_type,
                'description' => $job->description,
                'client' => $client?->name,
                'address' => $client?->address,
                'is_repeat' => $this->isRepeatIssue($job->client_id),
            ];
        }

        foreach ($tickets->reject(fn($t) => $losTickets->contains($t)) as $ticket) {
            $priorityList[] = [
                'type' => 'ticket',
                'id' => $ticket->id,
                'priority' => $ticket->priority,
                'title' => $ticket->subject,
                'client' => $ticket->client?->name,
                'address' => $ticket->client?->address,
                'is_los' => false,
            ];
        }

        return $priorityList;
    }

    public function isRepeatIssue(int $clientId): bool
    {
        $recentTickets = Ticket::where('client_id', $clientId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return $recentTickets >= 3;
    }

    public function startShift(int $technicianId): array
    {
        DB::table('technician_shifts')->updateOrInsert(
            ['technician_id' => $technicianId, 'shift_date' => today()],
            [
                'clock_in' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return ['status' => 'clocked_in', 'time' => now()->format('H:i:s')];
    }

    public function endShift(int $technicianId): array
    {
        $shift = DB::table('technician_shifts')
            ->where('technician_id', $technicianId)
            ->where('shift_date', today())
            ->first();

        if (!$shift || !$shift->clock_in) {
            return ['error' => 'No active shift found'];
        }

        $clockIn = \Carbon\Carbon::parse($shift->clock_in);
        $totalMinutes = $clockIn->diffInMinutes(now());

        DB::table('technician_shifts')
            ->where('id', $shift->id)
            ->update([
                'clock_out' => now(),
                'total_minutes' => $totalMinutes,
                'updated_at' => now(),
            ]);

        return [
            'status' => 'clocked_out',
            'time' => now()->format('H:i:s'),
            'total_hours' => round($totalMinutes / 60, 2),
        ];
    }

    public function completeJob(int $jobId, array $data): array
    {
        $job = DB::table('technician_jobs')->find($jobId);
        if (!$job) {
            return ['error' => 'Job not found'];
        }

        $responseTime = $job->started_at 
            ? \Carbon\Carbon::parse($job->assigned_at)->diffInMinutes($job->started_at)
            : null;

        DB::table('technician_jobs')
            ->where('id', $jobId)
            ->update([
                'status' => 'completed',
                'completion_notes' => $data['notes'] ?? null,
                'checklist' => json_encode($data['checklist'] ?? []),
                'completed_at' => now(),
                'response_time_minutes' => $responseTime,
                'updated_at' => now(),
            ]);

        if (!empty($data['photos'])) {
            foreach ($data['photos'] as $photo) {
                DB::table('job_photos')->insert([
                    'technician_job_id' => $jobId,
                    'photo_type' => $photo['type'] ?? 'after',
                    'file_path' => $photo['path'],
                    'notes' => $photo['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->updatePerformanceScore($job->technician_id);

        return ['status' => 'completed', 'job_id' => $jobId];
    }

    public function getPerformanceScore(int $technicianId): array
    {
        $user = User::find($technicianId);
        if (!$user) {
            return ['error' => 'Technician not found'];
        }

        $completedJobs = DB::table('technician_jobs')
            ->where('technician_id', $technicianId)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(30))
            ->get();

        $avgResponseTime = $completedJobs->avg('response_time_minutes') ?? 0;

        $ratings = DB::table('service_ratings')
            ->where('technician_id', $technicianId)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $avgRating = $ratings->avg('rating') ?? 5;

        $collections = DB::table('payments')
            ->where('collected_by', $technicianId)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        $responseScore = max(0, 100 - ($avgResponseTime / 2));
        $ratingScore = ($avgRating / 5) * 100;
        $productivityScore = min(100, $completedJobs->count() * 5);

        $overallScore = ($responseScore * 0.3) + ($ratingScore * 0.4) + ($productivityScore * 0.3);

        return [
            'technician_id' => $technicianId,
            'name' => $user->name,
            'overall_score' => round($overallScore),
            'jobs_completed' => $completedJobs->count(),
            'avg_response_minutes' => round($avgResponseTime),
            'avg_rating' => round($avgRating, 1),
            'total_collections' => $collections,
            'breakdown' => [
                'response_score' => round($responseScore),
                'rating_score' => round($ratingScore),
                'productivity_score' => round($productivityScore),
            ],
        ];
    }

    public function updatePerformanceScore(int $technicianId): void
    {
        $score = $this->getPerformanceScore($technicianId);
        
        User::where('id', $technicianId)->update([
            'performance_score' => $score['overall_score'] ?? 0,
        ]);
    }

    public function getAssignedInventory(int $technicianId): array
    {
        return DB::table('device_inventory')
            ->where('assigned_to', $technicianId)
            ->whereIn('status', ['assigned', 'available'])
            ->get()
            ->toArray();
    }

    public function getNearestIssues(int $technicianId, float $lat, float $lng): array
    {
        $tickets = Ticket::whereIn('status', ['open', 'in_progress'])
            ->with('client')
            ->get()
            ->filter(fn($t) => $t->client && $t->client->latitude && $t->client->longitude)
            ->map(function ($ticket) use ($lat, $lng) {
                $distance = $this->calculateDistance(
                    $lat, $lng,
                    $ticket->client->latitude,
                    $ticket->client->longitude
                );
                return [
                    'ticket_id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'client' => $ticket->client->name,
                    'address' => $ticket->client->address,
                    'distance_km' => round($distance, 2),
                ];
            })
            ->sortBy('distance_km')
            ->values()
            ->take(10)
            ->toArray();

        return $tickets;
    }

    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    public function getTechnicianOverloadAlerts(): array
    {
        $technicians = User::role('technician')->get();
        $alerts = [];

        foreach ($technicians as $tech) {
            $pendingJobs = DB::table('technician_jobs')
                ->where('technician_id', $tech->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            $pendingTickets = Ticket::whereHas('assignee', fn($q) => $q->where('users.id', $tech->id))
                ->whereIn('status', ['open', 'in_progress'])
                ->count();

            $totalLoad = $pendingJobs + $pendingTickets;

            if ($totalLoad >= 10) {
                $alerts[] = [
                    'technician_id' => $tech->id,
                    'name' => $tech->name,
                    'pending_jobs' => $pendingJobs,
                    'pending_tickets' => $pendingTickets,
                    'total_load' => $totalLoad,
                    'severity' => $totalLoad >= 15 ? 'critical' : 'warning',
                ];
            }
        }

        return $alerts;
    }
}
