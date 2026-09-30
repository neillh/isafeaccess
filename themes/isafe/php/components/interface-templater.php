<?php
/**
 * Interface for classes that return template tags.
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Interface Templater
 */
interface Templater {
	/**
	 * Retrieve the template tags.
	 *
	 * @return array
	 */
	public function get_template_tags();
}
