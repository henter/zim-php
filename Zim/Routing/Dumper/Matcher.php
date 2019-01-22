<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zim\Routing\Dumper;

use Zim\Http\Exception\MethodNotAllowedException;
use Zim\Http\Exception\NotFoundException;
use Zim\Http\Request;
use Zim\Routing\Route;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class Matcher
{
    /**
     * @var Request
     */
    protected $request;

    protected $staticRoutes = array();
    protected $regexpList = array();
    protected $dynamicRoutes = array();
    protected $allow = [];
    protected $method;

    public function matchRequest(Request $request)
    {
        return $this->match($request->getPathInfo(), $request->getMethod());
    }

    private function matchedRoute(array $ret)
    {
        /**
         * @var Route $route
         */
        $route = \Zim\Zim::app('router')->get($ret['_route']);
        unset($ret['_route']);//TODO
        $route->setParameters($ret);
        return $route;
    }

    public function match($pathinfo, $method = 'GET')
    {
        $this->method = $method;
        if ($ret = $this->doMatch($pathinfo)) {
            return $this->matchedRoute($ret);
        }
        if ($this->allow) {
            throw new MethodNotAllowedException(array_keys($this->allow));
        }

        if (!\in_array($this->method, array('HEAD', 'GET'), true)) {
            // no-op
        } elseif ('/' !== $pathinfo) {
            //disable trailing slash redirect
            //see https://symfony.com/doc/current/routing.html#redirecting-urls-with-trailing-slashes
//            $pathinfo = '/' !== $pathinfo[-1] ? $pathinfo.'/' : substr($pathinfo, 0, -1);
//            if ($ret = $this->doMatch($pathinfo)) {
//                return $this->matchedRoute($ret);
//            }
        }

        throw new NotFoundException();
    }

    private function doMatch(string $rawPathinfo): ?array
    {
        $pathinfo = rawurldecode($rawPathinfo) ?: '/';
        $requestMethod = $canonicalMethod = $this->method;
        $trimmedPathinfo = '/' !== $pathinfo && '/' === $pathinfo[-1] ? substr($pathinfo, 0, -1) : $pathinfo;

        if ('HEAD' === $requestMethod) {
            $canonicalMethod = 'GET';
        }

        foreach ($this->staticRoutes[$trimmedPathinfo] ?? array() as list($ret, $requiredHost, $requiredMethods, $hasTrailingSlash)) {
            if ('/' === $pathinfo || $hasTrailingSlash === ('/' === $pathinfo[-1])) {
                // no-op
            } else {
                continue;
            }

            if ($requiredMethods && !isset($requiredMethods[$canonicalMethod]) && !isset($requiredMethods[$requestMethod])) {
                $this->allow = array_merge($this->allow, $requiredMethods);
                continue;
            }

            return $ret;
        }

        $matchedPathinfo = $pathinfo;

        foreach ($this->regexpList as $offset => $regex) {
            while (preg_match($regex, $matchedPathinfo, $matches)) {
                foreach ($this->dynamicRoutes[$m = (int) $matches['MARK']] as list($ret, $vars, $requiredMethods, $hasTrailingSlash)) {

                    if ('/' === $pathinfo || (
                                                !$hasTrailingSlash ?
                                                    '/' !== $pathinfo[-1] || !preg_match($regex, substr($pathinfo, 0, -1), $n) || $m !== (int) $n['MARK']
                                                    : '/' === $pathinfo[-1])
                    ) {
                        // no-op
                    } else {
                        continue;
                    }

                    foreach ($vars as $i => $v) {
                        if (isset($matches[1 + $i])) {
                            $ret[$v] = $matches[1 + $i];
                        }
                    }

                    if ($requiredMethods && !isset($requiredMethods[$canonicalMethod]) && !isset($requiredMethods[$requestMethod])) {
                        $this->allow = array_merge($this->allow, $requiredMethods);
                        continue;
                    }

                    return $ret;
                }

                $regex = substr_replace($regex, 'F', $m - $offset, 1 + \strlen($m));
                $offset += \strlen($m);
            }
        }

        if ('/' === $pathinfo && !$this->allow) {
            throw new NotFoundException();
        }

        return null;
    }

}
