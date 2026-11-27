@extends('admin.layout')

@section('content')
    <section class="panel" aria-label="{{ $title }} results">
        <form class="filters" id="filters" aria-label="Filter {{ $title }}"></form>
        <div id="list-state" class="list-state" role="status" aria-live="polite">Loading records…</div>
        <div class="table-responsive" id="table-wrap" tabindex="0" aria-label="{{ $title }} table" hidden>
            <table class="table align-middle admin-table">
                <caption class="visually-hidden">{{ $title }}</caption>
                <thead id="table-head"></thead>
                <tbody id="table-body"></tbody>
            </table>
        </div>
        <nav class="table-pagination" id="pagination" aria-label="Results pagination" hidden></nav>
    </section>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('admin-assets/js/collections.js') }}"></script>
@endpush
