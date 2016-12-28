<?php
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Response extends SymfonyResponse {

    use TLoggable;

    protected $view;

    protected $isXmlHttpRequest = false;

    public function redirect($url = '/', $code = 302, $headers = []) {
        $response = SymfonyRedirectResponse::create($url, $code, $headers);
        return $response;
    }

    public function prepare(SymfonyRequest $request) {
        if ($request->isXmlHttpRequest()) $this->isXmlHttpRequest(true);
        return parent::prepare($request);
    }

    public function isXmlHttpRequest($yesno = null) {
        if ($yesno === null) return $this->isXmlHttpRequest;
        if (!!$yesno) {
            if (!defined('AJAX')) define('AJAX', 1);
            $this->isXmlHttpRequest = !!$yesno;
        }
        else return $this;
    }

    public function setView(View $view) {
        $this->log(__METHOD__);
        $this->view = $view;
        return $this;
    }

    public function send() {
        $this->log(__METHOD__);
        if (!$this->isRedirection()) {
            if ($this->view instanceof View) {
                if (!$this->getContent()) $this->setContent($this->view->renderToString());
                //$this->setContent($this->view->renderToString());
            }
        }
        return parent::send();
    }

    public function appendContent($content) {
        $this->setContent($this->getContent() . $content);
    }

}