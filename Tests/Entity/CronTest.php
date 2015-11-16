<?php

namespace Roro\Bundle\CronBundle\Tests\Entity;

use Roro\Bundle\CronBundle\Entity\Cron;

class CronTest extends \PHPUnit_Framework_TestCase
{
    use \Codeception\Specify;

    protected $cron;
    protected $logFilePath;
    protected $errorFilePath;
    protected $minute;
    protected $hour;
    protected $dayOfMonth;
    protected $month;
    protected $dayOfWeek;
    protected $command;
    protected $comment;

    public function setUp()
    {
        $this->cron = new Cron();
        $this->minute = 30;
        $this->hour = 23;
        $this->dayOfMonth = 31;
        $this->month = 12;
        $this->dayOfWeek = 6;
        $this->command = 'ls -lah';
        $this->comment = 'my awesome comment';
        $this->logFilePath = sprintf('%s%s', __DIR__, '/logs.log');
        $this->errorFilePath = sprintf('%s%s', __DIR__, '/error-logs.log');
    }

    public function tearDown()
    {
        if (file_exists($this->logFilePath)) {
            unlink($this->logFilePath);
        }

        if (file_exists($this->errorFilePath)) {
            unlink($this->errorFilePath);
        }
    }

    public function testComputeLastRunTimeAndLogSize()
    {
        $this->specify("compute last run time by not existings log path", function () {
            $this->cron->computeLastRunTimeAndLogSize($this->logFilePath);

            verify($this->cron->getLastRunTime())->null();
            verify($this->cron->getLogSize())->null();
            verify($this->cron->getLogFile())->null();
        });

        $this->specify("compute last run time and log size with existings log file", function () {
            $fp = fopen($this->logFilePath, "w+");
            fclose($fp);

            $this->cron->computeLastRunTimeAndLogSize($this->logFilePath);

            verify($this->cron->getLastRunTime())->equals(filemtime($this->logFilePath));
            verify($this->cron->getLogSize())->equals(filesize($this->logFilePath));
            verify($this->cron->getLogFile())->equals($this->logFilePath);
        });
    }

    public function testComputeLastRunTimeAndErrorSize()
    {
        $this->specify("compute last run time by error log path and size", function () {
            $this->cron->computeLastRunTimeAndErrorSize($this->errorFilePath);

            verify($this->cron->getLastRunTime())->null();
            verify($this->cron->getErrorSize())->null();
            verify($this->cron->getErrorFile())->null();
        });

        $this->specify("compute last run time by existings error log and size", function () {
            $fp = fopen($this->errorFilePath, "w+");
            fclose($fp);

            $this->cron->computeLastRunTimeAndErrorSize($this->errorFilePath);

            verify($this->cron->getLogSize())->null();
            verify($this->cron->getLogFile())->null();

            verify($this->cron->getLastRunTime())->equals(filemtime($this->errorFilePath));
            verify($this->cron->getErrorSize())->equals(filesize($this->errorFilePath));
            verify($this->cron->getErrorFile())->equals($this->errorFilePath);
        });
    }

    public function testToString()
    {
        $this->cron->setSuspended();
        $this->cron->setMinute($this->minute);
        $this->cron->setHour($this->hour);
        $this->cron->setDayOfMonth($this->dayOfMonth);
        $this->cron->setMonth($this->month);
        $this->cron->setDayOfWeek($this->dayOfWeek);
        $this->cron->setCommand($this->command);

        $this->specify("Check full cron line __toString transformation", function () {
            $this->cron->setLogFile($this->logFilePath);
            $this->cron->setErrorFile($this->errorFilePath);
            $this->cron->setComment($this->comment);

            verify($this->cron->__toString())->equals(
                sprintf(
                    '#suspended: %d %d %d %d %d %s > %s 2> %s #%s',
                    $this->minute,
                    $this->hour,
                    $this->dayOfMonth,
                    $this->month,
                    $this->dayOfWeek,
                    $this->command,
                    $this->logFilePath,
                    $this->errorFilePath,
                    $this->comment
                )
            );
        });

        $this->specify("Check minimal cron line __toString transformation", function () {
            $this->cron->setSuspended(false);

            verify($this->cron->__toString())->equals(
                sprintf(
                    '%d %d %d %d %d %s',
                    $this->minute,
                    $this->hour,
                    $this->dayOfMonth,
                    $this->month,
                    $this->dayOfWeek,
                    $this->command
                )
            );
        });
    }

    public function testSetStatus()
    {
        $this->specify("Check status cron if log and error size is null", function () {
            $this->cron->setStatus();

            verify($this->cron->getStatus())->equals('unknown');
        });

        $this->specify("Check status cron if error size is 2", function () {
            $this->cron->setErrorSize(2);
            $this->cron->setStatus();

            verify($this->cron->getStatus())->equals('error');
        });

        $this->specify("Check status cron if error size is 0 and log is 5", function () {
            $this->cron->setErrorSize(0);
            $this->cron->setLogSize(5);
            $this->cron->setStatus();

            verify($this->cron->getStatus())->equals('success');
        });
    }

}
