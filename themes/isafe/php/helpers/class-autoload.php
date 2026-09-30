<?php
/**
 * Set up Theme Autoloader.
 *
 * @package Mavero
 */

namespace Mavero\Helpers;

/**
 * Class Autoload.
 *
 * @package Mavero
 */
class Autoload {

	/**
	 * Namespace to directory mapping.
	 *
	 * @var array
	 */
	protected $namespace_dir_map = [];

	/**
	 * Set up the autoloader.
	 */
	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
	}

	/**
	 * Add mapping for a directory to a namespace.
	 *
	 * @param string $namespace_name Namespace.
	 * @param string $dir            Absolute path to the directory.
	 */
	public function add( $namespace_name, $dir ) {
		$this->namespace_dir_map[ trim( $namespace_name, '/\\' ) ] = rtrim( $dir, '/\\' );

		krsort( $this->namespace_dir_map ); // Ensure the sub-namespaces are matched first.
	}

	/**
	 * Autoload the registered classes.
	 *
	 * @param string $class_name Fully qualified class name.
	 *
	 * @return void
	 */
	public function autoload( $class_name ) {
		$paths = $this->resolve( $class_name );

		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) {
				require_once $path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable, WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound

				return; // Return as soon as we've resolved the first one.
			}
		}
	}

	/**
	 * Resolve the requested classname to the possible file path
	 * of a registered namespace by type (class, interface, trait).
	 *
	 * @param string $class_name Fully qualified class name.
	 *
	 * @return array List of mapped file paths.
	 */
	public function resolve( $class_name ) {
		$prefixes = [ 'class', 'interface', 'trait' ];

		foreach ( $this->namespace_dir_map as $namespace => $path ) {
			if ( 0 === strpos( $class_name, $namespace . '\\' ) ) { // Append the trailing slash to not match SomeClassName where SomeClass is defined.
				$class_name = substr( $class_name, strlen( $namespace ) + 1 );

				$file_path_template = $this->file_path_from_parts(
					[
						$path,
						$this->class_to_file_path_template( $class_name, '{prefix}' ),
					]
				);

				return array_map(
					function ( $prefix ) use ( $file_path_template ) {
						return str_replace( '{prefix}', $prefix, $file_path_template );
					},
					$prefixes
				);
			}
		}

		return [];
	}

	/**
	 * Map fully qualified class names to file path
	 * according to WP coding standard rules.
	 *
	 * @param string $a_class     Fully qualified class name.
	 * @param string $placeholder Placeholder string to use for the file type designation.
	 *
	 * @return string
	 */
	protected function class_to_file_path_template( $a_class, $placeholder = '{prefix}' ) {
		$class_parts = explode( '\\', $a_class );
		$class_name  = array_pop( $class_parts );

		// Map nested namespaces to sub-directories.
		if ( ! empty( $class_parts ) ) {
			$class_parts = array_map(
				[ $this, 'class_to_file_name' ],
				$class_parts
			);
		}

		// Add filename at the end.
		$class_parts[] = sprintf(
			'%s-%s.php',
			$placeholder,
			$this->class_to_file_name( $class_name )
		);

		return $this->file_path_from_parts( $class_parts );
	}

	/**
	 * Generate file path based on components and the system
	 * directory separator.
	 *
	 * @param array $parts File path parts.
	 *
	 * @return string
	 */
	protected function file_path_from_parts( $parts ) {
		return implode( DIRECTORY_SEPARATOR, $parts );
	}

	/**
	 * Sanitize class name to filename according to WP coding standards.
	 *
	 * @param string $class_name Class name.
	 *
	 * @return string
	 */
	protected function class_to_file_name( $class_name ) {
		return strtolower( str_replace( '_', '-', $class_name ) );
	}
}
