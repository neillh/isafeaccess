<?php
/**
 * Class Theme
 *
 * @package Mavero
 */

namespace Mavero;

use Mavero\Components\Article_Card;
use Mavero\Helpers\Utils;

class Theme {
	/**
	 * List of components.
	 *
	 * @var Components\Component[]
	 */
	private $components;

	/**
	 * Templater instances.
	 *
	 * @var Components\Templater[]
	 */
	private $template_tags;

	/**
	 * Specify list of supported components.
	 *
	 * @return Components\Component[]
	 */
	protected function core_components() {
		$classes = [
			Components\Foundation::class,
			Components\Accessibility::class,
			//Components\Block_Types_Allowed::class,
			Components\Styles::class,
			Components\Scripts::class,
			Components\Script_Custom_Attributes_Handler::class,
			Components\Editor::class,
			Components\WPCom_Thumbnail_Editor::class,
			Components\Block_Patterns::class,
			Components\Block_Registry::class,
			Components\Block_Styles::class,
			Components\Mega_Menu::class,
			Components\Capability::class,
			Components\Archive::class,
			Components\Custom_Login::class,
			Components\Security::class,
			Components\Post_Types\Testimonial::class,
			Components\Post_Types\Project::class,
			Components\Taxonomies\Industry::class,
		];

		$instances = [];
		foreach ( $classes as $class ) {
			$interfaces = class_implements( $class );
			$instances[] = new $class();
		}
		return $instances;
	}

	/**
	 * Theme constructor.
	 */
	public function __construct() {
		$components = $this->core_components();
		foreach ( $components as $component ) {
			Utils::throw_if_not_of_type( $component, Components\Component::class );
			$this->components[ get_class( $component ) ] = $component;
		}

		$this->template_tags = array_filter(
			$this->components,
			static function ( Components\Component $component ) {
				return $component instanceof Components\Templater;
			}
		);
	}

	/**
	 * Add a new component to the list.
	 *
	 * @param Components\Component $component The new Component to register.
	 *
	 * @throws \RuntimeException Throws an exception if component is already registered.
	 */
	public function add_component( Components\Component $component ) {
		$component_slug = get_class( $component );

		if ( isset( $this->components[ $component_slug ] ) ) {
			throw new \RuntimeException( 'Component ' . esc_html( $component_slug ) . ' has already been registered' );
		}

		$this->components[ $component_slug ] = $component;
		$component->init();

		if ( $component instanceof Components\Templater ) {
			$this->template_tags[ $component_slug ] = $component;
		}
	}

	/**
	 * Get registered component instance.
	 *
	 * @param string $component_slug The component slug.
	 *
	 * @return false|Components\Component Component instance if registered or false.
	 */
	public function get_component( $component_slug ) {

		if ( isset( $this->components[ $component_slug ] ) ) {
			return $this->components[ $component_slug ];
		}

		return false;
	}

	/**
	 * Init the theme and its components.
	 */
	public function init() {
		foreach ( $this->components as $component ) {
			$component->init();
		}
	}

	/**
	 * Magic call method.
	 *
	 * Will proxy to the template tag $method, unless it is not available, in which case an exception will be thrown.
	 *
	 * @param string $method Template tag name.
	 * @param array  $args   Template tag arguments.
	 *
	 * @return mixed Template tag result, or null if template tag only outputs markup.
	 *
	 * @throws \RuntimeException Thrown if the template tag does not exist.
	 */
	public function __call( $method, array $args ) {
		foreach ( $this->template_tags as $component ) {
			$tags = $component->get_template_tags();
			if ( isset( $tags[ $method ] ) ) {
				return call_user_func_array( $tags[ $method ], $args );
			}
		}

		throw new \RuntimeException(
			sprintf(
				/* translators: %s: template tag name */
				esc_html__( 'The template tag %s does not exist.', 'mavero' ),
				'xwp_theme()->' . esc_html( $method ) . '()'
			)
		);
	}
}
