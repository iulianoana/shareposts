<?php
  class Users extends Controller{
    
    public function __construct(){
      // Initialise the user model.
      $this->userModel = $this->model('User');
    }

    public function register() {
      // Check for POST
      if($_SERVER['REQUEST_METHOD'] == 'POST'){
        // Sanitize the fields.
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        // Initialise the data array.
        $data = [
          'name' => trim($_POST['name']),
          'email' => trim($_POST['email']),
          'password' => trim($_POST['password']),
          'confirm_password' => trim($_POST['confirm_password']),
          'name_err' => '',
          'email_err' => '',
          'password_err' => '',
          'confirm_password_err' => ''
        ];

        // Check if name is empty.
        if(empty($data['name'])){
          $data['name_err'] = 'Please enter your name';
        }
        
        // Check if email is empty.
        if(empty($data['email'])){
          $data['email_err'] = 'Please enter your email';
        }else if($this->userModel->findUserByEmail($data['email'])){ // If the email is taken.
          $data['email_err'] = 'This email is already in use.';
        }

        // Password validation.
        if(empty($data['password'])){ // If the password field is left empty.
          $data['password_err'] = 'Please choose a password (at least 6 characters long, have an uppercase letter and contain a number)';
        }else if(strlen($data['password']) < 6){ // If the password is less than 6 characters long.
          $data['password_err'] = 'Your password must be at least 6 characters long (have an uppercase letter and contain a number).';
        }else if(!preg_match('~[0-9]+~', $data['password'])){ // If the password does not contain a number (https://www.php.net/manual/en/function.preg-match.php).
          $data['password_err'] = 'Your password must contain a number (have an uppercase letter and be at least 6 characters long).';
        }else if(!preg_match('/[A-Z]/', $data['password'])){ // Check for uppercase.
          $data['password_err'] = 'Your password must contain an uppercase letter (contain a number and be at least 6 characters long).';
        }

        // Check for password confirmation (not empty and match).
        if(empty($data['confirm_password'])){ // If the confirm pass is empty.
          $data['confirm_password_err'] = 'Please confirm your password.'; // Throw error
        }else if($data['confirm_password'] != $data['password']){ // If not matching.
          $data['confirm_password_err'] = 'The passwords do not match.';
        }

        // If all the errors are empty.
        if(empty($data['name_err']) && empty($data['email_err']) && empty($data['password_err']) && empty($data['confirm_password_err'])){
          // Hash password.
          $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);       
          // Save user to DB.
          if($this->userModel->register($data)){
            flash('register_success', 'Your are registered, please log in');
            redirect('users/login');
          }else{
            die('Something went wrong.');
          }
        }else{
          $this->view('users/register', $data);
        }

      }else{
        $data = [
          'name' => '',
          'email' => '',
          'password' => '',
          'confirm_password' => '',
          'name_err' => '',
          'email_err' => '',
          'password_err' => '',
          'confirm_password_err' => ''
        ];

        // Load the register.php view.
        $this->view('users/register', $data);
      }
    }

    public function login(){
      // Check for POST
      if($_SERVER['REQUEST_METHOD'] == 'POST'){
        // Process form fields.
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        // Initialise the data array.
        $data = [
          'email' => trim($_POST['email']),
          'password' => trim($_POST['password']),
          'email_err' => '',
          'password_err' => '',
        ];
        
        // Check if email is empty.
        if(empty($data['email'])){
          $data['email_err'] = 'Please enter your email';
        }

        // Password validation.
        if(empty($data['password'])){ // If the password field is left empty.
          $data['password_err'] = 'Please enter your password.';
        }

        // If all the errors are empty.
        if(empty($data['email_err']) && empty($data['password_err'])){
          $loggedInUser = $this->userModel->login($data['email'], $data['password']);
          
          // If the user is logged in
          if($loggedInUser){
            $this->createUserSession($loggedInUser);
          }else{
            $data['password_err'] = 'Password incorrect';
            $this->view('users/login', $data);  
          }
        }else{
          $this->view('users/login', $data);
        }
      }else{
        $data = [
          'email' => '',
          'password' => '',
          'email_err' => '',
          'password_err' => ''
        ];

        // Load the register.php view.
        $this->view('users/login', $data);
      }
    }

    private function createUserSession($user){
      $_SESSION['user_id'] = $user->id;
      $_SESSION['user_email'] = $user->email;
      $_SESSION['user_name'] = $user->name;

      redirect('posts/index');
    }

    public function logout(){
      unset($_SESSION['user_id']);
      unset($_SESSION['user_name']);
      unset($_SESSION['user_email']);

      redirect('users/login');
    }
  }


?>