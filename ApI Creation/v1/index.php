<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;
$role=NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            global $role;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
            $role = $db->getUserRole($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email', 'password','access'));

            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');
       $access = $app->request->post('access');
            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password,$access);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user != NULL) {
                    $response["error"] = false;
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['propic']=$user['profilepicurl'];
                    $response['apiKey'] = $user['api_key'];
                    $response['createdAt'] = $user['created_at'];
                    $response['role']=$user['role'];
                    $response['id']=$user['id'];

                    $response['status']=$user['status'];

                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
        });

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks
 */
$app->get('/tasks', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllUserTasks($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["task"] = $task["task"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });

/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/tasks/:id', 'authenticate', function($task_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getTask($task_id, $user_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["task"] = $result["task"];
                $response["status"] = $result["status"];
                $response["createdAt"] = $result["created_at"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/tasks', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('task'));

            $response = array();
            $task = $app->request->post('task');

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createTask($user_id, $task);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Task created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }
        });

/* Creating new post in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/posts', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('post_description'));

            $response = array();
            $img_url=$app->request->post('img_url');
			$post_description=$app->request->post('post_description');
            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createPosts($user_id, $img_url,$post_description);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Post created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create post. Please try again";
                echoRespnse(200, $response);
            }
        });

/* Creating new event in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/cevents', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('event_desc'));

            $response = array();
            $event_name=$app->request->post('event_name');
			$event_desc=$app->request->post('event_desc');
            $eventfee=$app->request->post('event_fee');
			$img_url=$app->request->post('img_url');
            $event_department=$app->request->post('event_dep');
			$event_dur=$app->request->post('event_dur');

            global $user_id;
            global $role;
            $db = new DbHandler();

            // creating new task
            if($role!=0){
          $task_id=NULL;
            }else{
            $task_id = $db->createEvent($user_id,$role,$event_name,$event_desc, $eventfee,$img_url,$event_department,$event_dur);
}
            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Event created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create Event. You are Not Eligible";
                echoRespnse(200, $response);
            }
        });




/* Creating new Upload Image in db
 * method POST
 * params - name
 * url - /tasks/
*/
/*
$app->post('/uploadpic', 'authenticate', function() use ($app) {
            // check for required params
           //Constants for database connection
 define('DB_OST','localhost');
 define('DB_SER','id2554645_papaji');
 define('DB_ASS','jasmine9415');
 define('DB_AME','id2554645_dbindia');

 //We will upload files to this folder
 //So one thing don't forget, also create a folder named uploads inside your project folder i.e. MyApi folder
 define('UPLOAD_PATH', '/uploads/');

 //connecting to database
 $conn = new mysqli(DB_OST,DB_SER,DB_ASS,DB_AME) or die('Unable to connect');


 //An array to display the response
 $response = array();

 //if the call is an api call
 if(true){

 //switching the api call
 switch('uploadpic'){

 //if it is an upload call we will upload the image
 case 'uploadpic':

 //first confirming that we have the image and tags in the request parameter
 if(isset($_FILES['pic']['name']) && isset($_POST['tags'])){

 //uploading file and storing it to database as well
 try{
 move_uploaded_file($_FILES['pic']['tmp_name'], UPLOAD_PATH . $_FILES['pic']['name']);
 $stmt = $conn->prepare("INSERT INTO posts (image_url, tags) VALUES (?,?)");
 $stmt->bind_param("ss", $_FILES['pic']['name'],$_POST['tags']);
 if($stmt->execute()){
 $response['error'] = false;
 $response['message'] = 'File uploaded successfully';
 }else{
 throw new Exception("Could not upload file");
 }
 }catch(Exception $e){
 $response['error'] = true;
 $response['message'] = 'Could not upload file';
 }

 }else{
 $response['error'] = true;
 $response['message'] = "Required params not available";
 }

 break;

 //in this call we will fetch all the images
 case 'getpics':

 //getting server ip for building image url
 $server_ip = gethostbyname(gethostname());

 //query to get images from database
 $stmt = $conn->prepare("SELECT id, image_url, tags FROM posts");
 $stmt->execute();
 $stmt->bind_result($id, $image, $tags);

 $images = array();

 //fetching all the images from database
 //and pushing it to array
 while($stmt->fetch()){
 $temp = array();
 $temp['id'] = $id;
 $temp['image'] = 'http://' . $server_ip . '/MyApi/'. UPLOAD_PATH . $image;
 $temp['tags'] = $tags;

 array_push($images, $temp);
 }

 //pushing the array in response
 $response['error'] = false;
 $response['images'] = $images;
 break;

 default:
 $response['error'] = true;
 $response['message'] = 'Invalid api call';
 }

 }else{
 header("HTTP/1.0 404 Not Found");
 echo "<h1>404 Not Found</h1>";
 echo "The page that you have requested could not be found.";
 exit();
 }


  header('Content-Type: application/json');
 echo json_encode($response);
 /////////////
           /* if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Post created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create post. Please try again";
                echoRespnse(200, $response);
            }*/

