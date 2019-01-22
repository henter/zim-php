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
            '/just_head' => array(array(array('_route' => 'just_head'), null, array('HEAD' => 0), false)),
            '/head_and_get' => array(array(array('_route' => 'head_and_get'), null, array('HEAD' => 0, 'GET' => 1), false)),
            '/get_and_head' => array(array(array('_route' => 'get_and_head'), null, array('GET' => 0, 'HEAD' => 1), false)),
            '/post_and_head' => array(array(array('_route' => 'post_and_head'), null, array('POST' => 0, 'HEAD' => 1), false)),
            '/put_and_post' => array(
                array(array('_route' => 'put_and_post'), null, array('PUT' => 0, 'POST' => 1), false),
                array(array('_route' => 'put_and_get_and_head'), null, array('PUT' => 0, 'GET' => 1, 'HEAD' => 2), false),
            ),
        );

    }
}
