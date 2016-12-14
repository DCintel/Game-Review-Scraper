<?php

    require ('../includes/config.php');
    
    //prepare response and user id to store as cookie
    $response [] = [
        "message" => "",
        "id" => ""
        ];
    
    //if passed to without username or password, reject and send error message.
    if(empty($_GET['username']) || empty($_GET['password']))
    {
        $response[0]["message"] = "no username provided";
        $response[0]["id"] = 0;

    }
    
    //get username and password from database.
    $rows = CS50::query("SELECT * FROM users WHERE username = ?", $_GET["username"]);
    
    //if count of rows returns 1, username exists. If so, check that password matches. Return appropriate response and user id to main site.
    if (count($rows) == 1)
    {
        $row = $rows[0];
        
        if (password_verify($_GET["password"], $row["password"]))
        {
            $response[0]["message"] = "login";
            $response[0]["id"] = $row["id"];
        }
        else
        {
            $response[0]["message"] = "password incorrect";
            $response[0]["id"] = "0";
        }
    }
    else
    {
        $response[0]["message"] = "username does not exist";
        $response[0]["id"] = "0";
    }
  
    //output response as JSON (pretty-printed for debugging convenience)
    header("Content-type: application/json");
    print(json_encode($response, JSON_PRETTY_PRINT));
?>
