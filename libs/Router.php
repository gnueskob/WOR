<?php

namespace lsb\Libs;

class Router
{
    private $request;
    private $httpMethods = array(
        "GET",
        "POST",
        "PUT"
    );
    private $group = "";

    public function __construct(IRequest $request)
    {
        $this->request = $request;
    }

    public function __call($name, $args)
    {
        list($route, $method) = $args;
        if (!in_array(strtoupper($name), $this->httpMethods)) {
            $this->invalidMethodHandler();
            return;
        }
        $this->{strtolower($name)}[$this->formatRoute($route)] = $method;
    }

    /**
     * Removes trailing forward slashes from the right of the route.
     * @param route (string)
     * @return route format
     */
    private function formatRoute($route)
    {
        $result = rtrim($route, '/');
        if ($result === '' && $this->group === "") {
            return '/';
        }
        if ($this->group !== "") {
            $result = $this->group.$result;
        }
        return $result;
    }

    private function invalidMethodHandler()
    {
        header("{$this->request->serverProtocol} 405 Method Not Allowed");
    }

    private function defaultRequestHandler()
    {
        header("{$this->request->serverProtocol} 404 Not Found");
    }

    /**
     * Resolves a route
     */
    private function resolve()
    {
        $methodDictionary = $this->{strtolower($this->request->requestMethod)};
        $formatedRoute = $this->formatRoute($this->request->requestUri);
        $method = $methodDictionary[$formatedRoute];
        if (is_null($method)) {
            $this->defaultRequestHandler();
            return;
        }
        call_user_func_array($method, array($this->request, new Response()));
    }

    public function run()
    {
        $this->resolve();
    }

    public function group($group)
    {
        $this->group = $group;
    }
}
