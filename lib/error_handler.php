<?php
/**
 * Copyright (c) 2012 GroupSpaces Ltd. <info@groupspaces.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @license    MIT
 *
 * @author     Andy Young <andy@teamgroupspaces.com>
 * @author     Dave Ingram <dmi@teamgroupspaces.com> (GroupSpaces)
 * @author     Gabor Vizi <vgabor@teamgroupspaces.com> (GroupSpaces)
 */

ErrorHandler::register();
/**
 * Handles custom error and exception handling
 *
 */
class ErrorHandler
{
	// stack of error reporting level integer constants
	protected static $_errorReportingLevelStack = array();

	// stack of extra data to log if an error occurs
	protected static $extraData = array();

	// store pointers to previous error/exception handlers, where present
	protected static $prevErrorHandler = array();
	protected static $prevExceptionHandler = array();

	protected static $customHandlerCount = 0;

	// sensitive data what we will change to *s
	protected static $sensitiveDataFields = array('password', 'password1', 'password2');

	/**
	 * Register this custom error/exception handler
	 */
	public static function register() {
		$class = get_called_class();
		if (!empty(self::$prevErrorHandler[$class])) {
			// can't register error handler more than once
			trigger_error('cant register error handler more than once: ' . $class, E_USER_WARNING);
			return;
		}
		self::$prevErrorHandler[$class] = set_error_handler(array($class, 'GlobalErrorHandler'), E_ALL &~ E_STRICT);
		self::$prevExceptionHandler[$class] = set_exception_handler(array($class, 'GlobalExceptionHandler'));
		register_shutdown_function(array($class, 'FatalErrorShutdownHandler'));
		self::$customHandlerCount++;
	}

	/**
	 * Bubble given error up to previously registered error handler
	 */
	protected static function bubbleError($code, $message, $file, $line, $context) {
		if (is_callable(self::$prevErrorHandler[get_called_class()])) {
			return call_user_func(self::$prevErrorHandler[get_called_class()], $code, $message, $file, $line, $context);
		}
	}

	/**
	 * Bubble given exception up to previously registered exception handler
	 */
	protected static function bubbleException($exception) {
		if (is_callable(self::$prevExceptionHandler[get_called_class()])) {
			return call_user_func(self::$prevExceptionHandler[get_called_class()], $exception);
		}
	}

	/**
	 * Unregister this and restore the previous error/exception handler
	 */
	public static function unregister() {
		$class = get_called_class();
		if (!empty(self::$prevErrorHandler[$class])) {
			restore_error_handler();
			restore_exception_handler();
			self::$customHandlerCount--;
			unset(self::$prevErrorHandler[$class]);
			unset(self::$prevExceptionHandler[$class]);
		}
	}

	/**
	 * Handle Exceptions
	 */
	public static function GlobalExceptionHandler($exception)
	{
		// restore default exception handler as additional way to try and get to the bottom of "Exception thrown without a stack frame in Unknown on line 0" errors
		restore_exception_handler();

		try {
			self::hasErred(true);

			$msg = "PHP Fatal error: Uncaught exception";

			$msg .= self::getExceptionLogString($exception);

			// output to PHP error log as configured by error_log ini setting
			self::_logError($msg, false, false); // don't add extra info, don't display

			if (ini_get('display_errors')) {
				self::displayError($msg);
			} else {
				// output message
				require_once 'templates/php_error.php';
			}
		} catch (Exception $e) {
			// we shouldnt throw an exception within this exception handler, but just in case - to avoid "Exception thrown without a stack frame in unknown"
			error_log('Exception thrown within exception handler');
			error_log(sprintf('Exception info: %s %s %s %s %s', get_class($e), $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine()));
		}

		// die script, die!
		die();
	}

