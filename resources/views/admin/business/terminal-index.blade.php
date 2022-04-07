@extends('layouts.admin', [
    'title' => 'Terminals - '.$business->getName(),
])

@section('admin-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('admin.business.show', $business->getKey()) }}">
                <i class="fas fa-reply fa-fw mr-3"></i> Back to {{ $business->getName() }}</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <p class="text-uppercase text-muted mb-0">{{ $business->getName() }}</p>
                    <h2 class="text-primary mb-3 title">Terminals</h2>
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.business.terminal.create', $business->getKey()) }}">Add New Terminal</a>
                </div>
                @if ($successMessage = session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 mb-0">
                        {{ $successMessage }}
                    </div>
                @endif
                <div class="card-body {{ $successMessage ? '' : 'border-top' }} px-4 py-2">
                    <p class="small text-muted mb-0">Showing the latest {{ $paginator->count() }} results</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            <p class="font-weight-bold mb-2">{{ $item->name }}</p>
                            <p class="small font-weight-bold mb-2">{{ $item->remark }}</p>
                            <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                            <p class="text-dark small mb-0">Stripe Reader ID :
                                <span class="text-muted">{{ $item->stripe_terminal_id }}</span></p>
                            <p class="text-dark small mb-0">Device Type :
                                <span class="text-muted">{{ $item->device_type }}</span></p>
                            <a class="text-danger small font-weight-bold" href="#" data-toggle="modal" data-target="#deleteModal" data-terminal-id="{{ $item->getKey() }}" data-terminal-name="{{ $item->name }}">Remove terminal</a>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fas fa-calculator fa-4x"></i></p>
                            <p class="small mb-0">- No terminal found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top pt-0">
                </div>
            </div>
            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title font-weight-bold text-danger" id="deleteModalLabel">Warning!</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p id="warning-text">Are you sure you want to delete this terminal?</p>
                            <form id="delete-form" method="post">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-danger">Confirm</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <ul class="pagination mb-0">
                @if ($paginator->currentPage() <= 1)
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.previous')</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->currentPage() - 1) }}" rel="prev">@lang('pagination.previous')</a>
                    </li>
                @endif

                @if ($paginator->currentPage() < $paginator->lastPage())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->currentPage() + 1) }}" rel="next">@lang('pagination.next')</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.next')</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#deleteModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var terminalId = button.data('terminal-id');
                var terminalName = button.data('terminal-name');
                var modal = $(this)

                modal.find('#warning-text').text('Are you sure you want to remove terminal \'' + terminalName + '\'?')
                modal.find('#delete-form').prop('action', '{{ route('admin.business.terminal.destroy', [
                    'business_id' => $business->getKey(),
                    'terminal_id' => 'random_id',
                ]) }}'.replace('random_id', terminalId));
            });
        });
    </script>
@endpush
