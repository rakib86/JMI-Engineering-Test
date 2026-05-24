<?php

/**
 * Tests for GET /api/readings/export (Part-1 feature).
 *
 * Mirrors the conventions in ReadingTest — Pest closures,
 * actingAsUser() for auth, RefreshDatabase via Pest.php.
 */

use App\Models\Anemometer;
use App\Models\Reading;
use App\Models\Tag;

it('exports readings as json by default', function (): void {
    actingAsUser();
    Reading::factory()->count(3)->create();

    $response = $this->getJson('/api/readings/export');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/json');
    expect($response->json())->toHaveCount(3);
});

it('exports readings as json when format=json', function (): void {
    actingAsUser();
    Reading::factory()->count(2)->create();

    $response = $this->getJson('/api/readings/export?format=json');

    $response->assertOk();
    expect($response->json())->toHaveCount(2);

    // shape should match ReadingResource
    $first = $response->json()[0];
    expect($first)->toHaveKeys(['id', 'speed', 'recorded_at', 'tags']);
});

it('exports readings as csv', function (): void {
    actingAsUser();
    $anemometer = Anemometer::factory()->create(['name' => 'Station Alpha']);
    Reading::factory()->for($anemometer)->create(['speed' => 12.5]);

    $response = $this->get('/api/readings/export?format=csv');

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $response->assertHeader('content-disposition', 'attachment; filename="readings_export.csv"');

    $content = $response->streamedContent();
    $lines = array_filter(explode("\n", trim($content)));

    expect($lines)->toHaveCount(2); // header + 1 data row
    expect($lines[0])->toContain('id', 'speed', 'recorded_at', 'anemometer_id');
    expect($lines[1])->toContain('12.5', 'Station Alpha');
});

it('filters export by anemometer', function (): void {
    actingAsUser();
    $target = Anemometer::factory()->create();
    $other = Anemometer::factory()->create();

    Reading::factory()->for($target)->count(2)->create();
    Reading::factory()->for($other)->count(3)->create();

    $response = $this->getJson("/api/readings/export?anemometer={$target->id}");

    $response->assertOk();
    expect($response->json())->toHaveCount(2);
});

it('filters export by tags_any', function (): void {
    actingAsUser();

    $tagged = Reading::factory()->create();
    $tag = Tag::firstOrCreate(['name' => 'gusty']);
    $tagged->tags()->attach($tag);

    Reading::factory()->create(); // untagged, should be excluded

    $response = $this->getJson('/api/readings/export?tags_any=gusty');

    $response->assertOk();
    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['id'])->toBe($tagged->id);
});

it('returns empty array when no readings exist', function (): void {
    actingAsUser();

    $response = $this->getJson('/api/readings/export');

    $response->assertOk();
    expect($response->json())->toBeEmpty();
});

it('returns only header row for empty csv', function (): void {
    actingAsUser();

    $response = $this->get('/api/readings/export?format=csv');

    $response->assertOk();

    $content = $response->streamedContent();
    $lines = array_filter(explode("\n", trim($content)));

    expect($lines)->toHaveCount(1); // just the header
});

it('rejects invalid format parameter', function (): void {
    actingAsUser();

    $response = $this->getJson('/api/readings/export?format=xml');

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['format']);
});

it('rejects unauthenticated export request', function (): void {
    // Sanctum returns 401, Django would return 403
    $response = $this->getJson('/api/readings/export');

    $response->assertStatus(401);
});

it('rejects export with invalid anemometer uuid', function (): void {
    actingAsUser();

    $response = $this->getJson('/api/readings/export?anemometer=not-a-uuid');

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['anemometer']);
});
