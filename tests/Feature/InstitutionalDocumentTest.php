<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can inspect canonical institutional documents', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('institutional-documents.show', [
        'document' => 'vision/firm-thesis',
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Documents/Show')
            ->where('document.title', 'Firm Thesis')
            ->where('document.source_path', 'docs/vision/firm-thesis.md')
            ->where('document.key', 'vision/firm-thesis')
            ->where('document.html', fn (string $html): bool => str_contains($html, '<h1>Firm Thesis</h1>'))
        );
});

test('unknown or traversal document paths are rejected', function (string $document) {
    $this->actingAs(User::factory()->create());

    $this->get('/documents/'.$document)->assertNotFound();
})->with([
    'unknown document' => 'vision/not-a-document',
    'repository traversal' => '../composer',
]);

test('relative Markdown links may retain their source extension', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/documents/architecture/partnership-compiler.md')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Documents/Show')
            ->where('document.key', 'architecture/partnership-compiler')
        );
});
