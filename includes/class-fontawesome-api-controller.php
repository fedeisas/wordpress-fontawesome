<?php
namespace FortAwesome;

require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-metadata-provider.php';
require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesomeexception.php';
require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/error-util.php';

use \WP_REST_Controller, \WP_REST_Response, \WP_Error, \Error, \Exception;

if ( ! class_exists( 'FortAwesome\FontAwesome_API_Controller' ) ) :

	/**
	 * Controller class for the plugin's GraphQL API REST endpoint.
	 *
	 * This controller provides a REST route for WordPress client access to the
	 * Font Awesome GraphQL API. It delegates to {@see FontAwesome::query()}.
	 * The plugin's setting page is a React app that acts as such a client,
	 * querying kits.
	 *
	 * Requests to this REST route should have the following headers and body:
	 *
	 * <h3>Headers</h3>
	 * 
	 * `X-WP-Nonce`: include an appropriate nonce from WordPress.
	 *
	 * <h3>Body</h3>
	 *
	 * The request body should contain a GraphQL query document as a string.
	 *
	 * For example, the following query would return all available Font Awesome
	 * version numbers:
	 *
	 * ```
	 * query { versions }
	 * ```
	 *
	 * <h3>Internal Use vs. Public API</h3>
	 * 
	 * The PHP methods in this controller class are not part of this plugin's
	 * public API, but the REST route it exposes is.
	 * 
	 * If you need to issue a query from server-side PHP code to the
	 * Font Awesome API server, use the {@see FontAwesome::query()} method.
	 *
	 * If you need to issue a query from client-side JavaScript, send
	 * an HTTP POST request to WP REST route `/font-awesome/v1/api`.
	 */
	class FontAwesome_API_Controller extends WP_REST_Controller {

		/**
		 * @ignore
		 * @internal
		 */
		private $plugin_slug = null;

		/**
		 * @ignore
		 * @internal
		 */
		protected $namespace = null;

		/**
		 * @ignore
		 * @internal
		 */
		private $_metadata_provider = null;

		/**
		 * @ignore
		 * @internal
		 */
		public function __construct( $plugin_slug, $namespace ) {
			$this->plugin_slug = $plugin_slug;
			$this->namespace   = $namespace;
			$this->_metadata_provider = fa_metadata_provider();
		}

		/**
		 * Register REST routes.
		 *
		 * @internal
		 * @ignore
		 */
		public function register_routes() {
			$route_base = 'api';

			register_rest_route(
				$this->namespace,
				'/' . $route_base,
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'query' ),
						'permission_callback' => function() {
							return current_user_can( 'edit_posts' ); },
						'args'                => array(),
					),
				)
			);
		}

		/**
		 * Run the query by delegating to {@see FontAwesome_Metadata_Provider}.
		 * 
		 * Internal use only. This method is not part of this plugin's public API.
		 *
		 * @ignore
		 * @internal
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function query( $request ) {
			try {
				$result = $this->metadata_provider()->metadata_query( $request->get_body() );

				return new WP_REST_Response( json_decode( $result, true ), 200 );
			} catch( FontAwesomeServerException $e ) {
				return fa_500( $e );
			} catch( FontAwesomeException $e ) {
				return fa_400( $e );
			} catch ( Exception $e ) {
				return unknown_error_500($e);
			} catch ( Error $e ) {
				return unknown_error_500($e);
			}
		}

		/**
		 * Allows a test subclass to mock the metadata provider.
		 *
		 * Internal use only, not part of this plugin's public API.
		 *
		 * @internal
		 * @ignore
		 */
		protected function metadata_provider() {
			return $this->_metadata_provider;
		}
	}

endif; // end class_exists.
