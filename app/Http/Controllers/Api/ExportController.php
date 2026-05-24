<?php

namespace App\Http\Controllers\Api;

use App\Http\Filters\ReadingFilter;
use App\Http\Requests\ExportReadingsRequest;
use App\Http\Resources\ReadingResource;
use App\Models\Reading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GET /api/readings/export — fills the Part-1 gap left by the Django port.
 *
 * Accepts ?format=json (default) or ?format=csv. Reuses ReadingFilter
 * for the same tag-based filtering the index endpoint supports, and adds
 * an optional ?anemometer= scope that the comments in api.php hinted at.
 */
class ExportController extends Controller
{
    public function export(ExportReadingsRequest $request): JsonResponse|StreamedResponse
    {
        $query = $this->buildQuery($request);
        $format = $request->validated('format', 'json');

        return $format === 'csv'
            ? $this->exportCsv($query)
            : $this->exportJson($query);
    }

    /**
     * @return Builder<Reading>
     */
    protected function buildQuery(ExportReadingsRequest $request): Builder
    {
        $query = Reading::query()->with(['tags', 'anemometer']);

        $query = ReadingFilter::apply($query, $request->only(['tags_any', 'tags_exact']));

        if ($anemometerId = $request->validated('anemometer')) {
            $query->where('anemometer_id', $anemometerId);
        }

        return $query;
    }

    /**
     * JSON — uses ReadingResource so the shape matches /api/readings.
     */
    protected function exportJson(Builder $query): JsonResponse
    {
        $readings = $query->get();

        $data = $readings->map(
            fn (Reading $r) => (new ReadingResource($r))->resolve(),
        )->values()->all();

        return response()->json($data);
    }

    /**
     * CSV — streamed so we don't blow memory on large datasets.
     */
    protected function exportCsv(Builder $query): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="readings_export.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'id',
                'speed',
                'recorded_at',
                'anemometer_id',
                'anemometer_name',
                'tags',
            ]);

            $query->lazy(500)->each(function (Reading $reading) use ($handle) {
                fputcsv($handle, [
                    $reading->id,
                    $reading->speed,
                    optional($reading->recorded_at)->toJSON(),
                    $reading->anemometer_id,
                    optional($reading->anemometer)->name,
                    $reading->tags->pluck('name')->implode(','),
                ]);
            });

            fclose($handle);
        }, 200, $headers);
    }
}
