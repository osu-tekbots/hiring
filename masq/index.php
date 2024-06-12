<?php
/**
 * This just links to the normal masquerade page, but is included for consistency with the
 * other TekBots websites that use the interface through /masq/index.php for local 
 * development.
 */

include_once '../bootstrap.php';
header('Location: '.$configManager->getBaseUrl().'pages/admin/user.php');