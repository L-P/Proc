<?php
/* Léo Peltier <contact@leo-peltier.fr> wrote this file. As long as you retain
 * this notice you can do whatever you want with this stuff. If we meet some
 * day, and you think this stuff is worth it, you can buy me a beer in
 * return.
 */

namespace lpeltier;

/// POO interface to proc_* functions.
class Proc {
    const STDIN  = 0; //< Index of the stdin pipe.
    const STDOUT = 1; //< Index of the stdout pipe.
    const STDERR = 2; //< Index of the stderr pipe.

    const SIGTERM = 15; //< SIGTERM signal number.
    const SIGKILL = 9;  //< SIGKILL signal number.

    const STD_BUFFER_SIZE = 1024; //< Reads from stdout and stderr will be made by chunks of n bytes.

    protected $proc  = null; //< Our proc handler.
    protected $pipes = null; ///< Array with stdin, stdout and stderr.

    /// Default parameters for proc_open(), will be overrided by thoses given to __construct().
    protected $procParams = array(
        'cmd' => null,
        'descriptorspec' => array(
            self::STDIN   => array('pipe', 'r'),
            self::STDOUT  => array('pipe', 'w'),
            self::STDERR  => array('pipe', 'w'),
        ),
        'cwd'           => null,
        'env'           => null,
        'other_options' => null
    );

    protected $wroteToIn = false; ///< Flag set if we already wrote to STDIN.

    /** Constructor.
     * @param $cmd command to execute.
     * @param $options array containg the other parameters to give to proc_open().
     * */
    public function __construct($cmd, array $options = array()) {
        $this->procParams = array_merge($this->procParams, array_intersect_key($options, $this->procParams));
        $this->procParams['cmd'] = $cmd;
    }


    /** Opens (runs) the process.
     * @return true on success, false on failure.
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
     * @return stdClass containing the result of proc_get_status() or null if the process is not running.
     */
    public function status() {
        if(!$this->proc)
            return null;

        return (object) proc_get_status($this->proc);
    }


    /** Closes the process and returns its exit code.
     * @return exit code of the process.
     * */
    public function close() {
        if(!$this->proc)
            throw new \RuntimeException('Process not running.');

        // PHP says we must close pipes to avoid deadlocks when closing the process.
        $this->closePipes();

        $ret = proc_close($this->proc);
        $this->proc = null;
        return $ret;
    }


    /** Returns the contents of stdout.
     * @return contents of stdout.
     * */
    public function out() {
        return $this->readPipe(self::STDOUT);
    }


    /** Returns the contents of stderr.
     * @return contents of stderr.
     * */
    public function err() {
        return $this->readPipe(self::STDERR);
    }


    /** Returns the contents of a pipe.
     * @return the contents of the given pipe.
     * */
    protected function readPipe($pipeNumber) {
        if(empty($this->pipes[$pipeNumber]))
            throw new \RuntimeException("Pipe $pipeNumber is unreachable.");

        return fread($this->pipes[$pipeNumber], self::STD_BUFFER_SIZE);
    }


    /** Returns the contents of a pipe.
     * This function is blocking, PHP will halt until EOF is reached.
     * @return the contents of the given pipe.
     * */
    protected function readBufferedPipe($pipeNumber) {
        if(empty($this->pipes[$pipeNumber]))
            throw new \RuntimeException("Pipe $pipeNumber is unreachable.");

        return stream_get_contents($this->pipes[$pipeNumber]);
    }


    /** Returns the contents of stderr.
     * @see readBufferedPipe().
     * is reached.
     * \returs contents of stderr.
     * */
    public function bufferedErr() {
        return $this->readBufferedPipe(self::STDERR);
    }

    /** Returns the contents of stdout.
     * @see readBufferedPipe().
     * is reached.
     * \returs contents of stdout.
     * */
    public function bufferedOut() {
        return $this->readBufferedPipe(self::STDOUT);
    }


    /** Writes to stdin.
     * @param $in what to write to stdin.
     * */
    public function in($in) {
        if($this->wroteToIn)
            throw new \RuntimeException('Can only write to stdin once.');

        if(empty($this->pipes[self::STDIN]))
            throw new \RuntimeException('STDIN is unreachable.');

        fwrite($this->pipes[self::STDIN], $in);
        fclose($this->pipes[self::STDIN]);
        $this->pipes[self::STDIN] = null;
        $this->wroteToIn = true;
    }

    protected function closePipes() {
        foreach($this->pipes as $pipe) {
            if(empty($pipe))
                continue;

            fclose($pipe);
        }
        $this->pipes = null;
        $this->wroteToIn = false;
    }

    /// Kills the process.
    public function kill($signal = self::SIGTERM) {
        if(!$this->proc)
            throw new \RuntimeException('Process not running.');

        $this->closePipes();
        proc_terminate($this->proc, $signal);
        $this->proc = null;
    }

    /// Destructor.
    public function __destruct() {
        if(!$this->proc)
            return null;

        $this->close();
    }
}
