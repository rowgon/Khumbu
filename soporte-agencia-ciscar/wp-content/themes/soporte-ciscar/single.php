<?php
/**
 * Plantilla de Entradas Individuales de Blog (Single Post)
 * Agencia Ciscar Soporte
 */
get_header();

if ( have_posts() ) : while ( have_posts() ) : the_post();
    $post_id    = get_the_ID();
    $word_count = str_word_count( strip_tags( get_the_content() ) );
    $read_time  = max( 1, ceil( $word_count / 200 ) );
    $cats       = get_the_category();
    $cat        = ! empty( $cats ) ? $cats[0] : null;
    $thumb_url  = get_the_post_thumbnail_url( $post_id, 'full' );
?>

<!-- Breadcrumbs Bar -->
<div class="bg-[#07111E] text-slate-400 py-3 border-b border-white/10 text-xs">
    <div class="max-w-container-max mx-auto px-gutter flex items-center gap-2 overflow-x-auto whitespace-nowrap">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-action-orange transition-colors">Inicio</a>
        <span class="text-slate-600">/</span>
        <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="hover:text-action-orange transition-colors">Blog</a>
        <?php if ( $cat ) : ?>
            <span class="text-slate-600">/</span>
            <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="hover:text-action-orange transition-colors">
                <?php echo esc_html( $cat->name ); ?>
            </a>
        <?php endif; ?>
        <span class="text-slate-600">/</span>
        <span class="text-slate-200 font-medium truncate max-w-xs md:max-w-md"><?php the_title(); ?></span>
    </div>
</div>

<!-- Article Hero Header -->
<header class="bg-[#0A1628] text-white py-12 md:py-16 border-b border-white/10 relative">
    <div class="max-w-4xl mx-auto px-gutter relative z-10">
        <?php if ( $cat ) : ?>
            <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="inline-block bg-action-orange text-white text-xs font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-full mb-4 hover:bg-action-orange/90 transition-colors shadow-sm">
                <?php echo esc_html( $cat->name ); ?>
            </a>
        <?php endif; ?>

        <h1 class="font-display font-extrabold text-[28px] sm:text-[36px] md:text-[46px] leading-[1.18] text-white mb-6">
            <?php the_title(); ?>
        </h1>

        <!-- Author & Meta Info Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-white/15 text-sm text-slate-300">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-trust-blue text-white font-bold flex items-center justify-center text-base border-2 border-white/20 shadow-sm">
                    <?php echo strtoupper( substr( get_the_author_meta( 'display_name' ), 0, 1 ) ); ?>
                </div>
                <div>
                    <div class="font-semibold text-white text-[15px]"><?php the_author(); ?></div>
                    <div class="text-xs text-slate-400">División de Ciberseguridad & Mantenimiento</div>
                </div>
            </div>

            <div class="flex items-center gap-4 text-xs md:text-sm text-slate-300">
                <div class="flex items-center gap-1.5 bg-white/10 px-3 py-1.5 rounded-lg border border-white/10">
                    <span class="material-symbols-outlined text-[16px] text-action-orange">calendar_today</span>
                    <span><?php echo get_the_date( 'd \d\e F, Y' ); ?></span>
                </div>
                <div class="flex items-center gap-1.5 bg-white/10 px-3 py-1.5 rounded-lg border border-white/10">
                    <span class="material-symbols-outlined text-[16px] text-[#38BDF8]">schedule</span>
                    <span><?php echo $read_time; ?> min de lectura</span>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Main Article Body -->
