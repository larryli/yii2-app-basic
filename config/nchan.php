<?php

return [
    'class' => app\components\Nchan::class,
    'baseUrl' => 'http://' . (getenv('NCHAN_HOST') ?: 'localhost') . ':9090/',
];