	/**
	 * Global error handler
	 * - This is only really useful for E_ERROR and E_RECOVERABLE_ERROR since we want to treat all non-fatal errors exactly as php does by default (log, dont display)
	 */
	public static function GlobalErrorHandler($code, $message, $file, $line, $context) {
		$msg = '';
		$fatal = true;

		$strong = (ini_get('html_errors') ? '<strong>' : '');
		$endstrong = (ini_get('html_errors') ? '</strong>' : '');

		switch ($code) {
			case E_ERROR:
				// apparently not any more: // message line for fatal errors is logged anyhow before we get to our custom handler. //
				$msg .= 'Fatal Error:';
				$fatal = true;
				break;
			case E_WARNING:
				$msg .= 'Warning:';
				$fatal = false;
				break;
			case E_NOTICE:
				$msg .= 'Notice:';
				$fatal = false;
				break;
			case E_USER_ERROR:
				$msg .= 'User Error:';
				$fatal = false;
				break;
			case E_USER_WARNING:
				$msg .= 'User Warning:';
				$fatal = false;
				break;
			case E_USER_NOTICE:
				$msg .= 'User Notice:';
				$fatal = false;
				break;
			case E_STRICT:
				$msg .= 'Strict Error:';
				$fatal = false;
				break;
			case E_RECOVERABLE_ERROR:
				$msg .= 'Recoverable Error:';
				// treated as E_ERROR (i.e. fatal) if not caught by custom handler
				break;
			case E_DEPRECATED:
				$msg .= 'Deprecated Warning:';
				$fatal = false;
				break;
			case E_USER_DEPRECATED:
				$msg .= 'User Deprecated Warning:';
				$fatal = false;
				break;
			default:
				$msg .= "Unknown Error Type ($code):";
				// play safe - fatal
		}

		//if ($code !== E_ERROR) {
		// apparently not any more: // message line for fatal errors is logged anyhow before we get to our custom handler.
		$msg = "PHP " . $msg . " " . $message;
		if ($code) {
			$msg .= " ($code)";
		}
		$msg .= " in $file:$line.\n";
		//}

		$error_reporting_level = error_reporting();
		if (!$error_reporting_level) {
			// override @-suppression of errors - this is a BAD coding style since hides fatal errors
			//  NB in case of an @-suppressed fatal error this code won't be called, but at least we warn devs against using @-supression
			//  to hide warning errors here

			// only complain about this in debug mode (don't fill up error logs on live site)
			if (SITE_DEBUG_MODE && self::warnOnSuppression()) {
				self::hasErred(true);

				$warning = "Warning: @-suppression of errors (or setting error_reporting() level to 0) is bad coding practice - causes code to die without explanation on fatal error. Use error_reporting(E_ERROR) or similar instead.\n";
				// Skip one level of backtracing... i.e. this function
				$trace = self::debug_backtrace_pretty(ini_get('html_errors') ? 'html_string' : 'plain_string', 2 + ((self::$customHandlerCount - 1) * 3));
				$trace_text = self::debug_backtrace_pretty('plain_string', 2 + ((self::$customHandlerCount - 1) * 3));

				// output to PHP error log as configured by error_log ini setting
				self::_logError($warning . $trace_text, false, false); // dont add extra info, don't display
				if (ini_get('display_errors'))
				{
					self::displayError($warning . "\n$trace");
				}
			}

			// override with E_ERROR - i.e. report fatal errors
			$error_reporting_level = E_ERROR;
		}

		if ( ($error_reporting_level & $code) || $fatal) {
			self::hasErred(true);

			$msg .= trim(self::getEnvironmentInfoString());

			// Skip one level of backtracing... i.e. this function
			if (empty($trace)) { $trace = self::debug_backtrace_pretty(ini_get('html_errors') ? 'html_string' : 'plain_string', 2 + ((self::$customHandlerCount - 1) * 3)); }
			if (empty($trace_text)) { $trace_text = self::debug_backtrace_pretty('plain_string', 2 + ((self::$customHandlerCount - 1) * 3)); }
			$benchmarking = self::getBenchmarkingString();

			$variables_text = '';
			if (defined('LOG_ERROR_CONTEXT') && LOG_ERROR_CONTEXT) {
				$variables_text .= print_r($context, 1);
				if ($variables_text) { $variables_text = "\n" . $variables_text; }
			}

			// output to PHP error log as configured by error_log ini setting
			self::_logError($msg."\n". $trace_text . $benchmarking . $variables_text, false, false); // dont add extra info, don't display

			if (ini_get('display_errors')) {
				$msg .="\n$trace$benchmarking";
				self::displayError($msg);
			}
		}

		if (!ini_get('display_errors') && $fatal) {
			// output message
			require_once 'templates/php_error.php';
		}

		if ($fatal) {
			// we don't die() for E_ERRORs since these are called from within register_shutdown_function() and we want to allow any other shutdown functions to process
			if ($code !== E_ERROR) {
				// die script, die!
				die();
			}
		}

		// don't return false to avoid php logging a duplicate of this error itself
		//  - note that as a result this won't populate $php_errormsg / error_get_last() since php 5.2.0
	}

