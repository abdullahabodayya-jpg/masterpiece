<?php

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$navigation = [
    'Overview' => ['dashboard' => ['Dashboard', 'grid-1x2']],
    'User management' => [
        'users' => ['Users', 'people'],
        'verification' => ['Editor Verification', 'patch-check'],
    ],
    'Marketplace' => [
        'jobs' => ['Jobs', 'briefcase'],
        'applications' => ['Applications', 'file-earmark-text'],
        'services' => ['Services', 'collection-play'],
        'service-requests' => ['Service Requests', 'inboxes'],
        'projects' => ['Projects', 'kanban'],
    ],
    'Finance' => [
        'payments' => ['Payments', 'credit-card'],
        'refunds' => ['Refunds', 'arrow-counterclockwise'],
        'payouts' => ['Payouts', 'wallet2'],
    ],
    'Community' => [
        'posts' => ['Posts', 'card-text'],
        'comments' => ['Comments', 'chat-left-text'],
        'reviews' => ['Reviews', 'star'],
    ],
    'Configuration' => [
        'content-types' => ['Content Types', 'tags'],
        'editing-tools' => ['Editing Tools', 'tools'],
    ],
];

Route::prefix('admin')->group(function () use ($navigation) {
    Route::get('login', function (Request $request) {
        $user = $request->user('web');
        if ($user?->role === UserRole::Admin && $user->status === AccountStatus::Active) {
            return redirect()->route('admin.dashboard');
        }

        return response()->view('admin.login', ['hasSession' => $user !== null])
            ->header('Cache-Control', 'private, no-store');
    })->name('login');

    Route::middleware(['auth:web', 'admin', 'cache.headers:private;no_store'])->group(function () use ($navigation) {
        foreach ($navigation as $group => $pages) {
            foreach ($pages as $page => [$title]) {
                Route::get($page === 'dashboard' ? '/' : $page, fn () => view(
                    $page === 'dashboard' ? 'admin.dashboard' : 'admin.collection',
                    ['navigation' => $navigation, 'group' => $group, 'page' => $page, 'title' => $title],
                ))->name('admin.'.$page);
            }
        }
    });
});
