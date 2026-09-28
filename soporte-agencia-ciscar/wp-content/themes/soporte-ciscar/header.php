<?php
/**
 * Header Template - Agencia Ciscar Soporte
 */
?>
<!DOCTYPE html>
<html class="scroll-smooth" <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php wp_title('|', true, 'right'); ?> Soporte Técnico & Blog | Agencia Ciscar</title>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "action-orange": "#F15A29",
                        "trust-blue": "#065186",
                        "primary": "#003a62",
                        "primary-container": "#065186",
                        "primary-fixed": "#d1e4ff",
                        "primary-fixed-dim": "#9dcaff",
                        "surface": "#f7f9fb",
                        "surface-bright": "#f7f9fb",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f2f4f6",
                        "surface-container": "#eceef0",
                        "surface-container-high": "#e6e8ea",
                        "surface-container-highest": "#e0e3e5",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#41474f",
                        "outline": "#727780",
                        "outline-variant": "#c1c7d1",
                        "ink-black": "#000000",
                        "slate-gray": "#475569"
                    },
                    spacing: {
                        "margin-mobile": "16px",
                        "gutter": "24px",
                        "stack-sm": "12px",
                        "stack-md": "24px",
                        "stack-lg": "48px",
                        "container-max": "1200px"
                    },
                    fontFamily: {
                        "display": ["Montserrat", "sans-serif"],
                        "body": ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-surface text-on-surface font-body antialiased selection:bg-action-orange selection:text-white min-h-screen flex flex-col justify-between' ); ?>>

<!-- TopNavBar -->
<header class="bg-[#0A1628]/95 backdrop-blur-md w-full top-0 sticky border-b border-white/10 z-50 transition-all duration-200 shadow-md">
    <div class="max-w-container-max mx-auto px-gutter flex justify-between items-center h-20">
        <!-- Brand -->
        <a class="flex items-center gap-3 font-display font-extrabold scale-95 active:scale-90 transition-transform" href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <img src="<?php echo get_template_directory_uri(); ?>/logo-1.png" alt="Agencia Ciscar Logo" class="h-9 md:h-11 w-auto object-contain brightness-105">
            <span class="bg-action-orange text-white text-[11px] px-2.5 py-0.5 rounded-full uppercase tracking-wider font-semibold shadow-xs">Soporte</span>
        </a>

        <!-- Navigation Links (Desktop) -->
        <nav class="hidden md:flex gap-7 items-center">
            <a class="text-slate-300 hover:text-action-orange font-medium text-[15px] transition-colors duration-200" href="<?php echo esc_url( home_url( '/#servicios' ) ); ?>">Servicios</a>
            <a class="text-slate-300 hover:text-action-orange font-medium text-[15px] transition-colors duration-200" href="<?php echo esc_url( home_url( '/#seguridad' ) ); ?>">Seguridad</a>
            <a class="text-slate-300 hover:text-action-orange font-medium text-[15px] transition-colors duration-200" href="<?php echo esc_url( home_url( '/#actualizaciones' ) ); ?>">Actualizaciones</a>
            <a class="text-slate-300 hover:text-action-orange font-medium text-[15px] transition-colors duration-200 <?php echo ( is_home() || is_archive() || is_single() ) ? 'text-action-orange font-semibold' : ''; ?>" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a>
            <a class="text-[#38BDF8] font-semibold text-[15px] hover:underline transition-colors duration-200 flex items-center gap-1.5 bg-trust-blue/20 border border-trust-blue/40 px-3.5 py-1.5 rounded-full" href="<?php echo esc_url( home_url( '/portal-soporte/' ) ); ?>">
                <span class="material-symbols-outlined text-[18px]">support_agent</span>
                <span>Portal de Tickets</span>
            </a>
        </nav>

        <!-- Trailing Action -->
        <div class="hidden md:flex items-center gap-3">
            <a class="inline-flex items-center justify-center bg-action-orange text-white font-semibold text-[14px] px-6 py-3 rounded-md shadow-sm scale-95 active:scale-90 transition-all hover:bg-action-orange/90 hover:shadow" href="<?php echo esc_url( home_url( '/portal-soporte/' ) ); ?>">
                Abrir Ticket
            </a>
        </div>

        <!-- Mobile Menu Toggle -->
        <button id="mobile-menu-toggle" class="md:hidden text-white p-2 focus:outline-none" aria-label="Abrir menú móvil">
            <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">menu</span>
        </button>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div id="mobile-menu" class="hidden md:hidden bg-[#0A1628] border-b border-white/10 px-gutter py-6 flex flex-col gap-4 shadow-lg animate-fadeIn">
        <a class="text-slate-200 font-semibold text-[16px] hover:text-action-orange" href="<?php echo esc_url( home_url( '/#servicios' ) ); ?>">Servicios</a>
        <a class="text-slate-200 font-semibold text-[16px] hover:text-action-orange" href="<?php echo esc_url( home_url( '/#seguridad' ) ); ?>">Seguridad</a>
        <a class="text-slate-200 font-semibold text-[16px] hover:text-action-orange" href="<?php echo esc_url( home_url( '/#actualizaciones' ) ); ?>">Actualizaciones</a>
        <a class="text-slate-200 font-semibold text-[16px] hover:text-action-orange" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a>
        <a class="text-[#38BDF8] font-semibold text-[16px] flex items-center gap-1.5 bg-trust-blue/20 border border-trust-blue/40 px-4 py-2 rounded-lg" href="<?php echo esc_url( home_url( '/portal-soporte/' ) ); ?>">
            <span class="material-symbols-outlined text-[18px]">support_agent</span>
            <span>Portal de Tickets</span>
        </a>
        <a class="inline-flex items-center justify-center bg-action-orange text-white font-semibold text-[15px] px-6 py-3 rounded-md mt-2 text-center" href="<?php echo esc_url( home_url( '/portal-soporte/' ) ); ?>">
            Abrir Ticket Ahora
        </a>
    </div>
</header>
