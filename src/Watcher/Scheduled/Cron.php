<?php
/**
 * Effortless maintenance management (http://juliangut.com/janitor)
 *
 * @link https://github.com/juliangut/janitor for the canonical source repository
 *
 * @license https://github.com/juliangut/janitor/blob/master/LICENSE
 */

namespace Janitor\Watcher\Scheduled;

use Cron\CronExpression;

/**
 * Cron syntax scheduled maintenance status watcher.
 *
 * Maintenance mode is considered to be On if current date is in the interval
 * initiated by a crontab expression
 *
 * Cron expression syntax
 *   *    *    *    *    *    *
 *   |    |    |    |    |    |
 *   |    |    |    |    |    +--- Year [optional]
 *   |    |    |    |    +-------- Day of week (0-7) (Sunday=0|7)
 *   |    |    |    +------------- Month (1-12)
 *   |    |    +------------------ Day of month (1-31)
 *   |    +----------------------- Hour (0-23)
 *   +---------------------------- Minute (0-59)
 */
class Cron extends AbstractScheduled
{
    /**
     * Special cron expression shorthands.
     */
    const PERIOD_YEARLY   = '@yearly';
    const PERIOD_ANNUALLY = '@annually';
    const PERIOD_MONTHLY  = '@monthly';
    const PERIOD_WEEKLY   = '@weekly';
    const PERIOD_DAILY    = '@daily';
    const PERIOD_HOURLY   = '@hourly';

    /**
     * Mapper for especial cron expression shorthands.
     *
     * @var array
     */
    protected $expressionMapper = [
        self::PERIOD_YEARLY   => '0 0 1 1 *',
        self::PERIOD_ANNUALLY => '0 0 1 1 *',
        self::PERIOD_MONTHLY  => '0 0 1 * *',
        self::PERIOD_WEEKLY   => '0 0 * * 0',
        self::PERIOD_DAILY    => '0 0 * * *',
        self::PERIOD_HOURLY   => '0 * * * *',
    ];

    /**
     * Cron expression handler.
     *
     * @var \Cron\CronExpression
     */
    protected $expression;

    /**
     * Maintenance mode interval time.
     *
     * @var \DateInterval
     */
    protected $interval;

    /**
     * @param \Cron\CronExpression|string $expression
     * @param \DateInterval|string        $interval
     * @param mixed                       $timeZone
     */
    public function __construct($expression, $interval, $timeZone = null)
    {
        $this->setExpression($expression);
        $this->setInterval($interval);

        if ($timeZone !== null) {
            $this->setTimeZone($timeZone);
        }
    }

    /**
     * Set cron expression.
     *
     * @param \Cron\CronExpression|string $expression
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setExpression($expression)
    {
        if (!$expression instanceof CronExpression) {
            try {
                $this->expression = CronExpression::factory($expression);
            } catch (\Exception $exception) {
                throw new \InvalidArgumentException(
                    sprintf('"%s" is not a valid cron expression', $expression)
                );
            }
        }

        return $this;
    }

    /**
     * Get cron expression.
     *
     * @return string
     */
    public function getExpression()
    {
        $expression = $this->expression ? $this->expression->getExpression() : '';

        return !array_key_exists($expression, $this->expressionMapper)
            ? $expression
            : $this->expressionMapper[$expression];
    }

    /**
     * Sets a valid \DateInterval.
     *
     * @param \DateInterval|string $interval
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setInterval($interval)
    {
        if (!$interval instanceof \DateInterval) {
            try {
                $interval = new \DateInterval($interval);
            } catch (\Exception $exception) {
                throw new \InvalidArgumentException(
                    sprintf('"%s" is not a valid DateInterval string definition', $interval)
                );
            }
        }

        $this->interval = $interval;

        return $this;
    }

    /**
     * Get maintenance interval.
     *
     * @return \DateInterval
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * {@inheritdoc}
     */
    public function getStart()
    {
        if (!$this->isActive()) {
            return;
        }

        $now = new \DateTime('now', $this->getTimeZone());

        return $this->expression->getPreviousRunDate($now, 0, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getEnd()
    {
        if (!$this->isActive()) {
            return;
        }

        $now = new \DateTime('now', $this->getTimeZone());

        $end = $this->expression->getPreviousRunDate($now, 0, true);
        $end->add($this->interval);

        return $end;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledTimes($count = 5)
    {
        try {
            $now = new \DateTime('now', $this->getTimeZone());

            $runDates = $this->expression->getMultipleRunDates($count, $now);
        // @codeCoverageIgnoreStart
        } catch (\RuntimeException $exception) {
            return [];
        }
        // @codeCoverageIgnoreEnd

        $interval = $this->interval;

        return array_map(
            function (\DateTime $start) use ($interval) {
                $end = clone $start;
                $end->add($interval);

                return [
                    'start' => $start,
                    'end'   => $end,
                ];
            },
            $runDates
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isScheduled()
    {
        try {
            $now = new \DateTime('now', $this->getTimeZone());

            return $this->expression->getNextRunDate($now) instanceof \DateTime;
        // @codeCoverageIgnoreStart
        } catch (\Exception $exception) {
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        $now = new \DateTime('now', $this->getTimeZone());

        try {
            $limitDate = $this->expression->getPreviousRunDate($now, 0, true);
            $limitDate->add($this->interval);
        // @codeCoverageIgnoreStart
        } catch (\RuntimeException $exception) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        return $now <= $limitDate;
    }
}
