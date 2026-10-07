<?php

it('defaults to the onair light theme', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-theme="onair"', false);
});

it('serves onair-dark with the dark class when appearance is dark', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get('/')
        ->assertOk()
        ->assertSee('data-theme="onair-dark"', false)
        ->assertSee('class="dark"', false);
});

it('serves a fixed daisyui theme from the theme cookie', function () {
    $this->withUnencryptedCookie('theme', 'dracula')
        ->get('/')
        ->assertOk()
        ->assertSee('data-theme="dracula"', false)
        ->assertSee('class="dark"', false);
});

it('never applies the dark class for a fixed light theme', function () {
    $this->withUnencryptedCookie('theme', 'nord')
        ->withUnencryptedCookie('appearance', 'dark')
        ->get('/')
        ->assertOk()
        ->assertSee('data-theme="nord"', false)
        ->assertDontSee('class="dark"', false);
});

it('falls back to onair for an unknown theme cookie', function () {
    $this->withUnencryptedCookie('theme', 'not-a-theme')
        ->get('/')
        ->assertOk()
        ->assertSee('data-theme="onair"', false);
});
