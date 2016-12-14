
$(function() {
    //on loading the site's main page, check for stored user information. If present, log user in, else load main page's nav and main-content divs
    var user_cookie = getCookie('username');
    var pass_cookie = getCookie('password');
    
    if(user_cookie != "" && pass_cookie != "")
    {
        login(user_cookie, pass_cookie);
    }
    else
    {
        navigate('views/nav_start.html', 'nav-content');
        navigate('views/about_form.html', 'main-content');
    }
    
    configureTypeahead();
    
})




/**
 * Adds review to user review database.
 */
function add_review()
{
    //grab all field values. interest requires if condition because function crashes if null returned. 
    parameters = {
        game: document.getElementById('game').value,
        rating: document.getElementById('rating-form').value,
        comment: document.getElementById('review-comment-form').value,
        interest: document.querySelector('input[name=review_radio]:checked'),
        userid: ""
        };
        
    if (parameters.interest != null)
        {
            parameters.interest = parameters.interest.value;
        }   
        
    //check for presence of required fields. alert if missing. also check that review score is within 1-10. If all checks passed, send data to php add review function.
    if (parameters.game == "" || parameters.interest == null || parameters.rating == "")
    {
        alert("You must fill in at least the game, interest level, and rating fields!");
    }
    else if(parameters.rating < 1 || parameters.rating > 10)
    {
        alert("Your review score must be between 1 and 10.");
    }
    else
    {
        parameters.userid = getCookie('userid');
        
        console.log(parameters.game, parameters.interest, parameters.rating, parameters.comment, parameters.userid);
        
        $.getJSON('add_review.php', parameters)
        .done(function(data, textStatus, jqXHR){
            alert(data[0]['message']);   

             if (data[0]['message'] == "Review successfully added!")
             {
                navigate('views/about_form.html', 'main-content');
             }
        })
        .fail(function(data, textStatus, errorThrown){
            alert(errorThrown);
        });
    }
    
    //this function was activated by submit button with obsubmit="return", which requires returning of false to prevent page from reloading.
    return false;    
}




/**
 * Initializes typeahead functionality.
 */
function configureTypeahead(){
    
    // configure typeahead
    // https://github.com/twitter/typeahead.js/blob/master/doc/jquery_typeahead.md
    $("#q").typeahead({
        hint: true,
        autoselect: true,
        highlight: true,
        minLength: 1
    },
    {
        source: search,
        templates: {
            empty: "no games found yet",
            suggestion: _.template("<p><%- name %></p>")
        }
    });
    
    $("#q").on("typeahead:selected", function(eventObject, suggestion){
        
        //set up parameters to be sent to ajax functions
        var parameters = {
            name: suggestion['name']
            };
        
        //load review scores format in preparation for results 
        navigate('views/scores_form.html', 'main-content');
        
        //send game name to user review pull function. results are sent back and added to appropriate divs.
        $.getJSON('get_user_reviews.php', parameters)
        .done(function(data, textStatus, jqXHR){
            console.log(suggestion);
            console.log(data[0]);
            
            document.getElementById('hardcore').innerHTML = "Hardcore: " + data[0].hardcore_review;
            document.getElementById('hobbyist').innerHTML = "Hobbyist: " + data[0].hobbyist_review;
            document.getElementById('kids').innerHTML = "Kids: " + data[0].kids_review;
            
            if (data[0].comments.length != 0)
            {
                for(var item of data[0].comments)
                {
                    var comments = comments + '<div id="comment">' + item["comment"] + '</div>';
                }
            
                var comments_final = comments.replace("undefined", "");
            }
            else
            {
                var comments_final = "No user comments found for this game."
            }
            
            document.getElementById('comments').innerHTML = comments_final;
            
        })
        .fail(function(data, textStatus, jqXHR){
            console.log(jqXHR);
        });
        
        
        //send game name to review scraper. restults are sent back and added to the appropriate divs.
        $.getJSON("fullscrapeV4.php", parameters)
        .done(function(data, textStatus, jqXHR) {
            console.log(data);
            
            document.getElementById('ign_content').innerHTML = "IGN: " + data[0].ign;
            
            document.getElementById('gamespot_content').innerHTML = "Gamespot: " + data[0].gamespot;
            
            document.getElementById('metacritic_content').innerHTML = "Metacritic: " + data[0].metacritic;
            
            console.log(jqXHR);
        })
        .fail(function(data, textStatus, jqXHR){
            console.log(jqXHR);
        })
    }); 
    
}




/**
 * Get specified cookie
 */
function getCookie(cname) 
{
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length,c.length);
        }
    }
    return "";
}




/**
 * logs user in.
 */
