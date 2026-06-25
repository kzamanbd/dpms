<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot view the status page', function () {
    $this->get(route('status.index'))->assertRedirect(route('login'));
});

test('the status page renders system checks that pass in the test environment', function () {
    $statusOf = fn (array $checks, string $label): ?string => collect($checks)->firstWhere('label', $label)['status'] ?? null;

    $this->actingAs(User::factory()->create())
        ->get(route('status.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('status/index')
            ->has('checks')
            ->has('summary.ok')
            ->has('summary.warn')
            ->has('summary.error')
            ->where('checks', function ($checks) use ($statusOf): bool {
                $all = collect($checks)->all();

                return $statusOf($all, 'PHP version') === 'ok'
                    && $statusOf($all, 'ext-sockets') === 'ok'
                    && $statusOf($all, 'Redis connection') === 'ok'
                    && $statusOf($all, 'Database connection') === 'ok'
                    && $statusOf($all, 'UDP datagram socket') === 'ok';
            })
        );
});
