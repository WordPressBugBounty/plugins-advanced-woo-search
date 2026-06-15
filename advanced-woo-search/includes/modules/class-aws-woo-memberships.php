<?php
/**
 *  WooCommerce Memberships plugin integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_Woo_Memberships' ) ) :

    /**
     * Class
     */
    class AWS_Woo_Memberships {

        /**
         * Main AWS_Woo_Memberships Instance
         *
         * Ensures only one instance of AWS_Woo_Memberships is loaded or can be loaded.
         *
         * @static
         * @return AWS_Woo_Memberships - Main instance
         */
        protected static $_instance = null;

        private $data = array();

        /**
         * Main AWS_Woo_Memberships Instance
         *
         * Ensures only one instance of AWS_Woo_Memberships is loaded or can be loaded.
         *
         * @static
         * @return AWS_Woo_Memberships - Main instance
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

            // Restrict products
            add_filter( 'aws_search_query_array', array( $this, 'search_query_array' ), 1 );

            // Restrict product categories
            add_filter( 'aws_terms_search_query', array( $this, 'terms_search_query' ), 1, 2 );

            // Restrict products content
            add_filter( 'aws_search_pre_filter_products', array( $this, 'aws_search_pre_filter_products' ), 1 );

            // Make search cache membership aware
            add_filter( 'aws_cache_name', array( $this, 'cache_name' ), 1 );

        }

        /*
         * Vary search cache bucket by user membership restrictions
         *
         * The default cache key is keyed by user role, but Memberships restrictions
         * are per user and independent of role. Two users sharing a role can have a
         * different set of restricted products, so the cache key must reflect that
         * set to avoid leaking ( or wrongly hiding ) restricted content.
         */
        public function cache_name( $cache_option_name ) {

            if ( ! function_exists( 'wc_memberships' ) ) {
                return $cache_option_name;
            }

            if ( current_user_can( 'wc_memberships_access_all_restricted_content' ) ) {
                return $cache_option_name . '_wcm_full';
            }

            $restricted_posts = wc_memberships()->get_restrictions_instance()->get_user_restricted_posts();
            $restricted_posts = ! empty( $restricted_posts ) ? (array) $restricted_posts : array();

            // Include category/term restricted products so the cache bucket reflects
            // the full set of products hidden for this user, not just direct ones.
            $restricted_posts = array_merge( $restricted_posts, $this->get_term_restricted_product_ids() );
            $restricted_posts = array_unique( array_map( 'intval', $restricted_posts ) );

            sort( $restricted_posts );

            return $cache_option_name . '_wcm_' . substr( md5( implode( ',', $restricted_posts ) ), 0, 12 );

        }

        /*
         * Hide restricted products
         */
        public function search_query_array( $query ) {

            global $wp_query;

            if ( ! current_user_can( 'wc_memberships_access_all_restricted_content' ) && function_exists( 'wc_memberships' ) ) {
                $feed_is_restricted = $wp_query instanceof \WP_Query && $wp_query->is_feed() && ! wc_memberships()->get_restrictions_instance()->is_restriction_mode( 'hide_content' );
                if ( $feed_is_restricted || wc_memberships()->get_restrictions_instance()->is_restriction_mode('hide') ) {
                    $restricted_posts = wc_memberships()->get_restrictions_instance()->get_user_restricted_posts();
                    if ( ! empty( $restricted_posts ) ) {
                        $query['search'] .= sprintf( ' AND ( id NOT IN ( %s ) )', implode( ',', $restricted_posts ) );
                    }

                    // Products restricted because they belong to a restricted category/term.
                    // get_user_restricted_posts() only returns directly restricted post IDs,
                    // so these have to be excluded separately.
                    $restricted_term_products = $this->get_term_restricted_product_ids();
                    if ( ! empty( $restricted_term_products ) ) {
                        $query['search'] .= sprintf( ' AND ( id NOT IN ( %s ) )', implode( ',', $restricted_term_products ) );
                    }
                }
            }

            return $query;

        }

        /*
         * Hide restricted categories
         */
        public function terms_search_query( $sql, $taxonomy ) {

            global $wpdb, $wp_query;

            if ( ! current_user_can( 'wc_memberships_access_all_restricted_content' ) && function_exists( 'wc_memberships' ) ) {
                $feed_is_restricted = $wp_query instanceof \WP_Query && $wp_query->is_feed() && !wc_memberships()->get_restrictions_instance()->is_restriction_mode('hide_content');
                if ( $feed_is_restricted || wc_memberships()->get_restrictions_instance()->is_restriction_mode('hide') ) {

                    $conditions = wc_memberships()->get_restrictions_instance()->get_user_content_access_conditions();
                    $conditions = isset( $conditions['restricted']['terms'] ) && is_array( $conditions['restricted']['terms'] ) ? $conditions['restricted']['terms'] : array();

                    if ( ! empty( $conditions ) && isset( $conditions['product_cat'] ) && ! empty( $conditions['product_cat'] ) ) {
                        $sql_terms = "AND $wpdb->term_taxonomy.term_id NOT IN ( " . implode( ',', $conditions['product_cat'] ) . " )";
                        $sql = str_replace( 'WHERE 1 = 1', 'WHERE 1 = 1 ' . $sql_terms, $sql );
                    }

                }
            }

            return $sql;

        }

        /*
         * Filter restricted products content
         */
        public function aws_search_pre_filter_products( $products_array ) {
            if ( ! current_user_can( 'wc_memberships_access_all_restricted_content' ) && function_exists( 'wc_memberships' ) ) {
                if ( wc_memberships()->get_restrictions_instance()->is_restriction_mode( 'hide_content' ) ) {
                    $restricted_posts = wc_memberships()->get_restrictions_instance()->get_user_restricted_posts();
                    $restricted_posts = ! empty( $restricted_posts ) ? array_map( 'intval', $restricted_posts ) : array();

                    // Include products restricted via a restricted category/term, so their
                    // content is masked too ( get_user_restricted_posts() omits these ).
                    $restricted_posts = array_merge( $restricted_posts, $this->get_term_restricted_product_ids() );

                    if ( ! empty( $restricted_posts ) ) {

                        $show_excerpts = 'yes' === get_option( 'wc_memberships_show_excerpts' );

                        foreach ( $products_array as $key => $product_item ) {
                            if ( array_search( $product_item['parent_id'], $restricted_posts ) !== false ) {
                                $products_array[$key]['image'] = wc_placeholder_img_src();
                                $products_array[$key]['excerpt'] = $show_excerpts ? $product_item['excerpt'] : '';
                                $products_array[$key]['price'] = '';
                                $products_array[$key]['categories'] = '';
                                $products_array[$key]['tags'] = '';
                                $products_array[$key]['brands'] = '';
                                $products_array[$key]['on_sale'] = '';
                                $products_array[$key]['sku'] = '';
                                $products_array[$key]['stock_status'] = '';
                                $products_array[$key]['rating'] = '';
                                $products_array[$key]['reviews'] = '';
                                $products_array[$key]['variations'] = '';
                                $products_array[$key]['add_to_cart'] = '';
                            }
                        }

                    }
                }
            }

            return $products_array;

        }

        /*
         * Get product IDs that are restricted for the current user because they
         * belong to a restricted category / term.
         *
         * WooCommerce Memberships restricts content via separate buckets ( posts,
         * post_types, taxonomies, terms ). get_user_restricted_posts() only returns
         * the directly restricted post IDs, so products that inherit a restriction
         * from a restricted term have to be resolved here.
         */
        private function get_term_restricted_product_ids() {

            global $wpdb;

            $restrictions = wc_memberships()->get_restrictions_instance();
            $conditions   = $restrictions->get_user_content_access_conditions();
            $terms        = isset( $conditions['restricted']['terms'] ) && is_array( $conditions['restricted']['terms'] ) ? $conditions['restricted']['terms'] : array();

            if ( empty( $terms ) ) {
                return array();
            }

            $term_ids = array();
            foreach ( $terms as $taxonomy_terms ) {
                if ( is_array( $taxonomy_terms ) ) {
                    $term_ids = array_merge( $term_ids, $taxonomy_terms );
                }
            }

            $term_ids = array_filter( array_unique( array_map( 'intval', $term_ids ) ) );

            if ( empty( $term_ids ) ) {
                return array();
            }

            $term_ids_string = implode( ',', $term_ids );

            $product_ids = $wpdb->get_col( "
                SELECT DISTINCT tr.object_id
                FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.term_id IN ( {$term_ids_string} )
            " );

            if ( empty( $product_ids ) || is_wp_error( $product_ids ) ) {
                return array();
            }

            $product_ids = array_map( 'intval', $product_ids );

            // A rule may grant access to specific products inside an otherwise
            // restricted term - those must not be hidden.
            $granted_posts = $restrictions->get_user_granted_posts();
            if ( ! empty( $granted_posts ) ) {
                $product_ids = array_diff( $product_ids, array_map( 'intval', $granted_posts ) );
            }

            return $product_ids;

        }

    }

endif;

AWS_Woo_Memberships::instance();