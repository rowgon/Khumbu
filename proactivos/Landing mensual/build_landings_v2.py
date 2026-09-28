import json
import uuid
import copy

def generate_id():
    return uuid.uuid4().hex[:7]

def new_id_for_element(element):
    element['id'] = generate_id()
    if 'settings' in element:
        for key, value in element['settings'].items():
            if isinstance(value, list):
                for item in value:
                    if isinstance(item, dict) and '_id' in item:
                        item['_id'] = generate_id()
    if 'elements' in element:
        for child in element['elements']:
            new_id_for_element(child)
    return element

with open("elementor-15256-2026-08-03.json", "r", encoding="utf-8") as f:
    template = json.load(f)

# Extract building blocks
orig_elements = template['content'][0]['elements']
block_hero_title = copy.deepcopy(orig_elements[0])
block_hero_text = copy.deepcopy(orig_elements[1])
block_section_title_text = copy.deepcopy(orig_elements[2])
block_section_grid5_intro = copy.deepcopy(orig_elements[3])
block_section_grid5 = copy.deepcopy(orig_elements[4])

block_huge = orig_elements[5]
block_huge_table_title = copy.deepcopy(block_huge['elements'][0])
block_huge_table_html = copy.deepcopy(block_huge['elements'][1])
block_huge_table_closing = copy.deepcopy(block_huge['elements'][2])
block_huge_bullets_title = copy.deepcopy(block_huge['elements'][3])
block_huge_bullets_intro = copy.deepcopy(block_huge['elements'][4])
block_huge_bullets_list = copy.deepcopy(block_huge['elements'][5])
block_huge_grid4 = copy.deepcopy(block_huge['elements'][6])
block_huge_cta = copy.deepcopy(block_huge['elements'][7])

def set_text(el, text, target='editor'):
    if target == 'title':
        # the heading widget might be inside a container
        if el['elType'] == 'container':
            el['elements'][0]['settings']['title'] = text
        else:
            el['settings']['title'] = text
    elif target == 'html':
        if el['elType'] == 'container':
            el['elements'][0]['settings']['html'] = text
        else:
            el['settings']['html'] = text
    else: # editor
        if el['elType'] == 'container':
            el['elements'][0]['settings']['editor'] = text
        else:
            el['settings']['editor'] = text

def create_hero(title, text):
    t = copy.deepcopy(block_hero_title)
    set_text(t, title, 'title')
    txt = copy.deepcopy(block_hero_text)
    set_text(txt, text, 'editor')
    new_id_for_element(t)
    new_id_for_element(txt)
    return [t, txt]

def create_simple_section(title, text):
    s = copy.deepcopy(block_section_title_text)
    set_text(s['elements'][0], title, 'title')
    set_text(s['elements'][1], text, 'editor')
    new_id_for_element(s)
    return [s]

def create_grid(title, intro, items, closing, use_grid5=True):
    if use_grid5:
        # Title + intro
        s_intro = copy.deepcopy(block_section_grid5_intro)
        set_text(s_intro['elements'][0], title, 'title')
        set_text(s_intro['elements'][1], intro, 'editor')
        new_id_for_element(s_intro)
        
        # Grid + closing
        s_grid = copy.deepcopy(block_section_grid5)
        # s_grid elements: [0] = grid container, [1] = closing text
        grid_container = s_grid['elements'][0]
        original_item = grid_container['elements'][0]
        new_items = []
        for item_text in items:
            new_item = copy.deepcopy(original_item)
            new_item['elements'][1]['settings']['editor'] = f"<p><strong>{item_text}</strong></p>"
            new_items.append(new_item)
        grid_container['elements'] = new_items
        grid_container['settings']['grid_columns_grid'] = {"unit":"fr", "size": min(len(items), 5), "sizes":[]}
        
        if closing:
            set_text(s_grid['elements'][1], closing, 'editor')
        else:
            s_grid['elements'] = [grid_container] # remove closing text
            
        new_id_for_element(s_grid)
        return [s_intro, s_grid]
    else:
        # grid4 container structure
        s_grid4 = copy.deepcopy(block_huge_grid4)
        set_text(s_grid4['elements'][0], title, 'title')
        set_text(s_grid4['elements'][1], intro, 'editor')
        
        grid_container = s_grid4['elements'][2]
        original_item = grid_container['elements'][0]
        new_items = []
        for item_text in items:
            new_item = copy.deepcopy(original_item)
            new_item['elements'][1]['settings']['editor'] = f"<p><strong>{item_text}</strong></p>"
            new_items.append(new_item)
        grid_container['elements'] = new_items
        grid_container['settings']['grid_columns_grid'] = {"unit":"fr", "size": min(len(items), 4), "sizes":[]}
        
        if closing:
            set_text(s_grid4['elements'][3], closing, 'editor')
        else:
            s_grid4['elements'] = s_grid4['elements'][:3] # remove closing
            
        new_id_for_element(s_grid4)
        return [s_grid4]

