<?php
/**
 * Plugin Name: Tecnopremium Master Production Suite
 * Description: Sistema integral de diseño Luxury Dark-Mode, Ficha Técnica VIP, Grid Equalizer de Tienda y Maquetación B2B.
 * Version: 2.0.0
 * Author: Tecnopremium Engineering
 */

if (!defined('ABSPATH')) {
    exit;
}

// Evitamos duplicidad si el snippet de Code Snippets está ejecutándose
if (!defined('TECNOPREMIUM_MASTER_ACTIVE')) {
    define('TECNOPREMIUM_MASTER_ACTIVE', true);

/**
 * TECNOPREMIUM: Código Maestro para Tienda, Categorías, Producto Individual y ACF
 */

add_action('wp_head', function() {
    if (is_woocommerce() || is_shop() || is_product_category() || is_product() || is_archive()) {
        ?>
        <style>
            /* Fondo y Tipografía Global */
            .woocommerce, .woocommerce-page {
                background-color: #27262B !important;
                color: #FFFFFF !important;
            }

            /* ==============================================
               PÁGINA DE PRODUCTO INDIVIDUAL (SINGLE PRODUCT)
               ============================================== */
            /* Galería de Imagen Principal */
            .woocommerce-product-gallery {
                background: #1C1B1E !important;
                border: 1px solid rgba(212, 203, 146, 0.25) !important;
                border-radius: 12px !important;
                padding: 16px !important;
                box-shadow: 0 16px 36px rgba(0, 0, 0, 0.55) !important;
            }
            .woocommerce-product-gallery__image img {
                border-radius: 8px !important;
                width: 100% !important;
                height: auto !important;
            }
            .woocommerce-product-gallery .flex-control-thumbs {
                margin-top: 16px !important;
                display: flex !important;
                gap: 12px !important;
            }
            .woocommerce-product-gallery .flex-control-thumbs li img {
                border-radius: 6px !important;
                border: 1px solid rgba(255, 255, 255, 0.15) !important;
                transition: all 0.3s ease !important;
            }
            .woocommerce-product-gallery .flex-control-thumbs li img:hover,
            .woocommerce-product-gallery .flex-control-thumbs li img.flex-active {
                border-color: #EABB61 !important;
                transform: scale(1.05) !important;
            }

            /* Título y Precio Principal */
            .woocommerce-div .product_title,
            h1.product_title.entry-title {
                font-size: 2.2rem !important;
                font-weight: 700 !important;
                line-height: 1.25 !important;
                color: #FFFFFF !important;
                margin-bottom: 14px !important;
            }

            .woocommerce-div p.price,
            .summary p.price {
                font-size: 1.85rem !important;
                font-weight: 700 !important;
                color: #EABB61 !important;
                margin-bottom: 24px !important;
            }

            /* Selector de Cantidad y Botón Añadir al Carrito */
            form.cart {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 16px !important;
                align-items: center !important;
                margin: 24px 0 !important;
            }
            form.cart .quantity input.qty {
                background: #1C1B1E !important;
                border: 1px solid #EABB61 !important;
                color: #FFFFFF !important;
                font-size: 1.1rem !important;
                font-weight: 600 !important;
                border-radius: 6px !important;
                padding: 12px 16px !important;
                width: 80px !important;
                text-align: center !important;
            }
            form.cart button.single_add_to_cart_button {
                background: linear-gradient(135deg, #EABB61 0%, #dca943 100%) !important;
                color: #27262B !important;
                font-size: 0.98rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
                padding: 15px 34px !important;
                border-radius: 6px !important;
                border: none !important;
                cursor: pointer !important;
                transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
                box-shadow: 0 4px 15px rgba(212, 203, 146, 0.25) !important;
            }
            form.cart button.single_add_to_cart_button:hover {
                background: #FFFFFF !important;
                color: #27262B !important;
                box-shadow: 0 6px 24px rgba(212, 203, 146, 0.45) !important;
                transform: translateY(-2px) !important;
            }

            /* Pestañas de Información (Tabs) */
            .woocommerce-tabs ul.tabs {
                border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
                padding: 0 !important;
                margin: 30px 0 20px 0 !important;
                display: flex !important;
                gap: 10px !important;
            }
            .woocommerce-tabs ul.tabs li {
                background: #1C1B1E !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 6px 6px 0 0 !important;
                margin: 0 !important;
                padding: 12px 22px !important;
            }
            .woocommerce-tabs ul.tabs li a {
                color: #FFFFFF !important;
                font-weight: 600 !important;
                text-decoration: none !important;
                font-size: 0.98rem !important;
            }
            .woocommerce-tabs ul.tabs li.active {
                background: rgba(212, 203, 146, 0.12) !important;
                border-color: #EABB61 !important;
                border-bottom-color: transparent !important;
            }
            .woocommerce-tabs ul.tabs li.active a {
                color: #EABB61 !important;
            }
            .woocommerce-Tabs-panel {
                background: #1C1B1E !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 8px !important;
                padding: 24px 28px !important;
                line-height: 1.7 !important;
                color: #E2E2E2 !important;
            }

            /* Ficha Técnica ACF Premium en Single Product */
            .tecnopremium-ficha-tecnica {
                background: rgba(212, 203, 146, 0.06);
                border: 1px solid rgba(212, 203, 146, 0.25);
                border-left: 4px solid #EABB61;
                padding: 20px 24px;
                margin: 24px 0;
                border-radius: 8px;
            }
            .tecnopremium-ficha-tecnica h4 {
                color: #EABB61;
                margin: 0 0 14px 0;
                font-size: 1.05rem;
                text-transform: uppercase;
                letter-spacing: 1.2px;
            }
            .tecnopremium-ficha-tecnica ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .tecnopremium-ficha-tecnica li {
                margin-bottom: 8px;
                color: #E2E2E2;
                font-size: 0.98rem;
                display: flex;
                justify-content: space-between;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
                padding-bottom: 6px;
            }
            .tecnopremium-ficha-tecnica li strong {
                color: #FFFFFF;
            }
            .tecnopremium-b2b-notice {
                background: #1C1B1E;
                border: 1px dashed #EABB61;
                border-radius: 8px;
                padding: 16px 20px;
                margin: 20px 0;
                font-size: 0.94rem;
                color: #E2E2E2;
                line-height: 1.5;
            }
            .tecnopremium-b2b-notice strong {
                color: #EABB61;
            }

            /* ==============================================
               CATEGORÍAS EN SIDEBAR: 2 COLUMNAS Y TARJETAS COMPACTAS
               ============================================== */
            .elementor-widget-wc-categories ul.products,
            .wc-categories ul.products,
            ul.products.columns-2 {
                /* Eliminado por Antigravity: display: grid y grid-template-columns forzados */
                gap: 12px !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Estilos de Categoría con fondo #131315 */
            .elementor-widget-wc-categories ul.products li.product-category,
            .wc-categories ul.products li.product-category,
            .woocommerce ul.products li.product-category {
                background: #131315 !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 10px !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                transition: all 0.3s ease !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                overflow: hidden !important;
            }

            .elementor-widget-wc-categories ul.products li.product-category a,
            .wc-categories ul.products li.product-category a,
            .woocommerce ul.products li.product-category a {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-decoration: none !important;
                width: 100% !important;
            }

            .elementor-widget-wc-categories ul.products li.product-category img,
            .wc-categories ul.products li.product-category img,
            .woocommerce ul.products li.product-category img {
                width: 100% !important;
                aspect-ratio: 1 / 1 !important;
                object-fit: cover !important;
                border-radius: 10px 10px 0 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            .elementor-widget-wc-categories ul.products li.product-category h2.woocommerce-loop-category__title,
            .wc-categories ul.products li.product-category h2.woocommerce-loop-category__title,
            .woocommerce ul.products li.product-category h2.woocommerce-loop-category__title {
                color: #FFFFFF !important;
                font-size: 0.9rem !important;
                font-weight: 600 !important;
                text-align: center !important;
                line-height: 1.3 !important;
                margin: 12px 10px 4px 10px !important;
                padding: 0 !important;
            }

            .elementor-widget-wc-categories ul.products li.product-category mark.count,
            .wc-categories ul.products li.product-category mark.count,
            .woocommerce ul.products li.product-category mark.count {
                background: #131315 !important;
                color: #EABB61 !important;
                font-weight: 500 !important;
                font-size: 0.8rem !important;
                margin-bottom: 12px !important;
            }
            
            .elementor-widget-wc-categories ul.products li.product-category:hover,
            .wc-categories ul.products li.product-category:hover,
            .woocommerce ul.products li.product-category:hover {
                border-color: #EABB61 !important;
                transform: translateY(-4px) !important;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
            }
            
            /* ==============================================
               GRID PRINCIPAL DE PRODUCTOS WOOCOMMERCE
               ============================================== */
            .woocommerce ul.products:not(.columns-2), .woocommerce-page ul.products:not(.columns-2) {
                /* Eliminado por Antigravity para devolver control a Elementor */
                grid-gap: 30px !important;
                align-items: stretch !important;
            }
            
            .woocommerce ul.products li.product:not(.product-category), .woocommerce-page ul.products li.product:not(.product-category) {
                display: flex !important;
                flex-direction: column !important;
                background: #1F1E23 !important;
                border: 1px solid rgba(255, 255, 255, 0.09) !important;
                border-radius: 12px !important;
                padding: 15px !important;
                margin: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease !important;
                align-items: center !important; /* Centra el contenido secundario horizontalmente */
            }
            
            .woocommerce ul.products li.product:not(.product-category):hover {
                border-color: #EABB61 !important;
                transform: translateY(-5px) !important;
                box-shadow: 0 12px 28px rgba(0, 0, 0, 0.5) !important;
            }
            
            .woocommerce ul.products li.product:not(.product-category) a.woocommerce-LoopProduct-link {
                display: flex !important;
                flex-direction: column !important;
                flex-grow: 1 !important;
                text-decoration: none !important;
                color: inherit !important;
            }
            
            .woocommerce ul.products li.product:not(.product-category) a.woocommerce-LoopProduct-link img {
                width: 100% !important;
                height: 250px !important; /* Altura fija para no ser tan larga */
                object-fit: cover !important;
                object-position: center !important;
                border-radius: 8px !important;
                margin: 0 0 16px 0 !important;
                display: block !important;
                background: #161519 !important;
                transition: transform 0.5s ease !important;
            }
            
            .woocommerce ul.products li.product:not(.product-category) .woocommerce-loop-product__title {
                color: #FFFFFF !important;
                font-size: 0.95rem !important;
                font-weight: 600 !important;
                text-align: center !important;
                line-height: 1.25 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 0 8px 0 !important;
                padding: 0 4px !important;
            }
            
            .woocommerce ul.products li.product:not(.product-category) .price {
                color: #EABB61 !important;
                font-weight: 700 !important;
                font-size: 1.1rem !important;
                text-align: center !important;
                margin: 0 0 12px 0 !important;
                display: block !important;
            }
            
            .woocommerce ul.products li.product:not(.product-category) .button {
                margin: 15px auto 0 auto !important; /* Centrado con margen automático y elimina el auto gap superior gigante */
                display: inline-block !important;
                text-align: center !important;
                background: rgba(212, 203, 146, 0.12) !important;
                border: 1px solid #EABB61 !important;
                color: #EABB61 !important;
                font-weight: 600 !important;
                font-size: 0.75rem !important;
                border-radius: 6px !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                padding: 10px 24px !important; /* Padding horizontal ajustado */
                box-sizing: border-box !important;
                transition: all 0.3s ease !important;
            }
            
            .woocommerce ul.products li.product:not(.product-category) .button:hover {
                background: #EABB61 !important;
                color: #27262B !important;
                box-shadow: 0 4px 14px rgba(212, 203, 146, 0.35) !important;
            }
        
            /* ==============================================
               ESTILOS PREMIUM PARA BLOG & SINGLE POST
               ============================================== */
            /* Single Post Contenido */
            .single-post .elementor-widget-theme-post-content,
            .single-post .entry-content {
                font-size: 1.1rem !important;
                line-height: 1.85 !important;
                color: #E2E2E2 !important;
            }
            .single-post .lead {
                font-size: 1.25rem !important;
                font-weight: 400 !important;
                color: #FFFFFF !important;
                border-bottom: 1px solid rgba(212, 203, 146, 0.2) !important;
                padding-bottom: 20px !important;
                margin-bottom: 28px !important;
            }
            .single-post h3 {
                color: #EABB61 !important;
                font-size: 1.4rem !important;
                font-weight: 700 !important;
                margin: 36px 0 16px 0 !important;
                letter-spacing: 0.5px !important;
            }
            .single-post ul {
                padding-left: 20px !important;
                margin-bottom: 24px !important;
            }
            .single-post ul li {
                margin-bottom: 12px !important;
                line-height: 1.7 !important;
            }
            .single-post ul li strong {
                color: #FFFFFF !important;
            }
            /* Sección de Comentarios en Single Post */
            #comments, .comment-respond {
                background: #1C1B1E !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 12px !important;
                padding: 30px !important;
                margin-top: 40px !important;
            }
            #comments h3, .comment-respond h3 {
                color: #FFFFFF !important;
                font-size: 1.4rem !important;
                margin-bottom: 20px !important;
            }
            #comments textarea, #comments input[type="text"], #comments input[type="email"] {
                background: #151417 !important;
                border: 1px solid rgba(212, 203, 146, 0.3) !important;
                color: #FFFFFF !important;
                border-radius: 6px !important;
                padding: 12px !important;
                width: 100% !important;
            }
            #comments input[type="submit"] {
                background: linear-gradient(135deg, #EABB61 0%, #dca943 100%) !important;
                color: #27262B !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                padding: 14px 30px !important;
                border-radius: 6px !important;
                border: none !important;
                cursor: pointer !important;
                transition: all 0.3s ease !important;
            }
            #comments input[type="submit"]:hover {
                background: #FFFFFF !important;
                box-shadow: 0 6px 20px rgba(212, 203, 146, 0.45) !important;
            }
        
            /* =========================================================
               MASTER WOOCOMMERCE CORE LUXURY DARK-MODE CSS
               (CARRITO, CHECKOUT, GRACIAS, MI CUENTA & AVISOS)
               ========================================================= */

            /* --- 1. Contenedores y Estructura Base --- */
            .woocommerce-cart .entry-content,
            .woocommerce-checkout .entry-content,
            .woocommerce-account .entry-content {
                max-width: 1240px !important;
                margin: 40px auto !important;
                padding: 0 20px !important;
                color: #FFFFFF !important;
            }

            /* --- 2. Avisos y Mensajes de WooCommerce (Notices / Alerts) --- */
            .woocommerce-message,
            .woocommerce-info,
            .woocommerce-error {
                background: #1C1B1E !important;
                border: 1px solid rgba(212, 203, 146, 0.4) !important;
                border-left: 5px solid #EABB61 !important;
                border-radius: 8px !important;
                color: #FFFFFF !important;
                padding: 16px 24px !important;
                margin-bottom: 28px !important;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45) !important;
            }
            .woocommerce-error {
                border-left-color: #E74C3C !important;
            }
            .woocommerce-message .button,
            .woocommerce-info .button {
                background: #EABB61 !important;
                color: #27262B !important;
                font-weight: 700 !important;
                border-radius: 6px !important;
                padding: 8px 16px !important;
            }

            /* --- 3. Carrito de Compra (Cart Table & Totals) --- */
            .woocommerce-cart table.shop_table {
                background: #1C1B1E !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 12px !important;
                overflow: hidden !important;
                width: 100% !important;
                border-collapse: separate !important;
                border-spacing: 0 !important;
                box-shadow: 0 16px 36px rgba(0, 0, 0, 0.5) !important;
                margin-bottom: 34px !important;
            }
            .woocommerce-cart table.shop_table th {
                background: #151417 !important;
                color: #EABB61 !important;
                font-size: 0.88rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
                padding: 18px 20px !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
            }
            .woocommerce-cart table.shop_table td {
                padding: 20px !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
                color: #FFFFFF !important;
                vertical-align: middle !important;
            }
            .woocommerce-cart table.shop_table td.product-thumbnail img {
                width: 76px !important;
                height: 76px !important;
                object-fit: cover !important;
                border-radius: 8px !important;
                border: 1px solid rgba(255, 255, 255, 0.15) !important;
            }
            .woocommerce-cart table.shop_table td.product-name a {
                color: #FFFFFF !important;
                font-weight: 600 !important;
                font-size: 1.05rem !important;
                text-decoration: none !important;
            }
            .woocommerce-cart table.shop_table td.product-name a:hover {
                color: #EABB61 !important;
            }
            .woocommerce-cart table.shop_table td.product-price,
            .woocommerce-cart table.shop_table td.product-subtotal {
                color: #EABB61 !important;
                font-weight: 700 !important;
                font-size: 1.1rem !important;
            }
            .woocommerce-cart table.shop_table a.remove {
                color: #FFFFFF !important;
                background: rgba(231, 76, 60, 0.2) !important;
                border-radius: 50% !important;
                width: 28px !important;
                height: 28px !important;
                line-height: 28px !important;
                display: inline-block !important;
                text-align: center !important;
                font-weight: 700 !important;
                transition: all 0.25s ease !important;
            }
            .woocommerce-cart table.shop_table a.remove:hover {
                background: #E74C3C !important;
                color: #FFFFFF !important;
                transform: scale(1.1) !important;
            }

            /* Cupones y Acciones del Carrito */
            .woocommerce-cart .actions {
                padding: 20px !important;
                background: #18171B !important;
            }
            .woocommerce-cart .actions .coupon {
                display: flex !important;
                gap: 12px !important;
                align-items: center !important;
            }
            .woocommerce-cart .actions input#coupon_code {
                background: #121114 !important;
                border: 1px solid rgba(212, 203, 146, 0.4) !important;
                color: #FFFFFF !important;
                border-radius: 6px !important;
                padding: 12px 18px !important;
                height: 44px !important;
            }
            .woocommerce-cart .actions button.button {
                background: rgba(212, 203, 146, 0.15) !important;
                border: 1px solid #EABB61 !important;
                color: #EABB61 !important;
                font-weight: 600 !important;
                text-transform: uppercase !important;
                padding: 12px 22px !important;
                border-radius: 6px !important;
                cursor: pointer !important;
                height: 44px !important;
                transition: all 0.3s ease !important;
            }
            .woocommerce-cart .actions button.button:hover {
                background: #EABB61 !important;
                color: #27262B !important;
            }

            /* Resumen y Totales del Carrito */
            .cart-collaterals .cart_totals {
                background: #1C1B1E !important;
                border: 1px solid #EABB61 !important;
                border-radius: 12px !important;
                padding: 28px 32px !important;
                width: 100% !important;
                max-width: 480px !important;
                float: right !important;
                box-shadow: 0 16px 40px rgba(0, 0, 0, 0.55) !important;
            }
            .cart-collaterals .cart_totals h2 {
                color: #EABB61 !important;
                font-size: 1.35rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
                padding-bottom: 16px !important;
                margin-bottom: 20px !important;
            }
            .cart-collaterals .cart_totals table {
                width: 100% !important;
                margin-bottom: 24px !important;
            }
            .cart-collaterals .cart_totals table th {
                color: #FFFFFF !important;
                font-weight: 600 !important;
                padding: 12px 0 !important;
                text-align: left !important;
            }
            .cart-collaterals .cart_totals table td {
                color: #EABB61 !important;
                font-weight: 700 !important;
                text-align: right !important;
                padding: 12px 0 !important;
            }
            .cart-collaterals .cart_totals .wc-proceed-to-checkout a.checkout-button {
                background: linear-gradient(135deg, #EABB61 0%, #dca943 100%) !important;
                color: #27262B !important;
                font-size: 1.05rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 1.2px !important;
                padding: 16px 28px !important;
                border-radius: 6px !important;
                display: block !important;
                text-align: center !important;
                text-decoration: none !important;
                box-shadow: 0 6px 20px rgba(212, 203, 146, 0.3) !important;
                transition: all 0.3s ease !important;
            }
            .cart-collaterals .cart_totals .wc-proceed-to-checkout a.checkout-button:hover {
                background: #FFFFFF !important;
                box-shadow: 0 8px 28px rgba(212, 203, 146, 0.55) !important;
                transform: translateY(-2px) !important;
            }

            /* --- 4. Checkout / Finalizar Compra --- */
            .woocommerce-checkout #customer_details .col-1,
            .woocommerce-checkout #customer_details .col-2 {
                background: #1C1B1E !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 12px !important;
                padding: 28px !important;
                margin-bottom: 30px !important;
                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45) !important;
            }
            .woocommerce-checkout #customer_details h3 {
                color: #EABB61 !important;
                font-size: 1.3rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
                margin-bottom: 22px !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
                padding-bottom: 12px !important;
            }
            .woocommerce-checkout form .form-row label {
                color: #FFFFFF !important;
                font-weight: 600 !important;
                font-size: 0.92rem !important;
                margin-bottom: 6px !important;
                display: block !important;
            }
            .woocommerce-checkout form .form-row input.input-text,
            .woocommerce-checkout form .form-row textarea,
            .woocommerce-checkout form .form-row select {
                background: #151417 !important;
                border: 1px solid rgba(212, 203, 146, 0.35) !important;
                color: #FFFFFF !important;
                border-radius: 6px !important;
                padding: 12px 16px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                font-size: 0.98rem !important;
            }
            .woocommerce-checkout form .form-row input.input-text:focus,
            .woocommerce-checkout form .form-row textarea:focus {
                border-color: #EABB61 !important;
                outline: none !important;
                box-shadow: 0 0 10px rgba(212, 203, 146, 0.3) !important;
            }

            /* Panel de Pedido y Pago en Checkout */
            #order_review_heading {
                color: #EABB61 !important;
                font-size: 1.5rem !important;
                font-weight: 700 !important;
                margin-top: 30px !important;
            }
            #order_review {
                background: #1C1B1E !important;
                border: 1px solid #EABB61 !important;
                border-radius: 12px !important;
                padding: 28px !important;
                box-shadow: 0 16px 40px rgba(0, 0, 0, 0.55) !important;
            }
            #order_review table.shop_table {
                width: 100% !important;
                margin-bottom: 24px !important;
            }
            #order_review table.shop_table th,
            #order_review table.shop_table td {
                padding: 14px 10px !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
                color: #FFFFFF !important;
            }
            #order_review table.shop_table td.product-total,
            #order_review table.shop_table tr.order-total td {
                color: #EABB61 !important;
                font-weight: 700 !important;
                font-size: 1.15rem !important;
            }
            #payment.woocommerce-checkout-payment {
                background: #151417 !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 8px !important;
                padding: 20px !important;
            }
            #payment.woocommerce-checkout-payment ul.wc_payment_methods li {
                list-style: none !important;
                margin-bottom: 14px !important;
                color: #FFFFFF !important;
            }
            #payment.woocommerce-checkout-payment button#place_order {
                background: linear-gradient(135deg, #EABB61 0%, #dca943 100%) !important;
                color: #27262B !important;
                font-size: 1.1rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 1.2px !important;
                padding: 18px 32px !important;
                border-radius: 6px !important;
                border: none !important;
                width: 100% !important;
                cursor: pointer !important;
                transition: all 0.3s ease !important;
                box-shadow: 0 6px 20px rgba(212, 203, 146, 0.35) !important;
                margin-top: 16px !important;
            }
            #payment.woocommerce-checkout-payment button#place_order:hover {
                background: #FFFFFF !important;
                box-shadow: 0 8px 28px rgba(212, 203, 146, 0.6) !important;
                transform: translateY(-2px) !important;
            }

            /* --- 5. Página de Pedido Recibido / Gracias --- */
            .woocommerce-order-received .woocommerce-order {
                background: #1C1B1E !important;
                border: 1px solid #EABB61 !important;
                border-radius: 12px !important;
                padding: 36px !important;
                box-shadow: 0 16px 40px rgba(0, 0, 0, 0.55) !important;
            }
            .woocommerce-order-overview {
                background: rgba(212, 203, 146, 0.08) !important;
                border: 1px solid rgba(212, 203, 146, 0.3) !important;
                border-radius: 8px !important;
                padding: 20px !important;
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 20px !important;
                list-style: none !important;
                margin-bottom: 30px !important;
            }
            .woocommerce-order-overview li {
                color: #FFFFFF !important;
            }
            .woocommerce-order-overview li strong {
                color: #EABB61 !important;
                display: block !important;
                font-size: 1.1rem !important;
            }

            /* --- 6. Mi Cuenta / Área Arquitectos --- */
            .woocommerce-account .woocommerce-MyAccount-navigation ul {
                list-style: none !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 10px !important;
            }
            .woocommerce-account .woocommerce-MyAccount-navigation ul li a {
                display: block !important;
                background: #1C1B1E !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 8px !important;
                padding: 14px 20px !important;
                color: #FFFFFF !important;
                font-weight: 600 !important;
                text-decoration: none !important;
                transition: all 0.25s ease !important;
            }
            .woocommerce-account .woocommerce-MyAccount-navigation ul li.is-active a,
            .woocommerce-account .woocommerce-MyAccount-navigation ul li a:hover {
                background: rgba(212, 203, 146, 0.12) !important;
                border-color: #EABB61 !important;
                color: #EABB61 !important;
                transform: translateX(4px) !important;
            }
            .woocommerce-account .woocommerce-MyAccount-content {
                background: #1C1B1E !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 12px !important;
                padding: 32px !important;
                color: #E2E2E2 !important;
                line-height: 1.75 !important;
            }
            .woocommerce-account .woocommerce-MyAccount-content h3 {
                color: #EABB61 !important;
                margin-bottom: 18px !important;
            }
        
            /* =========================================================
               ESTILOS DE MAQUETACIÓN VIP PARA MI CUENTA & CARRITO VACÍO
               ========================================================= */

            /* --- 1. Contenedor Principal de Mi Cuenta (2 Columnas Flex) --- */
            .woocommerce-account .woocommerce {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 32px !important;
                align-items: flex-start !important;
                max-width: 1240px !important;
                margin: 0 auto !important;
            }

            /* Columna Izquierda: Navegación Píldora VIP (Ancho Fijo 280px) */
            .woocommerce-account .woocommerce-MyAccount-navigation {
                flex: 0 0 280px !important;
                width: 280px !important;
                background: #1C1B1E !important;
                border: 1px solid rgba(212, 203, 146, 0.25) !important;
                border-radius: 12px !important;
                padding: 24px !important;
                box-shadow: 0 16px 36px rgba(0, 0, 0, 0.5) !important;
            }
            .woocommerce-account .woocommerce-MyAccount-navigation ul {
                list-style: none !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 10px !important;
            }
            .woocommerce-account .woocommerce-MyAccount-navigation ul li {
                margin: 0 !important;
            }
            .woocommerce-account .woocommerce-MyAccount-navigation ul li a {
                display: flex !important;
                align-items: center !important;
                background: #151417 !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 8px !important;
                padding: 14px 18px !important;
                color: #FFFFFF !important;
                font-weight: 600 !important;
                font-size: 0.98rem !important;
                text-decoration: none !important;
                transition: all 0.25s ease !important;
            }
            .woocommerce-account .woocommerce-MyAccount-navigation ul li.is-active a,
            .woocommerce-account .woocommerce-MyAccount-navigation ul li a:hover {
                background: rgba(212, 203, 146, 0.14) !important;
                border-color: #EABB61 !important;
                color: #EABB61 !important;
                transform: translateX(5px) !important;
            }

            /* Columna Derecha: Contenido del Dashboard Arquitectónico */
            .woocommerce-account .woocommerce-MyAccount-content {
                flex: 1 !important;
                min-width: 320px !important;
                background: #1C1B1E !important;
                border: 1px solid rgba(212, 203, 146, 0.25) !important;
                border-radius: 12px !important;
                padding: 34px !important;
                color: #E2E2E2 !important;
                line-height: 1.8 !important;
                box-shadow: 0 16px 36px rgba(0, 0, 0, 0.5) !important;
            }
            .woocommerce-account .woocommerce-MyAccount-content p {
                font-size: 1.05rem !important;
                margin-bottom: 20px !important;
            }
            .woocommerce-account .woocommerce-MyAccount-content a {
                color: #EABB61 !important;
                font-weight: 600 !important;
                text-decoration: underline !important;
            }
            .woocommerce-account .woocommerce-MyAccount-content a:hover {
                color: #FFFFFF !important;
            }

            /* Tarjetas Interactivas Rápidas para Mi Cuenta */
            .tp-myaccount-grid {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
                gap: 18px !important;
                margin-top: 26px !important;
            }
            .tp-myaccount-card {
                background: #151417 !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 10px !important;
                padding: 20px !important;
                transition: all 0.3s ease !important;
                text-decoration: none !important;
                display: block !important;
            }
            .tp-myaccount-card:hover {
                border-color: #EABB61 !important;
                transform: translateY(-4px) !important;
                box-shadow: 0 10px 24px rgba(0, 0, 0, 0.45) !important;
            }
            .tp-myaccount-card h4 {
                color: #EABB61 !important;
                margin: 0 0 8px 0 !important;
                font-size: 1.08rem !important;
            }
            .tp-myaccount-card span {
                color: #B5B5B5 !important;
                font-size: 0.9rem !important;
                line-height: 1.4 !important;
                display: block !important;
            }

            /* --- 2. Carrito Vacío y Avisos Nativos de WooCommerce --- */
            .woocommerce-info,
            .cart-empty.woocommerce-info {
                background: #1C1B1E !important;
                border: 1px solid rgba(212, 203, 146, 0.35) !important;
                border-left: 5px solid #EABB61 !important;
                border-radius: 8px !important;
                color: #FFFFFF !important;
                padding: 22px 28px !important;
                font-size: 1.08rem !important;
                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45) !important;
                margin: 28px 0 !important;
            }
            p.return-to-shop {
                margin-top: 24px !important;
            }
            p.return-to-shop a.button {
                background: linear-gradient(135deg, #EABB61 0%, #dca943 100%) !important;
                color: #27262B !important;
                font-size: 1.02rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
                padding: 16px 32px !important;
                border-radius: 6px !important;
                text-decoration: none !important;
                display: inline-block !important;
                transition: all 0.3s ease !important;
                box-shadow: 0 6px 20px rgba(212, 203, 146, 0.35) !important;
            }
            p.return-to-shop a.button:hover {
                background: #FFFFFF !important;
                color: #27262B !important;
                box-shadow: 0 8px 28px rgba(212, 203, 146, 0.6) !important;
                transform: translateY(-2px) !important;
            }

            /* Corrección Botones de Compartir */
            .elementor-share-btn, .elementor-share-btn__icon {
                transition: all 0.3s ease !important;
            }
            .elementor-share-btn:hover, 
            .elementor-share-btn:hover .elementor-share-btn__icon {
                background-color: #1C1B1E !important;
                border-color: #EABB61 !important;
                box-shadow: 0 4px 15px rgba(234, 187, 97, 0.3) !important;
            }
            .elementor-share-btn:hover {
                transform: translateY(-3px) !important;
            }
            .elementor-share-btn:hover .elementor-share-btn__icon svg,
            .elementor-share-btn:hover .elementor-share-btn__icon i {
                fill: #EABB61 !important;
                color: #EABB61 !important;
            }
        </style>
        <?php
    }
});

