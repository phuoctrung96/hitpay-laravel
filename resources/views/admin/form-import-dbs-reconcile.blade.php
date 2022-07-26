@extends('layouts.admin', [
    'title' => 'Import DBS Reconcile'
])

@section('admin-content')
    <div class="row justify-content-center">
        <div class="col-6 main-content ">
            <div class="card border-0 shadow-sm">
                <admin-form-import-dbs-reconcile></admin-form-import-dbs-reconcile>
            </div>
        </div>
    </div>
@endsection
