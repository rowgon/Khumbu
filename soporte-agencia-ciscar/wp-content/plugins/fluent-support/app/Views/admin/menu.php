<?php defined('ABSPATH') or die; ?>
<div id='alpha_app'>
    <div class="fframe_app">
        <div class="fs_main_navbar">
            <div class="menu_logo_holder">
                <a href="<?php echo esc_url($base_url); ?>">
                    <img src="<?php echo esc_url($logo); ?>" alt="Logo" />
                </a>
            </div>

            <div class="fs_nav_container">
                <ul class="fs_nav_menu">
                    <?php foreach ($menuItems as $item): ?>
                        <?php if (!empty($item['children'])): ?>
                            <li data-key="<?php echo esc_attr($item['key']); ?>" class="fs_nav_item fs_item_<?php echo esc_attr($item['key']); ?> fs_has_dropdown">
                                <button class="fs_nav_link fs_dropdown_trigger" type="button">
                                    <?php echo esc_html($item['label']); ?>
                                    <span class="fs_dropdown_arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </button>
                                <ul class="fs_dropdown_menu">
                                    <?php foreach ($item['children'] as $child): ?>
                                        <li data-key="<?php echo esc_attr($child['key']); ?>" class="fs_dropdown_item fs_item_<?php echo esc_attr($child['key']); ?>">
                                            <a class="fs_dropdown_link" href="<?php echo esc_url($child['permalink']); ?>">
                                                <?php echo esc_html($child['label']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li data-key="<?php echo esc_attr($item['key']); ?>" class="fs_nav_item fs_item_<?php echo esc_attr($item['key']); ?>">
                                <a class="fs_nav_link" href="<?php echo esc_url($item['permalink']); ?>">
                                    <?php echo esc_html($item['label']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>

                <ul class="fs_nav_menu fs_secondary_menu">
                    <fs-notifications-pop></fs-notifications-pop>
                    <li class="fs_nav_item fs_item_color_mode">
                        <color-mode></color-mode>
                    </li>
                    <?php if($secondaryItems): ?>
                    <?php foreach ($secondaryItems as $item): ?>
                        <li data-key="<?php echo esc_attr($item['key']); ?>" class="fs_nav_item fs_item_<?php echo esc_attr($item['key']); ?>">
                            <a data-key="<?php echo esc_attr($item['key']); ?>" class="fs_nav_right_item fs_item_<?php echo esc_attr($item['key']); ?>" href="<?php echo esc_url($item['permalink']); ?>">
                                <?php if($item['key'] === 'settings'): ?>
                                    <img src="<?php echo esc_url($settingsLogo); ?>" alt="Settings" width="20" height="20" />
                                <?php elseif($item['key'] === 'upgrade_to_pro'): ?>
                                    <?php echo esc_attr($item['label']); ?>
                                <?php else: ?>
                                    <?php echo esc_attr($item['label']); ?>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <ul class="fs_nav_menu fs_secondary_menu fs_mobile_secondary_menu">
                <fs-notifications-pop></fs-notifications-pop>
                <li class="fs_nav_item fs_item_color_mode">
                    <color-mode></color-mode>
                </li>
                <?php if($secondaryItems): ?>
                <?php foreach ($secondaryItems as $item): ?>
                    <li data-key="<?php echo esc_attr($item['key']); ?>" class="fs_nav_item fs_item_<?php echo esc_attr($item['key']); ?>">
                        <a data-key="<?php echo esc_attr($item['key']); ?>" class="fs_nav_right_item fs_item_<?php echo esc_attr($item['key']); ?>" href="<?php echo esc_url($item['permalink']); ?>">
                            <?php if($item['key'] === 'settings'): ?>
                                <img src="<?php echo esc_url($settingsLogo); ?>" alt="Settings" width="20" height="20" />
                            <?php elseif($item['key'] === 'upgrade_to_pro'): ?>
                                <img src="<?php echo esc_url($upgradeLogo); ?>" alt="Settings" width="20" height="20" />
                                <?php echo esc_attr($item['label']); ?>
                            <?php else: ?>
                                <?php echo esc_attr($item['label']); ?>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <div class="fs_mobile_menu_container">
                <div class="fs_offcanvas_menu_overlay" data-fs-offcanvas-menu-overlay></div>

                <div class="fs_menu_toggle_button" data-fs-menu-toggle>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                        <path d="M8.3335 4.16602L16.6668 4.16602" stroke="currentColor" stroke-width="1.5"
                              stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M3.3335 10L16.6668 10" stroke="currentColor" stroke-width="1.5"
                              stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M3.3335 15.832L11.6668 15.832" stroke="currentColor" stroke-width="1.5"
                              stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </div>

                <div class="fs_offcanvas_menu" data-fs-offcanvas-menu>
                    <div class="fs_offcanvas_menu_content">
                        <button class="fs_offcanvas_menu_close" data-fs-offcanvas-menu-close>
                            <span class="icon">
                                <svg class="cross" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15.8337 4.1665L4.16699 15.8332M4.16699 4.1665L15.8337 15.8332"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round"></path>
                                </svg>
                            </span>
                        </button>
                        <div class="fs_offcanvas_menu_list">
                            <?php foreach ($menuItems as $item): ?>
                                <?php if (!empty($item['children'])): ?>
                                    <?php foreach ($item['children'] as $child): ?>
                                        <div class="fs_offcanvas_menu_item">
                                            <div class="fs_offcanvas_menu_label">
                                                <a href="<?php echo esc_url($child['permalink']); ?>"><?php echo esc_html($child['label']); ?></a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="fs_offcanvas_menu_item">
                                        <div class="fs_offcanvas_menu_label">
                                            <a href="<?php echo esc_url($item['permalink']); ?>"><?php echo esc_html($item['label']); ?></a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <div class="fframe_body">
            <router-view></router-view>
        </div>
    </div>
</div>
