@extends('layouts.admin')

@section('title', 'Edit Email Template')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-edit me-2"></i>Edit: {{ ucwords(str_replace('_', ' ', $template->name)) }}</h4>
            <p class="text-muted mb-0">Customize the email template content</p>
        </div>
        <a href="{{ route('admin.settings.email-templates') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Template Editor</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.email-templates.update', $template) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Subject Line</label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject', $template->subject) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Body (HTML)</label>
                            <textarea name="body" class="form-control font-monospace" rows="20" style="font-size: 12px;">{{ old('body', $template->body) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ $template->is_active ? 'checked' : '' }}>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Changes
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="previewTemplate()">
                                <i class="fas fa-eye me-1"></i>Preview
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Available Variables</h6>
                </div>
                <div class="card-body">
                    @if($template->variables)
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(json_decode($template->variables, true) ?? [] as $var)
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertVariable('{{ $var }}')">
                            {{ '{{' . $var . '}}' }}
                        </button>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2">Click to insert into editor</small>
                    @else
                    <p class="text-muted mb-0">No variables defined</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Preview</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="previewTemplate()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div id="preview-subject" class="bg-light p-2 border-bottom">
                        <strong>Subject:</strong> <span id="preview-subject-text">{{ $template->subject }}</span>
                    </div>
                    <iframe id="preview-frame" style="width: 100%; height: 400px; border: none;"></iframe>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Send Test Email</h6>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <input type="email" id="test-email" class="form-control" placeholder="test@example.com">
                        <button type="button" class="btn btn-outline-primary" onclick="sendTestEmail()">
                            <i class="fas fa-paper-plane me-1"></i>Send
                        </button>
                    </div>
                    <small class="text-muted">Send a test email with sample data</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewTemplate() {
    const subject = document.querySelector('input[name="subject"]').value;
    const body = document.querySelector('textarea[name="body"]').value;
    
    fetch('{{ route("admin.settings.email-templates.preview", $template) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ subject, body })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('preview-subject-text').textContent = data.subject;
        const frame = document.getElementById('preview-frame');
        frame.contentDocument.open();
        frame.contentDocument.write(data.body);
        frame.contentDocument.close();
    });
}

function insertVariable(varName) {
    const textarea = document.querySelector('textarea[name="body"]');
    const pos = textarea.selectionStart;
    const text = textarea.value;
    const variable = '{{' + varName + '}}';
    textarea.value = text.slice(0, pos) + variable + text.slice(pos);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = pos + variable.length;
}

function sendTestEmail() {
    const email = document.getElementById('test-email').value;
    if (!email) {
        alert('Please enter an email address');
        return;
    }
    
    fetch('{{ route("admin.settings.email-templates.test", $template) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ email })
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
    });
}

document.addEventListener('DOMContentLoaded', previewTemplate);
</script>
@endpush
