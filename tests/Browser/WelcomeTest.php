<?php

declare(strict_types=1);

it('has welcome page', function (): void {
    $page = visit('/');

    $page->assertSee('QROO');
    $page->assertSee('QrooEMR');
    $page->assertSee('Inspire Creativity, Enrich Life');
});