add_action('acf/init', function() {
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group([
            'key' => 'group_tecnopremium_ficha',
            'title' => 'Especificaciones Técnicas Tecnopremium',
            'fields' => [
                [
                    'key' => 'field_tp_linea',
                    'label' => 'Línea Arquitectónica',
                    'name' => 'tecnopremium_linea',
                    'type' => 'text',
                    'default_value' => 'Serie Wellness Oficial 2026',
                ],
                [
                    'key' => 'field_tp_garantia',
                    'label' => 'Garantía Oficial',
                    'name' => 'tecnopremium_garantia',
                    'type' => 'text',
                    'default_value' => '10 años estructura / 5 años casco ProLast™',
                ],
                [
                    'key' => 'field_tp_plazo',
                    'label' => 'Disponibilidad / Entrega',
                    'name' => 'tecnopremium_plazo_entrega',
                    'type' => 'text',
                    'default_value' => 'Stock Directo · Envío Especializado',
                ],
                [
                    'key' => 'field_tp_instalacion',
                    'label' => 'Instalación y Puesta en Marcha',
                    'name' => 'tecnopremium_instalacion',
                    'type' => 'text',
                    'default_value' => 'Servicio Técnico Oficial en Obra',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'product',
                    ],
                ],
            ],
        ]);
    }
});

