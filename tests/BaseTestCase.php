<?php
namespace Zim\Tests;

/**
 * File BaseTestCase.php
 * @henter
 * Time: 2018-03-02 11:12
 */

use PHPUnit\Framework\TestCase;
use Zim\Event\Dispatcher;
use Zim\Event\Event;
use Zim\Zim;

class BaseTestCase extends TestCase
{
    /**
     * @var Zim
     */
    public $zim;

    public function __construct()
    {
        parent::__construct();
        $this->zim = Zim::getInstance();
        $this->zim->make('config')->set('app', ['k' => 'v']);
    }

    public function getConfig(string $name)
    {
        $all = [
            'app' => ['k' => 'v']
        ];
        return $all[$name] ?? null;
    }

    /**
     * @return Dispatcher
     */
    public function getEvent()
    {
        return $this->zim->make('event');
    }

    protected function tearDown()
    {
        parent::tearDown();
    }
}
