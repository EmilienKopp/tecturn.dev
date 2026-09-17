<?php

declare(strict_types=1);

test('the page shell exposes Open Graph tags for social sharing', function () {
    $response = $this->get(route('home'))->assertSuccessful();

    $response->assertSee('<meta property="og:type" content="website">', false);
    $response->assertSee('<meta property="og:site_name"', false);
    $response->assertSee('<meta property="og:title"', false);
    $response->assertSee('<meta property="og:description"', false);
    $response->assertSee('<meta property="og:url" content="'.route('home').'"', false);
    $response->assertSee('<meta property="og:image" content="'.url('/og-image.png').'"', false);
});

test('the page shell exposes Twitter card tags', function () {
    $response = $this->get(route('home'))->assertSuccessful();

    $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    $response->assertSee('<meta name="twitter:title"', false);
    $response->assertSee('<meta name="twitter:description"', false);
    $response->assertSee('<meta name="twitter:image"', false);
});

test('the page shell exposes a meta description', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('<meta name="description"', false);
});
