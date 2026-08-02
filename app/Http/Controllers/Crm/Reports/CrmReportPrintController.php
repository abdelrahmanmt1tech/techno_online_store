<?php

namespace App\Http\Controllers\Crm\Reports;

use App\Http\Controllers\Controller;
use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Support\Crm\CrmReportAccess;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use JsonException;

abstract class CrmReportPrintController extends Controller
{
    public const MAX_ROWS = 5000;

    abstract protected function reportTitle(): string;

    abstract protected function viewName(): string;

    abstract protected function permissionCheck(TenantUser $user): bool;

    /**
     * @return Collection<int, mixed>
     */
    abstract protected function rows(TenantUser $user, CrmReportFilters $filters): Collection;

    /**
     * @return array<string, mixed>
     */
    abstract protected function summary(TenantUser $user, CrmReportFilters $filters): array;

    public function __invoke(Request $request): View
    {
        abort_unless(Auth::check(), 403);

        // Temporary signed URL: rejects tampered (e.g. altered branch_id/filters) or expired links.
        // Permission + branch scope below are still enforced independently of the signature.
        abort_unless($request->hasValidSignature(), 403);

        $user = Auth::user();
        abort_unless($user instanceof TenantUser, 403);

        abort_unless($this->permissionCheck($user) && CrmReportAccess::canPrint($user), 403);

        $payload = $this->decodePayload((string) $request->query('p', ''));
        app()->setLocale($payload['locale'] ?? config('app.locale'));

        $printedById = isset($payload['printed_by_id']) ? (int) $payload['printed_by_id'] : null;
        if ($printedById !== null && (int) Auth::id() !== $printedById) {
            abort(403);
        }

        $tableFilters = is_array($payload['table_filters'] ?? null) ? $payload['table_filters'] : [];
        $filters = CrmReportFilters::fromTableFilters($tableFilters, $this->defaultDateBasis());

        $rows = $this->rows($user, $filters);

        return view($this->viewName(), [
            'rows' => $rows,
            'summary' => $this->summary($user, $filters),
            'summaryLines' => CrmReportFilters::summarizeForPrint($tableFilters, $this->defaultDateBasis()),
            'rowCount' => $rows->count(),
            'maxRows' => self::MAX_ROWS,
            'printedBy' => (string) ($payload['printed_by'] ?? '-'),
            'printedAt' => Carbon::now(),
            'reportTitle' => $this->reportTitle(),
        ]);
    }

    protected function defaultDateBasis(): string
    {
        return 'created_at';
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodePayload(string $token): array
    {
        if ($token === '') {
            abort(400, 'Missing print payload');
        }

        $json = base64_decode(strtr($token, '-_', '+/'), true);
        if ($json === false) {
            abort(400, 'Invalid print payload');
        }

        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException) {
            abort(400, 'Invalid print payload');
        }

        return is_array($decoded) ? $decoded : [];
    }
}
