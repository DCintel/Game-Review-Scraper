<?php
    
    require ('../includes/config.php');
    
    //response code. message to be redirected to user, means of checking whether game exists to be used for error checking/verification
    $response[] = [
        "message" => "",
        "means" => ""
        ];
    
    //check that all necessary fields were passed correctly. if not, respond with error.
    if (empty($_GET['game']) || empty($_GET['interest']) || empty($_GET['rating']) || empty($_GET['userid']))
    {
        $response[0]['message'] = 'Error: missing field';
    }
    else
    {
        //check if user has already submitted a review for this game. if so, reject with error message. if not, attempt to insert game review into database.
        $check_reviews = CS50::query('SELECT * FROM userreviews WHERE id = ? AND name = ?', $_GET['userid'], $_GET['game']);
                
        if (count($check_reviews) >= 1)
        {
            $response[0]['message'] = "You have already made a review for this game!";
        }
        else
        {
            //check to see that user has entered a valid game by first checking if the game is in the gameinfo database. if so, continue with review addition, otherwise use secondary check.
            $result = CS50::query('SELECT name FROM gameinfo WHERE name = ?', $_GET['game']);
            
            if (count($result) == 1)
            {
                
                CS50::query('INSERT IGNORE INTO userreviews (id, name, interest, rating, comment) VALUES (?, ?, ?, ?, ?)', $_GET['userid'], $_GET['game'], $_GET['interest'], $_GET['rating'], $_GET['comment']);
                
                $response[0]['message'] = "Review successfully added!";
                $response[0]['means'] = "In database";
            }
            else
            {
                //if game is not in database, check for its presence in gamespot.com. if it is there, continue with review addition, otherwise reject with error.
                $gameformated = preg_replace('/\s+/', "-", $_GET['game']);
                $gamespot_url = "http://www.gamespot.com/" . $gameformated . "/";
            
                if (get_http_response_code($gamespot_url) == "200")
                {
                    //add game to database before adding user review
                    CS50::query('INSERT IGNORE INTO gameinfo (name) VALUES (?)', $_GET['game']);
                    
                    CS50::query('INSERT IGNORE INTO userreviews (id, name, interest, rating, comment) VALUES (?, ?, ?, ?, ?)', $_GET['userid'], $_GET['game'], $_GET['interest'], $_GET['rating'], $_GET['comment']);
                    
                    $response[0]['message'] = "Review successfully added!";
                    $response[0]['means'] = "Found in Gamespot";
                }
                else
                {
                    $response[0]['message'] = 'Error: game not found. Check your spelling or try again with a different game.';
                }
            }
        }
    }
    
    //if review has been added to database (checked by the presence of the 'means' key), update average score in gameinfo database.
    if ($response[0]['means'] != "")
    {
        //pull all ratings of added interest level
        $ratings = CS50::query('SELECT rating FROM userreviews WHERE name = ? AND interest = ?', $_GET['game'], $_GET['interest']);
        
        //set up average variable, add all reviews together, then divide by number of reviews to get average
        $reviews_average = 0;
        
        foreach($ratings as $rating)
        {
            $reviews_average = $reviews_average + $rating['rating'];
        }
        
        $reviews_average = $reviews_average / count($ratings);
        
        //determine which interest category of gameinfo database the average is to be stored in
        switch ($_GET['interest']){
            case 'hardcore_review':
                CS50::query('UPDATE gameinfo SET hardcore_review = ? WHERE name = ?', $reviews_average, $_GET['game']);
                break;
            case 'hobbyist_review':
                CS50::query('UPDATE gameinfo SET hobbyist_review = ? WHERE name = ?', $reviews_average, $_GET['game']);   
                break;
            case 'kids_review':
                CS50::query('UPDATE gameinfo SET kids_review = ? WHERE name = ?', $reviews_average, $_GET['game']);
                break;
            }
    }
  
    //output response as JSON (pretty-printed for debugging convenience)
    header("Content-type: application/json");
    print(json_encode($response, JSON_PRETTY_PRINT));
?>