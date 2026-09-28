<?php
require __DIR__ . '/wp-load.php';

// Helper to escape non-ascii safely in JSON for Elementor
function tp_save_elementor_data($post_id, $data) {
    $json = wp_slash(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    update_post_meta($post_id, '_elementor_data', $json);
}

// 1. TRANSFORM PAGE ID 120: ABOUT / QUIÉNES SOMOS
echo "=== 1. TRANSFORMING ABOUT PAGE (ID 120) ===\n";
$about_json = get_post_meta(120, '_elementor_data', true);
if ($about_json) {
    $adata = json_decode($about_json, true);
    
    function transform_about_node(&$node) {
        if (is_array($node)) {
            if (isset($node['widgetType'])) {
                $wt = $node['widgetType'];
                
                // Headings
                if ($wt === 'heading' && isset($node['settings']['title'])) {
                    $t = $node['settings']['title'];
                    if (strpos($t, 'Architecture Is') !== false || strpos($t, 'Experience') !== false) {
                        $node['settings']['title'] = 'Arquitectura & Bienestar<br>sin Concesiones';
                    } elseif (strpos($t, 'Why Choose Us') !== false) {
                        $node['settings']['title'] = 'Por Qué Elegir Tecnopremium';
                    } elseif (strpos($t, 'We Make Furniture') !== false) {
                        $node['settings']['title'] = 'Ingeniería Bioclimática & Wellness de Alta Precisión';
                    } elseif (strpos($t, 'Build Your Dream') !== false) {
                        $node['settings']['title'] = 'Diseñamos tu Espacio Exterior de Lujo';
                    }
                }
                
                // Text Editors
                if ($wt === 'text-editor' && isset($node['settings']['editor'])) {
                    $e = $node['settings']['editor'];
                    if (strpos($e, 'Graziano') !== false) {
                        $node['settings']['editor'] = '<p style="color:#D4CB92; font-weight:600;">Dirección de Proyectos & Arquitectura Tecnopremium</p>';
                    } elseif (strpos($e, 'Neque porro') !== false || strpos($e, 'Lorem') !== false || strpos($e, 'Sed ut') !== false) {
                        $node['settings']['editor'] = '<p>Diseñamos atmósferas vivas donde la arquitectura contemporánea se funde con la hidroterapia de alto rendimiento y la excelencia estructural. Cada proyecto es ejecutado con materiales de ingeniería europea certificados para garantizar durabilidad, eficiencia energética y una estética atemporal.</p>';
                    }
                }
                
                // Counters
                if ($wt === 'counter' && isset($node['settings']['title'])) {
                    if (strpos($node['settings']['title'], 'Experiences') !== false) {
                        $node['settings']['title'] = 'Años de Experiencia en Proyectos Wellness';
                    }
                }
                
                // Flip Boxes / Feature boxes with people images
                if ($wt === 'flip-box' && isset($node['settings']['image']['id'])) {
                    $img_id = $node['settings']['image']['id'];
                    if (in_array($img_id, [65, 66, 67])) {
                        // Replace carpenters with luxury pergola & spa images
                        $node['settings']['image']['id'] = 175;
                    }
                }
                
                // Any image widget pointing to carpenters
                if ($wt === 'image' && isset($node['settings']['image']['id'])) {
                    if (in_array($node['settings']['image']['id'], [65, 66, 67])) {
                        $node['settings']['image']['id'] = 177;
                    }
                }
            }
            foreach ($node as &$v) {
                transform_about_node($v);
            }
        }
    }
    transform_about_node($adata);
    tp_save_elementor_data(120, $adata);
    echo "About Page updated successfully!\n";
}

// 2. TRANSFORM PAGE ID 112: CONTACT / CONTACTO
echo "=== 2. TRANSFORMING CONTACT PAGE (ID 112) ===\n";
$contact_json = get_post_meta(112, '_elementor_data', true);
if ($contact_json) {
    $cdata = json_decode($contact_json, true);
    
    function transform_contact_node(&$node) {
        if (is_array($node)) {
            if (isset($node['widgetType'])) {
                $wt = $node['widgetType'];
                
                if ($wt === 'heading' && isset($node['settings']['title'])) {
                    $t = $node['settings']['title'];
                    if (strpos($t, 'Add Your Heading') !== false || strpos($t, 'Contact') !== false) {
                        $node['settings']['title'] = 'Atención Arquitectónica & Contacto';
                    } elseif (strpos($t, 'Hours') !== false) {
                        $node['settings']['title'] = 'Horario Profesional';
                    } elseif (strpos($t, 'Parking') !== false) {
                        $node['settings']['title'] = 'Showroom & Atención B2B';
                    } elseif (strpos($t, 'Location') !== false) {
                        $node['settings']['title'] = 'Sede & Cobertura Nacional';
                    } elseif (strpos($t, 'Leave A Message') !== false) {
                        $node['settings']['title'] = 'Solicita tu Estudio Técnico Presupuestal';
                    }
                }
                
                if ($wt === 'text-editor' && isset($node['settings']['editor'])) {
                    $e = $node['settings']['editor'];
                    if (strpos($e, 'spacious, luxurious') !== false) {
                        $node['settings']['editor'] = '<p>Disponemos de equipo técnico especializado para asesorar a arquitectos, interioristas y particulares en la planificación e instalación llave en mano.</p>';
                    } elseif (strpos($e, 'very strategic, close to rice fields') !== false) {
                        $node['settings']['editor'] = '<p>Madrid & Barcelona (Atención previa cita técnica) · Cobertura integral de instalación y servicio técnico en toda España y Portugal.</p>';
                    } elseif (strpos($e, 'Lorem ipsum') !== false) {
                        $node['settings']['editor'] = '<p>Completa el formulario o contáctanos para recibir especificaciones CAD/BIM, tarifas de proyecto y asesoramiento personalizado de nuestro equipo de ingeniería.</p>';
                    }
                }
            }
            foreach ($node as &$v) {
                transform_contact_node($v);
            }
        }
    }
    transform_contact_node($cdata);
    tp_save_elementor_data(112, $cdata);
    echo "Contact Page updated successfully!\n";
}

// 3. TRANSFORM PAGE ID 128: FAQ / PREGUNTAS FRECUENTES
echo "=== 3. TRANSFORMING FAQ PAGE (ID 128) ===\n";
$faq_json = get_post_meta(128, '_elementor_data', true);
if ($faq_json) {
    $fdata = json_decode($faq_json, true);
    
    function transform_faq_node(&$node) {
        if (is_array($node)) {
            if (isset($node['widgetType'])) {
                $wt = $node['widgetType'];
                
                if ($wt === 'heading' && isset($node['settings']['title'])) {
                    $t = $node['settings']['title'];
                    if (strpos($t, 'Frequently Asked') !== false) {
                        $node['settings']['title'] = 'Preguntas Frecuentes';
                    } elseif (strpos($t, 'Customer Service') !== false) {
                        $node['settings']['title'] = 'Ingeniería, Instalación & Garantías Tecnopremium';
                    }
                }
                
                if ($wt === 'accordion' && isset($node['settings']['tabs'])) {
                    // Let's replace the accordion items with professional Spanish architecture/wellness FAQ items
                    $node['settings']['tabs'] = [
                        [
                            'tab_title' => '¿Qué requisitos de suelo o estructura necesita un Spa o Swimspa Tecnopremium?',
                            'tab_content' => '<p>Nuestros spas están diseñados con estructura autoportante de alta resistencia. Para su instalación en ático, terraza o jardín, recomendamos una solera nivelada capaz de soportar entre 450 kg/m² y 600 kg/m² según el modelo. Nuestro departamento técnico proporciona la ficha de cargas exacta para tu arquitecto o aparejador.</p>'
                        ],
                        [
                            'tab_title' => '¿Cómo funciona el aislamiento térmico ProLast™ y cuánto consume?',
                            'tab_content' => '<p>El sistema de aislamiento ProLast™ combina espuma de alta densidad multicapa con cobertores térmicos herméticos, reduciendo las pérdidas de calor hasta un 75%. El consumo medio en modo mantenimiento es extremadamente bajo y compatible con aerotermia y energía solar fotovoltaica.</p>'
                        ],
                        [
                            'tab_title' => '¿Las pérgolas bioclimáticas son totalmente estancas a la lluvia?',
                            'tab_content' => '<p>Sí. Las lamas orientables de aluminio motorizado cuentan con juntas de estanqueidad perimetral y un sistema oculto de evacuación de aguas pluviales integrado directamente en los pilares estructurales, garantizando impermeabilidad total.</p>'
                        ],
                        [
                            'tab_title' => '¿Se pueden motorizar e integrar con domótica residencial?',
                            'tab_content' => '<p>Por supuesto. Todas las pérgolas y cerramientos Tecnopremium pueden operarse mediante mando a distancia o integrarse en sistemas domóticos (KNX, Alexa, Google Home, Somfy IO) con sensores inteligentes de lluvia y viento automáticos.</p>'
                        ],
                        [
                            'tab_title' => '¿Cuál es el plazo de entrega e instalación oficial en obra?',
                            'tab_content' => '<p>Los modelos en stock directo se entregan e instalan mediante servicio técnico oficial en un plazo de 7 a 15 días laborables. Para configuraciones estructurales a medida, el plazo estimado es de 3 a 5 semanas.</p>'
                        ],
                        [
                            'tab_title' => '¿Qué garantía oficial ofrece Tecnopremium?',
                            'tab_content' => '<p>Ofrecemos 10 años de garantía oficial en la estructura de spas y pérgolas, 5 años en el casco acrílico ProLast™ y 3 años completos en motorizaciones, bombas e ingeniería electrónica.</p>'
                        ]
                    ];
                }
            }
            foreach ($node as &$v) {
                transform_faq_node($v);
            }
        }
    }
    transform_faq_node($fdata);
    tp_save_elementor_data(128, $fdata);
    echo "FAQ Page updated successfully!\n";
}

// Clear all Elementor caches
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_element_cache'");
if (class_exists('\Elementor\Plugin')) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "SUCCESS: About, Contact, and FAQ pages fully translated and upgraded!\n";
