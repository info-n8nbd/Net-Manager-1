@extends('layouts.admin')

@section('title', 'Fiber Out')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.fiber.index') }}">Fiber Stock</a></li>
<li class="breadcrumb-item active">Fiber Out</li>
@endsection

@section('content')
@include('admin.inventory.partials.nav-buttons')

<div class="row">
    <div class="col-md-5">
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-arrow-circle-up mr-2"></i>Record Fiber Out</h3>
            </div>
            <form method="POST" action="{{ route('admin.inventory.fiber.out.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="fiber_stock_id"><i class="fas fa-network-wired mr-1"></i> Select Fiber Stock <span class="text-danger">*</span></label>
                        <select name="fiber_stock_id" id="fiber_stock_id" class="form-control" required>
                            <option value="">-- Select Fiber --</option>
                            @foreach($stocks as $stock)
                                <option value="{{ $stock->id }}" {{ old('fiber_stock_id') == $stock->id ? 'selected' : '' }}>
                                    {{ $stock->fiber_code }}{{ $stock->brand_name ? ' - '.$stock->brand_name : '' }}{{ $stock->core ? ' ('.$stock->core.' Core)' : '' }} - {{ number_format($stock->remaining_meter) }}m remaining
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="out_meter"><i class="fas fa-ruler mr-1"></i> Out Meter <span class="text-danger">*</span></label>
                        <input type="number" name="out_meter" id="out_meter" class="form-control" step="0.01" min="0.01" placeholder="Enter meters" value="{{ old('out_meter') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="reason"><i class="fas fa-clipboard mr-1"></i> Reason <span class="text-danger">*</span></label>
                        <select name="reason" id="reason" class="form-control" required>
                            <option value="">-- Select Reason --</option>
                            @foreach(\App\Models\DropdownOption::getOptions('fiber_out_reasons') as $opt)
                            <option value="{{ $opt }}" {{ old('reason') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('admin.partials.client-search', [
                        'inputId' => 'fiberClientSearch',
                        'hiddenId' => 'fiberClientSearchId',
                        'fieldName' => 'client_id',
                        'label' => 'Client Name',
                        'required' => false,
                    ])
                    <div class="form-group">
                        <label for="technician_id"><i class="fas fa-user-cog mr-1"></i> Technician</label>
                        <select name="technician_id" id="technician_id" class="form-control">
                            <option value="">-- Select Technician (Optional) --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ old('technician_id') == $tech->id ? 'selected' : '' }}>
                                    {{ $tech->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="out_date"><i class="fas fa-calendar mr-1"></i> Date <span class="text-danger">*</span></label>
                        <input type="date" name="out_date" id="out_date" class="form-control" value="{{ old('out_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="note"><i class="fas fa-sticky-note mr-1"></i> Note</label>
                        <textarea name="note" id="note" class="form-control" rows="2" placeholder="e.g. Outdoor network repair, fiber cut at pole #12...">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-danger btn-block"><i class="fas fa-arrow-circle-up mr-1"></i> Record Fiber Out</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Available Fiber Stock</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fiber Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Core</th>
                            <th>Remaining</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stocks as $stock)
                        <tr>
                            <td><strong>{{ $stock->fiber_code }}</strong></td>
                            <td>{{ $stock->name ?? '-' }}</td>
                            <td>{{ $stock->fiber_type ?? '-' }}</td>
                            <td>{{ $stock->core }} Core</td>
                            <td>
                                {{ number_format($stock->remaining_meter) }}m
                                @php
                                    $pct = $stock->total_meter > 0 ? round(($stock->remaining_meter / $stock->total_meter) * 100) : 0;
                                @endphp
                                <div class="progress progress-xs mt-1">
                                    <div class="progress-bar {{ $pct > 50 ? 'bg-success' : ($pct > 20 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $stock->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($stock->status) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No active fiber stock available.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

