<?php
/**
 * Plantilla Principal del Blog (Blog Archive / Posts Page)
 * Agencia Ciscar Soporte
 */
get_header();
?>

<!-- Blog Hero Header -->
<section class="bg-[#0A1628] text-white py-16 md:py-20 border-b border-white/10 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-trust-blue/20 via-transparent to-action-orange/10 pointer-events-none"></div>
    <div class="max-w-container-max mx-auto px-gutter relative z-10">
        <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 bg-trust-blue/30 border border-trust-blue/50 text-[#38BDF8] rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-widest mb-4">
                <span class="w-2 h-2 rounded-full bg-[#38BDF8] animate-pulse"></span>
                <span>Blog & Centro de Conocimiento</span>
            </div>
            <h1 class="font-display text-[32px] md:text-[50px] font-extrabold text-white leading-tight mb-4">
                Mantenimiento, Ciberseguridad y Rendimiento WordPress<span class="text-action-orange">.</span>
            </h1>
            <p class="text-slate-300 text-[16px] md:text-[18px] leading-relaxed">
                Guías técnicas avanzadas, análisis de vulnerabilidades, consejos de optimización y mejores prácticas para mantener tu web corporativa invulnerable y ultrarrápida.
            </p>
        </div>

        <!-- Category Filter Pills -->
        <?php
        $categories = get_categories( array(
            'orderby' => 'name',
            'order'   => 'ASC',
            'hide_empty' => false,
        ) );
        if ( ! empty( $categories ) ) :
        ?>
            <div class="flex flex-wrap items-center gap-2.5 mt-8 pt-6 border-t border-white/10">
                <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider mr-2">Categorías:</span>
                <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="px-4 py-2 rounded-full text-xs font-semibold transition-all <?php echo ( is_home() && ! is_category() ) ? 'bg-action-orange text-white shadow-sm' : 'bg-white/10 text-slate-200 hover:bg-white/20'; ?>">
                    Todos los artículos
                </a>
                <?php foreach ( $categories as $cat ) : ?>
                    <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="px-4 py-2 rounded-full text-xs font-semibold transition-all <?php echo ( is_category( $cat->term_id ) ) ? 'bg-action-orange text-white shadow-sm' : 'bg-white/10 text-slate-200 hover:bg-white/20'; ?>">
                        <?php echo esc_html( $cat->name ); ?> (<?php echo intval( $cat->count ); ?>)
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Blog Content Grid -->
<main class="flex-grow bg-surface-container-low py-12 md:py-20">
    <div class="max-w-container-max mx-auto px-gutter">

        <?php if ( have_posts() ) : ?>

            <?php
            // Post destacado (Primer post de la lista en la página 1)
            $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
            if ( 1 === $paged ) :
                the_post();
                $featured_id = get_the_ID();
                $word_count  = str_word_count( strip_tags( get_the_content() ) );
                $read_time   = max( 1, ceil( $word_count / 200 ) );
                $cats        = get_the_category();
                $cat_name    = ! empty( $cats ) ? $cats[0]->name : 'General';
                $thumb_url   = get_the_post_thumbnail_url( $featured_id, 'large' );
            ?>
                <!-- Featured Article Card -->
                <div class="mb-14 bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 grid grid-cols-1 lg:grid-cols-12">
                    <div class="lg:col-span-7 relative min-h-[300px] lg:min-h-[420px] bg-[#0A1628] overflow-hidden group">
                        <?php if ( $thumb_url ) : ?>
                            <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <?php else : ?>
                            <div class="w-full h-full min-h-[300px] bg-gradient-to-br from-trust-blue via-[#0A1628] to-action-orange/40 flex items-center justify-center p-8 text-center relative">
                                <span class="material-symbols-outlined text-[90px] text-white/15 absolute">article</span>
                                <span class="text-white/80 font-display font-bold text-xl relative z-10 px-6 py-3 border border-white/20 rounded-xl bg-white/5 backdrop-blur-sm">
                                    Agencia Ciscar Tech Blog
                                </span>
                            </div>
                        <?php endif; ?>
                        <span class="absolute top-4 left-4 bg-action-orange text-white text-xs font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-full shadow-md z-10">
                            Artículo Destacado
                        </span>
                    </div>

                    <div class="lg:col-span-5 p-8 lg:p-10 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-3 text-xs text-slate-gray font-medium mb-3">
                                <span class="bg-trust-blue/10 text-trust-blue font-semibold px-2.5 py-1 rounded-md">
                                    <?php echo esc_html( $cat_name ); ?>
                                </span>
                                <span>•</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px]">schedule</span>
                                    <?php echo $read_time; ?> min de lectura
                                </span>
                            </div>

                            <h2 class="font-display font-bold text-[22px] md:text-[28px] text-ink-black leading-tight mb-4 hover:text-action-orange transition-colors">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>

                            <p class="text-slate-gray text-[15px] leading-relaxed mb-6 line-clamp-3">
                                <?php echo esc_html( get_the_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 30 ) ); ?>
                            </p>
                        </div>

                        <div class="pt-6 border-t border-outline-variant flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-trust-blue text-white font-bold flex items-center justify-center text-sm shadow-xs">
                                    <?php echo strtoupper( substr( get_the_author_meta( 'display_name' ), 0, 1 ) ); ?>
                                </div>
                                <div>
                                    <div class="font-semibold text-[14px] text-ink-black"><?php the_author(); ?></div>
                                    <div class="text-[12px] text-slate-gray"><?php echo get_the_date( 'd M, Y' ); ?></div>
                                </div>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1 text-action-orange font-bold text-[14px] hover:translate-x-1 transition-transform">
                                Leer artículo
                                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Grid Header -->
            <div class="flex justify-between items-center mb-8 border-b border-outline-variant pb-4">
                <h3 class="font-display font-bold text-xl text-ink-black flex items-center gap-2">
                    <span class="material-symbols-outlined text-action-orange">rss_feed</span>
                    Últimas Publicaciones
                </h3>
                <span class="text-xs text-slate-gray font-medium">Mostrando artículos técnicos</span>
            </div>

            <!-- Articles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                while ( have_posts() ) : the_post();
                    // Omitir el post destacado en la página 1
                    if ( 1 === $paged && isset( $featured_id ) && get_the_ID() === $featured_id ) {
                        continue;
                    }
                    $word_count = str_word_count( strip_tags( get_the_content() ) );
                    $read_time  = max( 1, ceil( $word_count / 200 ) );
                    $cats       = get_the_category();
                    $cat_name   = ! empty( $cats ) ? $cats[0]->name : 'General';
                    $thumb_url  = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
                ?>
                    <article class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
                        <div>
                            <!-- Post Thumbnail / Visual Placeholder -->
                            <a href="<?php the_permalink(); ?>" class="block relative h-48 bg-[#0A1628] overflow-hidden">
                                <?php if ( $thumb_url ) : ?>
                                    <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else : ?>
                                    <div class="w-full h-full bg-gradient-to-br from-trust-blue/20 via-[#0A1628] to-action-orange/30 flex items-center justify-center p-6 text-center relative">
                                        <span class="material-symbols-outlined text-[60px] text-white/10 absolute">verified_user</span>
                                        <span class="text-white/70 font-display font-semibold text-xs px-3 py-1.5 border border-white/15 rounded-lg bg-white/5">
                                            Agencia Ciscar
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <span class="absolute top-3 left-3 bg-trust-blue text-white text-[11px] font-bold px-2.5 py-1 rounded-md shadow-xs">
                                    <?php echo esc_html( $cat_name ); ?>
                                </span>
                            </a>

                            <!-- Post Body -->
                            <div class="p-6">
                                <div class="flex items-center gap-2 text-xs text-slate-gray font-medium mb-3">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                        <?php echo get_the_date( 'd M, Y' ); ?>
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">schedule</span>
                                        <?php echo $read_time; ?> min
                                    </span>
                                </div>

                                <h3 class="font-display font-bold text-[18px] text-ink-black leading-snug mb-3 group-hover:text-action-orange transition-colors">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>

                                <p class="text-slate-gray text-[14px] leading-relaxed line-clamp-3 mb-4">
                                    <?php echo esc_html( get_the_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 22 ) ); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Post Footer -->
                        <div class="px-6 pb-6 pt-0 flex items-center justify-between border-t border-slate-100 mt-2">
                            <div class="flex items-center gap-2.5 pt-4">
                                <div class="w-8 h-8 rounded-full bg-trust-blue text-white font-bold flex items-center justify-center text-xs">
                                    <?php echo strtoupper( substr( get_the_author_meta( 'display_name' ), 0, 1 ) ); ?>
                                </div>
                                <span class="text-xs font-semibold text-ink-black"><?php the_author(); ?></span>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="pt-4 text-action-orange text-xs font-bold flex items-center gap-0.5 group-hover:translate-x-1 transition-transform">
                                Leer más
                                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <div class="mt-14 flex justify-center">
                <?php
                echo paginate_links( array(
                    'prev_text' => '<span class="material-symbols-outlined align-middle">chevron_left</span> Anterior',
                    'next_text' => 'Siguiente <span class="material-symbols-outlined align-middle">chevron_right</span>',
                    'type'      => 'plain',
                    'before_page_number' => '<span class="px-3.5 py-2 rounded-lg border border-outline-variant bg-white font-semibold text-sm hover:bg-trust-blue hover:text-white transition-all inline-block mx-1">',
                    'after_page_number'  => '</span>',
                ) );
                ?>
            </div>

        <?php else : ?>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center max-w-xl mx-auto">
                <span class="material-symbols-outlined text-[64px] text-slate-400 mb-4">search_off</span>
                <h3 class="font-display font-bold text-2xl text-ink-black mb-2">No se encontraron artículos</h3>
                <p class="text-slate-gray mb-6">Actualmente no hay artículos publicados en esta sección. Vuelve a consultar pronto.</p>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex items-center gap-2 bg-action-orange text-white font-semibold px-6 py-3 rounded-md">
                    Volver al Inicio
                </a>
            </div>
        <?php endif; ?>

        <!-- CTA Box Banner -->
        <div class="mt-20 bg-gradient-to-r from-[#0A1628] to-trust-blue rounded-2xl p-8 md:p-12 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-8 border border-white/10">
            <div class="max-w-xl text-center md:text-left">
                <span class="bg-action-orange text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full mb-3 inline-block">Soporte Técnico Especializado</span>
                <h3 class="font-display font-extrabold text-[24px] md:text-[32px] leading-tight mb-2">¿Necesitas ayuda técnica con tu sitio WordPress?</h3>
                <p class="text-slate-300 text-[15px]">Nuestros ingenieros se encargan del mantenimiento, parches de seguridad, optimización de velocidad y resolución de incidencias 24/7.</p>
            </div>
            <a href="<?php echo esc_url( home_url( '/portal-soporte/' ) ); ?>" class="bg-action-orange hover:bg-action-orange/90 text-white font-bold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition-all whitespace-nowrap flex items-center gap-2 text-[15px]">
                <span class="material-symbols-outlined">support_agent</span>
                <span>Abrir Ticket de Soporte</span>
            </a>
        </div>

    </div>
</main>

<?php
get_footer();
