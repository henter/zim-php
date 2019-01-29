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

use Zim\Routing\Route;
use Zim\Routing\RouteCollection;

/**
 * PhpMatcherDumper creates a PHP class able to match URLs for a given set of routes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 * @author Arnaud Le Blanc <arnaud.lb@gmail.com>
 * @author Nicolas Grekas <p@tchwork.com>
 */
class MatcherDumper
{
    private $routes;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    private $signalingException;

    /**
     * Dumps a set of routes to a PHP class.
     *
     * Available options:
     *
     *  * class:      The class name
     *  * base_class: The base class name
     *
     * @param array $options An array of options
     *
     * @return string A PHP class representing the matcher class
     */
    public function dump(array $options = [])
    {
        $options = array_replace(['class' => 'RouteMatcher'], $options);

        return <<<EOF
<?php

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class {$options['class']} extends \Zim\Routing\Dumper\Matcher
{
    public function __construct()
    {
{$this->generateProperties()}
    }
}

EOF;
    }

    /**
     * Generates the code for the match method implementing UrlMatcherInterface.
     */
    private function generateProperties(): string
    {
        $routes = new StaticPrefixCollection();
        foreach ($this->getRoutes()->all() as $name => $route) {
            $routes->addRoute('/(.*)', [$name, $route]);
        }

        $code = '';
        $routes = $this->getRoutes();

        list($staticRoutes, $dynamicRoutes) = $this->groupStaticRoutes($routes);

        $code .= $this->compileStaticRoutes($staticRoutes);
        $chunkLimit = \count($dynamicRoutes);

        while (true) {
            try {
                $this->signalingException = new \RuntimeException('preg_match(): Compilation failed: regular expression is too large');
                $code .= $this->compileDynamicRoutes($dynamicRoutes, $chunkLimit);
                break;
            } catch (\Exception $e) {
                if (1 < $chunkLimit && $this->signalingException === $e) {
                    $chunkLimit = 1 + ($chunkLimit >> 1);
                    continue;
                }
                throw $e;
            }
        }

        return $this->indent($code, 2);
    }

    /**
     * Splits static routes from dynamic routes, so that they can be matched first, using a simple switch.
     */
    private function groupStaticRoutes(RouteCollection $collection): array
    {
        $staticRoutes = $dynamicRegex = [];
        $dynamicRoutes = new RouteCollection();

        foreach ($collection->all() as $name => $route) {
            $compiledRoute = $route->compile();
            $regex = $compiledRoute->getRegex();
            if ($hasTrailingSlash = '/' !== $route->getPath()) {
                $pos = strrpos($regex, '$');
                $hasTrailingSlash = '/' === $regex[$pos - 1];
                $regex = substr_replace($regex, '/?$', $pos - $hasTrailingSlash, 1 + $hasTrailingSlash);
            }

            if (!$compiledRoute->getPathVariables()) {
                $url = $route->getPath();
                if ($hasTrailingSlash) {
                    $url = substr($url, 0, -1);
                }
                foreach ($dynamicRegex as list($hostRx, $rx)) {
                    if (preg_match($rx, $url)) {
                        $dynamicRegex[] = [null, $regex];
                        $dynamicRoutes->add($name, $route);
                        continue 2;
                    }
                }

                $staticRoutes[$url][$name] = [$route, $hasTrailingSlash];
            } else {
                $dynamicRegex[] = [null, $regex];
                $dynamicRoutes->add($name, $route);
            }
        }

        return [$staticRoutes, $dynamicRoutes];
    }

    /**
     * Compiles static routes in a switch statement.
     *
     * Condition-less paths are put in a static array in the switch's default, with generic matching logic.
     * Paths that can match two or more routes, or have user-specified conditions are put in separate switch's cases.
     *
     * @throws \LogicException
     */
    private function compileStaticRoutes(array $staticRoutes): string
    {
        if (!$staticRoutes) {
            return '';
        }
        $code = '';

        foreach ($staticRoutes as $url => $routes) {
            $code .= self::export($url) . " => array(\n";
            foreach ($routes as $name => list($route, $hasTrailingSlash)) {
                $code .= $this->compileRoute($route, $name, null, $hasTrailingSlash);
            }
            $code .= "),\n";
        }

        if ($code) {
            return "\$this->staticRoutes = array(\n{$this->indent($code, 1)});\n";
        }

        return $code;
    }