def create_table_section(title, headers, rows, closing):
    t_title = copy.deepcopy(block_huge_table_title)
    set_text(t_title, title, 'title')
    
    html = f"""<div class="mi-tabla-ajustada">
    <table>
        <thead>
            <tr>\n"""
    for h in headers:
        html += f"                <th>{h}</th>\n"
    html += """            </tr>
        </thead>
        <tbody>\n"""
    for row in rows:
        html += "            <tr>\n"
        for i, cell in enumerate(row):
            if i == 0:
                html += f"                <td><strong>{cell}</strong></td>\n"
            else:
                html += f"                <td>{cell}</td>\n"
        html += "            </tr>\n"
        
    html += """        </tbody>
    </table>
</div>

<style>
.mi-tabla-ajustada { margin: 20px 0; font-family: 'Suisse Intl', sans-serif; border: 1px solid #59819933; border-radius: 10px; overflow-x: auto; -webkit-overflow-scrolling: touch; box-shadow: none !important; }
.mi-tabla-ajustada table { width: 100%; min-width: 500px; border-collapse: collapse; background-color: #FFFFFF; margin: 0; }
.mi-tabla-ajustada thead th { background-color: #fcfcfc; color: #598199; padding: 12px 15px; text-align: center; font-size: 13px; font-weight: 800; border-bottom: 1px solid #f0f0f0; }
.mi-tabla-ajustada td { padding: 12px 15px; color: #598199; font-size: 12px; border-bottom: 1px solid #f0f0f0; text-align: center; }
.mi-tabla-ajustada td:first-child { text-align: left; font-weight: 500; }
.mi-tabla-ajustada tr td:last-child { background-color: #f8fbff; font-weight: 600; }
.mi-tabla-ajustada tr:last-child td { border-bottom: none; }
@media (max-width: 600px) { .mi-tabla-ajustada thead th { font-size: 11px; padding: 10px; } .mi-tabla-ajustada td { font-size: 11px; padding: 10px; } }
</style>"""

    t_html = copy.deepcopy(block_huge_table_html)
    set_text(t_html, html, 'html')
    
    t_closing = copy.deepcopy(block_huge_table_closing)
    set_text(t_closing, closing, 'editor')
    
    new_id_for_element(t_title)
    new_id_for_element(t_html)
    new_id_for_element(t_closing)
    return [t_title, t_html, t_closing]

def create_bullets_section(title, intro, bullets, closing):
    t_title = copy.deepcopy(block_huge_bullets_title)
    set_text(t_title, title, 'title')
    
    t_intro = copy.deepcopy(block_huge_bullets_intro)
    set_text(t_intro, intro, 'editor')
    
    t_bullets = copy.deepcopy(block_huge_bullets_list)
    bullets_html = "<ul>"
    for b in bullets:
        bullets_html += f"<li>{b}</li>"
    bullets_html += "</ul>"
    set_text(t_bullets, bullets_html, 'editor')
    
    # Using hero_text block for closing to have a standalone text editor, 
    # but we need it inside the huge block logic? Actually we can just return a list of widgets.
    # The original template puts all these widgets inside ONE huge container.
    # We can just wrap them in a container, or put them directly. Wait, the template has them directly in the huge container.
    
    t_closing = copy.deepcopy(block_huge_table_closing)
    set_text(t_closing, closing, 'editor')
    
    new_id_for_element(t_title)
    new_id_for_element(t_intro)
    new_id_for_element(t_bullets)
    new_id_for_element(t_closing)
    return [t_title, t_intro, t_bullets, t_closing]

