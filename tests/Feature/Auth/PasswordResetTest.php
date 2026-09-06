<?php

test('password reset is unavailable until email delivery is configured', function () {
    config(['auth.password_reset_enabled' => false]);

    $this->get('/forgot-password')->assertNotFound();
    $this->post('/forgot-password')->assertNotFound();
});
