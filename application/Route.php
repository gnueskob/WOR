<?php

require(__DIR__ . '/controller/User.php');

$app->group('/wor/user', $userRouter);
