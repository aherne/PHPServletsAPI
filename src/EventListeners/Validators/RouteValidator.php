<?php
namespace Lucinda\STDOUT\EventListeners\Validators;

use Lucinda\STDOUT\Request;
use Lucinda\STDOUT\Application;
use Lucinda\STDOUT\PathNotFoundException;
use Lucinda\STDOUT\MethodNotAllowedException;
use Lucinda\STDOUT\ValidationFailedException;

/**
 * Binds information in 'application', and 'routes' XML tags with request information in order to detect final requested route
 */
class RouteValidator
{
    private $url;
    private $pathParameters=[];
    private $validParameters=[];
    
    /**
     * Performs detection process
     *
     * @param Application $application
     * @param Request $request
     */
    public function __construct(Application $application, Request $request)
    {
        $this->validateUrl($application, $request);
        $this->validateRequestMethod($application, $request);
        $this->validateParameters($application, $request);
    }
    
    /**
     * Matches requested page to a 'route' and detects path parameters, if any
     *
     * @param Application $application
     * @param Request $request
     * @throws PathNotFoundException
     */
    private function validateUrl(Application $application, Request $request): void
    {
        $url = $request->getURI()->getPage();
        if ($url=="") {
            $url = $application->getDefaultRoute();
        }
        if ($application->routes($url)===null) {
            $matchFound = false;
            $routes = $application->routes();
            foreach ($routes as $route) {
                if (strpos($route->getID(), "(")!==false) {
                    $matches = [];
                    preg_match_all("/(\(([^)]+)\))/", $route->getID(), $matches);
                    $names = $matches[2];
                    $pattern = "/^".str_replace($matches[1], "([^\/]+)", str_replace("/", "\/", $route->getID()))."$/";
                    $results = [];
                    if (preg_match_all($pattern, $url, $results)==1) {
                        $parameters = [];
                        foreach ($results as $i=>$item) {
                            if ($i==0) {
                                continue;
                            }
                            $parameters[$names[$i-1]]=$item[0];
                        }
                        $this->pathParameters = $parameters;
                        $url = $route->getID();
                        $matchFound = true;
                        break;
                    }
                }
            }
            if (!$matchFound) {
                throw new PathNotFoundException("Route could not be matched to routes.route tag @ XML: ".$url);
            }
        }
        $this->url = $url;
    }
    
    /**
     * Matches request method supported by detected route, if any, to that used in request
     *
     * @param Application $application
     * @param Request $request
     * @throws MethodNotAllowedException
     */
    private function validateRequestMethod(Application $application, Request $request): void
    {
        $validRequestMethod = $application->routes($this->url)->getValidRequestMethod();
        if ($validRequestMethod && strcasecmp($validRequestMethod, $request->getMethod())!==0) {
            throw new MethodNotAllowedException("Route allows only request method: ".$validRequestMethod);
        }
    }
    
    /**
     * Validates request and path parameters based on matching 'parameter' subtags of found 'route'
     *
     * @param Application $application
     * @param Request $request
     * @throws ValidationFailedException
     */
    private function validateParameters(Application $application, Request $request): void
    {
        // get request parameters
        $parameters = $request->parameters();
        foreach ($this->pathParameters as $k=>$v) {
            $parameters[$k] = $v;
        }
        
        $validators = $application->routes($this->url)->getValidParameters();
        foreach ($validators as $parameterName=>$class) {
            if ($class->isMandatory() && empty($parameters[$parameterName])) {
                throw new ValidationFailedException("Parameter has no value: ".$parameterName);
            }
            $className = $class->getValidator();
            $object = new $className();
            $result = $object->validate(isset($parameters[$parameterName])?$parameters[$parameterName]:null);
            if ($result===null) {
                throw new ValidationFailedException("Parameter failed validation: ".$parameterName);
            }
            $this->validParameters[$parameterName] = $result;
        }
    }
    
    /**
     * Gets route requested (value of 'url' of matching 'route' XML tag)
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
    
    /**
     * Gets path parameters detected from requested page
     *
     * @return array
     */
    public function getPathParameters(): array
    {
        return $this->pathParameters;
    }
    
    /**
     * Gets route/request parameter validation results for requested page
     *
     * @return array
     */
    public function getValidParameters(): array
    {
        return $this->validParameters;
    }
}
