<?

class Enviroment {


    public function __construct() {

    }

    public function isCLI() {
        return php_sapi_name() === "cli" ? true : false;
    }









}