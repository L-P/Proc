<?php
/* Léo Peltier <contact@leo-peltier.fr> wrote this file. As long as you retain
 * this notice you can do whatever you want with this stuff. If we meet some
 * day, and you think this stuff is worth it, you can buy me a beer in
 * return.
 */

/// Executes PHP code in a separate thread.
class Worker extends Proc {
    /** Executes PHP.
     * @param $params string containing the parameters to give to PHP.
     * If $params is a file path, PHP will execute the file.
     * */
    public function __construct($params = null) {
        parent::__construct("php $params");
        $this->open();
    }
}
