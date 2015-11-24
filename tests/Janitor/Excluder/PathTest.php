<?php
/**
 * Effortless maintenance management (http://juliangut.com/janitor)
 *
 * @link https://github.com/juliangut/janitor for the canonical source repository
 *
 * @license https://github.com/juliangut/janitor/blob/master/LICENSE
 */

namespace Janitor\Test\Excluder;

use Janitor\Excluder\Path;

/**
 * @covers Janitor\Excluder\Path
 */
class PathTest extends \PHPUnit_Framework_TestCase
{
    protected $excludedPaths = [
        '/user',
        '/blog/post',
    ];

    /**
     * @covers \Janitor\Excluder\Path::__construct
     * @covers \Janitor\Excluder\Path::addPath
     * @covers \Janitor\Excluder\Path::isExcluded
     */
    public function testIsExcluded()
    {
        $pathProvider = $this->getMock('Janitor\\Provider\\Path\\Basic');
        $pathProvider->expects($this->once())->method('getPath')->will($this->returnValue('/user'));

        $excluder = new Path($this->excludedPaths, $pathProvider);

        $this->assertTrue($excluder->isExcluded());
    }

    /**
     * @covers \Janitor\Excluder\Path::isExcluded
     */
    public function testIsNotExcluded()
    {
        $pathProvider = $this->getMock('Janitor\\Provider\\Path\\Basic');
        $pathProvider->expects($this->once())->method('getPath')->will($this->returnValue('/home'));

        $excluder = new Path($this->excludedPaths, $pathProvider);

        $this->assertFalse($excluder->isExcluded());
    }
}
