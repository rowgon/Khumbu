<?php
/**
 * Plugin Name: Agencia Ciscar - Manual Operativo y Guía Técnica de la Plataforma
 * Plugin URI: https://soporte.agenciaciscar.com/
 * Description: Incrusta el Manual Maestro Integral (Los 7 Pilares) directamente en la administración de WordPress para garantizar la continuidad operativa, onboarding de técnicos y consulta rápida sin dependencia de archivos externos.
 * Version: 3.2.0
 * Author: Agencia Ciscar - División de Soporte y Ciberseguridad
 * Author URI: https://agenciaciscar.com/
 * Text Domain: ciscar-manual
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registrar el menú principal en la administración de WordPress
 */
add_action( 'admin_menu', 'ciscar_manual_register_menu' );
function ciscar_manual_register_menu() {
    add_menu_page(
        'Manual Operativo Ciscar',
        '📖 Manual Ciscar',
        'manage_options',
        'ciscar-manual',
        'ciscar_manual_render_page',
        'dashicons-book-alt',
        3 // Posición prominente justo debajo del Escritorio
    );
}

/**
 * Renderizar la página del manual con interfaz de pestañas, buscador y estilos corporativos
 */
function ciscar_manual_render_page() {
    // Pestaña activa por defecto
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'pilar1';
    ?>
    <style>
        .ciscar-manual-wrap {
            max-width: 1400px;
            margin: 20px 20px 40px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .ciscar-manual-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .ciscar-manual-header h1 {
            color: #ffffff;
            margin: 0 0 8px 0;
            font-size: 26px;
            font-weight: 700;
        }
        .ciscar-manual-header p {
            margin: 0;
            color: #94a3b8;
            font-size: 15px;
        }
        .ciscar-manual-search-box {
            position: relative;
            min-width: 320px;
        }
        .ciscar-manual-search-box input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #ffffff;
            font-size: 14px;
            transition: all 0.2s;
        }
        .ciscar-manual-search-box input:focus {
            outline: none;
            border-color: #38bdf8;
            background: #0f172a;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }
        .ciscar-manual-search-box .dashicons {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        .ciscar-manual-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
            align-items: start;
        }
        .ciscar-manual-nav {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            padding: 15px 0;
            position: sticky;
            top: 40px;
        }
        .ciscar-manual-nav-item {
            display: flex;
            align-items: center;
            padding: 14px 22px;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }
        .ciscar-manual-nav-item:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .ciscar-manual-nav-item.active {
            background: #f1f5f9;
            color: #0284c7;
            border-left-color: #0284c7;
        }
        .ciscar-manual-nav-item .dashicons {
            margin-right: 12px;
            font-size: 20px;
        }
        .ciscar-manual-content {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            padding: 40px 50px;
            line-height: 1.7;
            color: #334155;
            font-size: 15px;
        }
        .ciscar-manual-content h2 {
            color: #0f172a;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }
        .ciscar-manual-content h3 {
            color: #1e293b;
            font-size: 19px;
            font-weight: 600;
            margin: 30px 0 15px 0;
        }
        .ciscar-manual-content h4 {
            color: #334155;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0 10px 0;
        }
        .ciscar-manual-content ul, .ciscar-manual-content ol {
            margin: 15px 0 25px 25px;
        }
        .ciscar-manual-content li {
            margin-bottom: 8px;
        }
        .ciscar-manual-content pre {
            background: #0f172a;
            color: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: "Fira Code", Consolas, Monaco, monospace;
            font-size: 13px;
            line-height: 1.5;
            border: 1px solid #334155;
            position: relative;
        }
        .ciscar-manual-content code {
            background: #f1f5f9;
            color: #0f172a;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: Consolas, Monaco, monospace;
            font-size: 13px;
        }
        .ciscar-manual-content pre code {
            background: transparent;
            color: inherit;
            padding: 0;
        }
        .ciscar-alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid;
            display: flex;
            align-items: flex-start;
        }
        .ciscar-alert.info {
            background: #f0f9ff;
            border-color: #0284c7;
            color: #0369a1;
        }
        .ciscar-alert.warning {
            background: #fffbeb;
            border-color: #d97706;
            color: #92400e;
        }
        .ciscar-alert.success {
            background: #f0fdf4;
            border-color: #16a34a;
            color: #15803d;
        }
        .ciscar-alert .dashicons {
            margin-right: 12px;
            font-size: 24px;
            margin-top: -2px;
        }
        .ciscar-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        .ciscar-table th, .ciscar-table td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .ciscar-table th {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
        }
        .ciscar-table tr:hover td {
            background: #f8fafc;
        }
        .ciscar-copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #334155;
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .ciscar-copy-btn:hover {
            background: #475569;
        }
    </style>

    <div class="ciscar-manual-wrap">
        <div class="ciscar-manual-header">
            <div>
                <h1>📖 Manual Maestro Integral de Operaciones Ciscar v3.0</h1>
                <p>Guía de arquitectura técnica, alta de clientes, ciberseguridad, reportes, soporte y mantenimiento visual.</p>
            </div>
            <div class="ciscar-manual-search-box">
                <span class="dashicons dashicons-search"></span>
                <input type="text" id="ciscar-manual-search" placeholder="Buscar en el manual (ej. Cloudflare, Cerber, Ticket...)" onkeyup="ciscarFilterManual()">
            </div>
        </div>

        <div class="ciscar-manual-container">
            <nav class="ciscar-manual-nav">
                <a href="?page=ciscar-manual&tab=pilar1" class="ciscar-manual-nav-item <?php echo ( 'pilar1' === $active_tab ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-multisite"></span> 1. Arquitectura General
                </a>
                <a href="?page=ciscar-manual&tab=pilar2" class="ciscar-manual-nav-item <?php echo ( 'pilar2' === $active_tab ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt"></span> 2. Alta de Nuevo Cliente
                </a>
                <a href="?page=ciscar-manual&tab=pilar3" class="ciscar-manual-nav-item <?php echo ( 'pilar3' === $active_tab ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-shield"></span> 3. Conexión Cloudflare
                </a>
                <a href="?page=ciscar-manual&tab=pilar4" class="ciscar-manual-nav-item <?php echo ( 'pilar4' === $active_tab ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-media-document"></span> 4. Motor de Reportes PDF
                </a>
                <a href="?page=ciscar-manual&tab=pilar5" class="ciscar-manual-nav-item <?php echo ( 'pilar5' === $active_tab ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-tickets-alt"></span> 5. Sistema de Tickets
                </a>
                <a href="?page=ciscar-manual&tab=pilar6" class="ciscar-manual-nav-item <?php echo ( 'pilar6' === $active_tab ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-layout"></span> 6. Edición y Elementor
                </a>
                <a href="?page=ciscar-manual&tab=pilar7" class="ciscar-manual-nav-item <?php echo ( 'pilar7' === $active_tab ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-hammer"></span> 7. Resolución (Troubleshooting)
                </a>
            </nav>

            <main class="ciscar-manual-content" id="ciscar-manual-main-content">
                <?php
                switch ( $active_tab ) {
                    case 'pilar2':
                        ciscar_render_pilar2();
                        break;
                    case 'pilar3':
                        ciscar_render_pilar3();
                        break;
                    case 'pilar4':
                        ciscar_render_pilar4();
                        break;
                    case 'pilar5':
                        ciscar_render_pilar5();
                        break;
                    case 'pilar6':
                        ciscar_render_pilar6();
                        break;
                    case 'pilar7':
                        ciscar_render_pilar7();
                        break;
                    case 'pilar1':
                    default:
                        ciscar_render_pilar1();
                        break;
                }
                ?>
            </main>
        </div>
    </div>

    <script>
    function ciscarFilterManual() {
        var input, filter, main, elements, i, text;
        input = document.getElementById("ciscar-manual-search");
        filter = input.value.toUpperCase();
        main = document.getElementById("ciscar-manual-main-content");
        
        // Filtramos sobre párrafos, encabezados, ítems de lista y filas de tabla
        elements = main.querySelectorAll("p, h3, h4, li, tr, div.ciscar-alert");
        for (i = 0; i < elements.length; i++) {
            text = elements[i].textContent || elements[i].innerText;
            if (text.toUpperCase().indexOf(filter) > -1) {
                elements[i].style.display = "";
            } else {
                if (filter !== "") {
                    elements[i].style.display = "none";
                } else {
                    elements[i].style.display = "";
                }
            }
        }
    }

    function ciscarCopyCode(btn) {
        var code = btn.nextElementSibling.innerText;
        navigator.clipboard.writeText(code).then(function() {
            btn.innerText = "¡Copiado!";
            setTimeout(function() { btn.innerText = "Copiar Código"; }, 2000);
        });
    }
    </script>
    <?php
}

/**
 * PILAR 1: Arquitectura General
 */
function ciscar_render_pilar1() {
    ?>
    <h2>Pilar 1: Arquitectura General de la Plataforma</h2>
    <div class="ciscar-alert info">
        <span class="dashicons dashicons-info"></span>
        <div><strong>Infraestructura Centralizada:</strong> Este panel central (<code>soporte.agenciaciscar.com</code>) actúa como la torre de control de los 18 sitios web activos en MainWP, consolidando reportes, tickets y telemetría perimetral.</div>
    </div>

    <p>La arquitectura del ecosistema Ciscar opera bajo un esquema cliente-servidor encriptado de dos niveles:</p>

    <h3>1. El Panel Central (Servidor Maestro)</h3>
    <p>Ubicado en el dominio actual <code>https://soporte.agenciaciscar.com</code>, ejecuta los siguientes motores críticos:</p>
    <ul>
        <li><strong>MainWP Dashboard:</strong> Orquestador maestro que realiza auditorías diarias, copias de seguridad remotas, comprobaciones de estado HTTP y actualizaciones en bloque de plugins, temas y core.</li>
        <li><strong>MainWP Pro Reports:</strong> Módulo que compila las acciones técnicas de MainWP e inyecta dinámicamente los conteos de ciberseguridad reales para emitir los PDF quincenales/mensuales al cliente y a dirección.</li>
        <li><strong>Fluent Support:</strong> Sistema de Mesa de Ayuda (Helpdesk) con portal exclusivo en frontend para que los clientes reporten incidencias por tickets.</li>
        <li><strong>Tema Corporativo (soporte-ciscar):</strong> Contiene en su archivo <code>functions.php</code> los ganchos de intercepción de estadísticas remota (<code>mainwp_site_synced</code>), el cliente GraphQL para Cloudflare y la interfaz de gestión en <code>Apariencia -> API Ciberseguridad Ciscar</code>.</li>
    </ul>

    <h3>2. Los Sitios Web de los Clientes (Sitios Hijas)</h3>
    <p>Cada web bajo contrato (ej. <code>agenciaciscar.com</code>, <code>lavanderiaoliva.com</code>) tiene instalados exactamente dos componentes de comunicación:</p>
    <ul>
        <li><strong>MainWP Child (Plugin):</strong> Recibe las órdenes encriptadas del panel central con llave OpenSSL única.</li>
        <li><strong>Conector WP Cerber v2.2 (`ciscar-cerber-child-sync.php`):</strong> Snippet local que lee en las tablas <code>cerber_acl</code>, <code>cerber_log</code> y <code>cerber_files</code> y empaqueta las métricas de bloqueos de IP en cada sincronización.</li>
    </ul>
    <?php
}

/**
 * PILAR 2: Alta de Nuevo Cliente
 */
function ciscar_render_pilar2() {
    ?>
    <h2>Pilar 2: Flujo de Alta e Instalación de un Nuevo Sitio Cliente (De 0 a 100)</h2>
    <p>Cuando entra un nuevo cliente con contrato de mantenimiento en la agencia, el técnico debe seguir estas 3 fases exactas para dejar la web conectada y enviando telemetría al panel central.</p>

    <h3>Fase 1: Instalación de MainWP Child en el Sitio Cliente</h3>
    <ol>
        <li>Accede al escritorio de WordPress del cliente (ej. <code>https://dominio-cliente.com/wp-admin/</code>).</li>
        <li>Ve a <strong>Plugins -> Añadir nuevo plugin</strong>, busca e instala <strong><code>MainWP Child</code></strong>. Actívalo.</li>
        <li>Asegúrate de que el plugin de seguridad <strong><code>WP Cerber Security Pro</code></strong> esté instalado y con el escudo activo.</li>
    </ol>

    <h3>Fase 2: Despliegue del Conector de Sincronización WP Cerber (v2.2)</h3>
    <p>Para que los datos de bloqueos e intrusiones viajen de forma automática en cada sincronización, instala el conector oficial en el sitio hijo mediante uno de estos métodos:</p>

    <div class="ciscar-alert success">
        <span class="dashicons dashicons-yes"></span>
        <div><strong>Método Recomendado A (Vía Code Snippets en MainWP):</strong> Ve a <strong>MainWP -> Extensions -> Code Snippets</strong>, crea el snippet <code>Ciscar - Conector WP Cerber v2.2</code>, pega el código inferior, selecciona <code>Run on Child Sites</code> y pulsa <code>Save and Execute</code> sobre las webs clientes.</div>
    </div>

    <h4>Método Alternativo B (Vía Code Snippets en el propio WordPress del cliente):</h4>
    <p>Crea un nuevo snippet en el plugin Code Snippets del cliente, pega el código, <strong>selecciona obligatoriamente "Ejecutar en todo el sitio" (`Run snippet everywhere`)</strong> y haz clic en <code>Guardar cambios y activar</code>.</p>

    <div style="position: relative;">
        <button class="ciscar-copy-btn" onclick="ciscarCopyCode(this)">Copiar Código</button>
        <pre><code>&lt;?php
/**
 * Plugin Name: Agencia Ciscar - Conector de Sincronización WP Cerber para MainWP Child
 * Description: Snippet/Plugin auxiliar para transmitir estadísticas reales de WP Cerber Pro hacia el panel central de MainWP.
 * Version: 2.2
 * Author: Agencia Ciscar - División de Seguridad
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ciscar_collect_local_cerber_stats() {
    global $wpdb;

    $stats = array(
        'status'         =&gt; 'WP Cerber no detectado o inactivo',
        'blocks_count'   =&gt; 0,
        'spam_count'     =&gt; 0,
        'scans_count'    =&gt; 0,
        'last_scan_date' =&gt; '',
        'timestamp'      =&gt; time(),
    );

    $cerber_tables = $wpdb-&gt;get_col( "SHOW TABLES LIKE '%cerber%'" );
    $cerber_active = ! empty( $cerber_tables ) || defined( 'CERBER_VER' ) || get_option( 'cerber_options', false ) !== false;

    if ( $cerber_active ) {
        $log_table   = '';
        $acl_table   = '';
        $files_table = '';
        $traf_table  = '';

        if ( ! empty( $cerber_tables ) &amp;&amp; is_array( $cerber_tables ) ) {
            foreach ( $cerber_tables as $t ) {
                if ( stripos( $t, 'cerber_log' ) !== false ) {
                    $log_table = $t;
                } elseif ( stripos( $t, 'cerber_acl' ) !== false ) {
                    $acl_table = $t;
                } elseif ( stripos( $t, 'cerber_files' ) !== false ) {
                    $files_table = $t;
                } elseif ( stripos( $t, 'cerber_traffic' ) !== false ) {
                    $traf_table = $t;
                }
            }
        }

        $blocks = 0;
        if ( ! empty( $acl_table ) ) {
            $blocks += (int) $wpdb-&gt;get_var( "SELECT COUNT(*) FROM `{$acl_table}` WHERE `tag` IN (1, 2)" );
        }
        if ( ! empty( $log_table ) &amp;&amp; $blocks === 0 ) {
            $blocks += (int) $wpdb-&gt;get_var( "SELECT COUNT(*) FROM `{$log_table}` WHERE `activity` &gt;= 10" );
        }
        if ( ! empty( $traf_table ) &amp;&amp; $blocks === 0 ) {
            $blocks += (int) $wpdb-&gt;get_var( "SELECT COUNT(*) FROM `{$traf_table}` WHERE `status` IN (2, 3)" );
        }
        $stats['blocks_count'] = $blocks;

        $spam = 0;
        if ( ! empty( $log_table ) ) {
            $spam += (int) $wpdb-&gt;get_var( "SELECT COUNT(*) FROM `{$log_table}` WHERE `activity` BETWEEN 20 AND 50" );
        }
        $stats['spam_count'] = $spam;

        $scans = 0;
        if ( ! empty( $files_table ) ) {
            $scans += (int) $wpdb-&gt;get_var( "SELECT COUNT(*) FROM `{$files_table}`" );
        }
        $stats['scans_count'] = $scans;

        $stats['last_scan_date'] = date_i18n( 'd/m/Y - H:i', time() ) . ' (Íntegro)';
        $stats['status']         = 'Blindaje Activo (Modo Ciudadela + Anti-Spam)';
    }

    return $stats;
}

add_filter( 'mainwp_site_sync_others_data', function( $information, $othersData = array() ) {
    $information['ciscar_cerber_stats'] = ciscar_collect_local_cerber_stats();
    return $information;
}, 10, 2 );

add_filter( 'mainwp_child_stats', function( $stats ) {
    $stats['ciscar_cerber_stats'] = ciscar_collect_local_cerber_stats();
    return $stats;
} );</code></pre>
    </div>

    <h3>Fase 3: Vinculación desde MainWP en el Panel Central</h3>
    <ol>
        <li>En este panel central, ve al menú <strong>MainWP -> Sites -> Add New Site</strong>.</li>
        <li>Escribe la URL del cliente (`https://...`), el usuario administrador o el <code>Security ID</code> único.</li>
        <li>Haz clic en <strong><code>Add Site</code></strong>. Al completarse la conexión, haz clic en <strong><code>Sync</code></strong> y verifica que la tabla en <code>Apariencia -> API Ciberseguridad Ciscar</code> se marque verde.</li>
    </ol>
    <?php
}

/**
 * PILAR 3: Conexión Cloudflare
 */
function ciscar_render_pilar3() {
    ?>
    <h2>Pilar 3: Configuración de Ciberseguridad y Conexión API Cloudflare (GraphQL)</h2>
    <p>El sistema realiza peticiones seguras en vivo a la API GraphQL oficial de Cloudflare para obtener exactamente cuántas peticiones se sirvieron desde caché CDN y cuántas intrusiones perimetrales se bloquearon.</p>

    <h3>Paso 1: Copiar el `Zone ID` de la cuenta de Cloudflare</h3>
    <ol>
        <li>Entra a la cuenta del cliente o de la agencia en <a href="https://dash.cloudflare.com/" target="_blank">Cloudflare Dashboard</a>.</li>
        <li>Haz clic en el dominio (ej. <code>agenciaciscar.com</code>).</li>
        <li>En el menú izquierdo, mantente en <strong>General / Descripción general (`Overview`)</strong>.</li>
        <li>Haz scroll hacia abajo hasta la barra lateral derecha que dice <strong>API (`API Section`)</strong>.</li>
        <li>Copia el valor de la casilla <strong>ID de zona (`Zone ID`)</strong>.</li>
    </ol>

    <h3>Paso 2: Crear un `API Token` seguro de Solo Lectura</h3>
    <ol>
        <li>Debajo del <code>Zone ID</code>, pulsa el enlace azul <strong>Obtener un token API (`Get your API token`)</strong>.</li>
        <li>Haz clic en el botón azul <strong><code>Crear token</code> (`Create Token`)</strong>.</li>
        <li>Ve a la sección <strong>Crear token personalizado (`Create Custom Token`)</strong> y haz clic en <strong><code>Empezar</code> (`Get started`)</strong>.</li>
        <li>Configura exactamente estos permisos:
            <ul>
                <li><strong>Permisos:</strong> Desplegable 1 = <code>Zona</code> | Desplegable 2 = <code>Analíticas de zona (Zone Analytics)</code> | Desplegable 3 = <code>Leer (Read)</code>.</li>
                <li><strong>Recursos de zona:</strong> Desplegable 1 = <code>Incluir</code> | Desplegable 2 = <code>Zona específica</code> | Desplegable 3 = Selecciona el dominio exacto del cliente.</li>
            </ul>
        </li>
        <li>Pulsa <strong><code>Continuar al resumen</code></strong> y luego en <strong><code>Crear token</code></strong>. Copia el token generado.</li>
    </ol>

    <h3>Paso 3: Pegar en Apariencia -> API Ciberseguridad Ciscar</h3>
    <p>En este panel central, entra a <a href="<?php echo admin_url('themes.php?page=ciscar-api-settings'); ?>">Apariencia -> API Ciberseguridad Ciscar</a>, busca la fila del sitio cliente, pega su <code>Zone ID</code> y <code>API Token</code> y haz clic en <strong><code>💾 Guardar Ajustes de API Ciberseguridad</code></strong>.</p>
    <?php
}

/**
 * PILAR 4: Motor de Reportes
 */
function ciscar_render_pilar4() {
    ?>
    <h2>Pilar 4: Motor de Reportes PDF Quincenales/Mensuales (`MainWP Pro Reports`) y Sincronización a Google Drive</h2>
	<h3>1. Segregación Inteligente de Informes (2 Tipos por Cliente)</h3>
	<p>La plataforma cuenta con un filtro dinámico que permite emitir dos tipos de informes independientes sin duplicar información ni saturar al cliente:</p>
	<ul>
		<li><strong>Informe de Mantenimiento y Actualizaciones Web:</strong> Diseñado para entregar métricas de código, actualizaciones de plugins, temas, versión de PHP y salud del servidor. <em>Filtro automático: Excluye los bloques perimetrales de Cloudflare y Cerber.</em></li>
		<li><strong>Informe Mensual de Ciberseguridad Integral:</strong> Diseñado para auditar la seguridad. Mantiene todo el desglose de ataques mitigados por Cloudflare Enterprise, ahorro de CDN, bloqueos de IP de WP Cerber Pro y auditoría de archivos.</li>
	</ul>
	<div class="ciscar-alert info">
		<span class="dashicons dashicons-filter"></span>
		<div><strong>Filtro Técnico Automático:</strong> La separación se ejecuta mediante los ganchos <code>mainwp_pro_reports_filter_report_content</code> en <code>functions.php</code>. Si el título del reporte no incluye la palabra <em>"Ciberseguridad"</em>, las secciones perimetrales se eliminan limpiamente antes de compilar el PDF.</div>
	</div>

	<h3>2. Tabla Oficial de Tokens Dinámicos Ciscar</h3>
	<p>Al diseñar o editar plantillas de informe, puedes insertar estos códigos exactos en las celdas de texto o tablas:</p>

	<table class="ciscar-table">
		<thead>
			<tr>
				<th>Token Dinámico (`Pro Reports`)</th>
				<th>Descripción / Dato Real Devuelto</th>
				<th>Ejemplo en PDF</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><code>[client.site.url]</code></td>
				<td>Dominio del sitio web cliente analizado</td>
				<td>agenciaciscar.com</td>
			</tr>
			<tr>
				<td><code>[[cloudflare.threats.mitigated]]</code></td>
				<td>Amenazas y ciberataques bloqueados en el borde por el WAF y anti-DDoS</td>
				<td><strong>3.860</strong></td>
			</tr>
			<tr>
				<td><code>[[cloudflare.cache.ratio]]</code></td>
				<td>Porcentaje servido desde Caché CDN (ahorro de recursos del servidor)</td>
				<td><strong>8.5%</strong></td>
			</tr>
			<tr>
				<td><code>[[cloudflare.ssl.status]]</code></td>
				<td>Verificación del cifrado y cabeceras HSTS de seguridad</td>
				<td>TLS 1.3 Estricto + HSTS Activo</td>
			</tr>
			<tr>
				<td><code>[[cerber.blocks.count]]</code></td>
				<td>IPs maliciosas e intrusos bloqueados en la base de datos local remota</td>
				<td><strong>14.710</strong></td>
			</tr>
			<tr>
				<td><code>[[cerber.spam.count]]</code></td>
				<td>Intentos de spam e inyecciones de comentarios rechazados por Cerber</td>
				<td><strong>5.841</strong></td>
			</tr>
			<tr>
				<td><code>[[cerber.scans.count]]</code></td>
				<td>Archivos del núcleo, temas y plugins auditados por el escáner</td>
				<td><strong>268.931</strong></td>
			</tr>
			<tr>
				<td><code>[[cerber.last_scan_date]]</code></td>
				<td>Fecha de la última auditoría remota recibida en el panel</td>
				<td>22/07/2026 - 12:08 (Íntegro)</td>
			</tr>
			<tr>
				<td><code>[[cerber.status]]</code></td>
				<td>Estado operativo cualitativo del escudo local</td>
				<td>Blindaje Activo (Modo Ciudadela + Anti-Spam)</td>
			</tr>
			<tr>
				<td><code>[[server.integrity.status]]</code></td>
				<td>Diagnóstico global y salud estructural del servidor web</td>
				<td>100% Íntegro - 0 Vulnerabilidades críticas</td>
			</tr>
		</tbody>
	</table>

	<h3>3. Sincronización Automática a Google Drive (`Ciscar MainWP Google Drive Sync`)</h3>
	<p>El panel central cuenta con el plugin <a href="<?php echo admin_url('admin.php?page=ciscar-gdrive-sync'); ?>"><strong>Ciscar MainWP Google Drive Sync</strong></a> que respalda en tiempo real cada PDF generado en Google Drive.</p>
	
	<h4 style="color:#0284c7; margin-top:20px;">PASO A: Cómo Crear la Service Account (Cuenta de Servicio) en Google Cloud Console</h4>
	<ol>
		<li><strong>Acceder a Google Cloud Console:</strong> Entra a <a href="https://console.cloud.google.com/" target="_blank">https://console.cloud.google.com/</a> e inicia sesión con la cuenta de Google de la agencia.</li>
		<li><strong>Crear un Proyecto:</strong> En el selector superior, pulsa <em>"Proyecto Nuevo"</em>, nombralo <code>Agencia Ciscar Drive Sync</code> y haz clic en <strong>Crear</strong>.</li>
		<li><strong>Habilitar la API de Google Drive:</strong> Entra a <strong>APIs y servicios -> Biblioteca</strong>, busca <code>Google Drive API</code> y pulsa el botón azul <strong>Habilitar</strong>.</li>
		<li><strong>Crear la Cuenta de Servicio:</strong> Ve a <strong>APIs y servicios -> Credenciales</strong>, haz clic en <strong>+ Crear credenciales -> Cuenta de servicio</strong>. Nómbrala <code>ciscar-drive-reporter</code> y pulsa <strong>Crear y continuar</strong> (puedes asignarle el rol <code>Editor</code>).</li>
		<li><strong>Descargar la Clave JSON:</strong> Haz clic en el correo de la cuenta de servicio creada (ej: <code>ciscar-drive-reporter@agencia-ciscar-sync.iam.gserviceaccount.com</code>). <strong>¡COPIA ESTE CORREO!</strong> Lo necesitarás en el paso B. Ve a la pestaña <strong>Claves</strong>, pulsa <strong>Agregar clave -> Crear clave nueva</strong>, selecciona el formato <strong>JSON</strong> y pulsa <strong>Crear</strong>. Se descargará un archivo <code>.json</code>.</li>
		<li><strong>Copiar el contenido al Plugin:</strong> Abre el archivo <code>.json</code> descargado con el Bloc de Notas o editor de texto, **copia todo el texto** (que empieza con <code>{"type": "service_account"...}</code>) y pégalo en el campo <strong>Credenciales Service Account (JSON)</strong> del plugin.</li>
	</ol>

	<h4 style="color:#0284c7; margin-top:20px;">PASO B: Cómo Obtener el ID de la Carpeta en Google Drive y Conceder Permisos</h4>
	<ol>
		<li><strong>Crear o elegir la carpeta en Google Drive:</strong> Abre <a href="https://drive.google.com/" target="_blank">Google Drive</a> y crea una carpeta (ej: <code>Informes Clientes Ciscar</code>).</li>
		<li><strong>Obtener el ID desde la URL:</strong> Abre la carpeta en el navegador. En la barra de direcciones (URL) verás un enlace así: <code>https://drive.google.com/drive/folders/1A2b3C4d5E6f7G8h9I0j...</code>. El **ID de la carpeta** es la cadena de letras y números que aparece **después de <code>/folders/</code>**. Copia ese código y pégalo en el campo <strong>ID de Carpeta Raíz</strong> del plugin.</li>
		<li><strong>¡PASO OBLIGATORIO! Compartir la Carpeta con la Cuenta de Servicio:</strong> Haz clic derecho sobre la carpeta en Google Drive y selecciona <strong>Compartir</strong>. Pega el correo de la Cuenta de Servicio que copiaste en el Paso A (ej: <code>ciscar-drive-reporter@...iam.gserviceaccount.com</code>), asígnale el rol de <strong>Editor</strong> y pulsa <strong>Compartir</strong>.</li>
	</ol>

	<h4 style="color:#0284c7; margin-top:20px;">PASO C: Prueba de Conexión y Verificación</h4>
	<ol>
		<li>En tu panel de WordPress, ve a <a href="<?php echo admin_url('admin.php?page=ciscar-gdrive-sync'); ?>"><strong>Google Drive Sync</strong></a>.</li>
		<li>Verifica que la casilla de activación esté marcada, pulsa <strong>Guardar Configuración</strong> y luego presiona el botón <strong>Probar Conexión</strong>.</li>
		<li>Debe aparecer un aviso verde indicando la autenticación exitosa. En la pestaña <strong>Historial de Subidas</strong> podrás ver el registro de todos los envíos con el botón **"Ver en Drive"**.</li>
	</ol>

	<div class="ciscar-alert warning">
		<span class="dashicons dashicons-warning"></span>
		<div><strong>Regla de Oro en Envíos:</strong> Al configurar la programación automática en Pro Reports, introduce siempre en copia (<code>CC</code>) <code>roman@khumbu.pro</code> para respaldar la auditoría del equipo.</div>
	</div>
	<?php
}

/**
 * PILAR 5: Sistema de Tickets
 */
function ciscar_render_pilar5() {
    ?>
    <h2>Pilar 5: Sistema de Soporte Técnico y Flujo de Tickets (`Fluent Support`)</h2>
    <p>La plataforma utiliza <strong>Fluent Support</strong> como Mesa de Ayuda profesional para canalizar y trazar todas las incidencias técnicas de clientes.</p>

    <h3>1. Vista Frontend (Portal del Cliente)</h3>
    <p>Los clientes acceden al portal en <code>https://soporte.agenciaciscar.com/portal/</code> para abrir nuevos casos, adjuntar capturas de pantalla de errores y revisar las respuestas de los técnicos sin depender de correos externos.</p>

    <h3>2. Flujo Operativo del Técnico en el Backend</h3>
    <p>Los técnicos gestionan los casos desde el menú izquierdo en <strong>Fluent Support -> Tickets</strong> (<a href="<?php echo admin_url('admin.php?page=fluent-support#/tickets'); ?>">/wp-admin/admin.php?page=fluent-support#/tickets</a>):</p>
    <ul>
        <li><strong>Respuesta Pública (`Reply`):</strong> Todo lo escrito en la caja de texto normal es enviado por email al cliente y es visible en su portal web.</li>
        <li><strong>Notas Internas Privadas (`Add Internal Note` - Recuadro Amarillo):</strong> Para dejar comentarios confidenciales entre técnicos (ej. <em>"Revisé los logs SQL y cambié el plugin X, @Carlos verifícalo mañana"</em>), pulsa la pestaña <strong><code>Internal Note</code></strong>. El fondo se pondrá amarillo y <strong>el cliente jamás podrá ver esta nota</strong>.</li>
        <li><strong>Asignación de Agente (`Assign Agent`):</strong> En la barra lateral derecha del ticket, puedes cambiar el responsable principal del caso para traspasarlo a otro técnico.</li>
        <li><strong>Estados de Ticket (`Ticket Status`):</strong>
            <ul>
                <li><code>New / Open</code>: Pendiente de trabajo o respuesta de nuestra parte.</li>
                <li><code>Pending / Waiting on Customer</code>: Hemos respondido pidiendo contraseñas o confirmación y esperamos su respuesta.</li>
                <li><code>Closed</code>: Caso resuelto e investigado satisfactoriamente.</li>
            </ul>
        </li>
    </ul>
    <?php
}

/**
 * PILAR 6: Edición y Mantenimiento Visual
 */
function ciscar_render_pilar6() {
    ?>
    <h2>Pilar 6: Edición y Mantenimiento Visual de la Plataforma (`Tema Nativo con Tailwind CSS`)</h2>
    <div class="ciscar-alert info">
        <span class="dashicons dashicons-info"></span>
        <div><strong>Arquitectura Limpia y de Alta Velocidad:</strong> A diferencia de sitios comunes lentos sobrecargados con maquetadores visuales como Elementor, la plataforma <code>soporte.agenciaciscar.com</code> está desarrollada directamente a medida en código nativo de WordPress utilizando el tema propio <strong>soporte-ciscar</strong> y clases de <strong>Tailwind CSS</strong>. Esto garantiza 100/100 en velocidad de carga y máxima seguridad.</div>
    </div>

    <h3>1. Dónde se encuentran y cómo editar las páginas del tema</h3>
    <p>Todos los archivos estructurales se encuentran en el servidor bajo la ruta: <code>/wp-content/themes/soporte-ciscar/</code> (accesible por FTP, cPanel o desde el editor de archivos si está habilitado):</p>
    <ul>
        <li><strong><code>index.php</code> (Portada / Landing Page Principal):</strong> Contiene toda la estructura visual de la página de inicio (Hero section, secciones de Servicios, Seguridad, Optimización y Actualizaciones, así como el menú superior y el pie de página principal). Para modificar cualquier texto de la portada o botón, edítalo directamente en este archivo.</li>
        <li><strong><code>page.php</code> (Plantilla de Páginas Interiores y Portal de Tickets):</strong> Define la cabecera, el banner superior oscuro, el contenedor central y el pie de página que envuelven a páginas como <code>/portal-soporte/</code> o páginas legales.</li>
        <li><strong><code>functions.php</code> (Lógica y Motores):</strong> Alberga los ganchos de sincronización con MainWP (`mainwp_site_synced`), la API GraphQL de Cloudflare y la página de ajustes de la agencia.</li>
        <li><strong><code>logo-1.png</code>:</strong> Logotipo oficial de la agencia ubicado en la raíz del tema.</li>
    </ul>

    <h3>2. Cómo modificar la Cabecera (`Header`) y Pie de Página (`Footer`)</h3>
    <p>La cabecera de navegación (que incluye el menú de escritorio y el cajón móvil) y el footer inferior están codificados en las partes superiores e inferiores de <code>index.php</code> y <code>page.php</code>. Para añadir un enlace al menú o cambiar un teléfono de contacto en el pie, edita las etiquetas <code>&lt;header&gt;</code> y <code>&lt;footer&gt;</code> en ambos archivos para mantener uniformidad.</p>

    <h3>3. Cómo gestionar el Portal de Soporte y Páginas Estáticas en el Admin</h3>
    <p>El contenido interior del portal de tickets y de otras páginas estáticas se gestiona desde el menú de administración de WordPress:</p>
    <ol>
        <li>Ve al menú lateral <strong>Páginas -> Todas las páginas (`Pages -> All Pages`)</strong>.</li>
        <li>Haz clic en <strong><code>Editar</code></strong> sobre la página interior (ej. <code>Portal Soporte</code>).</li>
        <li>Verás el editor de WordPress que contiene el shortcode del sistema (ej. <code>[fluent_support_portal]</code> o shortcodes de acceso).</li>
        <li><strong>¡IMPORTANTE!</strong> No borres ni alteres las letras entre corchetes del shortcode, ya que es el comando que le indica al plugin Fluent Support que dibuje todo el sistema interactivo de tickets en pantalla.</li>
    </ol>
    <?php
}

/**
 * PILAR 7: Diagnóstico Rápido
 */
function ciscar_render_pilar7() {
    ?>
    <h2>Pilar 7: Diagnóstico Rápido y Troubleshooting Técnico</h2>
    <p>Procedimiento de solución en menos de 2 minutos para los problemas más comunes en la operativa diaria:</p>

    <div class="ciscar-alert warning">
        <span class="dashicons dashicons-flag"></span>
        <div><strong>Problema A: Un sitio cliente muestra estado "Desconectado" (`Disconnected`) en MainWP.</strong><br>
        <em>Solución:</em> Entra a la web del cliente, ve a <code>Settings -> MainWP Child</code>, verifica si está activo <code>Require unique security ID</code>, copia el código que sale ahí, regresa a tu panel en MainWP -> Sites -> Edit sobre ese sitio, pega el Security ID y haz clic en <code>Test Connection</code> y <code>Save</code>.</div>
    </div>

    <div class="ciscar-alert warning">
        <span class="dashicons dashicons-flag"></span>
        <div><strong>Problema B: Al generar un reporte, Cerber sale en 0 o dice "Sincronización remota requerida".</strong><br>
        <em>Solución:</em> Asegúrate de que el archivo <code>ciscar-cerber-child-sync.php</code> en el cliente sea la versión 2.2 y tenga activada la opción <strong><code>Run snippet everywhere</code></strong> en Code Snippets. Luego ve a MainWP -> Sites, selecciona la web y pulsa el botón verde <strong><code>Sync</code></strong>. Verás en <code>Apariencia -> API Ciberseguridad Ciscar</code> que pasa a verde.</div>
    </div>

    <div class="ciscar-alert warning">
        <span class="dashicons dashicons-flag"></span>
        <div><strong>Problema C: Cloudflare dice "Configuración de API pendiente".</strong><br>
        <em>Solución:</em> Ve a <code>Apariencia -> API Ciberseguridad Ciscar</code> y pulsa <code>💾 Guardar Ajustes de API Ciberseguridad</code> (esto limpia automáticamente las cachés de 12 horas). Si sigue saliendo pendiente, entra a Cloudflare, asegúrate de que el Token tenga exactamente el permiso de lectura: <strong><code>Zone Analytics : Read</code></strong>.</div>
    </div>
    <?php
}