    /**
     * Compiles a regular expression followed by a switch statement to match dynamic routes.
     *
     * The regular expression matches both the host and the pathinfo at the same time. For stellar performance,
     * it is built as a tree of patterns, with re-ordering logic to group same-prefix routes together when possible.
     *
     * Patterns are named so that we know which one matched (https://pcre.org/current/doc/html/pcre2syntax.html#SEC23).
     * This name is used to "switch" to the additional logic required to match the final route.
     *
     * Condition-less paths are put in a static array in the switch's default, with generic matching logic.
     * Paths that can match two or more routes, or have user-specified conditions are put in separate switch's cases.
     *
     * Last but not least:
     *  - Because it is not possibe to mix unicode/non-unicode patterns in a single regexp, several of them can be generated.
     *  - The same regexp can be used several times when the logic in the switch rejects the match. When this happens, the
     *    matching-but-failing subpattern is blacklisted by replacing its name by "(*F)", which forces a failure-to-match.
     *    To ease this backlisting operation, the name of subpatterns is also the string offset where the replacement should occur.
     */
    private function compileDynamicRoutes(RouteCollection $collection, int $chunkLimit): string
    {
        if (!$collection->all()) {
            return '';
        }
        $code = '';
        $state = (object)[
            'regex'    => '',
            'routes'   => '',
            'mark'     => 0,
            'markTail' => 0,
            'vars'     => [],
        ];
        $state->getVars = static function ($m) use ($state) {
            if ('_route' === $m[1]) {
                return '?:';
            }

            $state->vars[] = $m[1];

            return '';
        };

        $chunkSize = 0;
        $prev = null;
        $perModifiers = [];
        foreach ($collection->all() as $name => $route) {
            preg_match('#[a-zA-Z]*$#', $route->compile()->getRegex(), $rx);
            if ($chunkLimit < ++$chunkSize || $prev !== $rx[0] && $route->compile()->getPathVariables()) {
                $chunkSize = 1;
                $routes = new RouteCollection();
                $perModifiers[] = [$rx[0], $routes];
                $prev = $rx[0];
            }
            $routes->add($name, $route);
        }

        foreach ($perModifiers as list($modifiers, $routes)) {
            $rx = '{^(?';
            $code .= "\n    {$state->mark} => " . self::export($rx);
            $state->mark += \strlen($rx);
            $state->regex = $rx;

            $tree = new StaticPrefixCollection();
            foreach ($routes->all() as $name => $route) {
                preg_match('#^.\^(.*)\$.[a-zA-Z]*$#', $route->compile()->getRegex(), $rx);

                $state->vars = [];
                $regex = preg_replace_callback('#\?P<([^>]++)>#', $state->getVars, $rx[1]);
                if ($hasTrailingSlash = '/' !== $regex && '/' === $regex[-1]) {
                    $regex = substr($regex, 0, -1);
                }

                $tree->addRoute($regex, [$name, $regex, $state->vars, $route, $hasTrailingSlash]);
            }

            $code .= $this->compileStaticPrefixCollection($tree, $state, 0);

            $rx = ")(?:/?)$}{$modifiers}";
            $code .= "\n        .'{$rx}',";
            $state->regex .= $rx;
            $state->markTail = 0;

            // if the regex is too large, throw a signaling exception to recompute with smaller chunk size
            set_error_handler(function ($type, $message) {
                throw 0 === strpos($message, $this->signalingException->getMessage()) ? $this->signalingException : new \ErrorException($message);
            });
            try {
                preg_match($state->regex, '');
            } finally {
                restore_error_handler();
            }
        }

        unset($state->getVars);

        return "\$this->regexpList = array({$code}\n);\n"
            . "\$this->dynamicRoutes = array(\n{$this->indent($state->routes, 1)});\n";
    }

