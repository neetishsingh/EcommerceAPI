<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password,$access) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();
         if($access=="0"){
            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status,role) values(?, ?, ?, ?, 0,0)");}
            else if($access=="1"){
                $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status,role) values(?, ?, ?, ?, 1,1)");
            }
            else if($access=="2"){
                $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status,role) values(?, ?, ?, ?, 0,2)");
            }
            else if($access=="3"){
                $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status,role) values(?, ?, ?, ?, 0,3)");
            }
            else{

            }
            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Creating new faculty user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createFacUser($name, $email, $password) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status,role) values(?, ?, ?, ?, 0,2)");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Creating new Coordinater user role=3
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createCooUser($name, $email, $password) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status,role) values(?, ?, ?, ?, 0,3)");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }
    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Checking for do user follow * @param String $email email to check in db
     * @return boolean
     */
    public function isUserFollow($user_id,$secid) {
        $stmt = $this->conn->prepare("SELECT * FROM following WHERE followbyid = ? AND followtoid=?");
        $stmt->bind_param("ii", $user_id,$secid);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
         return $num_rows > 0;

    }
    /**
     * Checking for do user follow * @param String $email email to check in db
     * @return boolean
     */
    public function isUserF($user_id,$event_id) {
        $stmt = $this->conn->prepare("SELECT * FROM eventfollowing WHERE user_id = ? AND event_id=?");
        $stmt->bind_param("ii", $user_id,$event_id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
         return $num_rows > 0;

    }


    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT name,id,profilepicurl, email, api_key, status, created_at,role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name,$id,$profilepicurl, $email, $api_key, $status, $created_at,$role);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["id"] = $id;
            $user["profilepicurl"] = $profilepicurl;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $user["role"]=$role;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }
    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }
        /**
     * Fetching Userrole by api key
     * @param String $api_key user api key
     */
    public function getUserRoleMK($api_key) {
        $stmt = $this->conn->prepare("SELECT role FROM users WHERE users.id = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($role);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $role;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user role by api key
     * @param String $api_key user api key
     */
    public function getUserRole($api_key) {
        $stmt = $this->conn->prepare("SELECT role FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($role);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $role;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

/**
     * Validating user likes on the basis of Api
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function miLiked($userid,$eventid) {
        $stmt = $this->conn->prepare("SELECT id from postlikes WHERE userid = ? AND postid=?");
        $stmt->bind_param("ss", $userid,$eventid);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

/**
     * Validating student registered or not on the basis of Api
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function mireg($userid,$eventid) {
        $stmt = $this->conn->prepare("SELECT id from eventfollowing WHERE user_id = ? AND event_id=?");
        $stmt->bind_param("ss", $userid,$eventid);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    /**
     * Validating facilitator registered or not on the basis of Api
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isregF($userid,$eventid) {
        $stmt = $this->conn->prepare("SELECT id from facilitating WHERE fac_id = ? AND event_id=?");
        $stmt->bind_param("ss", $userid,$eventid);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    /**
     * Validating coordiator registered on the basis of Api
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isregC($userid,$eventid) {
        $stmt = $this->conn->prepare("SELECT id from coordinating WHERE coordinator_id = ? AND event_id=?");
        $stmt->bind_param("ss", $userid,$eventid);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
/**
     * Validating student paid or not on the basis of Api
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function mipaid($userid,$eventid) {
        $stmt = $this->conn->prepare("SELECT id from transaction WHERE paidby = ? AND event_id=?");
        $stmt->bind_param("ss", $userid,$eventid);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }


    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }
//-----------------------Creating POST----
/*--------------------------------------CREATE POST-------------------*/
//$user_id,$role,$event_name,$event_desc, $eventfee,$img_url,$event_department,$event_dur
/**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createEvent($user_id,$role,$event_name,$event_desc, $eventfee,$img_url,$event_department,$event_dur) {
        $ma=$user_id;
        $mb=$event_name;
        $mc=$event_desc;
        $md=$eventfee;
        $me=$img_url;
        $mf=$event_department;
        $mg=$event_dur;
        $yu="INSERT INTO events(eventname,createdby,eventdescription,imageurl,live,enabled,department,eventduration,registrationfee) VALUES('$mb','$ma','$mc','$me','1','1','$mf','$mg','$md')";
        $stmt = $this->conn->prepare($yu);
        //$stmt->bind_param($user_id, $imgurl, $post_desc);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
			return 200;
		}
		else{
			return NULL;
		}
       /* if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }*/
    }

	/**
     * Creating new report
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createReport($user_id, $post_id) {
        $mu=$user_id;
        $me=$post_id;
        //$mr=$post_desc;
        $yu="INSERT INTO reports(user_id,post_id,approved) VALUES('$mu','$me','0')";
        $stmt = $this->conn->prepare($yu);
        //$stmt->bind_param($user_id, $imgurl, $post_desc);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
			return 200;
		}
		else{
			return NULL;
		}
}
	/**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */

    public function createPosts($user_id, $imgurl,$post_desc) {
        $mu=$user_id;
        $me=$imgurl;
        $mr=$post_desc;
        $yu="INSERT INTO posts(user_id,image_url,post_description) VALUES('$mu','$me','$mr')";
        $stmt = $this->conn->prepare($yu);
        //$stmt->bind_param($user_id, $imgurl, $post_desc);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
			return 200;
		}
		else{
			return NULL;
		}
       /* if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }*/
    }
//--------------------------------------
//--------------------Liking a post-----------------------------------
public function likePosts($user_id, $postid) {
        $mu=$user_id;
        $me=$postid;
        $yu="INSERT INTO postlikes(userid,postid) VALUES('$mu','$me')";
        $stmt = $this->conn->prepare($yu);
        //$stmt->bind_param($user_id, $imgurl, $post_desc);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
			return 200;
		}
		else{
			return NULL;
		}
       /* if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }*/
    }

//--------------------registering a student-----------------------------------
public function registerme($user_id, $postid) {
        $mu=$user_id;
        $me=$postid;
        $yu="INSERT INTO eventfollowing(user_id,event_id) VALUES('$mu','$me')";
        $stmt = $this->conn->prepare($yu);
        //$stmt->bind_param($user_id, $imgurl, $post_desc);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
			return 200;
		}
		else{
			return NULL;
		}
       /* if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }*/
    }

