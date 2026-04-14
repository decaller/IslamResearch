<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('takes screenshots of the Scholar IDE in multiple resolutions', function () {
    $page = $this->visit('/scholar');
    
    $page->assertSee('Scholar IDE')
        ->screenshot(filename: 'scholar-desktop-full');
        
    $page->resize(375, 812)
        ->screenshot(filename: 'scholar-mobile-view');
});

it('takes a screenshot of the Filament Admin Dashboard after login', function () {
    $user = User::factory()->create([
        'email' => 'admin@islamresearch.com',
        'password' => bcrypt('password'),
        'role' => \App\Enums\UserRole::Admin,
    ]);

    // Note: Filament might need specific authorization which is handled in regular tests
    // Here we just want a visual smoke test.
    $page = $this->actingAs($user)->visit('/admin');

    $page->assertSee('Dashboard')
        ->screenshot(filename: 'admin-dashboard');
});
