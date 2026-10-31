<?php
/**
 * Main plugin container.
 *
 * @package ItsDZ\Doczur\Core
 */

namespace ItsDZ\Doczur\Core;

use ItsDZ\Doczur\Admin\Admin_Menu;
use ItsDZ\Doczur\Admin\Editor_Panel_Assets;
use ItsDZ\Doczur\Admin\Product_Panel_Assets;
use ItsDZ\Doczur\Admin\Assets as Admin_Assets;
use ItsDZ\Doczur\Admin\Dashboard_Widget;
use ItsDZ\Doczur\Admin\Onboarding;
use ItsDZ\Doczur\Analytics\View_Tracker;
use ItsDZ\Doczur\Core\Migrations\Migrator;
use ItsDZ\Doczur\Frontend\Assets as Frontend_Assets;
use ItsDZ\Doczur\Frontend\Blocks;
use ItsDZ\Doczur\Frontend\Glossary_Highlighter;
use ItsDZ\Doczur\Frontend\Llms_Txt;
use ItsDZ\Doczur\Frontend\Rewrite_Manager;
use ItsDZ\Doczur\Frontend\Shortcode;
use ItsDZ\Doczur\Frontend\Template_Loader;
use ItsDZ\Doczur\Integrations\WooCommerce\Bootstrap as WooCommerce_Bootstrap;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\PostTypes\Meta_Fields;
use ItsDZ\Doczur\REST\Analytics_Controller;
use ItsDZ\Doczur\REST\Article_Controller;
use ItsDZ\Doczur\REST\Feedback_Controller;
use ItsDZ\Doczur\REST\KB_Controller;
use ItsDZ\Doczur\REST\Onboarding_Controller;
use ItsDZ\Doczur\REST\Sample_Data_Controller;
use ItsDZ\Doczur\REST\Search_Controller;
use ItsDZ\Doczur\REST\Settings_Controller;
use ItsDZ\Doczur\REST\Transfer_Controller;
use ItsDZ\Doczur\Search\Indexer;
use ItsDZ\Doczur\Security\Capabilities;
use ItsDZ\Doczur\Security\Content_Sanitizer;
use ItsDZ\Doczur\Taxonomies\Glossary_Taxonomy;
use ItsDZ\Doczur\Taxonomies\Section_Taxonomy;
use ItsDZ\Doczur\Taxonomies\Tag_Taxonomy;
use ItsDZ\Doczur\Taxonomies\Version_Taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates all Nirdeshio services from one place.
 */
final class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Registered services.
	 *
	 * @var Service[]
	 */
	private $services = array();

	/**
	 * Whether services have already been registered.
	 *
	 * @var bool
	 */
	private $registered = false;

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}

	/**
	 * Get the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register every plugin service exactly once.
	 *
	 * @return void
	 */
	public function register() {
		if ( $this->registered ) {
			return;
		}

		$this->services = $this->get_services();

		foreach ( $this->services as $service ) {
			$service->register();
		}

		$this->registered = true;
	}

	/**
	 * Build the service list.
	 *
	 * @return Service[]
	 */
	private function get_services() {
		$services = array(
			new Capabilities(),
			new Content_Sanitizer(),
			new KB_Post_Type(),
			new Article_Post_Type(),
			new Section_Taxonomy(),
			new Tag_Taxonomy(),
			new Version_Taxonomy(),
			new Glossary_Taxonomy(),
			new Meta_Fields(),
			new Migrator(),
			new Indexer(),
			new View_Tracker(),
			new KB_Controller(),
			new Article_Controller(),
			new Search_Controller(),
			new Feedback_Controller(),
			new Transfer_Controller(),
			new Analytics_Controller(),
			new Sample_Data_Controller(),
			new Settings_Controller(),
			new Onboarding_Controller(),
			new Admin_Menu(),
			new Onboarding(),
			new Admin_Assets(),
			new Editor_Panel_Assets(),
			new Dashboard_Widget(),
			new Template_Loader(),
			new Shortcode(),
			new Frontend_Assets(),
			new Blocks(),
			new Glossary_Highlighter(),
			new Llms_Txt(),
			new Rewrite_Manager(),
		);

		// Only with WooCommerce active. The old separate "Doczur Pro" add-on carried this same
		// feature; if it is still active it keeps doing so, so the two never register twice.
		if ( function_exists( 'WC' ) && ! class_exists( 'ItsDZ\\DoczurPro\\Bootstrap' ) ) {
			$services[] = new WooCommerce_Bootstrap();
			$services[] = new Product_Panel_Assets();
		}

		/**
		 * Let add-on plugins (or any third party) register their
		 * own Service instances, without Free ever needing to know who is
		 * using this filter or why. This is the one and only extension
		 * point for adding backend behavior to Nirdeshio.
		 *
		 * @param Service[] $services Registered services.
		 */
		return apply_filters( 'itsdz_services', $services );
	}
}