/* Creating follow request in db
 * method POST
 * params - user_id,tofollow
 * url - /follow/
 */
$app->post('/follow', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('tofollow'));

            $response = array();
            $to_follow=$app->request->post('tofollow');
			//$post_description=$app->request->post('post_description');
            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createfollow($user_id,$to_follow);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "following successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to follow.Try Again";
                echoRespnse(200, $response);
            }
        });


/* Updating Event Description
 * method POST
 * params - user_id,tofollow
 * url - /follow/
 */
$app->post('/updatedesc', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('toupdate','name','desc'));

            $response = array();
            $to_follow=$app->request->post('toupdate');
			$name=$app->request->post('name');
			$desc=$app->request->post('desc');
			//$to_follow=$app->request->post('tolike');

			//$post_description=$app->request->post('post_description');
            global $user_id;
            global $role;
            $db = new DbHandler();
            if($role==0){
                 $db3 = new DbHandler();
                 $task_id = $db3->updateeve($user_id,$to_follow,$name,$desc);

            }else if($role==1){

            }else if($role==2){
                  $db9= new DbHandler();
                $registered=$db9->isregC($user_id,$to_follow);
                if($registered){
                 $db3 = new DbHandler();
                 $task_id = $db3->updateeve($user_id,$to_follow,$name,$desc);

                }else{
                    $task=NULL;
                }

            }else if($role==3){
                $db9= new DbHandler();
                $registered=$db9->isregF($user_id,$to_follow);
                if($registered){
                 $db3 = new DbHandler();
                 $task_id = $db3->updateeve($user_id,$to_follow,$name,$desc);

                }else{

                }
            }else{

            }

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Liked successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "You Cant Like It Again";
                echoRespnse(200, $response);
            }
        });


/* Creating paymentrequest of a student for a event
 * method POST
 * params - user_id,tofollow
 * url - /follow/
 */
$app->post('/yeppay', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('registerin','forpay'));

            $response = array();
            $to_follow=$app->request->post('registerin');
            $sec_boy=$app->request->post('forpay');
			//$post_description=$app->request->post('post_description');
            global $user_id;
            $db = new DbHandler();

            // creating new task
               $db3 = new DbHandler();
              $task_id=NULL;
              // fetching all user tasks
                 $tmpd = $db3->mipaid($sec_boy,$to_follow);
            if($tmpd==true){
            $task_id = NULL;
}else{       $task_id=$db->paytoall($user_id,$to_follow,$sec_boy);
        //     $task_id = $db->likePosts($user_id,$to_follow);
}
            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Liked successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "You Cant Like It Again";
                echoRespnse(200, $response);
            }
        });

/* Creating register a student for a event
 * method POST
 * params - user_id,tofollow
 * url - /follow/
 */
$app->post('/regstu', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('registerin'));

            $response = array();
            $to_follow=$app->request->post('registerin');
			//$post_description=$app->request->post('post_description');
            global $user_id;
            $db = new DbHandler();

            // creating new task
               $db3 = new DbHandler();
              $task_id=NULL;
              // fetching all user tasks
                 $tmpd = $db3->mireg($user_id,$to_follow);
            if($tmpd==true){
            $task_id = NULL;
}else{       $task_id=$db->registerme($user_id,$to_follow);
        //     $task_id = $db->likePosts($user_id,$to_follow);
}
            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Liked successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "You Cant Like It Again";
                echoRespnse(200, $response);
            }
        });
