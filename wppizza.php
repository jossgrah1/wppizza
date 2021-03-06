<?php
/*
Plugin Name: WPPizza
Description: Maintain your restaurant menu online and accept cash on delivery orders. Set categories, multiple prices per item and descriptions. Conceived for Pizza Delivery Businesses, but flexible enough to serve any type of restaurant.
Author: ollybach
Plugin URI: http://wordpress.org/extend/plugins/wppizza/
Author URI: https://www.wp-pizza.com
Version: 2.11.7.13
License:

  Copyright 2012 ollybach (dev@wp-pizza.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**set the following as  constants so we can use it throughout*/
/**although some/most of these are not necessary anymore, let's keep them for legacy reasons as they might have been used in extensions (notably add-ingredients < v1.1)*/
if(!defined('WPPIZZA_NAME')){
	define('WPPIZZA_NAME', 'WPPizza');/*allow change of name in admin, just set define('WPPIZZA_NAME', 'New Name') in the wp-config.php*/
}
/*
to save us having to mess around with templates for single items (when linked from search results for example set an identifier in permalinks
to change the variable (in case there are namespace clashes or just if one prefers another var,  set define('WPPIZZA_SINGLE_VAR', 'new-var') in the wp-config.php (lowercase , no spaces)
*/
if(!defined('WPPIZZA_SINGLE_PERMALINK_VAR')){
	define('WPPIZZA_SINGLE_PERMALINK_VAR', 'menu_item');
}
define('WPPIZZA_CLASS', 'WPPizza');
define('WPPIZZA_SLUG', 'wppizza');/* DON NOT EVEN THINK ABOUT CHANGING THIS*/
define('WPPIZZA_LOCALE', 'wppizza-locale');
define('WPPIZZA_POST_TYPE', ''.WPPIZZA_SLUG.'');
define('WPPIZZA_TAXONOMY', ''.WPPIZZA_POST_TYPE.'_menu');
define('WPPIZZA_PATH', plugin_dir_path(__FILE__) );
define('WPPIZZA_URL', plugin_dir_url(__FILE__) );

add_action('widgets_init', create_function('', 'register_widget("'.WPPIZZA_CLASS.'");'));
/***************************************************************
*
*	[uninstall]
*
***************************************************************/
register_uninstall_hook( __FILE__, 'wppizza_uninstall' );
/***************************************************************
*
*	[deactivate]
*
***************************************************************/
register_deactivation_hook( __FILE__, 'wppizza_deactivate' );
/***remove cronjobs****/
function wppizza_deactivate() {
	wp_clear_scheduled_hook( 'wppizza_cron' );
}

/***************************************************************
*
*	[CLASS]
*
***************************************************************/

