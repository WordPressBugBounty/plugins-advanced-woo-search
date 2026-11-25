<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Admin_Black_Friday' ) ) :

    /**
     * Class for Black Friday sale banner on plugin settings pages
     *
     * None of the text here goes through a translation function, and that is deliberate. The
     * banner lives for ten days and its wording changes while the sale runs, so handing three
     * dozen throwaway strings to translate.wordpress.org would have translators working on
     * text that is gone before their work ships, and would push every locale further from the
     * 90% a language pack needs. Everything printed from here is English and is escaped at the
     * point of output.
     */
    class AWS_Admin_Black_Friday {

        /*
         * Sale end time, December 2 2026 23:59:59 UTC
         */
        const ENDS = 1796255999;

        /*
         * User meta key, set when user hides the banner
         */
        const META_KEY = 'aws_hide_bf2026_banner';

        /*
         * User meta key, set the first time this user opens a page that carries the banner
         *
         * Kept apart from META_KEY on purpose: the dot on the menu is there to bring someone to
         * the page, the banner is what they find once they are there. Reading the offer and
         * deciding it is not for you are two different things, and closing the banner must not
         * be the same event as having seen the menu.
         */
        const SEEN_META = 'aws_bf2026_page_seen';

        /*
         * Pages that show the banner, and therefore clear the dot
         */
        static private $banner_pages = array( 'aws-options', 'aws-performance' );

        /*
         * Constructor
         */
        public function __construct() {

            add_action( 'wp_ajax_aws-hidePromoBanner', array( $this, 'hide_banner' ) );

            // the dot is cleared before it is drawn, so it is already gone on the very load
            // that brings the user to the page
            add_action( 'admin_init', array( $this, 'mark_page_seen' ), 9 );
            add_action( 'admin_init', array( $this, 'add_menu_dot' ), 10 );

            // ?aws_promo=reset puts this user back to never having seen any of it
            add_action( 'admin_init', array( $this, 'reset_user_flags' ), 11 );

        }

        /*
         * Check if banner must be shown for current user
         * @param string $page Plugin admin page name
         * @return bool
         */
        static public function is_active( $page ) {

            // premium page has its own offer
            if ( $page === 'aws-premium' ) {
                return false;
            }

            // a variant asked for in the url is shown whatever the date and the hidden flag say
            if ( ! empty( $_GET['aws_promo'] ) && current_user_can( AWS_Helpers::user_admin_capability() ) ) {
                return true;
            }

            if ( time() >= self::ENDS ) {
                return false;
            }

            return ! get_user_meta( get_current_user_id(), self::META_KEY, true );

        }

        /*
         * Option holding the offer this site shows
         *
         * The offer is worked out once, on the first load of a plugin page, and then stays put:
         * every visit after that, and every user of the site, sees the same banner. Nothing is
         * re-checked, so installing or removing a plugin mid-sale does not change what is on
         * screen, and no page load pays for the detection again.
         */
        const OFFER_OPTION = 'aws_bf2026_offer';

        /*
         * Shape of what is stored
         *
         * Goes up when a release changes the keys of the stored array, which is the only thing
         * that makes a site work its offer out a second time.
         */
        const OFFER_VERSION = 1;

        /*
         * Brands a store must carry before search by brand is worth offering
         */
        const BRANDS_MIN_TERMS = 100;

        /*
         * Terms a custom taxonomy must hold before it says anything about the catalogue
         */
        const TAXONOMY_MIN_TERMS = 20;

        /*
         * Products a catalogue must hold to count as a big one
         */
        const CATALOG_MIN_PRODUCTS = 3000;

        /*
         * How many taxonomies of a store are looked at, so a site that registered dozens of
         * them does not turn one banner into dozens of queries
         */
        const TAXONOMY_MAX_CHECKED = 8;

        /*
         * Product filter plugins of other vendors
         *
         * Folder names, matched by their first part, so premium builds ( -pro, -premium ) are
         * covered by the same entry. A store running one of them has a filtering need already,
         * which makes the bundle a better fit than the single plugin.
         */
        static private $filter_plugins = array(
            'woocommerce-products-filter',          // HUSKY ( WOOF )
            'woo-product-filter',                   // WBW
            'filter-everything',
            'woocommerce-ajax-filters',             // BeRocket
            'woocommerce-product-filters',
            'themify-wc-product-filter',
            'wc-ajax-product-filter',               // WCAPF
            'yith-woocommerce-ajax-navigation',      // YITH, 70k installs
            'yith-woocommerce-ajax-product-filter',  // its premium build
            'facetwp',
            'premmerce-woocommerce-product-filter',
            'annasta-woocommerce-product-filters',
            'dynamic-ajax-product-filters-for-woocommerce',
            'pofily-woo-product-filters',
            'ace-products-filters',
            'super-speedy-filters',
            'jet-smart-filters',
            'fibofilters',
            'search-filter',
        );

        /*
         * Product label and badge plugins of other vendors
         *
         * Same idea as the filter list: a store running one of these has told us it wants labels
         * on its products, and ours comes with the bundle. Ordered by how many stores run them,
         * the first four are at ten thousand installs each.
         *
         * Watch the names: nothing here may match print-invoices-packing-slip-labels, which is
         * about shipping paperwork, not product badges.
         */
        static private $label_plugins = array(
            'aco-product-labels-for-woocommerce',      // Product Labels For WooCommerce
            'advanced-product-labels-for-woocommerce',
            'yith-woocommerce-badges-management',
            'yith-woocommerce-badge-management',       // its premium build
            'sold-out-badge-for-woocommerce',
            'easy-sale-badges-for-woocommerce',
            'wpc-badge-management',
            'woo-badge-designer',
            'woocommerce-advanced-product-labels',
            'product-badges-for-woocommerce',
            'product-badge-manager-for-woocommerce',
            'custom-product-badge-for-woocommerce',
            'elegant-labels',
            'qode-badges-for-woocommerce',
        );

        /*
         * Offer of the banner, picked from what the store runs
         *
         * The order is by how much the visitor already told us: our own plugins first ( the
         * bundle is the only thing left to sell them ), then a filtering need proven by another
         * vendor's plugin, then the premium features their integrations and their catalogue ask
         * for. A store that says nothing gets the plain discount.
         *
         * Every banner says two things under its headline: what this store gets out of the
         * offer, and what it costs against the alternative. The discount is named once - in
         * the headline for the single plugin, in the second line for the bundle, where 50%
         * against buying the plugins one by one is the larger number.
         *
         * Everything is read locally and nothing is sent anywhere. The result is worked out on
         * the first load of a plugin page and stored, so it never changes again for this site.
         *
         * @return array type, id for the link, headline reason, second line, call to action
         */
        static public function get_offer() {

            // ?aws_promo=... shows a given variant without touching the store, see preview()
            $offer = self::preview();

            if ( ! $offer ) {

                $offer = get_option( self::OFFER_OPTION );

                if ( ! is_array( $offer ) || ! isset( $offer['reason'] ) || ! isset( $offer['v'] ) || self::OFFER_VERSION !== (int) $offer['v'] ) {

                    $offer = self::choose_offer();
                    $offer['v'] = self::OFFER_VERSION;

                    // not autoloaded: it is read on the plugin's own pages and nowhere else
                    update_option( self::OFFER_OPTION, $offer, false );

                }

            }

            $bundle = $offer['type'] === 'bundle';

            // text is built here and never cached: the admin language can change at any time
            if ( $bundle ) {

                // the headline names the set and nothing else: the bundle sells on 50% against
                // buying the plugins one by one, which is said in the lines below, and putting
                // the smaller 30% above it would only undercut that
                $title = 'Search, filters and product labels';

                // a store that runs another vendor's filtering or labels plugin hears about
                // the conflicts those cause instead of the list of what it is missing: it is
                // the same thought and the sharper half of it. The saving closes every bundle.
                $conflict = self::conflict_text( $offer['reason'] );

                $lines = array(
                    $conflict ? $conflict : self::missing_text( isset( $offer['missing'] ) ? $offer['missing'] : 'none' ),
                    '50% cheaper than buying separately',
                );

            } else {

                // the headline is the same for everyone, the reasons this store was picked for
                // take the lines under it
                $title = 'Get Advanced Woo Search Pro';
                $lines = array();

                // an id that a later release no longer knows gives an empty line, and an
                // empty line must not take one of the two places a fallback could fill
                $first  = $offer['reason'] === 'generic' ? '' : self::reason_text( $offer['reason'], isset( $offer['arg'] ) ? $offer['arg'] : '' );
                $second = empty( $offer['second'] ) ? '' : self::reason_text( $offer['second'], isset( $offer['second_arg'] ) ? $offer['second_arg'] : '' );

                if ( $first ) {
                    $lines[] = $first;
                }

                if ( $second ) {
                    $lines[] = $second;
                }

                // a store that told us little still gets two lines
                foreach ( self::fallback_lines() as $fallback ) {

                    if ( count( $lines ) >= 2 ) {
                        break;
                    }

                    $lines[] = $fallback;

                }

            }

            return array(
                'type'   => $offer['type'],
                'id'     => $offer['type'] . '_' . $offer['reason'],
                'title'  => $title,
                // the discount in front of the headline, for the single plugin only
                'prefix' => $bundle ? '' : '30% off.',
                'lines'  => array_slice( array_filter( $lines ), 0, 2 ),
                'cta'    => $bundle
                    ? 'See the bundle deal'
                    : 'Check the deals',
                // each offer lands on the page that sells what it talks about
                'url'    => $bundle
                    ? 'https://kramakit.com/plugins/bundle/'
                    : 'https://kramakit.com/plugins/advanced-woo-search/',
            );

        }

        /*
         * Variant asked for in the url, for looking at them all without changing the store
         *
         *     ?aws_promo=bundle:own_awl
         *     ?aws_promo=pro:acf
         *     ?aws_promo=pro:acf,vendors          two reasons, the second one goes on line two
         *     ?aws_promo=pro:taxonomies&aws_promo_arg=Collection
         *     ?aws_promo=off                      the banner as a store with nothing to say sees it
         *     ?aws_promo=reset                    forget the hidden banner and the seen dot
         *
         * Bundle reasons: own_both, own_awl, own_filters, filters, labels_plugin, filters_labels.
         * Pro reasons: acf, vendors, brands, multilingual, roles, variations, taxonomies,
         * attributes, catalog.
         *
         * Nothing is written and nothing is cached while previewing, and the date and the
         * "hidden by this user" check are skipped, so a variant can be looked at after the sale
         * as well. Only someone who may open the plugin's pages can do it.
         *
         * @return array|false
         */
        static private function preview() {

            if ( empty( $_GET['aws_promo'] ) ) {
                return false;
            }

            if ( ! current_user_can( AWS_Helpers::user_admin_capability() ) ) {
                return false;
            }

            $asked = sanitize_text_field( wp_unslash( $_GET['aws_promo'] ) );

            if ( $asked === 'off' ) {
                return array( 'type' => 'pro', 'reason' => 'generic' );
            }

            // handled by reset_user_flags(), the banner itself stays the stored one
            if ( $asked === 'reset' ) {
                return false;
            }

            $parts = explode( ':', $asked, 2 );
            $type  = $parts[0] === 'bundle' ? 'bundle' : 'pro';
            $ids   = isset( $parts[1] ) ? explode( ',', $parts[1] ) : array();
            $first = isset( $ids[0] ) ? sanitize_key( $ids[0] ) : '';

            if ( ! $first ) {
                return false;
            }

            if ( 'bundle' === $type ) {

                // the line about what is missing follows the reason, the way the store would
                $missing = array(
                    'own_both'       => 'none',
                    'own_awl'        => 'filters',
                    'own_filters'    => 'labels',
                    'filters'        => 'both',
                    'labels_plugin'  => 'both',
                    'filters_labels' => 'both',
                );

                return array(
                    'type'    => 'bundle',
                    'reason'  => $first,
                    'missing' => isset( $missing[ $first ] ) ? $missing[ $first ] : 'both',
                );

            }

            $offer = array(
                'type'   => 'pro',
                'reason' => $first,
                'arg'    => isset( $_GET['aws_promo_arg'] ) ? sanitize_text_field( wp_unslash( $_GET['aws_promo_arg'] ) ) : '',
            );

            if ( isset( $ids[1] ) ) {
                $offer['second']     = sanitize_key( $ids[1] );
                $offer['second_arg'] = '';
            }

            return $offer;

        }

        /*
         * Lines that fill the banner when the store gave us fewer than two reasons
         * @return array
         */
        static private function fallback_lines() {

            return array(
                // the one argument that holds whatever this store turned out to be
                'And a lot more in the Pro version',
                '30-day money back guarantee',
            );

        }

        /*
         * Headline of a reason
         * @param string $id  Reason id
         * @param string $arg Something of this store the line can name
         * @return string
         */
        static private function reason_text( $id, $arg = '' ) {

            if ( $id === 'taxonomies' && $arg ) {
                return sprintf( 'Search inside your %s taxonomy', $arg );
            }

            $reasons = self::reasons();

            return isset( $reasons[ $id ] ) ? $reasons[ $id ] : '';

        }

        /*
         * Reason lines
         * @return array
         */
        static private function reasons() {

            return array(
                'own_both'     => 'Search, filters and product labels in one bundle',
                'own_awl'      => 'Search, product labels plus product filters',
                'own_filters'  => 'Search, filters and product labels in one bundle',
                'filters'      => 'Search and filters from one team',
                'acf'          => 'Search inside your ACF fields',
                'vendors'      => 'Search inside vendor products',
                'brands'       => 'Let customers search products by brand',
                'multilingual' => 'Search in every language of your store',
                'roles'        => 'Different results for wholesale and members',
                'variations'   => 'Search product variations, not only products',
                'attributes'   => 'Search inside product attributes',
                'taxonomies'   => 'Search inside your custom taxonomies',
                'catalog'      => 'Built for catalogs of thousands of products',
                'generic'      => 'Our only sale of the year.',
            );

        }

        /*
         * What a store running another vendor's plugin hears instead of the missing list
         *
         * Those plugins and the search sit on the same page and fight over it, and the hours go
         * into finding out whose fault it is. That is what the bundle takes away, and it speaks
         * louder to this reader than a list of what they have not bought yet. The line also
         * names the other half of the bundle, which a store with one of these plugins is
         * missing completely.
         *
         * No competitor is named: this is their own admin, and we are guests in it.
         *
         * @param string $reason Why the bundle was picked
         *
         * @return string Empty when the store runs nothing of the sort
         */
        static private function conflict_text( $reason ) {

            // each line answers the plugin they run and names the one they do not have at all:
            // somebody with another vendor's filters is missing labels entirely, and the other
            // way round. Only a store running both already has every category covered.
            $conflicts = array(
                'filters'        => 'Filters that never break your search, plus product labels',
                'labels_plugin'  => 'Labels built to work with search, plus product filtering',
                'filters_labels' => 'One vendor for all three, no plugin conflicts',
            );

            return isset( $conflicts[ $reason ] ) ? $conflicts[ $reason ] : '';

        }

        /*
         * Line naming the plugins of the bundle this store does not have yet
         *
         * The headline sells the set, this one answers "and what do I get that I have not got
         * already", which is the question of someone who arrived here with one of our plugins.
         *
         * @param string $missing What is missing: both, filters, labels or none
         * @return string
         */
        static private function missing_text( $missing ) {

            $lines = array(
                'both'    => 'Additional filtering and labels plugins that work seamlessly',
                'filters' => 'Additional product filtering plugin that works seamlessly',
                'labels'  => 'Additional product labels plugin that works seamlessly',
                'none'    => 'Pro versions of all three plugins, working seamlessly together',
            );

            return isset( $lines[ $missing ] ) ? $lines[ $missing ] : $lines['none'];

        }

        /*
         * Which plugins of the bundle this store is missing
         * @return string both, filters, labels or none
         */
        static private function missing_plugins() {

            $labels  = defined( 'AWL_VERSION' ) || defined( 'AWL_PRO_VERSION' );
            $filters = defined( 'KRAMA_FILTERS_VERSION' ) || defined( 'KRAMA_FILTERS_PRO_VERSION' );

            if ( ! $labels && ! $filters ) {
                return 'both';
            }

            if ( ! $filters ) {
                return 'filters';
            }

            if ( ! $labels ) {
                return 'labels';
            }

            return 'none';

        }

        /*
         * Pick the offer
         * @return array
         */
        static private function choose_offer() {

            // 1. other KramaKit plugins are already installed: only the bundle is left to offer
            $own = self::own_plugins_reason();

            if ( $own ) {
                return array( 'type' => 'bundle', 'reason' => $own, 'missing' => self::missing_plugins() );
            }

            // 2. filtering or labels are handled by another vendor: both come with the bundle
            $has_filters = self::has_plugin_from( self::$filter_plugins );
            $has_labels  = self::has_plugin_from( self::$label_plugins );

            if ( $has_filters || $has_labels ) {

                if ( $has_filters && $has_labels ) {
                    $reason = 'filters_labels';
                } elseif ( $has_filters ) {
                    $reason = 'filters';
                } else {
                    $reason = 'labels_plugin';
                }

                return array( 'type' => 'bundle', 'reason' => $reason, 'missing' => self::missing_plugins() );

            }

            // 3. the premium features this store asks for, strongest first
            $found = self::pro_reasons();

            if ( $found ) {

                $offer = array( 'type' => 'pro', 'reason' => $found[0][0], 'arg' => $found[0][1] );

                if ( isset( $found[1] ) ) {
                    $offer['second']     = $found[1][0];
                    $offer['second_arg'] = $found[1][1];
                }

                return $offer;

            }

            // 4. nothing to go on, the discount alone
            return array( 'type' => 'pro', 'reason' => 'generic' );

        }

        /*
         * Premium features this store asks for, in order of how clearly it asks
         *
         * Two of them are enough for a banner, so the scan stops there: the integrations are
         * constant checks and cost nothing, while the catalogue ones query the database and are
         * only reached when the integrations did not fill both places.
         *
         * @return array Up to two pairs of reason id and the name the line may print
         */
        static private function pro_reasons() {

            $found = array();

            // integrations, same plugins the notices of class-aws-admin-notices.php look for
            if ( class_exists( 'ACF' ) ) {
                $found[] = array( 'acf', '' );
            }

            if ( class_exists( 'WCFMmp' ) || class_exists( 'WeDevs_Dokan' ) || defined( 'WCMp_PLUGIN_VERSION' ) || defined( 'MVX_PLUGIN_VERSION' ) ) {
                $found[] = array( 'vendors', '' );
            }

            if ( defined( 'PWB_PLUGIN_VERSION' ) ) {
                $found[] = array( 'brands', '' );
            }

            if ( defined( 'ICL_SITEPRESS_VERSION' ) || defined( 'POLYLANG_VERSION' ) ) {
                $found[] = array( 'multilingual', '' );
            }

            if ( class_exists( 'WooCommerceWholeSalePrices' ) || class_exists( 'WC_Memberships' ) || class_exists( 'UM_Functions' ) ) {
                $found[] = array( 'roles', '' );
            }

            if ( class_exists( 'Iconic_WSSV' ) || class_exists( 'JCK_WSSV' ) ) {
                $found[] = array( 'variations', '' );
            }

            if ( count( $found ) >= 2 ) {
                return array_slice( $found, 0, 2 );
            }

            // the catalogue, from here on every check is a query
            $taxonomy = self::custom_taxonomy_reason();

            if ( $taxonomy ) {
                $found[] = $taxonomy;
            }

            $found = self::unique_reasons( $found );

            if ( count( $found ) < 2 ) {

                $attributes = function_exists( 'wc_get_attribute_taxonomies' ) ? count( wc_get_attribute_taxonomies() ) : 0;

                if ( $attributes > 3 ) {
                    $found[] = array( 'attributes', '' );
                }

            }

            if ( count( $found ) < 2 && self::has_posts_beyond( 'product', self::CATALOG_MIN_PRODUCTS ) ) {
                $found[] = array( 'catalog', '' );
            }

            if ( count( $found ) < 2 && self::has_posts_beyond( 'product_variation', 0 ) ) {
                $found[] = array( 'variations', '' );
            }

            return array_slice( self::unique_reasons( $found ), 0, 2 );

        }

        /*
         * Drop reasons that were found twice, e.g. brands by plugin and by the size of the
         * taxonomy, keeping the first of them
         *
         * @param array $found Pairs of reason id and name
         * @return array
         */
        static private function unique_reasons( $found ) {

            $seen   = array();
            $unique = array();

            foreach ( $found as $reason ) {

                if ( in_array( $reason[0], $seen, true ) ) {
                    continue;
                }

                $seen[]   = $reason[0];
                $unique[] = $reason;

            }

            return $unique;

        }

        /*
         * Our own plugins running on this store
         * @return string|false Reason id
         */
        static private function own_plugins_reason() {

            $labels  = defined( 'AWL_VERSION' ) || defined( 'AWL_PRO_VERSION' );
            $filters = defined( 'KRAMA_FILTERS_VERSION' ) || defined( 'KRAMA_FILTERS_PRO_VERSION' );

            if ( $labels && $filters ) {
                return 'own_both';
            }

            if ( $labels ) {
                return 'own_awl';
            }

            if ( $filters ) {
                return 'own_filters';
            }

            return false;

        }

        /*
         * Folder a plugin file sits in
         * @param string $plugin Plugin file, e.g. woocommerce/woocommerce.php
         * @return string
         */
        static private function plugin_folder( $plugin ) {

            return current( explode( '/', $plugin ) );

        }

        /*
         * Is one of the listed plugins active
         *
         * Folders are compared by their first part, so a premium build sitting in its own folder
         * ( -pro, -premium ) is caught by the same entry.
         *
         * @param array $list Folder names to look for
         *
         * @return bool
         */
        static private function has_plugin_from( $list ) {

            $active = (array) get_option( 'active_plugins', array() );

            if ( is_multisite() ) {
                $active = array_merge( $active, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
            }

            foreach ( $active as $plugin ) {

                $folder = self::plugin_folder( $plugin );


                foreach ( $list as $known ) {
                    if ( strpos( $folder, $known ) === 0 ) {
                        return true;
                    }
                }

            }

            return false;

        }

        /*
         * Is there a published post of this type past the given position
         *
         * Counting is the expensive way to answer "are there more than N of them": COUNT walks
         * every row of the post type, so a catalogue of two hundred thousand variations is two
         * hundred thousand index entries read to learn a yes or a no. Asking for one row at an
         * offset stops as soon as it is found, so the work is bounded by the threshold and not
         * by the size of the store.
         *
         * @param string $post_type Post type
         * @param int    $offset    How many rows to skip, 0 to ask whether any exists at all
         *
         * @return bool
         */
        static private function has_posts_beyond( $post_type, $offset ) {

            global $wpdb;

            $found = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' LIMIT 1 OFFSET %d",
                $post_type,
                absint( $offset )
            ) );

            return ! empty( $found );

        }

        /*
         * Does this taxonomy hold at least the given number of terms used on products
         *
         * Same reasoning as has_posts_beyond(): the question is "at least N", so one row at an
         * offset answers it without counting a taxonomy that may hold tens of thousands of
         * terms. Only terms in use are considered, since WooCommerce registers its brand
         * taxonomy on every store whether anything was put in it or not.
         *
         * @param string $taxonomy Taxonomy name
         * @param int    $minimum  Terms the taxonomy must hold
         *
         * @return bool
         */
        static private function has_terms( $taxonomy, $minimum ) {

            global $wpdb;

            $found = $wpdb->get_var( $wpdb->prepare(
                "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s AND count > 0 LIMIT 1 OFFSET %d",
                $taxonomy,
                absint( $minimum ) > 0 ? absint( $minimum ) - 1 : 0
            ) );

            return ! empty( $found );

        }

        /*
         * Taxonomies of WooCommerce products that are not WooCommerce's own
         *
         * The free version searches titles, content and excerpts. Anything the store sorted its
         * products with itself - a Collection, a Designer, a Season - is premium territory, and
         * a store that bothered to register one is telling us its catalogue has structure the
         * free search cannot reach.
         *
         * Attribute taxonomies are left out: they have their own reason, and the brand
         * taxonomies of WooCommerce and the brand plugins get the brand line instead, which says
         * more to the reader than "custom taxonomy".
         *
         * Size is what makes this worth saying. A shop with a handful of brands does not need
         * search by brand, and a taxonomy holding three terms is not what makes a catalogue hard
         * to search, so both are counted first: brands from BRANDS_MIN_TERMS, anything else from
         * TAXONOMY_MIN_TERMS. Brands win over the rest, however the store registered them.
         *
         * @return array|false Reason id and the name to print
         */
        static private function custom_taxonomy_reason() {

            if ( ! function_exists( 'get_object_taxonomies' ) ) {
                return false;
            }

            // registered by WooCommerce itself, nothing to sell here
            $core = array(
                'product_cat',
                'product_tag',
                'product_type',
                'product_visibility',
                'product_shipping_class',
            );

            $brands = array(
                'product_brand',
                'product_brands',
                'pwb-brand',
                'yith_product_brand',
                'berocket_brand',
            );

            $candidates = array();

            foreach ( get_object_taxonomies( 'product', 'objects' ) as $slug => $taxonomy ) {

                if ( in_array( $slug, $core, true ) || strpos( $slug, 'pa_' ) === 0 ) {
                    continue;
                }

                // internal taxonomies of other plugins are of no interest to the visitor
                if ( empty( $taxonomy->public ) || empty( $taxonomy->show_ui ) ) {
                    continue;
                }

                $candidates[ $slug ] = isset( $taxonomy->labels->singular_name ) ? $taxonomy->labels->singular_name : $taxonomy->label;

            }

            // brands first and on their own, so they keep their priority without the number of
            // queries depending on where the store registered them
            foreach ( array_keys( $candidates ) as $slug ) {

                if ( ! in_array( $slug, $brands, true ) ) {
                    continue;
                }

                unset( $candidates[ $slug ] );

                if ( self::has_terms( $slug, self::BRANDS_MIN_TERMS ) ) {
                    return array( 'brands', '' );
                }

            }

            $custom  = array();
            $checked = 0;

            foreach ( $candidates as $slug => $name ) {

                if ( $checked >= self::TAXONOMY_MAX_CHECKED ) {
                    break;
                }

                $checked++;

                if ( self::has_terms( $slug, self::TAXONOMY_MIN_TERMS ) ) {
                    $custom[] = $name;
                }

            }

            if ( ! $custom ) {
                return false;
            }

            // one taxonomy gets named, several are spoken of together
            return count( $custom ) === 1 ? array( 'taxonomies', $custom[0] ) : array( 'taxonomies', '' );

        }

        /*
         * Is the sale on at all
         *
         * The plain question, with no user state in it: the strip on the premium page and the
         * badge on the header button are part of the page rather than a notice, so hiding the
         * banner does not hide them.
         *
         * @return bool
         */
        static public function is_running() {

            return time() < self::ENDS;

        }

        /*
         * Badge above a purchase box on the premium page
         *
         * The page already lays the offer out in full: two boxes, a comparison table, the
         * answers to the usual questions. All the sale has to add is the fact that these
         * prices are down right now, said once over each box, which is where the eye already
         * is. A banner of its own would only push the boxes further down the page.
         *
         * @return string
         */
        static public function get_card_badge() {

            if ( ! self::is_running() ) {
                return '';
            }

            return '<span class="aws-promo-deal"><b>Black Friday deal</b> 30% off</span>';

        }

        /*
         * Get banner content
         * @param string $page Plugin admin page name
         * @return string
         */
        static public function get_banner( $page ) {

            if ( ! self::is_active( $page ) ) {
                return '';
            }

            $left = self::ENDS - time();

            // countdown is updated every second by admin script
            $counters = array(
                'days'    => array( floor( $left / DAY_IN_SECONDS ), 'days' ),
                'hours'   => array( floor( $left % DAY_IN_SECONDS / HOUR_IN_SECONDS ), 'hrs' ),
                'minutes' => array( floor( $left % HOUR_IN_SECONDS / MINUTE_IN_SECONDS ), 'min' ),
                'seconds' => array( $left % MINUTE_IN_SECONDS, 'sec' ),
            );

            $offer = self::get_offer();

            $url = add_query_arg( array(
                'utm_source'   => 'plugin',
                'utm_medium'   => 'settings-banner',
                'utm_campaign' => 'aws-black-friday',
                // which rule picked this offer, so the landing page can lead with the same thing
                'utm_content'  => $offer['id'],
            ), $offer['url'] );

            $html = '';

            $html .= '<div id="aws-promo-banner" class="aws-promo-banner" data-ends="' . esc_attr( self::ENDS ) . '">';

                $html .= '<button type="button" class="aws-promo-close" title="Hide banner"><span class="screen-reader-text">Hide banner</span></button>';

                $html .= '<div class="aws-promo-text">';
                    $html .= '<span class="aws-promo-badge">Black Friday</span>';
                    $prefix = $offer['prefix'] ? '<b>' . esc_html( $offer['prefix'] ) . '</b> ' : '';

                    $html .= '<p class="aws-promo-title">' . $prefix . esc_html( $offer['title'] ) . '</p>';
                    $html .= '<ul class="aws-promo-list">';
                        foreach ( $offer['lines'] as $line ) {
                            // a line that opens with a number carries the offer, so the number
                            // is picked out: escaped first, marked up after, never the reverse
                            $line = preg_replace( '/^(\d+%)/', '<b>$1</b>', esc_html( $line ) );
                            $html .= '<li>' . $line . '</li>';
                        }
                    $html .= '</ul>';
                $html .= '</div>';

                $html .= '<div class="aws-promo-actions">';
                    $html .= '<div class="aws-promo-timer" aria-hidden="true">';
                        foreach ( $counters as $name => $counter ) {
                            $html .= '<span class="aws-promo-counter"><b data-countdown="' . esc_attr( $name ) . '">' . ( $name === 'days' ? $counter[0] : sprintf( '%02d', $counter[0] ) ) . '</b><span>' . esc_html( $counter[1] ) . '</span></span>';
                        }
                    $html .= '</div>';
                    $html .= '<a class="aws-promo-cta" href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $offer['cta'] ) . ' &rarr;</a>';
                    $html .= '<p class="aws-promo-note">Sale ends December 2.</p>';
                $html .= '</div>';

            $html .= '</div>';

            return $html;

        }

        /*
         * Forget, for this user, both the hidden banner and the seen menu dot
         *
         * Runs after the dot was drawn for this request, so the two flags are gone by the time
         * the next page loads: open any other admin page and the dot is back, as it is for
         * someone who has never been here. Only the current user is touched, never the stored
         * offer of the site and never another account.
         */
        public function reset_user_flags() {

            if ( empty( $_GET['aws_promo'] ) || 'reset' !== sanitize_text_field( wp_unslash( $_GET['aws_promo'] ) ) ) {
                return;
            }

            if ( ! current_user_can( AWS_Helpers::user_admin_capability() ) ) {
                return;
            }

            $user_id = get_current_user_id();

            if ( ! $user_id ) {
                return;
            }

            delete_user_meta( $user_id, self::META_KEY );
            delete_user_meta( $user_id, self::SEEN_META );

        }

        /*
         * Remember that this user has been on a page with the banner
         */
        public function mark_page_seen() {

            if ( time() >= self::ENDS ) {
                return;
            }

            $page = empty( $_GET['page'] ) ? '' : sanitize_key( wp_unslash( $_GET['page'] ) );

            if ( ! in_array( $page, self::$banner_pages, true ) ) {
                return;
            }

            $user_id = get_current_user_id();

            if ( ! $user_id || get_user_meta( $user_id, self::SEEN_META, true ) ) {
                return;
            }

            update_user_meta( $user_id, self::SEEN_META, 'true' );

        }

        /*
         * Count dot on the plugin menu, until the user has opened the page
         *
         * The banner only lives on the plugin's own pages, so somebody who never opens them
         * never learns about the sale. The dot is the usual way this admin asks for attention,
         * the same one the premium version puts there for licence notices.
         */
        public function add_menu_dot() {

            global $menu;

            if ( time() >= self::ENDS || ! $menu ) {
                return;
            }

            $user_id = get_current_user_id();

            if ( ! $user_id || get_user_meta( $user_id, self::SEEN_META, true ) ) {
                return;
            }

            if ( ! current_user_can( AWS_Helpers::user_admin_capability() ) ) {
                return;
            }

            // no colour of our own: .update-plugins is painted by the admin colour scheme the
            // user picked, the same bubble the Plugins menu carries for pending updates
            $dot = '<span class="aws-promo-menu-dot update-plugins" style="margin-left:5px;"><span class="aws-promo-menu-dot-count">1</span></span>';

            foreach ( $menu as $key => $item ) {

                if ( ! isset( $item[2] ) || 'aws-options' !== $item[2] ) {
                    continue;
                }

                $menu[ $key ][0] = '<div style="position:relative;white-space:nowrap;">' . $menu[ $key ][0] . '<span style="position:absolute;right:4px;">' . $dot . '</span></div>';

            }

        }

        /*
         * Hide banner for current user
         */
        public function hide_banner() {

            check_ajax_referer( 'aws_admin_ajax_nonce' );

            update_user_meta( get_current_user_id(), self::META_KEY, 'true' );

            wp_send_json_success( '1' );

        }

    }

endif;


// nothing here has anything to say on the front end: no object, no hooks
if ( is_admin() ) {
    new AWS_Admin_Black_Friday();
}
