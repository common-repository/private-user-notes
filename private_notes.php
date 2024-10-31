<?php
/**
 * Plugin Name:       Private User Notes
 * Plugin URI:        https://www.mansurahamed.com/private-user-notes/
 * Description:       Create a frontend private note section for users where they can save & edit their private notes. Users can print their private note with a single click. Allows you to restrict your private note feature for specific user roles or user.
 * Version:           1.0.3
 * Author:            mansurahamed
 * Author URI:        https://www.upwork.com/freelancers/~013259d08861bd5bd8
 * Text Domain:       private-user-notes
 */


if(!class_exists('PrivateUserNotes'))
{
	class PrivateUserNotes
	{
		private $options = array(); //options of plugin settings : array
		private $option_name; //wp_options , name : string
		
		public function __construct()
		{
			$this->options = array(
					'roles' => array(),
					'include_only' => '',
					'exclude' => '',
			);
			$this->option_name = 'private_user_note_options';
			add_action('wp_enqueue_scripts', array(&$this, 'register_scripts') );
			add_action( 'admin_menu', array(&$this,'add_menu_page') );
			add_shortcode('private-user-notes', array(&$this,'shortcode'));
		}
	
		public function register_scripts() //register plugin js file
		{
			wp_register_script( 'private-user-notes', plugins_url( '/assets/js/private-user-notes.js', __FILE__ ) );
		}
		
		/**
		*Adds admin settings page menu under Settings menu in dashboard
		*uses add_options_page() see wpcodex
		*/
		public function add_menu_page() 
		{
				add_options_page( 
			__( 'Private User Note Plugin', 'private-user-notes' ),
			__( 'Private User Notes', 'private-user-notes' ),
			'manage_options',
			'private_user_notes',
			array(&$this, 'admin_page')
				); 
		}
		
		/**
		*Renders the admin settings page at Settings->Private User Notes
		*@uses $wp_roles to get all available roles
		*@See save_admin_setting()
		*@uses save_admin_setting() function to save admin settings
		*/
		public function admin_page() //@settings page for admin
		{
			global $wp_roles;
			$output = '';
			$save_message = $this->save_admin_settings(); //@uses save_admin_setting() function to save admin settings
			?>
			<h2><?php echo esc_html_e('Private User Notes Settings', 'private-user-notes' ); ?><hr></h2>
				<form id="private-user-notes" action="" method="post">
				<?php wp_nonce_field( 'private_user_note_nonce', 'private_user_note_nonce_field' ); ?>
				<label for="roles"><?php echo esc_html_e('Enable for these roles only : ', 'private-user-notes' ); ?></label>
				<select name="roles[]" id="roles" multiple>
			<?php foreach($wp_roles->get_names() as $role_slug => $role_name) //gets all roles 
			{
				$selected = '';
				if(in_array($role_slug, $this->options['roles']) ) $selected = 'selected'; ?>
					<option value="<?php echo esc_attr($role_slug); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($role_name); ?></option>
			<?php } ?>
				</select><br/><br/>
				<label for="include_only"><?php echo esc_html_e('Show for these User ID only : ', 'private-user-notes' ); ?></label>
				<input name="include_only" type="text" size="40" placeholder="<?php echo esc_attr_e('Comma separated values', 'private-user-notes'); ?>" value="<?php echo esc_attr($this->options['include_only']); ?>" />
				<br/><br/>
				<label for="exclude"><?php echo esc_html_e('Hide for these User ID only : ', 'private-user-notes' ); ?></label>
				<input name="exclude" type="text" size="40" placeholder="<?php echo esc_attr_e('Comma separated values', 'private-user-notes'); ?>" value="<?php echo esc_attr($this->options['exclude']); ?>" />
				<br/><br/>
			<?php
				/**
				*Action hook to add new fields under admin settings
				*@example -
					add_action('private_user_note_setting_add_field','my_custom_admin_settings_fields');
					function my_custom_admin_settings_fields(){
					<input name="custom_field" type="text" value="some_value" />;
					}
				*/
				do_action('private_user_note_setting_add_field'); //action hook
			?>
				<br/><br/><input type="submit" class="button-primary" name="submit_settings" value="<?php echo esc_attr_e('Save Settings', 'private-user-notes' ); ?>" />
				</form><br/>
				<?php echo esc_html($save_message);
		}
		
		/**
		*Saves admin settings in wp_options table
		*@saves data in wp_options
		*@used in admin_page()
		*@returns string, for post save message
		*@return $msg
		*/
		private function save_admin_settings()
		{
			$msg = '';
			
			if(isset($_POST['submit_settings']) && wp_verify_nonce( $_POST['private_user_note_nonce_field'], 'private_user_note_nonce' )) // check if settings form submitted or load settings : see else
			{
				$this->options['roles'] = array();
				if(isset($_POST['roles'])) $this->options['roles'] = $_POST['roles'];
				if(isset($_POST['include_only'])) $this->options['include_only'] = $_POST['include_only'];
				if(isset($_POST['exclude'])) $this->options['exclude'] = $_POST['exclude'];
				
				/**
				*Adds custom settings field into the option array
				*@filter private_user_note_pre_save_setting
				*@param $options, array containing current options
				*@param $post, array contains all submitted data
				*@verify and add your custom data here
				*@returns options, array with added custom fields values
				*@example -
				add_filter('private_user_note_pre_save_setting','add_my_custom_option_fields',0,2);
				function add_my_custom_option_fields($options,$post){
				if(isset($post['my_custom_field_name'])){
					$options['my_custom_field_name'] = $post['my_custom_field_name'];
					return $options;
					}
				}
				*/
				$this->options = apply_filters('private_user_note_pre_save_setting', $this->options, $_POST);
				if(update_option($this->option_name, $this->options)) $msg = __('Settings saved successfully.', 'private-user-notes' ); //save successful message
			}
			else if(get_option($this->option_name)) $this->options = get_option($this->option_name);
			
			return $msg;
		}
		
		/**
		*Returns if current user has access to private note page
		*@returns bool 
		*@returns true if has access otherwise false
		*/
		private function can_have_note()
		{
			if(get_option($this->option_name))
			{
				$this->options = get_option($this->option_name); //load option from wp_options
				/**
				*Apply custom logic to determine if user can access private page or not
				*@filter  private_user_notes_custom_access
				*@param $options, array of settings options 
				*@returns true if can access, false if can't 
				*@example -
				add_filter('private_user_notes_custom_access','add_my_custom_access',0,1);
				function add_my_custom_access($options){
				if($options['my_custom_option'] == 'something') return true;
				else return false;
				}
				*/
				$custom_rule = apply_filters('private_user_notes_custom_access',$this->options);
				if($custom_rule == false) return false;
				$user = wp_get_current_user();
				$exclude = explode(',', $this->options['exclude']);
				if(in_array($user->ID, $exclude) ) return false; //user don't have access for ID ban
				$include = explode(',', $this->options['include_only']);
				if(in_array($user->ID, $include) ) return true; //has access for this ID
    			$roles = ( array ) $user->roles;
				if(!count($this->options['roles'])) return true; //all roles has access
				if(!count(array_intersect($roles, $this->options['roles']))) return false; //if user doesn't have any white role restrict access
				
				/**
				*Use filter 'private_user_notes_custom_access' to force if user must have all white roles given
				*/
				 return true;
			}
			else return true;
		}
		
		public function shortcode(){  //Renders shortcode
			/**
			/*Change restricted private note message
			*@filter private_user_notes_restricted_message
			*@param string, $restricted_message
			*@return string, $restricted_message
			*/
			if(!is_user_logged_in() || !$this->can_have_note()) return apply_filters('private_user_notes_restricted_message','<p>'.esc_html__('You don\'t have access to this content! ','private-user-notes').'</p>' );
			$user_id = get_current_user_id();
			if(!wp_script_is('jquery')) wp_enqueue_script('jquery');
			wp_enqueue_script( 'private-user-notes');
			$content = get_user_meta($user_id, 'private_user_notes',true); //get saved note
			/**
			/*Change submit button class
			*@filter private_user_notes_button_class
			*@return string, $classes  for css classes name separated by space
			*/
			$button_class = apply_filters('private_user_notes_button_class', 'submit wp-block-button__link wp-element-button');
			if(isset($_POST['private-user-notes']) && wp_verify_nonce( $_POST['private_user_note_save_nonce'], 'private_user_note_nonce' ))
			{
				update_user_meta($user_id, 'private_user_notes',wp_kses_post( stripslashes($_POST['private-user-notes'])) );
			}
			if(isset($_POST['edit_private_user_note']) && wp_verify_nonce( $_POST['private_user_note_edit_nonce'], 'private_user_note_nonce' ))
			{	
				/**
				/*Modifies the wp_editor for notes
				*@filter private_user_notes_editor_settings
				*@return array, $settings  for wp_editor settings
				*@see wp_editor in wp_codex
				*/
				ob_start();
				?>
				<form action="" method="post"><?php
				echo wp_nonce_field( 'private_user_note_nonce', 'private_user_note_save_nonce',true,false );
				wp_editor( $content, 'private-user-notes',apply_filters('private_user_notes_editor_settings',$setting=array()) ); ?>
				<br /><input name="save_user_note" class="<?php echo esc_attr($button_class); ?>" type="submit" value="<?php echo esc_attr__('Save Note', 'private-user-notes') ?>">
							</form>
			<?php
				return ob_get_clean();
			}
			else 
			{
				$content = wp_kses_post(get_user_meta($user_id, 'private_user_notes',true) ); //load_content
				$container = '<div id="private-user-notes">'.$content.'</div>';
				$buttons = '<form action="" method="post">'.wp_nonce_field( 'private_user_note_nonce', 'private_user_note_edit_nonce',true,false ).		
				'<input name="edit_private_user_note" class="'.esc_attr($button_class).'"  type="submit" value="'.(!trim($content)? esc_attr__('Add Note', 'private-user-notes'): esc_attr__('Edit Note', 'private-user-notes')).'"/ > &nbsp; <input type="button" class="'.esc_attr($button_class).'"  value="'.esc_attr__('Print Note', 'private-user-notes').'" onclick="print_private_user_notes();" />
				</form>';
				
				/**
				*Set position of frontend buttons before or after content
				*@filter private_user_notes_button_position
				*@param string, $position = 'top' or 'bottom', 'bottom' by default
				*@returns string, value of $position, top of bottom
				*/
				if(apply_filters('private_user_notes_button_position', 'bottom') == 'top') return $buttons.$container;
				else return $container.$buttons;
				
			}
		}


	}
}

$private_user_notes = new PrivateUserNotes(); 