<?php

/** @phpstan-ignore variable.undefined */
$this->respond(
    path: '/?',
    callback: function ($request, $response, $app) {
        echo 'yup';
    }
);

/** @phpstan-ignore variable.undefined */
$this->respond(
    path: '/testing/?',
    callback: function ($request, $response, $app) {
        echo 'yup';
    }
);
