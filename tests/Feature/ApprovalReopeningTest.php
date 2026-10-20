<?php

namespace Tests\Feature;

use App\Mail\ContractApprovedMail;
use App\Models\Approval;
use App\Models\ApprovalReopening;
use App\Models\Category;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use App\Notifications\ApprovalReopened;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalReopeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $lawyer;

    private User $approver;

    private User $otherApprover;

    private Contract $contract;

    private ContractVersion $version;

    private Approval $approved;

    private Approval $pending;

    protected function migrateFreshUsing(): array
    {
        return [
            '--realpath' => true,
            '--path' => array_values(array_filter(glob(database_path('migrations/*.php')), fn ($file) => ! str_contains(basename($file), 'brand'))),
        ];
    }

    protected function beforeRefreshingDatabase(): void
    {
        Event::listen(MigrationStarted::class, function (MigrationStarted $event) {
            $file = (new \ReflectionClass($event->migration))->getFileName();
            if (DB::getDriverName() === 'sqlite' && $event->method === 'up'
                && basename($file) === '2025_11_10_221000_drop_legacy_role_permission_columns.php'
                && ! Schema::hasIndex('permissions', 'permissions_nombre_unique')) {
                Schema::table('permissions', fn (Blueprint $table) => $table->unique('nombre', 'permissions_nombre_unique'));
            }
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
        Mail::fake();
        foreach (['admin', 'abogado', 'aprobador'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->lawyer = User::factory()->create()->assignRole('abogado');
        $this->approver = User::factory()->create()->assignRole('aprobador');
        $this->otherApprover = User::factory()->create()->assignRole('aprobador');
        $category = Category::create(['nombre' => 'Contratos', 'slug' => 'contratos']);
        $this->contract = Contract::forceCreate([
            'codigo' => 'LEX-'.Str::random(8), 'titulo' => 'Contrato de prueba',
            'categoria_id' => $category->id, 'creado_por' => $this->admin->id,
            'abogado_id' => $this->lawyer->id, 'estado' => Contract::STATUS_APPROVAL,
            'document' => 'documents/version-2.docx',
        ]);
        ContractVersion::create([
            'contract_id' => $this->contract->id, 'numero_version' => 1,
            'documento' => 'documents/version-1.docx', 'creado_por' => $this->admin->id, 'estado' => 'aprobado',
        ]);
        $this->version = ContractVersion::create([
            'contract_id' => $this->contract->id, 'numero_version' => 2,
            'documento' => 'documents/version-2.docx', 'creado_por' => $this->admin->id, 'estado' => 'borrador',
        ]);
        $this->approved = $this->version->approvals()->create([
            'user_id' => $this->approver->id, 'estado' => 'aprobado',
            'aprobado_at' => now()->subHour(), 'assigned_at' => now()->subDays(2), 'observaciones' => 'Conservar observación',
        ]);
        $this->pending = $this->version->approvals()->create([
            'user_id' => $this->otherApprover->id, 'estado' => 'pendiente', 'assigned_at' => now()->subDay(),
        ]);
    }

    public function test_admin_reopens_only_one_approval_without_changing_versions_or_contract(): void
    {
        $contractBefore = $this->contract->fresh()->getAttributes();
        $versionsBefore = $this->contract->versions()->orderBy('id')->get()->toArray();
        $pendingBefore = $this->pending->fresh()->getAttributes();
        $assignedAt = $this->approved->assigned_at->toISOString();
        $originalDate = $this->approved->aprobado_at->toISOString();

        $this->actingAs($this->admin)->post($this->url(), $this->payload())
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('pendiente', $this->approved->fresh()->estado);
        $this->assertNull($this->approved->fresh()->aprobado_at);
        $this->assertSame($assignedAt, $this->approved->fresh()->assigned_at->toISOString());
        $this->assertSame('Conservar observación', $this->approved->fresh()->observaciones);
        $this->assertSame($contractBefore, $this->contract->fresh()->getAttributes());
        $this->assertSame($versionsBefore, $this->contract->versions()->orderBy('id')->get()->toArray());
        $this->assertSame($pendingBefore, $this->pending->fresh()->getAttributes());
        $event = ApprovalReopening::sole();
        $this->assertSame($originalDate, $event->snapshot['approval_date']);
        $this->assertSame($this->admin->id, $event->admin_id);
        $this->assertSame($this->approved->id, $event->approval_id);
        $this->assertSame(2, $event->version_number);
        Notification::assertSentTo([$this->approver, $this->lawyer], ApprovalReopened::class);
        Mail::assertNothingSent();
    }

    public function test_only_admin_can_view_or_submit_corrections(): void
    {
        foreach ([$this->lawyer, $this->approver, $this->otherApprover] as $user) {
            $this->actingAs($user)->get(route('admin.approval-reopenings.index'))->assertForbidden();
            $this->actingAs($user)->postJson($this->url(), $this->payload())->assertForbidden();
        }
        $this->assertDatabaseCount('approval_reopenings', 0);
        $this->assertSame('aprobado', $this->approved->fresh()->estado);
    }

    public function test_guest_cannot_access_the_admin_action(): void
    {
        $this->get(route('admin.approval-reopenings.index'))->assertRedirect(route('login'));
        $this->postJson($this->url(), $this->payload())->assertUnauthorized();
    }

    public function test_admin_list_shows_only_eligible_contracts_and_keeps_history(): void
    {
        $this->actingAs($this->admin)->get(route('admin.approval-reopenings.index', ['q' => $this->contract->id]))
            ->assertOk()->assertViewHas('contracts', fn ($contracts) => $contracts->total() === 1)
            ->assertSee('Reabrir aprobación individual');
        $this->contract->update(['estado' => Contract::STATUS_APPROVED]);
        $this->get(route('admin.approval-reopenings.index', ['q' => $this->contract->id]))
            ->assertOk()->assertViewHas('contracts', fn ($contracts) => $contracts->total() === 0)
            ->assertDontSee('Reabrir aprobación individual');
    }

    #[DataProvider('blockedStates')]
    public function test_non_approval_contract_states_are_blocked(string $state): void
    {
        $this->contract->update(['estado' => $state]);
        $this->actingAs($this->admin)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertSame('aprobado', $this->approved->fresh()->estado);
        $this->assertSame($state, $this->contract->fresh()->estado);
        $this->assertDatabaseCount('approval_reopenings', 0);
        Notification::assertNothingSent();
    }

    public static function blockedStates(): array
    {
        return array_map(fn ($state) => [$state], [
            Contract::STATUS_APPROVED, Contract::STATUS_SIGNED, Contract::STATUS_OBSERVED,
            Contract::STATUS_CREATED, Contract::STATUS_ASSIGNED,
        ]);
    }

    #[DataProvider('signatureEvidence')]
    public function test_signature_evidence_blocks_both_list_and_submission(string $evidence): void
    {
        if ($evidence === 'document') {
            $this->contract->update(['document_signed' => 'signed/contract.pdf']);
        } elseif ($evidence === 'signer') {
            $this->contract->signers()->create(['user_id' => $this->lawyer->id, 'estado' => 'firmado', 'firmado_at' => now()]);
        } else {
            DB::table('signatures')->insert([
                'version_id' => $this->version->id, 'user_id' => $this->lawyer->id,
                'firmado' => $evidence === 'legacy_flag',
                'firmado_at' => $evidence === 'legacy_date' ? now() : null,
                'documento_firmado' => $evidence === 'legacy_document' ? 'signed/file.pdf' : null,
            ]);
        }
        $this->actingAs($this->admin)->get(route('admin.approval-reopenings.index'))
            ->assertOk()->assertViewHas('contracts', fn ($contracts) => $contracts->total() === 0);
        $this->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reopenings', 0);
    }

    public static function signatureEvidence(): array
    {
        return array_map(fn ($evidence) => [$evidence], ['document', 'signer', 'legacy_flag', 'legacy_date', 'legacy_document']);
    }

    public function test_old_versions_cannot_be_reopened_or_approved(): void
    {
        $old = $this->contract->versions()->where('numero_version', 1)->first()->approvals()->create([
            'user_id' => $this->approver->id, 'estado' => 'aprobado', 'aprobado_at' => now()->subDay(),
        ]);
        $this->actingAs($this->admin)->postJson($this->url($old), [
            ...$this->payload(), 'version_id' => $old->version_id, 'approval_date' => $old->aprobado_at->toISOString(),
        ])->assertUnprocessable();
        $old->update(['estado' => 'pendiente', 'aprobado_at' => null]);
        $this->actingAs($this->approver)->postJson(route('contracts.approvals.approve', [$this->contract, $old]))->assertUnprocessable();
        $this->assertSame('pendiente', $old->fresh()->estado);
        $this->assertDatabaseCount('approval_reopenings', 0);
    }

    public function test_unrelated_approval_is_not_found(): void
    {
        $other = Contract::forceCreate([
            'codigo' => 'OTHER-'.Str::random(8), 'titulo' => 'Otro contrato',
            'categoria_id' => $this->contract->categoria_id, 'creado_por' => $this->admin->id,
            'estado' => Contract::STATUS_APPROVAL,
        ]);
        $this->actingAs($this->admin)->postJson(route('admin.approval-reopenings.store', [$other, $this->approved]), $this->payload())->assertNotFound();
        $this->assertDatabaseCount('approval_reopenings', 0);
    }

    public function test_pending_approval_and_repeated_submission_cannot_be_reopened(): void
    {
        $payload = $this->payload();
        $this->actingAs($this->admin)->postJson($this->url($this->pending), $payload)->assertUnprocessable();
        $this->post($this->url(), $payload)->assertSessionHasNoErrors();
        $this->postJson($this->url(), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('approval_reopenings', 1);
    }

    public function test_stale_approval_timestamp_cannot_reopen_a_newer_decision(): void
    {
        $payload = $this->payload();
        $this->approved->update(['aprobado_at' => now()]);
        $this->actingAs($this->admin)->postJson($this->url(), $payload)->assertUnprocessable();
        $this->assertSame('aprobado', $this->approved->fresh()->estado);
        $this->assertDatabaseCount('approval_reopenings', 0);
    }

    public function test_completed_version_is_blocked_even_if_contract_state_is_inconsistent(): void
    {
        $this->version->update(['estado' => 'aprobado']);
        $this->actingAs($this->admin)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->version->update(['estado' => 'borrador']);
        $this->pending->update(['estado' => 'aprobado', 'aprobado_at' => now()]);
        $this->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reopenings', 0);
    }

    #[DataProvider('invalidInput')]
    public function test_reason_coordination_lawyer_and_confirmation_are_required(array $overrides): void
    {
        $this->actingAs($this->admin)->postJson($this->url(), [...$this->payload(), ...$overrides])->assertUnprocessable();
        $this->assertDatabaseCount('approval_reopenings', 0);
    }

    public static function invalidInput(): array
    {
        return [
            [['reason' => '']], [['reason' => 'breve']], [['coordination_reference' => '']],
            [['lawyer_id' => 999999]], [['confirmation' => '0']], [['version_id' => 999999]],
        ];
    }

    public function test_coordinator_must_have_the_lawyer_role(): void
    {
        $this->actingAs($this->admin)->postJson($this->url(), [...$this->payload(), 'lawyer_id' => $this->approver->id])->assertUnprocessable();
        $this->assertDatabaseCount('approval_reopenings', 0);
    }

    public function test_audit_is_rolled_back_if_approval_update_fails(): void
    {
        Approval::updating(function () {
            throw new \RuntimeException('Simulated write failure');
        });
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->admin)->post($this->url(), $this->payload());
            $this->fail('Expected write failure.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated write failure', $error->getMessage());
        } finally {
            Approval::flushEventListeners();
        }
        $this->assertDatabaseCount('approval_reopenings', 0);
        $this->assertSame('aprobado', $this->approved->fresh()->estado);
        Notification::assertNothingSent();
    }

    public function test_last_normal_approval_still_completes_contract_and_prevents_reopening(): void
    {
        $this->actingAs($this->otherApprover)->post(route('contracts.approvals.approve', [$this->contract, $this->pending]))
            ->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_APPROVED, $this->contract->fresh()->estado);
        $this->assertSame('aprobado', $this->version->fresh()->estado);
        $this->assertSame('documents/version-2.docx', $this->contract->fresh()->document);
        Mail::assertSent(ContractApprovedMail::class);
        $this->actingAs($this->admin)->postJson($this->url(), $this->payload())->assertUnprocessable();
    }

    public function test_reopened_approval_can_be_approved_again_on_the_same_version(): void
    {
        $this->actingAs($this->admin)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($this->approver)->post(route('contracts.approvals.approve', [$this->contract, $this->approved]))
            ->assertSessionHasNoErrors();
        $this->assertSame('aprobado', $this->approved->fresh()->estado);
        $this->assertSame(Contract::STATUS_APPROVAL, $this->contract->fresh()->estado);
        $this->assertSame(2, $this->contract->versions()->count());
        $this->assertDatabaseCount('approval_reopenings', 1);
    }

    public function test_normal_approval_cannot_change_a_contract_with_a_signed_document(): void
    {
        $this->contract->update(['document_signed' => 'signed/test.pdf', 'estado' => Contract::STATUS_SIGNED]);
        $this->actingAs($this->otherApprover)->postJson(route('contracts.approvals.approve', [$this->contract, $this->pending]))
            ->assertUnprocessable();
        $this->assertSame('pendiente', $this->pending->fresh()->estado);
        $this->assertSame('signed/test.pdf', $this->contract->fresh()->document_signed);
    }

    public function test_history_remains_visible_after_contract_is_approved(): void
    {
        $this->actingAs($this->admin)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->contract->update(['estado' => Contract::STATUS_APPROVED]);
        $this->get(route('admin.approval-reopenings.index'))
            ->assertOk()
            ->assertViewHas('contracts', fn ($contracts) => $contracts->total() === 0)
            ->assertViewHas('reopenings', fn ($events) => $events->total() === 1)
            ->assertSee('Aprobación registrada por equivocación.');
    }

    public function test_notification_failure_does_not_undo_the_recorded_correction(): void
    {
        $dispatcher = \Mockery::mock(\Illuminate\Contracts\Notifications\Dispatcher::class);
        $dispatcher->shouldReceive('send')->twice()->andThrow(new \RuntimeException('Simulated mail failure'));
        $this->app->instance(\Illuminate\Contracts\Notifications\Dispatcher::class, $dispatcher);

        $this->actingAs($this->admin)->post($this->url(), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'No se pudieron completar las notificaciones'));
        $this->assertDatabaseCount('approval_reopenings', 1);
        $this->assertSame('pendiente', $this->approved->fresh()->estado);
    }

    private function payload(): array
    {
        return [
            'version_id' => $this->version->id, 'approval_date' => $this->approved->fresh()->aprobado_at?->toISOString() ?? '',
            'lawyer_id' => $this->lawyer->id, 'reason' => 'Aprobación registrada por equivocación.',
            'coordination_reference' => 'Confirmado con abogado por ticket LEX-123.', 'confirmation' => '1',
        ];
    }

    private function url(?Approval $approval = null): string
    {
        return route('admin.approval-reopenings.store', [$this->contract, $approval ?? $this->approved]);
    }
}
