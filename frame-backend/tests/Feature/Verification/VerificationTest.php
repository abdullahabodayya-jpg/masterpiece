<?php

namespace Tests\Feature\Verification;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class VerificationTest extends VerificationTestCase
{
    public function test_pending_editor_submits_private_evidence_and_admin_records_a_decision(): void
    {
        Storage::fake('local');
        $editor = User::factory()->editor()->create(['status' => 'pending_verification']);
        $base = '/api/v1/verification-requests';
        $staff = '/api/v1/staff/verification-requests';
        $this->getJson($base)->assertUnauthorized();
        $this->signIn($editor);
        $this->post($base, ['document_type' => 'professional_evidence', 'document' => UploadedFile::fake()->createWithContent('bad.pdf', 'plain text')])
            ->assertUnprocessable();
        $this->post($base, $this->evidence() + ['document_storage_key' => 'arbitrary'])->assertUnprocessable();
        $id = $this->post($base, $this->evidence())->assertCreated()->assertJsonPath('data.status', 'pending')
            ->assertJsonMissingPath('data.document_storage_key')->json('data.id');
        $this->post($base, $this->evidence())->assertConflict();
        $this->assertCount(1, Storage::disk('local')->allFiles('editor-verification'));
        $this->getJson($base)->assertOk()->assertJsonPath('data.0.id', $id);
        $this->getJson("{$base}/status")->assertOk()->assertJsonPath('data.latest_decision', null);
        $this->getJson("{$base}/{$id}")->assertOk();
        $this->get("{$base}/{$id}/document")->assertOk()->assertDownload('verification-document.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->postJson("{$staff}/{$id}/decisions", ['decision' => 'approve'])->assertForbidden();
        $this->signIn(User::factory()->editor()->create());
        $this->get("{$base}/{$id}/document")->assertNotFound();
        $this->signIn(User::factory()->support()->create());
        $this->getJson($staff)->assertForbidden();

        $admin = $this->signIn(User::factory()->admin()->create());
        $this->getJson($staff)->assertOk()->assertJsonPath('data.0.id', $id);
        $this->getJson("{$staff}/{$id}")->assertOk();
        $this->get("{$staff}/{$id}/document")->assertOk()->assertDownload();
        $this->postJson("{$staff}/{$id}/decisions", ['decision' => 'reject', 'reason' => 'Evidence incomplete'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->postJson("{$staff}/{$id}/decisions", ['decision' => 'approve'])->assertConflict();
        $this->assertDatabaseHas('editor_verification_requests', ['id' => $id, 'reviewed_by_user_id' => $admin->id]);

        $this->signIn($editor);
        $this->getJson("{$base}/status")->assertJsonPath('data.latest_decision', 'rejected');
        $next = $this->post($base, $this->evidence())->assertCreated()->json('data.id');
        $this->signIn($admin);
        $this->travel(2)->seconds();
        $this->postJson("{$staff}/{$next}/decisions", ['decision' => 'approve'])->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('users', ['id' => $editor->id, 'status' => 'pending_verification']);
        $this->signIn($editor);
        $this->getJson("{$base}/status")->assertJsonPath('data.latest_decision', 'approved');
        $this->post($base, $this->evidence())->assertConflict();
        $this->assertDatabaseCount('editor_verification_requests', 2);
    }

    private function evidence(): array
    {
        return ['document_type' => 'professional_evidence',
            'document' => UploadedFile::fake()->createWithContent('synthetic.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\n")];
    }
}