//--------------------registering a student-----------------------------------
public function paytoall($user_id, $event_id,$sec_boy) {
        $mu=$user_id;
        $me=$event_id;
        $hj=$sec_boy;
        $yu="INSERT INTO transaction(paid_to,event_id,paidby) VALUES('$mu','$me','$hj')";
        $stmt = $this->conn->prepare($yu);
        //$stmt->bind_param($user_id, $imgurl, $post_desc);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
			return 200;
		}
		else{
			return NULL;
		}
       /* if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }*/
    }



//--------------------follow request-----------------------------------
public function createfollow($user_id, $following) {
        $mu=$user_id;
        $me=$following;
        $yu="INSERT INTO following(followbyid,followtoid) VALUES('$mu','$me')";
        $stmt = $this->conn->prepare($yu);
        //$stmt->bind_param($user_id, $imgurl, $post_desc);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
			return 200;
		}
		else{
			return NULL;
		}
       /* if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }*/
    }
//--------------------follow Facilitator Grant request-----------------------------------
public function createFafollow($user_id,$usertoid,$eventid) {
        $mu=$user_id;
        $me=$usertoid;
        $mg=$eventid;
        $yu="INSERT INTO facilitating(fac_id,approvedby,event_id) VALUES('$me','$mu','$mg')";
        $stmt = $this->conn->prepare($yu);
        //$stmt->bind_param($user_id, $imgurl, $post_desc);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
			return 200;
		}
		else{
			return NULL;
		}
       /* if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }*/
    }


//--------------------follow Coordinator Grant request-----------------------------------
public function createCofollow($user_id,$usertoid,$eventid) {
        $mu=$user_id;
        $me=$usertoid;
        $mg=$eventid;
        $yu="INSERT INTO coordinating(coordinator_id,approvedby,event_id) VALUES('$me','$mu','$mg')";
        $stmt = $this->conn->prepare($yu);
        //$stmt->bind_param($user_id, $imgurl, $post_desc);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
			return 200;
		}
		else{
			return NULL;
		}
       /* if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }*/
    }

