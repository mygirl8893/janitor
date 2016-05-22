<?php
/**
 * Effortless maintenance management (http://juliangut.com/janitor)
 *
 * @link https://github.com/juliangut/janitor for the canonical source repository
 *
 * @license https://github.com/juliangut/janitor/blob/master/LICENSE
 */

namespace Janitor\Test\Watcher\Scheduled;

use Janitor\Watcher\Scheduled\AbstractScheduled;

/**
 * Class AbstractScheduledTest
 */
class AbstractScheduledTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractScheduled
     */
    protected $watcher;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->watcher = $this->getMockForAbstractClass(AbstractScheduled::class);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadTimeZoneName()
    {
        $this->watcher->setTimeZone('World/Unknown');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadTimeZoneOffset()
    {
        $this->watcher->setTimeZone(158622);
    }

    public function testTimeZone()
    {
        self::assertEquals(
            (new \DateTimeZone(date_default_timezone_get()))->getName(),
            $this->watcher->getTimeZone()->getName()
        );

        $this->watcher->setTimeZone(1);
        self::assertEquals('Europe/London', $this->watcher->getTimeZone()->getName());

        $this->watcher->setTimeZone('Europe/Madrid');
        self::assertEquals('Europe/Madrid', $this->watcher->getTimeZone()->getName());
    }
}
