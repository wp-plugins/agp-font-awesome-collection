<?php

class Fac extends Agp_Module {
    
    
    /**
     * Ajax
     * 
     * @var Fac_Ajax
     */
    private $ajax;
    
    /**
     * Settings
     * 
     * @var Fac_Settings
     */
    private $settings;
    
    /**
     * Shortcode Conctructor
     * 
     * @var Fac_Constructor
     */
    private $constructor;
    
    /**
     * Icon Repository
     * 
     * @var Fac_IconRepository
     */
    private $iconRepository;        
    
    
    /**
     * Shortcodes
     * 
     * @var Fac_Shortcodes
     */
    private $shortcodes;
    
    
    
    private $customElements;
    
    /**
     * The single instance of the class 
     * 
     * @var object 
     */
    protected static $_instance = null;    
    
	/**
	 * Main Instance
	 *
     * @return object
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}    
    
	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
    }        
    
    public function __construct() {
        parent::__construct(dirname(dirname(__FILE__)));
        
        include_once ( $this->getBaseDir() . '/types/shortcodes-post-type.php' );                
        
        $this->iconRepository = new Fac_IconRepository();
        $this->settings = Fac_Settings::instance( $this );
        $this->constructor = Fac_Constructor::instance();
        $this->shortcodes = Fac_Shortcodes::instance();
        $this->ajax = Fac_Ajax::instance();

        add_action( 'init', array($this, 'registerShortcodes' ), 998 );                
        add_action( 'init', array($this, 'init' ), 999 );        
        add_action( 'wp_enqueue_scripts', array($this, 'enqueueScripts' ));                
        add_action( 'admin_enqueue_scripts', array($this, 'enqueueAdminScripts' ));                

        add_action( 'admin_init', array($this, 'facTinyMCEButtons' ) );        
    }
    
    public function registerShortcodes() {
        $shortcodes = $this->settings->getSortcodes();
        if (!empty($shortcodes)) {
            foreach ($shortcodes as $key => $obj) {
                add_shortcode( $key, array( $this, 'doShortcode' ) );                     
            }
        }
        
        $this->customElements = $this->settings->getCustomElementList();        
        if (!empty($this->customElements)) {
            foreach ($this->customElements as $key => $title) {
                add_shortcode( $key, array( $this, 'doShortcode' ) );                     
            }
        }
    }
    
    public function init () {
        $this->iconRepository->refreshRepository();
    }
    
    public function facTinyMCEButtons () {
        global $pagenow;
        
        if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
           return;
        }

        if ( get_user_option('rich_editing') == 'true' && $pagenow != 'post-new.php' ) {
           add_filter( 'mce_external_plugins', array($this, 'facTinyMCEAddPlugin') );
           add_filter( 'mce_buttons', array($this, 'facTinyMCERegisterButtons'));
        }        
    }

    public function facTinyMCERegisterButtons( $buttons ) {
       array_push( $buttons, "|", "fac_icon" );
       return $buttons;
    }    
    
    public function facTinyMCEAddPlugin( $plugin_array ) {
        $plugin_array['fac_icon'] = $this->getAssetUrl() . '/js/fac-icon.js';
        return $plugin_array;        
    }        
    
    public function enqueueScripts () {
        wp_enqueue_style( 'fac-fa', $this->getBaseUrl() .'/vendor/agpfontawesome/components/css/font-awesome.min.css' );
        wp_enqueue_script( 'fac', $this->getAssetUrl('js/main.js'), array('jquery') );                                                         
        wp_enqueue_style( 'fac-css', $this->getAssetUrl('css/style.css') );  
    }        
    
    public function enqueueAdminScripts () {
        wp_enqueue_style( 'wp-color-picker' );        
        wp_enqueue_script( 'wp-color-picker' );        
        wp_enqueue_script('colorbox-js', $this->getAssetUrl() . '/libs/colorbox/jquery.colorbox-min.js',array('jquery'));
        wp_enqueue_style('colorbox-css', $this->getAssetUrl() . '/libs/colorbox/colorbox.css');        
        wp_enqueue_style( 'fac-fa', $this->getBaseUrl() .'/vendor/agpfontawesome/components/css/font-awesome.min.css' );
        wp_enqueue_script( 'fac', $this->getAssetUrl('js/admin.js'), array('jquery', 'wp-color-picker') );                                                         
        wp_enqueue_style( 'fac-css', $this->getAssetUrl('css/admin.css') );  
        wp_enqueue_style( 'fac-css-front', $this->getAssetUrl('css/style.css') );          

        wp_localize_script( 'fac', 'ajax_fac', array( 
            'base_url' => site_url(),         
            'ajax_url' => admin_url( 'admin-ajax.php' ), 
            'ajax_nonce' => wp_create_nonce('ajax_atf_nonce'),        
        ));          
    }            

    public function getIconRepository() {
        return $this->iconRepository;
    }

    public function setIconRepository(Fac_IconRepository $iconRepository) {
        $this->iconRepository = $iconRepository;
        return $this;
    }
    
    public function doShortcode ($atts, $content, $tag) {
        $shortcodes = $this->settings->getSortcodes();
        $customShortcodes = $this->customElements;
        
        if (!empty($shortcodes[$tag])) {
            $obj = $shortcodes[$tag];
            $default = $this->settings->getShortcodeDefaults($tag);            
            if (empty($atts) || !is_array($atts)) {
                $atts = array();
            }
            $atts = array_merge($default, $atts );        
            
            return $this->getTemplate($obj->template, $atts);                             
        } elseif (!empty($customShortcodes[$tag])) {
            return $this->doCustomShortcode($atts, $content, $tag);
        }
    }    
    
    public function doCustomShortcode ($atts, $content, $tag) {
        global $post;
        $content = '';
        $wpautop = has_filter( 'the_content', 'wpautop');
        
        $args = array(
            'post_type' => 'fac-shortcodes',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key'     => '_name',
                    'value'   => array( $tag ),
                    'compare' => 'IN',
                ),
            ),            
        );

        $query = new WP_Query($args);
        
        
        if ($wpautop) {
            remove_filter ('the_content', 'wpautop');    
        }
        
        while ( $query->have_posts() ) : $query->the_post();
            $content .= apply_filters('the_content', get_the_content());
        endwhile;        
        
        if ($wpautop) {
            add_filter('the_content', 'wpautop');
        }
        
        wp_reset_query();
        
        return $content;        
    }    
    
    public function getSettings() {
        return $this->settings;
    }
 
    public function getConstructor() {
        return $this->constructor;
    }
    
    public function getAjax() {
        return $this->ajax;
    }
    
    public function getShortcodes() {
        return $this->shortcodes;
    }

    public function getCustomElements() {
        return $this->customElements;
    }

}