<div class="row">
    <div class="col-md-4">
        <div class="float-left">
            <form class="form-inline" method="GET" action="{{url()->current()}}">
                <label for="perPage" >Showing </label>
                <select class="form-control" onchange="paginationPerPageChanged(this);" id="perPage" name="perPage">
                    @foreach(\App\Helpers\Pagination::AVAILABLE_PAGE_NUMBER as $perPageNumber)
                        <option value="{{$perPageNumber}}" @if($perPage == $perPageNumber) selected @endif>{{$perPageNumber}}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
    <div class="col-md-8 float-right">
        {{ $paginator->appends(['perPage' => $perPage])->links('vendor.pagination.bootstrap-4') }}
    </div>
</div>

@push('body-stack')
    <script>
        function paginationPerPageChanged(trigger)
        {
            let current = new URL(window.location.href);
            let query = current.search;
            let params = new URLSearchParams(query);

            let perPage = $(trigger).val();

            params.set('perPage', perPage);

            current.search = params.toString();
            window.location = current.toString();
        }
    </script>
@endpush
