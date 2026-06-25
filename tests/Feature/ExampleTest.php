<?php

test('the home route redirects to the dashboard', function () {
    $this->get(route('home'))->assertRedirect(route('dashboard'));
});
