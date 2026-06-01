@if(session('info'))
<div class="alert alert-info alert-dismissible fade show">
    <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif

@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show">
    <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif

@if(session('mikrotik_error'))
<div class="alert alert-warning alert-dismissible fade show">
    <i class="fas fa-server mr-2"></i>{{ session('mikrotik_error') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif
