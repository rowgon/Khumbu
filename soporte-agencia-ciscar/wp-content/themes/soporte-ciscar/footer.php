<?php
/**
 * Footer Template - Agencia Ciscar Soporte
 */
?>

<!-- Footer -->
<footer class="bg-[#07111E] border-t border-white/10 py-12 text-slate-300 mt-16">
    <div class="max-w-container-max mx-auto px-gutter flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="flex items-center gap-3">
            <img src="<?php echo get_template_directory_uri(); ?>/logo-1.png" alt="Agencia Ciscar Logo" class="h-8 w-auto object-contain brightness-105">
            <span class="text-slate-600">|</span>
            <span class="text-slate-300 text-[14px] font-medium">División de Soporte y Mantenimiento Web</span>
        </div>
        
        <div class="flex flex-wrap justify-center gap-6 text-[14px]">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-slate-400 hover:text-action-orange transition-colors">Inicio</a>
            <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="text-slate-400 hover:text-action-orange transition-colors">Blog</a>
            <a href="<?php echo esc_url( home_url( '/portal-soporte/' ) ); ?>" class="text-slate-400 hover:text-action-orange transition-colors">Tickets de Soporte</a>
            <a href="https://agenciaciscar.com/" target="_blank" rel="noopener" class="text-slate-400 hover:text-action-orange transition-colors">Sitio Principal</a>
        </div>

        <div class="text-[13px] text-slate-500">
            &copy; <?php echo date('Y'); ?> Agencia Ciscar. Todos los derechos reservados.
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
});
</script>

<?php wp_footer(); ?>
</body>
</html>