//--------------------------------------------------------------------
    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
    /**
     * Fetching all Posts of User
     * @param String $user_id id of the user
     */
    public function getUserSPost($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM posts t, users ut WHERE t.user_id = ut.id AND ut.id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Fetching all Trending Posts
     * @param String $user_id id of the user
     */
    public function getTrending($user_id) {
        $stmt = $this->conn->prepare("SELECT p.id,u.eventid,u.department,z.name,u.eventname,p.image_url,p.post_description,p.no_of_likes,p.no_of_comments FROM events u,posts p,users z WHERE u.eventid=p.event_id AND z.id=p.user_id");
      //  $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
    /**
     * Fetching all Trending Events
     * @param String $user_id id of the user
     */
    public function getTrendingEvents($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM events");
      //  $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
    /**
     * Fetching all Trending Eventsof mine(Coordinator)
     * @param String $user_id id of the user
     */
    public function getMineTrendingEvents($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM events WHERE eventid IN(SELECT event_id FROM coordinating WHERE coordinating.coordinator_id=?)");
      //  $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
        /**
     * Fetching all Coordinator deatils of a event
     * @param String $user_id id of the user
     */
    public function getCoDetails($event_id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE users.id IN(SELECT coordinator_id FROM coordinating WHERE event_id=?)");
       $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
       /**
     * Fetching all Participation deatils of a event
     * @param String $user_id id of the user
     */
    public function getParDetails($event_id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE users.id IN(SELECT user_id FROM eventfollowing WHERE event_id=?)");
       $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
    /**
     * Fetching No of followers of user
     * @param String $user_id id of the user
     */


    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getNoFollower($user_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS DATA FROM following WHERE following.followtoid=?");
        $stmt->bind_param("i",$user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($DATA);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["DATA"] = $DATA;
            //$res["task"] = $task;
            //$res["status"] = $status;
            //$res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }
    //no.of follwing
    public function getNoFollowing($user_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS DATA FROM following WHERE following.followbyid=?");
        $stmt->bind_param("i",$user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($DATA);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
             $res["DATA"] = $DATA;
            //$res["task"] = $task;
            //$res["status"] = $status;
            //$res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }
    //no.of No of coordinator of an event
    public function getnoco($event_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS DATA FROM coordinating WHERE coordinating.event_id=?");
        $stmt->bind_param("i",$event_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($DATA);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
             $res["DATA"] = $DATA;
            //$res["task"] = $task;
            //$res["status"] = $status;
            //$res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }
    //no.of event follwing
    public function getNoEveF($event_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS DATA FROM eventfollowing WHERE eventfollowing.event_id=?");
        $stmt->bind_param("i",$event_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($DATA);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
             $res["DATA"] = $DATA;
            //$res["task"] = $task;
            //$res["status"] = $status;
            //$res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }
    //gettotal revenue
    public function gettotalrevenue($event_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS DATA FROM transaction WHERE transaction.event_id=?");
        $stmt->bind_param("i",$event_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($DATA);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
             $res["DATA"] = $DATA;
            //$res["task"] = $task;
            //$res["status"] = $status;
            //$res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }
    //name and rest information of USERs by id
    public function getNameUse($user_id) {
        $stmt = $this->conn->prepare("SELECT name,email,profilepicurl,status FROM `users` WHERE users.id=?");
        $stmt->bind_param("i",$user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($name,$email,$purl,$status);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
             $res["name"] = $name;
            $res["email"] = $email;
            $res["purl"] = $purl;
            $res["status"] = $status;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }


    //////////no of posts
        public function getNoPosts($user_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS DATA FROM posts t, users ut WHERE t.user_id = ut.id AND ut.id = ?");
        $stmt->bind_param("i",$user_id);
        if ($stmt->execute()) {
            $res = array();
           $stmt->bind_result($DATA);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["DATA"] = $DATA;
            //$res["task"] = $task;
            //$res["status"] = $status;
            //$res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Updating Activating a user
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateUserState($user_id,$touser) {
        $nh=$user_id;
        $nhs=$touser;
        //$ghj="UPDATE users SET users.status = 1,users.activatedby='$nh' WHERE users.id ='$nhs'";
        $stmt = $this->conn->prepare("UPDATE users SET users.status = 1,users.activatedby='$nh' WHERE users.id ='$nhs'");
       // $stmt->bind_param("ii",$user_id,$to_user);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
    //todelete

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
    /**
     * Updating likes and dislikes
     * @param String //$task_id id of the task to delete
     */
    public function updateLikes($user_id,$post_id) {
        if($user_id==1){
        $stmt = $this->conn->prepare("UPDATE posts p SET p.no_of_likes=p.no_of_likes+1 WHERE p.id=?");
        $stmt->bind_param("i",$post_id);
        $stmt->execute();

        }else if($user_id==0){
        $stmt = $this->conn->prepare("UPDATE posts p SET p.no_of_likes=p.no_of_likes-1 WHERE p.id=?");
        $stmt->bind_param("i",$post_id);
        $stmt->execute();

        }else{

        }

        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
    /**
     * Updating likes and dislikes
     * @param String //$task_id id of the task to delete
     */
    public function updateeve($user_id,$event_id,$name,$desc) {
        $mgj=$name;
        $mgl=$desc;
        $yui=$user_id;
        $gh=$event_id;
        $stmt = $this->conn->prepare("UPDATE events  SET eventname='$mgj',eventdescription='$mgl',updatedby='$yui' WHERE eventid='$gh'");
        //$stmt->bind_param("ssii",$name,$desc,$user_id,$event_id);
        $stmt->execute();


        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
     /**
     * Updating likes and dislikes
     * @param String //$task_id id of the task to delete
     */
    public function updateProfilePic($user_id,$img_url) {

        $stmt = $this->conn->prepare("UPDATE users p SET p.profilepicurl=? WHERE p.id=?");
        $stmt->bind_param("si",$img_url,$user_id);
        $stmt->execute();

        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
/**
     * Deleting/Unliking a Post
     * @param String //$task_id id of the task to delete
     */
    public function unlikePosts($user_id, $postid) {
        $stmt = $this->conn->prepare("DELETE t FROM postlikes t WHERE t.userid = ? AND t.postid = ?");
        $stmt->bind_param("ii", $user_id, $postid);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

}

?>