add_action('woocommerce_single_product_summary', function() {
    global $product;
    $linea = get_field('tecnopremium_linea') ?: 'Serie Wellness Oficial 2026';
    $garantia = get_field('tecnopremium_garantia') ?: '10 años estructura / 5 años casco ProLast™';
    $plazo = get_field('tecnopremium_plazo_entrega') ?: 'Stock Directo · Envío Especializado';
    $inst = get_field('tecnopremium_instalacion') ?: 'Servicio Técnico Oficial en Obra';

    echo '<div class="tecnopremium-ficha-tecnica">';
    echo '<h4>✦ Especicaciones & Garantía Tecnopremium</h4>';
    echo '<ul>';
    echo '<li><span>Línea Arquitectónica:</span> <strong>' . esc_html($linea) . '</strong></li>';
    echo '<li><span>Garantía Oficial:</span> <strong>' . esc_html($garantia) . '</strong></li>';
    echo '<li><span>Disponibilidad:</span> <strong>' . esc_html($plazo) . '</strong></li>';
    echo '<li><span>Servicio Técnico:</span> <strong>' . esc_html($inst) . '</strong></li>';
    echo '</ul>';
    echo '</div>';

    echo '<div class="tecnopremium-b2b-notice">';
    echo '<strong>✦ Atención Profesional B2B:</strong> ¿Eres Arquitecto, Interiorista o Distribuidor? ';
    echo '<a href="' . esc_url(wp_login_url()) . '" style="color: #EABB61; text-decoration: underline;">Inicia sesión</a> ';
    echo 'para aplicar automáticamente tu tarifa especial en obra (-15% a -30%).';
    echo '</div>';
}, 25);

