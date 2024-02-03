<?php
    include_once 'user/user_helper.php';

    function route($method, $urlList, $requestData){
        if($method == "POST"){
            global $Link;
            switch($urlList[1]){
                case 'login':
                    $username = $requestData->body->username;
                    $pass = hash("sha1", $requestData->body->password);

                    $user = $Link->query("SELECT id from users Where username='$username' AND password='$pass'")->fetch_assoc();
                    if(!is_null($user)){
                        $token = bin2hex(random_bytes(16));
                        $userID = $user['id'];
                        $tokenInsertsResult = $Link->query("INSERT INTO tokens(value,userID) VALUES('$token','$userID')");

                        if(!$tokenInsertsResult){
                            //400
                            echo json_encode($Link->error);
                        }else{
                            echo json_encode(['token' => $token, "username" => $username,"userID" => $userID]);
                        }

                    }else{
                        echo "Error";
                    }

                    // echo json_encode($userID);
                    break;
                case "register":
                    $username = $requestData->body->username;
                    $isValidated = true;
                    $validationErrors=[];

                    if(!validatePassword($requestData->body->password)){
                        $isValidated = false;
                        $validationErrors[] = ["Password","Password is less then 8 characters"];
                    }

                    $password = hash("sha1", $requestData->body->password);
                    $username = $requestData->body->username;

                    if(!validateStringNotLess($username, 3)){
                        $isValidated = false;
                        $validationErrors[] = ["username","username is less then 3 characters"];
                    }

                    if(!$isValidated){
                        $validationMessage = "";
                        foreach($validationErrors as $err){
                            $validationMessage .= "$err[0]: $err[1] \r\n";
                        }
                        setHTTPStatus("403",$validationMessage);
                        return;
                    }

                    $userInsertsResult = $Link->query("INSERT INTO `users` (`id`, `username`, `bio`, `password`) VALUES (NULL, '$username', NULL, '$password');");

                    $user = $Link->query("SELECT * from users Where username='$username'")->fetch_assoc();

                    echo json_encode($user['id']);


                    if(!$userInsertsResult){
                        if($Link->errno == 1062){
                            setHTTPStatus("409","Such '$username' username is taken");
                            return;
                        }
                    }else{
                        setHTTPStatus("200","User '$username' was succesfully created");
                    }
                    
                    break;
                case 'logout':
                        //TOOOO
                    break;
                default:
                    setHTTPStatus("404","there is no such path as 'auth/$urlList[1]'");
                    break;
        }
        }else{
            setHTTPStatus("400","You can only use POST to 'auth/*'");
        }
    }

?>