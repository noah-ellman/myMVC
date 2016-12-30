<?php

abstract class HttpRouter extends Router {


    public function __construct(Request $request) {
        parent::__construct($request);
    }

    protected function boot() {
        if ( isset($this->request->json) ) $this->responseContentType = "json";
        $this->routePath = $this->request->route();
        $this->setup($this->parseRoutePath());
        parent::boot();
    }

    public function run() {
        parent::run();
    }

    protected function parseRoutePath() {
        $routes = explode('/', $this->routePath);
        foreach ( $routes as $v ) {
            if ( !empty($v) ) $this->route[] = $v;
        }
        $this->route[0] = $this->route[0] ?? $this->getDefaultController();
        $this->route[1] = $this->route[1] ?? 'index';
        $this->route[2] = $this->route[2] ?? '';
        return $this->route;
    }

    public function getResponseContentType() {
        return $this->responseContentType ?? 'html';
    }

    protected function normalizeString($string) {
        $parts = preg_split('/[-_ ]*/', $string);
        array_walk($parts, 'ucwords');
        return implode('', $parts);

    }


}
