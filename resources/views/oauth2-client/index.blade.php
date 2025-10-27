@extends('layouts.app')

@section('content')
<div class="container">
  <h1>OAuth Providers</h1>

  <a class="btn btn-primary" href="{{ route('oauth2-client.connect','github') }}">Connect GitHub</a>
  <a class="btn btn-secondary" href="{{ route('oauth2-client.connect','google') }}">Connect Google</a>
  <hr/>

  <h3>Attached</h3>
  @if($attached)
    <ul>
      @foreach($attached as $p)
        <li>{{ $p['provider'] }} - {{ $p['provider_user_id'] ?? '---' }}</li>
      @endforeach
    </ul>
  @else
    <p>No providers attached.</p>
  @endif
</div>
@endsection
