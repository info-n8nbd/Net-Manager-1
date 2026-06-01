<div class="form-group" style="position: relative;">
    <label for="{{ $inputId ?? 'clientSearch' }}"><i class="fas fa-user mr-1"></i> {{ $label ?? 'Client Name' }} @if($required ?? true)<span class="text-danger">*</span>@endif</label>
    <input type="text" id="{{ $inputId ?? 'clientSearch' }}" class="form-control client-search-input" placeholder="Search by Name, PPPoE ID, Client ID, Phone..." autocomplete="off" value="{{ $selectedName ?? '' }}" data-search-url="{{ $searchUrl ?? route('admin.api.search-clients') }}">
    <input type="hidden" name="{{ $fieldName ?? 'client_id' }}" id="{{ $hiddenId ?? 'clientSearchId' }}" value="{{ $selectedValue ?? '' }}" @if($required ?? true) required @endif>
    <div class="client-search-results list-group position-absolute w-100" style="z-index:1050; max-height:220px; overflow-y:auto; display:none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 0 0 6px 6px;"></div>
    <small class="text-muted client-search-info" id="{{ ($inputId ?? 'clientSearch') . 'Info' }}"></small>
</div>

@once
@push('scripts')
<script>
$(function() {
    var searchTimers = {};

    $(document).on('input', '.client-search-input', function() {
        var $input = $(this);
        var inputId = $input.attr('id');
        var searchUrl = $input.data('search-url');
        var $wrap = $input.closest('.form-group');
        var $results = $wrap.find('.client-search-results');
        var $hidden = $wrap.find('input[type="hidden"]');
        var $info = $wrap.find('.client-search-info');
        var q = $input.val().trim();

        $hidden.val('');
        $info.html('');

        clearTimeout(searchTimers[inputId]);
        if (q.length < 2) {
            $results.hide().html('');
            return;
        }

        searchTimers[inputId] = setTimeout(function() {
            $.get(searchUrl, { q: q }, function(data) {
                if (data.length === 0) {
                    $results.html('<div class="list-group-item text-muted text-sm py-2">No clients found</div>').show();
                    return;
                }
                var html = '';
                data.forEach(function(c) {
                    html += '<a href="#" class="list-group-item list-group-item-action py-2 px-3 client-search-result" '
                        + 'data-id="' + c.id + '" '
                        + 'data-name="' + (c.name || '').replace(/"/g, '&quot;') + '" '
                        + 'data-username="' + (c.username || '') + '" '
                        + 'data-client-id="' + (c.client_id || '') + '" '
                        + 'data-phone="' + (c.phone || '') + '">'
                        + '<strong>' + (c.name || '-') + '</strong>'
                        + (c.username ? ' <code class="ml-1" style="font-size:12px">' + c.username + '</code>' : '')
                        + (c.client_id ? ' <small class="text-muted ml-1">[' + c.client_id + ']</small>' : '')
                        + (c.phone ? ' <small class="text-muted ml-2"><i class="fas fa-phone"></i> ' + c.phone + '</small>' : '')
                        + '</a>';
                });
                $results.html(html).show();
            });
        }, 300);
    });

    $(document).on('click', '.client-search-result', function(e) {
        e.preventDefault();
        var $this = $(this);
        var $wrap = $this.closest('.form-group');
        var $input = $wrap.find('.client-search-input');
        var $hidden = $wrap.find('input[type="hidden"]');
        var $results = $wrap.find('.client-search-results');
        var $info = $wrap.find('.client-search-info');

        var name = $this.data('name');
        var username = $this.data('username');
        var clientId = $this.data('client-id');
        var phone = $this.data('phone');

        $input.val(name + (username ? ' (' + username + ')' : ''));
        $hidden.val($this.data('id'));
        var info = '<i class="fas fa-check-circle text-success mr-1"></i> ' + name;
        if (username) info += ' | PPPoE: <code>' + username + '</code>';
        if (clientId) info += ' | ID: ' + clientId;
        if (phone) info += ' | Phone: ' + phone;
        $info.html(info);
        $results.hide().html('');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.client-search-input, .client-search-results').length) {
            $('.client-search-results').hide();
        }
    });
});
</script>
@endpush
@endonce
