<?php

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class RouteMatcher extends \Zim\Routing\Dumper\Matcher
{
    public function __construct()
    {
        $this->regexpList = array(
            0 => '{^(?'
                    .'|/(a)(*:11)'
                .')(?:/?)$}sD',
            11 => '{^(?'
                    .'|/(.)(*:22)'
                .')(?:/?)$}sDu',
            22 => '{^(?'
                    .'|/(.)(*:33)'
                .')(?:/?)$}sD',
        );
        $this->dynamicRoutes = array(
            11 => array(array(array('_route' => 'a'), array('a'), null, false)),
            22 => array(array(array('_route' => 'b'), array('a'), null, false)),
            33 => array(array(array('_route' => 'c'), array('a'), null, false)),
        );

    }
}
