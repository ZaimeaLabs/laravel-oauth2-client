@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <div class="max-w-3xl mx-auto bg-white shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-semibold mb-4">Provider: {{ $provider }}</h1>

        @if($attached)
            <p class="mb-2">Connected as: <strong>{{ $attached['provider_user_id'] ?? 'connected' }}</strong></p>
            <p class="mb-2">Expires at: <strong>{{ $attached['expires_at'] ?? '—' }}</strong></p>
            <form method="POST" action="{{ route('oauth2-client.detach', $provider) }}">@csrf
                <button class="px-4 py-2 bg-red-600 text-white rounded">Detach</button>
            </form>
            <pre class="mt-4 bg-gray-100 p-3 rounded">{{ json_encode($attached['meta'] ?? [], JSON_PRETTY_PRINT) }}</pre>
        @else
            <p class="mb-4">This provider is not attached to your account.</p>
            <a class="px-4 py-2 bg-blue-600 text-white rounded" href="{{ route('oauth2-client.connect', $provider) }}">Connect {{ $provider }}</a>
        @endif
    </div>
</div>
@endsection
