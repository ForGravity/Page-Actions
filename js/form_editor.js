var FGPageActions = function() {

	var self = this,
	    $    = jQuery;

	self.init = function() {

		$( document ).bind( 'gform_load_field_settings', function( event, field ) {

			// Get selected field.
			var field = self.getSelectedField( field );

			self.destroyExistingUI();
			
			// If this is not a page field, exit.
			if ( 'page' !== field.type ) {
				return;
			}
			
			self.initUI();

			$( '#pageActions' ).on( 'change', function() {
				self.setFieldSettings( JSON.parse( $( this ).val() ) );
			} );

		} );

	}

	/**
	 * Destroy existing Page Actions settings UI.
	 *
	 * @since 1.0
	 *
	 * @return object
	 */
	self.destroyExistingUI = function() {

		// If no Vue.js virtual machine is initialized, exit.
		if ( 'object' !== typeof self.$vm ) {
			return;
		}

		// Destroy Vue.js virtual machine.
		self.$vm.$destroy();

		// Remove settings element.
		$( '#pageActions_setting' ).remove();

	}

	/**
	 * Initialize Page Actions settings UI.
	 *
	 * @since 1.0
	 *
	 * @return object
	 */
	self.initUI = function() {

		// Copy template to settings.
		$( '<li id="pageActions_setting" class="pageActions_setting field_setting"></li>' ).insertAfter( $( '.conditional_logic_nextbutton_setting' ) );
		$( '#pageActions_setting' ).html( $( '#pageActions_template' ).html() );

		// Get current settings.
		var settings = self.getFieldSettings();

		// Initialize Vue.js virtual machine.
		self.$vm = new Vue( {

			// Define template element.
			el: '#pageActions_setting',

			// Attach Page Actions settings.
			data: settings,

			// Assign forms data to virtual machine.
			beforeMount: function() {
				this.dictionary = forgravity_pageactions_form_editor_strings.dictionary;
			},

			// Push settings to input.
			mounted: function() {
				$( '#pageActions_setting #pageActions' ).val( JSON.stringify( this.$data ) );
			},

			// Get selected forms.
			computed: {

				selectedObjectTypes: function() {

					var selected = [];

					for ( var i = 0; i < this.objects.length; i++ ) {

						selected.push( this.objects[ i ].type );

					}

					return selected;

				}

			},

			// Update settings field on change.
			watch: {

				enabled: function() {
					this.saveSettings();
				},

				objects: {
					handler: function() {
						this.saveSettings();
					},
					deep: true
				}

			},

			methods: {

				addObjectGroup: function( index ) {

					this.objects.splice( ( index + 1 ), 0, {
						type: 0,
						id:   [ 0 ],
					} );

				},

				removeObjectGroup: function( index ) {

					this.objects.splice( index, 1 );

				},

				addObjectID: function( index, objectIndex ) {

					this.objects[ index ].id.splice( ( objectIndex + 1 ), 0, 0 );

				},

				removeObjectID: function( index, objectIndex ) {

					this.objects[ index ].id.splice( objectIndex, 1 );

				},

				resetObjectIDs: function( index ) {

					this.objects[ index ].id = [ 0 ];

				},

				saveSettings: function() {

					var settings = {
						enabled: this.enabled,
						objects: this.objects,
					};

					$( '#pageActions' ).val( JSON.stringify( settings ) ).trigger( 'change' );

				},

			}

		} );

	}

	/**
	 * Get default Page Actions settings.
	 *
	 * @since 1.0
	 *
	 * @return object
	 */
	self.getDefaultSettings = function() {

		return {
			enabled: false,
			objects: [
				{
					type: 0,
					id:   [ 0 ],
				}
			],
		};

	}

	/**
	 * Get Page Actions settings for field.
	 *
	 * @since 1.0
	 *
	 * @param object field Currently selected field.
	 *
	 * @return object
	 */
	self.getFieldSettings = function( field ) {

		// Get field.
		var field = field ? field : self.getSelectedField();

		return field.pageActions ? field.pageActions : self.getDefaultSettings();

	}

	/**
	 * Set Page Actions settings for field.
	 *
	 * @since 1.0
	 *
	 * @param object|string settings Page Actions settings.
	 */
	self.setFieldSettings = function( settings ) {

		// Convert field settings from string.
		if ( 'string' === typeof settings ) {
			settings = JSON.parse( settings );
		}

		// Get field.
		var field = self.getSelectedField();

		// Save field settings.
		field.pageActions = settings;

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Get a specific form field.
	 *
	 * @since 1.0
	 *
	 * @param int fieldId Field ID.
	 *
	 * @return object|null
	 */
	self.getField = function( fieldId ) {

		// If we cannot get the form object, return false.
		if ( ! form ) {
			return null;
		}

		// Loop through the form fields.
		for ( var i = 0; i < form.fields.length; i++ ) {

			// If this is not the target field, skip it.
			if ( fieldId == form.fields[ i ].id ) {
				return form.fields[ i ];
			}

		}

		return null;

	}

	/**
	 * Retrieve currently selected field in form editor.
	 *
	 * @since 1.0
	 *
	 * @param object field Currently selected field.
	 *
	 * @return object
	 */
	self.getSelectedField = function( field ) {

		// Get selected field.
		return field == null ? GetSelectedField() : field;

	}

	self.init();

}
