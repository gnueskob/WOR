<?php

namespace lsb\Libs;

use \ReflectionFunction;

class Router
{
    protected $request;
    protected $response;
    protected $httpMethods = array(
        "GET",
        "POST",
        "PUT"
    );

    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
    }

    public function __call($name, $args)
    {
        $route = null;
        $method = null;
        $authMethod = null;

        if (count($args) > 2) {
            list($route, $authMethod, $method) = $args;
        } else {
            list($route, $method) = $args;
        }
        if (!in_array(strtoupper($name), $this->httpMethods)) {
            $this->invalidMethodHandler();
            return;
        }

        $formatedRoute = $this->formatRoute($route);

        $this->{strtolower($name)}[$formatedRoute]['method'] = $method;

        if (isset($authMethod)) {
            $this->{strtolower($name)}[$formatedRoute]['auth'] = $authMethod;
        }
    }

    /**
     * Removes trailing forward slashes from the right of the route.
     * @param route (string)
     * @return route format
     */
    private function formatRoute($route)
    {
        $result = rtrim($route, '/');
        if ($result === '') {
            return '/';
        }
        return $result;
    }

    /**
     * Find regex pattern about route string
     * @param route (string)
     * @return regexPattern (string)
    */
    private function getRouteRegexPattern($route)
    {
        $paramPattern = '[a-zA-Z0-9-_]+';
        $routePattern = preg_replace('/\//', '\\/', $route);
        $routePattern = preg_replace('/:[a-zA-Z0-9-_]+/', $paramPattern, $routePattern);
        $routePattern = '/^' . $routePattern . '$/';
        return $routePattern;
    }

    /**
     * Find parameters about request route URL
     * @param string $route     route already registered in Router
     * @param string $reqRoute  route where user send request
     * @return array $params    $params[:key] = $value
    */
    private function findRouteParams($route, $reqRoute)
    {
        $paramKey = explode('/', ltrim($route, '/'));
        $paramValue = explode('/', ltrim($reqRoute, '/'));
        $params = array();
        foreach ($paramKey as $idx => $key) {
            if (preg_match('/^:.*/', $key)) {
                $params[ltrim($key, ':')] = $paramValue[$idx];
            }
        }
        return $params;
    }

    /**
     * Resolves a route
     */
    private function resolve()
    {
        $req = $this->request;
        $res = $this->response;

        $methodDictionary = $this->{strtolower($req->requestMethod)};
        $reqRoute = $this->formatRoute($req->requestUri);
        $method = null;
        $matchedRoute = null;

        if (isset($methodDictionary[$reqRoute])) {
            $method = $methodDictionary[$reqRoute]['method'];
            $matchedRoute = $reqRoute;
        } else {
            // If there is no route exactly matched with key of methodDictionary
            // Find route with regex of each route which matches with req uri
            foreach ($methodDictionary as $route => $_) {
                if (preg_match($this->getRouteRegexPattern($route), $reqRoute)) {
                    // If find route matching current URL
                    // then, register the function what we applied in Controller
                    $method = $methodDictionary[$route]['method'];
                    $matchedRoute = $route;

                    // And also register the parameters in URL to request object
                    $req->setParams($this->findRouteParams($route, $reqRoute));
                    break;
                }
            }
        }

        if (is_null($method)) {
            $this->defaultRequestHandler();
            return;
        }

        if (isset($methodDictionary[$matchedRoute]['auth'])) {
            $authMethod = $methodDictionary[$matchedRoute]['auth'];
            if (!call_user_func_array($authMethod, array($req))) {
                $this->unauthenticatedHandler();
                return;
            }
        }

        $argsNum = (new ReflectionFunction($method))->getNumberOfParameters();
        switch ($argsNum) {
            case 1:
                $methodParams = array($res);
                break;
            case 2:
                $methodParams = array($req, $res);
                break;
            default:
                $methodParams = array();
                break;
        }
        call_user_func_array($method, $methodParams);
    }

    public function run()
    {
        $this->resolve();
    }

    private function _setHeader(int $code, string $msg) {
        $res = $this->response;
        $protocol = $this->request->serverProtocol;
        $res->setHeader($protocol, $code, $msg);
    }

    // return status for invalid options
    private function invalidMethodHandler()
    {
        $this->_setHeader(405, "Method Not Allowed");
    }

    private function defaultRequestHandler()
    {
        $this->_setHeader(404, "Not Found");
    }

    private function unauthenticatedHandler()
    {
        $this->_setHeader(401, "Unauthenticated");
    }
}
