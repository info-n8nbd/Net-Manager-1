@if($item->action_status === 'synced')
    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>In Sync</span>
@elseif($item->action_status === 'applied')
    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Applied</span>
@elseif($item->action_status === 'pending')
    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
@elseif($item->action_status === 'failed')
    <span class="badge badge-danger" title="{{ $item->action_message }}"><i class="fas fa-times mr-1"></i>Failed</span>
@elseif($item->action_status === 'skipped')
    <span class="badge badge-secondary"><i class="fas fa-forward mr-1"></i>Skipped</span>
@endif
