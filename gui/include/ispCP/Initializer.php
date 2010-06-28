<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * @category	ispCP
 * @package		ispCP_Initializer
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 * @filesource
 */

/**
 * ispCP Initializer class
 *
 * The initializer is responsible for processing the ispCP configuration,
 * such as setting the include_path, initializing logging, database and
 * more.
 *
 * @category	ispCP
 * @package		ispCP_Initializer
 * @author		Laurent declercq <laurent.declercq@ispcp.net>
 * @since		1.0.6
 * @version		1.0.5
 */
class ispCP_Initializer {

	/**
	 * ispCP_Config_Handler instance used by this class
	 *
	 * @var ispCP_Config_Handler
	 */
	private $_config;

	/**
	 * Initialization status
	 *
	 * @staticvar boolean
	 */
	private static $_initialized = false;

	/**
	 * Runs the initializer
	 *
	 * By default, this will invoke the {@link _processAll} methods, which
	 * simply executes all of the initialization methods. Alternately, you can
	 * specify explicitly which initialization methods you want:
	 *
	 * <code>
	 *	ispCP_Initializer::run('_setIncludePath')
	 * </code>
	 *
	 * This is useful if you only want the include_path path initialized,
	 * without incurring the overhead of completely loading the entire
	 * environment.
	 *
	 * @throws ispCP_Exception
	 * @param string|ispCP_Config_Handler $command Initializer method to be
	 *	executed or an ispCP_Config_Handler object
	 * @param ispCP_Config_Handler $config Optional ispCP_Config_Handler object
	 * @return ispCP_Initializer The ispCP_Initializer instance
	 */
	public static function run($command = '_processAll',
		ispCP_Config_Handler $config = null) {

		if(!self::$_initialized) {

			if($command instanceof ispCP_Config_Handler) {
				$config = $command;
				$command = '_processAll';
			}

			$initializer = new self(
				is_object($config) ? $config : Config::getInstance()
			);

			$initializer->$command();

		} else {
			throw new ispCP_Exception(
				'Error: ispCP is already fully initialized!'
			);
		}

		return $initializer;
	}

	/**
	 * Create a new Initializer instance that references the given
	 * {@link ispCP_Config_Handler} instance
	 *
	 * @param ispCP_Config_Handler $config ispCP_Config_Handler instance
	 * @return void
	 */
	protected function __construct(ispCP_Config_Handler $config) {

		$this->_config = ispCP_Registry::set('Config', $config);
	}

	/**
	 * Object of this class shouldn't be cloned
	 */
	protected function __clone() {}

	/**
	 * Execute all of the availables initialization routines
	 *
	 * @return void
	 */
	 protected function _processAll() {

		// Check php version and availability of the Php Standard Library
		$this->_checkPhp();

		$this->_setDisplayErrors();

		// Initialize output buffering
		$this->_initializeOutputBuffering();

		// Include path
		$this->_setIncludePath();

		// Create or restore the session
		$this->_initializeSession();

		// Establish the connection to the database
		$this->_initializeDatabase();

		// Set additionally ispCP_ExceptionHandler_Writer_Abstract observers
		//$this->_setExceptionHandlerWriters();

		// Initialize logger
		$this->_initializeLogger();

		// Load all the configuration parameters from the database
		$this->_processConfiguration();

		// Se encodage
		$this->_setEncoding();

		// Set timezone
		$this->_setTimezone();

		$this->_initializeI18n();

		// Not yet fully integrated - (testing in progress)
		// $this->loadPlugins();

      	// Trigger the 'OnAfterInitialize' action hook
		// (will be activated later)
		//ispCP_Registry::get('Hook')->OnAfterInitialize();

		self::$_initialized = true;
	}

	/**
	 * Check for PHP version and Standard PHP library availability
	 *
	 * ispCP uses interfaces and classes that come from the Standard Php library
	 * under PHP version 5.1.4. This methods ensures that the PHP version used
	 * is more recent or equal to the PHP version 5.1.4 and that the SPL is
	 * loaded.
	 *
	 * Note: ispCP requires PHP 5.1.4 or later because some SPL interfaces were
	 * not stable in earlier versions of PHP.
	 *
	 * @throws ispCP_Exception
	 * @return void
	 */
	protected function _checkPhp() {

		// MAJOR . MINOR . TINY
		$php_version = substr(phpversion(), 0, 5);

		if(!version_compare($php_version, '5.1.4', '>=')) {
			$err_msg = sprintf(
				'Error: PHP version is %s. Version 5.1.4 or later is required!',
				$php_version
			);

		// We will use SPL interfaces like SplObserver, SplSubject
		// Note: Both ArrayAccess and Iterator interfaces are part of PHP core,
		// so, we can do the checking here without any problem.
		} elseif($php_version < '5.3.0' && !extension_loaded('SPL')) {
			$err_msg = 
				'Error: Standard PHP Library (SPL) was not detected! ' .
				'See http://php.net/manual/en/book.spl.php for more information!';
		} else {
			return;
		}

		throw new ispCP_Exception($err_msg);
	}

