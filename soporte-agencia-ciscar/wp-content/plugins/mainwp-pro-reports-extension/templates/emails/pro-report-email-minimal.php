<?php

defined( 'ABSPATH' ) || exit;

$heading    = $report->heading;
$from_email = $report->femail;

do_action( 'mainwp_pro_reports_email_header' );

?>

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
	</head>
	<body marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background-color:#f7f7f7;">
		<div id="mainwp-report-wrapper">
			<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
				<tr>
					<td align="center" valign="top">
						<table border="0" cellpadding="0" cellspacing="0" width="600" style="margin-top: 50px; background-color: #ffffff; border: 1px solid #dedede; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1); border-radius: 3px;">
							<tr>
								<td align="center" valign="top">
									<!-- Header -->
									<table border="0" cellpadding="0" cellspacing="0" width="600">
										<tr>
											<td id="header_wrapper" style="padding: 36px 48px; display: block;">
												<h1 style="text-align:center;color:#333;"><?php echo $heading; ?></h1>
											</td>
										</tr>
									</table>
									<!-- End Header -->
								</td>
							</tr>
							<tr>
								<td align="center" valign="top">
									<!-- Body -->
									<table border="0" cellpadding="0" cellspacing="0" width="600">
										<tr>
											<td valign="top">
												<!-- Content -->
												<table border="0" cellpadding="20" cellspacing="0" width="100%">
													<tr>
														<td valign="top">
															<div>
															<?php
															if ( $email_message ) {
																$email_message = stripslashes( $email_message );
																echo wp_kses_post( wpautop( wptexturize( $email_message ) ) );
															}
															?>
														</div>
													</td>
												</tr>
											</table>
											<!-- End Content -->
										</td>
									</tr>
								</table>
								<!-- End Body -->
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align="center" valign="top">
					<!-- Footer -->
					<table border="0" cellpadding="10" cellspacing="0" width="600">
						<tr>
							<td valign="top">
								<table border="0" cellpadding="10" cellspacing="0" width="100%">
									<tr>
										<td colspan="2" align="center" valign="middle">
											<?php echo __( 'Have questions? Email us at ', 'mainwp-pro-reports-extension' ) . $from_email; ?>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<!-- End Footer -->
				</td>
			</tr>
		</table>
	</div>
</body>
</html>
<?php

do_action( 'mainwp_pro_reports_email_footer' );
