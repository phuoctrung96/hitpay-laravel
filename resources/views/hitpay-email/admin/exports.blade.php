@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Hi There!</p>
    <p>{{ $content }}</p>
    @if (count($files))
        <p>Click on the link(s) below to download the files.</p>
        <ol>
        @foreach ($files as $name => $url)
            <li><a href="{{ $url }}">{{ $name }}</a></li>
        @endforeach
        </ol>
    @endif
@endsection
