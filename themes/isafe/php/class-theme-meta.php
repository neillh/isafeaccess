<?php
/**
 * Class Theme_Meta
 *
 * @package Mavero
 */

namespace Mavero;

/**
 * This class is responsible for holding theme meta information, such as version, path, etc.
 */
class Theme_Meta {

	/**
	 * Seperator used for URLs.
	 *
	 * @var string
	 */
	protected const URL_SEPARATOR = '/';

	/**
	 * The theme version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Absolute path to the theme root directory.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * URL to the theme root directory.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Theme_Meta constructor.
	 *
	 * @param string $version The theme version.
	 * @param string $path    The theme path.
	 */
	public function __construct( $version, $path ) {
		$this->version = $version;
		$this->path    = $this->normalize_path( $path );
		$this->url     = content_url( $this->path_to_root( WP_CONTENT_DIR ) );
	}

	/**
	 * Get the theme version.
	 *
	 * @return string
	 */
	public function version() {
		return $this->version;
	}

	/**
	 * Get path with normalized directory seperators.
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	protected function normalize_path( $path ) {
		return preg_replace( '/[\\/]+/', DIRECTORY_SEPARATOR, $path );
	}

	/**
	 * Get the theme path.
	 *
	 * @param string ...$append_to_path Path to append to the end of the theme path.
	 *
	 * @return string
	 */
	public function path( ...$append_to_path ) {
		$path = [
			$this->path,
		];

		if ( count( $append_to_path ) > 0 ) {
			$path = array_merge( $path, $append_to_path );
		}

		return implode( DIRECTORY_SEPARATOR, $path );
	}

	/**
	 * Get the path to an asset (assets/)
	 *
	 * @param string ...$append_to_path Path to append to the end of the theme path.
	 *
	 * @return string
	 */
	public function asset_path( ...$append_to_path ): string {
		return $this->path( 'assets', ...$append_to_path );
	}

	/**
	 * Resolve relative path back to the root directory.
	 *
	 * @param string $path Absolute or relative path to a file or directory.
	 *
	 * @return string|null
	 */
	public function path_to_root( $path ) {
		$path_from_root = $this->path_from_root( $path );

		// Resolve paths after the root directory.
		if ( ! empty( $path_from_root ) && strlen( $path_from_root ) ) {
			// Trim any trailing slashes before counting directories.
			$nest_level = substr_count( trim( $path_from_root, DIRECTORY_SEPARATOR ), DIRECTORY_SEPARATOR ) + 1;

			return implode( DIRECTORY_SEPARATOR, array_fill( 0, $nest_level, '..' ) );
		}

		$path = $this->normalize_path( $path );

		// Account for paths before the root directory.
		if ( str_starts_with( $this->path, $path ) ) {
			return ltrim( str_replace( $path, '', $this->path ), DIRECTORY_SEPARATOR );
		}

		return null;
	}

	/**
	 * Resolve absolute path relative to the theme directory.
	 *
	 * @param string $path Absolute path to an asset in the root directory.
	 *
	 * @return string|null
	 */
	public function path_from_root( $path ) {
		$path = $this->normalize_path( $path );

		if ( str_starts_with( $path, $this->path ) ) {
			return ltrim( str_replace( $this->path, '', $path ), DIRECTORY_SEPARATOR );
		}

		return null;
	}

	/**
	 * Retrieve the URL of the current path.
	 *
	 * @param string ...$append_to_path Path to append to the end of the theme path.
	 *
	 * @return string
	 */
	public function url( ...$append_to_path ) {
		$url = [
			$this->url,
		];

		if ( count( $append_to_path ) > 0 ) {
			$url = array_merge( $url, $append_to_path );
		}

		return implode( self::URL_SEPARATOR, $url );
	}

	/**
	 * Get the url to an asset (assets/)
	 *
	 * @param string ...$append_to_path Path to append to the end of the theme path.
	 *
	 * @return string
	 */
	public function asset_url( ...$append_to_path ): string {
		return $this->url( 'assets', ...$append_to_path );
	}

	/**
	 * @param array      $args        Template part args array.
	 * @param string     $key         The key to fetch.
	 * @param mixed|null $default_val Default value.
	 * @return mixed
	 */
	public function get_arg( array $args, string $key, mixed $default_val = null ): mixed {
		$value = null;

		if ( isset( $args[ $key ] ) && is_array( $args[ $key ] ) && 0 === count( $args[ $key ] ) ) {
			$value = $default_val;
		} else {
			$value = $args[ $key ] ?? $default_val;
		}

		return apply_filters( 'theme_args', $value, $key, $default_val, $args );
	}
}
