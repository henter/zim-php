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

use PHPUnit\Framework\TestCase;
use Zim\Http\Exception\MethodNotAllowedException;
use Zim\Http\Exception\NotFoundException;
use Zim\Http\Request;
use Zim\Routing\Route;
use Zim\Routing\RouteCollection;
use Zim\Routing\Router;

class UrlMatcherTest extends TestCase
{
    public function testNoMethodSoAllowed()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/foo'));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertInstanceOf(Route::class, $matcher->match('/foo'));
    }

    public function testMethodNotAllowed()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/foo', array(), array(), array('post')));

        $matcher = $this->getUrlMatcher($coll);

        try {
            $matcher->match('/foo');
            $this->fail();
        } catch (MethodNotAllowedException $e) {
            $this->assertEquals(array('POST'), $e->getAllowedMethods());
        }
    }

    public function testMethodNotAllowedOnRoot()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/', array(), array(), array('GET')));

        $request = Request::create('/', 'POST');
        $matcher = $this->getUrlMatcher($coll);

        try {
            $matcher->matchRequest($request);
            $this->fail();
        } catch (MethodNotAllowedException $e) {
            $this->assertEquals(array('GET'), $e->getAllowedMethods());
        }
    }

    public function testHeadAllowedWhenRequirementContainsGet()
    {
        $coll = new RouteCollection();
        $r = new Route('/foo', array(), array(), array('get'));
        $coll->add('foo', $r);

        $request = Request::create('/foo', 'head');
        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals($r, $matcher->matchRequest($request));
    }

    public function testMethodNotAllowedAggregatesAllowedMethods()
    {
        $coll = new RouteCollection();
        $coll->add('foo1', new Route('/foo', array(), array(), array('post')));
        $coll->add('foo2', new Route('/foo', array(), array(), array('put', 'delete')));

        $matcher = $this->getUrlMatcher($coll);

        try {
            $matcher->match('/foo');
            $this->fail();
        } catch (MethodNotAllowedException $e) {
            $this->assertEquals(array('POST', 'PUT', 'DELETE'), $e->getAllowedMethods());
        }
    }

    public function testMatch()
    {
        // test the patterns are matched and parameters are returned
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/foo/{bar}'));
        $matcher = $this->getUrlMatcher($collection);
        try {
            $matcher->match('/no-match');
            $this->fail();
        } catch (NotFoundException $e) {
        }
        $this->assertEquals(array('bar' => 'baz'), $matcher->match('/foo/baz')->getParameters());

        // test that defaults are merged
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/foo/{bar}', array('def' => 'test')));
        $matcher = $this->getUrlMatcher($collection);
        $this->assertEquals(array('bar' => 'baz', 'def' => 'test'), $matcher->match('/foo/baz')->getParameters());

        // test that route "method" is ignored if no method is given in the context
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/foo', array(), array(), array('get', 'head')));
        $matcher = $this->getUrlMatcher($collection);
        $this->assertInstanceOf(Route::class, $matcher->match('/foo'));

        // route does not match with POST method context
        $request = Request::create('/foo', 'post');
        $matcher = $this->getUrlMatcher($collection);
        try {
            $matcher->matchRequest($request);
            $this->fail();
        } catch (MethodNotAllowedException $e) {
        }

        // route does match with GET or HEAD method context
        $matcher = $this->getUrlMatcher($collection);
        $request = Request::create('/foo', 'head');
        $this->assertInstanceOf(Route::class, $matcher->match('/foo'));
        $matcher = $this->getUrlMatcher($collection);
        $this->assertInstanceOf(Route::class, $matcher->matchRequest($request));

        // route with an optional variable as the first segment
        $collection = new RouteCollection();
        $collection->add('bar', new Route('/{bar}/foo', array('bar' => 'bar'), array('bar' => 'foo|bar')));
        $matcher = $this->getUrlMatcher($collection);
        $this->assertEquals(array('bar' => 'bar'), $matcher->match('/bar/foo')->getParameters());
        $this->assertEquals(array('bar' => 'foo'), $matcher->match('/foo/foo')->getParameters());

        $collection = new RouteCollection();
        $collection->add('bar', new Route('/{bar}', array('bar' => 'bar'), array('bar' => 'foo|bar')));
        $matcher = $this->getUrlMatcher($collection);
        $this->assertEquals(array('bar' => 'foo'), $matcher->match('/foo')->getParameters());
        $this->assertEquals(array('bar' => 'bar'), $matcher->match('/')->getParameters());

        // route with only optional variables
        $collection = new RouteCollection();
        $collection->add('bar', new Route('/{foo}/{bar}', array('foo' => 'foo', 'bar' => 'bar'), array()));
        $matcher = $this->getUrlMatcher($collection);
        $this->assertEquals(array('foo' => 'foo', 'bar' => 'bar'), $matcher->match('/')->getParameters());
        $this->assertEquals(array('foo' => 'a', 'bar' => 'bar'), $matcher->match('/a')->getParameters());
        $this->assertEquals(array('foo' => 'a', 'bar' => 'b'), $matcher->match('/a/b')->getParameters());
    }

    public function testMatchWithPrefixes()
    {
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/{foo}'));
        $collection->addPrefix('/b');
        $collection->addPrefix('/a');

        $matcher = $this->getUrlMatcher($collection);
        $this->assertEquals(array('foo' => 'foo'), $matcher->match('/a/b/foo')->getParameters());
    }

    public function testMatchWithDynamicPrefix()
    {
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/{foo}'));
        $collection->addPrefix('/b');

        $matcher = $this->getUrlMatcher($collection);
        $this->assertEquals(array('foo' => 'foo'), $matcher->match('/b/foo')->getParameters());
    }

    /**
     * @expectedException \Zim\Http\Exception\NotFoundException
     */
    public function testTrailingEncodedNewlineIsNotOverlooked()
    {
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/foo'));

        $matcher = $this->getUrlMatcher($collection);
        $matcher->match('/foo%0a');
    }

    public function testMatchNonAlpha()
    {
        $collection = new RouteCollection();
        $chars = '!"$%éà &\'()*+,./:;<=>@ABCDEFGHIJKLMNOPQRSTUVWXYZ\\[]^_`abcdefghijklmnopqrstuvwxyz{|}~-';
        $collection->add('foo', new Route('/{foo}/bar', array(), array('foo' => '['.preg_quote($chars).']+'), array(), array('utf8' => true)));

        $matcher = $this->getUrlMatcher($collection);
        $this->assertEquals(array('foo' => $chars), $matcher->match('/'.rawurlencode($chars).'/bar')->getParameters());
        $this->assertEquals(array('foo' => $chars), $matcher->match('/'.strtr($chars, array('%' => '%25')).'/bar')->getParameters());
    }

    public function testMatchWithDotMetacharacterInRequirements()
    {
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/{foo}/bar', array(), array('foo' => '.+')));

        $matcher = $this->getUrlMatcher($collection);
        $this->assertEquals(array('foo' => "\n"), $matcher->match('/'.urlencode("\n").'/bar')->getParameters(), 'linefeed character is matched');
    }

    public function testMatchOverriddenRoute()
    {
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/foo'));

        $collection1 = new RouteCollection();
        $collection1->add('foo', new Route('/foo1'));

        $collection->addCollection($collection1);

        $matcher = $this->getUrlMatcher($collection);

        $this->assertEquals(array(), $matcher->match('/foo1')->getParameters());
        $this->{method_exists($this, $_ = 'expectException') ? $_ : 'setExpectedException'}('Zim\Http\Exception\NotFoundException');
        $this->assertEquals(array(), $matcher->match('/foo')->getParameters());
    }

    public function testMatchRegression()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/foo/{foo}'));
        $coll->add('bar', new Route('/foo/bar/{foo}'));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals(array('foo' => 'bar'), $matcher->match('/foo/bar/bar')->getParameters());

        $collection = new RouteCollection();
        $collection->add('foo', new Route('/{bar}'));
        $matcher = $this->getUrlMatcher($collection);
        try {
            $matcher->match('/');
            $this->fail();
        } catch (NotFoundException $e) {
        }
    }

    public function testMultipleParams()
    {
        $route2 = new Route('/foo/{a}/test/test/{b}');
        $coll = new RouteCollection();
        $coll->add('foo1', new Route('/foo/{a}/{b}'));
        $coll->add('foo2', $route2);
        $coll->add('foo3', new Route('/foo/{a}/{b}/{c}/{d}'));

        $route = $this->getUrlMatcher($coll)->match('/foo/test/test/test/bar');

        $this->assertEquals($route2, $route);
    }

    public function testDefaultRequirementForOptionalVariables()
    {
        $coll = new RouteCollection();
        $coll->add('test', new Route('/{page}.{_format}', array('page' => 'index', '_format' => 'html')));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals(array('page' => 'my-page', '_format' => 'xml'), $matcher->match('/my-page.xml')->getParameters());
    }

    public function testMatchingIsEager()
    {
        $coll = new RouteCollection();
        $coll->add('test', new Route('/{foo}-{bar}-', array(), array('foo' => '.+', 'bar' => '.+')));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals(array('foo' => 'text1-text2-text3', 'bar' => 'text4'), $matcher->match('/text1-text2-text3-text4-')->getParameters());
    }

    public function testAdjacentVariables()
    {
        $coll = new RouteCollection();
        $coll->add('test', new Route('/{w}{x}{y}{z}.{_format}', array('z' => 'default-z', '_format' => 'html'), array('y' => 'y|Y')));

        $matcher = $this->getUrlMatcher($coll);
        // 'w' eagerly matches as much as possible and the other variables match the remaining chars.
        // This also shows that the variables w-z must all exclude the separating char (the dot '.' in this case) by default requirement.
        // Otherwise they would also consume '.xml' and _format would never match as it's an optional variable.
        $this->assertEquals(array('w' => 'wwwww', 'x' => 'x', 'y' => 'Y', 'z' => 'Z', '_format' => 'xml'), $matcher->match('/wwwwwxYZ.xml')->getParameters());
        // As 'y' has custom requirement and can only be of value 'y|Y', it will leave  'ZZZ' to variable z.
        // So with carefully chosen requirements adjacent variables, can be useful.
        $this->assertEquals(array('w' => 'wwwww', 'x' => 'x', 'y' => 'y', 'z' => 'ZZZ', '_format' => 'html'), $matcher->match('/wwwwwxyZZZ')->getParameters());
        // z and _format are optional.
        $this->assertEquals(array('w' => 'wwwww', 'x' => 'x', 'y' => 'y', 'z' => 'default-z', '_format' => 'html'), $matcher->match('/wwwwwxy')->getParameters());

        $this->{method_exists($this, $_ = 'expectException') ? $_ : 'setExpectedException'}('Zim\Http\Exception\NotFoundException');
        $matcher->match('/wxy.html');
    }

    public function testOptionalVariableWithNoRealSeparator()
    {
        $coll = new RouteCollection();
        $coll->add('test', new Route('/get{what}', array('what' => 'All')));
        $matcher = $this->getUrlMatcher($coll);

        $this->assertEquals(array('what' => 'All'), $matcher->match('/get')->getParameters());
        $this->assertEquals(array('what' => 'Sites'), $matcher->match('/getSites')->getParameters());

        // Usually the character in front of an optional parameter can be left out, e.g. with pattern '/get/{what}' just '/get' would match.
        // But here the 't' in 'get' is not a separating character, so it makes no sense to match without it.
        $this->{method_exists($this, $_ = 'expectException') ? $_ : 'setExpectedException'}('Zim\Http\Exception\NotFoundException');
        $matcher->match('/ge');
    }

    public function testRequiredVariableWithNoRealSeparator()
    {
        $coll = new RouteCollection();
        $coll->add('test', new Route('/get{what}Suffix'));
        $matcher = $this->getUrlMatcher($coll);

        $this->assertEquals(array('what' => 'Sites'), $matcher->match('/getSitesSuffix')->getParameters());
    }

    public function testDefaultRequirementOfVariable()
    {
        $coll = new RouteCollection();
        $coll->add('test', new Route('/{page}.{_format}'));
        $matcher = $this->getUrlMatcher($coll);

        $this->assertEquals(array('page' => 'index', '_format' => 'mobile.html'), $matcher->match('/index.mobile.html')->getParameters());
    }

    /**
     * @expectedException \Zim\Http\Exception\NotFoundException
     */
    public function testDefaultRequirementOfVariableDisallowsSlash()
    {
        $coll = new RouteCollection();
        $coll->add('test', new Route('/{page}.{_format}'));
        $matcher = $this->getUrlMatcher($coll);

        $matcher->match('/index.sl/ash');
    }

    /**
     * @expectedException \Zim\Http\Exception\NotFoundException
     */
    public function testDefaultRequirementOfVariableDisallowsNextSeparator()
    {
        $coll = new RouteCollection();
        $coll->add('test', new Route('/{page}.{_format}', array(), array('_format' => 'html|xml')));
        $matcher = $this->getUrlMatcher($coll);

        $matcher->match('/do.t.html');
    }

    /**
     * @expectedException \Zim\Http\Exception\NotFoundException
     */
    public function testMissingTrailingSlash()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/foo/'));

        $matcher = $this->getUrlMatcher($coll);
        $matcher->match('/foo');
    }

    /**
     * @expectedException \Zim\Http\Exception\NotFoundException
     */
    public function testExtraTrailingSlash()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/foo'));

        $matcher = $this->getUrlMatcher($coll);
        $matcher->match('/foo/');
    }

    /**
     * @expectedException \Zim\Http\Exception\NotFoundException
     */
    public function testMissingTrailingSlashForNonSafeMethod()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/foo/'));

        $request = Request::create('/foo', 'POST');
        $matcher = $this->getUrlMatcher($coll);
        $matcher->matchRequest($request);
    }

    /**
     * @expectedException \Zim\Http\Exception\NotFoundException
     */
    public function testExtraTrailingSlashForNonSafeMethod()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/foo'));

        $request = Request::create('/foo/', 'POST');
        $matcher = $this->getUrlMatcher($coll);
        $matcher->matchRequest($request);
    }

    public function testDecodeOnce()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/foo/{foo}'));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals(array('foo' => 'bar%23'), $matcher->match('/foo/bar%2523')->getParameters());
    }

    public function testCannotRelyOnPrefix()
    {
        $coll = new RouteCollection();

        $subColl = new RouteCollection();
        $subColl->add('bar', new Route('/bar'));
        $subColl->addPrefix('/prefix');
        // overwrite the pattern, so the prefix is not valid anymore for this route in the collection
        $subColl->get('bar')->setPath('/new');

        $coll->addCollection($subColl);

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals(array(), $matcher->match('/new')->getParameters());
    }

    /**
     * @expectedException \Zim\Http\Exception\NotFoundException
     */
    public function testPathIsCaseSensitive()
    {
        $coll = new RouteCollection();
        $coll->add('foo', new Route('/locale', array(), array('locale' => 'EN|FR|DE')));

        $matcher = $this->getUrlMatcher($coll);
        $matcher->match('/en');
    }

    /**
     * @expectedException \Zim\Http\Exception\NotFoundException
     */
    public function testNoConfiguration()
    {
        $coll = new RouteCollection();

        $matcher = $this->getUrlMatcher($coll);
        $matcher->match('/');
    }

    public function testNestedCollections()
    {
        $coll = new RouteCollection();

        $subColl = new RouteCollection();
        $subColl->add('a', new Route('/a'));
        $subColl->add('b', new Route('/b'));
        $subColl->add('c', new Route('/c'));
        $subColl->addPrefix('/p');
        $coll->addCollection($subColl);

        $coll->add('baz', new Route('/{baz}'));

        $subColl = new RouteCollection();
        $subColl->add('buz', new Route('/buz'));
        $subColl->addPrefix('/prefix');
        $coll->addCollection($subColl);

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals('/p/a', $matcher->match('/p/a')->getPath());
        $this->assertEquals(array('baz' => 'p'), $matcher->match('/p')->getParameters());
        $this->assertEquals('/prefix/buz', $matcher->match('/prefix/buz')->getPath());
    }

    public function testSiblingRoutes()
    {
        $coll = new RouteCollection();
        $coll->add('a', (new Route('/a{a}'))->setMethods(['POST']));
        $coll->add('b', (new Route('/a{a}'))->setMethods(['PUT']));
        $coll->add('c', new Route('/a{a}'));
        $coll->add('f', (new Route('/{b}{a}'))->setRequirements(array('b' => 'b')));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals(array('a' => 'a'), $matcher->match('/aa')->getParameters());
        $this->assertEquals(array('b' => 'b', 'a' => 'a'), $matcher->match('/ba')->getParameters());
    }

    public function testUnicodeRoute()
    {
        $coll = new RouteCollection();
        $coll->add('a', new Route('/{a}', array(), array('a' => '.'), array(), array('utf8' => false)));
        $coll->add('b', new Route('/{a}', array(), array('a' => '.'), array(), array('utf8' => true)));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals(array('a' => 'é'), $matcher->match('/é')->getParameters());
    }

    public function testRequirementWithCapturingGroup()
    {
        $coll = new RouteCollection();
        $coll->add('a', new Route('/{a}/{b}', array(), array('a' => '(a|b)')));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals(array('a' => 'a', 'b' => 'b'), $matcher->match('/a/b')->getParameters());
    }

    public function testDotAllWithCatchAll()
    {
        $coll = new RouteCollection();
        $coll->add('a', new Route('/{id}.html', array(), array('id' => '.+')));
        $coll->add('b', new Route('/{all}', array(), array('all' => '.+')));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals(array('id' => 'foo/bar'), $matcher->match('/foo/bar.html')->getParameters());
    }
    
    public function testUtf8Prefix()
    {
        $coll = new RouteCollection();
        $a = $b = null;
        $coll->add('a', $a = new Route('/é{foo}', array(), array(), array(), array('utf8' => true)));
        $coll->add('b', $b = new Route('/è{bar}', array(), array(), array(), array('utf8' => true)));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals($a, $matcher->match('/éo'));
    }

    public function testUtf8AndMethodMatching()
    {
        $coll = new RouteCollection();
        $a = $b = $c = null;
        $coll->add('a', $a = new Route('/admin/api/list/{shortClassName}/{id}.{_format}', array(), array(), array('PUT'), array('utf8' => true)));
        $coll->add('b', $b = new Route('/admin/api/package.{_format}', array(), array(), array('POST')));
        $coll->add('c', $c = new Route('/admin/api/package.{_format}', array('_format' => 'json'), array(), array('GET')));

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals($c, $matcher->match('/admin/api/package.json'));
    }

    public function testSlashVariant()
    {
        $coll = new RouteCollection();
        $a = new Route('/foo/{bar}', array(), array('bar' => '.*'));
        $coll->add('a', $a);

        $matcher = $this->getUrlMatcher($coll);
        $this->assertEquals($a, $matcher->match('/foo/'));
    }

    protected function getUrlMatcher(RouteCollection $routes)
    {
        return new Router($routes);
    }
}
