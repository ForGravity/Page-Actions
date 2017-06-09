<?php

namespace ForGravity\PageActions;

use GFAddOn;
use GFCommon;
use GFFormDisplay;
use GFForms;
use GFFormsModel;

GFForms::include_addon_framework();

/**
 * Page Actions for Gravity Forms.
 *
 * @since     1.0
 * @author    ForGravity
 * @copyright Copyright (c) 2017, Travis Lopes
 */
class Page_Actions extends GFAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of Page Actions for Gravity Forms.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from pageactions.php
	 */
	protected $_version = FG_PAGEACTIONS_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.2';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'forgravity-pageactions';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'forgravity-pageactions/pageactions.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'https://forgravity.com/plugins/page-actions/';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Page Actions for Gravity Forms';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Page Actions';

	/**
	 * Stores entries to be used when applying Page Actions.
	 *
	 * @since  1.0
	 * @access private
	 * @var    array $_entry_cache Entries used when applying Page Actions.
	 */
	private $_entry_cache = array();

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @return $_instance
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Register needed hooks.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function init() {

		parent::init();

		add_filter( 'gform_pre_render', array( $this, 'maybe_run_actions' ), 10 );
		add_filter( 'gform_submit_button', array( $this, 'maybe_add_hidden_input' ), 10, 2 );

	}

	/**
	 * Register needed admin hooks.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function init_admin() {

		parent::init_admin();

		add_action( 'gform_editor_js', array( $this, 'initialize_field_settings' ) );
		add_action( 'gform_field_advanced_settings', array( $this, 'add_field_settings_fields' ), 10, 2 );

	}

	/**
	 * Enqueue needed scripts.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function scripts() {

		$scripts = array(
			array(
				'handle'    => 'forgravity_vendor_vue',
				'src'       => $this->get_base_url() . '/js/vendor/vue.min.js',
				'version'   => $this->_version,
			),
			array(
				'handle'  => 'forgravity_pageactions_form_editor',
				'deps'    => array( 'jquery', 'forgravity_vendor_vue' ),
				'src'     => $this->get_base_url() . '/js/form_editor.js',
				'version' => $this->_version,
				'enqueue' => array( array( 'admin_page' => array( 'form_editor' ) ) ),
				'strings' => array(
					'dictionary' => $this->get_dictionary_for_form_editor()
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );

	}

	/**
	 * Enqueue needed stylesheets.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function styles() {

		$styles = array(
			array(
				'handle'  => $this->_slug . '_form_editor',
				'src'     => $this->get_base_url() . '/css/form_editor.css',
				'version' => $this->_version,
				'enqueue' => array( array( 'admin_page' => array( 'form_editor' ) ) ),
			),
		);

		return array_merge( parent::styles(), $styles );

	}





	// # FORM SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Add Progressive Profiling Javascript initialization to the form settings page.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function initialize_field_settings() {
		?>

		<script type="text/javascript">

			fieldSettings['page'] += ', .pageActions_setting';

			jQuery( document ).ready( function() {
				window.FGPageActions = new FGPageActions();
			} );

		</script>

		<?php
	}

	/**
	 * Add Progressive Profiling settings field to the field settings tab.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param int $position The position that the settings will be displayed.
	 * @param int $form_id  The ID of the form being edited.
	 */
	public function add_field_settings_fields( $position, $form_id ) {

		// If this is not the end of the advanced settings, exit.
		if ( -1 !== $position ) {
			return;
		}

		?>
		<div id="pageActions_template" style="display: none;">

			<input type="hidden" id="pageActions"></textarea>

			<div class="section_label">
				<?php esc_attr_e( 'Page Actions', 'forgravity_pageactions' ); ?>
			</div>

			<label class="inline">
				<input type="checkbox" v-model="enabled" />
				<?php esc_attr_e( 'Enable Page Actions', 'forgravity_pageactions' ); ?>
			</label>

			<ul class="objectGroups" v-if="enabled">

				<li class="objectGroup" v-for="( objectGroup, index ) in objects">

					<select v-model="objects[ index ].type" v-on:change="resetObjectIDs( index )">
						<option :value="0"><?php esc_html_e( 'Select an Object Type', 'forgravity_pageactions' ); ?></option>
						<option v-for="type in dictionary" :disabled="selectedObjectTypes.includes( type.type ) && type.type !== objectGroup.type" :key="type.type" :value="type.type">{{ type.label }}</option>
					</select>

					<a class="add_field_choice" v-on:click="addObjectGroup( index )">
						<i class="gficon-add"></i>
					</a>
					<a class="delete_field_choice" v-if="objects.length > 1" v-on:click="removeObjectGroup( index )">
						<i class="gficon-subtract"></i>
					</a>

					<ul class="ids" v-if="objectGroup.type">

						<li class="id" v-for="( id, objectIndex ) in objectGroup.id">

							<select v-model="objectGroup.id[ objectIndex ]">
								<option :value="0"><?php esc_html_e( 'Select an Object', 'forgravity_pageactions' ); ?></option>
								<option v-for="object in dictionary[ objectGroup.type ].objects" :disabled="objectGroup.id.includes( object.id ) && object.id !== objectGroup.id[ objectIndex ]" :key="object.id" :value="object.id">{{ object.label }}</option>
							</select>

							<a class="add_field_choice" v-if="objectGroup.id.length < dictionary[ objectGroup.type ].objects.length" v-on:click="addObjectID( index, objectIndex )">
								<i class="gficon-add"></i>
							</a>
							<a class="delete_field_choice" v-if="objectGroup.id.length > 1" v-on:click="removeObjectID( index, objectIndex )">
								<i class="gficon-subtract"></i>
							</a>

						</li>

					</ul>

				</li>

			</ul>

		</div>
		<?php

	}

	/**
	 * Prepare forms and their fields for Progressive Profiling settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_current_form()
	 * @uses GFAddOn::get_registered_addons()
	 * @uses GFAddOn::get_short_title()
	 * @uses GFAddOn::get_slug()
	 *
	 * @return array
	 */
	public function get_dictionary_for_form_editor() {

		// Initialize return array.
		$dictionary = array();

		// Get the current form.
		$current_form = $this->get_current_form();

		// Add notifications.
		if ( ! empty( $current_form['notifications'] ) ) {

			// Add object type to dictionary.
			$dictionary['notification'] = array(
				'type'    => 'notification',
				'label'   => esc_html__( 'Notifications', 'forgravity_pageactions' ),
				'objects' => array(),
			);

			// Loop through notifications.
			foreach ( $current_form['notifications'] as $notification ) {

				// Add notification to dictionary.
				$dictionary['notification']['objects'][] = array(
					'id'    => esc_attr( $notification['id'] ),
					'label' => esc_html( $notification['name'] ),
				);

			}

		}

		// Loop through registered Add-Ons.
		foreach ( GFAddOn::get_registered_addons() as $addon ) {

			// If get_instance method does not exist, skip.
			if ( ! method_exists( $addon, 'get_instance' ) ) {
				continue;
			}

			// Get Add-On instance.
			$addon = call_user_func( array( $addon, 'get_instance' ) );

			// If this is not a Feed Add-On or is a Payment Add-On, skip.
			if ( ! is_subclass_of( $addon, 'GFFeedAddOn' ) || is_subclass_of( $addon, 'GFPaymentAddOn' ) ) {
				continue;
			}

			// Get feeds.
			$feeds = $addon->get_feeds();

			// If no feeds are configured for Add-On, skip.
			if ( empty( $feeds ) ) {
				continue;
			}

			// Add object type to dictionary.
			$dictionary[ $addon->get_slug() ] = array(
				'type'    => $addon->get_slug(),
				'label'   => esc_html( $addon->get_short_title() ),
				'objects' => array(),
			);

			// Loop through feeds.
			foreach ( $feeds as $feed ) {

				// Add feed to dictionary.
				$dictionary[ $addon->get_slug() ]['objects'][] = array(
					'id'    => esc_attr( $feed['id'] ),
					'label' => rgars( $feed, 'meta/feed_name' ) ? esc_html( $feed['meta']['feed_name'] ) : esc_html( $feed['meta']['feedName'] ),
				);

			}

		}

		return $dictionary;

	}





	// # FORM RENDER ---------------------------------------------------------------------------------------------------

	/**
	 * Maybe Run actions for page.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $form Current form object.
	 *
	 * @uses GFFormDisplay::get_current_page()
	 * @uses GFFormsModel::get_page_by_number()
	 * @uses Page_Actions::get_form_page_count()
	 * @uses Page_Actions::get_processed_pages()
	 * @uses Page_Actions::run_actions()
	 */
	public function maybe_run_actions( $form ) {

		// If this is not a multi-page form, return.
		if ( 1 === $this->get_form_page_count( $form ) ) {
			return $form;
		}

		// If we are currently on the first page, return.
		if ( 1 === GFFormDisplay::get_current_page( $form['id'] ) ) {
			return $form;
		}

		// Get previous page number.
		$previous_page = GFFormDisplay::get_current_page( $form['id'] ) - 1;

		// Get processed pages.
		$processed_pages = $this->get_processed_pages();

		// If previous page was already processed, return.
		if ( isset( $processed_pages[ $previous_page ] ) ) {
			return $form;
		}

		// Get page to process.
		$page_to_process = GFFormsModel::get_page_by_number( $form, GFFormDisplay::get_current_page( $form['id'] ) );

		// If Page Actions is not setup or not enabled, return.
		if ( ! isset( $page_to_process->pageActions ) || ( isset( $page_to_process->pageActions ) && ! $page_to_process->pageActions['enabled'] ) ) {
			return $form;
		}

		// Run actions.
		$this->run_actions( $page_to_process, $previous_page, $form );

		return $form;

	}

	/**
	 * Run actions for page.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param object $page        Page field object.
	 * @param int    $page_number Page number being processed.
	 * @param array  $form        Current form object.
	 *
	 * @uses GFCommon::send_notifications()
	 * @uses GFFeedAddOn::get_feed()
	 * @uses GFFeedAddOn::is_feed_condition_met()
	 * @uses GFFeedAddOn::process_feed()
	 * @uses GFFormsModel::get_current_lead()
	 * @uses Page_Actions::get_addon_by_slug()
	 * @uses Page_Actions::get_processed_pages()
	 */
	public function run_actions( $page, $page_number, $form ) {

		// Get processed pages.
		$processed_pages = $this->get_processed_pages();

		// Get current entry.
		$entry = GFFormsModel::get_current_lead();

		// Loop through objects groups.
		foreach ( $page->pageActions['objects'] as $object_group ) {

			// If object group type is not defined, skip.
			if ( 0 === $object_group['type'] ) {
				continue;
			}

			// Remove empty object IDs.
			$object_group['id'] = array_filter( $object_group['id'] );

			// If no object IDs are defined, skip.
			if ( empty( $object_group['id'] ) ) {
				continue;
			}

			// Run actions based on object type
			switch ( $object_group['type'] ) {

				case 'notification':

					// Send notifications.
					GFCommon::send_notifications( $object_group['id'], $form, $entry );

					// Add notification IDs to procesed pages.
					$processed_pages[ $page_number ][ $object_group['type'] ] = $object_group['id'];

					break;

				default:

					// Get Feed Add-On.
					$addon = $this->get_addon_by_slug( $object_group['type'] );

					// If Add-On does not exist, skip.
					if ( ! is_object( $addon ) ) {
						continue;
					}

					// Loop through feed IDs.
					foreach ( $object_group['id'] as $feed_id ) {

						// Get feed object.
						$feed = $addon->get_feed( $feed_id );

						// If feed condition is met, process feed.
						if ( $addon->is_feed_condition_met( $feed, $form, $entry ) ) {

							// Process feed.
							$addon->process_feed( $feed, $entry, $form );

							// Add object ID to procesed pages.
							$processed_pages[ $page_number ][ $object_group['type'] ][] = $feed_id;

						}

					}

					break;

			}

		}

		// Set processed pages.
		$this->set_processed_pages( $processed_pages );

	}

	/**
	 * Add hidden input containing processed page actions.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $button_input The string containing the <input> tag to be filtered.
	 * @param array  $form         The form currently being processed.
	 *
	 * @uses Page_Actions::get_form_page_count()
	 * @uses Page_Actions::get_processed_pages()
	 *
	 * @return string
	 */
	public function maybe_add_hidden_input( $button_input, $form ) {

		// If this is not a multi-page form, return.
		if ( 1 === $this->get_form_page_count( $form ) ) {
			return $button_input;
		}

		// Add input.
		$button_input .= sprintf(
			"<input type='hidden' name='fg_pageactions_processed' value='%s' />",
			json_encode( $this->get_processed_pages() )
		);

		return $button_input;

	}




	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Get Add-On instance by slug.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $slug Add-On slug.
	 *
	 * @uses GFAddOn::get_registered_addons()
	 *
	 * @return object|null
	 */
	public function get_addon_by_slug( $slug = '' ) {

		// If not slug is defined, return.
		if ( rgblank( $slug ) ) {
			return null;
		}

		// Loop through registered Add-Ons.
		foreach ( GFAddOn::get_registered_addons() as $addon ) {

			// If get_instance method does not exist, skip.
			if ( ! method_exists( $addon, 'get_instance' ) ) {
				continue;
			}

			// Get Add-On instance.
			$addon = call_user_func( array( $addon, 'get_instance' ) );

			// If the Add-On slug does not match the Add-On we are looking for, skip it.
			if ( $addon->get_slug() !== $slug ) {
				continue;
			}

			return call_user_func( array( $addon, 'get_instance' ) );

		}

		return null;

	}

	/**
	 * Get number of pages for form.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $form Form object.
	 *
	 * @uses GFCommon::get_fields_by_type()
	 *
	 * @return int
	 */
	public function get_form_page_count( $form ) {

		// Get page fields.
		$page_fields = GFCommon::get_fields_by_type( $form, array( 'page' ) );

		return count( $page_fields ) + 1;

	}

	/**
	 * Get pages processed by Page Actions.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::is_json()
	 * @uses Page_Actions::set_processed_pages()
	 *
	 * @return array
	 */
	public function get_processed_pages() {

		// If processed pages are set, return.
		if ( isset( $this->processed_pages ) ) {
			return $this->processed_pages;
		}

		// Get processed pages.
		$processed = rgpost( 'fg_pageactions_processed' );

		// Convert to array.
		$processed = $this->is_json( $processed ) ? json_decode( $processed, ARRAY_A ) : array();

		// Set processed pages.
		$this->set_processed_pages( $processed );

		return $this->processed_pages;

	}

	/**
	 * Set pages processed by Page Actions.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $processed Processed pages.
	 */
	public function set_processed_pages( $processed = array() ) {

		$this->processed_pages = $processed;

	}

}
