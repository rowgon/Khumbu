/**
 * ============================================================================
 * KHUMBU B2B — ORIGINAL HTML SHORTCODES & SCRIPTS (100% FAITHFUL DESIGN)
 * ============================================================================
 * 
 * Este snippet administra los shortcodes con la estructura HTML y clases CSS
 * originales (display, serif, sol-grid, faq, finale) garantizando el diseno 
 * premium de Khumbu.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ============================================================================
 * SECCION 1: CONVERSACIONES
 * Shortcode: [khumbu_conversaciones]
 * Descripcion: Bloque numerico y llamado a accion secundario
 * ============================================================================ */
add_shortcode( 'khumbu_conversaciones', function() {
    return "<section class=\"hero\" id=\"conversaciones\">\n    <svg class=\"hero__shape\" viewBox=\"0 0 48 44\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"0.7\" aria-hidden=\"true\">\n      <path d=\"M15 5 L43 11 L25 32 Z\" />\n      <path d=\"M5 15 L33 8 L28 39 L9 31 Z\" />\n    </svg>\n    <div class=\"container hero__inner\">\n      <h2 class=\"display reveal\">Conversaciones <span class=\"serif\">reales</span> con decisores industriales.</h2>\n      <div class=\"hero__meta reveal\">\n        <span class=\"label\">+3.700 conversaciones</span>\n        <span class=\"dot\" aria-hidden=\"true\"></span>\n        <span class=\"label\">+850 reuniones comerciales</span>\n        <span class=\"dot\" aria-hidden=\"true\"></span>\n        <span class=\"label\">Cada semana</span>\n      </div>\n      <p class=\"hero__sub reveal\">Operamos tu prospección de principio a fin y tú entras solo cuando hay interés real.</p>\n      <div class=\"hero__actions reveal\">\n        <a class=\"btn-fill\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">Agenda tu diagnóstico</a>\n        <a class=\"ghost-cta\" href=\"#sistema\">Cómo funciona el sistema <span class=\"arrow\" aria-hidden=\"true\">↗</span></a>\n      </div>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 2: SISTEMA INTERACTIVO
 * Shortcode: [khumbu_sistema]
 * Descripcion: Panel grafico con simulacion de leads
 * ============================================================================ */
add_shortcode( 'khumbu_sistema', function() {
    return "<section class=\"section\" id=\"sistema\" style=\"padding-top: var(--sp-10);\">\n    <div class=\"container\">\n      <div class=\"section__head reveal\">\n        <h2 class=\"display\">El sistema, <span class=\"serif\">en</span> marcha</h2>\n        <p>Señales, conversaciones y leads entregados con contexto — todo en un mismo panel.</p>\n      </div>\n    </div>\n    <div class=\"container panel-wrap panel-wrap--xl reveal\">\n      <aside class=\"float-card float-card--tl\" aria-hidden=\"true\">\n        <span class=\"chip\"><i class=\"i-amber\"></i> Señal detectada</span>\n        <b>Reactivación de presupuestos dormidos</b>\n        Recupera presupuestos sin respuesta con el contexto de tu CRM y un mensaje que retoma la conversación donde se quedó.\n      </aside>\n      <div class=\"panel panel--xl\" role=\"img\" aria-label=\"Vista ilustrativa del panel de prospección de Khumbu\">\n        <div class=\"panel__bar\">\n          <span class=\"panel__title\">Panel Khumbu · Semana 24</span>\n          <span class=\"panel__tag\">12 conversaciones abiertas</span>\n        </div>\n        <div class=\"panel__grid\">\n          <div class=\"panel__list\">\n            <div class=\"lead-row\">\n              <div class=\"lead-row__who\"><strong>Mecanizados Alba</strong><span>Dir. de Compras · Valencia</span></div>\n              <span class=\"chip\"><i class=\"i-green\"></i> Respondió en LinkedIn</span>\n            </div>\n            <div class=\"lead-row\">\n              <div class=\"lead-row__who\"><strong>Grupo Ferroval</strong><span>Export Manager · Bilbao</span></div>\n              <span class=\"chip\"><i class=\"i-cyan\"></i> Cambió de cargo</span>\n            </div>\n            <div class=\"lead-row\">\n              <div class=\"lead-row__who\"><strong>Politermia</strong><span>Gerente · Zaragoza</span></div>\n              <span class=\"chip\"><i class=\"i-amber\"></i> Presupuesto sin respuesta 2024</span>\n            </div>\n            <div class=\"lead-row\">\n              <div class=\"lead-row__who\"><strong>Envasados Lumar</strong><span>Dir. Técnico · Murcia</span></div>\n              <span class=\"chip\"><i class=\"i-violet\"></i> Visitó tu perfil</span>\n            </div>\n            <div class=\"lead-row\">\n              <div class=\"lead-row__who\"><strong>Aceros Cantábrico</strong><span>CEO · Santander</span></div>\n              <span class=\"chip\"><i class=\"i-pink\"></i> Expositor confirmado en feria</span>\n            </div>\n            <div class=\"lead-row\">\n              <div class=\"lead-row__who\"><strong>Frimavol</strong><span>Dir. Comercial · Girona</span></div>\n              <span class=\"chip\"><i class=\"i-cyan\"></i> Abre planta nueva</span>\n            </div>\n            <div class=\"lead-row\">\n              <div class=\"lead-row__who\"><strong>Talleres Urbión</strong><span>Gerente · Soria</span></div>\n              <span class=\"chip\"><i class=\"i-green\"></i> Pide llamada</span>\n            </div>\n          </div>\n          <div class=\"panel__detail\">\n            <h4>Mecanizados Alba</h4>\n            <p class=\"role\">Dir. de Compras · Fabricante de componentes · 50-100 empleados</p>\n            <p class=\"sig\"><span class=\"chip\"><i class=\"i-accent\"></i> Interés real: pide más información</span></p>\n            <div class=\"panel__seq\">\n              <div class=\"seq-step\"><b>1.</b> Conexión con nota contextual — aceptada</div>\n              <div class=\"seq-step\"><b>2.</b> Mensaje: caso de un fabricante de su nicho</div>\n              <div class=\"seq-step\"><b>3.</b> Pregunta sobre plazos de implantación</div>\n              <div class=\"seq-step\"><b>4.</b> Respuesta recibida → entrega con contexto a tu equipo</div>\n            </div>\n            <span class=\"panel__send\">Entregar a comercial →</span>\n          </div>\n        </div>\n      </div>\n      <aside class=\"float-card float-card--br\" aria-hidden=\"true\">\n        <span class=\"chip\"><i class=\"i-pink\"></i> Ferias</span>\n        <b>Agenda antes de pisar el pabellón</b>\n        Detectamos expositores y visitantes de tu nicho y llegamos con reuniones cerradas antes de que empiece la feria.\n      </aside>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 3: TICKER DE OPORTUNIDADES
 * Shortcode: [khumbu_ticker]
 * Descripcion: Carrusel continuo de eventos y senales del mercado
 * ============================================================================ */
add_shortcode( 'khumbu_ticker', function() {
    return "<section class=\"ticker-section\" aria-label=\"Ejemplos de señales de oportunidad\">\n    <h2 class=\"display reveal\">Ninguna oportunidad <span class=\"serif\">se escapa</span></h2>\n    <div class=\"ticker ticker--l\" aria-hidden=\"true\">\n      <span class=\"chip\"><i class=\"i-cyan\"></i> Un decisor de tu nicho cambió de cargo</span>\n      <span class=\"chip\"><i class=\"i-amber\"></i> Presupuesto de 2024 sigue sin respuesta</span>\n      <span class=\"chip\"><i class=\"i-green\"></i> Tu prospecto abre planta nueva en Valencia</span>\n      <span class=\"chip\"><i class=\"i-pink\"></i> Expositores confirmados para la próxima feria de tu sector</span>\n      <span class=\"chip\"><i class=\"i-violet\"></i> Un gerente visitó tu perfil de LinkedIn</span>\n      <span class=\"chip\"><i class=\"i-cyan\"></i> Un decisor de tu nicho cambió de cargo</span>\n      <span class=\"chip\"><i class=\"i-amber\"></i> Presupuesto de 2024 sigue sin respuesta</span>\n      <span class=\"chip\"><i class=\"i-green\"></i> Tu prospecto abre planta nueva en Valencia</span>\n      <span class=\"chip\"><i class=\"i-pink\"></i> Expositores confirmados para la próxima feria de tu sector</span>\n      <span class=\"chip\"><i class=\"i-violet\"></i> Un gerente visitó tu perfil de LinkedIn</span>\n    </div>\n    <div class=\"ticker ticker--r\" aria-hidden=\"true\">\n      <span class=\"chip\"><i class=\"i-green\"></i> Ex-cliente vuelve a interactuar con tu contenido</span>\n      <span class=\"chip\"><i class=\"i-violet\"></i> Empresa de tu nicho publica oferta de compras</span>\n      <span class=\"chip\"><i class=\"i-amber\"></i> Contacto de feria del año pasado sin trabajar</span>\n      <span class=\"chip\"><i class=\"i-pink\"></i> Tu competidor sube precios</span>\n      <span class=\"chip\"><i class=\"i-cyan\"></i> Distribuidor busca nueva marca para su catálogo</span>\n      <span class=\"chip\"><i class=\"i-green\"></i> Ex-cliente vuelve a interactuar con tu contenido</span>\n      <span class=\"chip\"><i class=\"i-violet\"></i> Empresa de tu nicho publica oferta de compras</span>\n      <span class=\"chip\"><i class=\"i-amber\"></i> Contacto de feria del año pasado sin trabajar</span>\n      <span class=\"chip\"><i class=\"i-pink\"></i> Tu competidor sube precios</span>\n      <span class=\"chip\"><i class=\"i-cyan\"></i> Distribuidor busca nueva marca para su catálogo</span>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 4: COPILOT / CRM CONTEXTUAL
 * Shortcode: [khumbu_copilot]
 * Descripcion: Demostracion de contexto de leads para integracion CRM
 * ============================================================================ */
add_shortcode( 'khumbu_copilot', function() {
    return "<section class=\"section\">\n    <div class=\"container copilot reveal\">\n      <div>\n        <h2 class=\"display\">Cada lead llega <span class=\"serif\">con</span> contexto</h2>\n        <p>Tu equipo no recibe un nombre y un teléfono: recibe la conversación entera, la señal que la abrió y el siguiente paso recomendado.</p>\n        <a class=\"ghost-cta\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">Ver el sistema en detalle <span class=\"arrow\" aria-hidden=\"true\">↗</span></a>\n      </div>\n      <div class=\"panel\" role=\"img\" aria-label=\"Vista ilustrativa de un lead entregado con contexto\">\n        <div class=\"panel__bar\">\n          <span class=\"panel__title\">Lead cualificado · Grupo Ferroval</span>\n          <span class=\"panel__tag\">Entregado hoy</span>\n        </div>\n        <div class=\"panel__detail\">\n          <h4>Export Manager · Grupo Ferroval</h4>\n          <p class=\"role\">Componentes y metal · Bilbao · Exporta a Francia y Alemania</p>\n          <p class=\"sig\" style=\"display:flex; gap:0.5rem; flex-wrap:wrap;\">\n            <span class=\"chip\"><i class=\"i-cyan\"></i> Señal: nuevo cargo hace 3 semanas</span>\n            <span class=\"chip\"><i class=\"i-green\"></i> Respondió: “¿me pasas más info?”</span>\n          </p>\n          <div class=\"panel__seq\">\n            <div class=\"seq-step\"><b>›</b> Resumen de la conversación de LinkedIn (6 mensajes)</div>\n            <div class=\"seq-step\"><b>›</b> Qué le interesa: reducir dependencia de un solo proveedor</div>\n            <div class=\"seq-step\"><b>›</b> Paso recomendado: llamada corta esta semana + caso Bidegain</div>\n          </div>\n          <span class=\"panel__send\">Abrir en tu CRM →</span>\n        </div>\n      </div>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 5: CAPAS DE PLATAFORMA
 * Shortcode: [khumbu_plataforma]
 * Descripcion: Modulo interactivo de 4 capas: Senales, Multicanal, Contenido y CRM
 * ============================================================================ */
add_shortcode( 'khumbu_plataforma', function() {
    return "<section class=\"section\" id=\"plataforma\" style=\"padding-top: 0;\">\n    <div class=\"container\">\n      <div class=\"section__head reveal\">\n        <h2 class=\"display\">Todo el sistema, <span class=\"serif\">un solo</span> equipo</h2>\n        <p>De la señal al lead entregado. Toca cada capa para ver qué hace.</p>\n      </div>\n      <div class=\"stack reveal\" id=\"stack\">\n        <p class=\"annot\" aria-hidden=\"true\">las cuatro capas del sistema\n          <svg width=\"34\" height=\"26\" viewBox=\"0 0 34 26\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.2\"><path d=\"M30 2 C22 14 16 18 4 21 M4 21 l7 -2 M4 21 l5 4\"/></svg>\n        </p>\n        <ul class=\"stack__back\" id=\"stackBack\"></ul>\n        <div class=\"stack__front\">\n          <!-- Capa: señales -->\n          <div class=\"layer-pane\" data-layer=\"0\">\n            <div>\n              <span class=\"tab-label\"><i class=\"ldot i-accent\"></i> Señales y detección de decisores</span>\n              <h3>Mejores datos, más conversaciones</h3>\n              <p>Localizamos a los decisores reales de tu nicho industrial y las señales que indican que es el momento de hablar.</p>\n            </div>\n            <div class=\"icp\" role=\"img\" aria-label=\"Ejemplo ilustrativo de búsqueda de cliente ideal\">\n              <p class=\"icp__q\">¿Cómo es tu cliente ideal?</p>\n              <div class=\"icp__search\">Directores de compras de fabricantes de packaging que exportan a Europa…</div>\n              <p class=\"icp__hint\">Segmentos sugeridos</p>\n              <div class=\"icp__grid\">\n                <div class=\"icp__card\"><b>Fabricantes de maquinaria</b>Con oficina técnica propia y ciclo de venta consultivo.</div>\n                <div class=\"icp__card\"><b>Exportadoras en crecimiento</b>Abriendo mercado en Francia, Alemania o Portugal.</div>\n                <div class=\"icp__card\"><b>Ingenierías industriales</b>Que prescriben o compran componentes a medida.</div>\n                <div class=\"icp__card\"><b>Software industrial</b>Vendiendo a planta y a dirección de operaciones.</div>\n              </div>\n            </div>\n          </div>\n          <!-- Capa: multicanal -->\n          <div class=\"layer-pane\" data-layer=\"1\" hidden>\n            <div>\n              <span class=\"tab-label\"><i class=\"ldot i-violet\"></i> Prospección multicanal</span>\n              <h3>Donde está tu cliente, estamos nosotros</h3>\n              <p>LinkedIn, email y teléfono trabajando en secuencia — cada contacto por el canal donde responde.</p>\n            </div>\n            <div class=\"icp\" role=\"img\" aria-label=\"Ejemplo ilustrativo de secuencia multicanal\">\n              <p class=\"icp__q\">Secuencia tipo · Dir. de Compras</p>\n              <div class=\"panel__seq\" style=\"margin-top: var(--sp-2); border: none; padding-top: 0;\">\n                <div class=\"seq-step\"><b>D1</b> LinkedIn · conexión con nota contextual</div>\n                <div class=\"seq-step\"><b>D3</b> LinkedIn · mensaje con caso de su nicho</div>\n                <div class=\"seq-step\"><b>D6</b> Email · resumen + material técnico</div>\n                <div class=\"seq-step\"><b>D10</b> Teléfono · llamada corta de cierre</div>\n              </div>\n              <p class=\"icp__hint\" style=\"margin-top: var(--sp-2);\">Resultados de referencia</p>\n              <div style=\"display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.6rem;\">\n                <span class=\"chip\"><i class=\"i-violet\"></i> Aceptación LinkedIn 20-65%</span>\n                <span class=\"chip\"><i class=\"i-cyan\"></i> Respuesta 15-42%</span>\n              </div>\n            </div>\n          </div>\n          <!-- Capa: contenido -->\n          <div class=\"layer-pane\" data-layer=\"2\" hidden>\n            <div>\n              <span class=\"tab-label\"><i class=\"ldot i-green\"></i> Contenido y posicionamiento</span>\n              <h3>Tu marca, delante de los decisores</h3>\n              <p>Perfiles optimizados y contenido que trabaja para la venta: cada publicación abre la puerta al primer mensaje.</p>\n            </div>\n            <div class=\"icp\" role=\"img\" aria-label=\"Ejemplo ilustrativo de contenido publicado\">\n              <p class=\"icp__q\">Publicado esta semana</p>\n              <div class=\"icp__grid\" style=\"grid-template-columns: minmax(0,1fr); margin-top: var(--sp-2);\">\n                <div class=\"icp__card\"><b>Post técnico · caso real</b>“Cómo un fabricante de packaging redujo un 30% el tiempo de presupuesto” — visto por decisores de tu nicho.</div>\n                <div class=\"icp__card\"><b>Perfil comercial optimizado</b>Tu equipo aparece como especialista del sector, no como vendedor a puerta fría.</div>\n              </div>\n              <p class=\"icp__hint\" style=\"margin-top: var(--sp-2);\">Apertura de email de referencia</p>\n              <div style=\"display:flex; gap:0.5rem; margin-top:0.6rem;\">\n                <span class=\"chip\"><i class=\"i-green\"></i> 35-47% de apertura</span>\n              </div>\n            </div>\n          </div>\n          <!-- Capa: CRM -->\n          <div class=\"layer-pane\" data-layer=\"3\" hidden>\n            <div>\n              <span class=\"tab-label\"><i class=\"ldot i-cyan\"></i> CRM e inteligencia comercial</span>\n              <h3>Nada se pierde por el camino</h3>\n              <p>Cada conversación, señal y oportunidad queda registrada. El pipeline deja de ser una caja negra.</p>\n            </div>\n            <div class=\"icp\" role=\"img\" aria-label=\"Ejemplo ilustrativo de pipeline en CRM\">\n              <p class=\"icp__q\">Tu pipeline, en vivo</p>\n              <div class=\"pipe\" aria-hidden=\"true\">\n                <div class=\"pipe__col\"><b>Conversación</b><span>12</span></div>\n                <div class=\"pipe__col\"><b>Interés real</b><span>5</span></div>\n                <div class=\"pipe__col\"><b>Reunión</b><span>3</span></div>\n                <div class=\"pipe__col pipe__col--hot\"><b>Propuesta</b><span>2</span></div>\n              </div>\n              <p class=\"icp__hint\" style=\"margin-top: var(--sp-2);\">Y cuando algo se enfría…</p>\n              <div style=\"display:flex; gap:0.5rem; margin-top:0.6rem;\">\n                <span class=\"chip\"><i class=\"i-amber\"></i> Reactivación automática a los 90 días</span>\n              </div>\n            </div>\n          </div>\n        </div>\n      </div>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 6: CATALOGO DE SOLUCIONES
 * Shortcode: [khumbu_soluciones]
 * Descripcion: Grilla con las 8 soluciones B2B originales
 * ============================================================================ */
add_shortcode( 'khumbu_soluciones', function() {
    return "<section class=\"section\" id=\"soluciones\" style=\"padding-top: 0;\">\n    <div class=\"container\">\n      <div class=\"section__head reveal\">\n        <h2 class=\"display\">Las <span class=\"serif\">soluciones</span> Khumbu</h2>\n        <p>Se entra por la prospección. Se crece por donde tu empresa lo necesite.</p>\n      </div>\n      <div class=\"sol-grid reveal\">\n        <a class=\"sol\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"sol__num\">01</span>\n          <div><h3>SDR Industrial</h3><p>Cualificación de leads con método BANT y entrega a tu equipo solo cuando hay interés real.</p></div>\n          <span class=\"sol__arrow\" aria-hidden=\"true\">↗</span>\n        </a>\n        <a class=\"sol\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"sol__num\">02</span>\n          <div><h3>ABM Industrial</h3><p>Cuentas estratégicas trabajadas una a una: los clientes que de verdad quieres firmar.</p></div>\n          <span class=\"sol__arrow\" aria-hidden=\"true\">↗</span>\n        </a>\n        <a class=\"sol\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"sol__num\">03</span>\n          <div><h3>Prospección para Ferias</h3><p>Reuniones cerradas antes de la feria y seguimiento de cada contacto después.</p></div>\n          <span class=\"sol__arrow\" aria-hidden=\"true\">↗</span>\n        </a>\n        <a class=\"sol\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"sol__num\">04</span>\n          <div><h3>Automatización</h3><p>Recupera horas de venta automatizando los procesos comerciales que roban tiempo a tu equipo.</p></div>\n          <span class=\"sol__arrow\" aria-hidden=\"true\">↗</span>\n        </a>\n        <a class=\"sol\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"sol__num\">05</span>\n          <div><h3>CRM Industrial</h3><p>Un CRM montado para venta industrial: pipeline claro, seguimiento sin fugas.</p></div>\n          <span class=\"sol__arrow\" aria-hidden=\"true\">↗</span>\n        </a>\n        <a class=\"sol\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"sol__num\">06</span>\n          <div><h3>Comunicación Corporativa</h3><p>Contenido y materiales que posicionan tu marca ante los decisores de tu nicho.</p></div>\n          <span class=\"sol__arrow\" aria-hidden=\"true\">↗</span>\n        </a>\n        <a class=\"sol\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"sol__num\">07</span>\n          <div><h3>Prospección Internacional</h3><p>Abre mercado y encuentra distribuidores en otros países, en su idioma y con su cultura.</p></div>\n          <span class=\"sol__arrow\" aria-hidden=\"true\">↗</span>\n        </a>\n        <a class=\"sol sol--featured\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"sol__num\">★</span>\n          <div><h3>El Departamento completo</h3><p>Marketing y ventas industrial integral: el sistema entero trabajando como tu departamento.</p></div>\n          <span class=\"sol__arrow\" aria-hidden=\"true\">↗</span>\n        </a>\n      </div>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 7: AUDIENCIAS POR ROL
 * Shortcode: [khumbu_audiencias]
 * Descripcion: Pestanas interactivas para Gerencia, Comercial, Export, Mkt y Ventas
 * ============================================================================ */
add_shortcode( 'khumbu_audiencias', function() {
    return "<section class=\"section\" style=\"padding-top: 0;\">\n    <div class=\"container\">\n      <div class=\"section__head reveal\">\n        <h2 class=\"display\">Hecho para <span class=\"serif\">quien</span> vende industria</h2>\n        <p>Cada rol sube la montaña por una cara distinta. Toca el tuyo.</p>\n      </div>\n      <div class=\"audience reveal\" id=\"audiencias\" role=\"tablist\" aria-label=\"Elige tu rol\">\n        <button class=\"aud-card\" role=\"tab\" aria-selected=\"false\" aria-controls=\"audPanel\" data-aud=\"0\">\n          <h3>Gerencia</h3>\n          <svg viewBox=\"0 0 100 70\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.5\" aria-hidden=\"true\">\n            <path d=\"M5 62 L38 22 L52 38 L72 10 L95 62\" />\n            <circle class=\"spark spark--fill\" cx=\"72\" cy=\"10\" r=\"2.5\" fill=\"currentColor\" stroke=\"none\" />\n          </svg>\n        </button>\n        <button class=\"aud-card\" role=\"tab\" aria-selected=\"false\" aria-controls=\"audPanel\" data-aud=\"1\">\n          <h3>Dirección Comercial</h3>\n          <svg viewBox=\"0 0 100 70\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.5\" aria-hidden=\"true\">\n            <path d=\"M5 62 L35 30 L55 45 L80 12 L95 62\" />\n            <path class=\"spark\" d=\"M10 58 C30 50 35 38 48 40 C62 42 66 24 78 15\" stroke-dasharray=\"3 4\" />\n            <circle class=\"spark spark--fill\" cx=\"78\" cy=\"14\" r=\"2.5\" fill=\"currentColor\" stroke=\"none\" />\n          </svg>\n        </button>\n        <button class=\"aud-card\" role=\"tab\" aria-selected=\"false\" aria-controls=\"audPanel\" data-aud=\"2\">\n          <h3>Export Managers</h3>\n          <svg viewBox=\"0 0 100 70\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.5\" aria-hidden=\"true\">\n            <path d=\"M5 62 L30 25 L48 42 L68 18 L95 62\" />\n            <path class=\"spark\" d=\"M20 20 C40 8 60 8 84 20\" stroke-dasharray=\"2 4\" />\n            <circle class=\"spark spark--fill\" cx=\"84\" cy=\"20\" r=\"2.5\" fill=\"currentColor\" stroke=\"none\" />\n          </svg>\n        </button>\n        <button class=\"aud-card\" role=\"tab\" aria-selected=\"false\" aria-controls=\"audPanel\" data-aud=\"3\">\n          <h3>Marketing Industrial</h3>\n          <svg viewBox=\"0 0 100 70\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.5\" aria-hidden=\"true\">\n            <path d=\"M5 62 L40 18 L60 40 L78 25 L95 62\" />\n            <path class=\"spark\" d=\"M40 18 L40 6 L52 10 L40 13\" fill=\"none\" />\n          </svg>\n        </button>\n        <button class=\"aud-card\" role=\"tab\" aria-selected=\"false\" aria-controls=\"audPanel\" data-aud=\"4\">\n          <h3>Ventas / SDR</h3>\n          <svg viewBox=\"0 0 100 70\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.5\" aria-hidden=\"true\">\n            <path d=\"M5 62 L28 35 L45 48 L70 15 L95 62\" />\n            <path class=\"spark\" d=\"M62 26 L78 10 M70 28 L84 16\" />\n          </svg>\n        </button>\n      </div>\n      <div class=\"aud-panel\" id=\"audPanel\" hidden>\n        <div class=\"aud-panel__inner\">\n          <div class=\"aud-panel__body\">\n            <p class=\"label aud-panel__label\"></p>\n            <p class=\"aud-panel__situation\"></p>\n            <ul class=\"aud-panel__wins\"></ul>\n            <a class=\"ghost-cta\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">Hablemos de tu caso <span class=\"arrow\" aria-hidden=\"true\">↗</span></a>\n          </div>\n        </div>\n      </div>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 8: EQUIPO KHUMBU
 * Shortcode: [khumbu_equipo]
 * Descripcion: Carrusel continuo con fotos reales de los 10 miembros del equipo
 * ============================================================================ */
add_shortcode( 'khumbu_equipo', function() {
    return "<section class=\"section team-section\" id=\"equipo\" style=\"padding-top: 0;\">\n    <div class=\"container\">\n      <div class=\"section__head reveal\">\n        <h2 class=\"display\">Los <span class=\"serif\">guías</span> de tu expedición</h2>\n        <p>Detrás del sistema hay un equipo que conoce el terreno. Estas son las personas que suben contigo.</p>\n      </div>\n    </div>\n    <div class=\"team-marquee reveal\" aria-label=\"Equipo Khumbu\">\n      <div class=\"team-track\">\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/aitor.jpg\" alt=\"Aitor\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"aitor\" /><figcaption><b>Aitor</b><span>CEO</span></figcaption></figure>\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/sebas.jpg\" alt=\"Sebas\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"sebas\" /><figcaption><b>Sebas</b><span>Account Manager</span></figcaption></figure>\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/mar.jpg\" alt=\"Mar\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"mar\" /><figcaption><b>Mar</b><span>Administración</span></figcaption></figure>\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/joaquim.jpg\" alt=\"Joaquín\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"joaquim\" /><figcaption><b>Joaquín</b><span>Copy</span></figcaption></figure>\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/andrea.jpg\" alt=\"Andrea\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"andrea\" /><figcaption><b>Andrea</b><span>Community Manager</span></figcaption></figure>\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/roman.jpg\" alt=\"Román\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"roman\" /><figcaption><b>Román</b><span>Sistemas</span></figcaption></figure>\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/mar-mateo.jpg\" alt=\"Mar Mateo\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"mar-mateo\" /><figcaption><b>Mar Mateo</b><span>Comercial</span></figcaption></figure>\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/diego.jpg\" alt=\"Diego\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"diego\" /><figcaption><b>Diego</b><span>Prospección</span></figcaption></figure>\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/moises.jpg\" alt=\"Moisés\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"moises\" /><figcaption><b>Moisés</b><span>Diseño</span></figcaption></figure>\n        <figure class=\"guide\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/leander.jpg\" alt=\"Leander\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"leander\" /><figcaption><b>Leander</b><span>SEO</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/aitor.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"aitor\" /><figcaption><b>Aitor</b><span>CEO</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/sebas.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"sebas\" /><figcaption><b>Sebas</b><span>Account Manager</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/mar.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"mar\" /><figcaption><b>Mar</b><span>Administración</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/joaquim.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"joaquim\" /><figcaption><b>Joaquín</b><span>Copy</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/andrea.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"andrea\" /><figcaption><b>Andrea</b><span>Community Manager</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/roman.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"roman\" /><figcaption><b>Román</b><span>Sistemas</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/mar-mateo.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"mar-mateo\" /><figcaption><b>Mar Mateo</b><span>Comercial</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/diego.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"diego\" /><figcaption><b>Diego</b><span>Prospección</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/moises.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"moises\" /><figcaption><b>Moisés</b><span>Diseño</span></figcaption></figure>\n        <figure class=\"guide\" aria-hidden=\"true\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/leander.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"640\" data-name=\"leander\" /><figcaption><b>Leander</b><span>SEO</span></figcaption></figure>\n      </div>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 9: CASOS DE EXITO
 * Shortcode: [khumbu_casos]
 * Descripcion: Fichas de clientes con modal de video
 * ============================================================================ */
add_shortcode( 'khumbu_casos', function() {
    return "<section class=\"section deck-section\" id=\"casos\">\n    <div class=\"container\">\n      <div class=\"section__head reveal\">\n        <h2 class=\"display\">Ellos ya <span class=\"serif\">han</span> subido</h2>\n        <p>Cifras con fuente interna y clientes que lo cuentan en vídeo. Toca una ficha y escúchalos.</p>\n      </div>\n      <div class=\"deck reveal\">\n        <button class=\"fcase\" data-yt=\"yNctZof4OVs\">\n          <span class=\"fcase__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/t-proandre.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"fcase__play\" aria-hidden=\"true\">▶</span></span>\n          <span class=\"fcase__body\">\n            <h3>Bidegain</h3>\n            <span class=\"who\">Unai Dambolonea · International Sales Manager</span>\n            <span class=\"fcase__figs\">\n              <span><b>56</b><i>leads cualificados</i></span>\n              <span><b>265</b><i>respuestas</i></span>\n              <span><b>+839</b><i>seguidores</i></span>\n            </span>\n            <span class=\"window\">Primeros 90 días · ▶ Escucha su experiencia</span>\n          </span>\n        </button>\n        <button class=\"fcase\" data-yt=\"yNctZof4OVs\">\n          <span class=\"fcase__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/t-proandre.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"fcase__play\" aria-hidden=\"true\">▶</span></span>\n          <span class=\"fcase__body\">\n            <h3>Proandre</h3>\n            <span class=\"who\">Andrea Cerezalez · Directora de Ventas Internacional</span>\n            <span class=\"fcase__figs\">\n              <span><b>46</b><i>leads cualificados</i></span>\n              <span><b>373</b><i>respuestas</i></span>\n              <span><b>+830</b><i>seguidores</i></span>\n            </span>\n            <span class=\"window\">Primeros 90 días · ▶ Escucha su experiencia</span>\n          </span>\n        </button>\n        <button class=\"fcase\" data-yt=\"yNctZof4OVs\">\n          <span class=\"fcase__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/t-proandre.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"fcase__play\" aria-hidden=\"true\">▶</span></span>\n          <span class=\"fcase__body\">\n            <h3>Gladtolink</h3>\n            <span class=\"who\">Software industrial</span>\n            <span class=\"fcase__figs\">\n              <span><b>+135%</b><i>contactos</i></span>\n              <span><b>299</b><i>conversaciones</i></span>\n              <span><b>38,9%</b><i>aceptación</i></span>\n            </span>\n            <span class=\"window\">Campaña LinkedIn · ▶ Escucha su experiencia</span>\n          </span>\n        </button>\n        <button class=\"fcase\" data-yt=\"yNctZof4OVs\">\n          <span class=\"fcase__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/t-proandre.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"fcase__play\" aria-hidden=\"true\">▶</span></span>\n          <span class=\"fcase__body\">\n            <h3>Tecnoterrazas</h3>\n            <span class=\"who\">La prueba de retención</span>\n            <span class=\"fcase__figs fcase__figs--one\">\n              <span><b>~6 años</b><i>de cordada, y seguimos subiendo</i></span>\n            </span>\n            <span class=\"window\">Cliente desde 2020 · ▶ Escucha su experiencia</span>\n          </span>\n        </button>\n      </div>\n      <p class=\"deck__claim reveal\">No damos mapas. <span class=\"serif\">Te acompañamos en la subida.</span></p>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 10: CUADERNO DE RUTA / BLOG
 * Shortcode: [khumbu_blog]
 * Descripcion: Tarjetas de articulos del blog Khumbu
 * ============================================================================ */
add_shortcode( 'khumbu_blog', function() {
    return "<section class=\"section\" id=\"blog\">\n    <div class=\"container\">\n      <div class=\"section__head reveal\">\n        <h2 class=\"display\">Cuaderno <span class=\"serif\">de</span> ruta</h2>\n        <p>Lo que aprendemos vendiendo industria, contado sin humo.</p>\n      </div>\n      <div class=\"postcards reveal\">\n        <a class=\"postcard\" href=\"https://khumbu.pro/captar-clientes-corporativos-en-industria-b2b/\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"postcard__img\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/post-1.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"427\" /></span>\n          <span class=\"postcard__num\">01</span>\n          <h3>Captar clientes corporativos en industria B2B</h3>\n          <span class=\"postcard__go label\">Leer ↗</span>\n        </a>\n        <a class=\"postcard\" href=\"https://khumbu.pro/estrategia-de-marketing-b2b-industrial-desde-cero/\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"postcard__img\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/post-2.jpg\" alt=\"\" loading=\"lazy\" width=\"640\" height=\"427\" /></span>\n          <span class=\"postcard__num\">02</span>\n          <h3>Estrategia de marketing B2B industrial desde cero</h3>\n          <span class=\"postcard__go label\">Leer ↗</span>\n        </a>\n        <a class=\"postcard\" href=\"https://khumbu.pro/plan-editorial-b2b-para-septiembre-contenidos-q3/\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"postcard__img postcard__img--brand\" aria-hidden=\"true\">\n            <img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/icono-color2.svg\" alt=\"\" loading=\"lazy\" style=\"width: 30%;\" />\n          </span>\n          <span class=\"postcard__num\">03</span>\n          <h3>Plan editorial B2B para septiembre: contenidos Q3</h3>\n          <span class=\"postcard__go label\">Leer ↗</span>\n        </a>\n      </div>\n      <p class=\"posts__more reveal\"><a class=\"ghost-cta\" href=\"https://khumbu.pro/blog/\" target=\"_blank\" rel=\"noopener\">Ver todo el blog <span class=\"arrow\" aria-hidden=\"true\">↗</span></a></p>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 11: PODCAST PROANDRE
 * Shortcode: [khumbu_podcast]
 * Descripcion: Seccion con los 6 episodios destacados del podcast
 * ============================================================================ */
add_shortcode( 'khumbu_podcast', function() {
    return "<section class=\"section podcast-xl\" id=\"podcast\" style=\"padding-top: 0;\">\n    <div class=\"container\">\n      <div class=\"podcast-xl__head reveal\">\n        <div class=\"podcast__eq podcast__eq--big\" aria-hidden=\"true\"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>\n        <p class=\"label\">El podcast de Khumbu</p>\n        <h2 class=\"podcast-xl__title\">Campamentos <span class=\"serif\">base</span></h2>\n        <p class=\"podcast-xl__sub\">Conversaciones sobre venta y marketing industrial con gente que está en la pared. Sin teoría de despacho.</p>\n      </div>\n      <div class=\"episodes reveal\">\n        <a class=\"episode\" href=\"https://www.youtube.com/watch?v=oubEB2Wz7RA\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"episode__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/ep-oubEB2Wz7RA.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"episode__play\" aria-hidden=\"true\">▶</span></span>\n          <h3>El coste real de parar una fábrica y cómo vender calidad frente a precio · Mecatronic</h3>\n        </a>\n        <a class=\"episode\" href=\"https://www.youtube.com/watch?v=RvaFNZirk8M\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"episode__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/ep-RvaFNZirk8M.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"episode__play\" aria-hidden=\"true\">▶</span></span>\n          <h3>De los datos al beneficio: cómo digitalizar tu fábrica sin romper nada · Proyingel</h3>\n        </a>\n        <a class=\"episode\" href=\"https://www.youtube.com/watch?v=wDl75cUsFps\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"episode__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/ep-wDl75cUsFps.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"episode__play\" aria-hidden=\"true\">▶</span></span>\n          <h3>Cómo hacer que tu competencia colabore contigo · Clúster Envase y Embalaje</h3>\n        </a>\n        <a class=\"episode\" href=\"https://www.youtube.com/watch?v=xa5n9ullJ-U\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"episode__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/ep-xa5n9ullJ-U.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"episode__play\" aria-hidden=\"true\">▶</span></span>\n          <h3>Cómo reducir costes logísticos sin destrozar tu mercancía · Embalpack</h3>\n        </a>\n        <a class=\"episode\" href=\"https://www.youtube.com/watch?v=r3-cHRuRvhM\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"episode__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/ep-r3-cHRuRvhM.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"episode__play\" aria-hidden=\"true\">▶</span></span>\n          <h3>Cómo vender a empresas que dicen “siempre lo hemos hecho así” · Agromarketing</h3>\n        </a>\n        <a class=\"episode\" href=\"https://www.youtube.com/watch?v=dDWpODjoP4c\" target=\"_blank\" rel=\"noopener\">\n          <span class=\"episode__thumb\"><img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/ep-dDWpODjoP4c.jpg\" alt=\"\" loading=\"lazy\" width=\"480\" height=\"360\" /><span class=\"episode__play\" aria-hidden=\"true\">▶</span></span>\n          <h3>Por qué tu cliente elige el presupuesto más bajo (y cómo evitarlo) · Tecnoterrazas</h3>\n        </a>\n      </div>\n      <p class=\"podcast-xl__cta reveal\"><a class=\"btn-outline\" href=\"https://www.youtube.com/@khumbupro\" target=\"_blank\" rel=\"noopener\">Visita el podcast <span aria-hidden=\"true\">↗</span></a></p>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 12: CTA FINAL
 * Shortcode: [khumbu_finale]
 * Descripcion: Llamado al diagnostico final Subimos?
 * ============================================================================ */
add_shortcode( 'khumbu_finale', function() {
    return "<section class=\"finale\" id=\"diagnostico\">\n    <svg class=\"finale__shape\" viewBox=\"0 0 48 44\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"0.7\" aria-hidden=\"true\">\n      <path d=\"M15 5 L43 11 L25 32 Z\" />\n      <path d=\"M5 15 L33 8 L28 39 L9 31 Z\" />\n    </svg>\n    <div class=\"container\">\n      <h2 class=\"display reveal\">¿Subimos? <span class=\"dot\" style=\"width: 0.22em; height: 0.22em;\" aria-hidden=\"true\"></span></h2>\n      <p class=\"reveal\">El diagnóstico es una reunión de trabajo, no una llamada de ventas: revisamos tu mercado, tus decisores localizables y qué puede esperar tu empresa de la prospección.</p>\n      <div class=\"finale__actions reveal\">\n        <a class=\"btn-fill\" href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">Agenda tu diagnóstico</a>\n        <a class=\"ghost-cta\" href=\"#casos\">Escucha a los clientes primero <span class=\"arrow\" aria-hidden=\"true\">↗</span></a>\n      </div>\n      <p class=\"note reveal\">Sin permanencia · Sin compromiso</p>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 13: FAQ ACORDEON ORIGINAL
 * Shortcode: [khumbu_faq]
 * Descripcion: Preguntas frecuentes originales con estilo dark
 * ============================================================================ */
add_shortcode( 'khumbu_faq', function() {
    return "<section class=\"section\" style=\"padding-top: 0;\">\n    <div class=\"container\">\n      <div class=\"faq\">\n        <details>\n          <summary>¿Qué es Khumbu y qué hace exactamente?</summary>\n          <p>Khumbu es un sistema de prospección para empresas industriales B2B. Posicionamos tu marca, detectamos a tus decisores y generamos conversaciones comerciales por LinkedIn y email. Tu equipo entra solo cuando hay interés real.</p>\n        </details>\n        <details>\n          <summary>¿Qué diferencia hay entre Khumbu y una agencia?</summary>\n          <p>Una agencia entrega campañas y espera. Nosotros operamos un sistema completo de prospección orientado a conversaciones con decisores, con acompañamiento continuo.</p>\n        </details>\n        <details>\n          <summary>¿Cuánto se tarda en ver las primeras conversaciones?</summary>\n          <p>El sistema queda operativo en 9 días desde la firma. Las primeras conversaciones suelen llegar en las primeras semanas, empezando por los quick wins: presupuestos sin respuesta, ex-clientes y contactos de ferias.</p>\n        </details>\n        <details>\n          <summary>¿Garantizáis ventas?</summary>\n          <p>No, y desconfía de quien lo haga. Garantizamos conversaciones reales con decisores de tu mercado, entregadas con contexto. El cierre es de tu equipo — nosotros te llevamos hasta la mesa.</p>\n        </details>\n        <details>\n          <summary>¿Trabajáis solo con empresas industriales?</summary>\n          <p>Sí. La especialización industrial es lo que hace que los mensajes, los targets y los casos funcionen. Si tu empresa no es industrial, te lo diremos en el diagnóstico.</p>\n        </details>\n      </div>\n    </div>\n  </section>";
} );

/* ============================================================================
 * SECCION 14: FOOTER OFICIAL
 * Shortcode: [khumbu_footer]
 * Descripcion: Pie de pagina institucional oficial
 * ============================================================================ */
add_shortcode( 'khumbu_footer', function() {
    return "<footer class=\"footer\">\n  <div class=\"container\">\n    <div class=\"footer__mast\">\n      <div>\n        <a class=\"brand\" href=\"#\" aria-label=\"Khumbu — inicio\">\n          <img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/logo-blanco.svg\" alt=\"Khumbu\" width=\"170\" height=\"45\" />\n        </a>\n        <p class=\"footer__tagline\">El sistema de prospección de las empresas industriales.</p>\n      </div>\n      <div class=\"footer__cols\">\n        <ul class=\"footer__links\">\n          <li><a href=\"http://localhost/Khumbu/khumbunew/soluciones/\">Soluciones</a></li>\n          <li><a href=\"http://localhost/Khumbu/khumbunew/casos/\">Casos de éxito</a></li>\n          <li><a href=\"http://localhost/Khumbu/khumbunew/blog/\">Blog</a></li>\n          <li><a href=\"http://localhost/Khumbu/khumbunew/contacto/\">Contacto</a></li>\n          <li><a href=\"https://www.youtube.com/@khumbupro\" target=\"_blank\" rel=\"noopener\">Podcast</a></li>\n          <li><a href=\"https://calendar.app.google/vrn5Vxnyyci9F7cK8\" target=\"_blank\" rel=\"noopener\">Diagnóstico</a></li>\n        </ul>\n        <ul class=\"footer__links\">\n          <li><a href=\"https://www.linkedin.com/company/khumbu-b2b-lead-factory/\" target=\"_blank\" rel=\"noopener\">LinkedIn</a></li>\n          <li><a href=\"https://www.instagram.com/khumbupro/\" target=\"_blank\" rel=\"noopener\">Instagram</a></li>\n          <li><a href=\"https://www.facebook.com/profile.php?id=61554347593263\" target=\"_blank\" rel=\"noopener\">Facebook</a></li>\n          <li><a href=\"https://www.youtube.com/channel/UCQstwCRTvAKFPTCMWuKJavQ\" target=\"_blank\" rel=\"noopener\">YouTube</a></li>\n        </ul>\n      </div>\n    </div>\n    <div class=\"footer__meta\">\n      <span>© 2026 Khumbu · khumbu.pro</span>\n      <span style=\"display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;\"><a href=\"https://khumbu.pro/fondos-next-generation-eu/\">Fondos Next Generation EU</a> · <a href=\"https://khumbu.pro/aviso-legal/\">Aviso legal</a> · <a href=\"https://khumbu.pro/politica-de-privacidad/\">Privacidad</a> · <a href=\"https://khumbu.pro/politica-de-cookies/\">Cookies</a></span>\n    </div>\n    <div style=\"text-align: center; margin-top: 24px; opacity: 0.9;\">\n      <img src=\"http://localhost/Khumbu/khumbunew/wp-content/uploads/2026/09/media__1789731154274.png\" alt=\"Financiado por la Unión Europea NextGenerationEU\" style=\"max-width: 100%; height: auto; max-height: 50px;\" />\n    </div>\n  </div>\n</footer>";
} );

/* ============================================================================
 * SCRIPTS INTERACTIVOS GLOBALES KHUMBU
 * Encola en wp_footer el motor JS de animaciones
 * ============================================================================ */
add_action( 'wp_footer', function() {
    ?>
    <script id="khumbu-interactive-core">

(function () {
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Reveal on scroll */
  if (reduced) {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('is-in'));
  } else {
    const io = new IntersectionObserver(entries => {
      for (const e of entries) if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
    }, { threshold: 0.18, rootMargin: '0px 0px -8% 0px' });
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));
  }

  /* Nav retráctil */
  const nav = document.getElementById('nav');
  let lastY = window.scrollY;
  function navTick() {
    const y = window.scrollY;
    const overCover = y < innerHeight * 0.6;
    nav.classList.toggle('nav--solid', !overCover);
    if (overCover || y < lastY) nav.classList.remove('nav--hidden');
    else if (y > lastY + 4) nav.classList.add('nav--hidden');
    lastY = y;
  }
  addEventListener('scroll', navTick, { passive: true });
  navTick();

  /* Menú móvil */
  const burger = document.getElementById('burger');
  const menu = document.getElementById('menu');
  function toggleMenu(open) {
    menu.hidden = !open;
    burger.setAttribute('aria-expanded', open);
    document.documentElement.style.overflow = open ? 'hidden' : '';
    burger.classList.toggle('burger--x', open);
  }
  burger.addEventListener('click', () => toggleMenu(menu.hidden));
  menu.addEventListener('click', e => { if (e.target.closest('a')) toggleMenu(false); });

  /* Manifiesto: knockout → naranja → zoom (nítido) → credo → palabras que viajan a columna */
  const man = document.querySelector('.manifesto');
  const knock = man.querySelector('.manifesto__knock');
  const phaseC = man.querySelector('.manifesto__phase--c');
  const zoomer = man.querySelector('.manifesto__zoomer');
  const phaseD = man.querySelector('.manifesto__phase--d');
  const phaseE = man.querySelector('.manifesto__phase--e');
  const creed = man.querySelector('.manifesto__creed');
  const fliersLayer = man.querySelector('.manifesto__fliers');
  const manVideo = man.querySelector('.manifesto__video');
  const coarse = matchMedia('(pointer: coarse)').matches;
  if (reduced) {
    man.classList.add('manifesto--static');
    knock.style.opacity = 0; phaseC.style.opacity = 0;
    phaseD.style.opacity = 1; creed.style.setProperty('--wfade', 1);
    phaseE.style.opacity = 0;
  } else {
    let manPlaying = false;
    const clamp = v => Math.min(1, Math.max(0, v));
    const ease = (a, b, p) => clamp((p - a) / (b - a));
    const lerp = (a, b, t) => a + (b - a) * t;
    const smooth = t => t * t * (3 - 2 * t);

    /* FLIP: mide origen (em del credo) y destino (span de la columna), crea viajeras */
    let fliers = [];
    function buildFliers() {
      fliersLayer.innerHTML = '';
      fliers = [];
      const stickyR = man.querySelector('.manifesto__sticky').getBoundingClientRect();
      /* medir con las capas visibles temporalmente */
      const dPrev = phaseD.style.cssText, ePrev = phaseE.style.cssText;
      phaseD.style.opacity = 1; phaseD.style.transform = 'none';
      phaseE.style.opacity = 1; phaseE.style.transform = 'none';
      creed.querySelectorAll('em[data-w]').forEach(em => {
        const i = em.dataset.w;
        const target = phaseE.querySelector('span[data-w="' + i + '"]');
        const a = em.getBoundingClientRect();
        const b = target.getBoundingClientRect();
        const el = document.createElement('span');
        el.className = 'flier';
        el.textContent = target.textContent;
        el.style.fontSize = getComputedStyle(target).fontSize;
        fliersLayer.appendChild(el);
        const w0 = el.getBoundingClientRect().width || 1;
        fliers.push({
          el,
          x0: a.left - stickyR.left, y0: a.top - stickyR.top,
          x1: b.left - stickyR.left, y1: b.top - stickyR.top,
          s0: a.width / w0
        });
      });
      phaseD.style.cssText = dPrev; phaseE.style.cssText = ePrev;
    }
    let fliersDirty = true;
    document.fonts.ready.then(() => { fliersDirty = true; });
    addEventListener('resize', () => { fliersDirty = true; });

    function manTick() {
      const r = man.getBoundingClientRect();
      const total = r.height - innerHeight;
      const p = clamp(-r.top / total);
      const inView = r.top < innerHeight && r.bottom > 0;
      if (inView && !manPlaying) { manVideo.play().catch(() => {}); manPlaying = true; }
      if (!inView && manPlaying) { manVideo.pause(); manPlaying = false; }

      const kIn = ease(0.02, 0.14, p);
      const toC = ease(0.18, 0.26, p);          // knockout → naranja (centrado idéntico)
      const zoom = ease(0.3, 0.48, p);          // zoom hacia la U
      const toD = ease(0.46, 0.56, p);          // credo entra
      const wOut = ease(0.64, 0.73, p);         // las palabras blancas se apagan
      const fly = smooth(ease(0.74, 0.88, p));  // las naranjas VIAJAN a la columna
      const settle = ease(0.9, 0.96, p);        // columna real toma el relevo

      knock.style.opacity = kIn * (1 - toC);
      manVideo.style.opacity = 0.85 * kIn * (1 - toC);
      phaseC.style.opacity = toC * (1 - ease(0.42, 0.52, p));
      zoomer.style.transform = 'scale(' + (1 + (coarse ? 0 : zoom * 13)) + ')';

      phaseD.style.opacity = toD;
      phaseD.style.transform = 'translateY(' + ((1 - toD) * 24) + 'px)';
      creed.style.setProperty('--wfade', (1 - wOut) * toD);

      /* durante el viaje: ems del credo y columna ocultos; viajeras visibles */
      const flying = fly > 0 && settle < 1;
      if (flying && fliersDirty) { buildFliers(); fliersDirty = false; }
      creed.querySelectorAll('em[data-w]').forEach(em => { em.style.opacity = fly > 0 ? 0 : 1; });
      fliersLayer.style.opacity = flying && toD > 0 ? 1 : 0;
      fliers.forEach(f => {
        const x = lerp(f.x0, f.x1, fly);
        const y = lerp(f.y0, f.y1, fly);
        const s = lerp(f.s0, 1, fly);
        f.el.style.transform = 'translate(' + x + 'px,' + y + 'px) scale(' + s + ')';
      });
      phaseE.style.opacity = settle;
      phaseE.style.transform = 'none';
    }
    addEventListener('scroll', () => requestAnimationFrame(manTick), { passive: true });
    manTick();
  }

  /* Capas del sistema — clicables */
  const LAYERS = [
    { label: 'Señales y detección de decisores', dot: 'i-accent' },
    { label: 'Prospección multicanal', dot: 'i-violet' },
    { label: 'Contenido y posicionamiento', dot: 'i-green' },
    { label: 'CRM e inteligencia comercial', dot: 'i-cyan' }
  ];
  const stackBack = document.getElementById('stackBack');
  const panes = document.querySelectorAll('.layer-pane');
  function setLayer(active) {
    panes.forEach(p => { p.hidden = +p.dataset.layer !== active; });
    stackBack.innerHTML = '';
    LAYERS.forEach((l, i) => {
      if (i === active) return;
      const li = document.createElement('li');
      const btn = document.createElement('button');
      btn.className = 'stack__tab';
      btn.innerHTML = '<i class="ldot ' + l.dot + '"></i> ' + l.label;
      btn.addEventListener('click', () => setLayer(i));
      li.appendChild(btn);
      stackBack.appendChild(li);
    });
  }
  setLayer(0);

  /* Modal vídeo */
  const modal = document.getElementById('videoModal');
  const frame = document.getElementById('videoFrame');
  document.querySelectorAll('[data-yt]').forEach(btn => {
    btn.addEventListener('click', () => {
      frame.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + btn.dataset.yt + '?autoplay=1&rel=0" title="Testimonio de cliente" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
      modal.showModal();
    });
  });
  document.getElementById('videoClose').addEventListener('click', () => modal.close());
  modal.addEventListener('click', e => { if (e.target === modal) modal.close(); });
  modal.addEventListener('close', () => { frame.innerHTML = ''; });

  /* Audiencias — primera activa por defecto */
  const AUD = [
    { label: 'Gerencia',
      sit: 'Tu empresa depende de ferias, recomendaciones y de la agenda de un par de comerciales. Cada trimestre empieza en blanco.',
      wins: ['Previsión de entrada de oportunidades, semana a semana', 'El sistema queda en tu empresa: datos y conversaciones son tuyos', 'Crecimiento sin ampliar la estructura comercial'] },
    { label: 'Dirección Comercial',
      sit: 'Tu equipo vende bien cuando tiene con quién hablar. El problema es el flujo: prospectar se hace “cuando se puede”.',
      wins: ['Conversaciones abiertas con decisores de tu nicho, cada semana', 'Comerciales cerrando en vez de haciendo puerta fría', 'Informe semanal y reunión mensual: pipeline sin caja negra'] },
    { label: 'Export Managers',
      sit: 'Abrir un mercado nuevo desde cero: sin contactos, sin marca conocida y con viajes que tienen que salir rentables.',
      wins: ['Prospección en el idioma y la cultura de cada mercado', 'Distribuidores y cuentas objetivo localizados antes de viajar', 'Agenda cerrada antes de cada feria internacional'] },
    { label: 'Marketing Industrial',
      sit: 'Publicas contenido y consigues visitas, pero ventas pregunta: ¿y esto cuántas reuniones ha traído?',
      wins: ['Contenido conectado a prospección: cada pieza abre conversaciones', 'Posicionamiento ante los decisores exactos de tu nicho', 'Métricas que le importan a gerencia: conversaciones y leads'] },
    { label: 'Ventas / SDR',
      sit: 'Listas frías, mensajes que nadie contesta y horas de LinkedIn que no llevan a nada.',
      wins: ['Señales y secuencias listas cada mañana: a quién, por qué y con qué mensaje', 'Cada contacto llega con su historia y su momento', 'Tú a lo tuyo: conversar y cerrar'] }
  ];
  const audPanel = document.getElementById('audPanel');
  const audCards = document.querySelectorAll('.aud-card');
  let audOpen = -1;
  function openAud(i, card) {
    audOpen = i;
    audCards.forEach(c => { c.setAttribute('aria-selected', 'false'); c.classList.remove('is-active'); });
    card.setAttribute('aria-selected', 'true');
    card.classList.add('is-active');
    const d = AUD[i];
    audPanel.querySelector('.aud-panel__label').textContent = d.label;
    audPanel.querySelector('.aud-panel__situation').textContent = d.sit;
    audPanel.querySelector('.aud-panel__wins').innerHTML = d.wins.map(w => '<li>' + w + '</li>').join('');
    audPanel.hidden = false;
    requestAnimationFrame(() => audPanel.classList.add('is-open'));
  }
  audCards.forEach(card => {
    card.addEventListener('click', () => {
      const i = +card.dataset.aud;
      if (audOpen === i) return;
      openAud(i, card);
    });
  });
  openAud(0, audCards[0]); /* primera activa: invita a tocar el resto */

  /* Guías: foto “hola” al hover si existe (http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/<nombre>-hola.jpg) */
  document.querySelectorAll('.guide img[data-name]').forEach(img => {
    const alt = 'http://localhost/Khumbu/khumbunew/wp-content/uploads/khumbu/' + img.dataset.name + '-hola.jpg';
    const probe = new Image();
    probe.onload = () => {
      const base = img.src;
      img.closest('.guide').addEventListener('mouseenter', () => { img.src = alt; });
      img.closest('.guide').addEventListener('mouseleave', () => { img.src = base; });
    };
    probe.src = alt;
  });

  /* Vídeo de portada: respeta save-data */
  if (navigator.connection && navigator.connection.saveData) {
    const v = document.querySelector('.cover__video');
    v.removeAttribute('autoplay'); v.pause();
  }
})();

    </script>
    <?php
} );PHP: 2026-09-22 17:44:49 [notice X 0][/var/www/html/Khumbu/khumbunew/wp-content/plugins/elementor/modules/global-classes/atomic-global-styles.php::410] Elementor\Modules\GlobalClasses\Atomic_Global_Styles::get_cache_root_key(): Implicitly marking parameter $key as nullable is deprecated, the explicit nullable type must be used instead [array (
  'trace' => '
#0: Elementor\Core\Logger\Manager -> shutdown()
',
)]
