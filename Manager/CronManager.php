<?php
/**
 * This file is part of the DZCronManagerBundle.
 *
 * (c) Stanislav Stepanenko <dsazztazz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DZ\CronManagerBundle\Manager;

use Symfony\Component\Process\Process;
use DZ\CronManagerBundle\Entity\Cron;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CronManager
 *
 * @author Stanislav Stepanenko <dsazztazz@gmail.com>
 */
class CronManager
{
    /**
     * The collections of Cron instances
     *
     * @var ArrayCollection
     */
    protected $crons;

    /**
     * The error of command 'crontab' 
     *
     * @var string
     */
    protected $error;

    /**
     * The output of command 'crontab'
     *
     * @var string
     */
    protected $output;

    public function __construct()
    {
        $this->crons = new ArrayCollection();
    }

    /**
     * Parse cron file
     *
     * @throw Exception If find invalide line of cron file or permissions denied for create logs
     *
     */
    public function parseCronFile()
    {
        $process = new Process('crontab -l');
        $process->run();

        $cronLines = array_filter(explode(PHP_EOL, $process->getOutput()), function ($cronLine) {
            return '' != trim($cronLine);
        });

        foreach ($cronLines as $lineNumber => $cronLine) {
            if (false === $this->isCronLineComment($cronLine)) {
                try {
                    $cron = $this->parseCronLine($cronLine);
                    $this->crons->add($cron);
                } catch (\Exception $e) {
                    $process->addErrorOutput(
                        sprintf('CronManager was unable to parse crontab at line %d !', $lineNumber)
                    );
                    $process->addError(sprintf('Detail: %s', $e->getError()));
                }
            }
        }

        $this->error = $process->getErrorOutput();
    }

    /**
     * Parses a cron line and create Cron instance
     *
     * @param string $cronLine - the cron line
     *
     * @return string
     */
    public function parseCronLine($cronLine)
    {
        $partsTime = $this->extractTimeWithCommand($cronLine);
        $command = array_pop($partsTime);

        return $this->create(
            $partsTime,
            $command,
            $this->extractIsSuspended($cronLine),
            $this->extractComment($cronLine),
            $this->extractLogFile($cronLine),
            $this->extractErrorFile($cronLine)
        );
    }

    /**
     * Check cron line is comment
     *
     * @param string $cron - the cron line
     *
     * @return boolean
     */
    public function isCronLineComment($cron)
    {
        return ($this->extractIsSuspended($cron) || 0 !== strpos($cron, '#', 0)) ? false : true;
    }

    /**
     * Extracting suspended out of a cron line
     *
     * @param string $cron - the cron line
     *
     * @return boolean
     */
    public function extractIsSuspended($cron)
    {
        $patternCronIsSuspended = '/^\#(?P<suspended>(?:suspended))/';
        preg_match($patternCronIsSuspended, $cron, $matches);

        return !empty($matches);
    }

    /**
     * Extracting comments out of a cron line
     *
     * @param string $cron - the cron line
     *
     * @return string
     */
    public function extractComment($cron)
    {
        $patternCronComment = '/\#(?P<comment>(?!suspended).*)/';
        preg_match($patternCronComment, $cron, $matches);

        return empty($matches) ? '' : $matches['comment'];
    }

    /**
     * Extracting log file path out of a cron line
     *
     * @param string $cron - the cron line
     *
     * @return string|null
     */
    public function extractLogFile($cron)
    {
        $patternCronLogFile = '/(?:\s\>\s+)(?P<logFile>.+?(?=\s|$))/';
        preg_match($patternCronLogFile, $cron, $matches);

        return empty($matches) ? null : $matches['logFile'];
    }

    /**
     * Extracting error log file path out of a cron line
     *
     * @param string $cron - the cron line
     *
     * @return string|null
     */
    public function extractErrorFile($cron)
    {
        $patternCronLogFile = '/(?:\d\>\s+)(?P<errorFile>.+?(?=\s|$))/';
        preg_match($patternCronLogFile, $cron, $matches);

        return empty($matches) ? null : $matches['errorFile'];
    }

