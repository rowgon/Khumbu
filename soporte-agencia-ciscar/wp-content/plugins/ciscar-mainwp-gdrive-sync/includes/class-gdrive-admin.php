<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ciscar_GDrive_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_ajax_ciscar_gdrive_test_connection', array( __CLASS__, 'ajax_test_connection' ) );
	}

	public static function add_admin_menu() {
		add_menu_page(
			'Google Drive Sync',
			'Google Drive Sync',
			'manage_options',
			'ciscar-gdrive-sync',
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-cloud-upload',
			30
		);
	}

	public static function register_settings() {
		register_setting( 'ciscar_gdrive_settings_group', 'ciscar_gdrive_sync_enabled' );
		register_setting( 'ciscar_gdrive_settings_group', 'ciscar_gdrive_service_account_json' );
		register_setting( 'ciscar_gdrive_settings_group', 'ciscar_gdrive_root_folder_id' );
		register_setting( 'ciscar_gdrive_settings_group', 'ciscar_gdrive_organize_by_site' );
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
		$client     = new Ciscar_GDrive_Client();
		$sa_email   = $client->get_client_email();
		?>
		<div class="wrap">
			<h1 style="display:flex; align-items:center; gap:10px;">
				<span class="dashicons dashicons-cloud-upload" style="font-size:32px; width:32px; height:32px;"></span>
				Císcar MainWP Google Drive Sync
			</h1>
			<p>Sincronización automática de informes PDF de MainWP Pro Reports con Google Drive.</p>

			<h2 class="nav-tab-wrapper">
				<a href="?page=ciscar-gdrive-sync&tab=settings" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">Configuración</a>
				<a href="?page=ciscar-gdrive-sync&tab=logs" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>">Historial de Subidas</a>
			</h2>

			<?php if ( 'settings' === $active_tab ) : ?>
				<div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px; margin-top:15px; max-width:900px;">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'ciscar_gdrive_settings_group' );
						do_settings_sections( 'ciscar_gdrive_settings_group' );
						?>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">Estado de la Sincronización</th>
								<td>
									<label>
										<input type="checkbox" name="ciscar_gdrive_sync_enabled" value="yes" <?php checked( get_option( 'ciscar_gdrive_sync_enabled', 'yes' ), 'yes' ); ?> />
										Activar la sincronización automática de informes en PDF a Google Drive
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row">Credenciales Service Account (JSON)</th>
								<td>
									<textarea name="ciscar_gdrive_service_account_json" rows="8" class="large-text code" placeholder='{"type": "service_account", "project_id": "...", "private_key": "...", "client_email": "..."}'><?php echo esc_textarea( get_option( 'ciscar_gdrive_service_account_json', '' ) ); ?></textarea>
									<p class="description">Pega el contenido completo del archivo <code>JSON</code> de tu <strong>Cuenta de Servicio de Google Cloud</strong>.</p>
									<?php if ( ! empty( $sa_email ) ) : ?>
										<div style="margin-top:8px; padding:10px; background:#e0f2fe; border-left:4px solid #0284c7; border-radius:3px; color:#0369a1;">
											<strong>Correo de la Cuenta de Servicio detectado:</strong><br/>
											<code style="user-select:all; background:#fff; padding:3px 6px; font-weight:bold; color:#0f172a; border:1px solid #bae6fd;"><?php echo esc_html( $sa_email ); ?></code>
											<p style="margin:5px 0 0 0; font-size:12px;">Comparte tu carpeta de Google Drive con este correo asignándole el rol de <strong>Editor</strong>.</p>
										</div>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">ID de Carpeta Raíz en Google Drive</th>
								<td>
									<input type="text" name="ciscar_gdrive_root_folder_id" value="<?php echo esc_attr( get_option( 'ciscar_gdrive_root_folder_id', '' ) ); ?>" class="regular-text" placeholder="1a2b3c4d5e6f7g8h9i..." />
									<p class="description">ID de la carpeta principal de Google Drive donde se guardarán los informes (las letras y números al final de la URL en la barra de direcciones).</p>
								</td>
							</tr>
							<tr>
								<th scope="row">Organización de Carpetas</th>
								<td>
									<label>
										<input type="checkbox" name="ciscar_gdrive_organize_by_site" value="yes" <?php checked( get_option( 'ciscar_gdrive_organize_by_site', 'yes' ), 'yes' ); ?> />
										Crear automáticamente subcarpetas por Nombre de Web y Año (ej: <code>Informes > MiSitioWeb > 2026</code>)
									</label>
								</td>
							</tr>
						</table>

						<?php submit_button( 'Guardar Configuración' ); ?>
					</form>

					<hr style="margin:25px 0;" />
					<h3>Probar Conexión con Google Drive</h3>
					<p class="description" style="margin-bottom:10px;"><em>Asegúrate de pulsar primero <strong>"Guardar Configuración"</strong> si acabas de ingresar o modificar los campos superiores.</em></p>
					<button type="button" id="ciscar-test-gdrive-btn" class="button button-secondary">
						<span class="dashicons dashicons-admin-plugins" style="vertical-align:middle;"></span> Probar Conexión
					</button>
					<span id="ciscar-gdrive-test-status" style="margin-left:10px; font-weight:bold;"></span>

					<script>
					jQuery(document).ready(function($) {
						$('#ciscar-test-gdrive-btn').on('click', function() {
							var $btn = $(this);
							var $status = $('#ciscar-gdrive-test-status');
							$btn.prop('disabled', true);
							$status.css('color', '#666').text('Verificando credenciales...');

							$.post(ajaxurl, { action: 'ciscar_gdrive_test_connection' }, function(response) {
								$btn.prop('disabled', false);
								if (response.success) {
									$status.css('color', 'green').html('&#10004; ' + response.data);
								} else {
									$status.css('color', 'red').html('&#10008; ' + response.data);
								}
							});
						});
					});
					</script>
				</div>
			<?php else : ?>
				<div style="margin-top:15px;">
					<?php self::render_logs_table(); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function render_logs_table() {
		$logs = Ciscar_GDrive_Logger::get_logs( 50, 0 );
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:140px;">Fecha / Hora</th>
					<th>Sitio Web</th>
					<th>Título del Informe</th>
					<th>Nombre del Archivo PDF</th>
					<th>Estado</th>
					<th>Acción / Enlace</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $logs ) ) : ?>
					<tr>
						<td colspan="6">No hay registros de subidas aún. Los informes generados por MainWP aparecerán aquí automáticamente.</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( $log->created_at ); ?></td>
							<td><strong><?php echo esc_html( $log->site_name ); ?></strong></td>
							<td><?php echo esc_html( $log->report_title ); ?></td>
							<td><code><?php echo esc_html( $log->file_name ); ?></code></td>
							<td>
								<?php if ( 'success' === $log->status ) : ?>
									<span style="color:#2e7d32; font-weight:bold;">&#10004; Subido</span>
								<?php else : ?>
									<span style="color:#c62828; font-weight:bold;" title="<?php echo esc_attr( $log->error_message ); ?>">&#10008; Error</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $log->gdrive_file_url ) ) : ?>
									<a href="<?php echo esc_url( $log->gdrive_file_url ); ?>" target="_blank" class="button button-small">
										<span class="dashicons dashicons-external" style="font-size:14px; width:14px; height:14px; vertical-align:middle;"></span> Ver en Drive
									</a>
								<?php else : ?>
									<span style="color:#666; font-size:12px;"><?php echo esc_html( $log->error_message ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	public static function ajax_test_connection() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Acceso denegado.' );
		}

		$client = new Ciscar_GDrive_Client();
		if ( ! $client->is_configured() ) {
			wp_send_json_error( 'Faltan credenciales de Google Service Account. Completa los campos y haz clic en "Guardar Configuración".' );
		}

		$token = $client->get_access_token();
		if ( is_wp_error( $token ) ) {
			wp_send_json_error( $token->get_error_message() );
		}

		$sa_email = $client->get_client_email();
		$root_folder_id = get_option( 'ciscar_gdrive_root_folder_id', '' );

		if ( empty( $root_folder_id ) ) {
			wp_send_json_error( 'Autenticado con éxito, pero el campo ID de Carpeta Raíz está vacío. Pega el ID de tu carpeta de Google Drive y haz clic en "Guardar Configuración".' );
		}

		$folder_res = $client->get_or_create_folder( 'Conexion_Exitosa_Ciscar', $root_folder_id );
		if ( is_wp_error( $folder_res ) ) {
			$err_msg = $folder_res->get_error_message();
			$hint = ' ' . sprintf( 'Comprueba que has compartido tu carpeta de Google Drive asignándole el rol de <strong>Editor</strong> al correo de la Cuenta de Servicio: <code>%s</code>', esc_html( $sa_email ) );
			wp_send_json_error( 'Autenticado correctamente, pero hubo un problema al acceder a la carpeta (' . esc_html( $err_msg ) . ').' . $hint );
		}

		wp_send_json_success( '¡Conexión exitosa! Autenticado correctamente con la Cuenta de Servicio y verificado el acceso de escritura en Google Drive.' );
	}
}
