@php($tab = Request::segment(1))

@extends('layouts.app', [
    'navbar_main_border_bottom' => false
])

@section('app-content')
    <nav class="navbar navbar-dark navbar-sub navbar-expand-md bg-primary border-bottom small shadow-sm">
        <div class="container-fluid">
            <div class="mx-auto">
                <a class="navbar-brand d-md-none" href="{{ route('admin') }}">
                    Admin Dashboard
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <hr class="d-md-none my-2">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item{{ $tab === null ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin') }}">
                                @if ($tab === null)
                                    <i class="fa fas fa-home fa-fw mr-1"></i>
                                    <span class="d-md-none d-lg-inline">Home</span>
                                    <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-home fa-fw mr-1"></i>
                                    <span class="d-md-none d-lg-inline">Home</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'business' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.business.index') }}">
                                @if ($tab === 'business')
                                    <i class="fa fas fa-cash-register fa-fw mr-1"></i> Business <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-cash-register fa-fw mr-1"></i> Business
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'charge' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.charge.index') }}">
                                @if ($tab === 'charge')
                                    <i class="fa fas fa-list-alt fa-fw mr-1"></i> Charge <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-list-alt fa-fw mr-1"></i> Charge
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'transfer' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.transfer.fast-payment.index') }}">
                                @if ($tab === 'transfer')
                                    <i class="fa fas fa-list-alt fa-fw mr-1"></i> Fast Transfer <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-list-alt fa-fw mr-1"></i> Fast Transfer
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'commission' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.commission.index') }}">
                                @if ($tab === 'commission')
                                    <i class="fa fas fa-list-alt fa-fw mr-1"></i> Commission <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-list-alt fa-fw mr-1"></i> Commission
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'failed-refunds' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.failed-refund.index') }}">
                                @if ($tab === 'failed-refunds')
                                    <i class="fa fas fa-times fa-fw mr-1"></i> Failed Refunds <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-times fa-fw mr-1"></i> Failed Refunds
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'terminal' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.terminal.index') }}">
                                @if ($tab === 'terminal')
                                    <i class="fa fas fa-boxes fa-fw mr-1"></i> Terminals <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-boxes fa-fw mr-1"></i> Terminals
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'campaigns' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.campaigns.index') }}">
                                @if ($tab === 'campaigns')
                                    <i class="fa fas fa-boxes fa-fw mr-1"></i> Campaigns <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-boxes fa-fw mr-1"></i> Campaigns
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'partner' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.partner.index') }}">
                                @if ($tab === 'partner')
                                    <i class="fa fas fa-user-astronaut fa-fw mr-1"></i> Partners <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-user-astronaut fa-fw mr-1"></i> Partners
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'onboarding' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.onboarding.index') }}">
                                @if ($tab === 'partner')
                                    <i class="fa fas fa-thumbs-up fa-fw mr-1"></i> Pending Onboarding <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-thumbs-up fa-fw mr-1"></i> Pending Onboarding
                                    @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'attachments' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.email-attachment.index') }}">
                                @if ($tab === 'attachments')
                                    <i class="fa fas fa-file fa-fw mr-1"></i> Attachments <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-file fa-fw mr-1"></i> Attachments
                                @endif
                            </a>
                        </li>
                        <li class="nav-item{{ $tab === 'import-dbs-reconcile' ? ' active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.importdbsreconcile.index') }}">
                                @if ($tab === 'import-dbs-reconcile')
                                    <i class="fa fas fa-file fa-fw mr-1"></i> DBS Reconcile <span class="sr-only">(current)</span>
                                @else
                                    <i class="fa fas fa-file fa-fw mr-1"></i> DBS Reconcile
                                @endif
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <div class="{{ $app_classes['content'] ?? 'container pt-4 pb-5' }}">
        @yield('admin-content')
    </div>
@endsection
