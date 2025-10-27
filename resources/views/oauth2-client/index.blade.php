@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">OAuth Providers</h1>

  <div class="flex gap-2 mb-4">
    <a href="{{ route('oauth2-client.connect','github') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Connect GitHub</a>
    <a href="{{ route('oauth2-client.connect','google') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">Connect Google</a>
  </div>

  <hr class="my-4"/>

  <h3 class="text-xl font-semibold mb-2">Attached</h3>
  @if(session('attached'))
    <ul class="list-disc pl-5">
      @foreach(session('attached') as $p)
        <li class="mb-1">{{ $p['provider'] }} - {{ $p['provider_user_id'] ?? '---' }}</li>
      @endforeach
    </ul>
  @else
    <p class="text-gray-500">No providers attached.</p>
  @endif

  @if(session('success'))
    <p class="text-green-600 mt-4">{{ session('success') }}</p>
  @endif
  @if(session('error'))
    <p class="text-red-600 mt-4">{{ session('error') }}</p>
  @endif
</div>
@endsection