	/**
	 * Handler for register_shutdown_function() to catch PHP Fatal errors that are otherwise not caught by set_error_handler
	 */
	public static function FatalErrorShutdownHandler() {
		// grab as much info as we can about the error from error_get_last()
		$last_error = error_get_last();
		if($last_error['type'] === E_ERROR) {
			// it's a fatal error - pass info to self::GlobalErrorHandler() to handle like all other errors
			self::GlobalErrorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line'], null);
		}
	}

	/**
	 * Write error to the error log (and output it to the browser if display_errors is on)
	 * $msg can be a string or Exception
	 */
	public static function logError($msg)
	{
		self::hasErred(true);
		if (func_num_args() > 1) {
			// we used to provide the $include_meta param in the publicly-available logError() api, but this was a bad idea since we always want to log userland errors with full context
			trigger_error('use of $include_meta param with ErrorHandler::logError() is deprecated and has no effect');
		}
		return self::_logError($msg, true, true);
	}

	/**
	 * This function is protected to avoid confusion when using the public interface logError()
	 *  - subsequent params are just for use in special cases within this class when we want to control display etc manually
	 */
	protected static function _logError($msg, $include_meta = true, $display = true)
	{
		$trace = '';
		$trace_text = '';
		$benchmarking = '';

		if ($msg instanceof Exception) {
			$msg = 'PHP Exception ' . self::getExceptionLogString($msg);
		} elseif ($include_meta) {
			$msg .= self::getEnvironmentInfoString();

			// Skip one level of backtracing... i.e. this function
			$trace = self::debug_backtrace_pretty(ini_get('html_errors') ? 'html_string' : 'plain_string', 2 + ((self::$customHandlerCount - 1) * 3));
			$trace_text = self::debug_backtrace_pretty('plain_string', 2 + ((self::$customHandlerCount - 1) * 3));

			$benchmarking = self::getBenchmarkingString();
			if ($benchmarking) { $benchmarking = "\n" . $benchmarking; }
		}
		$msg .= "\n";

		if ($display) {
			self::displayError($msg . $trace . $benchmarking);
		}
		$msg = explode("\n", $msg . $trace_text . $benchmarking);
		foreach ($msg as $line) {
			error_log($line);
		}
	}

	/**
	 * helper function to call logError() with both an exception and some extra info (e.g. original sql query)
	 *
	 * @param $e Exception		Exception to log
	 * @param $extra_info mixed 	string or array with extra info to log
	 */
	public static function logExceptionWithInfo($e, $extra_info) {
		self::hasErred(true);
		self::_logError($e);
		self::_logError('Extra info: ' . self::makeString($extra_info), false); // no need to include extra info a second time
	}

	public static function displayError($msg) {
		if (ini_get('display_errors')) {
			echo ini_get('error_prepend_string') . (ini_get('html_errors') ? '<br><code>' . nl2br($msg) . '</code><br>' : $msg . "\n") . ini_get('error_append_string');
		}
	}

	public static function getExceptionLogString($exception)
	{
		$msg = '';

		if ($exception instanceof Exception) {

			$class_name = get_class($exception);
			$message = $exception->getMessage();
			$code = $exception->getCode();
			$file = $exception->getFile();
			$line = $exception->getLine();
			$trace = self::format_backtrace_pretty($exception->getTrace(), 'plain_string');

			if ($class_name && $class_name != 'Exception') {
				$msg .= " '$class_name'";
			}
			$msg .= " with message '$message'";
			if ($code) {
				$msg .= " ($code)";
			}
			$msg .= " in $file:$line.";
			$msg .= self::getEnvironmentInfoString();
			$msg .="\n$trace\n";
			$benchmarking = self::getBenchmarkingString();
			if ($benchmarking) { $msg .= $benchmarking . "\n"; }

			if (method_exists($exception, 'getPrevious')) {
				$e2 = $exception->getPrevious();
				if ($e2) {
					$msg .= "Previous exception in chain:\n" . self::getExceptionLogString($e2);
				}
			}
		} else {
			trigger_error('shouldnt be here');
		}

		return $msg;
	}

