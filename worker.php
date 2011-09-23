<?php
/* <contact@leo-peltier.fr> wrote this file. As long as you retain this
 * notice you can do whatever you want with this stuff. If we meet some day,
 * and you think this stuff is worth it, you can buy me a beer in return.
 *																Léo Peltier
 */

class Worker extends Proc {
	public function __construct($params = null) {
		parent::__construct("php $params");
		$this->open();
	}
}

