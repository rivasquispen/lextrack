<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Category;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use App\Services\ApprovalAccessService;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Support\UsesContractTestDatabase;
use Tests\TestCase;

class ApprovalAccessTest extends TestCase
{
    use UsesContractTestDatabase;

    private User $lawyer;

    private User $advisor;

    private User $approver;

    private Contract $contract;

    private ContractVersion $version;

    private Approval $approval;

    private ArrayTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->startOfSecond());
        config(['mail.default' => 'array', 'mail.monitoring_bcc' => null, 'filesystems.default' => 'local']);
        Storage::fake('local');
        $this->transport = Mail::mailer('array')->getSymfonyTransport();
        foreach (['admin', 'abogado', 'asesor', 'aprobador', 'colaborador'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->lawyer = User::factory()->create(['nombre' => 'Abogada de prueba'])->assignRole('abogado');
        $this->advisor = User::factory()->create(['nombre' => 'Asesor de prueba'])->assignRole('asesor');
        $this->approver = User::factory()->create(['nombre' => 'Aprobador de prueba'])->assignRole('aprobador');
        $category = Category::create(['nombre' => 'Prueba', 'slug' => 'prueba']);
        $this->contract = Contract::forceCreate(['codigo' => 'LEX-'.Str::random(8), 'titulo' => 'Contrato ficticio', 'categoria_id' => $category->id,
            'creado_por' => $this->lawyer->id, 'abogado_id' => $this->lawyer->id, 'asesor_id' => $this->advisor->id, 'estado' => Contract::STATUS_APPROVAL]);
        $this->version = $this->contract->versions()->create(['numero_version' => 1, 'documento' => 'contracts/test.docx', 'creado_por' => $this->lawyer->id, 'estado' => 'borrador']);
        $this->approval = $this->version->approvals()->create(['user_id' => $this->approver->id, 'estado' => 'pendiente', 'assigned_at' => now()]);
    }

    private function lockUrl(?Approval $approval = null): string
    {
        return route('contracts.approvals.lock', [$this->contract, $approval ?? $this->approval]);
    }

    private function payload(bool $unlocked): array
    {
        return ['version_id' => $this->version->id, 'is_unlocked' => $unlocked];
    }

    public function test_new_assignments_start_locked_and_cannot_approve_or_upload_even_as_creator(): void
    {
        $this->assertFalse($this->approval->fresh()->is_unlocked);
        $this->assertSame(1, $this->approval->position);
        $this->contract->update(['creado_por' => $this->approver->id]);
        $this->actingAs($this->approver)->postJson(route('contracts.approvals.approve', [$this->contract, $this->approval]))->assertUnprocessable();
        $this->postJson(route('contracts.versions.store', $this->contract))->assertForbidden();
        $this->postJson(route('contracts.attachments.store', $this->contract))->assertForbidden();
        $this->deleteJson(route('contracts.attachments.destroy', $this->contract))->assertForbidden();
        $this->get(route('contracts.show', $this->contract))->assertOk()->assertSee('Pendiente de habilitación')->assertDontSee('Aprobar contrato');
        $this->assertSame('pendiente', $this->approval->fresh()->estado);
        $this->assertCount(0, $this->transport->messages());
    }

    public function test_assigned_advisor_unlocks_with_one_email_and_can_relock_and_unlock_again(): void
    {
        $this->actingAs($this->advisor)->patch($this->lockUrl(), $this->payload(true))->assertSessionHasNoErrors()
            ->assertRedirect(route('contracts.show', $this->contract).'#approval-flow');
        $this->assertTrue($this->approval->fresh()->is_unlocked);
        $this->assertCount(1, $this->transport->messages());
        $email = $this->transport->messages()->first()->getOriginalMessage();
        $this->assertSame($this->approver->email, $email->getTo()[0]->getAddress());
        foreach (['Asesor de prueba', 'Ya puedes aprobar el contrato', $this->contract->codigo, route('contracts.show', $this->contract), 'email-card'] as $text) {
            $this->assertStringContainsString($text, $email->getHtmlBody());
        }
        $this->patch($this->lockUrl(), $this->payload(true))->assertSessionHasNoErrors();
        $this->assertCount(1, $this->transport->messages());
        $this->patch($this->lockUrl(), $this->payload(false))->assertSessionHasNoErrors();
        $this->assertFalse($this->approval->fresh()->is_unlocked);
        $this->assertCount(1, $this->transport->messages());
        $this->patch($this->lockUrl(), $this->payload(true))->assertSessionHasNoErrors();
        $this->assertCount(2, $this->transport->messages());
        $this->assertDatabaseCount('approval_reminders', 0);
        $this->assertDatabaseCount('approval_reopenings', 0);
    }

    public function test_any_lawyer_can_manage_but_other_advisor_approver_and_admin_without_lawyer_role_cannot(): void
    {
        $otherLawyer = User::factory()->create()->assignRole('abogado');
        $this->actingAs($otherLawyer)->patch($this->lockUrl(), $this->payload(true))->assertSessionHasNoErrors();
        foreach (['asesor', 'aprobador', 'admin', 'colaborador'] as $role) {
            $other = User::factory()->create()->assignRole($role);
            $this->actingAs($other)->patchJson($this->lockUrl(), $this->payload(false))->assertForbidden();
            $this->patchJson(route('contracts.approvals.position', [$this->contract, $this->approval]), ['version_id' => $this->version->id, 'direction' => 'right'])->assertForbidden();
        }
        $this->assertTrue($this->approval->fresh()->is_unlocked);
        // The separate proposal to let advisors assign approvers remains unimplemented.
        $this->actingAs($this->advisor)->postJson(route('contracts.approvals.update', $this->contract), ['approvers' => [$this->approver->id]])->assertForbidden();
    }

    public function test_an_approved_user_cannot_be_locked_or_have_their_decision_changed(): void
    {
        $this->approval->update(['is_unlocked' => true, 'estado' => 'aprobado', 'aprobado_at' => now()]);
        $before = $this->approval->fresh()->getAttributes();
        $this->actingAs($this->advisor)->patchJson($this->lockUrl(), $this->payload(false))->assertUnprocessable();
        $this->assertSame($before, $this->approval->fresh()->getAttributes());
        $this->assertCount(0, $this->transport->messages());
    }

    #[DataProvider('closedContexts')]
    public function test_closed_or_signed_contexts_reject_both_lock_and_order_changes(string $context): void
    {
        if ($context === 'version') {
            $this->version->update(['estado' => 'aprobado']);
        } elseif ($context === 'document') {
            $this->contract->update(['document_signed' => 'signed/test.pdf']);
        } else {
            $this->contract->update(['estado' => $context]);
        }
        $this->actingAs($this->lawyer)->patchJson($this->lockUrl(), $this->payload(true))->assertUnprocessable();
        $this->patchJson(route('contracts.approvals.position', [$this->contract, $this->approval]), ['version_id' => $this->version->id, 'direction' => 'right'])->assertUnprocessable();
        $this->assertFalse($this->approval->fresh()->is_unlocked);
        $this->assertCount(0, $this->transport->messages());
    }

    public static function closedContexts(): array
    {
        return [['aprobado'], ['firmado'], ['version'], ['document']];
    }

    public function test_old_versions_and_wrong_contracts_are_rejected(): void
    {
        $otherVersion = $this->contract->versions()->create(['numero_version' => 2, 'documento' => 'contracts/test-2.docx', 'estado' => 'borrador', 'creado_por' => $this->lawyer->id]);
        $this->actingAs($this->advisor)->patchJson($this->lockUrl(), $this->payload(true))->assertUnprocessable();
        $foreign = Contract::forceCreate(['codigo' => 'OTRO-'.Str::random(8), 'titulo' => 'Otro', 'categoria_id' => $this->contract->categoria_id, 'creado_por' => $this->lawyer->id, 'estado' => Contract::STATUS_APPROVAL]);
        $this->actingAs($this->lawyer)->patchJson(route('contracts.approvals.lock', [$foreign, $this->approval]), ['version_id' => $otherVersion->id, 'is_unlocked' => true])->assertNotFound();
        $this->assertFalse($this->approval->fresh()->is_unlocked);
    }

    public function test_visual_reordering_does_not_unlock_or_change_approval_decisions(): void
    {
        $otherUser = User::factory()->create()->assignRole('aprobador');
        $second = $this->version->approvals()->create(['user_id' => $otherUser->id, 'estado' => 'aprobado', 'aprobado_at' => now(), 'is_unlocked' => true]);
        $this->assertSame(2, $second->position);
        $this->actingAs($this->advisor)->patch(route('contracts.approvals.position', [$this->contract, $second]), ['version_id' => $this->version->id, 'direction' => 'left'])->assertSessionHasNoErrors()
            ->assertRedirect(route('contracts.show', $this->contract).'#approval-flow');
        $this->assertSame([$second->id, $this->approval->id], $this->version->approvals()->pluck('approvals.id')->all());
        $this->assertFalse($this->approval->fresh()->is_unlocked);
        $this->assertSame('aprobado', $second->fresh()->estado);
        $this->assertCount(0, $this->transport->messages());
        $this->patch(route('contracts.approvals.position', [$this->contract, $second]), ['version_id' => $this->version->id, 'direction' => 'right'])->assertSessionHasNoErrors();
        $this->assertSame([$this->approval->id, $second->id], $this->version->approvals()->pluck('approvals.id')->all());
    }

    public function test_unlock_allows_revision_and_approval_and_relocking_revokes_upload_access(): void
    {
        app(ApprovalAccessService::class)->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->actingAs($this->approver)->post(route('contracts.versions.store', $this->contract), [
            'document' => UploadedFile::fake()->create('revision.docx', 5, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('contract_versions', 1);
        $this->assertSame($this->approver->id, $this->version->fresh()->histories()->latest('id')->first()->uploaded_by);
        app(ApprovalAccessService::class)->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, false);
        $this->postJson(route('contracts.versions.store', $this->contract))->assertForbidden();
        app(ApprovalAccessService::class)->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->post(route('contracts.approvals.approve', [$this->contract, $this->approval]))->assertSessionHasNoErrors();
        $this->assertSame('aprobado', $this->approval->fresh()->estado);
        $this->assertSame(Contract::STATUS_APPROVED, $this->contract->fresh()->estado);
    }

    public function test_migration_enables_existing_assignments_but_new_assignments_remain_locked(): void
    {
        $id = $this->approval->id;
        $migration = require database_path('migrations/2026_11_24_000000_add_lock_and_position_to_approvals.php');
        $migration->down();
        $migration->up();
        $this->assertTrue(Approval::findOrFail($id)->is_unlocked);
        $this->assertSame(1, Approval::findOrFail($id)->position);
        $new = $this->version->approvals()->create(['user_id' => User::factory()->create()->id, 'estado' => 'pendiente']);
        $this->assertFalse($new->fresh()->is_unlocked);
        $this->assertSame(2, $new->position);
    }

    public function test_formal_new_version_starts_locked_and_preserves_visual_order(): void
    {
        $this->approval->update(['is_unlocked' => true, 'position' => 3, 'estado' => 'aprobado', 'aprobado_at' => now()]);
        $this->version->update(['estado' => 'aprobado']);
        $this->contract->update(['estado' => Contract::STATUS_APPROVED]);
        Storage::put('contracts/test.docx', 'Fictitious document');
        $this->withoutExceptionHandling();
        $this->actingAs($this->lawyer)->post(route('contracts.observe', $this->contract))->assertRedirect()->assertSessionHasNoErrors();
        $new = $this->contract->versions()->orderByDesc('numero_version')->firstOrFail();
        $this->assertSame(2, $new->numero_version);
        $this->assertFalse($new->approvals()->sole()->is_unlocked);
        $this->assertSame(3, $new->approvals()->sole()->position);
        $this->assertTrue($this->approval->fresh()->is_unlocked);
        $this->assertSame('aprobado', $this->approval->fresh()->estado);
    }

    public function test_email_failure_preserves_unlocked_state_and_does_not_resend_on_replay(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Simulated SMTP failure'));
        $this->actingAs($this->advisor)->patch($this->lockUrl(), $this->payload(true))->assertSessionHasErrors('approval_access')
            ->assertRedirect(route('contracts.show', $this->contract).'#approval-flow');
        $this->assertTrue($this->approval->fresh()->is_unlocked);
        $this->patch($this->lockUrl(), $this->payload(true))->assertSessionHasNoErrors();
    }

    public function test_unlocked_approver_can_add_and_remove_attachments(): void
    {
        app(ApprovalAccessService::class)->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->actingAs($this->approver)->post(route('contracts.attachments.store', $this->contract), [
            'attachments' => [['name' => 'Adjunto de prueba', 'file' => UploadedFile::fake()->createWithContent('prueba.pdf', "%PDF-1.4\nFicticio\n%%EOF")]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $path = $this->version->fresh()->attachments[0]['path'];
        Storage::assertExists($path);
        $this->delete(route('contracts.attachments.destroy', $this->contract), ['attachment_path' => $path])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([], $this->version->fresh()->attachments);
        Storage::assertMissing($path);
    }

    public function test_assigned_advisor_sees_controls_but_approver_and_historical_versions_do_not(): void
    {
        $this->actingAs($this->advisor)->get(route('contracts.show', $this->contract))->assertOk()
            ->assertSee(route('contracts.approvals.lock', [$this->contract, $this->approval]), false)
            ->assertSee(route('contracts.approvals.position', [$this->contract, $this->approval]), false);
        $this->actingAs($this->approver)->get(route('contracts.show', $this->contract))->assertOk()
            ->assertDontSee(route('contracts.approvals.lock', [$this->contract, $this->approval]), false);
        $this->contract->versions()->create(['numero_version' => 2, 'documento' => 'contracts/second.docx', 'creado_por' => $this->lawyer->id, 'estado' => 'borrador']);
        $this->actingAs($this->advisor)->get(route('contracts.versions.show', [$this->contract, $this->version]))->assertOk()
            ->assertDontSee(route('contracts.approvals.lock', [$this->contract, $this->approval]), false)
            ->assertDontSee(route('contracts.approvals.position', [$this->contract, $this->approval]), false);
    }

    public function test_adding_an_approver_keeps_existing_lock_and_assigns_new_record_at_end_locked(): void
    {
        $this->approval->update(['is_unlocked' => true]);
        $other = User::factory()->create()->assignRole('aprobador');
        $this->actingAs($this->lawyer)->post(route('contracts.approvals.update', $this->contract), [
            'approvers' => [$other->id],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $new = $this->version->approvals()->where('user_id', $other->id)->sole();
        $this->assertFalse($new->is_unlocked);
        $this->assertSame(2, $new->position);
        $this->assertTrue($this->approval->fresh()->is_unlocked);
    }

    public function test_move_query_does_not_duplicate_order_columns_rejected_by_sql_server(): void
    {
        $second = $this->version->approvals()->create([
            'user_id' => User::factory()->create()->id, 'estado' => 'pendiente',
        ]);
        $queries = [];
        DB::listen(function ($event) use (&$queries) {
            if (str_contains($event->sql, 'from "approvals"') && str_contains($event->sql, 'order by')) {
                $queries[] = $event->sql;
            }
        });

        app(ApprovalAccessService::class)->move($this->advisor, $this->contract, $second, $this->version->id, 'left');

        $this->assertNotEmpty($queries);
        foreach ($queries as $sql) {
            $order = explode('order by', $sql, 2)[1];
            $columns = array_map(function ($expression) {
                $expression = preg_replace('/["\[\]]|\s+(asc|desc)\b/i', '', trim($expression));

                return strtolower(Str::afterLast($expression, '.'));
            }, explode(',', $order));
            $this->assertSame(['position', 'id'], $columns);
            $this->assertSame(count($columns), count(array_unique($columns)));
        }
        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $this->approval->fresh()->position);
    }

    public function test_dashboard_lists_only_unlocked_pending_approvers_of_the_current_version(): void
    {
        $this->approval->update(['is_unlocked' => true]);
        $newVersion = $this->contract->versions()->create(['numero_version' => 2, 'documento' => 'contracts/latest.docx', 'estado' => 'borrador', 'creado_por' => $this->lawyer->id]);
        $enabled = User::factory()->create(['nombre' => 'Habilitado vigente'])->assignRole('aprobador');
        $current = $newVersion->approvals()->create(['user_id' => $enabled->id, 'estado' => 'pendiente', 'is_unlocked' => true]);
        $newVersion->approvals()->create(['user_id' => User::factory()->create()->id, 'estado' => 'pendiente']);
        $newVersion->approvals()->create(['user_id' => User::factory()->create()->id, 'estado' => 'aprobado', 'aprobado_at' => now(), 'is_unlocked' => true]);
        $this->actingAs($this->advisor)->get(route('dashboard'))->assertOk()->assertSee('Aprobadores habilitados')->assertSee('Habilitado vigente')
            ->assertSee(route('contracts.show', $this->contract).'#approval-flow', false)
            ->assertViewHas('contractRecords', fn ($records) => $records->sole()['pending_approvers']->modelKeys() === [$current->id]);
    }

    public function test_dashboard_hides_approvers_for_finished_or_signed_contracts_and_keeps_visibility_scope(): void
    {
        $this->approval->update(['is_unlocked' => true]);
        $this->actingAs($this->advisor);
        foreach ([Contract::STATUS_APPROVED, Contract::STATUS_SIGNED] as $status) {
            $this->contract->update(['estado' => $status]);
            $this->get(route('dashboard'))->assertOk()->assertViewHas('contractRecords', fn ($records) => $records->sole()['pending_approvers']->isEmpty());
        }
        $this->contract->update(['estado' => Contract::STATUS_APPROVAL, 'document_signed' => 'signed/test.pdf']);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('contractRecords', fn ($records) => $records->sole()['pending_approvers']->isEmpty());
        $outsider = User::factory()->create()->assignRole('asesor');
        $this->actingAs($outsider)->get(route('dashboard'))->assertOk()->assertViewHas('contractRecords', fn ($records) => $records->isEmpty());
    }

    public function test_read_only_lock_icons_are_rendered_on_every_approver_card(): void
    {
        $other = User::factory()->create()->assignRole('aprobador');
        $this->version->approvals()->create(['user_id' => $other->id, 'estado' => 'pendiente', 'is_unlocked' => true]);
        $response = $this->actingAs($this->approver)->get(route('contracts.show', $this->contract))->assertOk();
        // Count only the state icons, not the two icons in the shared legend.
        $this->assertSame(2, substr_count($response->getContent(), 'data-approval-lock-icon'));
        $this->assertSame(1, substr_count($response->getContent(), 'aria-label="Significado de los candados"'));
    }

    public function test_dashboard_excludes_pending_actions_when_signature_evidence_exists(): void
    {
        $this->approval->update(['is_unlocked' => true]);
        $this->actingAs($this->advisor)->get(route('dashboard'))->assertOk()
            ->assertViewHas('contractRecords', fn ($records) => $records->sole()['pending_approvers']->isNotEmpty());
        $this->contract->signers()->create(['user_id' => $this->approver->id, 'estado' => 'firmado', 'firmado_at' => now()]);
        $this->get(route('dashboard'))->assertOk()
            ->assertViewHas('contractRecords', fn ($records) => $records->sole()['pending_approvers']->isEmpty());
        $this->contract->signers()->delete();
        DB::table('signatures')->insert(['version_id' => $this->version->id, 'user_id' => $this->approver->id, 'firmado' => true]);
        $this->get(route('dashboard'))->assertOk()
            ->assertViewHas('contractRecords', fn ($records) => $records->sole()['pending_approvers']->isEmpty());
    }

    public function test_enabled_clock_accumulates_only_open_intervals(): void
    {
        $service = app(ApprovalAccessService::class);
        $assignedAt = $this->approval->assigned_at->toISOString();
        $this->travel(2)->days();
        $this->assertSame(0, $this->approval->fresh()->totalEnabledSeconds());
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->travel(2)->days();
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, false);
        $this->assertSame(172800, $this->approval->fresh()->enabled_seconds);
        $this->assertNull($this->approval->fresh()->unlocked_at);
        $this->travel(3)->days();
        $this->assertSame(172800, $this->approval->fresh()->totalEnabledSeconds());
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->travel(1)->days();
        $this->assertSame(259200, $this->approval->fresh()->totalEnabledSeconds());
        $this->actingAs($this->advisor)->get(route('contracts.show', $this->contract))->assertOk()->assertSee('Tiempo habilitado: 3 días');
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, false);
        $this->travel(5)->days();
        $this->assertSame(259200, $this->approval->fresh()->totalEnabledSeconds());
        $this->assertSame($assignedAt, $this->approval->fresh()->assigned_at->toISOString());
    }

    public function test_repeated_lock_requests_do_not_reset_or_double_the_clock(): void
    {
        $service = app(ApprovalAccessService::class);
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $startedAt = $this->approval->fresh()->unlocked_at->toISOString();
        $this->travel(2)->hours();
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->assertSame($startedAt, $this->approval->fresh()->unlocked_at->toISOString());
        $this->assertSame(7200, $this->approval->fresh()->totalEnabledSeconds());
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, false);
        $this->travel(3)->hours();
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, false);
        $this->assertSame(7200, $this->approval->fresh()->totalEnabledSeconds());
        $this->assertCount(1, $this->transport->messages());
    }

    public function test_approval_stops_the_clock_and_exceptional_reversion_resumes_without_counting_the_gap(): void
    {
        $this->version->approvals()->create(['user_id' => User::factory()->create()->id, 'estado' => 'pendiente']);
        app(ApprovalAccessService::class)->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->travel(2)->hours();
        $this->actingAs($this->approver)->post(route('contracts.approvals.approve', [$this->contract, $this->approval]))->assertSessionHasNoErrors();
        $this->assertSame(7200, $this->approval->fresh()->enabled_seconds);
        $this->assertNull($this->approval->fresh()->unlocked_at);
        $this->travel(3)->days();
        $this->assertSame(7200, $this->approval->fresh()->totalEnabledSeconds());
        $admin = User::factory()->create()->assignRole('admin');
        app(\App\Services\ApprovalReopeningService::class)->reopen($admin, $this->contract, $this->approval, [
            'version_id' => $this->version->id, 'approval_date' => $this->approval->fresh()->aprobado_at->toISOString(),
            'lawyer_id' => $this->lawyer->id, 'reason' => 'Corrección ficticia', 'coordination_reference' => 'Prueba',
        ]);
        $this->travel(1)->hours();
        $this->assertSame(10800, $this->approval->fresh()->totalEnabledSeconds());
        $this->assertSame(7200, $this->approval->fresh()->enabled_seconds);
    }

    public function test_formal_version_change_freezes_previous_clock_and_starts_new_assignment_at_zero(): void
    {
        $this->contract->update(['estado' => Contract::STATUS_OBSERVED]);
        app(ApprovalAccessService::class)->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->travel(3)->hours();
        Storage::put('contracts/test.docx', 'Fictitious document');
        $this->actingAs($this->lawyer)->post(route('contracts.observe', $this->contract))->assertRedirect()->assertSessionHasNoErrors();
        $new = $this->contract->versions()->orderByDesc('numero_version')->firstOrFail()->approvals()->sole();
        $this->assertFalse($new->is_unlocked);
        $this->assertNull($new->unlocked_at);
        $this->assertSame(0, $new->totalEnabledSeconds());
        $this->travel(5)->days();
        $this->assertSame(10800, $this->approval->fresh()->totalEnabledSeconds());
        $this->assertNull($this->approval->fresh()->unlocked_at);
    }

    public function test_reminder_uses_accumulated_enabled_time_and_rejects_closed_candado(): void
    {
        $service = app(ApprovalAccessService::class);
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->travel(2)->days();
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, false);
        $this->travel(3)->days();
        $url = route('contracts.approvals.reminder', [$this->contract, $this->approval]);
        $this->actingAs($this->lawyer)->postJson($url, ['version_id' => $this->version->id])->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 0);
        $service->setUnlocked($this->advisor, $this->contract, $this->approval, $this->version->id, true);
        $this->travel(1)->days();
        $this->post($url, ['version_id' => $this->version->id])->assertSessionHasNoErrors();
        $reminder = \App\Models\ApprovalReminder::sole();
        $this->assertSame(3, $reminder->pending_days);
        $this->assertSame(259200, $reminder->snapshot['enabled_seconds']);
        $html = $this->transport->messages()->last()->getOriginalMessage()->getHtmlBody();
        $this->assertStringContainsString('3 días', $html);
        $this->assertStringContainsString('tiempo habilitado', $html);
    }

    public function test_clock_migration_measures_only_current_pending_assignments_from_its_execution(): void
    {
        $this->approval->update(['is_unlocked' => true, 'unlocked_at' => now()->subDays(12), 'assigned_at' => now()->subDays(12)]);
        $current = $this->contract->versions()->create(['numero_version' => 2, 'documento' => 'contracts/latest.docx', 'estado' => 'borrador', 'creado_por' => $this->lawyer->id]);
        $active = $current->approvals()->create(['user_id' => User::factory()->create()->id, 'estado' => 'pendiente', 'is_unlocked' => true]);
        $closed = $current->approvals()->create(['user_id' => User::factory()->create()->id, 'estado' => 'pendiente']);
        $approved = $current->approvals()->create(['user_id' => User::factory()->create()->id, 'estado' => 'aprobado', 'aprobado_at' => now(), 'is_unlocked' => true]);
        $before = DB::table('approvals')->select('id', 'version_id', 'is_unlocked', 'estado', 'aprobado_at', 'assigned_at', 'position', 'updated_at')->orderBy('id')->get()->toJson();
        $migration = require database_path('migrations/2026_11_25_000000_add_enabled_time_to_approvals.php');
        $migration->down();
        $migration->up();
        $this->assertSame(now()->toISOString(), $active->fresh()->unlocked_at->toISOString());
        $this->assertSame(0, $active->fresh()->totalEnabledSeconds());
        $this->assertNull($this->approval->fresh()->unlocked_at);
        $this->assertNull($closed->fresh()->unlocked_at);
        $this->assertNull($approved->fresh()->unlocked_at);
        $after = DB::table('approvals')->select('id', 'version_id', 'is_unlocked', 'estado', 'aprobado_at', 'assigned_at', 'position', 'updated_at')->orderBy('id')->get()->toJson();
        $this->assertSame($before, $after);
    }

    public function test_negative_or_future_clock_values_never_produce_negative_time(): void
    {
        $this->approval->forceFill(['is_unlocked' => true, 'unlocked_at' => now()->addHour(), 'enabled_seconds' => 120]);
        $this->assertSame(120, $this->approval->totalEnabledSeconds());
        $this->assertSame('0 minutos', Approval::formatEnabledSeconds(0));
        $this->assertSame('menos de un minuto', Approval::formatEnabledSeconds(30));
        $this->assertSame('2 días 1 hora', Approval::formatEnabledSeconds(176400));
    }
}
