<?php

namespace Tests\Feature;

use App\Mail\ApprovalReminderMail;
use App\Models\Approval;
use App\Models\ApprovalReminder;
use App\Models\ApprovalReopening;
use App\Models\Category;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use App\Services\ApprovalReminderService;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Support\UsesContractTestDatabase;
use Tests\TestCase;

class ApprovalReminderTest extends TestCase
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
        config(['mail.default' => 'array', 'mail.monitoring_bcc' => null]);
        $this->transport = Mail::mailer('array')->getSymfonyTransport();
        foreach (['admin', 'abogado', 'asesor', 'aprobador', 'colaborador'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->lawyer = User::factory()->create(['nombre' => 'Karen Abogada'])->assignRole('abogado');
        $this->advisor = User::factory()->create(['nombre' => 'Carlos Asesor'])->assignRole('asesor');
        $this->approver = User::factory()->create(['nombre' => 'Ana Aprobadora'])->assignRole('aprobador');
        $category = Category::create(['nombre' => 'Servicios', 'slug' => 'servicios']);
        $this->contract = Contract::forceCreate([
            'codigo' => 'LEX-'.Str::random(8), 'titulo' => 'Contrato para recordar',
            'categoria_id' => $category->id, 'creado_por' => $this->lawyer->id,
            'abogado_id' => $this->lawyer->id, 'asesor_id' => $this->advisor->id,
            'estado' => Contract::STATUS_APPROVAL, 'document' => 'contracts/test.docx',
        ]);
        $this->version = $this->contract->versions()->create([
            'numero_version' => 2, 'documento' => 'contracts/test.docx',
            'creado_por' => $this->lawyer->id, 'estado' => 'borrador',
        ]);
        $this->approval = $this->version->approvals()->create([
            'user_id' => $this->approver->id, 'estado' => 'pendiente',
            'assigned_at' => now()->subDays(12), 'is_unlocked' => true, 'unlocked_at' => now()->subDays(12), 'observaciones' => 'Conservar decisión',
        ]);
    }

    public function test_assigned_lawyer_sends_audited_mail_with_actual_name_and_pending_days(): void
    {
        $before = $this->contract->fresh()->getAttributes();
        $versionBefore = $this->version->fresh()->getAttributes();
        $approvalBefore = $this->approval->fresh()->getAttributes();

        $this->actingAs($this->lawyer)->post($this->url(), $this->payload())->assertSessionHasNoErrors()->assertRedirect();

        $event = ApprovalReminder::sole();
        $this->assertSame('sent', $event->status);
        $this->assertSame(12, $event->pending_days);
        $this->assertSame($this->lawyer->id, $event->sender_id);
        $this->assertSame($this->approver->id, $event->recipient_id);
        $this->assertNotNull($event->sent_at);
        $this->assertCount(1, $this->transport->messages());
        $email = $this->transport->messages()->first()->getOriginalMessage();
        $this->assertSame($this->approver->email, $email->getTo()[0]->getAddress());
        $this->assertStringContainsString('Karen Abogada', $email->getHtmlBody());
        $this->assertStringContainsString('12 días', $email->getHtmlBody());
        $this->assertStringContainsString($this->contract->codigo, $email->getHtmlBody());
        $this->assertStringContainsString(route('contracts.show', $this->contract), $email->getHtmlBody());
        $this->assertStringNotContainsString('Versión', $email->getHtmlBody());
        $this->assertSame($before, $this->contract->fresh()->getAttributes());
        $this->assertSame($versionBefore, $this->version->fresh()->getAttributes());
        $this->assertSame($approvalBefore, $this->approval->fresh()->getAttributes());
    }

    public function test_assigned_advisor_can_send_and_uses_their_own_name(): void
    {
        $this->actingAs($this->advisor)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($this->advisor->id, ApprovalReminder::sole()->sender_id);
        $this->assertStringContainsString('Carlos Asesor', $this->transport->messages()->first()->getOriginalMessage()->getHtmlBody());
    }

    #[DataProvider('unauthorizedRoles')]
    public function test_unassigned_users_and_roles_cannot_send(string $role): void
    {
        $user = User::factory()->create()->assignRole($role);
        $this->actingAs($user)->postJson($this->url(), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('approval_reminders', 0);
        $this->assertCount(0, $this->transport->messages());
    }

    public static function unauthorizedRoles(): array
    {
        return array_map(fn ($role) => [$role], ['asesor', 'aprobador', 'admin', 'colaborador']);
    }

    public function test_assignment_without_the_matching_role_is_not_enough(): void
    {
        $this->advisor->removeRole('asesor');
        $this->advisor->assignRole('colaborador');
        $this->actingAs($this->advisor)->postJson($this->url(), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('approval_reminders', 0);
    }

    public function test_guest_cannot_send(): void
    {
        $this->postJson($this->url(), $this->payload())->assertUnauthorized();
        $this->assertDatabaseCount('approval_reminders', 0);
    }

    public function test_daily_limit_is_shared_between_lawyer_and_advisor(): void
    {
        $this->actingAs($this->lawyer)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($this->advisor)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 1);
        $this->assertCount(1, $this->transport->messages());
        $this->travel(24)->hours();
        $this->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('approval_reminders', 2);
        $this->assertCount(2, $this->transport->messages());
    }

    public function test_each_approval_has_an_independent_daily_limit(): void
    {
        $other = User::factory()->create()->assignRole('aprobador');
        $approval = $this->version->approvals()->create([
            'user_id' => $other->id, 'estado' => 'pendiente', 'assigned_at' => now()->subDay(), 'is_unlocked' => true, 'unlocked_at' => now()->subDay(),
        ]);
        $this->actingAs($this->lawyer)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->post($this->url($approval), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('approval_reminders', 2);
    }

    public function test_an_in_progress_attempt_also_prevents_a_duplicate(): void
    {
        $this->createAttempt('sending');
        $this->actingAs($this->lawyer)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 1);
        $this->assertCount(0, $this->transport->messages());
    }

    public function test_mail_failure_is_recorded_without_changing_approval_and_is_not_retried_blindly(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Simulated SMTP failure'));
        $this->actingAs($this->lawyer)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $event = ApprovalReminder::sole();
        $this->assertSame('failed', $event->status);
        $this->assertSame(\RuntimeException::class, $event->failure_type);
        $this->assertNull($event->sent_at);
        $this->assertSame('pendiente', $this->approval->fresh()->estado);
        $this->actingAs($this->advisor)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 1);
    }

    public function test_auditing_failure_after_transport_acceptance_does_not_allow_resending(): void
    {
        ApprovalReminder::updating(function () {
            throw new \RuntimeException('Simulated result write failure');
        });
        try {
            $this->actingAs($this->lawyer)->postJson($this->url(), $this->payload())->assertUnprocessable();
        } finally {
            ApprovalReminder::flushEventListeners();
        }
        $this->assertCount(1, $this->transport->messages());
        $this->assertSame('sending', ApprovalReminder::sole()->status);
        $this->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertCount(1, $this->transport->messages());
    }

    public function test_no_mail_is_sent_if_the_initial_audit_cannot_be_saved(): void
    {
        ApprovalReminder::creating(function () {
            throw new \RuntimeException('Simulated initial write failure');
        });
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->lawyer)->post($this->url(), $this->payload());
            $this->fail('Expected audit write failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated initial write failure', $exception->getMessage());
        } finally {
            ApprovalReminder::flushEventListeners();
        }
        $this->assertDatabaseCount('approval_reminders', 0);
        $this->assertCount(0, $this->transport->messages());
    }

    public function test_recipient_without_a_valid_email_and_future_unlock_are_rejected(): void
    {
        $this->approver->update(['email' => 'invalid']);
        $this->actingAs($this->lawyer)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->approver->update(['email' => 'approver@example.test']);
        $this->approval->update(['unlocked_at' => now()->addDay()]);
        $this->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 0);
        $this->assertCount(0, $this->transport->messages());
    }

    #[DataProvider('blockedContractStates')]
    public function test_unavailable_contracts_do_not_generate_mail(string $state): void
    {
        $this->contract->update(['estado' => $state]);
        $this->actingAs($this->lawyer)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 0);
        $this->assertCount(0, $this->transport->messages());
    }

    public static function blockedContractStates(): array
    {
        return array_map(fn ($state) => [$state], [Contract::STATUS_APPROVED, Contract::STATUS_SIGNED, Contract::STATUS_CREATED, Contract::STATUS_ASSIGNED]);
    }

    public function test_observed_contract_with_pending_current_version_can_receive_a_reminder(): void
    {
        $this->contract->update(['estado' => Contract::STATUS_OBSERVED]);
        $this->version->update(['estado' => 'observado']);
        $this->actingAs($this->lawyer)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('approval_reminders', 1);
    }

    public function test_approved_decisions_and_approved_versions_are_blocked(): void
    {
        $this->approval->update(['estado' => 'aprobado', 'aprobado_at' => now()]);
        $this->actingAs($this->lawyer)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->approval->update(['estado' => 'pendiente', 'aprobado_at' => null]);
        $this->version->update(['estado' => 'aprobado']);
        $this->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 0);
    }

    public function test_signed_document_blocks_a_reminder_even_if_contract_state_is_inconsistent(): void
    {
        $this->contract->update(['document_signed' => 'signed/file.pdf']);
        $this->actingAs($this->lawyer)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 0);
    }

    public function test_older_version_or_stale_form_cannot_send(): void
    {
        $this->contract->versions()->create([
            'numero_version' => 3, 'documento' => 'contracts/version3.docx',
            'creado_por' => $this->lawyer->id, 'estado' => 'borrador',
        ]);
        $this->actingAs($this->lawyer)->postJson($this->url(), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 0);
    }

    public function test_approval_from_another_contract_is_rejected(): void
    {
        $other = Contract::forceCreate([
            'codigo' => 'OTHER-'.Str::random(8), 'titulo' => 'Otro contrato', 'categoria_id' => $this->contract->categoria_id,
            'creado_por' => $this->lawyer->id, 'abogado_id' => $this->lawyer->id, 'estado' => Contract::STATUS_APPROVAL,
        ]);
        $this->actingAs($this->lawyer)->postJson(route('contracts.approvals.reminder', [$other, $this->approval]), $this->payload())->assertNotFound();
        $this->assertDatabaseCount('approval_reminders', 0);
    }

    public function test_missing_version_is_validated(): void
    {
        $this->actingAs($this->lawyer)->postJson($this->url())->assertUnprocessable();
        $this->assertDatabaseCount('approval_reminders', 0);
    }

    public function test_assignment_is_rechecked_under_lock(): void
    {
        $other = User::factory()->create()->assignRole('asesor');
        Contract::whereKey($this->contract->id)->update(['asesor_id' => $other->id]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        try {
            app(ApprovalReminderService::class)->send($this->advisor, $this->contract, $this->approval, $this->version->id);
        } finally {
            $this->assertDatabaseCount('approval_reminders', 0);
        }
    }

    public function test_enabled_time_is_independent_of_reversion_history(): void
    {
        ApprovalReopening::create([
            'contract_id' => $this->contract->id, 'version_id' => $this->version->id, 'approval_id' => $this->approval->id,
            'approver_id' => $this->approver->id, 'admin_id' => $this->lawyer->id, 'lawyer_id' => $this->lawyer->id,
            'version_number' => 2, 'previous_status' => 'aprobado', 'previous_approved_at' => now()->subDays(2),
            'reason' => 'Corrección excepcional', 'coordination_reference' => 'Ticket prueba', 'snapshot' => [], 'created_at' => now()->subDay(),
        ]);
        $this->actingAs($this->lawyer)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(12, ApprovalReminder::sole()->pending_days);
    }

    public function test_missing_assignment_does_not_prevent_measuring_enabled_time(): void
    {
        $this->approval->forceFill(['assigned_at' => null, 'created_at' => now()->subDays(20), 'unlocked_at' => now()->subDays(3)])->save();
        $this->actingAs($this->lawyer)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(3, ApprovalReminder::sole()->pending_days);
    }

    #[DataProvider('elapsedDays')]
    public function test_elapsed_days_are_calculated_and_worded_correctly(int $hours, int $days, string $text): void
    {
        $this->approval->update(['unlocked_at' => now()->subHours($hours)]);
        $this->actingAs($this->lawyer)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $event = ApprovalReminder::sole();
        $this->assertSame($days, $event->pending_days);
        $html = (new ApprovalReminderMail($event))->render();
        $this->assertStringContainsString($text, $html);
    }

    public static function elapsedDays(): array
    {
        return [[5, 0, '5 horas'], [24, 1, '1 día'], [311, 12, '12 días']];
    }

    public function test_button_is_visible_to_all_lawyers_and_assigned_advisor_and_cooldown_is_shown(): void
    {
        $url = route('contracts.show', $this->contract);
        $this->actingAs($this->lawyer)->get($url)->assertOk()->assertSee('lucide-mail-warning', false);
        $this->actingAs($this->advisor)->get($url)->assertOk()->assertSee('lucide-mail-warning', false);
        $otherLawyer = User::factory()->create()->assignRole('abogado');
        $this->actingAs($otherLawyer)->get($url)->assertOk()->assertSee('lucide-mail-warning', false);
        $this->actingAs($this->approver)->get($url)->assertOk()->assertDontSee('lucide-mail-warning', false);
        $this->createAttempt('sent');
        $this->actingAs($this->lawyer)->get($url)->assertOk()->assertSee('Ya se registró un intento. Disponible desde');
    }

    public function test_unassigned_lawyer_can_send_even_when_contract_has_no_assigned_lawyer(): void
    {
        $otherLawyer = User::factory()->create(['nombre' => 'Otra Abogada'])->assignRole('abogado');
        $this->actingAs($otherLawyer)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($otherLawyer->id, ApprovalReminder::sole()->sender_id);
        $this->assertStringContainsString('Otra Abogada', $this->transport->messages()->first()->getOriginalMessage()->getHtmlBody());

        ApprovalReminder::query()->delete();
        $this->contract->update(['abogado_id' => null]);
        $this->actingAs($otherLawyer)->post($this->url(), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($otherLawyer->id, ApprovalReminder::sole()->sender_id);
    }

    public function test_button_is_hidden_on_approved_decisions_contracts_and_previous_versions(): void
    {
        $this->approval->update(['estado' => 'aprobado', 'aprobado_at' => now()]);
        $this->actingAs($this->lawyer)->get(route('contracts.show', $this->contract))->assertOk()->assertDontSee('lucide-mail-warning', false);
        $this->approval->update(['estado' => 'pendiente', 'aprobado_at' => null]);
        $this->contract->update(['estado' => Contract::STATUS_APPROVED]);
        $this->get(route('contracts.show', $this->contract))->assertOk()->assertDontSee('lucide-mail-warning', false);
        $this->contract->update(['estado' => Contract::STATUS_APPROVAL]);
        $this->contract->versions()->create([
            'numero_version' => 3, 'documento' => 'contracts/version3.docx', 'creado_por' => $this->lawyer->id, 'estado' => 'borrador',
        ]);
        $this->get(route('contracts.versions.show', [$this->contract, $this->version]))->assertOk()->assertDontSee('lucide-mail-warning', false);
    }

    private function createAttempt(string $status): ApprovalReminder
    {
        return ApprovalReminder::create([
            'contract_id' => $this->contract->id, 'version_id' => $this->version->id, 'approval_id' => $this->approval->id,
            'sender_id' => $this->lawyer->id, 'recipient_id' => $this->approver->id,
            'pending_days' => 12, 'status' => $status, 'snapshot' => [],
        ]);
    }

    private function url(?Approval $approval = null): string
    {
        return route('contracts.approvals.reminder', [$this->contract, $approval ?? $this->approval]);
    }

    private function payload(): array
    {
        return ['version_id' => $this->version->id];
    }
}
