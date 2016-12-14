<?php

    //pulls all game names from the wikipedia database and loads them into SQL table
    
    require ('../includes/config.php');
    include_once('../includes/simple_html_dom.php');
    
    $target_url = "https://en.wikipedia.org/wiki/2001_in_video_gaming";
    
    $html = new simple_html_dom();
    
    $html->load_file($target_url);
    
    $games= [];
    $games_final = [];
    $special_char_games = [];
    
    $i = 1;
    
    foreach($html->find('table') as $table)
    {
        if ($i === 9)
        {
            foreach($table->find('i') as $item)
            {
                $games[] = $item->plaintext;
                //print_r($games);
                //print("<br>" . $item->plaintext);
            }
        }   
        $i++;
    }
    
    //foreach($html->find('i') as $element) 
      // if ($element->plaintext !== "citation needed")
       //{
        //    $games[] =  $element->plaintext;
       //}
   
   $find = array("+", "&", "amp;", "/", "'", ":", "!", ".", ",");
   $replace = array("", "and", "", "", "", "", "", "", "");
   
    foreach($games as $game)
    {
        if (preg_match("/[:\+\&\'\!\.\/]/", $game))
        {
            $game = str_replace($find, $replace, $game);
        }
        
        $check = CS50::query('SELECT name FROM gameinfo WHERE name=?', $game);
        
        
        if (!preg_match('/^[a-z0-9 \-]+$/i', $game))
        {
            $special_char_games [] = $game;
        }
        else if ($game !== "" && $game !== "metacritic" && !in_array($game, $games_final) && $game !== $check[0]['name'] && $game !== "citation needed" && $game !== "2001" && $game !== "007 Agent Under Fire")
        {
            $games_final [] = $game;
            CS50::query('INSERT INTO gameinfo (name) VALUES(?)', $game);
        }
   }
   
    print("<br><br><br>");
    print_r($special_char_games);
?>