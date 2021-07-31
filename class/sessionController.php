<?php

require_once 'class/session.php';

class SessionController extends Controller{
    
    private $userSession, $username, $userid, $session, $sites, $user;
 
    function __construct(){
        parent::__construct();

        $this->init();
    }

    public function getUserSession(){ return $this->userSession; }
    public function getUsername(){ return $this->username; }
    public function getUserId(){ return $this->userid; }

    private function init(){
        $this->session = new Session();
        $json = $this->getJSONFileConfig();
        $this->sites = $json['sites'];
        $this->defaultSites = $json['default-sites'];
        $this->validateSession();
    }
    private function getJSONFileConfig(){
        $string = file_get_contents("config/access.json");
        $json = json_decode($string, true);

        return $json;
    }


    function validateSession(){
        error_log('SessionController::validateSession()');
        if($this->existsSession()){
            error_log("SessionController::validateSession -> existe la session");
            $role = $this->getUserSessionData()->getRole();

            error_log("sessionController::validateSession(): username:" . $this->user->getUsername() . " - role: " . $this->user->getRole());
            if($this->isPublic()){
                $this->redirectDefaultSiteByRole($role);
                error_log( "SessionController::validateSession() => sitio público, redirige al main de cada rol" );
            }else{
                if($this->isAuthorized($role)){
                    error_log( "SessionController::validateSession() => autorizado, lo deja pasar" );
                }else{
                    error_log( "SessionController::validateSession() => no autorizado, redirige al main de cada rol" );
                    $this->redirectDefaultSiteByRole($role);
                }
            }
        }else{
            error_log("SessionController::validateSession -> la session no existe");
            if($this->isPublic()){
                error_log('SessionController::validateSession() public page');
            }else{
                error_log('SessionController::validateSession() redirect al login');
                header('location: '. constant('URL') . '');
            }
        }
    }
    
    function existsSession(){
        error_log("SessionController::existSession -> Funcion Exist");
        if(!$this->session->exists()){
            return false;  
        }else if($this->session->getCurrentUser() == NULL){
            return false;  
        }
        $userid = $this->session->getCurrentUser();
        if($userid){
            return true;
        }
        return false;
    }

    function getUserSessionData(){
        $id = $this->session->getCurrentUser();
        $this->user = new UserModel();
        $this->user->get($id);
        error_log("sessionController::getUserSessionData(): " . $this->user->getUsername());
        return $this->user;
    }

    public function initialize($user){
        error_log("sessionController::initialize(): user: " . $user->getUsername() . " id: " . $user->getId());
        $this->session->setCurrentUser($user->getId());
        $this->authorizeAccess($user->getRole());
    }

    private function isPublic(){
        $currentURL = $this->getCurrentPage();
        error_log("sessionController::isPublic(): currentURL => " . $currentURL);
        $currentURL = preg_replace( "/\?.*/", "", $currentURL); //omitir get info
        for($i = 0; $i < sizeof($this->sites); $i++){
            if($currentURL === $this->sites[$i]['site'] && $this->sites[$i]['access'] === 'public'){
                return true;
            }
        }
        return false;
    }

    private function redirectDefaultSiteByRole($role){
        $url = '';
        for($i = 0; $i < sizeof($this->sites); $i++){
            if($this->sites[$i]['role'] === $role){
                $url = '/binnary/'.$this->sites[$i]['site'];
            break;
            }
        }
        header('location: '.$url);
        
    }

    private function isAuthorized($role){
        $currentURL = $this->getCurrentPage();
        $currentURL = preg_replace( "/\?.*/", "", $currentURL); //omitir get info
        
        for($i = 0; $i < sizeof($this->sites); $i++){
            if($currentURL === $this->sites[$i]['site'] && $this->sites[$i]['role'] === $role){
                return true;
            }
        }
        return false;
    }

    private function getCurrentPage(){
        
        $actual_link = trim("$_SERVER[REQUEST_URI]");
        $url = explode('/', $actual_link);
        error_log("sessionController::getCurrentPage(): actualLink =>" . $actual_link . ", url => " . $url[2]);
        return $url[2];
    }

    function authorizeAccess($role){
        error_log("sessionController::authorizeAccess(): role: $role");
        switch($role){
            case 'user':
                $this->redirect($this->defaultSites['user']);
            break;
            case 'admin':
                    $this->redirect($this->defaultSites['admin']);
            break;
            default:
        }
    }

    function logout(){
        $this->session->closeSession();
    }
}


?>