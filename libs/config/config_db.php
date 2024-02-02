<?php

define('DB_DSN', 'mysql:dbname=' . $cDefinitions->getDb()["name"] . ';host=' . $cDefinitions->getDb()["host"] . ';port=' .$cDefinitions->getDb()["port"]);
define('DB_USER', $cDefinitions->getDb()["user"]);
define('DB_PASSWORD', $cDefinitions->getDb()["password"]);
define('DB_NAME', $cDefinitions->getDb()["name"]);
$MasterDbName = $cDefinitions->getMasterDb();
$ActiveProductId = $cDefinitions->getProductId();

$db_pdo = new Database();
$CurrentUser = new User();

UserModule::SetCurrentUserInfo();
UserModule::CheckUser();

$db_pdo->current_user_id = (isset($CurrentUser->id) && !empty($CurrentUser->id) ? $CurrentUser->id : 0);