/* Creating like a event post request in db
 * method POST
 * params - user_id,tofollow
 * url - /follow/
 */
$app->post('/like', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('tolike'));

            $response = array();
            $to_follow=$app->request->post('tolike');
			//$post_description=$app->request->post('post_description');
            global $user_id;
            $db = new DbHandler();

            // creating new task
               $db3 = new DbHandler();
              $task_id=NULL;
              // fetching all user tasks
                 $tmpd = $db3->miLiked($user_id,$to_follow);
            if($tmpd==true){
            $task_id = NULL;
}else{       $dhbjcb=$db->updateLikes(1,$to_follow);
             $task_id = $db->likePosts($user_id,$to_follow);
}
            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Liked successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "You Cant Like It Again";
                echoRespnse(200, $response);
            }
        });

/* Creating like a event post request in db
 * method POST
 * params - user_id,tofollow
 * url - /follow/
 */
$app->post('/uploadprofilepic', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('url'));

            $response = array();
            $to_follow=$app->request->post('url');
			//$post_description=$app->request->post('post_description');
            global $user_id;
            $db = new DbHandler();

            // creating new task
               //$db3 = new DbHandler();
              $task_id=NULL;
             $task_id = $db->updateProfilePic($user_id,$to_follow);
            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Uploaded successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "You Cant Like It Again";
                echoRespnse(200, $response);
            }
        });


/* Creating dislike a event post request in db
 * method POST
 * params - user_id,tofollow
 * url - /follow/
 */
$app->post('/unlike', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('tolike'));

            $response = array();
            $to_follow=$app->request->post('tolike');
			//$post_description=$app->request->post('post_description');
            global $user_id;
            $db = new DbHandler();

            // creating new task
            $db3 = new DbHandler();
              $task_id=NULL;
              // fetching all user tasks
                 $tmpd = $db3->miLiked($user_id,$to_follow);
            if($tmpd==true){
                $dhbjcb=$db->updateLikes(0,$to_follow);
             $task_id = $db->unlikePosts($user_id,$to_follow);

}else{      $task_id=NULL;
}

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "UnLiked successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to follow.Try Again";
                echoRespnse(200, $response);
            }
        });


/* Creating dislike a event post request in db
 * method POST
 * params - user_id,tofollow
 * url - /follow/
 */
$app->post('/report', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('tolike'));

            $response = array();
            $to_follow=$app->request->post('tolike');
			//$post_description=$app->request->post('post_description');
            global $user_id;
            $db = new DbHandler();

            // creating new task
            $db3 = new DbHandler();
              $task_id=NULL;
              // fetching all user tasks
                 $task_id = $db3->createReport($user_id,$to_follow);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "UnLiked successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to follow.Try Again";
                echoRespnse(200, $response);
            }
        });


        /* Granting Access to Coordinator/Facilitator
 * method POST
 * params - user_id,tofollow
 * url - /follow/
 */
$app->post('/grantcf', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('eventid'));

            $response = array();
            $event_id=$app->request->post('eventid');
			$to_user=$app->request->post('touser');
			$to_userrole=$app->request->post('touserrole');

			//$post_description=$app->request->post('post_description');
            global $user_id;
            global $role;
            $db4=new DbHandler();
           $to_userrole=$db4->getUserRoleMK($to_user);
            $db = new DbHandler();

            // creating new task
            if($to_userrole==0){$task_id=NULL;}
            else if($to_userrole==3&&$role==0){
            $task_id = $db->createFafollow($user_id,$to_user,$event_id);
            $db1 = new DbHandler();

            $nnkv=$db1->updateUserState($user_id,$to_user);
            }else if($to_userrole==2&&$role==0){

            $task_id = $db->createCofollow($user_id,$to_user,$event_id);
            $db5 = new DbHandler();
            $nnkv=$db5->updateUserState($user_id,$to_user);

            }else if($to_userrole==3&&$role==2){
            $task_id = $db->createFafollow($user_id,$to_user,$event_id);
                $db5 = new DbHandler();
            $nnkv=$db5->updateUserState($user_id,$to_user);

            }else{
                $task_id=NULL;
            }

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Access Granted successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to follow.Try Again";
                echoRespnse(200, $response);
            }
        });

    /**
 * Listing all users info using id ;
 * method GET
 * url /tasks
 */
