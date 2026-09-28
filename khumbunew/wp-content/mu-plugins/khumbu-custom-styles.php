<?php
/* Plugin Name: Khumbu Custom Styles */
add_action('wp_head', function() {
    ?>
    <style id="khumbu-master-styles">
    /* Reset button styles */
    button, input[type="button"], input[type="submit"], .e-button, .elementor-button {
      appearance: none !important;
      -webkit-appearance: none !important;
      border: none !important;
      outline: none !important;
      background: transparent !important;
      box-shadow: none !important;
      text-decoration: none !important;
      cursor: pointer !important;
    }

    /* Fixed Glass Header */
    .elementor-element-4dba9b3, header, [data-elementor-type="header"] {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      width: 100% !important;
      z-index: 99999 !important;
      background: rgba(10, 9, 22, 0.88) !important;
      backdrop-filter: blur(16px) !important;
      -webkit-backdrop-filter: blur(16px) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
      padding: 14px 28px !important;
      box-sizing: border-box !important;
    }

    /* Header Logo */
    .elementor-element-4dba9b3 img, header img, [data-elementor-type="header"] img {
      max-height: 34px !important;
      width: auto !important;
      display: block !important;
    }

    /* Header Nav Links */
    .elementor-element-82c0253 .e-button, 
    header .elementor-element-82c0253 a, 
    .elementor-location-header .e-button,
    header nav a {
      background: transparent !important;
      border: none !important;
      color: #fcfcfd !important;
      font-family: "Poppins", sans-serif !important;
      font-size: 13px !important;
      font-weight: 500 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.08em !important;
      padding: 8px 16px !important;
      border-radius: 4px !important;
      text-decoration: none !important;
      transition: all 0.2s ease !important;
    }

    .elementor-element-82c0253 .e-button:hover, 
    header .elementor-element-82c0253 a:hover, 
    .elementor-location-header .e-button:hover,
    header nav a:hover {
      color: #e46a25 !important;
      background: rgba(228, 106, 37, 0.08) !important;
    }

    /* Header CTA Button ("Agenda tu diagnóstico") */
    .elementor-element-340a005 .e-button, 
    .elementor-element-340a005 a,
    header .elementor-element-340a005 a {
      background: #e46a25 !important;
      color: #0a0916 !important;
      border: 1px solid #e46a25 !important;
      border-radius: 8px !important;
      font-family: "Poppins", sans-serif !important;
      font-size: 13px !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.08em !important;
      padding: 12px 24px !important;
      text-decoration: none !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      box-shadow: 0 4px 14px rgba(228, 106, 37, 0.35) !important;
      transition: all 0.25s ease !important;
    }

    .elementor-element-340a005 .e-button:hover, 
    .elementor-element-340a005 a:hover,
    header .elementor-element-340a005 a:hover {
      background: #c85a1c !important;
      border-color: #c85a1c !important;
      color: #ffffff !important;
      transform: translateY(-2px) !important;
      box-shadow: 0 6px 20px rgba(228, 106, 37, 0.5) !important;
    }

    /* Footer Design */
    .elementor-element-334c233, footer, [data-elementor-type="footer"] {
      background-color: #050409 !important;
      border-top: 1px solid #2a2842 !important;
      padding: 60px 24px 40px !important;
      color: #cfcdd8 !important;
    }

    footer a, .elementor-location-footer a, [data-elementor-type="footer"] a {
      color: #8f8da0 !important;
      font-family: "Poppins", sans-serif !important;
      font-size: 14px !important;
      text-decoration: none !important;
      transition: color 0.2s ease !important;
    }

    footer a:hover, .elementor-location-footer a:hover, [data-elementor-type="footer"] a:hover {
      color: #e46a25 !important;
    }

    .tarjeta-khumbu {
      background-color: #131124 !important;
      border: 1px solid #2a2842 !important;
      border-radius: 8px !important;
      transition: all 300ms ease !important;
    }

    .tarjeta-khumbu:hover {
      border-color: #e46a25 !important;
    }

    /* Fix Huge Typography Overflows on Mobile */
    @media (max-width: 480px) {
      .cover__title, .hero h2, .display, .clients__track span {
        font-size: clamp(2rem, 8vw + 0.5rem, 3.5rem) !important;
        line-height: 1.1 !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        white-space: normal !important;
      }
    }

    /* Force overflow visible on Elementor parents so position: sticky works (WOW Block fix) */
    html, body {
      overflow-x: visible !important;
    }
    .elementor-section, .elementor-container, .e-con, .elementor-widget-wrap, .elementor-widget-html, .elementor-widget-container {
      overflow: visible !important;
    }
    .elementor-widget-container {
      overflow-x: clip !important; /* Only clip the very final container to prevent horizontal scroll */
    }
    </style>
    <?php
});
