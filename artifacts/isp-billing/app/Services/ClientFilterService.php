<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientFilterService
{
    /**
     * Apply Zone/Subzone/TJ filtering to client query.
     * 
     * Priority: TJ (box_id) > Subzone (area_id) > Zone (zone_id)
     * 
     * @param Builder $query The client query builder
     * @param array|Request $filters Filter parameters (zone_id, area_id, box_id)
     * @return Builder
     */
    public static function applyLocationFilters(Builder $query, $filters): Builder
    {
        $zoneId = null;
        $areaId = null;
        $boxId = null;

        if ($filters instanceof Request) {
            $zoneId = $filters->input('zone_id');
            $areaId = $filters->input('area_id') ?? $filters->input('subzone_id');
            $boxId = $filters->input('box_id') ?? $filters->input('tj_id');
        } elseif (is_array($filters)) {
            $zoneId = $filters['zone_id'] ?? null;
            $areaId = $filters['area_id'] ?? $filters['subzone_id'] ?? null;
            $boxId = $filters['box_id'] ?? $filters['tj_id'] ?? null;
        }

        // Priority: TJ > Subzone > Zone
        if (!empty($boxId)) {
            // TJ selected - filter by TJ only
            $query->where('box_id', $boxId);
        } elseif (!empty($areaId)) {
            // Subzone selected - filter by subzone only
            $query->where('area_id', $areaId);
        } elseif (!empty($zoneId)) {
            // Zone selected - filter by zone (includes all subzones and TJs)
            $query->where('zone_id', $zoneId);
        }

        return $query;
    }

    /**
     * Apply all filters to client query including location, status, search, package.
     * 
     * @param Builder $query The client query builder
     * @param Request $request The HTTP request
     * @return Builder
     */
    public static function applyAllFilters(Builder $query, Request $request): Builder
    {
        // Apply location filters (Zone/Subzone/TJ)
        self::applyLocationFilters($query, $request);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Package filter
        if ($request->filled('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        // Connection type filter
        if ($request->filled('connection_type')) {
            $query->where('connection_type', $request->connection_type);
        }

        // Technician filter
        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        // Search filter (respects location filters)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('client_id', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhere('address', 'ilike', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Get filtered client query with eager loading.
     * 
     * @param Request $request The HTTP request
     * @param array $with Relationships to eager load
     * @param bool $excludeInactive Whether to exclude inactive clients
     * @return Builder
     */
    public static function getFilteredQuery(Request $request, array $with = [], bool $excludeInactive = true): Builder
    {
        $query = Client::query();

        if (!empty($with)) {
            $query->with($with);
        }

        // Exclude inactive (left) clients by default
        if ($excludeInactive) {
            $query->where('status', '!=', 'inactive');
        }

        return self::applyAllFilters($query, $request);
    }

    /**
     * Get areas (subzones) for a zone.
     */
    public static function getAreasForZone($zoneId)
    {
        return \App\Models\Area::where('zone_id', $zoneId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get boxes (TJs) for an area/subzone.
     */
    public static function getBoxesForArea($areaId)
    {
        return \App\Models\Box::where('area_id', $areaId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get boxes (TJs) for a zone.
     */
    public static function getBoxesForZone($zoneId)
    {
        return \App\Models\Box::where('zone_id', $zoneId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
