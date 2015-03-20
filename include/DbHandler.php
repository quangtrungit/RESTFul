<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Halley Team
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `user` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($email, $password) {
        require_once 'PassHash.php';

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            $sql_query = "INSERT INTO user(email, password, api_key, status) values(?, ?, ?, ". USER_NOT_ACTIVATE. ")";

            // insert query
            if ($stmt = $this->conn->prepare($sql_query)) {
                $stmt->bind_param("sss", $email, $password_hash, $api_key);

                $result = $stmt->execute();
            } else {
                var_dump($this->conn->error);
            }

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
    }

    /**
     * Activate user
     * @param String $activation_code Activation code
     */
    public function activateUser($activation_code) {
        // fetching user by activation code
        $sql_query = "SELECT user_id FROM user WHERE api_key = ? AND status = ". USER_NOT_ACTIVATE;

        $stmt = $this->conn->prepare($sql_query);

        $stmt->bind_param("s", $activation_code);

        if ($stmt->execute()) {
            $stmt->bind_result($user_id);

            $stmt->store_result();

            $stmt->fetch();
        }

        if ($stmt->num_rows > 0) {
            // Found user with the activation code
            // Now activate user

            $api_key = $this->generateApiKey();

            $sql_query = "UPDATE user SET api_key = ?, status = ". USER_ACTIVATED. " WHERE user_id = ". $user_id;

            // insert query
            if ($stmt = $this->conn->prepare($sql_query)) {
                $stmt->bind_param("s", $api_key);

                $result = $stmt->execute();
            } else {
                var_dump($user_id);
                var_dump($this->conn->error);
            }

            $stmt->close();

            return USER_ACTIVATED_SUCCESSFULLY;
        } else {
            $stmt->close();

            return USER_ACTIVATE_FAILED;
        }       
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password, status FROM user WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash, $status);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                //Check status of user
                if ($status > 1) {
                    return LOGIN_SUCCESSFULL;
                } else {
                    // User password is correct
                    return USER_NOT_ACTIVATE;
                }
            } else {
                // user password is incorrect
                return WRONG_PASSWORD;
            }
        } else {
            $stmt->close();
            // user not existed with the email
            return USER_NOT_REGISTER;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT user_id from user WHERE email = ?");
        $stmt->bind_param("s", $email);
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
        $stmt = $this->conn->prepare("SELECT email, api_key, fullname, phone, personalID, 
                                        personalID_img, link_avatar, status, created_at FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($email, $api_key, $fullname, $phone, $personalID, $personalID_img,
                                    $link_avatar, $status, $created_at);
            $stmt->fetch();
            $user = array();
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["fullname"] = $fullname;
            $user["phone"] = $phone;
            $user["personalID"] = $personalID;
            $user["personalID_img"] = $personalID_img;
            $user["link_avatar"] = $link_avatar;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByUserID($user_id) {
        $stmt = $this->conn->prepare("SELECT email, api_key, fullname, phone, personalID, 
                                        personalID_img, link_avatar, status, created_at FROM user WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($email, $api_key, $fullname, $phone, $personalID, $personalID_img,
                                    $link_avatar, $status, $created_at);
            $stmt->fetch();
            $user = array();
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["fullname"] = $fullname;
            $user["phone"] = $phone;
            $user["personalID"] = $personalID;
            $user["personalID_img"] = $personalID_img;
            $user["link_avatar"] = $link_avatar;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user field by user_id
     * @param String $field User User field want to get
     * @param String $user_id User id
     */
    public function getUserByField($user_id, $field) {

        $stmt = $this->conn->prepare("SELECT ".$field." FROM user WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($field);
            $stmt->fetch();
            $stmt->close();
            return $field;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getListUser() {
        $stmt = $this->conn->prepare("SELECT email, api_key, fullname, phone, personalID, 
                                        personalID_img, link_avatar, status, created_at FROM user");
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $users = $stmt->get_result();
            $stmt->close();
            return $users;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT user_id FROM user WHERE api_key = ?");
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
     * Change password
     * @param String $user_id id of user
     * @param String $password Password
     */
    public function changePassword($user_id, $password) {
        // Generating password hash
        $password_hash = PassHash::hash($password);

        $stmt = $this->conn->prepare("UPDATE user set password = ?
                                        WHERE user_id = ?");
        $stmt->bind_param("si", $password_hash, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Updating user
     * @param String $user_id id of user
     * @param String $fullname Fullname
     * @param String $phone Phone Number
     * @param String $personalID Personal Identification
     * @param String $personalID_img Personal Identification Image
     * @param String $link_avatar Link Avartar
     */
    public function updateUser($user_id, $fullname, $phone, $personalID, $personalID_img, $link_avatar) {
        $stmt = $this->conn->prepare("UPDATE user set fullname = ?, phone = ?, personalID = ?,
                                        personalID_img = ?, link_avatar = ?
                                        WHERE user_id = ?");
        $stmt->bind_param("sssssi", $fullname, $phone, $personalID, $personalID_img, $link_avatar, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;

        $stmt = $this->conn->prepare("SELECT fullname, phone, personalID, 
                                        personalID_img, link_avatar, status FROM user WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        if ($stmt->execute()) {
            $stmt->bind_result($fullname, $phone, $personalID, $personalID_img, $link_avatar, $status);
            $stmt->fetch();
            if ($status < 7 && $status > 2) {
                if ($personalID_img != NULL) $status = 3;
                if (!$fullname == NULL) $status++;
                if (!$fullname == NULL) $status++;
                if (!$fullname == NULL) $status++;
            }
        }

        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Delete user
     * @param String $user_id id of user
     */
    public function deleteUser($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `staff` table method ------------------ */

    /**
     * Creating new staff
     * @param String $fullname Staff full name
     * @param String $email Staff login email id
     * @param String $personalID Staff personal ID
     */
    public function createStaff($role, $email, $fullname, $personalID) {
        require_once 'PassHash.php';

        // First check if user already existed in db
        if (!$this->isStaffExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($email);

            // Generating API key
            $api_key = $this->generateApiKey();

            $sql_query = "INSERT INTO staff(email, password, api_key, role, fullname, personalID) 
                            values(?, ?, ?, ?, ?, ?)";

            // insert query
            if ($stmt = $this->conn->prepare($sql_query)) {
                $stmt->bind_param("sssiss", $email, $password_hash, $api_key, $role==NULL?ROLE_STAFF:$role,
                                    $fullname==NULL?' ':$fullname, $personalID==NULL?' ':$personalID);
                $result = $stmt->execute();
                echo $personalID;
            } else {
                var_dump($this->conn->error);
            }

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return STAFF_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return STAFF_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return STAFF_ALREADY_EXISTED;
        }
    }

    /**
     * Checking staff login
     * @param String $email staff login email id
     * @param String $password staff login password
     * @return boolean User login status success/fail
     */
    public function checkLoginStaff($email, $password) {
        // fetching staff by email
        $stmt = $this->conn->prepare("SELECT password, role FROM staff WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash, $role);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                return LOGIN_SUCCESSFULL;
            } else {
                // staff password is incorrect
                return WRONG_PASSWORD;
            }
        } else {
            $stmt->close();
            // staff not existed with the email
            return STAFF_NOT_REGISTER;
        }
    }

    /**
     * Fetching staff by email
     * @param String $email Staff email id
     */
    public function getStaffByEmail($email) {
        $stmt = $this->conn->prepare("SELECT role, email, api_key, fullname, personalID, created_at 
                                        FROM staff WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($role, $email, $api_key, $fullname,$personalID, $created_at);
            $stmt->fetch();
            $staff = array();
            $staff["role"] = $role;
            $staff["email"] = $email;
            $staff["api_key"] = $api_key;
            $staff["fullname"] = $fullname;
            $staff["personalID"] = $personalID;
            $staff["created_at"] = $created_at;
            $stmt->close();
            return $staff;
        } else {
            return NULL;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isStaffExists($email) {
        $stmt = $this->conn->prepare("SELECT staff_id from staff WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching staff id by api key
     * @param String $api_key staff api key
     */
    public function getStaffId($api_key) {
        $stmt = $this->conn->prepare("SELECT staff_id FROM staff WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($staff_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $staff_id;
        } else {
            return NULL;
        }
    }

    /* ------------- `itinerary` table method ------------------ */

    //not finished yet
    /**
     * Creating new itinerary
     * @param Integer $driver_id user id to whom itinerary belongs to
     * @param String $start_address, $end_address, $leave_day, $duration, $cost, $description are itinerary's properties
     */
    public function createItinerary($driver_id, $start_address, $end_address, $leave_day, $duration, $cost, $description) {
        $q = "INSERT INTO itinerary(driver_id, start_address, end_address, leave_date, duration, cost, description, status) ";
                $q .= " VALUES(?,?,?,?,?,?,?,". ITINERARY_STATUS_NOTACCEPT.")";
        $stmt = $this->conn->prepare($q);
        $stmt->bind_param("isssids",$driver_id, $start_address, $end_address, $leave_day, $duration, $cost, $description);
        
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $new_itinerary_id = $this->conn->insert_id;
            
            // Itinerary successfully inserted
            return $new_itinerary_id;
            
        } else {
            echo $q;
            return NULL;
        }

        // Check for successful insertion
        if ($result) {
            // Itinerary successfully inserted
            return ITINERARY_CREATED_SUCCESSFULLY;
        } else {
            // Failed to create itinerary
            return ITINERARY_CREATE_FAILED;
        }

    }

    //not finished yet
    /**
     * Fetching single itinerary
     * @param Integer $itinerary_id id of the itinerary
     */
    public function getItinerary($itinerary_id) {
        $q = "SELECT * FROM itinerary WHERE itinerary_id = ?";
        $stmt = $this->conn->prepare($q);
        $stmt->bind_param("i",$itinerary_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($itinerary_id, $driver_id, $customer_id, $start_address, $pick_up_address, $drop_address, $end_address, $leave_date, $duration, $cost, $description, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["itinerary_id"] = $itinerary_id;
            $res["driver_id"] = $driver_id;
            $res["customer_id"] = $customer_id;
            $res["start_address"] = $start_address;
            $res["pick_up_address"] = $pick_up_address;
            $res["drop_address"] = $drop_address;
            $res["end_address"] = $end_address;
            $res["leave_date"] = $leave_date;
            $res["duration"] = $duration;
            $res["cost"] = $cost;
            $res["description"] = $description;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }

    }

    //not finished yet
    /**
     * Fetching all itineraries
     */
    public function getAllItineraries() {
        $q = "SELECT * FROM itinerary";
        $stmt = $this->conn->prepare($q);
        $stmt->execute();
        $itineraries = $stmt->get_result();
        $stmt->close();
        return $itineraries;
    }

    //not finished yet
    /**
     * Fetching all itineraries of one driver
     * @param Integer $driver_id id of the driver
     */
    public function getDriverItineraries($driver_id) {
        $q = "SELECT * FROM itinerary WHERE driver_id = ?";
        $stmt = $this->conn->prepare($q);
        $stmt->bind_param("i",$driver_id);
        $stmt->execute();
        $itineraries = $stmt->get_result();
        $stmt->close();
        return $itineraries;
    }

    //not finished yet
    /**
     * Fetching all itineraries of one customer
     * @param Integer $customer_id id of the customer
     */
    public function getCustomerItineraries($customer_id) {
        $q = "SELECT * FROM itinerary WHERE customer_id = ?";
        $stmt = $this->conn->prepare($q);
        $stmt->bind_param("i",$customer_id);
        $stmt->execute();
        $itineraries = $stmt->get_result();
        $stmt->close();
        return $itineraries;
    }

    //not finished yet
    /**
     * Updating itinerary before accept
     * @param Integer $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateItinerary($itinerary_id) {
        $q = "UPDATE itinerary set start_address = ?, end_address = ?, leave_day = ?, duration = ?, cost = ?, description = ? 
                WHERE itinerary_id = ?";
        $stmt = $this->conn->prepare();
        $stmt->bind_param("sssidsi", $itinerary_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    //not finished yet
    /**
     * Updating itinerary
     * @param Aray $itinerary_fields properties of the itinerary
     * @param Integer $itinerary_id id of the itinerary
     */
    public function updateItinerary2($itinerary_fields, $itinerary_id) {

        $q= "UPDATE itinerary SET ";
        foreach ($itinerary_fields as $key => $value) {
            //check whether the value is numeric
            if(!is_numeric($value)){
                $q .= "{$key} = '{$value}', ";
            } else {
                $q .= "{$key} = {$value}, ";
            }
            
        }

        $q = trim(($q));

        $nq = substr($q, 0, strlen($q) - 1 );

        $nq .= " WHERE itinerary_id = {$itinerary_id} LIMIT 1";

        $stmt = $this->conn->prepare($nq);

        print_r($nq);
        
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    //not finished yet
    /**
     * Deleting a itinerary
     * @param String $itinerary_id id of the itinerary to delete
     */
    public function deleteItinerary($itinerary_id) {
        $stmt = $this->conn->prepare("DELETE FROM itinerary WHERE itinerary_id = ?");
        $stmt->bind_param("i", $itinerary_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- Utility method ------------------ */

    /**
     * Fetching api key
     * @param String $id id primary key in table
     */
    public function getApiKeyById($id, $page) {
        $stmt = $this->conn->prepare("SELECT api_key FROM ".$page." WHERE id = ?");
        $stmt->bind_param("i", $id);
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
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key, $page) {
        $stmt = $this->conn->prepare("SELECT ".$page."_id from ".$page." WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
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
}

?>
