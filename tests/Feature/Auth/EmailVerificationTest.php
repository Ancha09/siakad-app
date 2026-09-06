<?php

test('unused email verification routes are disabled', function () {
    $this->get('/verify-email')->assertNotFound();
    $this->post('/email/verification-notification')->assertNotFound();
});