$app->get('/qrscan', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getTrending($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["postid"]=$task["id"];
                  $db3 = new DbHandler();

              // fetching all user tasks
                 $tmp["miliked"] = $db3->miLiked($user_id,$task["id"]);
                $tmp["department"]=$task["department"];
                $tmp["id"]=$task["eventid"];
                $tmp["postername"]=$task["name"];
                $tmp["name"] = $task["eventname"];
                $tmp["imageurl"] = $task["image_url"];
                $tmp["post_desc"] = $task["post_description"];
                $tmp["likes"] = $task["no_of_likes"];
                $tmp["comments"] = $task["no_of_comments"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });


    /**
 * Listing all Trending Posts
 * method GET
 * url /tasks
 */
$app->get('/trending', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getTrending($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["postid"]=$task["id"];
                  $db3 = new DbHandler();

              // fetching all user tasks
                 $tmp["miliked"] = $db3->miLiked($user_id,$task["id"]);
                $tmp["department"]=$task["department"];
                $tmp["id"]=$task["eventid"];
                $tmp["postername"]=$task["name"];
                $tmp["name"] = $task["eventname"];
                $tmp["imageurl"] = $task["image_url"];
                $tmp["post_desc"] = $task["post_description"];
                $tmp["likes"] = $task["no_of_likes"];
                $tmp["comments"] = $task["no_of_comments"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });

    /**
 * Listing all Trending Events with no.of registrations and all informations
 * method GET
 * url /tasks
 */
$app->get('/seeevents', 'authenticate', function() {
            global $user_id;
            global $role;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getTrendingEvents($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["eventid"]=$task["eventid"];
                $tmp["eventname"] = $task["eventname"];
                $tmp["imageurl"] = $task["imageurl"];
                $tmp["eventdesc"] = $task["eventdescription"];
                $tmp["department"] = $task["department"];
                $tmp["eventfee"]=$task["registrationfee"];
                $dbs=new DbHandler();
               $gyh=$dbs->getNoEveF($task["eventid"]);
                $tmp["totalreg"] = $gyh["DATA"];
                if($role==1){
                    $dbsm=new DbHandler();
                $tyh=$dbsm->isUserF($user_id,$task["eventid"]);//is user following
                $tmp["userisfollowing"]=$tyh;
                    }
                //$tmp["comments"] = $task["no_of_comments"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });



    /**
 * Listing all Trending Events with no.of registrations and all informations
 * method GET
 * url /tasks
 */
$app->get('/seemineevents', 'authenticate', function() {
            global $user_id;
            global $role;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            if($role==0){
                $result = $db->getTrendingEvents($user_id);
            }else if($role==2){
            $result = $db->getMineTrendingEvents($user_id);
            }else{
                // $result = $db->getTrendingEvents($user_id);
            }
            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["eventid"]=$task["eventid"];
                $tmp["eventname"] = $task["eventname"];
                $tmp["imageurl"] = $task["imageurl"];
                $tmp["eventdesc"] = $task["eventdescription"];
                $tmp["department"] = $task["department"];
                $tmp["eventfee"]=$task["registrationfee"];
                $dbs=new DbHandler();
               $gyh=$dbs->getNoEveF($task["eventid"]);
                $tmp["totalreg"] = $gyh["DATA"];
                if($role==1){
                    $dbsm=new DbHandler();
                $tyh=$dbsm->isUserF($user_id,$task["eventid"]);//is user following
                $tmp["userisfollowing"]=$tyh;
                    }
                //$tmp["comments"] = $task["no_of_comments"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });


    /**
 * Listing all User Detail
 * method GET
 * url /tasks
 */
$app->get('/profile', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            $db1= new DbHandler();
            $db2= new DbHandler();
            $db3= new DbHandler();

            // fetching all user tasks
            $result = $db->getUserSPost($user_id);
            $follower=$db1->getNoFollower($user_id);
            $following=$db2->getNoFollowing($user_id);
            $posts=$db3->getNoPosts($user_id);
            $detail=$db3->getNameUse($user_id);
            $response["name"]=$detail["name"];
            $response["email"]=$detail["email"];
            $response["imgurl"]=$detail["purl"];
            $response["status"]=$detail["status"];
            $response["follower"] = $follower["DATA"];
            $response["following"] =$following["DATA"];
            $response["posts"] = $posts["DATA"];
            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["imageurl"] = $task["image_url"];
                $tmp["post_desc"] = $task["post_description"];
                $tmp["likes"] = $task["no_of_likes"];
                $tmp["comments"] = $task["no_of_comments"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });

    /**
 * Listing all User Detail
 * method GET
 * url /tasks
 */
$app->get('/friends/:id', 'authenticate', function($toseeid) {
            // check for required params
            //verifyRequiredParams(array('toseeid'));

            //$response = array();
            $to_follow=$toseeid;

            global $user_id;
            $response = array();
            $db = new DbHandler();
            $db1= new DbHandler();
            $db2= new DbHandler();
            $db3= new DbHandler();

            // fetching all user tasks
            $result = $db->getUserSPost($to_follow);
            $follower=$db1->getNoFollower($to_follow);
            $following=$db2->getNoFollowing($to_follow);
            $posts=$db3->getNoPosts($to_follow);
            $detail=$db3->getNameUse($to_follow);
            if(!$db3->isUserFollow($user_id,$to_follow)){
                $response["mefollowing"]=0;
            }else{
                $response["mefollowing"]=1;
            }
            $response["name"]=$detail["name"];
            $response["email"]=$detail["email"];
            $response["imgurl"]=$detail["purl"];
            $response["status"]=$detail["status"];
            //$response["mefollowing"]=$mefollowing;
            $response["follower"] = $follower["DATA"];
            $response["following"] =$following["DATA"];
            $response["posts"] = $posts["DATA"];
            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["imageurl"] = $task["image_url"];
                $tmp["post_desc"] = $task["post_description"];
                $tmp["likes"] = $task["no_of_likes"];
                $tmp["comments"] = $task["no_of_comments"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });


    /**
 *All Detail of a particular event
 * method GET
 * url /tasks
 */
$app->get('/events/:id', 'authenticate', function($toseeid) {
            // check for required params
            //verifyRequiredParams(array('toseeid'));

            //$response = array();
            $to_follow=$toseeid;

            global $user_id;
            global $role;
            $response = array();
            $db = new DbHandler();
            $db1= new DbHandler();
            $db2= new DbHandler();
            $db3= new DbHandler();

            // fetching all user tasks
            //$result = $db->getUserSPost($to_follow);
            $follower=$db1->gettotalrevenue($to_follow);
            $following=$db2->getnoco($to_follow);
             $dbs=new DbHandler();
               $gyh=$dbs->getNoEveF($to_follow);
                //$tmp["totalreg"] = $gyh["DATA"];
        //    $posts=$db3->getNoPosts($to_follow);
         //   $detail=$db3->getNameUse($to_follow);

            //$response["mefollowing"]=$mefollowing;
            /*if($role!=1){
            $response["totalpeoplepaid"] = $follower["DATA"];
            }*/
            if($role==0){
                $response["totalpeoplepaid"] = $follower["DATA"];
            }
            else if($role==1){
            //$response["totalpeoplepaid"] = $follower["DATA"];
             $db9= new DbHandler();
                $registered=$db9->mireg($user_id,$to_follow);//to check whether i have registered
                $paid=$db9->mipaid($user_id,$to_follow);//to check if i have paid
                        $response["registered"] = $registered;
                        $response["paid"] = $paid;
            }else if($role==2){
            $response["totalpeoplepaid"] = $follower["DATA"];
                  $db9= new DbHandler();
                $registered=$db9->isregC($user_id,$to_follow);//to check whether it is my event registered
            $response["registered"] = $registered;
            }else if($role==3){
            $response["totalpeoplepaid"] = $follower["DATA"];
        $db9= new DbHandler();
                $registered=$db9->isregF($user_id,$to_follow);//to check whether it is my event registered
                $response["registered"] = $registered;
            }else{
            }
            $response["noofco"] =$following["DATA"];
            $response["noofstu"] =$gyh["DATA"];
            $response["error"] = false;
            //$response["tasks"] = array();

            // looping through result and preparing tasks array
            /*while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["imageurl"] = $task["image_url"];
                $tmp["post_desc"] = $task["post_description"];
                $tmp["likes"] = $task["no_of_likes"];
                $tmp["comments"] = $task["no_of_comments"];
                array_push($response["tasks"], $tmp);
            }
                 */
            echoRespnse(200, $response);
        });

    /**
 *All Detail of Coorddinators a particular event
 * method GET
 * url /tasks
 */
$app->get('/coordinators/:id', 'authenticate', function($toseeid) {
            // check for required params
            //verifyRequiredParams(array('toseeid'));

            $response = array();
            $to_follow=$toseeid;

            global $user_id;
            global $role;
            $response = array();
            $db = new DbHandler();
            $db1= new DbHandler();
            $db2= new DbHandler();
            $db3= new DbHandler();

            // fetching all user tasks
            $result = $db->getCoDetails($to_follow);
            ///$follower=$db1->gettotalrevenue($to_follow);
            //$following=$db2->getnoco($to_follow);
             //$dbs=new DbHandler();
              // $gyh=$dbs->getNoEveF($to_follow);
                //$tmp["totalreg"] = $gyh["DATA"];
        //    $posts=$db3->getNoPosts($to_follow);
         //   $detail=$db3->getNameUse($to_follow);

            //$response["mefollowing"]=$mefollowing;
            //if($role!=1){
            //$response["totalpeoplepaid"] = $follower["DATA"];
            //}
        //    $response["noofco"] =$following["DATA"];
            //$response["noofstu"] =$gyh["DATA"];
            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["imageurl"] = $task["profilepicurl"];
                $tmp["name"] = $task["name"];
                $tmp["email"] = $task["email"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });

    /**
 *All Detail of a particular event
 * method GET
 * url /tasks
 */
$app->get('/participants/:id', 'authenticate', function($toseeid) {
            // check for required params
            //verifyRequiredParams(array('toseeid'));

            $response = array();
            $to_follow=$toseeid;

            global $user_id;
            global $role;
            $response = array();
            $db = new DbHandler();
            $db1= new DbHandler();
            $db2= new DbHandler();
            $db3= new DbHandler();

            // fetching all user tasks
            $result = $db->getParDetails($to_follow);
            ///$follower=$db1->gettotalrevenue($to_follow);
            //$following=$db2->getnoco($to_follow);
             //$dbs=new DbHandler();
              // $gyh=$dbs->getNoEveF($to_follow);
                //$tmp["totalreg"] = $gyh["DATA"];
        //    $posts=$db3->getNoPosts($to_follow);
         //   $detail=$db3->getNameUse($to_follow);

            //$response["mefollowing"]=$mefollowing;
            //if($role!=1){
            //$response["totalpeoplepaid"] = $follower["DATA"];
            //}
        //    $response["noofco"] =$following["DATA"];
            //$response["noofstu"] =$gyh["DATA"];
            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["imageurl"] = $task["profilepicurl"];
                $tmp["name"] = $task["name"];
                $tmp["email"] = $task["email"];
                //$tmp["comments"] = $task["no_of_comments"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
            // check for required params
            verifyRequiredParams(array('task', 'status'));

            global $user_id;
            $task = $app->request->put('task');
            $status = $app->request->put('status');

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->updateTask($user_id, $task_id, $task, $status);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>
