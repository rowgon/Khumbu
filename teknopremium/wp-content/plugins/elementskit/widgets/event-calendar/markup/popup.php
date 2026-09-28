<?php

/**
 * Unified Event Calendar Popup Template
 * Works with: manual, the_events_calendar, google_calendar
 */

// Determine event source and prepare data
$event_source = $ekit_event_calendar_source;
// Initialize events array
$popup_events = [];

switch ($event_source) {
	case 'manual':
		if (!empty($ekit_event_calendar_manual_event_lists)) {
			foreach ($ekit_event_calendar_manual_event_lists as $index => $event) {
				$popup_events[] = [
					'id' => $event['_id'],
					'title' => $event['ekit_event_calendar_title'] ?? '',
					'description' => $event['ekit_event_calendar_description'] ?? '',
					'image' => \Elementor\Group_Control_Image_Size::get_attachment_image_html($event, 'ekit_event_calendar_thumbnail', 'ekit_event_calendar_event_banner_image'),
					'start' => $event['ekit_event_calendar_start_date'] ?? '',
					'end' => $event['ekit_event_calendar_end_date'] ?? '',
					'speaker' => $event['ekit_event_calendar_speaker'] ?? '',
					'location' => $event['ekit_event_calendar_location'] ?? '',
					'host' => $event['ekit_event_calendar_host'] ?? '',
					'category' => $event['ekit_event_calendar_category'] ?? '',
					'link' => [
						'show' => ($event['ekit_event_calendar_redirect_event_link'] == 'yes'),
						'url' => $event['ekit_event_calendar_event_link']['url'] ?? '',
						'is_external' => $event['ekit_event_calendar_event_link']['is_external'] ?? false,
						'nofollow' => $event['ekit_event_calendar_event_link']['nofollow'] ?? false,
					],
					'button_text' => $event['ekit_event_calendar_event_link_button_text'] ?? '',
					'title_size' => $event['ekit_event_calendar_title_size'] ?? 'h2',
				];
			}
		}
		break;

	case 'the_events_calendar':
		if (!empty($events['popup_data'])) {
			foreach ($events['popup_data'] as $event) {
				$popup_events[] = [
					'id' => $event['id'] ?? '',
					'title' => $event['title'] ?? '',
					'description' => $event['description'] ?? '',
					'image' => !empty($event['image']['src']) ? '<img src="' . esc_url($event['image']['src']) . '" alt="' . esc_attr($event['image']['alt']) . '">' : '',
					'start' => $event['start'] ?? '',
					'end' => $event['end'] ?? '',
					'speaker' => $event['guest'] ?? '',
					'location' => $event['location'] ?? '',
					'host' => '',
					'category' => $event['category'] ?? '',
					'link' => [
						'show' => !empty($event['url']),
						'url' => $event['url'] ?? '',
						'is_external' => true,
						'nofollow' => false,
					],
					'title_size' => 'h2',
				];
			}
		}
		break;

	case 'google_calendar':
		if (!empty($events)) {
			foreach ($events as $event) {
				$image_html = '';

				// Check for attachments and ensure the first one is an image
				if (!empty($event['attachments'][0]['fileId'])) {
					$file_id = $event['attachments'][0]['fileId'];
					$image_title = $event['attachments'][0]['title'] ?? '';

					/**
					 * CONSTRUCT THE IMAGE URL
					 * Option 1 (Thumbnail): Best for speed and avoids "too many redirects" errors.
					 * Option 2 (Direct): Best for full resolution.
					 */
					// We use &sz=w1000 to request a 1000px wide version of the image
					$image_src = "https://drive.google.com/thumbnail?id=" . $file_id . "&sz=w1000";

					$image_html = '<img src="' . esc_url($image_src) . '" alt="' . esc_attr($image_title) . '" class="ekit-event-gcal-img">';
				}

				$popup_events[] = [
					'id' => $event['id'] ?? '',
					'title' => $event['summary'] ?? '',
					'description' => $event['description'] ?? '',
					'image' => $image_html,
					'start' => $event['start']['date'] ?? $event['start']['dateTime'] ?? '',
					'end' => $event['end']['date'] ?? $event['end']['dateTime'] ?? '',
					'speaker' => $event['organizer']['email'] ?? '',
					'location' => $event['location'] ?? '',
					'host' => $event['creator']['displayName'] ?? $event['creator']['email'] ?? '',
					'category' => '',
					'link' => [
						'show' => !empty($event['htmlLink']),
						'url' => $event['htmlLink'] ?? '',
						'is_external' => true,
						'nofollow' => false,
					],
					'title_size' => 'h2',
				];
			}
		}
		break;
}

// Render popups
foreach ($popup_events as $popup_event) :
	// Format dates
	$start_formatted = $popup_event['start'] ? date_i18n('M j, Y · g:i A', strtotime($popup_event['start'])) : '';
	$end_formatted = $popup_event['end'] ? date_i18n('M j, Y · g:i A', strtotime($popup_event['end'])) : '';

	// Prepare link attributes
	$link_attrs = '';
	if ($popup_event['link']['show'] && !empty($popup_event['link']['url'])) {
		$link_attrs = 'href="' . esc_url($popup_event['link']['url']) . '"';
		if ($popup_event['link']['is_external']) {
			$link_attrs .= ' target="_blank"';
		}
		if ($popup_event['link']['nofollow']) {
			$link_attrs .= ' rel="nofollow"';
		}
	}
