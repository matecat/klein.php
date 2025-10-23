<?php

$this->respond(
    path: '/?',
    callback: function ($request, $response, $app) {
        echo 'yup';
    }
);

$this->respond(
    path: '/testing/?',
    callback: function ($request, $response, $app) {
        echo 'yup';
    }
);
