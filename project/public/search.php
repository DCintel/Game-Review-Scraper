<?php

    require ('../includes/config.php');
    
    $names = [];
             
    $names = CS50::query("SELECT name FROM gameinfo WHERE MATCH(name) AGAINST (?)", $_GET['name']);
    
    // output places as JSON (pretty-printed for debugging convenience)
    header("Content-type: application/json");
    print(json_encode($names, JSON_PRETTY_PRINT));
?>