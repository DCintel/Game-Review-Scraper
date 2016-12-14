<?php

    require ('../includes/config.php');
    include_once('../includes/simple_html_dom.php');
    
    $badign;
    $badgs;
    $badmeta;
    
    
    
    $badign = CS50::query('SELECT name FROM gameinfo WHERE ign_review = 0');
    
    $badmeta = CS50::query('SELECT name FROM gameinfo WHERE metacritic_review = 0');
    
    $badgs = CS50::query('SELECT name FROM gameinfo WHERE gamespot_review = 0');
    
    
    
    print ("GAMES WITH NO IGN SCORE:");
    
    foreach($badign as $i)
    {
        print("<p>" . $i["name"] . "</p>");
    }
    print("<br> <br> <br>");
    
    print ("GAMES WITH NO GAMESPOT SCORE:");
    
    foreach($badgs as $i)
    {
        print("<p>" . $i["name"] . "</p>");
    }
    print("<br> <br> <br>");
    
    print ("GAMES WITH NO METACRITIC SCORE:");
    
    foreach($badmeta as $i)
    {
        print("<p>" . $i["name"] . "</p>");
    }
   
  
?>