    /**
     * Extracting time execution of cron and command out of a cron line
     *
     * @param string $cron - the cron line
     *
     * @return array|boolean
     */
    public function extractTimeWithCommand($cron)
    {
        $patternCronTime = sprintf(
            '%s\s+%s\s+%s\s+%s\s+%s',
            $patternCronMinute = '(\*(?:\/\d+)?|(?:[0-5]?\d)(?:-(?:[0-5]?\d)(?:\/\d+)?)?(?:,(?:[0-5]?\d)(?:-(?:[0-5]?\d)(?:\/\d+)?)?)*)',
            $patternCronHour = '(\*(?:\/\d+)?|(?:[01]?\d|2[0-3])(?:-(?:[01]?\d|2[0-3])(?:\/\d+)?)?(?:,(?:[01]?\d|2[0-3])(?:-(?:[01]?\d|2[0-3])(?:\/\d+)?)?)*)',
            $patternCronDayOfMonth = '(\*(?:\/\d+)?|(?:0?[1-9]|[12]\d|3[01])(?:-(?:0?[1-9]|[12]\d|3[01])(?:\/\d+)?)?(?:,(?:0?[1-9]|[12]\d|3[01])(?:-(?:0?[1-9]|[12]\d|3[01])(?:\/\d+)?)?)*)',
            $patternCronMonth = '(\*(?:\/\d+)?|(?:[1-9]|1[012])(?:-(?:[1-9]|1[012])(?:\/\d+)?)?(?:,(?:[1-9]|1[012])(?:-(?:[1-9]|1[012])(?:\/\d+)?)?)*|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)',
            $patternCrondayOfWeek = '(\*(?:\/\d+)?|(?:[0-6])(?:-(?:[0-6])(?:\/\d+)?)?(?:,(?:[0-6])(?:-(?:[0-6])(?:\/\d+)?)?)*|mon|tue|wed|thu|fri|sat|sun)'
        );

        $patternCronCommand = '((?=\b).+?(?=\s+\>|\s+2\>|\s+\#|$))';

        $patternCronCommandWithTime = sprintf(
            '/%s\s+%s/i',
            $patternCronTime,
            $patternCronCommand
        );

        preg_match($patternCronCommandWithTime, $cron, $matches);
        $matches = array_slice($matches, 1);

        return empty($matches) ? false : $matches;
    }

    /**
     * Create cron
     *
     * @param array   $partsTime   - Parts time execution of cron line
     * @param string  $command     - cron command
     * @param boolean $isSuspended - is suspended
     * @param string  $comment     - the comment for cron command
     * @param string  $logFile     - the log file for cron
     * @param string  $errorFile   - the error log file for cron
     *
     * @return DZ\CronManagerBundle\Entity\Cron
     */
    public function create(
        array $partsTime = ['*', '*', '*', '*', '*'],
        $command = '',
        $isSuspended = false,
        $comment = '',
        $logFile = null,
        $errorFile = null
    ) {
        $cron = new Cron();

        $cron->setMinute($partsTime[0]);
        $cron->setHour($partsTime[1]);
        $cron->setDayOfMonth($partsTime[2]);
        $cron->setMonth($partsTime[3]);
        $cron->setDayOfWeek($partsTime[4]);
        $cron->setCommand($command);
        $cron->setSuspended($isSuspended);
        $cron->setComment($comment);
        $cron->setLogFile($logFile);
        $cron->setErrorFile($errorFile);
        $cron->computeLastRunTimeAndLogSize($logFile);
        $cron->computeLastRunTimeAndErrorSize($errorFile);
        $cron->setStatus();

        return $cron;
    }

    /**
     * Gets the collections of crons
     *
     * @return ArrayCollection<Cron>
     */
    public function getCrons()
    {
        return $this->crons;
    }

    /**
     * Add cron to the cron table
     *
     * @param DZ\CronManagerBundle\Entity\Cron $cron
     */
    public function add(Cron $cron)
    {
        $this->crons->add($cron);
    }

    /**
     * Remove cron from the cron table and write changes with process outputs
     *
     * @param int $index - the line number
     */
    public function remove($index)
    {
        $this->crons->remove($index);
    }

    /**
     * Write the current crons in the cron table
     *
     * @return string - cron temp file path
     */
    public function writeCronFile()
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'cron');
        file_put_contents($tmpfile, $this->getRaw().PHP_EOL);
        
        return $tmpfile;
    }

    /**
     * Write the current cron process outputs
     *
     * @param string file - file path
     */
    public function saveCronFileProcessOutputs($tmpfile)
    {
        $process = new Process(sprintf('crontab %s', $tmpfile));
        $process->run();

        $this->error = $process->getErrorOutput();
        $this->output = $process->getOutput();
    }

    /**
     * Write the current crons in cron table and save process outputs
     *
     */
    public function writeCronFileAndSaveProcessOutputs()
    {
        $this->saveCronFileProcessOutputs($tmpfile = $this->writeCronFile());
    }

    /**
     * Gets the error of crontab
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Gets the output of crontab
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Gets a representation of the cron table file
     *
     * @return mixed
     */
    public function getRaw()
    {
        return implode(PHP_EOL, $this->crons->toArray());
    }
}
