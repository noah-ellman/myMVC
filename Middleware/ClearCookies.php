<?php

class ClearCookies extends Middleware {

    use TLoggable;


    public function run(Request $request, Response $response, $action) {

        //$cookies = $this->app->getService('cookies');
        //$cookies->testing = "noah";

        if( $request->query->has('clearcookies') ) {
            $this->app->getService('cookies')->clear();
            $this->log("!Clearing all cookies");
        }

        return true;


    }

}