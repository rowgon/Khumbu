<?php
/**
 * Plantilla de Páginas Estáticas y Portal de Tickets
 */
get_header();
?>

<main class="flex-grow bg-surface-container-low py-12 md:py-16">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        
        <!-- Page Banner -->
        <div class="bg-[#0A1628] text-white py-12 mb-10 border-b border-white/10 shadow-md">
            <div class="max-w-container-max mx-auto px-gutter flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <span class="text-action-orange font-bold text-[12px] uppercase tracking-widest font-display block mb-1">Agencia Ciscar Soporte</span>
                    <h1 class="font-display text-[28px] md:text-[38px] font-bold text-white"><?php the_title(); ?></h1>
                </div>
                <div class="inline-flex items-center gap-2 bg-white/10 border border-white/15 rounded-lg px-4 py-2 text-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Soporte Técnico en Línea</span>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="max-w-container-max mx-auto px-gutter">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm p-6 md:p-10 min-h-[500px]">
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </div>
        </div>

    <?php endwhile; endif; ?>
</main>

<?php
get_footer();
