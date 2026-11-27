@extends('admin.layout')

@section('content')
    <div id="overview-state" class="panel list-state" role="status" aria-live="polite">Loading dashboard…</div>
    <div id="overview" hidden>
        <section class="metrics" id="metrics" aria-label="Key indicators"></section>
        <div class="overview-grid">
            <section class="panel panel-pad" aria-labelledby="chart-heading">
                <div class="panel-heading">
                    <div><h2 id="chart-heading">User registrations</h2><p>Monthly sign-ups over the last six months</p></div>
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                </div>
                <div class="chart-area">
                    <canvas id="registrations" role="img" aria-label="Monthly user registrations" aria-describedby="chart-values"></canvas>
                    <p id="chart-values" class="visually-hidden"></p>
                </div>
            </section>
            <section class="panel panel-pad" aria-labelledby="queues-heading">
                <div class="panel-heading"><div><h2 id="queues-heading">Operational queues</h2><p>Current workloads across Frame</p></div><i class="bi bi-inboxes" aria-hidden="true"></i></div>
                <div id="queues"></div>
            </section>
        </div>
        <section class="panel panel-pad mb-3" aria-labelledby="finance-heading">
            <div class="panel-heading"><div><h2 id="finance-heading">Financial summary</h2><p>Stripe sandbox collections and simulated payouts</p></div><i class="bi bi-wallet2" aria-hidden="true"></i></div>
            <div class="table-responsive" id="financial-summary"></div>
        </section>
        <section class="activity-grid" id="activity" aria-label="Recent activity"></section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/vendor/chart.umd.js') }}" defer></script>
    <script type="module" src="{{ asset('admin-assets/js/dashboard.js') }}"></script>
@endpush
