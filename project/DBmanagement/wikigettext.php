<?php

    require('helpers.php');
    include_once('simple_html_dom.php');
    
    $target_url = "https://en.wikipedia.org/wiki/Another_World_(video_game)";
    
    $html = new simple_html_dom();
    
    $html->load_file($target_url);
    
    $games= [];
    
    
    foreach($html->find('p') as $element) 
       echo $element->plaintext . '<br>';
       //echo $element->children[0]->plaintext . '<br>';
       
    print_r($games);
    
    
?>