def create_cta(title, text):
    t = copy.deepcopy(block_huge_cta)
    set_text(t['elements'][0], title, 'title')
    set_text(t['elements'][1], text, 'editor')
    new_id_for_element(t)
    return [t]

# === LANDING 1: Hipoteca Puente ===
def build_landing_1():
    elements = []
    # 1. Hero
    elements.extend(create_hero(
        "Hipoteca Puente con Capital Privado",
        '''<p>Una <strong>hipoteca puente con capital privado</strong> permite obtener liquidez de forma rápida utilizando un inmueble como garantía mientras se completa una venta, se cierra una operación o llega un ingreso previsto. En ProActivo Finance estudiamos operaciones en toda España con un enfoque práctico, flexible y orientado a resolver necesidades reales de financiación.</p><p>Cuando la banca tarda demasiado o no entiende la urgencia del caso, el <strong>capital privado</strong> puede actuar como una alternativa eficaz para empresas, inversores, promotores y propietarios con patrimonio inmobiliario. Analizamos el valor del activo, la salida de la operación y la viabilidad global, no solo el historial bancario.</p><p>Si necesitas una respuesta ágil, puedes solicitar una primera valoración desde <a href="https://proactivofinance.com/"><strong>ProActivo Finance</strong></a> y saber si tu inmueble permite estructurar una operación con <strong>firma ante notario</strong>.</p>'''
    ))
    # 2. Qué es
    elements.extend(create_simple_section(
        "¿Qué es una hipoteca puente con capital privado?",
        '''<p>La hipoteca puente con capital privado es una financiación temporal respaldada por una garantía inmobiliaria. Su función es cubrir una necesidad de liquidez durante un periodo concreto, normalmente hasta que se produce un hecho previsto: la venta de un inmueble, la entrada de fondos, la cancelación de una deuda o la formalización de una operación empresarial.</p><p>A diferencia de una hipoteca puente bancaria, el análisis no se limita a criterios automatizados. En ProActivo Finance revisamos la garantía, las cargas existentes, el importe solicitado, el plazo necesario y la estrategia de salida. Esto permite valorar operaciones que pueden quedar bloqueadas en la banca por ASNEF, incidencias puntuales, falta de ingresos demostrables o urgencias registrales.</p><p>Este planteamiento es especialmente útil cuando existe una oportunidad clara, pero el cliente necesita actuar antes de que el mercado, el vendedor o una situación legal avance. En operaciones similares, también trabajamos soluciones como el <a href="https://proactivofinance.com/prestamo-puente-con-capital-privado-en-madrid"><strong>préstamo puente con capital privado en Madrid</strong></a>, adaptando el análisis a la ubicación y al tipo de activo.</p>'''
    ))
    
    # We will put all subsequent sections into a new "huge" container to maintain the exact layout behavior, 
    # except the grid5 which has its own structure.
    # Wait, the original template uses a huge container because of a background or padding. 
    # Let's check block_huge. It has a gradient background.
    huge_container = copy.deepcopy(block_huge)
    huge_container['elements'] = [] # clear elements
    
    # 3. Cuándo solicitar
    huge_container['elements'].extend(create_bullets_section(
        "¿Cuándo solicitar una hipoteca puente con capital privado?",
        '''<p>Este tipo de financiación encaja cuando el tiempo es un factor crítico y existe un inmueble que puede respaldar la operación. No se trata de endeudarse sin planificación, sino de utilizar el patrimonio inmobiliario para ganar margen de maniobra.</p>''',
        [
            "Comprar un inmueble antes de vender otro sin perder una oportunidad de mercado.",
            "Cancelar cargas, embargos o deudas urgentes antes de que el problema avance.",
            "Aportar liquidez temporal a una empresa con patrimonio inmobiliario.",
            "Completar una reforma, promoción o inversión con salida prevista.",
            "Reordenar una operación inmobiliaria que la banca no resuelve a tiempo."
        ],
        '''<p>En todos estos casos, la clave está en definir bien el importe, el plazo y la salida. La operación debe tener sentido económico, una garantía suficiente y una formalización transparente.</p>'''
    ))
    
    # 4. Ventajas
    huge_container['elements'].append(copy.deepcopy(block_huge_bullets_intro)) # spacer basically
    huge_container['elements'].extend(create_table_section(
        "Ventajas frente a la banca",
        ["Necesidad", "Cómo ayuda el capital privado"],
        [
            ["Rapidez", "Permite obtener una respuesta ágil cuando la operación no puede esperar."],
            ["Flexibilidad", "Valora el inmueble, la salida prevista y el contexto del cliente."],
            ["Seguridad", "La operación se formaliza ante notario con condiciones claras."]
        ],
        '''<p>Además, podemos estudiar operaciones con incidencias siempre que el inmueble tenga valor suficiente y exista una salida razonable. Para clientes que buscan liquidez con respaldo inmobiliario, también puede ser útil revisar opciones de <a href="https://proactivofinance.com/prestamos-con-garantia-hipotecaria-en-barcelona-proactive"><strong>préstamos con garantía hipotecaria en Barcelona</strong></a> como referencia de estructura.</p>'''
    ))
    
    # 5. Qué inmuebles (using grid 4 format)
    huge_container['elements'].extend(create_grid(
        "¿Qué inmuebles pueden servir como garantía?",
        '''<p>Estudiamos activos ubicados en España con documentación revisable y valor suficiente para sostener la operación. La ubicación, el estado registral, las cargas existentes y la liquidez del activo influyen en la propuesta final.</p>''',
        [
            "Viviendas, pisos, chalets y activos residenciales.",
            "Locales, oficinas, naves o inmuebles vinculados a actividad empresarial.",
            "Terrenos urbanos, solares o activos con potencial de desarrollo.",
            "Inmuebles con cargas que puedan regularizarse dentro de la operación."
        ],
        '''<p>Cuando la finalidad está vinculada a una oportunidad de compra, reforma o reposicionamiento, el análisis puede conectarse con soluciones de <a href="https://proactivofinance.com/prestamos-para-inversion-inmobiliaria-en-madrid/"><strong>préstamos para inversión inmobiliaria en Madrid</strong></a>, siempre con condiciones adaptadas al activo y a la salida prevista.</p>''',
        use_grid5=False
    ))
    
    # 6. CTA
    huge_container['elements'].extend(create_cta(
        "ProActivo Finance: capital privado para actuar a tiempo",
        '''<p>En ProActivo Finance entendemos que muchas operaciones no fallan por falta de viabilidad, sino por falta de tiempo. Por eso trabajamos con análisis directo, respuesta ágil y una estructura financiera pensada para actuar cuando la banca no llega.</p><p>Si tienes un inmueble y necesitas liquidez temporal, podemos estudiar tu caso sin compromiso y plantear una propuesta clara, con <strong>firma ante notario</strong> y acompañamiento profesional durante todo el proceso.</p>'''
    ))
    
    new_id_for_element(huge_container)
    elements.append(huge_container)
    
    final_template = copy.deepcopy(template)
    final_template['content'][0]['elements'] = elements
    with open("landing-hipoteca-puente.json", "w", encoding="utf-8") as f:
        json.dump(final_template, f)