    /**
     * Compiles a regexp tree of subpatterns that matches nested same-prefix routes.
     *
     * @param \stdClass $state A simple state object that keeps track of the progress of the compilation,
     *                         and gathers the generated switch's "case" and "default" statements
     */
    private function compileStaticPrefixCollection(StaticPrefixCollection $tree, \stdClass $state, int $prefixLen): string
    {
        $code = '';
        $prevRegex = null;
        $routes = $tree->getRoutes();

        foreach ($routes as $i => $route) {
            if ($route instanceof StaticPrefixCollection) {
                $prevRegex = null;
                $prefix = substr($route->getPrefix(), $prefixLen);
                $state->mark += \strlen($rx = "|{$prefix}(?");
                $code .= "\n            ." . self::export($rx);
                $state->regex .= $rx;
                $code .= $this->indent($this->compileStaticPrefixCollection($route, $state, $prefixLen + \strlen($prefix)));
                $code .= "\n            .')'";
                $state->regex .= ')';
                ++$state->markTail;
                continue;
            }

            list($name, $regex, $vars, $route, $hasTrailingSlash) = $route;
            /**
             * @var Route $route
             */
            $compiledRoute = $route->compile();

            if ($compiledRoute->getRegex() === $prevRegex) {
                $state->routes = substr_replace($state->routes, $this->compileRoute($route, $name, $vars, $hasTrailingSlash), -3, 0);
                continue;
            }

            $state->mark += 3 + $state->markTail + \strlen($regex) - $prefixLen;
            $state->markTail = 2 + \strlen($state->mark);
            $rx = sprintf('|%s(*:%s)', substr($regex, $prefixLen), $state->mark);
            $code .= "\n            ." . self::export($rx);
            $state->regex .= $rx;

            $prevRegex = $compiledRoute->getRegex();
            $state->routes .= sprintf("%s => array(\n%s),\n", $state->mark, $this->compileRoute($route, $name, $vars, $hasTrailingSlash));
        }

        return $code;
    }

    /**
     * Compiles a single Route to PHP code used to match it against the path info.
     */
    private function compileRoute(Route $route, string $name, $vars, bool $hasTrailingSlash): string
    {
        $defaults = $route->getDefaults();

        if (isset($defaults['_canonical_route'])) {
            $name = $defaults['_canonical_route'];
            unset($defaults['_canonical_route']);
        }

        return sprintf(
            "    array(%s, %s, %s, %s),\n",
            self::export(['_route' => $name] + $defaults),
            self::export($vars),
            self::export(array_flip($route->getMethods()) ?: null),
            self::export($hasTrailingSlash)
        );
    }

    private function indent($code, $level = 1)
    {
        $code = preg_replace('/ => array\(\n    (array\(.+),\n\),/', ' => array($1),', $code);

        return preg_replace('/^./m', str_repeat('    ', $level) . '$0', $code);
    }

    /**
     * @internal
     */
    public static function export($value): string
    {
        if (null === $value) {
            return 'null';
        }
        if (!\is_array($value)) {
            if (\is_object($value)) {
                if ($value instanceof \Closure) {
                    return self::closure_source($value);
                } else {
                    throw new \InvalidArgumentException('Zim\Routing\Route cannot contain objects.');
                }
            }

            return str_replace("\n", '\'."\n".\'', var_export($value, true));
        }
        if (!$value) {
            return 'array()';
        }

        $i = 0;
        $export = 'array(';

        foreach ($value as $k => $v) {
            if ($i === $k) {
                ++$i;
            } else {
                $export .= self::export($k) . ' => ';

                if (\is_int($k) && $i < $k) {
                    $i = 1 + $k;
                }
            }

            $export .= self::export($v) . ', ';
        }

        return substr_replace($export, ')', -2);
    }

    /**
     * @param \Closure $c
     * @return string
     * @throws \ReflectionException
     */
    public static function closure_source(\Closure $c)
    {
        $rfx = new \ReflectionFunction($c);
        $args = [];
        foreach ($rfx->getParameters() as $p) {
            $args[] = ($p->isArray() ? 'array ' : ($p->getClass() ? $p->getClass()->name . ' ' : ''))
                . ($p->isPassedByReference() ? '&' : '') . '$' . $p->name
                . ($p->isOptional() ? ' = ' . var_export($p->getDefaultValue(), true) : '');
        }
        return 'function(' . implode(',', $args) . "){\n"
            . implode('', array_slice(file($rfx->getFileName()),
                $s = $rfx->getStartLine(), $rfx->getEndLine() - $s - 1)) . '}';
    }
}