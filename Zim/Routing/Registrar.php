<?php

namespace Zim\Routing;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * @method get(string $uri, \Closure|array|string|null $info = null)
 * @method post(string $uri, \Closure|array|string|null $info = null)
 * @method put(string $uri, \Closure|array|string|null $info = null)
 * @method delete(string $uri, \Closure|array|string|null $info = null)
 * @method patch(string $uri, \Closure|array|string|null $info = null)
 * @method options(string $uri, \Closure|array|string|null $info = null)
 * @method any(string $uri, \Closure|array|string|null $info = null)
 * @method static match(array|string $method, string $uri, \Closure|array|string|null $info = null)
 */
class Registrar
{
    /**
     * The router instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * The methods to dynamically pass through to the router.
     *
     * @var array
     */
    protected $passthru = [
        'get', 'post', 'put', 'patch', 'delete', 'options', 'any'
    ];

    /**
     * Create a new route registrar instance.
     *
     * @param  Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Registrar a new route with the router.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  \Closure|array|string|null  $info
     * @return Route
     */
    protected function registrarRoute($method, $uri, $info = null)
    {
        if ($method == 'any') {
            $method = [];
        }
        return $this->router->addRoute($method, $uri, $info);
    }

    /**
     * Register a new route with the given verbs.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|null  $info
     * @return Route
     */
    public function doMatch($methods, $uri, $info = null)
    {
        return $this->router->addRoute($methods, $uri, $info);
    }

    /**
     * Dynamically handle calls into the route registrar.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return Route|$this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, $this->passthru)) {
            return $this->registrarRoute($method, ...$parameters);
        }

        if ($method == 'match') {
            return $this->doMatch(...$parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}