# === LANDING 2: Préstamos Marbella ===
def build_landing_2():
    elements = []
    # 1. Hero
    elements.extend(create_hero(
        "Préstamos para Inversión Inmobiliaria en Marbella",
        '''<p>En ProActivo Finance trabajamos con inversores, promotores y empresas que buscan <a href="https://proactivofinance.com/financiacion-con-capital-privado-en-marbella/"><strong>financiación ágil</strong></a> para ejecutar operaciones inmobiliarias en <strong>Marbella y la Costa del Sol</strong> sin depender de los tiempos de la banca tradicional.</p><p>Marbella es uno de los mercados inmobiliarios más competitivos de España. Las oportunidades de <strong>compra, reforma, promoción o reposicionamiento de activos</strong> suelen requerir decisiones rápidas, capital disponible y una estructura financiera flexible.</p><p>Por eso ofrecemos <strong>préstamos para inversión inmobiliaria en Marbella</strong>: soluciones de <strong>capital privado con garantía hipotecaria</strong>, pensadas para quienes necesitan actuar con velocidad y seguridad.</p>'''
    ))
    
    # 2. Marbella
    elements.extend(create_simple_section(
        "Marbella: un mercado de alto valor y alta competencia",
        '''<p>El mercado inmobiliario de Marbella combina demanda internacional, escasez de activos prime y una fuerte presión en zonas como Nueva Andalucía, Puerto Banús, Sierra Blanca, Golden Mile, San Pedro de Alcántara o Benahavís.</p><p>En este contexto, esperar semanas o meses a una aprobación bancaria puede significar perder una operación rentable. Muchos inversores necesitan cerrar arras, comprar un inmueble con descuento, iniciar una reforma o desbloquear una carga antes de que la oportunidad desaparezca.</p><p>La financiación privada permite responder a ese ritmo. Analizamos la operación desde la lógica del activo y del proyecto, no desde un modelo bancario rígido que muchas veces no entiende la urgencia del mercado.</p>'''
    ))
    
    huge_container = copy.deepcopy(block_huge)
    huge_container['elements'] = []
    
    # 3. Qué operaciones (Bullets)
    huge_container['elements'].extend(create_bullets_section(
        "¿Qué operaciones financiamos en Marbella?",
        '''<p>Nuestros préstamos para inversión inmobiliaria en Marbella están orientados a operaciones con una garantía real y un objetivo claro. No buscamos encajar al cliente en un producto estándar, sino diseñar una estructura que permita ejecutar la inversión con sentido financiero.</p><p>Podemos estudiar operaciones como:</p>''',
        [
            "Compra de viviendas, villas o apartamentos con potencial de revalorización.",
            "<a href=\"https://proactivofinance.com/prestamos-privados-para-reformas-en-marbella\"><strong>Reformas integrales</strong></a> para venta posterior o alquiler de alto rendimiento.",
            "Adquisición de <a href=\"https://proactivofinance.com/prestamos-para-cancelar-embargos-en-marbella/\"><strong>activos con cargas, embargos</strong></a> o necesidad de regularización previa.",
            "Operaciones de compra-reforma-venta en zonas prime de Marbella.",
            "<a href=\"https://proactivofinance.com/prestamo-puente-con-capital-privado-en-marbella\"><strong>Financiación puente</strong></a> mientras se vende otro activo inmobiliario.",
            "Promociones pequeñas o proyectos de rehabilitación con salida comercial clara."
        ],
        '''<p>En todos los casos, la clave es contar con un inmueble que pueda respaldar la operación y una estrategia de devolución coherente.</p>'''
    ))
    
    # 4. Por qué elegir capital privado (Grid 5 items, we can use Grid4 style but add 5 items)
    huge_container['elements'].extend(create_grid(
        "¿Por qué elegir capital privado para invertir en Marbella?",
        '''<p>La banca tradicional suele trabajar con plazos, documentación y criterios que no siempre encajan con una inversión inmobiliaria urgente. En Marbella, donde una oportunidad puede cerrarse en cuestión de días, esa lentitud puede ser la diferencia entre ganar o quedarse fuera.</p><p>Con capital privado, el análisis se centra en el valor del activo, la viabilidad de la operación y la capacidad de estructurar una salida razonable. Esto permite financiar escenarios que el banco rechazaría por timing, incidencias previas o falta de encaje documental.</p><p>Las ventajas principales son:</p>''',
        [
            "Respuesta rápida tras recibir la información básica del inmueble y la operación.",
            "Financiación con garantía hipotecaria, sin depender exclusivamente del scoring bancario.",
            "Posibilidad de trabajar con operaciones complejas, activos con cargas o liquidez urgente.",
            "Plazos flexibles y carencias adaptadas al ciclo de compra, reforma o venta.",
            "Firma ante notario y proceso transparente desde el primer momento."
        ],
        '''<p>Este tipo de financiación es especialmente útil para inversores que saben detectar oportunidades, pero necesitan capital en el momento exacto para ejecutarlas.</p>''',
        use_grid5=False
    ))
    
    # 5. Condiciones Generales (Bullets)
    huge_container['elements'].extend(create_bullets_section(
        "Condiciones generales de nuestros préstamos",
        '''<p>Cada operación se estudia de forma individual, porque no es lo mismo financiar una villa en la Milla de Oro que una reforma en San Pedro o una compra de oportunidad en Nueva Andalucía. Aun así, existen bases comunes que nos permiten trabajar con rapidez.</p><p>Normalmente valoramos:</p>''',
        [
            "El importe solicitado y su relación con el valor del inmueble aportado como garantía.",
            "La ubicación, liquidez y estado del activo inmobiliario.",
            "El objetivo de la financiación: compra, reforma, cancelación de cargas, puente o inversión.",
            "El plazo previsto de devolución y la salida de la operación.",
            "La documentación registral y la viabilidad jurídica del caso."
        ],
        '''<p>Nuestro objetivo es ofrecer una propuesta clara, realista y útil para que el cliente pueda decidir con información suficiente y sin perder tiempo.</p>'''
    ))
    
    # 6. Que perfiles (Bullets)
    huge_container['elements'].extend(create_bullets_section(
        "¿Qué perfiles pueden solicitar esta financiación?",
        '''<p>Trabajamos con perfiles profesionales y patrimoniales que necesitan una respuesta flexible. Marbella atrae a inversores nacionales e internacionales, promotores, empresarios y propietarios con activos de alto valor que buscan liquidez para aprovechar una oportunidad.</p><p>Este servicio puede encajar con:</p>''',
        [
            "Inversores que compran activos para reformar y vender.",
            "Promotores que necesitan capital para iniciar o cerrar una fase del proyecto.",
            "Empresas con patrimonio inmobiliario que desean financiar una operación estratégica.",
            "Propietarios que quieren utilizar un inmueble como garantía sin venderlo.",
            "Compradores que necesitan financiación puente para cerrar una operación antes de vender otro activo."
        ],
        '''<p>Si hay una garantía sólida y una operación coherente, podemos estudiar alternativas incluso cuando la banca no ofrece una respuesta viable.</p>'''
    ))
    
    # 7. CTA
    huge_container['elements'].extend(create_cta(
        "ProActivo Finance: financiación privada sin frenos",
        '''<p>En ProActivo Finance entendemos que en Marbella el tiempo y la capacidad de decisión son determinantes. Una buena oportunidad no siempre espera a que un banco complete su circuito interno, y muchas operaciones requieren soluciones financieras más ágiles.</p><p>Nuestro equipo analiza cada caso con visión práctica, confidencialidad y enfoque de negocio. Te ayudamos a estructurar la financiación necesaria para avanzar, proteger tu oportunidad y cerrar la operación con seguridad.</p><p>Si tienes una inversión inmobiliaria en Marbella y necesitas capital privado con garantía hipotecaria, cuéntanos tu caso. Podemos darte una primera valoración rápida y una propuesta adaptada a tu objetivo.</p><p><strong>Solicita información y descubre cómo financiar tu próxima operación inmobiliaria en Marbella.</strong></p>'''
    ))
    
    # 8. Tabla
    huge_container['elements'].extend(create_table_section(
        "Resumen rápido para elegir financiación",
        ["Escenario inmobiliario", "Solución recomendada"],
        [
            ["Compra de oportunidad", "Liquidez con garantía hipotecaria y análisis rápido del activo."],
            ["Compra-reforma-venta", "Financiación flexible para compra, mejora y posterior venta."],
            ["Necesidad de rapidez", "Capital puente o a medida para ejecutar sin frenar la operación."]
        ],
        ""
    ))
    
    new_id_for_element(huge_container)
    elements.append(huge_container)
    
    final_template = copy.deepcopy(template)
    final_template['content'][0]['elements'] = elements
    with open("landing-inversion-marbella.json", "w", encoding="utf-8") as f:
        json.dump(final_template, f)

if __name__ == "__main__":
    build_landing_1()
    build_landing_2()
    print("Done")
