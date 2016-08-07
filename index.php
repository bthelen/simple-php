<?php

// https://github.com/cloudfoundry-community/cf-helper-php
require_once __DIR__ .'/../lib/vendor/autoload.php';
use CfCommunity\CfHelper\CfHelper;

use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\Stomp;


echo "### APACHE1 ####";
var_dump($_REQUEST);
var_dump($_SERVER);
var_dump($_GET);
var_dump($_POST);
echo "### APACHE ####";

/////////////////////////////////////////////////////////
// CF
/////////////////////////////////////////////////////////
$cfHelper = CfHelper::getInstance();

$applicationInfo = $cfHelper->getApplicationInfo();
$version = $applicationInfo->getVersion();
$name = $applicationInfo->getName();
$uris = $applicationInfo->getUris();

echo "############### ", $version, $name, " ############### ";

/////////////////////////////////////////////////////////
// REDIS
/////////////////////////////////////////////////////////


$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host' => '10.0.16.66',
    'port' => '34569',
    'password' => '0fee886c-a121-41fc-92ec-37fdb4e48c56']);
echo "Connected to Redis";

//$redis = CfHelper::getInstance()->getRedisConnector()->getConnection();
$redis->set("foo", "bar");
$value = $redis->get("foo");
var_dump($value);

/////////////////////////////////////////////////////////
// ORACLE
/////////////////////////////////////////////////////////

$DB = 'ec2-54-149-58-221.us-west-2.compute.amazonaws.com:49161/Xe';
$DB_USER = 'system';
$DB_PASS = 'oracle';
$DB_CHAR = 'AL32UTF8';

//$conn = oci_connect($DB_USER, $DB_PASS, $DB, $DB_CHAR);
$conn = oci_connect($DB_USER, $DB_PASS, $DB);
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}
echo " ### ORACLE ### - connected";

$statement = oci_parse($conn, 'select * from error_log where ROWNUM <= 100 order by logdate desc');
oci_execute($statement);

echo "<table border='1'>\n";
while ($row = oci_fetch_array($statement, OCI_ASSOC+OCI_RETURN_NULLS)) {
    echo "<tr>\n";
    foreach ($row as $item) {
        echo "    <td>" . ($item !== null ? htmlentities($item, ENT_QUOTES) : "&nbsp;") . "</td>\n";
    }
    echo "</tr>\n";
}
echo "</table>\n";

//oci_close($conn);

/////////////////////////////////////////////////////////
// STOMP
/////////////////////////////////////////////////////////

// make a connection
$con = new Client('tcp://54.149.58.221:61613');
$con->setLogin("producer_login", "producer_password");
//$con->setVhostname("3551cee2-5f94-4b46-b9b7-0cbb909bfcfc");

//$con->setLogin("376fb158-3812-41ca-a004-3a4a1e3111d2", "8iilcmu1kfo9oeveaslidvpjgv");
//$con->setVhostname("3551cee2-5f94-4b46-b9b7-0cbb909bfcfc");

// connect
try {
    $con->connect();
} catch (StompException $e) {
    echo "dejan cannot connect\n";
    echo $e->getMessage() . "\n";
}

// send a message to the queue
try {
    $con->connect();
    $con->send('/queue/test', 'test');
    echo "Guest sent message with body 'test'\n";
} catch (StompException $e) {
    echo "guest cannot send\n";
    echo $e->getMessage() . "\n";
}

// disconnect
$con->disconnect();

/////////////////////////////////////////////////////////
// Reading VCAP_SERVICES
/////////////////////////////////////////////////////////


   function rabbit($service_blob, $rb_protocol) {
            $services = getProvider($service_blob, "p-rabbitmq");
            $service = $services[0]['credentials']['protocols'][$rb_protocol];
            return $service;
    }

   function redis($service_blob) {
            $services = getProvider($service_blob, "p-redis");
            $service = $services[0]['credentials'];
            return $service;
    }

   function user_provided($service_blob) {
            $services = getProvider($service_blob, "user-provided");
            $service = $services[0]['credentials'];
            return $service;
    }

	// returns the first service of a service provider
    function getProvider($service_blob, $name) {
            foreach($service_blob as $service_provider => $service_list) {
                if ($service_provider === $name) {
                    return $service_list;
                }
            }
    }


	$service_blob = json_decode($_ENV['VCAP_SERVICES'], true);

	echo "RabbitMQ: ", print_r(rabbit($service_blob, 'stomp')), "\r\n";
	echo "Redis: ", print_r(redis($service_blob)), "\r\n";
	echo "User Provided: ", print_r(user_provided($service_blob)), "\r\n";

	//phpinfo();
?>