function login(username, password)
{
    if (username == "" || password == "")
    {
        alert("You must fill in all fields.");
    }
    else
    {
        //set up parameters to send to ajax function
        var parameters = {
            username: username,
            password: password
        };
        
        //sends username and password to login.php to check that username exists and tha password matches database entry. Response message is logged, and, upon successful login, user info is saved , nav options are updated, and user is redirected to search page.
        $.getJSON("login.php", parameters)
        .done(function(data, textStatus, jqXHR){
            console.log(data[0]["message"]);
            
            if(data[0]["message"] == "login")
            {
                //save user data in cookies
                document.cookie = "userid=" + data[0]["id"];
                document.cookie = "username=" + username;
                document.cookie = "password=" + password;
                
                //update navigation bar and bring up search bar.
                navigate('views/nav_loggedin.html', 'nav-content');
                navigate('views/search_form.html', 'main-content');
                //configureTypeahead();
            }
            else
            {
                //alert user of login issue
                alert(data[0]["message"]);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown){
            console.log(errorThrown);
        });
    }
    
    //this function was activated by submit button with obsubmit="return", which requires returning of false to prevent page from reloading.
    return false;
}




/**
 * Logs user out.
 */
 function logout()
 {
     var cookies = ['userid', 'username', 'password'];
     
     console.log(cookies);
     
     for (var cookie of cookies)
     {
         console.log(cookie + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC');
         document.cookie = cookie + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC';
     }
     
     navigate('views/about_form.html', 'main-content');
     navigate('views/nav_start.html', 'nav-content');
 }




/**
 * Sends AJAX requests to different pages to change major divs of site. pipe is the information address, type is whether navigation or main content will be changed
 */
function navigate(pipe, type)
{
    //determine which div is being manipulated main-content or navigation bar content. adjust content accordingly.
    if (type == "main-content")
    {
        //hide search bar (to keep functionality) and remove content in preparation for loading new content. Show search bar if function is called to get search/scores/about form (so user can continuously to search)
        if(pipe == 'views/scores_form.html' || pipe == 'views/about_form.html' || pipe == 'views/search_form.html')
        {
            document.getElementById('search-form').style.display="block";
            $('#main-content').html("");
        }
        else
        {
            document.getElementById('search-form').style.display="none";
            $('#main-content').html("");
        }
        
        var ajax = new XMLHttpRequest();
        
        ajax.onreadystatechange = function(){
            if (ajax.readyState == 4 && ajax.status == 200)
            {
                $('#main-content').html(ajax.responseText);
                console.log(ajax.responseText);
            }
        };
        
        ajax.open('GET', pipe, true);
        ajax.send();
    }
    else if(type == "nav-content")
    {
        var ajax = new XMLHttpRequest();
        
        ajax.onreadystatechange = function(){
            if (ajax.readyState == 4 && ajax.status == 200)
            {
                $('#navigation').html(ajax.responseText);
                console.log(ajax.responseText);
            }
        };
        
        ajax.open('GET', pipe, true);
        ajax.send();
    }
    
}




/**
 * Adds user to database.
*/
function register()
{
    //variable to check if an error is thrown.
    var alerts = 0;
    
    //grabs username from form and checks it for presence and length
    var username = document.getElementById("username").value;
        
        if (username == "")
        {
            alert("You must fill in a username.");
            alerts++;
        }
        else if (username.length < 8)
        {
            alert("Username must be at least 8 characters long.");
            alerts++;
        }
    
    //grabs password from form and checks it for presence and length
    var password = document.getElementById("password").value;
    
        if (password == "")
        {
            alert("You must fill in a password.");
            alerts++;
        }
        else if (password.length < 8)
        {
            alert("Password must be at least 8 characters long.");
            alerts++;
        }
    
    //grabs verification password from form and checks it for presence and that it matches password
    var passverify = document.getElementById("passverify").value;
    
        if (passverify== "")
        {
            alert("You must verify password.");
            alerts++;
        }
        else if (passverify != password)
        {
            alert("Password and verification do not match.");
            alerts++;
        }
    
    //if all fields are filedl in correctly, information is sent to register.php. This checks that if the user already exists and adds user and password to database if it does not. User is alerted of result. User is logged in on success.
    if (alerts == 0)
    {
        var parameters = {
           username: username,
           password: password 
        };
        
        $.getJSON("register.php", parameters)
        .done(function(data, textStatus, jqXHR){
            alert(data["message"]);
            
            if(data["message"] == "Account successfully created")
            {
                login(username, password);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown){
            console.log(errorThrown);
        });
    }
    
    //this function was activated by submit button with obsubmit="return", which requires returning of false to prevent page from reloading.
    return false;
}




/**
 * Searches database for typeahead's suggestions.
 */
function search(query, cb)
{
    // get places matching query (asynchronously)
    var parameters = {
        name: query
    };
    $.getJSON("search.php", parameters)
    .done(function(data, textStatus, jqXHR) {

        // call typeahead's callback with search results (i.e., places)
        cb(data);
    })
    .fail(function(jqXHR, textStatus, errorThrown) {

        // log error to browser's console
        console.log(errorThrown.toString());
    });
}