<?php

$app->group('/api/v1', function () use ($app): void {
    // $app->get('/albums/{band}', SpotifyController::class . ':index');
    $app->get('/albums', SpotifyController::class . ':index');
});