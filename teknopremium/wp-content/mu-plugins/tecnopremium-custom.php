<?php
/*
Plugin Name: Tecnopremium Custom
Description: Custom B2B Checkout fields and Dark Luxury CSS
*/

// 1. Re-add B2B Checkout Fields (CIF/NIF)
add_filter( "woocommerce_checkout_fields" , "tecnopremium_add_nif_checkout_field" );
function tecnopremium_add_nif_checkout_field( $fields ) {
    $fields["billing"]["billing_nif"] = array(
        "type"        => "text",
        "label"       => __("CIF / NIF", "woocommerce"),
        "placeholder" => _x("Introduce tu CIF o NIF", "placeholder", "woocommerce"),
        "required"    => true,
        "class"       => array("form-row-wide"),
        "clear"       => true,
        "priority"    => 35,
    );
    return $fields;
}

add_action( "woocommerce_admin_order_data_after_billing_address", "tecnopremium_nif_checkout_field_display_admin_order_meta", 10, 1 );
function tecnopremium_nif_checkout_field_display_admin_order_meta($order){
    echo "<p><strong>".__("CIF/NIF").":</strong> " . get_post_meta( $order->get_id(), "_billing_nif", true ) . "</p>";
}

add_action( "woocommerce_checkout_update_order_meta", "tecnopremium_nif_checkout_field_update_order_meta" );
function tecnopremium_nif_checkout_field_update_order_meta( $order_id ) {
    if ( ! empty( $_POST["billing_nif"] ) ) {
        update_post_meta( $order_id, "_billing_nif", sanitize_text_field( $_POST["billing_nif"] ) );
    }
}

// 2. Global Styling (Cart, Checkout, My Account)
add_action("wp_head", function() {
    echo "<style>

        


        /* Force Elementor Posts Widget Images to be Square (1:1) */
        .elementor-widget-posts .elementor-post__thumbnail {
            padding-bottom: 100% !important; /* 1:1 Ratio */
            height: 0 !important;
            position: relative !important;
            overflow: hidden !important;
        }
        .elementor-widget-posts .elementor-post__thumbnail img {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }


        /* Global Fix for Elementor 100vw Horizontal Scroll Bug */
        html, body {
            overflow-x: hidden !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        /* Cart & Checkout Dark Luxury */
        .woocommerce { color: #fff !important; }
        .woocommerce table.shop_table {
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 8px !important;
            background: rgba(20, 19, 23, 0.9) !important;
        }
        .woocommerce table.shop_table th {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #EABB61 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        .woocommerce table.shop_table td {
            border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
            color: #ccc !important;
        }
        .woocommerce-cart .cart-collaterals .cart_totals {
            background: rgba(20, 19, 23, 0.9) !important;
            border: 1px solid rgba(234, 187, 97, 0.2) !important;
            border-radius: 8px !important;
            padding: 20px !important;
        }
        .woocommerce-cart .cart-collaterals .cart_totals h2 {
            color: #EABB61 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding-bottom: 10px !important;
        }
        .woocommerce a.remove { color: #ff4a4a !important; }
        .woocommerce button.button, .woocommerce a.button {
            background: linear-gradient(135deg, #EABB61 0%, #C89442 100%) !important;
            color: #111 !important;
            border: none !important;
            border-radius: 4px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
        }
        .woocommerce form .form-row input.input-text, .woocommerce form .form-row textarea {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            border-radius: 4px !important;
            padding: 10px !important;
        }
        #customer_details h3, #order_review_heading {
            color: #EABB61 !important;
            margin-top: 20px !important;
        }
        #order_review {
            background: rgba(20, 19, 23, 0.9) !important;
            border: 1px solid rgba(234, 187, 97, 0.2) !important;
            padding: 20px !important;
            border-radius: 8px !important;
        }
        .woocommerce-checkout #payment {
            background: rgba(255, 255, 255, 0.02) !important;
            border-radius: 5px !important;
        }
        .woocommerce-checkout #payment div.payment_box {
            background: rgba(0, 0, 0, 0.5) !important;
            color: #aaa !important;
        }

        /* Bulletproof Login/Register Layout for My Account */

        /* Fix CSS Grid Staggering caused by WooCommerce Clearfix Pseudo-elements */
        #customer_login::before,
        #customer_login::after {
            display: none !important;
        }
        
        /* Force Flexbox with ultra-high specificity to override Snippet 5 */
        body #customer_login.u-columns.col2-set {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: flex-start !important;
            gap: 40px !important;
            width: 100% !important;
        }
        body #customer_login.u-columns.col2-set .u-column1,
        body #customer_login.u-columns.col2-set .u-column2 {
            width: 48% !important;
            max-width: 48% !important;
            float: none !important;
            box-sizing: border-box !important;
            margin: 0 !important;
        }
        @media (max-width: 768px) {
            body #customer_login.u-columns.col2-set {
                flex-direction: column !important;
                gap: 30px !important;
            }
            body #customer_login.u-columns.col2-set .u-column1,
            body #customer_login.u-columns.col2-set .u-column2 {
                width: 100% !important;
                max-width: 100% !important;
            }
        }

    </style>";
});
