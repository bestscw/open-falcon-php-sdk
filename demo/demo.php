<?php
include 'vendor/autoload.php';

use \Wildfire\OpenFalcon\OpenFalconClient;

    
    $host = "127.0.0.1";
    $user = "root";
    $passwd = "root";
    $client = new OpenFalconClient($host,$user,$passwd);
    $hostgroups = $client->hostGroups();
    $templates = $client->templates();
    
    var_dump(compact('hostgroups','templates'));
    

