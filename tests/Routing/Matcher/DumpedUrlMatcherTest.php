<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zim\Tests\Routing\Matcher;

use Zim\Routing\Dumper\MatcherDumper;
use Zim\Routing\RouteCollection;

class DumpedUrlMatcherTest extends UrlMatcherTest
{
    protected function getUrlMatcher(RouteCollection $routes)
    {
        \Zim\Zim::app('router')->setRoutes($routes);
        static $i = 0;

        $class = 'DumpedUrlMatcher'.++$i;
        $dumper = new MatcherDumper($routes);
        eval('?>'.$dumper->dump(array('class' => $class)));

        return new $class();
    }
}
