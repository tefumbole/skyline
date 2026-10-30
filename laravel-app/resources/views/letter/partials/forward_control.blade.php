@php
    $actor = auth()->user();
    $perms = $all_permission ?? [];
    $canForwardLetter = $actor && (
        $actor->canManageHotelSettings()
        || in_array('forward_letter', $perms, true)
        || in_array('letter_create', $perms, true)
        || in_array('letter_edit', $perms, true)
        || in_array('letter_awaiting_edit', $perms, true)
    );
@endphp
@if($canForwardLetter && (int) $letter->is_sent === 0 && (int) $letter->is_rejected === 0)
    <form method="POST" action="{{ route('letter.forward', $letter->id) }}" class="letter-forward-form" style="padding:8px 16px;min-width:180px;" onclick="event.stopPropagation();">
        @csrf
        <label style="display:block;font-size:12px;margin-bottom:4px;">Forward to</label>
        <select name="forward_letter" class="form-control input-sm" style="margin-bottom:6px;">
            <option value="approver" @if((int) $letter->is_edit === 0) selected @endif>Approver</option>
            <option value="signer" @if((int) $letter->is_edit === 1 && (int) $letter->is_approve === 0) selected @endif>Signer</option>
            <option value="sender" @if((int) $letter->is_approve === 1) selected @endif>Ready to send</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm btn-block">Forward</button>
    </form>
@endif
