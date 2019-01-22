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
                    .'|/abc([^/]++)/(?'
                        .'|1(?'
                            .'|(*:27)'
                            .'|0(?'
                                .'|(*:38)'
                                .'|0(*:46)'
                            .')'
                        .')'
                        .'|2(?'
                            .'|(*:59)'
                            .'|0(?'
                                .'|(*:70)'
                                .'|0(*:78)'
                            .')'
                        .')'
                    .')'
                .')(?:/?)$}sD',
        );
        $this->dynamicRoutes = array(
            27 => array(array(array('_route' => 'r1'), array('foo'), null, false)),
            38 => array(array(array('_route' => 'r10'), array('foo'), null, false)),
            46 => array(array(array('_route' => 'r100'), array('foo'), null, false)),
            59 => array(array(array('_route' => 'r2'), array('foo'), null, false)),
            70 => array(array(array('_route' => 'r20'), array('foo'), null, false)),
            78 => array(array(array('_route' => 'r200'), array('foo'), null, false)),
        );

    }
}
