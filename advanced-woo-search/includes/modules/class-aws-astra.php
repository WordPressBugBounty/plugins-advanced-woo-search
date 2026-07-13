<?php
/**
 * Astra theme integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_Astra' ) ) :

    /**
     * Class
     */
    class AWS_Astra {

        /**
         * Main AWS_Astra Instance
         *
         * Ensures only one instance of AWS_Astra is loaded or can be loaded.
         *
         * @static
         * @return AWS_Astra - Main instance
         */
        protected static $_instance = null;

        /**
         * Main AWS_Astra Instance
         *
         * Ensures only one instance of AWS_Astra is loaded or can be loaded.
         *
         * @static
         * @return AWS_Astra - Main instance
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct() {

            if ( AWS()->get_settings( 'seamless' ) === 'true' ) {
                add_filter( 'aws_js_seamless_selectors', array( $this, 'js_seamless_selectors' ), 1 );
                add_filter( 'aws_js_seamless_searchbox_markup', array( $this, 'seamless_searchbox_markup' ), 1 );
                add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 999 );
                add_filter( 'astra_get_search_form', array( $this, 'astra_markup' ), 999999 );
                add_filter( 'aws_searchbox_markup', array( $this, 'astra_aws_searchbox_markup' ), 1 );
                add_action( 'wp_head', array( $this, 'astra_head_action' ) );
            }

            /*
             * Register AWS search as a first-class element in the Astra Header Builder,
             * so it can be dragged into any header row/column alongside the built-in
             * elements ( Search, HTML, Widget, etc. ). Available regardless of the
             * "Seamless Integration" setting.
             */
            if ( class_exists( 'Astra_Builder_Helper' ) ) {
                add_filter( 'astra_header_desktop_items', array( $this, 'add_header_builder_item' ) );
                add_filter( 'astra_header_mobile_items', array( $this, 'add_header_builder_item' ) );
                add_action( 'astra_render_header_components', array( $this, 'render_header_builder_item' ), 10, 2 );
                add_filter( 'astra_customizer_configurations', array( $this, 'header_builder_configs' ), 30, 2 );
                add_action( 'wp_head', array( $this, 'header_builder_styles' ) );
            }

        }

        /*
         * Add the "AWS Search" element to the Astra Header Builder palette
         */
        public function add_header_builder_item( $items ) {
            $items['aws-search'] = array(
                'name'    => __( 'AWS Search', 'advanced-woo-search' ),
                'icon'    => 'search',
                'section' => 'section-header-aws-search',
            );
            return $items;
        }

        /*
         * Render the AWS search form for the header builder element.
         * Fires from the builder's `default` component dispatch for custom slugs.
         */
        public function render_header_builder_item( $slug, $device ) {

            if ( 'aws-search' !== $slug || ! function_exists( 'aws_get_search_form' ) ) {
                return;
            }

            $placeholder = function_exists( 'astra_get_option' ) ? astra_get_option( 'aws-header-search-placeholder' ) : '';

            /**
             * Filter the placeholder text used by the Astra header builder element
             * @since 3.67
             * @param string $placeholder Placeholder text ( empty to use the form's own setting )
             */
            $placeholder = apply_filters( 'aws_astra_header_builder_placeholder', $placeholder );

            $args = array();

            if ( $placeholder ) {
                $args['placeholder'] = $placeholder;
            }

            echo '<div class="ast-builder-layout-element ast-flex site-header-focus-item ast-header-aws-search" data-section="section-header-aws-search">';

            if ( is_customize_preview() && class_exists( 'Astra_Builder_UI_Controller' ) && method_exists( 'Astra_Builder_UI_Controller', 'render_customizer_edit_button' ) ) {
                Astra_Builder_UI_Controller::render_customizer_edit_button();
            }

            echo aws_get_search_form( false, $args );

            echo '</div>';

        }

        /*
         * Register the customizer settings for the header builder element through
         * Astra's own configuration pipeline, so the section and its controls show up
         * inside the header builder ( opened by the element's edit button ).
         */
        public function header_builder_configs( $configurations, $wp_customize ) {

            if ( ! defined( 'ASTRA_THEME_SETTINGS' ) || ! function_exists( 'astra_get_option' )
                 || ! class_exists( 'Astra_Builder_Helper' ) || ! property_exists( 'Astra_Builder_Helper', 'general_tab' ) ) {
                return $configurations;
            }

            $section     = 'section-header-aws-search';
            $general_tab = Astra_Builder_Helper::$general_tab;

            $configs = array(

                // Section
                array(
                    'name'     => $section,
                    'type'     => 'section',
                    'priority' => 80,
                    'title'    => __( 'AWS Search', 'advanced-woo-search' ),
                    'panel'    => 'panel-header-builder-group',
                ),

                // Builder context tabs ( General / Design / ... )
                array(
                    'name'        => $section . '-ast-context-tabs',
                    'section'     => $section,
                    'type'        => 'control',
                    'control'     => 'ast-builder-header-control',
                    'priority'    => 0,
                    'description' => '',
                ),

                // Option: placeholder text override
                array(
                    'name'      => ASTRA_THEME_SETTINGS . '[aws-header-search-placeholder]',
                    'default'   => astra_get_option( 'aws-header-search-placeholder' ),
                    'section'   => $section,
                    'type'      => 'control',
                    'control'   => 'text',
                    'priority'  => 20,
                    'title'     => __( 'Placeholder', 'advanced-woo-search' ),
                    'context'   => $general_tab,
                    'transport' => 'refresh',
                ),

            );

            return array_merge( $configurations, $configs );

        }

        /*
         * Minimal front-end styles for the header builder element
         */
        public function header_builder_styles() {

            if ( ! method_exists( 'Astra_Builder_Helper', 'is_component_loaded' ) || ! Astra_Builder_Helper::is_component_loaded( 'aws-search', 'header' ) ) {
                return;
            }
            ?>
            <style>
                .ast-header-aws-search {
                    width: 100%;
                }
                .ast-header-aws-search .aws-container {
                    width: 100%;
                    max-width: 400px;
                    margin: 0;
                }
                .ast-header-aws-search .aws-wrapper {
                    margin-bottom: 0;
                }
            </style>
            <?php

        }

        /*
         * Selector filter of js seamless
         */
        public function js_seamless_selectors( $selectors ) {
            $selectors[] = '.ast-search-box.header-cover form';
            $selectors[] = '.ast-search-box.full-screen form';
            return $selectors;
        }

        /*
         * Markup for seamless js integration
         */
        public function seamless_searchbox_markup( $markup ) {
            $markup = str_replace( 'aws-search-field', 'aws-search-field search-field', $markup );
            return $markup;
        }

        /*
         * Astra theme form markup
         */
        public function astra_markup( $output ) {
            if ( function_exists( 'aws_get_search_form' ) && is_string( $output ) ) {

                $pattern = '/(<form[\s\S]*?<\/form>)/i';
                $form = aws_get_search_form(false);

                if ( strpos( $output, 'aws-container' ) !== false ) {
                    $pattern = '/(<div class="aws-container"[\s\S]*?<form.*?<\/form><\/div>)/i';
                }

                $output = trim(preg_replace('/\s\s+/', ' ', $output));
                $output = preg_replace( $pattern, $form, $output );
                $output = str_replace( 'aws-container', 'aws-container search-form', $output );
                $output = str_replace( 'aws-search-field', 'aws-search-field search-field', $output );

            }
            return $output;
        }

        /*
         * Astra theme form markup
         */
        public function astra_aws_searchbox_markup( $markup ) {
            $markup = str_replace( 'aws-container', 'aws-container search-form', $markup );
            return $markup;
        }

        /*
         * Add custom js scripts
         */
        public function wp_enqueue_scripts() {

            $script = ' 
              document.addEventListener("awsLoaded", function() {
                jQuery(document).on("click", ".ast-search-box .close", function(e) {
                    jQuery(this).closest(".ast-search-box.header-cover").attr("style", "");
                });
              });
            ';

            if ( function_exists('astra_get_option') && astra_get_option( 'header-search-box-type' ) === 'header-cover' && class_exists('Astra_Icons') ) {

                $close_btn = '<span id="close" class="close">' . str_replace(array("\r", "\n"), '', Astra_Icons::get_icons( 'close', false )) . '</span>';

                $script .= '
                document.addEventListener("awsLoaded", function() {
                      if ( ! jQuery(".ast-search-box.header-cover .close").length > 0 ) {
                          jQuery(".ast-search-box.header-cover form").append(\'' . $close_btn . '\');
                      }
                  });
                ';

            }

            wp_add_inline_script( 'aws-script', $script);
            wp_add_inline_script( 'aws-pro-script', $script);

        }

        /*
         * Astra theme
         */
        public function astra_head_action() { ?>

            <style>
                .ast-search-menu-icon.slide-search .search-form {
                    width: auto;
                }
                .ast-search-menu-icon .search-form {
                    padding: 0 !important;
                }
                .ast-search-menu-icon.ast-dropdown-active.slide-search .ast-search-icon {
                    opacity: 0;
                }
                .ast-search-menu-icon.slide-search .aws-container .aws-search-field {
                    width: 0;
                    background: #fff;
                    border: none;
                }
                .ast-search-menu-icon.ast-dropdown-active.slide-search .aws-search-field {
                    width: 235px;
                }
                .ast-search-menu-icon.slide-search .aws-container .aws-search-form .aws-form-btn {
                    background: #fff;
                    border: none;
                }
                .ast-search-menu-icon.ast-dropdown-active.slide-search .ast-search-icon {
                    opacity: 1;
                }
                .ast-search-menu-icon.ast-dropdown-active.slide-search .ast-search-icon .slide-search.astra-search-icon {
                    opacity: 0;
                }
                .ast-search-box.header-cover .aws-container .aws-search-form {
                    background: transparent;
                }
                .ast-search-box.header-cover .aws-container .aws-search-form .aws-search-field,
                .ast-search-box.full-screen .aws-container .aws-search-form .aws-search-field {
                    outline: none;
                }
                .ast-search-box.header-cover .aws-container .aws-search-form .aws-form-btn,
                .ast-search-box.full-screen .aws-container .aws-search-form .aws-form-btn {
                    background: transparent;
                    border: none;
                }
                .ast-search-box.header-cover .aws-container .aws-search-form .aws-search-btn_icon,
                .ast-search-box.full-screen .aws-container .aws-search-form .aws-search-btn_icon,
                .ast-search-box.header-cover .aws-container .aws-search-form .aws-main-filter .aws-main-filter__current,
                .ast-search-box.full-screen .aws-container .aws-search-form .aws-main-filter .aws-main-filter__current {
                    color: #fff;
                }
                .ast-search-box.full-screen .aws-container {
                    margin: 40px auto !important;
                }
                .ast-search-box.full-screen .aws-container #close {
                    display: none;
                }
                .ast-search-box.full-screen .aws-container .aws-search-form {
                    background: transparent;
                    border-bottom: 2px solid #9E9E9E;
                    height: 50px;
                }
                .ast-search-box.full-screen .aws-container .aws-search-form .aws-search-field {
                    padding-bottom: 10px;
                }
            </style>

        <?php }
        
    }

endif;

AWS_Astra::instance();