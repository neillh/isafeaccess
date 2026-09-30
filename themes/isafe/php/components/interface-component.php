<?php
/**
 * Interface for classes that act as theme components.
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Interface Component
 */
interface Component {
	/**
	 * Init the component. Hooks go in here.
	 *
	 * @return void
	 */
	public function init();
}
