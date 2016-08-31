<?php

    require ('../includes/config.php');
    include_once('../includes/simple_html_dom.php');
    
    
    //get game names. $i index allows games to be drawn by id. this can allow targeted searches for individual games or sets of games.
    $games = [];
    $i = 555;

    while ($i < 559)
    {
        if(($temp = CS50::query('SELECT name FROM gameinfo WHERE id = ?', $i)) !== false)
        {
            $games [] = [
                "name" => $temp[0]["name"]
                ];
            $i++;
        }
        else
        {
            $i++;
        }
    }
    
    //pull data of each game from both websites
    foreach($games as $title)
    {
        //create variabels to store score data
        $gamespot_score = "";
        $metacritic_score = "";
        $ign_score = "";
        
        //build urls for scraper functions from game name. all urls use - for spaces, so the name must be reformatted before being inserted into urls
        $gameformated = preg_replace('/\s+/', "-", $title["name"]);
        $gamespot_url = "http://www.gamespot.com/" . $gameformated . "/";
        $ign_url_new = "http://www.ign.com/articles/" . $gameformated . "-review";
        $ign_url_old = "http://www.ign.com/articles/" . $gameformated;
        
        //create scraper dom used in both scraper functions
        $html = new simple_html_dom();
        
        
        
        
        //**************GAMESPOT/METACRITIC REVIEW SCRAPER FUNCTION**************
        if (get_http_response_code($gamespot_url) == "200")
        {   
            $html->load_file($gamespot_url);
            
            //get gamespot review. if absent, set to NA. 
            if ($html && is_object($html) && isset($html))
            {
                //get gamespot score
                $gamespot_score = $html->find('span[itemprop="ratingValue"]', 0);
                
                if (is_object($gamespot_score))
                {
                    $gamespot_score = $gamespot_score->plaintext;
                }
                
                //get metacritic review. 
                $metacritic_score  = $html->find('a[data-event-tracking="Tracking|games_overview|Kubrick|Metascore"]',0);
                
                if (is_object($metacritic_score))
                {
                    $metacritic_score = $metacritic_score->plaintext;
                }
            }
        }
       
        //clear dom in prep for loading of second website
        $html->clear();
        
        
        
        
        
        //**************IGN REVIEW SCRAPER FUNCTION**************
        
        //variable necessary to check for clean access to website. necessary for secondary checks for special formats
        $access = false;
        
        //check both the new(-review) and old (nothing) ign url formats for a hit
        if (get_http_response_code($ign_url_new) !== "404" && "502")
        {
            if ($html && is_object($html) && isset($html))
            {
                $html->load_file($ign_url_new);
        
                $ign_score = $html->find('span[class="score"]', 0);
                
                $ign_url_final = $ign_url_new;
                
                $access = true;
            }
        }
        else if (get_http_response_code($ign_url_old) !== "404" && "502")
        {
            if ($html && is_object($html) && isset($html))
            {
                $html->load_file($ign_url_old);
        
                $ign_score = $html->find('span[class="score"]', 0);
                
                $ign_url_final = $ign_url_old;
            
                $access = true;
            }
        }
    
        //if site is accessed but no score found, review is multiple pages long or is of a unique format. check for unique format first, then multipage format
        //1. unique format
        if ($ign_score == "" && $access == true)
        {
            $ign_score = preg_replace('/\s+/', "", $html->find('dd[class="game-rating-score]', 0));
        } 
        
        //2. multiple pages. to check subsequent pages of review, date of publication must be added to url. this is added and then
        if ($ign_score == "" && $access == true)
        {
            //date of publication must be included in url to access later pages of a review
            $date = $html->find('meta[itemprop="datePublished"]', 0);
            $dateformated = preg_replace('/-/', '/', (substr($date->content, 0, 10)));
            
            $temp_url = substr_replace($ign_url_final, $dateformated, 28, 0) . "?page=";
            
            unset($date);
            unset($dateformated);
            
            //cycle backwards through page requests to hit the final page of the review where the score is located. check page for all possible review storage locations.
            for ($i = 10; $i != 1; $i--)
            {
                $ign_url_final = $temp_url . $i;
          
                 if (get_http_response_code($ign_url_final) == "200")
                {
                    $html->clear();
                    $html->load_file($ign_url_final);
            
                    //scores are found withing different ids
                    if (($score = $html->find('span[class="score"]', 0)) != false)
                    {
                      $ign_score = $score;
                      break;
                    }
                    
                    if (($newscore1 = $html->find('dd[class="game-rating-score]', 0)) != false)
                    {
                      $ign_score = $newscore1;
                      break;
                    }
                    
                    if (($newscore2 = $html->find('span[itemprop="reviewRating"]', 0)) != false)
                    {
                      $ign_score = $newscore2;
                      break;
                    }
                    
                    if (($newscore3 = $html->find('span[itemprop="ratingValue"]', 0)) != false)
                    {
                      $ign_score = $newscore3->plaintext;
                      break;
                    }
                }
            }
        }
        
        //if IGN score is found, remove tags and space to leave only the content 
        if ($ign_score !== "")
        {
            $ign_score = preg_replace('/\s+/', '', (strip_tags($ign_score)));
        }
        
        
        //update games database
        CS50::query("UPDATE gameinfo SET ign_review = ?, gamespot_review = ?, metacritic_review = ? WHERE name = ?", $ign_score, $gamespot_score, $metacritic_score, $title["name"]);
        
        //print scores and memory usage for testing/verification
        print($title["name"]);
        print("<p>IGN: " . $ign_score . "</p>");
        print("<p>Gamespot: " . $gamespot_score . "</p>");
        print("<p>Metacritic: " . $metacritic_score . "</p>");
        print("Memory: ");
        echo (memory_get_usage(true));
        print("<br><br><br>");
        
        //free all variables to prevent leaks ($games is kept so that loop can continue);
        $vars = array_keys(get_defined_vars());
        
        if(($key = array_search("games", $vars)) !== false) 
        {
            unset($vars[$key]);
        }
        
        foreach($vars as $var)
        {
            unset($$var);
        }   unset($vars, $var);
    }
    
    unset($games);
?>