<main class="flex-grow bg-surface-container-low py-12 md:py-16">
    <div class="max-w-4xl mx-auto px-gutter">

        <!-- Featured Image Banner -->
        <?php if ( $thumb_url ) : ?>
            <div class="mb-10 rounded-2xl overflow-hidden shadow-lg border border-outline-variant">
                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>" class="w-full max-h-[500px] object-cover">
            </div>
        <?php endif; ?>

        <!-- Post Content Box -->
        <article class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 md:p-12 shadow-sm mb-12">
            
            <!-- Custom CSS for Post Content Typography -->
            <style>
                .single-article-body h2 {
                    font-family: 'Montserrat', sans-serif;
                    font-weight: 700;
                    font-size: 1.65rem;
                    color: #0A1628;
                    margin-top: 2.2rem;
                    margin-bottom: 1rem;
                    line-height: 1.3;
                    border-bottom: 2px solid #F15A29;
                    padding-bottom: 0.4rem;
                    display: inline-block;
                }
                .single-article-body h3 {
                    font-family: 'Montserrat', sans-serif;
                    font-weight: 700;
                    font-size: 1.3rem;
                    color: #065186;
                    margin-top: 1.8rem;
                    margin-bottom: 0.8rem;
                }
                .single-article-body p {
                    font-size: 1.05rem;
                    line-height: 1.8;
                    color: #334155;
                    margin-bottom: 1.4rem;
                }
                .single-article-body ul, .single-article-body ol {
                    margin-bottom: 1.5rem;
                    padding-left: 1.5rem;
                }
                .single-article-body ul { list-style-type: disc; }
                .single-article-body ol { list-style-type: decimal; }
                .single-article-body li {
                    font-size: 1.02rem;
                    line-height: 1.7;
                    color: #334155;
                    margin-bottom: 0.5rem;
                }
                .single-article-body blockquote {
                    border-left: 4px solid #F15A29;
                    background: #f8fafc;
                    padding: 1.2rem 1.5rem;
                    margin: 1.8rem 0;
                    border-radius: 0 0.75rem 0.75rem 0;
                    font-style: italic;
                    color: #1e293b;
                }
                .single-article-body code {
                    background: #f1f5f9;
                    color: #065186;
                    padding: 0.2rem 0.4rem;
                    border-radius: 0.25rem;
                    font-size: 0.9em;
                    font-family: monospace;
                }
                .single-article-body pre {
                    background: #0A1628;
                    color: #f8fafc;
                    padding: 1.25rem;
                    border-radius: 0.75rem;
                    overflow-x: auto;
                    margin: 1.5rem 0;
                }
                .single-article-body pre code {
                    background: transparent;
                    color: inherit;
                    padding: 0;
                }
                .single-article-body img {
                    border-radius: 0.75rem;
                    margin: 1.8rem 0;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                }
                .single-article-body strong {
                    color: #0F172A;
                }
            </style>

            <div class="single-article-body entry-content">
                <?php the_content(); ?>
            </div>

            <!-- Article Footer: Tags & Share -->
            <div class="mt-10 pt-8 border-t border-outline-variant flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold text-slate-gray uppercase tracking-wider">Etiquetas:</span>
                    <?php
                    $tags = get_the_tags();
                    if ( $tags ) :
                        foreach ( $tags as $tag ) :
                    ?>
                        <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="bg-surface-container hover:bg-trust-blue hover:text-white text-slate-700 text-xs font-semibold px-3 py-1 rounded-md transition-colors">
                            #<?php echo esc_html( $tag->name ); ?>
                        </a>
                    <?php
                        endforeach;
                    else :
                    ?>
                        <span class="text-xs text-slate-400 italic">Mantenimiento, WordPress, Seguridad</span>
                    <?php endif; ?>
                </div>

                <!-- Quick Share -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-gray mr-1">Compartir:</span>
                    <button onclick="navigator.clipboard.writeText(window.location.href); alert('¡Enlace copiado al portapapeles!');" class="w-8 h-8 rounded-full bg-surface-container hover:bg-action-orange hover:text-white text-slate-700 flex items-center justify-center transition-colors" title="Copiar Enlace">
                        <span class="material-symbols-outlined text-[16px]">link</span>
                    </button>
                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode( get_the_title() ); ?>&url=<?php echo urlencode( get_permalink() ); ?>" target="_blank" rel="noopener" class="w-8 h-8 rounded-full bg-surface-container hover:bg-[#1DA1F2] hover:text-white text-slate-700 flex items-center justify-center transition-colors" title="Compartir en X / Twitter">
                        <span class="font-bold text-xs">X</span>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode( get_permalink() ); ?>" target="_blank" rel="noopener" class="w-8 h-8 rounded-full bg-surface-container hover:bg-[#0A66C2] hover:text-white text-slate-700 flex items-center justify-center transition-colors" title="Compartir en LinkedIn">
                        <span class="font-bold text-xs">in</span>
                    </a>
                </div>
            </div>
        </article>

        <!-- Author Bio Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 md:p-8 shadow-sm mb-12 flex flex-col sm:flex-row items-center sm:items-start gap-6">
            <div class="w-16 h-16 rounded-full bg-trust-blue text-white font-extrabold text-2xl flex items-center justify-center shrink-0 border-2 border-trust-blue/30 shadow-sm">
                <?php echo strtoupper( substr( get_the_author_meta( 'display_name' ), 0, 1 ) ); ?>
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1 justify-center sm:justify-start">
                    <h4 class="font-display font-bold text-lg text-ink-black"><?php the_author(); ?></h4>
                    <span class="bg-action-orange/10 text-action-orange text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full">Autor Técnico</span>
                </div>
                <p class="text-slate-gray text-sm leading-relaxed text-center sm:text-left">
                    Equipo especializado de soporte y ciberseguridad de <strong>Agencia Ciscar</strong>. Nos dedicamos a proteger, optimizar y mantener sitios web WordPress corporativos con disponibilidad 24/7.
                </p>
            </div>
        </div>

        <!-- Previous & Next Post Navigation -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">
            <?php
            $prev_post = get_previous_post();
            if ( ! empty( $prev_post ) ) :
            ?>
                <a href="<?php echo esc_url( get_permalink( $prev_post->ID ) ); ?>" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs hover:border-action-orange transition-all flex items-center gap-4 group">
                    <span class="w-10 h-10 rounded-full bg-surface-container group-hover:bg-action-orange group-hover:text-white text-slate-600 flex items-center justify-center shrink-0 transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </span>
                    <div class="overflow-hidden">
                        <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Artículo Anterior</span>
                        <h5 class="font-display font-bold text-sm text-ink-black group-hover:text-action-orange transition-colors truncate">
                            <?php echo esc_html( get_the_title( $prev_post->ID ) ); ?>
                        </h5>
                    </div>
                </a>
            <?php else : ?>
                <div></div>
            <?php endif; ?>

            <?php
            $next_post = get_next_post();
            if ( ! empty( $next_post ) ) :
            ?>
                <a href="<?php echo esc_url( get_permalink( $next_post->ID ) ); ?>" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs hover:border-action-orange transition-all flex items-center justify-between gap-4 text-right group">
                    <div class="overflow-hidden text-right w-full">
                        <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Siguiente Artículo</span>
                        <h5 class="font-display font-bold text-sm text-ink-black group-hover:text-action-orange transition-colors truncate">
                            <?php echo esc_html( get_the_title( $next_post->ID ) ); ?>
                        </h5>
                    </div>
                    <span class="w-10 h-10 rounded-full bg-surface-container group-hover:bg-action-orange group-hover:text-white text-slate-600 flex items-center justify-center shrink-0 transition-colors">
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Related Articles Section -->
        <?php
        $related_args = array(
            'post_type'      => 'post',
            'posts_per_page' => 3,
            'post__not_in'   => array( $post_id ),
            'orderby'        => 'rand',
        );
        if ( $cat ) {
            $related_args['cat'] = $cat->term_id;
        }
        $related_query = new WP_Query( $related_args );
        if ( $related_query->have_posts() ) :
        ?>
            <div class="mb-16">
                <div class="flex items-center justify-between mb-8 border-b border-outline-variant pb-4">
                    <h3 class="font-display font-bold text-2xl text-ink-black flex items-center gap-2">
                        <span class="material-symbols-outlined text-action-orange">auto_awesome</span>
                        Artículos Relacionados
                    </h3>
                    <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="text-action-orange font-bold text-xs uppercase tracking-wider hover:underline">
                        Ver todos los artículos ->
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php
                    while ( $related_query->have_posts() ) : $related_query->the_post();
                        $rel_thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                    ?>
                        <article class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
                            <div>
                                <a href="<?php the_permalink(); ?>" class="block relative h-40 bg-[#0A1628] overflow-hidden">
                                    <?php if ( $rel_thumb ) : ?>
                                        <img src="<?php echo esc_url( $rel_thumb ); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    <?php else : ?>
                                        <div class="w-full h-full bg-gradient-to-br from-trust-blue/20 to-action-orange/30 flex items-center justify-center p-4 text-center">
                                            <span class="material-symbols-outlined text-[40px] text-white/20">article</span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="p-5">
                                    <h4 class="font-display font-bold text-[16px] text-ink-black leading-snug mb-2 group-hover:text-action-orange transition-colors line-clamp-2">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h4>
                                    <p class="text-slate-gray text-xs line-clamp-2 mb-4">
                                        <?php echo esc_html( wp_trim_words( get_the_excerpt() ? get_the_excerpt() : get_the_content(), 15 ) ); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="px-5 pb-5 pt-0">
                                <a href="<?php the_permalink(); ?>" class="text-action-orange font-bold text-xs flex items-center gap-1">
                                    Leer artículo
                                    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- CTA Box Banner -->
        <div class="bg-gradient-to-r from-[#0A1628] to-trust-blue rounded-2xl p-8 md:p-12 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-8 border border-white/10">
            <div class="max-w-xl text-center md:text-left">
                <span class="bg-action-orange text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full mb-3 inline-block">¿Problemas Técnicos en tu Web?</span>
                <h3 class="font-display font-extrabold text-[24px] md:text-[30px] leading-tight mb-2">Apliquemos estas optimizaciones en tu WordPress</h3>
                <p class="text-slate-300 text-[15px]">Recibe soporte prioritario de nuestro equipo técnico. Nos encargamos de la seguridad, parches y velocidad.</p>
            </div>
            <a href="<?php echo esc_url( home_url( '/portal-soporte/' ) ); ?>" class="bg-action-orange hover:bg-action-orange/90 text-white font-bold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition-all whitespace-nowrap flex items-center gap-2 text-[15px]">
                <span class="material-symbols-outlined">support_agent</span>
                <span>Abrir Ticket de Soporte</span>
            </a>
        </div>

    </div>
</main>

<?php
endwhile; endif;
get_footer();
