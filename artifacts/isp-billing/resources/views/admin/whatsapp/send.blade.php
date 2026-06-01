@extends('layouts.admin')

@section('title', 'Send WhatsApp Message')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">

            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-whatsapp mr-2"></i>Send WhatsApp Message</h3>
                </div>
                <form action="{{ route('admin.whatsapp.send.process') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="form-group">
                            <label><i class="fas fa-paper-plane mr-1"></i>Send Type</label>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-outline-primary active">
                                    <input type="radio" name="send_type" value="single" checked> <i class="fas fa-user mr-1"></i>Single
                                </label>
                                <label class="btn btn-outline-primary">
                                    <input type="radio" name="send_type" value="bulk"> <i class="fas fa-users mr-1"></i>Bulk
                                </label>
                            </div>
                        </div>

                        <div id="singlePhone">
                            <div class="form-group">
                                <label><i class="fas fa-phone mr-1"></i>Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="e.g., 01XXXXXXXXX">
                            </div>
                        </div>

                        <div id="bulkPhones" style="display: none;">
                            <div class="form-group">
                                <label><i class="fas fa-list mr-1"></i>Phone Numbers (one per line)</label>
                                <textarea name="phones" class="form-control" rows="4" placeholder="01XXXXXXXXX&#10;01XXXXXXXXX&#10;01XXXXXXXXX">{{ old('phones') }}</textarea>
                                <small class="text-muted">Enter each phone number on a new line</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-file-alt mr-1"></i>Template (Optional)</label>
                            <select name="template_id" class="form-control" id="templateSelect">
                                <option value="">Custom Message</option>
                                @foreach($templates as $template)
                                <option value="{{ $template->id }}" data-content="{{ $template->content }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-comment mr-1"></i>Message</label>
                            <textarea name="message" class="form-control" rows="5" id="messageContent" placeholder="Type your message here..." required>{{ old('message') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fab fa-whatsapp mr-1"></i>Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('input[name="send_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        if (this.value === 'bulk') {
            document.getElementById('singlePhone').style.display = 'none';
            document.getElementById('bulkPhones').style.display = 'block';
        } else {
            document.getElementById('singlePhone').style.display = 'block';
            document.getElementById('bulkPhones').style.display = 'none';
        }
    });
});

document.getElementById('templateSelect').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    var content = selected.getAttribute('data-content');
    if (content) {
        document.getElementById('messageContent').value = content;
    }
});
</script>
@endpush
@endsection
