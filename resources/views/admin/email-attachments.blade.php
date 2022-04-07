@extends('layouts.admin', [
    'title' => 'Email Attachments'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Email Attachments</h2>
                </div>
                <div class="small">
                    @if($parent)
                        <a href="{{ $parent }}">
                            <div class="card-body bg-light border-top px-4 py-2 text-monospace">
                                <i class="fas fa-fw fa-reply"></i> ...
                            </div>
                        </a>
                    @endif
                    @foreach ($folders as $folder)
                        <a href="{{ $folder['url'] }}">
                            <div class="card-body bg-light border-top px-4 py-2 text-monospace">
                                <i class="far fa-fw fa-folder"></i> {{ $folder['name'] }}
                            </div>
                        </a>
                    @endforeach
                    @foreach ($files as $file)
                        <a href="{{ $file['url'] }}" target="_blank">
                            <div class="card-body bg-light border-top px-4 py-2 text-monospace">
                                <i class="far fa-fw fa-{{ $file['type'] ?? 'file' }}"></i> {{ $file['name'] }}
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="card-body border-top p-4">
                </div>
            </div>
        </div>
@endsection
