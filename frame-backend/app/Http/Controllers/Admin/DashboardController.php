<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\RefundStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ContentType;
use App\Models\EditingTool;
use App\Models\EditorService;
use App\Enums\VerificationStatus;
use App\Models\EditorVerificationRequest;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Post;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Payments\FinancialSummary;
use App\Services\Payments\PaymentAccess;

class DashboardController extends Controller
{
    public function index(FinancialSummary $financialSummary)
    {
        return response()->json([
            'total_users' => User::count(),
            'total_creators' => User::where(
                'role',
                UserRole::Creator->value
            )->count(),
            'total_editors' => User::where(
                'role',
                UserRole::Editor->value
            )->count(),

            'active_users' => User::where(
                'status',
                AccountStatus::Active->value
            )->count(),

            'total_work_items' => WorkItem::count(),

            'total_services' => EditorService::count(),

            'total_applications' => JobApplication::count(),

            'total_verification_requests' => EditorVerificationRequest::count(),

            'total_payments' => Payment::count(),

            'total_refunds' => Refund::count(),

            'total_payouts' => Payout::count(),

            'total_posts' => Post::count(),

            'total_comments' => Comment::count(),

            'total_reviews' => Review::count(),

            'total_content_types' => ContentType::count(),

            'total_editing_tools' => EditingTool::count(),

            'suspended_users' => User::where(
                'status',
                AccountStatus::Suspended->value
            )->count(),

            'total_jobs' => WorkItem::where(
                'type',
                WorkType::Job->value
            )->count(),

            'open_jobs' => WorkItem::where(
                'type',
                WorkType::Job->value
            )->where(
                'status',
                WorkStatus::Open->value
            )->count(),

            'active_projects' => WorkItem::whereIn(
                'status',
                [
                    WorkStatus::InProgress->value,
                    WorkStatus::InReview->value,
                ]
            )->count(),

            'completed_projects' => WorkItem::where(
                'status',
                WorkStatus::Completed->value,
            )->count(),

            'cancelled_work_items' => WorkItem::where(
                'status',
                WorkStatus::Cancelled->value,
            )->count(),

            'disputed_projects' => WorkItem::where(
                'status',
                WorkStatus::Disputed->value,
            )->count(),

            'successful_payments' => Payment::where(
                'status',
                PaymentStatus::Succeeded->value,
            )->count(),

            'pending_refunds' => PaymentAccess::blockingRefunds(Refund::query())->whereIn(
                'status',
                [
                    RefundStatus::Pending->value,
                    RefundStatus::Processing->value,

                ]
            )->count(),

            'pending_payouts' => Payout::whereIn(
                'status',
                [
                    PayoutStatus::Pending->value,
                    PayoutStatus::Processing->value,

                ]
            )->count(),

            'paid_payouts' => Payout::where(
                'status',
                PayoutStatus::Paid->value
            )->count(),

            'average_rating' => round(
                (float) Review::avg('rating'),
                2
            ),

            'pending_verifications' => EditorVerificationRequest::whereIn(
                'status',
                [
                    VerificationStatus::Pending->value,
                    VerificationStatus::UnderReview->value,
                ]
            )->count(),

            'approved_verifications' => EditorVerificationRequest::where(
                'status',
                VerificationStatus::Approved->value
            )->count(),

            'rejected_verifications' => EditorVerificationRequest::where(
                'status',
                VerificationStatus::Rejected->value
            )->count(),

            'monthly_registrations' => $this->monthlyRegistrations(),
            'cancelled_projects' => WorkItem::where(
                'status',
                WorkStatus::Cancelled->value
            )->whereNotNull('started_at')->count(),

            'recent_users' => User::latest()->limit(5)->get([
                'name',
                'role',
                'status',
                'created_at',
            ]),

            'recent_payments' => Payment::latest()
                ->limit(5)
                ->get([
                    'amount',
                    'currency',
                    'status',
                    'created_at',
                ]),

            'recent_verifications' => EditorVerificationRequest::latest()
                ->limit(5)
                ->get([
                    'status',
                    'created_at',
                ]),

                'financial_summary' => $financialSummary->staff(),
            'financial_mode' => 'academic_simulation',

        ]);
    }

    private function monthlyRegistrations(): array
    {

        $start = now()->startOfMonth()->subMonth(5);

        $registration = User::where('created_at', '>=', $start)->where('created_at', '<=', now())->selectRaw(
            "
            DATE_FORMAT(created_at, '%Y-%m') AS month,
            COUNT(*) AS total
            "
        )->groupBy('month')->pluck('total', 'month');
        $result = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);

            $result[] = [
                'month' => $month->format('M Y'),
                'total' => (int) $registration->get(
                    $month->format('Y-m'),
                    0
                ),
            ];
        }
        return $result;
    }
}