?>
	<div class="ekit-popup-wrapper" id="<?php echo esc_attr($popup_event['id']); ?>" data-popup-id="<?php echo esc_attr($popup_event['id']); ?>">
		<div class="ekit-popup-content">
			<!-- Close Button -->
			<button class="popup-close" aria-label="<?php echo esc_attr__('Close', 'elementskit'); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="15" height="15">
					<line x1="18" y1="6" x2="6" y2="18" />
					<line x1="6" y1="6" x2="18" y2="18" />
				</svg>
			</button>

			<!-- Hero Image -->
			<?php if (!empty($popup_event['image'])) : ?>
				<div class="popup-hero">
					<?php
					// PHPCS - Image HTML is already escaped in preparation
					echo $popup_event['image'];
					?>

					<!-- Category Tag -->
					<?php if (!empty($popup_event['category'])) : ?>
						<span class="popup-tag">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12">
								<circle cx="12" cy="12" r="10" />
								<line x1="12" y1="8" x2="12" y2="12" />
								<line x1="12" y1="16" x2="12.01" y2="16" />
							</svg>
							<?php echo esc_html($popup_event['category']); ?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Body -->
			<div class="popup-body">
				<!-- Title -->
				<?php if (!empty($popup_event['title'])) : ?>
					<div class="popup-title">
						<?php
						if ($event_source === 'manual' && !empty($popup_event['title_size'])) {
							echo sprintf(
								'<%1$s>%2$s</%1$s>',
								\Elementor\Utils::validate_html_tag($popup_event['title_size']),
								esc_html($popup_event['title'])
							);
						} else {
							echo '<h2>' . esc_html($popup_event['title']) . '</h2>';
						}
						?>
					</div>
				<?php endif; ?>

				<!-- Meta Information -->
				<div class="popup-meta">
					<?php if (!empty($popup_event['start']) || !empty($popup_event['end'])) : ?>
						<span class="meta-item">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="12" cy="12" r="10" />
								<polyline points="12 6 12 12 16 14" />
							</svg>
							<?php
							if (!empty($popup_event['start']) && !empty($popup_event['end'])) {
								echo esc_html(date_i18n('g:i A', strtotime($popup_event['start']))) . ' – ' . esc_html(date_i18n('g:i A', strtotime($popup_event['end'])));
							} elseif (!empty($popup_event['start'])) {
								echo esc_html(date_i18n('g:i A', strtotime($popup_event['start'])));
							}
							?>
						</span>
					<?php endif; ?>

					<?php if (!empty($popup_event['speaker'])) : ?>
						<span class="meta-item">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
								<circle cx="9" cy="7" r="4" />
								<path d="M23 21v-2a4 4 0 0 0-3-3.87" />
								<path d="M16 3.13a4 4 0 0 1 0 7.75" />
							</svg>
							<?php echo esc_html($popup_event['speaker']); ?>
						</span>
					<?php endif; ?>

					<?php if (!empty($popup_event['location'])) : ?>
						<span class="meta-item">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
								<circle cx="12" cy="10" r="3" />
							</svg>
							<?php echo esc_html($popup_event['location']); ?>
						</span>
					<?php endif; ?>

					<?php if (!empty($popup_event['host'])) : ?>
						<span class="meta-item">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
								<circle cx="12" cy="7" r="4" />
							</svg>
							<?php echo esc_html($popup_event['host']); ?>
						</span>
					<?php endif; ?>
				</div>

				<div class="popup-divider"></div>

				<!-- Description -->
				<?php if (!empty($popup_event['description'])) : ?>
					<div class="popup-description">
						<?php
						// PHPCS - the main text of a widget should not be escaped.
						if ($event_source === 'manual') {
							echo $this->parse_text_editor($popup_event['description']);
						} else {
							echo $this->parse_text_editor(wp_kses($popup_event['description'], \ElementsKit_Lite\Utils::get_kses_array()));
						}
						?>
					</div>
				<?php endif; ?>

				<!-- Date Band -->
				<?php if (!empty($popup_event['start']) || !empty($popup_event['end'])) : ?>
					<div class="popup-dates">
						<?php if (!empty($popup_event['start'])) : ?>
							<div class="date-box">
								<div class="date-box__label"><?php echo esc_html__('Start', 'elementskit'); ?></div>
								<div class="date-box__value-wrap">
									<div class="date-box__value"><?php echo esc_html($start_formatted); ?></div>
								</div>
							</div>
						<?php endif; ?>

						<?php if (!empty($popup_event['end'])) : ?>
							<div class="date-box">
								<div class="date-box__label"><?php echo esc_html__('End', 'elementskit'); ?></div>
								<div class="date-box__value-wrap">
									<div class="date-box__value"><?php echo esc_html($end_formatted); ?></div>
								</div>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- CTA Button -->
				<?php if ($popup_event['link']['show'] && !empty($popup_event['link']['url'])) : ?>
					<a <?php echo $link_attrs; // Already escaped above 
						?> class="popup-cta">
						<?php if ($ekit_event_calendar_source === 'manual') : ?>
							<?php echo esc_html(!empty($popup_event['button_text']) ? $popup_event['button_text'] : esc_html__('Register for Event', 'elementskit')); ?>
						<?php else : ?>
							<?php echo esc_html(!empty($ekit_event_calendar_event_popup_button) ? $ekit_event_calendar_event_popup_button : esc_html__('Register for Event', 'elementskit')); ?>
						<?php endif; ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endforeach; ?>