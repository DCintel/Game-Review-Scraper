<?php

    require ('../includes/config.php');
    
    //prepare message to send back to site
    $response [] = [
        "message" => ""
        ];
     
     //exit with error if passed to without a username or password   
    if(empty($_GET['username']) || empty($_GET['password']))
    {
        $response["message"] = "no username provided";

    }
    
    //attempt to insert username and hashed password into database
    $result = CS50::query('INSERT IGNORE INTO users (username, password) VALUES (?, ?)', $_GET['username'], password_hash($_GET['password'], PASSWORD_DEFAULT));
    
    //If rejected, alert that username already exists, else alert that account was created.
    if ($result == 0)
    {
        $response["message"] = "Username already exists";
            
    }
    else
    {
        $response["message"] ="Account successfully created";
            
    }

    //output response as JSON (pretty-printed for debugging convenience)
    header("Content-type: application/json");
    print(json_encode($response, JSON_PRETTY_PRINT));
?>