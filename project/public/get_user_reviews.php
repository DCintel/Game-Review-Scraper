<?php
    require ('../includes/config.php');
    
    //set up response object to be sent back to site
    $reviews [] = [
        "message" => "",
        "hardcore_review" => "",
        "hobbyist_review" => "",
        "kids_review" => "",
        "comments" => ""
        ];
    
    //check to make sure that name was sent properly. if yes, continue, else respond with error message.
    if (empty($_GET['name']))
    {
        $reviews[0]["message"] += " Error: no game name identified;";
    }
    else
    {
        //get user review averages from gameinfo database
        $result = CS50::query('SELECT * FROM gameinfo WHERE name=?', $_GET['name']);
        
        //if nothing is returned, there was an error. add error to response object. else, load reviews into response object, changing to "N/A" if a 0 is found
        if(count($result) == 0)
        {
            $reviews[0]['message'] += " Error: could not load user review data;";
        }
        else
        {
            $reviews[0]["hardcore_review"] = $result[0]['hardcore_review'];
            $reviews[0]["hobbyist_review"] = $result[0]['hobbyist_review'];
            $reviews[0]["kids_review"] = $result[0]['kids_review'];
        }
        
        
        //get all comments written about particular game and store them in response object
        $reviews[0]["comments"] = CS50::query('SELECT comment FROM userreviews WHERE name=?', $_GET['name']);
        
        if(count($reviews[0]["comments"]) == 0)
        {
            $reviews[0]["message"] += " No comments found;";
        }

        //output reviews as JSON (pretty-printed for debugging convenience)
        header("Content-type: application/json");
        print(json_encode($reviews, JSON_PRETTY_PRINT));
    }

?>