	/**
	 * Set the PHP display_errors parameter
	 *
	 * @return void
	 */
	protected function _setDisplayErrors() {

		if($this->_config->DEBUG) {
			ini_set('display_errors', 1);
		}
	}

	/**
	 * Set additional observers for the exception handler
	 *
	 * @return void
	 */
	protected function _setExceptionHandlerWriters() {

		// Get a reference to the ispCP_ExceptionHandler object
		$exceptionHandler = ispCP_Registry::get('ExceptionHandler');

		$writerObservers = explode(',', $this->_config->PHP_EXCEPTION_WRITERS);

		if(in_array('file', $writerObservers)) {
			// Writer not Yet Implemented
			/*$exceptionHandler->attach(
				new ispCP_ExceptionHandler_Writer_File(
					'path_to_logfile'
				)
			);*/
		} elseif(in_array('database', $writerObservers)) {
			/*$exceptionHandler->attach(
				new ispCP_ExceptionHandler_Writer_Db(
					ispCP_Registry::get('Pdo')
			);*/
		} elseif(in_array('mail', $writerObservers)) {
			// Writer not Yet Implemented
			/*$exceptionHandler->attach(
				new ispCP_ExceptionHandler_Writer_Mail(
					$this->_config->DEFAULT_ADMIN_ADDRESS
				)
			);*/
		}
	}

	/**
	 * Initialize the PHP output buffering / spGzip filter
	 *
	 * The buffer must be started at the earliest opportunity to avoid any
	 * encoding error (eg. during development phase where the developers uses
	 * some statements like echo, print in the code for debugging)
	 *
	 * Note: The hight level (like 8, 9) for compression are not recommended for
	 * performances reasons. The obtained gain with these levels is very small
	 * compared to the intermediate level like 6,7
	 *
	 * Note: ShowCompression option and checking for XmlHttpRequet will be done
	 * by a  filter hooked on the 'OnBeforeOutput' action hook.
	 *
	 * @return void
	 */
	protected function _initializeOutputBuffering() {

		// Create a new filter that will be applyed on the buffer output
		$filter = ispCP_Registry::set(
			'bufferFilter',
			new ispCP_Filter_Compress_Gzip(
				ispCP_Filter_Compress_Gzip::FILTER_BUFFER
			)
		);

		// Start the buffer and attach the filter to him
		ob_start(array($filter, ispCP_Filter_Compress_Gzip::FILTER_CALLBACK));
	}

	/**
	 * Set the include path
	 *
	 * Set the PHP include_path. Duplicates entries are removed.
	 *
	 * Note: Will be completed later with other paths (MVC switching).
	 *
	 * @return void
	 */
	protected function _setIncludePath() {

		$ps = PATH_SEPARATOR;

		// Get the current PHP include path string and transform it in array
		$include_path = explode(
			$ps, str_replace('.' . $ps, '', ini_get('include_path'))
		);

		// Adds the ispCP gui/include ABSPATH to the PHP include_path
		array_unshift($include_path, dirname(dirname(__FILE__)));

		// Transform array of path to string and set the new PHP include_path
		set_include_path('.' . $ps .implode($ps, array_unique($include_path)));
	}

	/**
	 * Create/restore the session
	 *
	 * @return void
	 */
	protected function _initializeSession() {

		session_name('ispCP');

		if (!isset($_SESSION))
			session_start();
	}

