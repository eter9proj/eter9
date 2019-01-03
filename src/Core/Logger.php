<?php
/**
 * @file src/Core/Logger.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\LoggerFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @brief Logger functions
 */
class Logger extends BaseObject
{
	/**
	 * @see Logger::error()
	 */
	const WARNING = LogLevel::ERROR;
	/**
	 * @see Logger::warning()
	 */
	const INFO = LogLevel::WARNING;
	/**
	 * @see Logger::notice()
	 */
	const TRACE = LogLevel::NOTICE;
	/**
	 * @see Logger::info()
	 */
	const DEBUG = LogLevel::INFO;
	/**
	 * @see Logger::debug()
	 */
	const DATA = LogLevel::DEBUG;
	/**
	 * @see Logger::debug()
	 */
	const ALL = LogLevel::DEBUG;

	/**
	 * @var array the legacy loglevels
	 * @deprecated 2019.03 use PSR-3 loglevels
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel
	 *
	 */
	public static $levels = [
		self::WARNING => 'Warning',
		self::INFO => 'Info',
		self::TRACE => 'Trace',
		self::DEBUG => 'Debug',
		self::DATA => 'Data',
		self::ALL => 'All',
	];

	/**
	 * @var LoggerInterface A PSR-3 compliant logger instance
	 */
	private static $logger;

	/**
	 * @var LoggerInterface A PSR-3 compliant logger instance for developing only
	 */
	private static $devLogger;

	/**
	 * Sets the default logging handler for Friendica.
	 * @todo Can be combined with other handlers too if necessary, could be configurable.
	 *
	 * @param LoggerInterface $logger The Logger instance of this Application
	 *
	 * @throws InternalServerErrorException if the logger factory is incompatible to this logger
	 */
	public static function setLogger($logger)
	{
		$debugging = Config::get('system', 'debugging');
		$logfile = Config::get('system', 'logfile');
		$loglevel = intval(Config::get('system', 'loglevel'));

		if (!$debugging || !$logfile) {
			return;
		}

		$level = self::mapPSR3Level($loglevel);
		LoggerFactory::addStreamHandler($logger, $logfile, $level);

		self::$logger = $logger;

		$logfile = Config::get('system', 'dlogfile');

		if (!$logfile) {
			return;
		}

		$developIp = Config::get('system', 'dlogip');

		self::$devLogger = LoggerFactory::createDev('develop', $developIp);
		LoggerFactory::addStreamHandler(self::$devLogger, $logfile, LogLevel::DEBUG);
	}

	/**
	 * System is unusable.
	 * @see LoggerInterface::emergency()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 */
	public static function emergency($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->emergency($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

	/**
	 * Action must be taken immediately.
	 * @see LoggerInterface::alert()
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 */
	public static function alert($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->alert($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

	/**
	 * Critical conditions.
	 * @see LoggerInterface::critical()
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 */
	public static function critical($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->critical($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 * @see LoggerInterface::error()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 */
	public static function error($message, $context = [])
	{
		if (!isset(self::$logger)) {
			echo "not set!?\n";
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->error($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

	/**
	 * Exceptional occurrences that are not errors.
	 * @see LoggerInterface::warning()
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 */
	public static function warning($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->warning($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

	/**
	 * Normal but significant events.
	 * @see LoggerInterface::notice()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 */
	public static function notice($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->notice($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

	/**
	 * Interesting events.
	 * @see LoggerInterface::info()
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 */
	public static function info($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->info($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

	/**
	 * Detailed debug information.
	 * @see LoggerInterface::debug()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public static function debug($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->debug($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

    /**
     * @brief Logs the given message at the given log level
     *
     * @param string $msg
     * @param int $level
	 *
	 * @deprecated since 2019.03 Use Logger::debug() Logger::info() , ... instead
     */
    public static function log($msg, $level = LogLevel::NOTICE)
    {
		if (!isset(self::$logger)) {
			return;
		}

        $stamp1 = microtime(true);
		self::$logger->log($level, $msg);
        self::getApp()->saveTimestamp($stamp1, "file");
    }

    /**
     * @brief An alternative logger for development.
     * Works largely as log() but allows developers
     * to isolate particular elements they are targetting
     * personally without background noise
     *
     * @param string $msg
	 * @param string $level
     */
    public static function devLog($msg, $level = LogLevel::DEBUG)
    {
		if (!isset(self::$logger)) {
			return;
		}

        $stamp1 = microtime(true);
        self::$devLogger->log($level, $msg);
        self::getApp()->saveTimestamp($stamp1, "file");
    }
}
