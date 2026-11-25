<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Admin_Page_Premium' ) ) :

    /**
     * Class for plugin admin ajax hooks
     */
    class AWS_Admin_Page_Premium {

        /*
         * Constructor
         */
        public function __construct() {
            
            $this->generate_content();

        }

        /*
         * Generate options fields
         */
        private function generate_content() {

            echo '<div class="links">';
                echo  '<span class="links-title">' . __( 'Website Links:', 'advanced-woo-search' ) . '</span>';
                echo '<ul>';
                    echo '<li><a target="_blank" href="https://kramakit.com/plugins/advanced-woo-search/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=aws-pro-plugin">' . __( 'Plugin home page', 'advanced-woo-search' ) . '</a></li>';
                    echo '<li><a target="_blank" href="https://kramakit.com/plugins/advanced-woo-search/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=aws-pro-plugin">' . __( 'Features', 'advanced-woo-search' ) . '</a></li>';
                    echo '<li><a target="_blank" href="https://kramakit.com/guide-category/aws/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=aws-pro-plugin">' . __( 'Documentation', 'advanced-woo-search' ) . '</a></li>';
                    echo '<li><a target="_blank" href="https://kramakit.com/plugins/advanced-woo-search/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=aws-pro-plugin">' . __( 'Pricing', 'advanced-woo-search' ) . '</a></li>';
                echo '</ul>';
            echo '</div>';

            $promo_deal = class_exists( 'AWS_Admin_Black_Friday' ) ? AWS_Admin_Black_Friday::get_card_badge() : '';

            echo '<div class="buy-premium-row">';

                echo '<div class="buy-premium">';
                    echo '<a target="_blank" href="https://kramakit.com/plugins/advanced-woo-search/?utm_source=plugin&utm_medium=settings-tab&utm_campaign=aws-pro-plugin">';
                        echo $promo_deal;
                        echo '<span class="desc">' . __( 'Upgrade to the', 'advanced-woo-search' ) . '<b> ' . __( 'Premium plugin version', 'advanced-woo-search' ) . '</b><br>' . __( 'to have all available features!', 'advanced-woo-search' ) . '</span>';
                        echo '<ul>';
                            echo '<li>' . __( '30-day money back guarantee', 'advanced-woo-search' ) . '</li>';
                            echo '<li>' . __( 'Priority support', 'advanced-woo-search' ) . '</li>';
                        echo '</ul>';
                    echo '</a>';
                echo '</div>';

                echo '<div class="buy-premium buy-bundle">';
                    echo '<a href="https://kramakit.com/plugins/bundle/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=aws-pro-plugin" target="_blank">';
                        echo $promo_deal;
                        echo '<span class="desc">' . esc_html__( 'OR get the', 'advanced-woo-search' ) . '<b> ' . esc_html__( 'KramaKit Bundle', 'advanced-woo-search' ) . '</b><br>' . esc_html__( 'and save 30% on all our plugins!', 'advanced-woo-search' ) . '</span>';
                        echo '<ul>';
                            echo '<li>' . esc_html__( 'All 3 plugins', 'advanced-woo-search' ) . '</li>';
                            echo '<li>' . esc_html__( 'All future plugins', 'advanced-woo-search' ) . '</li>';
                        echo '</ul>';
                    echo '</a>';
                echo '</div>';

            echo '</div>';

            echo '<div class="compare">';

                echo '<h3>' . esc_html__( 'Free vs. Pro', 'advanced-woo-search' ) . '</h3>';
                echo '<p class="compare-desc">' . esc_html__( 'Everything in the free version, plus powerful Pro features for growing stores.', 'advanced-woo-search' ) . '</p>';

                $features = array(
                    array(
                        'name' => __( 'Search for an unlimited number of products', 'advanced-woo-search' ),
                        'tip'  => __( 'There are no limits in the number of products that you can search for.', 'advanced-woo-search' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Search by title, content, SKU, category, tag, ID', 'advanced-woo-search' ),
                        'tip'  => __( 'Search for WooCommerce products by title, content, SKU, category, tag and ID.', 'advanced-woo-search' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Categories & tags pages search', 'advanced-woo-search' ),
                        'tip'  => __( 'Search and display categories and tags archive pages.', 'advanced-woo-search' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Variable products search', 'advanced-woo-search' ),
                        'tip'  => __( 'Search and display WooCommerce variable products. Search for any child product to display its parent one.', 'advanced-woo-search' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Multilingual support', 'advanced-woo-search' ),
                        'tip'  => __( 'Support for multilingual plugins like WPML, Polylang, qTranslate, WooCommerce Multilingual. Users will see only search results that are related to currently active language.', 'advanced-woo-search' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Synonyms support', 'advanced-woo-search' ),
                        'tip'  => __( 'Option to set synonyms words. Those words will be equal during the search.', 'advanced-woo-search' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Search results page', 'advanced-woo-search' ),
                        'tip'  => __( 'Display the plugin search results in both ways: inside ajax live results block and on a separate search results page. This page will inherit all your current theme styles and layout.', 'advanced-woo-search' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Search by product attributes', 'advanced-woo-search' ),
                        'tip'  => __( 'Search for WooCommerce products by their attributes values.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Search by product taxonomies', 'advanced-woo-search' ),
                        'tip'  => __( 'Search for all available custom taxonomies associated with your WooCommerce products. Choose to search for all of them or only for some. For example - search for products by Brand taxonomy value.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Search by product custom fields', 'advanced-woo-search' ),
                        'tip'  => __( 'Search for WooCommerce products by their custom fields values. You can specify individually what exact custom field must be available for search. It is also possible to search for custom fields created by other plugins ( e.g. Advanced Custom Fields plugin ).', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Product variations search & display', 'advanced-woo-search' ),
                        'tip'  => __( 'Search for WooCommerce product variations. Display each variation separately inside the plugin search results list.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'GTIN, UPC, EAN, or ISBN search & display', 'advanced-woo-search' ),
                        'tip'  => __( 'Search for WooCommerce products by their GTIN, UPC, EAN, or ISBN unique numbers. Display these values inside the plugin search results list.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Product Brands search & display', 'advanced-woo-search' ),
                        'tip'  => __( 'Search for WooCommerce products by their brands names. Display these brands inside the plugin search results list.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Attributes, taxonomies archive pages search', 'advanced-woo-search' ),
                        'tip'  => __( 'Search and display attributes and taxonomies archive pages.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Search suggestions', 'advanced-woo-search' ),
                        'tip'  => __( 'Show search suggestions ( search autocomplete, search recommendations ). They are automatically generated depending on current search query and existing store products.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Users search & display', 'advanced-woo-search' ),
                        'tip'  => __( 'Search for any user of your website. Display them inside the plugin search results list. Search for all users or only for certain user roles.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Search form instances', 'advanced-woo-search' ),
                        'tip'  => __( 'Create an unlimited number of search form instances. Each instance is a fully independent search form with its own unique set of settings.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Search form filtering buttons', 'advanced-woo-search' ),
                        'tip'  => __( 'Create and display a special filter button for each of your search forms. Such a button gives the option to switch between several pre-defined search settings. For example - create different filters based on product categories.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Search results filters', 'advanced-woo-search' ),
                        'tip'  => __( 'Exclude or include certain products, archive pages or users search results based on different parameters.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Search results layouts', 'advanced-woo-search' ),
                        'tip'  => __( 'Choose between several pre-defined search results layouts. Select from the following layouts: inline, grid, large grid.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Search form layouts', 'advanced-woo-search' ),
                        'tip'  => __( 'Choose from different search form layouts – with or without search button or search filters button. Also you can align them to the left or right side of the search form.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'AND search logic', 'advanced-woo-search' ),
                        'tip'  => __( 'Choose between AND or OR search logic. AND logic will show only products that contain all search words from the query.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( '"Add to Cart" button', 'advanced-woo-search' ),
                        'tip'  => __( 'Show the \'Add to Cart\' button right inside plugin search results for every product. Users will get the option to add products to their cart without leaving the current page.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Customize search results view', 'advanced-woo-search' ),
                        'tip'  => __( 'Show/hide product title, description, tags, categories, attributes, SKU, ID, custom fields and taxonomies.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Visibility & stock status options', 'advanced-woo-search' ),
                        'tip'  => __( 'Choose what product visibility ( catalog/search, catalog, search or hidden ) and stock status ( in-stock, out-of-stock or both ) products must have to appear in search results.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Image source options', 'advanced-woo-search' ),
                        'tip'  => __( 'Set image source for search results: featured image, gallery, product content, product short description and set default image if there are no other images.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Integration with 30+ plugins', 'advanced-woo-search' ),
                        'tip'  => __( 'Advanced integration with such plugins like Advanced Custom Fields, WCFM, Dokan, WPML, MultiVendorX, WC Vendors and many more.', 'advanced-woo-search' ),
                        'free' => false,
                    ),
                );

                $check = '<span class="check"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></span>';
                $dash = '<span class="dash">-</span>';

                echo '<table class="compare-table">';
                    echo '<thead>';
                        echo '<tr>';
                            echo '<th>' . esc_html__( 'Feature', 'advanced-woo-search' ) . '</th>';
                            echo '<th>' . esc_html__( 'Free', 'advanced-woo-search' ) . '</th>';
                            echo '<th class="pro-col">' . esc_html__( 'Pro', 'advanced-woo-search' ) . '</th>';
                        echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                        foreach ( $features as $feature ) {
                            echo '<tr>';
                                echo '<td><span class="feat">' . esc_html( $feature['name'] ) . '<span class="feat-i" aria-hidden="true"></span><span class="feat-tip">' . esc_html( $feature['tip'] ) . '</span></span></td>';
                                echo '<td>' . ( $feature['free'] ? $check : $dash ) . '</td>';
                                echo '<td>' . $check . '</td>';
                            echo '</tr>';
                        }
                    echo '</tbody>';
                echo '</table>';

                echo '<div class="compare-ctas">';
                    echo '<a class="compare-learn" href="https://kramakit.com/plugins/advanced-woo-search/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=aws-pro-plugin" target="_blank">' . esc_html__( 'Learn more', 'advanced-woo-search' ) . '</a>';
                    echo '<a class="compare-buy" href="https://kramakit.com/plugins/advanced-woo-search/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=aws-pro-plugin#pricing" target="_blank">' . esc_html__( 'Get Pro Version', 'advanced-woo-search' ) . ' &rarr;</a>';
                echo '</div>';

                echo '<p class="compare-guarantee">';
                    echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
                    echo esc_html__( '30-day money-back guarantee, no questions asked', 'advanced-woo-search' );
                echo '</p>';

            echo '</div>';

            echo '<div class="faq">';

                echo '<h3>' . __( 'Frequently Asked Questions', 'advanced-woo-search' ) . '</h3>';

                echo '<div class="faq-item">';
                    echo '<h4 class="question">';
                        echo __( 'Do you offer refunds?', 'advanced-woo-search' );
                    echo '</h4>';
                    echo '<div class="answer">';
                        echo __( 'If you\'re not completely happy with your purchase and we\'re unable to resolve the issue, let us know and we\'ll refund the full purchase price. Refunds can be processed within 30 days of the original purchase.', 'advanced-woo-search' );
                    echo '</div>';
                echo '</div>';

                echo '<div class="faq-item">';
                    echo '<h4 class="question">';
                        echo __( 'What payment methods do you accept?', 'advanced-woo-search' );
                    echo '</h4>';
                    echo '<div class="answer">';
                        echo __( 'Checkout is powered by FastSpring. It supports major credit and debit cards, PayPal, and a variety of other mainstream payment methods, so there’s plenty to pick from.', 'advanced-woo-search' );
                    echo '</div>';
                echo '</div>';

                echo '<div class="faq-item">';
                    echo '<h4 class="question">';
                        echo __( 'Do you offer support if I need help?', 'advanced-woo-search' );
                    echo '</h4>';
                    echo '<div class="answer">';
                        echo __( 'Yes! You will benefit of our full support for any issues you have with this plugin.', 'advanced-woo-search' );
                    echo '</div>';
                echo '</div>';

                echo '<div class="faq-item">';
                    echo '<h4 class="question">';
                        echo __( 'I have other pre-sale questions, can you help?', 'advanced-woo-search' );
                    echo '</h4>';
                    echo '<div class="answer">';
                        echo __( 'Yes! You can ask us any question through our', 'advanced-woo-search' ) . ' <a href="https://kramakit.com/contact/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=aws-pro-plugin" target="_blank">' . __( 'contact form.', 'advanced-woo-search' ) . '</a>';
                    echo '</div>';
                echo '</div>';

            echo '</div>';

            echo '<div class="buy-premium">';
                echo '<a target="_blank" href="https://kramakit.com/plugins/advanced-woo-search/?utm_source=plugin&utm_medium=settings-tab&utm_campaign=aws-pro-plugin">';
                    echo '<span class="desc">' . __( 'Upgrade to the', 'advanced-woo-search' ) . '<b> ' . __( 'Premium plugin version', 'advanced-woo-search' ) . '</b><br>' . __( 'to have all available features!', 'advanced-woo-search' ) . '</span>';
                echo '</a>';
            echo '</div>';

        }
        
    }

endif;
