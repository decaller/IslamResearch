<?php

use Illuminate\Support\Facades\Session;

it('redirects to quran foundation oauth page', function () {
    $response = $this->get(route('auth.quran.redirect'));

    $response->assertRedirectContains('oauth2.quran.foundation');
    $response->assertSessionHas('oauth_state');
});

it('fails on callback with invalid state', function () {
    $response = $this->get(route('auth.quran.callback', [
        'state' => 'invalid_state',
        'code' => 'some_code',
    ]));

    $response->assertStatus(403);
});
