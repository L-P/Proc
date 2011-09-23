<?php

/// POO interface to proc_* functions.
class Proc {
	const STDIN		= 0; //< Index of the stdin pipe.
	const STDOUT	= 1; //< Index of the stdout pipe.
	const STDERR	= 2; //< Index of the stderr pipe.

	protected $proc = null; //< Our proc handler.
	protected $pipes = null; ///< Array with stdin, stdout and stderr.

	/// Default parameters for proc_open(), will be overrided by thoses given to __construct().
	protected $procParams = array(
		'cmd' => null,
		'descriptorspec'	=> array(
			self::STDIN			=> array('pipe', 'r'),
			self::STDOUT		=> array('pipe', 'w'),
			self::STDERR		=> array('pipe', 'w'),
		),
		'cwd'				=> null,
		'env'				=> null,
		'other_options'		=> null
	);


	/** Constructor.
	 * \param $cmd command to execute.
	 * \param $options array containg the other parameters to give to proc_open().
	 * */
	public function __construct($cmd, array $options = array()) {
		$this->procParams = array_merge($this->procParams, array_intersect_key($options, $this->procParams));
		$this->procParams['cmd'] = $cmd;
	}


	/** Opens (runs) the process.
	 * \return true on success, false on failure.
	 * */
	public function open() {
		if($this->proc)
			throw new \RuntimeException('Process already running.');

		extract($this->procParams);

		$this->proc = proc_open($cmd, $descriptorspec, $this->pipes, $cwd, $env, $other_options);

		if($this->proc === false)
			$this->proc = null;

		return !is_null($this->proc);
	}


	/** Returns the status of the process.
	 * \return stdClass containing the result of proc_get_status() or null if the process is not running.
	 */
	public function status() {
		if(!$this->proc)
			return null;

		return (object) proc_get_status($this->proc);
	}


	/** Closes the process and returns its exit code.
	 * \return exit code of the process.
	 * */
	public function close() {
		if(!$this->proc)
			throw new \RuntimeException('Process not running.');

		// PHP says we must close pipes to avoid deadlocks when closing the process.
		foreach($this->pipes as $pipe) {
			if(empty($pipe))
				continue;

			fclose($pipe);
		}
		$this->pipes = null;

		$ret = proc_close($this->proc);
		$this->proc = null;
		return $ret;
	}


	/** Returns the contents of stdout.
	 * \return contents of stdout.
	 * */
	public function out() {
		return stream_get_contents($this->pipes[self::STDOUT]);
	}


	/** Returns the contents of stderr.
	 * \return contents of stderr.
	 * */
	public function err() {
		return stream_get_contents($this->pipes[self::STDERR]);
	}


	/** Writes to stdin.
	 * \param $in what to write to stdin.
	 * */
	public function in($in) {
		if(!$this->pipes[self::STDIN])
			throw new \RuntimeException('Can only write to stdin once.');

		fwrite($this->pipes[self::STDIN], $in);
		fclose($this->pipes[self::STDIN]);
		$this->pipes[self::STDIN] = null;
	}


	/// Destructor.
	public function __destruct() {
		if(!$this->proc)
			return null;

		$this->close();
	}
}


$foo = new Proc('cat');
print_r($foo->open());		echo PHP_EOL;
$foo->in('test');
print_r($foo->status());
print_r($foo->out());		echo PHP_EOL;
print_r($foo->close());		echo PHP_EOL;

