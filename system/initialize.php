<?php

//store this application configuration and free memory
Drivers\Registry::setConfig($config);

//free memory of this variable
//unset($config);

//store the database settings in the registry and free memory
Drivers\Registry::setSettings($database);

//free memory of this variable
//unset($database);

//store the request URI and free the memory
Drivers\Registry::setUrl( ( isset($_GET['url']) ) ? $_GET['url'] : '');
