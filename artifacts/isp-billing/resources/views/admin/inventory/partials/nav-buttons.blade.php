{{-- Inventory Quick Navigation --}}
<div class="mb-3">
    <div class="d-flex flex-wrap" style="gap:4px;">
        <a href="{{ route('admin.inventory.stock.summary') }}" class="btn btn-sm {{ request()->routeIs('admin.inventory.stock.summary') ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fas fa-tachometer-alt mr-1"></i>Dashboard</a>
        <a href="{{ route('admin.inventory.products.index') }}" class="btn btn-sm {{ request()->routeIs('admin.inventory.products.index') ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fas fa-boxes mr-1"></i>Products</a>
        <a href="{{ route('admin.inventory.products.out_history') }}" class="btn btn-sm {{ request()->routeIs('admin.inventory.products.out_history') ? 'btn-warning' : 'btn-outline-warning' }}"><i class="fas fa-history mr-1"></i>Out History</a>
        <a href="{{ route('admin.inventory.fiber.index') }}" class="btn btn-sm {{ request()->routeIs('admin.inventory.fiber.index') ? 'btn-info' : 'btn-outline-info' }}"><i class="fas fa-project-diagram mr-1"></i>Fiber Stock</a>
        <a href="{{ route('admin.inventory.stock.availability') }}" class="btn btn-sm {{ request()->routeIs('admin.inventory.stock.availability') ? 'btn-secondary' : 'btn-outline-secondary' }}"><i class="fas fa-warehouse mr-1"></i>Stock Availability</a>
        <a href="{{ route('admin.inventory.returned-equipments') }}" class="btn btn-sm {{ request()->routeIs('admin.inventory.returned-equipments*') ? 'btn-warning' : 'btn-outline-warning' }}"><i class="fas fa-undo-alt mr-1"></i>Returned Equipment</a>
        <a href="{{ route('admin.inventory.damage.index') }}" class="btn btn-sm {{ request()->routeIs('admin.inventory.damage.*') ? 'btn-danger' : 'btn-outline-danger' }}"><i class="fas fa-exclamation-triangle mr-1"></i>Damaged</a>
    </div>
</div>
