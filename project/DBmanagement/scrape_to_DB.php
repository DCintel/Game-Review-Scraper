<?php

    require ('../includes/config.php');
    include_once('../includes/simple_html_dom.php');
    
   // $_GET['id'] = 2;
    
    if (empty($_GET['id']))
    {
        exit;
    }
    
    $name = CS50::query('SELECT name FROM gameinfo WHERE id = ?', $_GET["id"]);
    $name = $name[0]['name'];
    
    
    //create variables to store score data
    $scores [] = [
        "ign" => "",
        "gamespot" => "",
        "metacritic" => "",
        ];
    
    
    
    //build urls for scraper functions from game name. all urls use - for spaces, so the name must be reformatted before being inserted into urls
    $gameformated = preg_replace('/\s+/', "-", $name);
    $gamespot_url = "http://www.gamespot.com/" . $gameformated . "/";
    $ign_url = "http://www.ign.com/articles/" . $gameformated;
    
        
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
            $scores[0]["gamespot"] = $html->find('span[itemprop="ratingValue"]', 0);
            
            if (is_object($scores[0]["gamespot"]))
            {
                $scores[0]["gamespot"] = preg_replace('/\s+/', '', ($scores[0]["gamespot"]->plaintext));
            }
            
            //get metacritic review. 
            $scores[0]["metacritic"]  = $html->find('a[data-event-tracking="Tracking|games_overview|Kubrick|Metascore"]',0);
            
            if (is_object($scores[0]["metacritic"]))
            {
                $scores[0]["metacritic"] = $scores[0]["metacritic"]->plaintext;
            }
        }
    }
    
    
    //if no scores found for metacritic or gamespot, set to "N/A"
    if ($scores[0]["gamespot"] == null || $scores[0]["gamespot"] == 0 || $scores[0]["gamespot"] == "")
        {
            $scores[0]["gamespot"] = "N/A";
        }
    
    if ($scores[0]["metacritic"] == null || $scores[0]["metacritic"] == 0 || $scores[0]["metacritic"] == "")
        {
            $scores[0]["metacritic"] = "N/A";
        }
            
            
    //clear dom in prep for loading of second website
    $html->clear();
        
        
        
        
        
    //**************IGN REVIEW SCRAPER FUNCTION**************
        
    //variable necessary to check for clean access to website. necessary for secondary checks for special formats
    $access = false;
    
    $ign_urls = [
        'new' => '-review',
        'old' => '',
        'pc' => '-pc-review',
        'wiiu' => '-wii-u-review',
        'series1' => '-1',
        'series2' => '-2',
        'series3' => '-3',
        ];    
        
    //check all possible review formats for a hit
    foreach($ign_urls as $url_tag)
    {
        $current_ign_url = $ign_url . $url_tag;
        
        if (get_http_response_code($current_ign_url) !== "404" && "502")
        {
           
            if ($html && is_object($html) && isset($html))
            {
                $html->load_file($current_ign_url);
        
                $scores[0]["ign"] = $html->find('span[class="score"]', 0);
                
                $ign_url_final = $current_ign_url;
                
                $access = true;
                
                break;
            }
        }
    }
    
    //if site is accessed but no score found, review is multiple pages long or is of a unique format. check for unique format first, then multipage format
    //1. unique format
    if ($scores[0]["ign"] == "" && $access == true)
    {
        $ign_score = preg_replace('/\s+/', "", $html->find('dd[class="game-rating-score]', 0));
    } 
        
    //2. multiple pages. to check subsequent pages of review, date of publication must be added to url. this is added and then
    if ($scores[0]["ign"] == "" && $access == true)
    {
        //date of publication must be included in url to access later pages of a review. Location of date is pulled first, then raw date is taken via substr. Finally, it is formatted to have /year/month/day/ structure as in the url.
        $date = $html->find('meta[itemprop="datePublished"]', 0);
        $dateformated = (preg_replace('/-/', '/', (substr($date->content, 0, 10)))) . "/";
        
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
                    $scores[0]["ign"] = $score;
                    break;
                }
                    
                if (($newscore1 = $html->find('dd[class="game-rating-score]', 0)) != false)
                {
                  $scores[0]["ign"] = $newscore1;
                  break;
                }
                    
                if (($newscore2 = $html->find('span[itemprop="reviewRating"]', 0)) != false)
                {
                  $scores[0]["ign"] = $newscore2;
                  break;
                }
                    
                if (($newscore3 = $html->find('span[itemprop="ratingValue"]', 0)) != false)
                {
                  $scores[0]["ign"] = $newscore3->plaintext;
                  break;
                }
            }
        }
    }
        
    //if IGN score is found, remove tags and space to leave only the content 
    if ($scores[0]["ign"] !== "")
    {
        $scores[0]["ign"] = preg_replace('/\s+/', '', (strip_tags($scores[0]["ign"])));
    }
    else
    {
        $scores[0]["ign"] = "N/A";
    }
  
    
    CS50::query('UPDATE gameinfo SET ign_review = ? WHERE name = ?', $scores[0]["ign"], $name);
    CS50::query('UPDATE gameinfo SET gamespot_review = ? WHERE name = ?', $scores[0]["gamespot"], $name);
    CS50::query('UPDATE gameinfo SET metacritic_review = ? WHERE name = ?', $scores[0]["metacritic"], $name);
     

    //output places as JSON (pretty-printed for debugging convenience)
    header("Content-type: application/json");
    print(json_encode($scores, JSON_PRETTY_PRINT));
?>