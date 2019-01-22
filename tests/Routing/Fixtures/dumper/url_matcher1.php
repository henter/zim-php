<?php

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class RouteMatcher extends \Zim\Routing\Dumper\Matcher
{
    public function __construct()
    {
        $this->staticRoutes = array(
            '/test/baz' => array(array(array('_route' => 'baz'), null, null, false)),
            '/test/baz.html' => array(array(array('_route' => 'baz2'), null, null, false)),
            '/test/baz3' => array(array(array('_route' => 'baz3'), null, null, true)),
            '/foofoo' => array(array(array('_route' => 'foofoo', 'def' => 'test'), null, null, false)),
            '/spa ce' => array(array(array('_route' => 'space'), null, null, false)),
            '/multi/new' => array(array(array('_route' => 'overridden2'), null, null, false)),
            '/multi/hey' => array(array(array('_route' => 'hey'), null, null, true)),
            '/ababa' => array(array(array('_route' => 'ababa'), null, null, false)),
        );
        $this->regexpList = array(
            0 => '{^(?'
                    .'|/foo/(baz|symfony)(*:25)'
                    .'|/bar(?'
                        .'|/([^/]++)(*:48)'
                        .'|head/([^/]++)(*:68)'
                    .')'
                    .'|/test/([^/]++)(?'
                        .'|(*:93)'
                    .')'
                    .'|/([\']+)(*:108)'
                    .'|/a/(?'
                        .'|b\'b/([^/]++)(?'
                            .'|(*:137)'
                            .'|(*:145)'
                        .')'
                        .'|(.*)(*:158)'
                        .'|b\'b/([^/]++)(?'
                            .'|(*:181)'
                            .'|(*:189)'
                        .')'
                    .')'
                    .'|/multi/hello(?:/([^/]++))?(*:225)'
                    .'|/([^/]++)/b/([^/]++)(?'
                        .'|(*:256)'
                        .'|(*:264)'
                    .')'
                    .'|/a(?'
                        .'|ba/([^/]++)(*:289)'
                        .'|/(?'
                            .'|a\\.\\.\\.(*:308)'
                            .'|b/(?'
                                .'|([^/]++)(*:329)'
                                .'|c/([^/]++)(*:347)'
                            .')'
                        .')'
                    .')'
                .')(?:/?)$}sD',
        );
        $this->dynamicRoutes = array(
            25 => array(array(array('_route' => 'foo', 'def' => 'test'), array('bar'), null, false)),
            48 => array(array(array('_route' => 'bar'), array('foo'), array('GET' => 0, 'HEAD' => 1), false)),
            68 => array(array(array('_route' => 'barhead'), array('foo'), array('GET' => 0), false)),
            93 => array(
                array(array('_route' => 'baz4'), array('foo'), null, true),
                array(array('_route' => 'baz5'), array('foo'), array('POST' => 0), true),
                array(array('_route' => 'baz.baz6'), array('foo'), array('PUT' => 0), true),
            ),
            108 => array(array(array('_route' => 'quoter'), array('quoter'), null, false)),
            137 => array(array(array('_route' => 'foo1'), array('foo'), array('PUT' => 0), false)),
            145 => array(array(array('_route' => 'bar1'), array('bar'), null, false)),
            158 => array(array(array('_route' => 'overridden'), array('var'), null, false)),
            181 => array(array(array('_route' => 'foo2'), array('foo1'), null, false)),
            189 => array(array(array('_route' => 'bar2'), array('bar1'), null, false)),
            225 => array(array(array('_route' => 'helloWorld', 'who' => 'World!'), array('who'), null, false)),
            256 => array(array(array('_route' => 'foo3'), array('_locale', 'foo'), null, false)),
            264 => array(array(array('_route' => 'bar3'), array('_locale', 'bar'), null, false)),
            289 => array(array(array('_route' => 'foo4'), array('foo'), null, false)),
            308 => array(array(array('_route' => 'a'), array(), null, false)),
            329 => array(array(array('_route' => 'b'), array('var'), null, false)),
            347 => array(array(array('_route' => 'c'), array('var'), null, false)),
        );

    }
}
