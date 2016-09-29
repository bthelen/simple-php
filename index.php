<html>
<head>
    <title>PHP Simple Application</title>
</head>

<body>

<?php

// https://github.com/cloudfoundry-community/cf-helper-php
require_once __DIR__ .'/vendor/autoload.php';
use CfCommunity\CfHelper\CfHelper;

use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\Stomp;

echo "<h1>TEST 4</h1>";

/////////////////////////////////////////////////////////
// CF
/////////////////////////////////////////////////////////
$cfHelper = CfHelper::getInstance();

try {
    //if we are in cloud foundry we use the connection given by cf-helper-php otherwise we use our database in local
    if ($cfHelper->isInCloudFoundry()) {
        echo "<h1>Running on Cloud Foundry</h1>";

        $applicationInfo = $cfHelper->getApplicationInfo();
        $name = $applicationInfo->getName();

        echo "<p><b>Application name -> </b>", $name, "</p>";
    } else {
        echo "<p><b>NOT</b> running on Cloud Foundry</p>";
    }
} catch (Exception $e) {
    die('Error : ' . $e->getMessage());
}

echo "<h2>services</h2><ul>";
$serviceManager = CfHelper::getInstance()->getServiceManager();
$services = $serviceManager->getAllServices();
foreach ($services as $key => $value) {
    echo "<li><b>{$key}</b> => ", print_r($value), "</li>";
}
echo "</ul>";

/////////////////////////////////////////////////////////
// REDIS
/////////////////////////////////////////////////////////

echo "<h2>Redis Connectivity - Using connector auto-detecting</h2>";
$redis = CfHelper::getInstance()->getRedisConnector()->getConnection();

/*
$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host' => '10.0.16.66',
    'port' => '34569',
    'password' => '0fee886c-a121-41fc-92ec-37fdb4e48c56']);
echo "Connected to Redis";
*/

$redis->set("foo", "bar");
$value = $redis->get("foo");
echo "<p><b>You should see bar</b>, foo -> <b>${value}</b></p>";

/////////////////////////////////////////////////////////
// ORACLE
/////////////////////////////////////////////////////////
echo "<h2>Oracle User Provided Services Connectivity</h2>"; 

$dbService = $serviceManager->getService('oracle');
echo "<ul>";
echo "<li>", $dbService->getValue('url'), "</li>";
echo "<li>", $dbService->getValue('username'), "</li>";
echo "<li>", $dbService->getValue('password'), "</li>";
echo "</ul>";

$DB = $dbService->getValue('url');
$DB_USER = $dbService->getValue('username');
$DB_PASS = $dbService->getValue('password');
$DB_CHAR = 'AL32UTF8';

echo "### $DB, $DB_USER, $DB_PASS ###";

$DB = 'ec2-54-149-58-221.us-west-2.compute.amazonaws.com:49161/Xe';
$DB_USER = 'system';
$DB_PASS = 'oracle';

//$conn = oci_connect($DB_USER, $DB_PASS, $DB, $DB_CHAR);
$conn = oci_connect($DB_USER, $DB_PASS, $DB);
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}
echo "<p> Some records from error_log table </p>";

$statement = oci_parse($conn, 'select * from error_log where ROWNUM <= 5 order by logdate desc');
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

oci_close($conn);

/////////////////////////////////////////////////////////
// STOMP
/////////////////////////////////////////////////////////
echo "<h2>RabbitMQ - Stomp</h2>"; 

echo "<p>######################## </p>";
$rabbit = $serviceManager->getService('rabbit')->getValue('protocols')['stomp'];
print_r($rabbit);

// make a connection
//$con = new Client('tcp://54.149.58.221:61613');
//$con->setLogin("producer_login", "producer_password");

echo "<p>########################</p> ";
echo "## ", $rabbit['host'], " ## ", $rabbit['port'];
$rabbitUrl = "tcp://" . $rabbit['host'] . ":" . $rabbit['port'];

$con = new Client($rabbitUrl);
$con->setLogin($rabbit['username'], $rabbit['password']);
$con->setVhostname($rabbit['vhost']);

// connect
try {
    $con->connect();
} catch (StompException $e) {
    echo "<p>dejan cannot connect</p>";
    echo $e->getMessage() . "\n";
}

// send a message to the queue
try {
    $con->connect();
    $con->send('/queue/test', 'test');
    echo "<p>Guest sent message with body 'test'</p>";
} catch (StompException $e) {
    echo "<p>guest cannot send</p>";
    echo "<p>", $e->getMessage(), "</p>";
}

// disconnect
$con->disconnect();

/////////////////////////////////////////////////////////
// Reading VCAP_SERVICES
/////////////////////////////////////////////////////////
echo "<h2>Reading VCAP_SERVICES</h2>"; 


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

    echo "<ul>";
	echo "<li>RabbitMQ: ", print_r(rabbit($service_blob, 'stomp')), "</li>";
	echo "<li>Redis: ", print_r(redis($service_blob)), "</li>";
	echo "<li>User Provided: ", print_r(user_provided($service_blob)), "</li>";
    echo "</ul>";

?>
</body>
</html>