	/**
	 * Return a string with additional info about what was going on at the time of the error - user and url
	 */
	public static function getEnvironmentInfoString()
	{
		$msg = '';
		if (!empty(self::$extraData)) {
			$msg .= "\nExtra Data: " . implode("\n  ", self::$extraData);
		}
		if (session_id()) {
			$user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
			$loggedin = !empty($_SESSION['user_logged_in']);
		} else {
			$user = null;
			$loggedin = null;
		}
		if ($user) {
			$msg .= "\nUser ID: $user";
			if (!$loggedin) {
				$msg .= " (not logged in)";
			}
		}
		if (!empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['SERVER_NAME'])) {
			$msg .= "\nCurrent URI: " . (!empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] . ' ' : '') . "http".(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		}
		if (!empty($_SERVER['HTTP_REFERER'])) {
			$msg .= "\nReferrer: ".$_SERVER['HTTP_REFERER'];
		}
		if (!empty($_SERVER['HTTP_USER_AGENT'])) {
			$msg .= "\nUser Agent: ".$_SERVER['HTTP_USER_AGENT'];
		}
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$msg .= "\nRemote Address: ".$_SERVER['REMOTE_ADDR'];
		}
		if ((php_sapi_name() == 'cli') && ($cli_args = implode(' ', $_SERVER['argv']))) {
			$msg .= "\nCLI: ".$cli_args;
		}
		$msg .= "\nProcess ID: ".getmypid();
		if (!empty($_SERVER['CONTENT_LENGTH'])) {
			$msg .= "\nRequest size: ".$_SERVER['CONTENT_LENGTH'];
		}
		if (!empty($_FILES)) { $msg .= "\n\$_FILES: " . json_encode($_FILES); }
		$msg .= "\n\$_POST: " . json_encode(self::cleanSensitiveDataFromArray($_POST));
		return $msg;
	}

	/**
	 *
	 */
	public static function getBenchmarkingString() {
		try {
			if (class_exists('GS_Benchmarking')) {
				GS_Benchmarking::calculateBenchmarks();
				return sprintf(
	"Time: %.5f seconds (%s seconds actual processor time). Memory: peak %s, final %s." . (!is_null(GS_Benchmarking::$data['query_count']) ? ' Queries: %s' . (!is_null(GS_Benchmarking::$data['query_time']) ? ' (time: %.5f, memory: %s)' : '') : '') . ' Load average: %s',
					GS_Benchmarking::$data['realtime'],
					GS_Benchmarking::$data['utime_elapsed'] + GS_Benchmarking::$data['stime_elapsed'],
					GS_Benchmarking::$data['peak_mem_usage'],
					GS_Benchmarking::$data['final_mem_usage'],
					GS_Benchmarking::$data['query_count'],
					GS_Benchmarking::$data['query_time'],
					GS_Benchmarking::$data['query_memory'],
					implode(' ', sys_getloadavg())
				);
			}
		} catch (Exception $e) {
			// ignore
		}
	}

	/**
	 * Take a (multi-dimensional) array and clean values that look potentially sentsitive so they are safe to log
	 *
	 * Replaces sanitised data with ***s:
	 *  - credit/debit card numbers
	 */
	public static function cleanSensitiveDataFromArray(array $input) {
		foreach ($input as $key => $value) {
			if (is_array($value)) {
				$input[$key] = self::cleanSensitiveDataFromArray($value);
			} else {
				if (in_array(strtolower($key), self::$sensitiveDataFields)) { // these are the _POST fields we accept card details via
					$value = str_repeat('*', mb_strlen($value));
				}
				$input[$key] = $value;
			}
		}
		return $input;
	}

	/*
	 * Keep track of whether an error has occurred
	 *  required because error_get_last() can be reset
	 */
	public static function hasErred($set = false)
	{
		static $has_erred = false;
		if ($set) {
			$has_erred = true;
		}
		return $has_erred;
	}

	/*
	 * Keep track of whether we want to override warning about (mis)use of @-suppression
	 *  useful for third-party libraries. N.b. not strictly required for the live site
	 */
	public static function warnOnSuppression($set = null)
	{
		static $warn = true;
		if (!is_null($set)) {
			$warn = $set;
		}
		return $warn;
	}

	/**
	 * Print a function backtrace in multiple formats. Available formats:
	 *   - 'html':    HTML unordered list format
	 *   - 'plain':   plain text output
	 *   - 'comment': HTML/XML comment
	 *   - 'css':     CSS/Javascript comment
	 *   - 'error'/'error_log'/'errorlog': PHP error log
	 *   - 'javascript'/'js'/'firebug': Javascript Firebug output, falling back to
	 *       document.write() if Firebug is not available
	 *   - 'plain_string': Return trace as a plain-text string
	 *   - 'html_string':  Return trace as an HTML string
	 *
	 * @param string $mode Formatted output mode
	 * @param int    $skip Number of items to ignore from the top of the call stack
	 *
	 * @return void
	 */
	public static function debug_backtrace_pretty($mode='html', $skip=0) {
		return self::format_backtrace_pretty(debug_backtrace(), $mode, $skip);
	}

	public static function format_backtrace_pretty($bt, $mode='html', $skip=0) {
		if ($skip>0) array_splice($bt, 0, $skip);
		switch (strtolower($mode)) {
			case 'plain_string':
				$msg = "Trace:\n";
				$i = 0;
				foreach ($bt as &$trace) {
					$msg .= '  #' . $i . ' ';
					$class = array_key_exists('class', $trace) ? $trace['class'] : '';
					$object_class = array_key_exists('object', $trace) ? (is_object($trace['object'])?get_class($trace['object']):$trace['object']) : '';
					if ($object_class && ($object_class != $class)) {
						$msg .= '[' . $class . ']' . $object_class . '';
					} elseif ($class) {
						$msg .= $class;
					}
					if ($object_class && class_exists('BaseObject') && $trace['object'] instanceof BaseObject) { // Propel support
						$msg .= '[' . print_r($trace['object']->getPrimaryKey(), true) . ']';
					}
					if (array_key_exists('type', $trace))  $msg .= $trace['type'];
					$msg .= $trace['function'].'(';
					if (array_key_exists('args', $trace)) $msg .= self::processArgs($trace['args']);
					$msg .= ')';
					if (array_key_exists('file', $trace)) {
						$msg .= ' at '.$trace['file'];
						if (array_key_exists('line', $trace)) {
							$msg .= ':'.$trace['line'];
						}
					}
					$msg .= "\n";
					$i++;
				}
				return $msg;
				break;

			case 'plain':
				echo self::debug_backtrace_pretty('plain_string', $skip+1);
				break;

			case 'comment':
				echo "\n<!-- \n";
				self::debug_backtrace_pretty('plain', $skip+1);
				echo "-->\n";
				break;

			case 'css':
				echo "\n/*\n";
				self::debug_backtrace_pretty('plain', $skip+1);
				echo "*/\n";
				break;

			case 'js':
			case 'javascript':
			case 'firebug':
				echo "\n<script type=\"text/javascript\">if (console && console.debug) { fn=console.debug; } else { fn=document.write; };\nfn(\"";
				$s = self::debug_backtrace_pretty('plain_string', $skip+1);
				echo str_replace(array('"', "\n"), array('\"', '\n'), $s);
				echo "\");</script>\n";
				break;

			case 'err':
			case 'error':
			case 'errorlog':
			case 'error_log':
				error_log(self::debug_backtrace_pretty('plain_string', $skip+1));
				break;

			case 'html_string':
				$msg = '<b>Backtrace</b>';
				if (isset($bt[0]['file'])) {
					$msg .= ' from <b>'.$bt[0]['file'].'</b>';
					if (isset($bt[0]['line'])) {
						$msg .= ':<b>'.$bt[0]['line'].'</b>';
					}
				}
				if (count($bt) > 1) { array_shift($bt); } // remove this function call from the list
				$msg .= ":<dl>";
				foreach ($bt as &$trace) {
					$msg .= '  <dd><tt>';
					$class = array_key_exists('class', $trace) ? $trace['class'] : '';
					$object_class = array_key_exists('object', $trace) ? (is_object($trace['object'])?get_class($trace['object']):$trace['object']) : '';
					if ($object_class && ($object_class != $class)) {
						$msg .= '[' . $class . ']' . $object_class . '';
					} elseif ($class) {
						$msg .= $class;
					}
					if (array_key_exists('type', $trace))  $msg .= $trace['type'];
					$msg .= $trace['function'].'(';
					if (array_key_exists('args', $trace)) $msg .= self::processArgs($trace['args']);
					$msg .= ')</tt>';
					if (array_key_exists('file', $trace)) {
						$msg .= ' at <b>'.$trace['file'].'</b>';
						if (array_key_exists('line', $trace)) {
							$msg .= ':<b>'.$trace['line'].'</b>';
						}
					}
					$msg .= "</dd>";
				}
				$msg .= "</dl>";
				return $msg;
				break;

			case 'html':
			default:
				echo self::debug_backtrace_pretty('html_string', $skip+1);
				break;
		}
	}

	public static function processArgs(&$args) {
		if (!is_array($args)) {
			return '';
		}
		$argsProcessed = array();
		foreach ($args as &$arg) { // using references to spare memory usage
			if (is_object($arg)) {
				$str = self::maxlen(get_class($arg));
				if (class_exists('BaseObject') && $arg instanceof BaseObject) { // Propel support
					$str .= '[' . print_r($arg->getPrimaryKey(), true) . ']';
				} elseif (class_exists('Criteria') && $arg instanceof Criteria && class_exists('GSDB')) {
					try {
						$str .= '[' . GSDB::criteriaToSqlString($arg) . ']';
					} catch (Exception $e) {
						// ignore
					}
				}
				$argsProcessed[] = $str;
			} elseif (is_resource($arg)) {
				$argsProcessed[] = self::maxlen(get_resource_type($arg));
			} elseif (is_array($arg)) {
				$subArgs = array();
				foreach ($arg as &$a) { // using references to spare memory usage
					if (is_object($a)) {
						$subArgs[] = self::maxlen(get_class($a));
					} elseif (is_resource($a)) {
						$subArgs[] = self::maxlen(get_resource_type($a));
					} elseif (is_array($a)) {
						$subArgs[] = 'array';
					} elseif (is_string($a) && strlen($a) > 10000) {
						$subArgs[] = 'variable('.strlen($a).')';
					} else {
						$subArgs[] = self::maxlen(var_export($a, true));
					}
				}
				$argsProcessed[] = 'array(' . implode(',', $subArgs) . ')';
			} elseif (strlen($arg) > 10000) {
				$argsProcessed[] = 'variable('.strlen($arg).')';
			} else {
				$argsProcessed[] = self::maxlen(var_export($arg, true));
			}
		}
		return implode(',', $argsProcessed);
	}

	protected static function makeString($val) {
		if (is_array($val)) {
			return self::processArgs($val);
		}
		return (string) $val;
	}

	protected static function maxlen($r) {
		if (mb_strlen($r) > 100) {
			$r = mb_substr($r, 0, 100) . "...";
			if ($r{0} == '"' || $r{0} == "'") {
				$r .= $r{0};
			}
		}
		return $r;
	}

	public static function clearLevelStack() {
		self::$_errorReportingLevelStack = array();
	}

	public static function pushLevel($level) {
		array_push(self::$_errorReportingLevelStack, error_reporting());
		error_reporting($level);
	}

	public static function popLevel() {
		if (!empty(self::$_errorReportingLevelStack)) {
			error_reporting(array_pop(self::$_errorReportingLevelStack));
		}
	}

	/**
	 * Ignore repeated errors with the same (message, file, line) tuple.
	 *
	 * @param bool $v Whether to ignore repeated errors.
	 */
	public static function ignoreRepeatedErrors($v) {
		ini_set('ignore_repeated_errors', $v?1:0);
	}

	/**
	 * add some extra data (an arbitrary string) to log if an error occurs
	 *
	 * @param      string $msg  extra data to log
	 */
	public static function pushExtraData($msg) {
		array_push(self::$extraData, $msg);
	}

	/**
	 * forget the most previously added extra data configured via pushExtraData()
	 */
	public static function popExtraData() {
		if (!empty(self::$extraData)) {
			array_pop(self::$extraData);
		}
	}

	public static function setErrorLogFile($file) {
		if ($file[0] != '/') {
			$file = (defined('ERROR_LOG_DIRECTORY') ? constant('ERROR_LOG_DIRECTORY') : '/var/log/php-errors/') . $file;
		}

		ini_set('error_log', $file);
	}

	public static function getErrorLogFile() {
		return ini_get('error_log');
	}
}

