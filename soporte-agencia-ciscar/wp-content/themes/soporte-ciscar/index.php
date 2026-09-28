<?php
/**
 * Plantilla Principal (Landing de Soporte y Mantenimiento Web - Agencia Ciscar)
 */
get_header();
?>

<main>
    <!-- Hero Section -->
    <section class="relative pt-16 pb-24 md:pt-28 md:pb-36 overflow-hidden bg-surface-container-lowest">
        <div class="max-w-container-max mx-auto px-gutter md:px-margin-mobile relative z-10 grid grid-cols-1 md:grid-cols-12 gap-12 items-center">
            
            <!-- Hero Copy -->
            <div class="md:col-span-7 flex flex-col gap-6 z-20">
                <div class="inline-flex items-center gap-2 bg-surface-container-low border border-outline-variant rounded-full px-4 py-1.5 w-fit shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-trust-blue animate-pulse"></span>
                    <span class="font-medium text-[13px] text-slate-gray">Mesa de Ayuda y Mantenimiento Web 24/7</span>
                </div>
                
                <h1 class="font-display text-[34px] md:text-[54px] font-bold text-ink-black leading-[1.12] tracking-tight">
                    Tu Web Segura, Rápida y Con Soporte Técnico VIP<span class="text-action-orange">.</span>
                </h1>
                
                <p class="font-body text-[17px] md:text-[19px] text-slate-gray max-w-2xl leading-relaxed">
                    El mantenimiento técnico no es un lujo, es la base estructural de tu negocio digital. En <strong class="text-trust-blue font-semibold">Agencia Ciscar</strong> nos encargamos de la seguridad, el rendimiento y te ofrecemos atención inmediata mediante nuestro sistema de tickets.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 mt-2">
                    <a class="inline-flex items-center justify-center bg-action-orange text-white font-semibold text-[15px] px-8 py-4 rounded-md shadow-md hover:bg-action-orange/90 transition-all hover:-translate-y-0.5" href="<?php echo esc_url( home_url( '/portal-soporte/' ) ); ?>">
                        Abrir Ticket de Soporte
                        <span class="material-symbols-outlined ml-2 text-[20px]">support_agent</span>
                    </a>
                    <a class="inline-flex items-center justify-center border-2 border-trust-blue text-trust-blue font-semibold text-[15px] px-8 py-4 rounded-md hover:bg-trust-blue hover:text-white transition-all" href="#servicios">
                        Ver Servicios
                    </a>
                </div>
            </div>

            <!-- Hero Illustration / Mockup -->
            <div class="md:col-span-5 relative hidden md:block mt-8 md:mt-0">
                <div class="relative w-full aspect-square bg-gradient-to-br from-trust-blue/5 to-surface-container rounded-2xl border border-outline-variant overflow-hidden shadow-xl p-6 flex flex-col justify-center items-center">
                    
                    <div class="absolute inset-0 bg-gradient-to-br from-surface-container-highest/40 to-surface"></div>
                    <img class="absolute inset-0 w-full h-full object-cover mix-blend-multiply opacity-85 scale-105 transition-transform duration-700 hover:scale-100" alt="Servidores de alta seguridad y rendimiento Agencia Ciscar" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBVhDy1e1qnBoCq2seyGmOY0suE1XzziP7Na-_kte_F262QppoYphfWImgPj3BeFUEg7NZ-XKXdAFhbNiUek7w2BsdnE7EkJzC42LRk-8YhO1zXD9XAD7tQQFEePFdCFaUrl40d6Sz8xhYuCGS5F-HMpgzwBuHYZtMSoyQKMh6pcypztn1HhpeFfsqg3uzMHkizwoqntZMghrKDRoli48ArEf8dUuww1JRu607rFA8Y8lxkafGtcGqaqRHCO-9RaoGK6vMpPpp19nsg">
                    
                    <!-- Floating Badge 1: Seguridad -->
                    <div class="absolute top-8 -left-4 bg-white/95 backdrop-blur border border-outline-variant p-4 rounded-xl shadow-lg flex items-center gap-3.5 transition-transform hover:scale-105">
                        <div class="w-10 h-10 rounded-lg bg-trust-blue/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-trust-blue" style="font-variation-settings: 'FILL' 1;">security</span>
                        </div>
                        <div>
                            <div class="font-display font-bold text-[14px] text-ink-black">Firewall Activo</div>
                            <div class="font-medium text-[12px] text-slate-gray">Disponibilidad 99.99%</div>
                        </div>
                    </div>

                    <!-- Floating Badge 2: Rendimiento -->
                    <div class="absolute bottom-12 -right-6 bg-white/95 backdrop-blur border border-outline-variant p-4 rounded-xl shadow-lg flex items-center gap-3.5 transition-transform hover:scale-105">
                        <div class="w-10 h-10 rounded-lg bg-action-orange/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-action-orange" style="font-variation-settings: 'FILL' 1;">speed</span>
                        </div>
                        <div>
                            <div class="font-display font-bold text-[14px] text-ink-black">Velocidad Optimizada</div>
                            <div class="font-medium text-[12px] text-slate-gray">Carga &lt; 1.2s</div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Background Grid Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9IiNlN2U1ZTQiLz48L3N2Zz4=')] opacity-50 z-0"></div>
    </section>

    <!-- Services Section -->
    <section class="py-24 bg-surface" id="servicios">
        <div class="max-w-container-max mx-auto px-gutter md:px-margin-mobile">
            
            <div class="text-center mb-16 flex flex-col gap-4 items-center">
                <span class="text-action-orange font-bold text-[13px] uppercase tracking-widest font-display">Soporte Integral</span>
                <h2 class="font-display text-[28px] md:text-[36px] font-bold text-ink-black">Arquitectura de Mantenimiento</h2>
                <p class="font-body text-[16px] text-slate-gray max-w-2xl">
                    Soluciones técnicas estructuradas y monitoreo continuo para garantizar la seguridad, integridad y rendimiento ininterrumpido de tu plataforma web.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1: Seguridad -->
                <div id="seguridad" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 flex flex-col gap-5 shadow-xs hover:shadow-md hover:border-trust-blue transition-all group">
                    <div class="w-14 h-14 bg-primary-fixed rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-trust-blue group-hover:text-white transition-all text-trust-blue">
                        <span class="material-symbols-outlined text-[28px]">shield_locked</span>
                    </div>
                    <h3 class="font-display text-[22px] font-bold text-ink-black">Seguridad &amp; Anti-Malware</h3>
                    <p class="font-body text-[15px] text-slate-gray mb-2 flex-grow leading-relaxed">
                        Auditorías continuas y protección proactiva mediante firewall inteligente contra inyecciones SQL, ataques DDoS y vulnerabilidades.
                    </p>
                    <ul class="flex flex-col gap-3 border-t border-outline-variant/60 pt-5">
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-action-orange text-[20px]">check_circle</span>
                            <span class="font-medium text-[14px] text-slate-gray">Monitoreo en Tiempo Real 24/7</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-action-orange text-[20px]">check_circle</span>
                            <span class="font-medium text-[14px] text-slate-gray">Escaneo y Limpieza de Malware</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-action-orange text-[20px]">check_circle</span>
                            <span class="font-medium text-[14px] text-slate-gray">Backups Encriptados en Nube</span>
                        </li>
                    </ul>
                </div>

                <!-- Card 2: Optimización -->
                <div id="optimizacion" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 flex flex-col gap-5 shadow-xs hover:shadow-md hover:border-trust-blue transition-all group">
                    <div class="w-14 h-14 bg-primary-fixed rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-trust-blue group-hover:text-white transition-all text-trust-blue">
                        <span class="material-symbols-outlined text-[28px]">rocket_launch</span>
                    </div>
                    <h3 class="font-display text-[22px] font-bold text-ink-black">Optimización de Velocidad</h3>
                    <p class="font-body text-[15px] text-slate-gray mb-2 flex-grow leading-relaxed">
                        Refinamiento del código, configuración de caché en servidor y mantenimiento de la base de datos para tiempos de respuesta instantáneos.
                    </p>
                    <ul class="flex flex-col gap-3 border-t border-outline-variant/60 pt-5">
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-action-orange text-[20px]">check_circle</span>
                            <span class="font-medium text-[14px] text-slate-gray">Aprobación de Core Web Vitals</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-action-orange text-[20px]">check_circle</span>
                            <span class="font-medium text-[14px] text-slate-gray">Integración de Red CDN</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-action-orange text-[20px]">check_circle</span>
                            <span class="font-medium text-[14px] text-slate-gray">Optimización de Consultas SQL</span>
                        </li>
                    </ul>
                </div>

                <!-- Card 3: Actualizaciones -->
                <div id="actualizaciones" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 flex flex-col gap-5 shadow-xs hover:shadow-md hover:border-trust-blue transition-all group">
                    <div class="w-14 h-14 bg-primary-fixed rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-trust-blue group-hover:text-white transition-all text-trust-blue">
                        <span class="material-symbols-outlined text-[28px]">system_update_alt</span>
                    </div>
                    <h3 class="font-display text-[22px] font-bold text-ink-black">Actualizaciones Controladas</h3>
                    <p class="font-body text-[15px] text-slate-gray mb-2 flex-grow leading-relaxed">
                        Gestión rigurosa de versiones para el núcleo de WordPress, plugins y temas, verificando compatibilidad total sin alterar el diseño.
                    </p>
                    <ul class="flex flex-col gap-3 border-t border-outline-variant/60 pt-5">
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-action-orange text-[20px]">check_circle</span>
                            <span class="font-medium text-[14px] text-slate-gray">Pruebas en Entorno Staging</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-action-orange text-[20px]">check_circle</span>
                            <span class="font-medium text-[14px] text-slate-gray">Actualizaciones y Soporte PHP 8.x</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-action-orange text-[20px]">check_circle</span>
                            <span class="font-medium text-[14px] text-slate-gray">Resolución de Conflictos Técnicos</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-surface-container-lowest border-t border-b border-outline-variant relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0iI2U3ZTVlNCIvPjwvc3ZnPg==')] opacity-40 z-0"></div>
        
        <div class="max-w-4xl mx-auto px-gutter text-center relative z-10 flex flex-col gap-6 items-center">
            <div class="w-20 h-20 rounded-full bg-trust-blue/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-trust-blue text-[44px]" style="font-variation-settings: 'FILL' 1;">terminal</span>
            </div>
            
            <h2 class="font-display text-[30px] md:text-[46px] font-bold text-ink-black leading-tight">
                No dejes la estabilidad de tu negocio digital al azar<span class="text-action-orange">.</span>
            </h2>
            
            <p class="font-body text-[17px] md:text-[19px] text-slate-gray max-w-2xl leading-relaxed">
                Delega la complejidad técnica a los expertos de <strong class="text-trust-blue">Agencia Ciscar</strong>. Protege tu inversión, mejora tu posicionamiento SEO y asegura que tu web esté siempre activa y segura.
            </p>
            
            <div class="mt-4">
                <a class="inline-flex items-center justify-center bg-action-orange text-white font-bold text-[16px] px-10 py-5 rounded-md shadow-lg hover:bg-action-orange/90 transition-all hover:-translate-y-0.5" href="https://agenciaciscar.com/contactanos/">
                    Solicitar Diagnóstico Técnico
                    <span class="material-symbols-outlined ml-2 text-[20px]">support_agent</span>
                </a>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();

