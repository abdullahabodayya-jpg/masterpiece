<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\CreateUserRequest;
use App\Http\Requests\Accounts\UpdateUserRequest;
use App\Models\User;
use App\Services\Accounts\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\Marketplace\MarketplaceAccess;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::enum(AccountStatus::class)],
            'page' => ['nullable', 'integer', 'between:1,21474836'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()->select([
            'id',
            'name',
            'email',
            'role',
            'status',
            'created_at',
        ]);

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = addcslashes($filters['search'], '\\%_');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $users = $query
            ->latest('created_at')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(
        CreateUserRequest $request,
        UserService $userService
    ): JsonResponse {
        try {
            $user = $userService->create($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'The email has already been taken.']);
        }

        return $this->show($user)->setStatusCode(201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->only([
                'id',
                'name',
                'email',
                'role',
                'status',
                'created_at',
                'updated_at',
            ]),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(
        UpdateUserRequest $request,
        User $user,
        MarketplaceAccess $access,
    ): JsonResponse {
        try {
            $updated = $access->lockAccounts($request->user(), [$user->id], function (User $admin, array $locked) use ($request, $user) {
                abort_unless($admin->role === UserRole::Admin && $admin->status === AccountStatus::Active, 403);
                $target = $locked[$user->id];
                $this->ensureManageable($target);
                $target->fill($request->validated());

                if ($target->isDirty('email')) {
                    $target->email_verified_at = null;
                }

                $target->save();

                return $target;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'The email has already been taken.']);
        }

        return $this->show($updated);
    }

    /**
     * Ensure the account can be managed
     * through the administrative users module.
     */
    private function ensureManageable(User $user): void
    {
        abort_unless(
            in_array($user->role, [
                UserRole::Creator,
                UserRole::Editor,
            ], true),
            403,
            'This account cannot be managed here.'
        );
    }
    public function updateStatus(
        Request $request,
        User $user,
        MarketplaceAccess $access
    ): JsonResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    AccountStatus::Active->value,
                    AccountStatus::Suspended->value,
                ]),
            ],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $newStatus = AccountStatus::from($validated['status']);

        $result = $access->lockAccounts(
            $request->user(),
            [$user->id],
            function (User $admin, array $locked) use (
                $user,
                $newStatus
            ) {
                abort_unless(
                    $admin->role === UserRole::Admin
                        && $admin->status === AccountStatus::Active,
                    403
                );

                $target = $locked[$user->id];

                $this->ensureManageable($target);

                $oldStatus = $target->status;

                $allowed =
                    ($oldStatus === AccountStatus::Active
                        && $newStatus === AccountStatus::Suspended)
                    ||
                    ($oldStatus === AccountStatus::Suspended
                        && $newStatus === AccountStatus::Active);

                abort_unless(
                    $allowed,
                    409,
                    'Invalid account status transition.'
                );

                $target->status = $newStatus;
                if ($newStatus === AccountStatus::Suspended) {
                    $target->setRememberToken(null);
                    $sessionConnection = config('session.connection');
                    if (config('session.driver') === 'database'
                        && ($sessionConnection === null || $sessionConnection === $target->getConnection()->getName())) {
                        $target->getConnection()->table(config('session.table'))->where('user_id', $target->id)->delete();
                    }
                }
                $target->save();

                return [
                    'user' => $target,
                    'old_status' => $oldStatus->value,
                ];
            }
        );

        Log::notice('Account status changed by admin.', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id,
            'old_status' => $result['old_status'],
            'new_status' => $newStatus->value,
            'reason' => $validated['reason'],
        ]);

        return $this->show($result['user']);
    }
}
