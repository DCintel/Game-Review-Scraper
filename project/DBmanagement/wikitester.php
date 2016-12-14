<?php

    //pulls all game names from the wikipedia database and loads them into SQL table
    
    require ('../includes/config.php');
    include_once('../includes/simple_html_dom.php');
    
    $target_url = "https://en.wikipedia.org/wiki/2000_in_video_gaming";
    
    $html = new simple_html_dom();
    
    $html->load_file($target_url);
    
    $i = 1;
    
    foreach($html->find('i') as $item)
    {
        foreach($item as $element)
        {
            if (is_object($element))
            {
                if ($i > 15)
                {
                    print("<br>" . $element->plaintext);
                }
                $i++;
            }
        }
    }

?>