<?php

namespace Lewis\Routing;

class Routing
{
    public $config;
    public $input;

    public function __construct($config, $input)
    {
        $this->config = $config;
        $this->input  = $input;
    }

    public function getController()
    {
        $request = $this->getRequest();
        $match = $this->matchController($request['server']['PATH_INFO']);

        return $this->headerController($match);
    }

    public function getRequest()
    {
        $request['get']     = $_GET;
        $request['post']    = $_POST;
        $request['file']    = $_FILES;
        $request['session'] = $_SESSION;
        $request['cookie']  = $_COOKIE;
        $request['server']  = $_SERVER;
        $request['request'] = $_REQUEST;

        return $request;
    }

    public function matchController($requestPath)
    {
        foreach ($this->config as $config)
        {
            try {
                $urlValue = $this->matchPath($requestPath, $config['path']);

                return array("controller" => $config['call'],
                             "urlValue"   => $urlValue);
            } catch (\Exception $exc) {
                continue;
            }
        }

        return array("controller" => "404", "urlVal" => null);
    }

    public function matchPath($path, $config)
    {
        $returnValue = array();

        if("" == $config or null == $config)
            $config = "/";

        if("/" == $config and "" == $path)
            return $returnValue;

        $path = explode("/", $path);
        $config = explode("/", $config);

        foreach ($config as $key => $value) {
            if(!isset($path[$key]))
                throw new \Exception("path not match");

            if($value == $path[$key])
                continue;

            if("\$" !== substr($value, 0, 1))
                throw new \Exception("path not match");

            $valName = str_replace("\$", "", $value);
            $valContent = null;

            if(isset($path[$key]))
                $valContent = $path[$key];

            $returnValue = array_merge($returnValue,
                                       array($valName => $valContent));
        }

        return $returnValue;
    }

    public function headerController($config)
    {
        if("404" === $config["controller"]) {
            header("HTTP/1.0 404 Not Found");
            exit();
        }

        $call = explode("/", $config["controller"]);

        $team       = $call[0];
        $bundle     = $call[1];
        $controller = $call[2];
        $function   = $call[3];

        $className = "\\$team\\$bundle\\$controller";

        $class = new $className($this->input);

        call_user_func_array(array($class, "setRequest"), array($this->getRequest()));
        call_user_func_array(array($class, "setUrlValue"), array($config['urlValue']));

        return call_user_func_array(array($class, $function), array());
    }
}