if ( ! class_exists( ''.WPPIZZA_CLASS.'' ) ) {
class WPPizza extends WP_Widget {

	public $pluginVersion;
	protected $pluginSlug;
	protected $pluginLocale;
	public $pluginOptions;
	public $pluginSession;
	protected $pluginName;
	protected $pluginSlugCategoryTaxonomy;
	protected $pluginNagNotice;
	protected $pluginGateways;
	protected $pluginUrl;

	public $pluginOrderTable;

/********************************************************
*
*
*	[Constructor]
*
*
********************************************************/
 function __construct() {

	/**init constants***/
	$this->pluginVersion='2.11.7.13';//increment in line with stable tag in readme and version above
 	$this->pluginName="".WPPIZZA_NAME."";
 	$this->pluginSlug="".WPPIZZA_SLUG."";//set also in uninstall when deleting options
	$this->pluginSlugCategoryTaxonomy="".WPPIZZA_TAXONOMY."";//also on uninstall delete wppizza_children as well as widget
	$this->pluginOrderTable="".WPPIZZA_SLUG."_orders";
	$this->pluginLocale="".WPPIZZA_LOCALE."";
	$this->pluginOptions = get_option(WPPIZZA_SLUG,0);
	$this->pluginNagNotice=0;//default off->for use in updates to this plugin
	$this->pluginPath=__FILE__;
	/**to get the template paths, uri's and possible subdir and set vars accordingly**/
	$pathDirUri=$this->wppizza_template_paths();
	$this->pluginTemplateDir=$pathDirUri['template_dir'];/**to amend get_stylesheet_directory() according to whether wppizza subdir exists*/
	$this->pluginTemplateUri=$pathDirUri['template_uri'];/**to amend get_stylesheet_directory_uri() according to whether wppizza subdir exists*/
	$this->pluginLocateDir=$pathDirUri['locate_dir'];/**to add relevant subdir - if exists - to locate_template*/
	/**blog charset*/
	$this->blogCharset=get_bloginfo('charset');

	/********************************************************************************************
		set session per blogid when multisite and enabled to avoid having same cart
		contents between different network sites (unless we want this)
	*********************************************************************************************/
	if(is_multisite() ){
		$multisession=true;
		/*get settings from parent blog for  this**/
		switch_to_blog(BLOG_ID_CURRENT_SITE);
			$wppOptions=get_option('wppizza');
			if(!$wppOptions['plugin_data']['wp_multisite_session_per_site']){
				$multisession=false;	
			}
		restore_current_blog();
		global $blog_id;		
		if($multisession){
			$this->pluginSession=$this->pluginSlug.''.$blog_id;
		}else{
			$this->pluginSession=$this->pluginSlug;	
		}
	}else{
		$this->pluginSession=$this->pluginSlug;
	}
	/**session name for user data for example such as address etc that keeps it's values across multisites**/
		$this->pluginSessionGlobal=$this->pluginSlug.'Global';


	/***************************************
		classname and description
	***************************************/
    $widget_opts = array (
        'classname' => WPPIZZA_CLASS,
        'description' => __('A Pizza Restaurant Plugin', $this->pluginLocale)
    );

    $this->WP_Widget(false, $name=$this->pluginName, $widget_opts);

    add_action('init', array($this, 'wppizza_load_plugin_textdomain'));

    /**allow overwriting of pluginVars in seperate class*/
    add_action('init', array( $this, 'wppizza_extend'),1);

	/**add wpml . must run front and backend (ajax request)***/
	add_action('init', array( $this, 'wppizza_wpml_localization'),99);

}

/*****************************************************************************************************************
*
*
*	[widget functions - apparently these have to be in main plugin when calling "extends WP_Widget"/"widgets_init"]
*	[althoug one can probably load them via includes - one day]
*
*
******************************************************************************************************************/
    /*****************************************************
     * load text domain on init.
     ******************************************************/
  	public function wppizza_load_plugin_textdomain(){
        load_plugin_textdomain(WPPIZZA_LOCALE, false, dirname(plugin_basename( __FILE__ ) ) . '/lang' );
    }
    /*****************************************************
     * Generates the administration form for the widget.
     * @instance    The array of keys and values for the widget.
     ******************************************************/
	function form($instance) {
    	include(WPPIZZA_PATH.'views/widget-admin.php');
    }
    /*******************************************************
     * Outputs the content of the widget.
     * @args            The array of form elements
     * @instance
     ******************************************************/
    function widget($args, $instance) {
		require(WPPIZZA_PATH.'views/widget.php');
    }
    /*******************************************************
     *
     * set default and return options for widget
     *
     ******************************************************/
	private function wppizza_default_widget_settings(){
		 $defaults=array(
            'title' => __("Shoppingcart", $this->pluginLocale),
            'type' => 'cart',
            'suppresstitle' => '',
            'noheader' => '',
            'width' => '',
            'height' => '',
            'openingtimes' => 'checked="checked"',
            'orderinfo' => 'checked="checked"'
        );
		return $defaults;
	}
    /*******************************************************
     *
     * available main options to choose from in widget
     *
     ******************************************************/
	private function wppizza_type_options(){
			$items['category']=__('Category Page', $this->pluginLocale);
			$items['navigation']=__('Navigation', $this->pluginLocale);
			$items['cart']=__('Cart', $this->pluginLocale);
			$items['orderpage']=__('Orderpage', $this->pluginLocale);
			$items['openingtimes']=__('Openingtimes', $this->pluginLocale);
			$items['search']=__('Search', $this->pluginLocale);
		return $items;
	}
	/****************************************************************
	*
	*	[get/set Template Directories/Uri's. also check for subdir 'wppizza']
	*
	***************************************************************/
	function wppizza_template_paths(){
		$paths['template_dir']='';
		$paths['template_uri']='';
		$paths['locate_dir']='';
		$dir=get_stylesheet_directory();
		$uri=get_stylesheet_directory_uri();

		if(is_dir($dir.'/'.WPPIZZA_SLUG)){
			$paths['template_dir']=$dir.'/'.WPPIZZA_SLUG;
			$paths['template_uri']=$uri.'/'.WPPIZZA_SLUG;
			$paths['locate_dir']=WPPIZZA_SLUG.'/';
		}else{
			$paths['template_dir']=$dir;
			$paths['template_uri']=$uri;
			$paths['locate_dir']='';
		}

		return $paths;
	}

	/*******************************************************
	*
	*
	*	[set/save submitted user post data in session, exclude tips though ]
	*	[moved from actions to be available throughout]
	*
	******************************************************/
	function wppizza_sessionise_userdata($postUserData,$orderFormOptions) {
			if (!session_id()) {session_start();}
			$params = array();
			parse_str($postUserData, $params);
			/**selects are zero indexed*/
			foreach($orderFormOptions as $elmKey=>$elm){
				if($elm['type']=='select' && isset($params[$elm['key']])){
					foreach($elm['value'] as $a=>$b){
						if($params[$elm['key']]==$b){
							$params[$elm['key']]=''.$a.'';
						}
					}
				}
			}
			/******************************************
				[get entered data to re-populate input fields but loose irrelevant vars
			********************************************/
			/**empty first and start over**/
			if(isset($_SESSION[$this->pluginSessionGlobal]['userdata'])){
				unset($_SESSION[$this->pluginSessionGlobal]['userdata']);
			}
			foreach($orderFormOptions as $oForm){
				if($oForm['key']!='ctips'){/**tips should not be in the global user session**/
					if(isset($params[$oForm['key']])){
						$_SESSION[$this->pluginSessionGlobal]['userdata'][$oForm['key']]=$params[$oForm['key']];
					}
				}
			}
			/***eliminate notice of undefined index userdata**/
			if(!isset($_SESSION[$this->pluginSessionGlobal]['userdata'])){$_SESSION[$this->pluginSessionGlobal]['userdata']=array();}


			/*also keep selected gateway in session*/
			if(isset($_SESSION[$this->pluginSessionGlobal]['userdata']['gateway'])){
				/**store previously selected in case we need to fall back to it**/
				//$prevGwFallback=$_SESSION[$this->pluginSessionGlobal]['userdata']['gateway'];
				/*unset session*/
				unset($_SESSION[$this->pluginSessionGlobal]['userdata']['gateway']);
			}
			$selectedGateway=!empty($params['wppizza-gateway']) ? strtoupper(wppizza_validate_string($params['wppizza-gateway'])) : '';
			
			/*reset session if not empty*/
			if($selectedGateway!=''){
				$_SESSION[$this->pluginSessionGlobal]['userdata']['gateway']=$selectedGateway;
			}
		
			/**allow filtering of session data**/
			$_SESSION[$this->pluginSessionGlobal]['userdata'] = apply_filters('wppizza_filter_sessionise_userdata', $_SESSION[$this->pluginSessionGlobal]['userdata'],$params);

		return $params;
	}
	/*********************************************************************************
	*
	*	[WPML : make strings wpml compatible]
	*	only include if icl_translate exists
	* 	only include once in admin to register strings (as it saves a ton of icl queries)
	********************************************************************************/
	function wppizza_wpml_localization(){
		/*get the wpml'd order page*/
		if($this->pluginOptions!=0) {
			if(function_exists('icl_object_id')){
				$this->pluginOptions['order']['orderpage']=icl_object_id($this->pluginOptions['order']['orderpage'],'page', true);
			}
		}
		if(function_exists('icl_translate')){
			if( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX )){
				require_once(WPPIZZA_PATH .'inc/wpml.inc.php');
			}else{
				require(WPPIZZA_PATH .'inc/wpml.inc.php');
			}
		}
	}
	function wppizza_wpml_localization_gateways(){
		if(function_exists('icl_translate')){/*only if wpml*/
			if( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX )){
				require_once(WPPIZZA_PATH .'inc/wpml.gateways.inc.php');
			}else{
				require(WPPIZZA_PATH .'inc/wpml.gateways.inc.php');
			}
		}
	}
	/*******************************************************
     *
     *	[EXTEND : class must start with WPPIZZA_EXTEND_]
     *
     ******************************************************/
	function wppizza_extend() {
		$allClasses=get_declared_classes();
		$wppizzaExtend=array();
		foreach ($allClasses AS $oe=>$class){
			$chkStr=substr($class,0,15);
			if($chkStr=='WPPIZZA_EXTEND_'){
				$wppizzaExtend[$oe]=new $class;
				foreach($wppizzaExtend[$oe] as $k=>$v){
					$this->$k=$v;
				}
			}
		}
	}
}
/*=======================================================================================*/
/*=========================load actions and gateways class===============================*/
/*=======================================================================================*/
add_action('plugins_loaded', 'wppizza_all_actions');
function wppizza_all_actions() {
	require_once(WPPIZZA_PATH .'classes/wppizza.actions.inc.php');
	$WPPIZZA_ACTIONS=new WPPIZZA_ACTIONS();
}
add_action('plugins_loaded', 'wppizza_get_gateways');
function wppizza_get_gateways() {
	require_once(WPPIZZA_PATH .'classes/wppizza.gateways.inc.php');
	$WPPIZZA_GATEWAYS=new WPPIZZA_GATEWAYS();
}
/*=======================================================================================*/
/*=======================================================================================*/
/*=======================================================================================*/
}
?>