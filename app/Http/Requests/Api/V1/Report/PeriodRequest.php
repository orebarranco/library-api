<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Report;

use App\Enums\ReportPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The `?period=` filter shared by every report that can be narrowed to a
 * rolling window.
 */
final class PeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'string', Rule::enum(ReportPeriod::class)],
        ];
    }

    /**
     * The window to report on, or null when the report covers all time.
     */
    public function period(): ?ReportPeriod
    {
        return $this->enum('period', ReportPeriod::class);
    }
}
