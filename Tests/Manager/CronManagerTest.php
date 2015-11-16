<?php

namespace Roro\Bundle\CronBundle\Tests\Manager;

use Roro\Bundle\CronBundle\Manager\CronManager;

class CronManagerTest extends \PHPUnit_Framework_TestCase
{
    use \Codeception\Specify;

    protected $cronManager;
    protected $logFilePath;
    protected $errorFilePath;
    protected $command;
    protected $comment;

    public function setUp()
    {
        $this->cronManager = new CronManager();
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

    public function testExtractComment()
    {
        $this->specify("cron line with comment", function () {
            $cronLine = '* * * * * ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractComment($cronLine))->equals('my awesome comment');
        });

        $this->specify("cron line with comment and suspended", function () {
            $cronLine = '#suspended: * * * * * ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractComment($cronLine))->equals('my awesome comment');
        });

        $this->specify("cron line without comment", function () {
            $cronLine = '* * * * * ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error';
            verify($this->cronManager->extractComment($cronLine))->equals('');
        });
    }

    public function testExtractIsSuspended()
    {
        $this->specify("cron line with suspended", function () {
            $cronLine = '#suspended: * * * * * ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractIsSuspended($cronLine))->true();
        });

        $this->specify("cron line without suspended", function () {
            $cronLine = '* * * * * ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractIsSuspended($cronLine))->false();
        });
    }

    public function testExtractLogFile()
    {
        $this->specify("cron line with log file", function () {
            $cronLine = '* * * * * ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractLogFile($cronLine))->equals('/var/www/html/roro/app/logs');
        });

        $this->specify("cron line without log file", function () {
            $cronLine = '* * * * * ls -lah 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractLogFile($cronLine))->null();
        });

        $this->specify("cron line with log file and without error and comment", function () {
            $cronLine = '* * * * * ls -lah > /var/www/html/roro/app/logs';
            verify($this->cronManager->extractLogFile($cronLine))->equals('/var/www/html/roro/app/logs');
        });
    }

    public function testExtractErrorFile()
    {
        $this->specify("cron line with error log file", function () {
            $cronLine = '* * * * * ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractErrorFile($cronLine))->equals('/var/www/html/roro/app/logs-error');
        });

        $this->specify("cron line without error log file", function () {
            $cronLine = '* * * * * ls -lah > /var/www/html/roro/app/logs #my awesome comment';
            verify($this->cronManager->extractErrorFile($cronLine))->null();
        });
    }

    public function testExtractTimeWithCommand()
    {
        $this->specify("cron line with time, command, log file, error file and comment", function () {
            $cronLine = '* * * * * ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->equals(
                array('*', '*', '*', '*', '*', 'ls -lah')
            );
        });

        $this->specify("cron line with correct time format, command, log file, error file and comment", function () {
            $cronLine = '59 23 31 12 6 ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->equals(
                array('59', '23', '31', '12', '6', 'ls -lah')
            );
        });

        $this->specify("cron line with correct time format, command and without log file, error file and comment", function () {
            $cronLine = '59 23 31 12 6 ls -lah';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->equals(
                array('59', '23', '31', '12', '6', 'ls -lah')
            );
        });

        $this->specify("cron line with correct time format with different case, command, log file, error file and comment", function () {
            $cronLine = '59 23 31 dec SUN ls -lah > /var/www/html/roro/app/logs 2> /var/www/html/roro/app/logs-error #my awesome comment';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->equals(
                array('59', '23', '31', 'dec', 'SUN', 'ls -lah')
            );
        });

        $this->specify("cron line with wrong count of time parts", function () {
            $cronLine = '* * * ls -lah > /var/www/html/roro/app/logs #my awesome comment';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->false();
        });

        $this->specify("cron line with wrong minute", function () {
            $cronLine = '559 * * * * ls -lah > /var/www/html/roro/app/logs #my awesome comment';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->equals(
                array('59', '*', '*', '*', '*', 'ls -lah')
            );
        });

        $this->specify("cron line with wrong hour", function () {
            $cronLine = '* 25 * * * ls -lah > /var/www/html/roro/app/logs #my awesome comment';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->false();
        });

        $this->specify("cron line with wrong day", function () {
            $cronLine = '* * 35 * * ls -lah > /var/www/html/roro/app/logs #my awesome comment';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->false();
        });

        $this->specify("cron line with wrong month", function () {
            $cronLine = '* * * 13 * ls -lah > /var/www/html/roro/app/logs #my awesome comment';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->false();
        });

        $this->specify("cron line with wrong week", function () {
            $cronLine = '* * * * 10 ls -lah > /var/www/html/roro/app/logs #my awesome comment';
            verify($this->cronManager->extractTimeWithCommand($cronLine))->false();
        });
    }

    public function testCreate()
    {
        $this->specify("Create cron with existing log file", function () {
            $fp = fopen($this->logFilePath, "w+");
            fclose($fp);

            $cron = $this->cronManager->create(
                $partsTime = ['*', '*', '*', '*', '*'],
                $command = $this->command,
                $isSuspended = true,
                $comment = $this->comment,
                $logFile = $this->logFilePath,
                $errorFile = $this->errorFilePath
            );

            $this->assertInstanceOf('Roro\Bundle\CronBundle\Entity\Cron', $cron);
            verify($cron->getMinute())->equals('*');
            verify($cron->getHour())->equals('*');
            verify($cron->getDayOfMonth())->equals('*');
            verify($cron->getMonth())->equals('*');
            verify($cron->getDayOfWeek())->equals('*');
            verify($cron->getCommand())->equals($this->command);
            verify($cron->isSuspended())->true();
            verify($cron->getLastRunTime())->equals(filemtime($this->logFilePath));
            verify($cron->getLogFile())->equals($this->logFilePath);
            verify($cron->getLogSize())->equals(filesize($this->logFilePath));
            verify($cron->getErrorFile())->equals($this->errorFilePath);
            verify($cron->getErrorSize())->null();
            verify($cron->getStatus())->equals('success');

            verify($cron->__toString())->equals(
                sprintf(
                    '#suspended: * * * * * %s > %s 2> %s #%s',
                    $this->command,
                    $this->logFilePath,
                    $this->errorFilePath,
                    $this->comment
                )
            );
        });
    }

    public function testParseCronLine()
    {
        $this->specify("Create cron with existings log file", function () {
            $fp = fopen($this->logFilePath, "w+");
            fclose($fp);

            $cronLine = sprintf(
                '#suspended: * * * * * %s > %s 2> %s #%s',
                $this->command,
                $this->logFilePath,
                $this->errorFilePath,
                $this->comment
            );

            $cron = $this->cronManager->parseCronLine($cronLine);

            $this->assertInstanceOf('Roro\Bundle\CronBundle\Entity\Cron', $cron);
            verify($cron->getMinute())->equals('*');
            verify($cron->getHour())->equals('*');
            verify($cron->getDayOfMonth())->equals('*');
            verify($cron->getMonth())->equals('*');
            verify($cron->getDayOfWeek())->equals('*');
            verify($cron->getCommand())->equals($this->command);
            verify($cron->isSuspended())->true();
            verify($cron->getLastRunTime())->equals(filemtime($this->logFilePath));
            verify($cron->getLogFile())->equals($this->logFilePath);
            verify($cron->getLogSize())->equals(filesize($this->logFilePath));
            verify($cron->getErrorFile())->equals($this->errorFilePath);
            verify($cron->getErrorSize())->null();
            verify($cron->getStatus())->equals('success');
            verify($cron->__toString())->equals($cronLine);
        });
    }
}
