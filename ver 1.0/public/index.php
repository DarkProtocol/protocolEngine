<?php

session_start(); //для токена csrf и логина

$config = require('../config.php');

require('../core/starter.php');

$app = new ProtocolEngine( $config );

