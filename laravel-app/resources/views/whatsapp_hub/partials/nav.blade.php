<div class="mb-3">
    <a class="btn btn-sm {{ request()->routeIs('whatsapp.index') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('whatsapp.index') }}">Command Center</a>
    <a class="btn btn-sm {{ request()->routeIs('whatsapp.conversations*') || request()->routeIs('whatsapp.conversation') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('whatsapp.conversations') }}">Conversations</a>
    <a class="btn btn-sm {{ request()->routeIs('whatsapp.leads*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('whatsapp.leads') }}">Leads</a>
    <a class="btn btn-sm {{ request()->routeIs('whatsapp.rentals*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('whatsapp.rentals') }}">Rentals</a>
    <a class="btn btn-sm {{ request()->routeIs('whatsapp.calls') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('whatsapp.calls') }}">Calls</a>
    <a class="btn btn-sm {{ request()->routeIs('whatsapp.assistant*') ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('whatsapp.assistant') }}">AI Assistant</a>
    <a class="btn btn-sm {{ request()->routeIs('whatsapp.settings') ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('whatsapp.settings') }}">Settings</a>
</div>
