<?php
/**
 * This file is part of the DZCronManagerBundle.
 *
 * (c) Stanislav Stepanenko <dsazztazz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DZ\CronManagerBundle\Entity;

/**
 * Cron represents a cron command.
 * It holds:
 * - time data
 * - command
 * - comment
 * - log files
 * - cron execution status
 *
 * @author Stanislav Stepanenko <dsazztazz@gmail.com>
 */
class Cron
{
    /**
     * @var string
     */
    protected $minute;

    /**
     * @var string
     */
    protected $hour;

    /**
     * @var string
     */
    protected $dayOfMonth;

    /**
     * @var string
     */
    protected $month;

    /**
     * @var string
     */
    protected $dayOfWeek;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var string
     */
    protected $logFile;

    /**
     * The size of the log file
     *
     * @var string
     */
    protected $logSize;

    /**
     * @var string
     */
    protected $errorFile;

    /**
     * The size of the error file
     *
     * @var string
     */
    protected $errorSize;

    /**
     * The last run time based on when log files have been written
     *
     * @var int
     */
    protected $lastRunTime;

    /**
     * The status of the cron, based on the log files
     *
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var boolean
     */
    protected $isSuspended;

    public function __construct()
    {
        $this->minute = '*';
        $this->hour = '*';
        $this->dayOfMonth = '*';
        $this->month = '*';
        $this->dayOfWeek = '*';
        $this->command = '';
        $this->logFile = null;
        $this->logSize = null;
        $this->errorFile = null;
        $this->errorSize = null;
        $this->lastRunTime = null;
        $this->status = 'unknown';
        $this->comment = '';
        $this->isSuspended = false;
    }

    /**
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $dayOfMonth
     */
    public function setDayOfMonth($dayOfMonth)
    {
        $this->dayOfMonth = $dayOfMonth;
    }

    /**
     * @return string
     */
    public function getDayOfMonth()
    {
        return $this->dayOfMonth;
    }

    /**
     * @param string $dayOfWeek
     */
    public function setDayOfWeek($dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;
    }

    /**
     * @return string
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * @param string $hour
     */
    public function setHour($hour)
    {
        $this->hour = $hour;
    }

    /**
     * @return string
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * @param string $minute
     */
    public function setMinute($minute)
    {
        $this->minute = $minute;
    }

    /**
     * @return string
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * @param string $month
     */
    public function setMonth($month)
    {
        $this->month = $month;
    }

    /**
     * @return string
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $logFile
     */
    public function setLogFile($logFile = null)
    {
        $this->logFile = $logFile;
    }

    /**
     * @return string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     * @param string $errorFile
     */
    public function setErrorFile($errorFile = null)
    {
        $this->errorFile = $errorFile;
    }

    /**
     * @return string
     */
    public function getErrorFile()
    {
        return $this->errorFile;
    }

    /**
     * @param int $lastRunTime
     */
    public function setLastRunTime($lastRunTime = null)
    {
        $this->lastRunTime = $lastRunTime;
    }

    /**
     * @return int
     */
    public function getLastRunTime()
    {
        return $this->lastRunTime;
    }

    /**
     * @param string $errorSize
     */
    public function setErrorSize($errorSize)
    {
        $this->errorSize = $errorSize;
    }

    /**
     * @return string
     */
    public function getErrorSize()
    {
        return $this->errorSize;
    }

    /**
     * @param string $logSize
     */
    public function setLogSize($logSize)
    {
        $this->logSize = $logSize;
    }

    /**
     * @return string
     */
    public function getLogSize()
    {
        return $this->logSize;
    }

    public function setStatus()
    {
        if (null === $this->logSize && null === $this->errorSize) {
            $this->status = 'unknown';
        } elseif (null === $this->errorSize || 0 == $this->errorSize) {
            $this->status = 'success';
        } else {
            $this->status = 'error';
        }
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Concats time data to get the time expression
     *
     * @return string
     */
    public function getExpression()
    {
        return sprintf('%s %s %s %s %s', $this->minute, $this->hour, $this->dayOfMonth, $this->month, $this->dayOfWeek);
    }

    /**
     * Gets the value of isSuspended
     *
     * @return boolean
     */
    public function isSuspended()
    {
        return $this->isSuspended;
    }

    /**
     * Set suspended status
     * 
     * @param boolean $isSuspended - Suspended status
     *
     * @return Cron
     */
    public function setSuspended($isSuspended = true)
    {
        $this->isSuspended = $isSuspended;

        return $this;
    }

    /**
     * Suspend a cron
     *
     * @return Cron
     */
    public function suspend()
    {
        if (false === $this->isSuspended) {
            $this->isSuspended = true;
        }

        return $this;
    }

    /**
     * Wakeup a cron
     *
     * @return Cron
     */
    public function wakeup()
    {
        if ($this->isSuspended) {
            $this->isSuspended = false;
        }

        return $this;
    }

    /**
     * Compute last run time, and file size for log
     *
     * @param string $logFile - log file path
     *
     */
    public function computeLastRunTimeAndLogSize($logFile = null)
    {
        if (null !== $logFile
            && file_exists($logFile)
        ) {
            $this->logFile = $logFile;

            $this->lastRunTime = filemtime($logFile);
            $this->logSize = filesize($logFile);
        }
    }

    /**
     * Compute last run time, and file size for error log
     *
     * @param string $errorFile - error log file path
     *
     */
    public function computeLastRunTimeAndErrorSize($errorFile = null)
    {
        if (null !== $errorFile
            && file_exists($errorFile)
        ) {
            $this->lastRunTime = max($this->lastRunTime ?: 0, filemtime($errorFile));
            $this->errorSize = filesize($errorFile);
            $this->errorFile = $errorFile;
        }
    }

    /**
     * Transforms the cron instance into a cron line
     *
     * @return string
     */
    public function __toString()
    {
        $cronLine = '';
        if ($this->isSuspended()) {
            $cronLine .= '#suspended: ';
        }

        $cronLine .= sprintf('%s %s', $this->getExpression(), $this->command);

        if ('' != $this->logFile) {
            $cronLine .= sprintf(' > %s', $this->logFile);
        }

        if ('' != $this->errorFile) {
            $cronLine .= sprintf(' 2> %s', $this->errorFile);
        }

        if ('' != $this->comment) {
            $cronLine .= sprintf(' #%s', $this->comment);
        }

        return $cronLine;
    }
}