	/**
	 * Establishes the connection to the database
	 *
	 * This methods establishes the default connection to the database by using
	 * configuration parameters that come from the basis configuration object
	 * and then, register the {@link ispCP_Database} instance in the
	 * {@link ispCP_Registry} for shared access.
	 *
	 * A PDO instance is also registered in the registry for shared access.
	 *
	 * @throws ispCP_Exception
	 * @return void
	 * @todo Add a specific test to check if the db keys were generated and 
	 * throws an exception if its not the case - Don't use global variables
	 */
	protected function _initializeDatabase() {

		global $ispcp_db_pass_key, $ispcp_db_pass_iv;

		require_once 'ispcp-db-keys.php';

		try {

			$connection = ispCP_Database::connect(
				$this->_config->DATABASE_USER,
				decrypt_db_password($this->_config->DATABASE_PASSWORD),
				$this->_config->DATABASE_TYPE,
				$this->_config->DATABASE_HOST,
				$this->_config->DATABASE_NAME
			);

		} catch(PDOException $e) {

			throw new ispCP_Exception(
				'Error: Unable to establish connection to the database! '.
				'SQL returned: ' . $e->getMessage()
			);
		}

		// Register both Database and PDO instances for shared access
		ispCP_Registry::set('Db', $connection);
		ispCP_Registry::set('Pdo', ispCP_Database::getRawInstance());
	}

	/**
	 * Not Yet Implemented
	 *
	 * Not used at this moment (testing in progress)
	 *
	 * @return void
	 */
	protected function _initializeLogger() {}

	/**
	 * Load configuration parameters from database
	 *
	 * This function retrieves all the parameters from the database and merge
	 * them with the basis configuration object.
	 *
	 * Parameters that exists in the basis configuration object will be replaced
	 * by them that come from the database. The basis configuration object
	 * contains parameters that come from the ispcp.conf configuration file or
	 * any parameter defined in the {@link environment.php} file.
	 *
	 * @return void
	 */
	protected function _processConfiguration() {

		// We get an ispCP_Config_Handler_Db object
		$db_cfg = Config::getInstance(Config::DB, ispCP_Registry::get('Pdo'));

		// Now, we can override our basis configuration object with parameter
		// that come from the database
		$this->_config->replaceWith($db_cfg);

		// Finally, we register the ispCP_Config_Handler_Db for shared access
		ispCP_Registry::set('Db_Config', $db_cfg);
	}

	/**
	 * Set encoding
	 *
	 * This methods set encoding for both communication database and PHP.
	 *
	 * @throws ispCP_Exception
	 * @return void
	 */
	protected function _setEncoding() {

		// Always send the following header:
		// Content-type: text/html; charset=UTF-8'
		// Note: This header can be overrided by calling the header() function
		ini_set('default_charset', 'UTF-8');

		// Switch optionally to utf8 based communication with the database
		if (isset($this->_config->DATABASE_UTF8) &&
			$this->_config->DATABASE_UTF8 == 'yes') {

			$db = ispCP_Registry::get('Db');

			if($db->Execute('SET NAMES `utf8`;') === false) {
				throw new ispCP_Exception(
					'Error: Unable to set charset for database communication! ' .
					'SQL returned: ' . $db->ErrorMsg()
				);
			}
		}
	}

	/**
	 * Set timezone
	 *
	 * This method ensures that the timezone is set to avoid any error with PHP
	 * versions equal or later than version 5.3.x
	 *
	 * This method acts by checking the `date.timezone` value, and sets it to
	 * the value from the ispCP PHP_TIMEZONE parameter if exists and if it not
	 * empty or to 'UTC' otherwise.
	 *
	 * Note : This method don't check if the timezone defined by the
	 * ispCP PHP_TIMEZONE parameter is valid.
	 *
	 * @return void
	 */
	protected function _setTimezone() {

		// Timezone is not set in the php.ini file ?
		if(ini_get('date.timezone') == '') {

			$timezone = (isset($this->_config->PHP_TIMEZONE) &&
				$this->_config->PHP_TIMEZONE != '')
					? $this->_config->PHP_TIMEZONE : 'UTC';

			ini_set('date.timezone', $timezone);
		}
	}

	/**
	 * Not Yet Implemented
	 *
	 * @return void
	 * @todo Ask Jochen for the new i18n library and initilization processing
	 */
	protected function _initializeI18n() {}

	/**
	 * Not yet implemented
	 *
	 * Not used at this moment because we have only one theme.
	 *
	 * @return void
	 */
	protected function _initializeLayout() {}

	/**
	 * Load all plugins
	 *
	 * This method loads all the active plugins. Only plugins for the current
	 * execution context are loaded.
	 *
	 * Note: Not used at this moment (testing in progress...)
	 *
	 * @return void
	 */
	protected function _loadPlugins() {

		// Load all the available plugins for the current execution context
		ispCP_Plugin_Helpers::getPlugins();

		// Register an ispCP_Plugin_ActionsHooks for shared access
		ispCP_Registry::set('Hook', ispCP_Plugin_ActionsHooks::getInstance());
	}
}