// Inyección segura de CSS para las categorías en la página de inicio (sin cargar los estilos globales de tienda)
add_action('wp_head', function() {
    if (is_front_page() || is_home()) {
        ?>
        <style>
            /* Estilos de Categoría Premium para Home */
            .elementor-widget-wc-categories ul.products li.product-category {
                background: #131315 !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 10px !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                transition: all 0.3s ease !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                overflow: hidden !important;
            }
            .elementor-widget-wc-categories ul.products li.product-category a {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-decoration: none !important;
                width: 100% !important;
            }
            .elementor-widget-wc-categories ul.products li.product-category img {
                width: 100% !important;
                aspect-ratio: 1 / 1 !important;
                object-fit: cover !important;
                border-radius: 10px 10px 0 0 !important;
                margin: 0 !important;
                display: block !important;
            }
            .elementor-widget-wc-categories ul.products li.product-category h2 {
                color: #FFFFFF !important;
                font-size: 0.9rem !important;
                font-weight: 600 !important;
                text-align: center !important;
                line-height: 1.3 !important;
                margin: 12px 10px 4px 10px !important;
                padding: 0 !important;
            }
            .elementor-widget-wc-categories ul.products li.product-category mark.count {
                background: transparent !important;
                color: #EABB61 !important;
                font-weight: 500 !important;
                font-size: 0.8rem !important;
                margin-bottom: 12px !important;
            }
            .elementor-widget-wc-categories ul.products li.product-category:hover {
                border-color: #EABB61 !important;
                transform: translateY(-4px) !important;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
            }
            
            /* Corrección Forzada Contadores Elementor Home */
            .elementor-counter .elementor-counter-title {
                display: flex !important;
                flex: 1 !important;
                font-size: 15px !important;
                font-weight: 400 !important;
                line-height: 1em !important;
                margin: 2px !important;
                padding: 5px !important;
            }
            
            /* Carrusel de Marcas Fluido (Marquee) */
            .elementor-widget-image-carousel .swiper-wrapper {
                transition-timing-function: linear !important;
            }
        </style>
        <?php
    }
});
}