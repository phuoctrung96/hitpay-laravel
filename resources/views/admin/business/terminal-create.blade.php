@extends('layouts.admin', [
    'title' => 'Terminals - '.$business->getName(),
])

@section('admin-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('admin.business.terminal.index', $business->getKey()) }}">
                <i class="fas fa-reply fa-fw mr-3"></i> Back to {{ $business->getName() }}'s Terminals
            </a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <p class="text-uppercase text-muted mb-0">{{ $business->getName() }}</p>
                    <h2 class="text-primary mb-0 title">Add New Terminals</h2>
                </div>
                <form class="card-body border-top" action="{{ route('admin.business.terminal.store', $business->getKey()) }}" method="post">
                    @method('POST')
                    @csrf
                    <div class="form-group">
                        <label for="label" class="small text-secondary">Label</label>
                        <input id="label" name="label" class="form-control bg-light{{ $errors->has('label') ? ' is-invalid' : '' }}" autocomplete="off" value="{{ old('label') }}" autofocus>
                        @error('label')
                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="registration_code" class="small text-secondary">Registration Code</label>
                        <input id="registration_code" name="registration_code" class="form-control bg-light{{ $errors->has('registration_code') ? ' is-invalid' : '' }}" autocomplete="off" value="{{ old('registration_code') }}" autofocus>
                        @error('registration_code')
                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
