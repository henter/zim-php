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
            '/trailing/simple/no-methods' => array(array(array('_route' => 'simple_trailing_slash_no_methods'), null, null, true)),
            '/trailing/simple/get-method' => array(array(array('_route' => 'simple_trailing_slash_GET_method'), null, array('GET' => 0), true)),
            '/trailing/simple/head-method' => array(array(array('_route' => 'simple_trailing_slash_HEAD_method'), null, array('HEAD' => 0), true)),
            '/trailing/simple/post-method' => array(array(array('_route' => 'simple_trailing_slash_POST_method'), null, array('POST' => 0), true)),
            '/not-trailing/simple/no-methods' => array(array(array('_route' => 'simple_not_trailing_slash_no_methods'), null, null, false)),
            '/not-trailing/simple/get-method' => array(array(array('_route' => 'simple_not_trailing_slash_GET_method'), null, array('GET' => 0), false)),
            '/not-trailing/simple/head-method' => array(array(array('_route' => 'simple_not_trailing_slash_HEAD_method'), null, array('HEAD' => 0), false)),
            '/not-trailing/simple/post-method' => array(array(array('_route' => 'simple_not_trailing_slash_POST_method'), null, array('POST' => 0), false)),
        );
        $this->regexpList = array(
            0 => '{^(?'
                    .'|/trailing/regex/(?'
                        .'|no\\-methods/([^/]++)(*:46)'
                        .'|get\\-method/([^/]++)(*:73)'
                        .'|head\\-method/([^/]++)(*:101)'
                        .'|post\\-method/([^/]++)(*:130)'
                    .')'
                    .'|/not\\-trailing/regex/(?'
                        .'|no\\-methods/([^/]++)(*:183)'
                        .'|get\\-method/([^/]++)(*:211)'
                        .'|head\\-method/([^/]++)(*:240)'
                        .'|post\\-method/([^/]++)(*:269)'
                    .')'
                .')(?:/?)$}sD',
        );
        $this->dynamicRoutes = array(
            46 => array(array(array('_route' => 'regex_trailing_slash_no_methods'), array('param'), null, true)),
            73 => array(array(array('_route' => 'regex_trailing_slash_GET_method'), array('param'), array('GET' => 0), true)),
            101 => array(array(array('_route' => 'regex_trailing_slash_HEAD_method'), array('param'), array('HEAD' => 0), true)),
            130 => array(array(array('_route' => 'regex_trailing_slash_POST_method'), array('param'), array('POST' => 0), true)),
            183 => array(array(array('_route' => 'regex_not_trailing_slash_no_methods'), array('param'), null, false)),
            211 => array(array(array('_route' => 'regex_not_trailing_slash_GET_method'), array('param'), array('GET' => 0), false)),
            240 => array(array(array('_route' => 'regex_not_trailing_slash_HEAD_method'), array('param'), array('HEAD' => 0), false)),
            269 => array(array(array('_route' => 'regex_not_trailing_slash_POST_method'), array('param'), array('POST' => 0), false)),
        );

    }
}
