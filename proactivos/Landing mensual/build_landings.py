import json
import uuid
import copy

def generate_id():
    return uuid.uuid4().hex[:7]

def new_id_for_element(element):
    """Recursively assign new IDs to the element and its children to avoid duplicates."""
    element['id'] = generate_id()
    
    # Also generate new _id for repeater items in settings
    if 'settings' in element:
        for key, value in element['settings'].items():
            if isinstance(value, list):
                for item in value:
                    if isinstance(item, dict) and '_id' in item:
                        item['_id'] = generate_id()
                        
    if 'elements' in element:
        for child in element['elements']:
            new_id_for_element(child)

with open("/var/www/html/Khumbu/proactivos/Landing mensual/elementor-15256-2026-08-03.json", "r", encoding="utf-8") as f:
    template = json.load(f)

# The root structure is {"content": [ { "id": "...", "elements": [ container1, container2, ... ] } ] }
main_container = template['content'][0]
containers = main_container['elements']

def clone_container(index):
    el = copy.deepcopy(containers[index])
    new_id_for_element(el)
    return el

def set_text(container, text, is_title=False, is_html=False):
    # Find the widget inside the container
    if not container.get('elements'):
        widget = container
    else:
        widget = container['elements'][0]
        
    if is_title:
        widget['settings']['title'] = text
    elif is_html:
        widget['settings']['html'] = text
    else:
        widget['settings']['editor'] = text

def set_grid_item_text(item, text):
    # Item is a container with icon and text-editor
    text_editor = item['elements'][1]
    text_editor['settings']['editor'] = text
    
def set_grid_items(container, texts):
    # Modify the grid to have exactly len(texts) items
    grid_container = container['elements'][0]
    original_item = grid_container['elements'][0]
    new_items = []
    
    for text in texts:
        new_item = copy.deepcopy(original_item)
        new_id_for_element(new_item)
        set_grid_item_text(new_item, f"<p><strong>{text}</strong></p>")
        new_items.append(new_item)
        
    grid_container['elements'] = new_items
    grid_container['settings']['grid_columns_grid'] = {"unit":"fr", "size": min(len(texts), 5), "sizes":[]}

def build_html_table(headers, rows):
    html = f"""<div class="mi-tabla-ajustada">
    <table>
        <thead>
            <tr>
"""
    for h in headers:
        html += f"                <th>{h}</th>\n"
    html += """            </tr>
        </thead>
        <tbody>
"""
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
    return html

# === LANDING 1: Hipoteca Puente ===
def build_landing_1():
    new_elements = []
    
    hero_title = clone_container(0)
    set_text(hero_title, "Hipoteca Puente con Capital Privado", is_title=True)
    new_elements.append(hero_title)
    
    hero_text = clone_container(1)
    set_text(hero_text, '''<p>Una <strong>hipoteca puente con capital privado</strong> permite obtener liquidez de forma rápida utilizando un inmueble como garantía mientras se completa una venta, se cierra una operación o llega un ingreso previsto. En ProActivo Finance estudiamos operaciones en toda España con un enfoque práctico, flexible y orientado a resolver necesidades reales de financiación.</p><p>Cuando la banca tarda demasiado o no entiende la urgencia del caso, el <strong>capital privado</strong> puede actuar como una alternativa eficaz para empresas, inversores, promotores y propietarios con patrimonio inmobiliario. Analizamos el valor del activo, la salida de la operación y la viabilidad global, no solo el historial bancario.</p><p>Si necesitas una respuesta ágil, puedes solicitar una primera valoración desde <a href="https://proactivofinance.com/"><strong>ProActivo Finance</strong></a> y saber si tu inmueble permite estructurar una operación con <strong>firma ante notario</strong>.</p>''')
    new_elements.append(hero_text)
    
    sec1_title = clone_container(2)
    set_text(sec1_title, "¿Qué es una hipoteca puente con capital privado?", is_title=True)
    new_elements.append(sec1_title)
    
    sec1_text = clone_container(3)
    set_text(sec1_text, '''<p>La hipoteca puente con capital privado es una financiación temporal respaldada por una garantía inmobiliaria. Su función es cubrir una necesidad de liquidez durante un periodo concreto, normalmente hasta que se produce un hecho previsto: la venta de un inmueble, la entrada de fondos, la cancelación de una deuda o la formalización de una operación empresarial.</p><p>A diferencia de una hipoteca puente bancaria, el análisis no se limita a criterios automatizados. En ProActivo Finance revisamos la garantía, las cargas existentes, el importe solicitado, el plazo necesario y la estrategia de salida. Esto permite valorar operaciones que pueden quedar bloqueadas en la banca por ASNEF, incidencias puntuales, falta de ingresos demostrables o urgencias registrales.</p><p>Este planteamiento es especialmente útil cuando existe una oportunidad clara, pero el cliente necesita actuar antes de que el mercado, el vendedor o una situación legal avance. En operaciones similares, también trabajamos soluciones como el <a href="https://proactivofinance.com/prestamo-puente-con-capital-privado-en-madrid"><strong>préstamo puente con capital privado en Madrid</strong></a>, adaptando el análisis a la ubicación y al tipo de activo.</p>''')
    new_elements.append(sec1_text)
    
    sec2_title = clone_container(9)
    set_text(sec2_title, "¿Cuándo solicitar una hipoteca puente con capital privado?", is_title=True)
    new_elements.append(sec2_title)
    
    sec2_intro = clone_container(10)
    set_text(sec2_intro, '''<p>Este tipo de financiación encaja cuando el tiempo es un factor crítico y existe un inmueble que puede respaldar la operación. No se trata de endeudarse sin planificación, sino de utilizar el patrimonio inmobiliario para ganar margen de maniobra.</p>''')
    new_elements.append(sec2_intro)
    
    sec2_bullets = clone_container(11)
    bullets_html = "<ul>"
    for item in [
        "Comprar un inmueble antes de vender otro sin perder una oportunidad de mercado.",
        "Cancelar cargas, embargos o deudas urgentes antes de que el problema avance.",
        "Aportar liquidez temporal a una empresa con patrimonio inmobiliario.",
        "Completar una reforma, promoción o inversión con salida prevista.",
        "Reordenar una operación inmobiliaria que la banca no resuelve a tiempo."
    ]:
        bullets_html += f"<li>{item}</li>"
    bullets_html += "</ul>"
    set_text(sec2_bullets, bullets_html)
    new_elements.append(sec2_bullets)
    
    sec2_closing = clone_container(10)
    set_text(sec2_closing, '''<p>En todos estos casos, la clave está en definir bien el importe, el plazo y la salida. La operación debe tener sentido económico, una garantía suficiente y una formalización transparente.</p>''')
    new_elements.append(sec2_closing)
    
    sec3_title = clone_container(6)
    set_text(sec3_title, "Ventajas frente a la banca", is_title=True)
    new_elements.append(sec3_title)
    
    sec3_intro = clone_container(3)
    set_text(sec3_intro, '''<p>La banca tradicional suele necesitar semanas para estudiar una operación, pedir documentación adicional y aplicar criterios de riesgo poco flexibles. El capital privado permite estudiar el caso desde la garantía y desde la urgencia real del cliente.</p>''')
    new_elements.append(sec3_intro)
    
    sec3_table = clone_container(7)
    html_table = build_html_table(
        ["Necesidad", "Cómo ayuda el capital privado"],
        [
            ["Rapidez", "Permite obtener una respuesta ágil cuando la operación no puede esperar."],
            ["Flexibilidad", "Valora el inmueble, la salida prevista y el contexto del cliente."],
            ["Seguridad", "La operación se formaliza ante notario con condiciones claras."]
        ]
    )
    set_text(sec3_table, html_table, is_html=True)
    new_elements.append(sec3_table)
    
    sec3_closing = clone_container(8)
    set_text(sec3_closing, '''<p>Además, podemos estudiar operaciones con incidencias siempre que el inmueble tenga valor suficiente y exista una salida razonable. Para clientes que buscan liquidez con respaldo inmobiliario, también puede ser útil revisar opciones de <a href="https://proactivofinance.com/prestamos-con-garantia-hipotecaria-en-barcelona-proactive"><strong>préstamos con garantía hipotecaria en Barcelona</strong></a> como referencia de estructura.</p>''')
    new_elements.append(sec3_closing)
    
    sec4_title = clone_container(12)
    set_text(sec4_title, "¿Qué inmuebles pueden servir como garantía?", is_title=True)
    new_elements.append(sec4_title)
    
    sec4_intro = clone_container(13)
    set_text(sec4_intro, '''<p>Estudiamos activos ubicados en España con documentación revisable y valor suficiente para sostener la operación. La ubicación, el estado registral, las cargas existentes y la liquidez del activo influyen en la propuesta final.</p>''')
    new_elements.append(sec4_intro)
    
    sec4_grid = clone_container(14)
    set_grid_items(sec4_grid, [
        "Viviendas, pisos, chalets y activos residenciales.",
        "Locales, oficinas, naves o inmuebles vinculados a actividad empresarial.",
        "Terrenos urbanos, solares o activos con potencial de desarrollo.",
        "Inmuebles con cargas que puedan regularizarse dentro de la operación."
    ])
    new_elements.append(sec4_grid)
    
    sec4_closing = clone_container(10)
    set_text(sec4_closing, '''<p>Cuando la finalidad está vinculada a una oportunidad de compra, reforma o reposicionamiento, el análisis puede conectarse con soluciones de <a href="https://proactivofinance.com/prestamos-para-inversion-inmobiliaria-en-madrid/"><strong>préstamos para inversión inmobiliaria en Madrid</strong></a>, siempre con condiciones adaptadas al activo y a la salida prevista.</p>''')
    new_elements.append(sec4_closing)
    
    sec5_title = clone_container(2)
    set_text(sec5_title, "ProActivo Finance: capital privado para actuar a tiempo", is_title=True)
    new_elements.append(sec5_title)
    
    sec5_text = clone_container(3)
    set_text(sec5_text, '''<p>En ProActivo Finance entendemos que muchas operaciones no fallan por falta de viabilidad, sino por falta de tiempo. Por eso trabajamos con análisis directo, respuesta ágil y una estructura financiera pensada para actuar cuando la banca no llega.</p><p>Si tienes un inmueble y necesitas liquidez temporal, podemos estudiar tu caso sin compromiso y plantear una propuesta clara, con <strong>firma ante notario</strong> y acompañamiento profesional durante todo el proceso.</p>''')
    new_elements.append(sec5_text)
    
    final_template = copy.deepcopy(template)
    final_template['content'][0]['elements'] = new_elements
    if 'page_settings' not in final_template:
         final_template['page_settings'] = {}
    
    with open("/var/www/html/Khumbu/proactivos/Landing mensual/landing-hipoteca-puente.json", "w", encoding="utf-8") as f:
        json.dump(final_template, f)

# === LANDING 2: Préstamos Marbella ===
def build_landing_2():
    new_elements = []
    
    hero_title = clone_container(0)
    set_text(hero_title, "Préstamos para Inversión Inmobiliaria en Marbella", is_title=True)
    new_elements.append(hero_title)
    
    hero_text = clone_container(1)
    set_text(hero_text, '''<p>En ProActivo Finance trabajamos con inversores, promotores y empresas que buscan <a href="https://proactivofinance.com/financiacion-con-capital-privado-en-marbella/"><strong>financiación ágil</strong></a> para ejecutar operaciones inmobiliarias en <strong>Marbella y la Costa del Sol</strong> sin depender de los tiempos de la banca tradicional.</p><p>Marbella es uno de los mercados inmobiliarios más competitivos de España. Las oportunidades de <strong>compra, reforma, promoción o reposicionamiento de activos</strong> suelen requerir decisiones rápidas, capital disponible y una estructura financiera flexible.</p><p>Por eso ofrecemos <strong>préstamos para inversión inmobiliaria en Marbella</strong>: soluciones de <strong>capital privado con garantía hipotecaria</strong>, pensadas para quienes necesitan actuar con velocidad y seguridad.</p>''')
    new_elements.append(hero_text)
    
    sec1_title = clone_container(2)
    set_text(sec1_title, "Marbella: un mercado de alto valor y alta competencia", is_title=True)
    new_elements.append(sec1_title)
    
    sec1_text = clone_container(3)
    set_text(sec1_text, '''<p>El mercado inmobiliario de Marbella combina demanda internacional, escasez de activos prime y una fuerte presión en zonas como Nueva Andalucía, Puerto Banús, Sierra Blanca, Golden Mile, San Pedro de Alcántara o Benahavís.</p><p>En este contexto, esperar semanas o meses a una aprobación bancaria puede significar perder una operación rentable. Muchos inversores necesitan cerrar arras, comprar un inmueble con descuento, iniciar una reforma o desbloquear una carga antes de que la oportunidad desaparezca.</p><p>La financiación privada permite responder a ese ritmo. Analizamos la operación desde la lógica del activo y del proyecto, no desde un modelo bancario rígido que muchas veces no entiende la urgencia del mercado.</p>''')
    new_elements.append(sec1_text)
    
    sec2_title = clone_container(9)
    set_text(sec2_title, "¿Qué operaciones financiamos en Marbella?", is_title=True)
    new_elements.append(sec2_title)
    
    sec2_intro = clone_container(10)
    set_text(sec2_intro, '''<p>Nuestros préstamos para inversión inmobiliaria en Marbella están orientados a operaciones con una garantía real y un objetivo claro. No buscamos encajar al cliente en un producto estándar, sino diseñar una estructura que permita ejecutar la inversión con sentido financiero.</p><p>Podemos estudiar operaciones como:</p>''')
    new_elements.append(sec2_intro)
    
    sec2_bullets = clone_container(11)
    bullets_html = "<ul>"
    for item in [
        "Compra de viviendas, villas o apartamentos con potencial de revalorización.",
        "<a href=\"https://proactivofinance.com/prestamos-privados-para-reformas-en-marbella\"><strong>Reformas integrales</strong></a> para venta posterior o alquiler de alto rendimiento.",
        "Adquisición de <a href=\"https://proactivofinance.com/prestamos-para-cancelar-embargos-en-marbella/\"><strong>activos con cargas, embargos</strong></a> o necesidad de regularización previa.",
        "Operaciones de compra-reforma-venta en zonas prime de Marbella.",
        "<a href=\"https://proactivofinance.com/prestamo-puente-con-capital-privado-en-marbella\"><strong>Financiación puente</strong></a> mientras se vende otro activo inmobiliario.",
        "Promociones pequeñas o proyectos de rehabilitación con salida comercial clara."
    ]:
        bullets_html += f"<li>{item}</li>"
    bullets_html += "</ul>"
    set_text(sec2_bullets, bullets_html)
    new_elements.append(sec2_bullets)
    
    sec2_closing = clone_container(10)
    set_text(sec2_closing, '''<p>En todos los casos, la clave es contar con un inmueble que pueda respaldar la operación y una estrategia de devolución coherente.</p>''')
    new_elements.append(sec2_closing)
    
    sec3_title = clone_container(9)
    set_text(sec3_title, "¿Por qué elegir capital privado para invertir en Marbella?", is_title=True)
    new_elements.append(sec3_title)
    
    sec3_intro = clone_container(10)
    set_text(sec3_intro, '''<p>La banca tradicional suele trabajar con plazos, documentación y criterios que no siempre encajan con una inversión inmobiliaria urgente. En Marbella, donde una oportunidad puede cerrarse en cuestión de días, esa lentitud puede ser la diferencia entre ganar o quedarse fuera.</p><p>Con capital privado, el análisis se centra en el valor del activo, la viabilidad de la operación y la capacidad de estructurar una salida razonable. Esto permite financiar escenarios que el banco rechazaría por timing, incidencias previas o falta de encaje documental.</p><p>Las ventajas principales son:</p>''')
    new_elements.append(sec3_intro)
    
    sec3_grid = clone_container(4)
    set_grid_items(sec3_grid, [
        "Respuesta rápida tras recibir la información básica del inmueble y la operación.",
        "Financiación con garantía hipotecaria, sin depender exclusivamente del scoring bancario.",
        "Posibilidad de trabajar con operaciones complejas, activos con cargas o liquidez urgente.",
        "Plazos flexibles y carencias adaptadas al ciclo de compra, reforma o venta.",
        "Firma ante notario y proceso transparente desde el primer momento."
    ])
    new_elements.append(sec3_grid)
    
    sec3_closing = clone_container(10)
    set_text(sec3_closing, '''<p>Este tipo de financiación es especialmente útil para inversores que saben detectar oportunidades, pero necesitan capital en el momento exacto para ejecutarlas.</p>''')
    new_elements.append(sec3_closing)
    
    sec4_title = clone_container(9)
    set_text(sec4_title, "Condiciones generales de nuestros préstamos", is_title=True)
    new_elements.append(sec4_title)
    
    sec4_intro = clone_container(10)
    set_text(sec4_intro, '''<p>Cada operación se estudia de forma individual, porque no es lo mismo financiar una villa en la Milla de Oro que una reforma en San Pedro o una compra de oportunidad en Nueva Andalucía. Aun así, existen bases comunes que nos permiten trabajar con rapidez.</p><p>Normalmente valoramos:</p>''')
    new_elements.append(sec4_intro)
    
    sec4_bullets = clone_container(11)
    bullets_html = "<ul>"
    for item in [
        "El importe solicitado y su relación con el valor del inmueble aportado como garantía.",
        "La ubicación, liquidez y estado del activo inmobiliario.",
        "El objetivo de la financiación: compra, reforma, cancelación de cargas, puente o inversión.",
        "El plazo previsto de devolución y la salida de la operación.",
        "La documentación registral y la viabilidad jurídica del caso."
    ]:
        bullets_html += f"<li>{item}</li>"
    bullets_html += "</ul>"
    set_text(sec4_bullets, bullets_html)
    new_elements.append(sec4_bullets)
    
    sec4_closing = clone_container(10)
    set_text(sec4_closing, '''<p>Nuestro objetivo es ofrecer una propuesta clara, realista y útil para que el cliente pueda decidir con información suficiente y sin perder tiempo.</p>''')
    new_elements.append(sec4_closing)
    
    sec5_title = clone_container(12)
    set_text(sec5_title, "¿Qué perfiles pueden solicitar esta financiación?", is_title=True)
    new_elements.append(sec5_title)
    
    sec5_intro = clone_container(13)
    set_text(sec5_intro, '''<p>Trabajamos con perfiles profesionales y patrimoniales que necesitan una respuesta flexible. Marbella atrae a inversores nacionales e internacionales, promotores, empresarios y propietarios con activos de alto valor que buscan liquidez para aprovechar una oportunidad.</p><p>Este servicio puede encajar con:</p>''')
    new_elements.append(sec5_intro)
    
    sec5_bullets = clone_container(11)
    bullets_html = "<ul>"
    for item in [
        "Inversores que compran activos para reformar y vender.",
        "Promotores que necesitan capital para iniciar o cerrar una fase del proyecto.",
        "Empresas con patrimonio inmobiliario que desean financiar una operación estratégica.",
        "Propietarios que quieren utilizar un inmueble como garantía sin venderlo.",
        "Compradores que necesitan financiación puente para cerrar una operación antes de vender otro activo."
    ]:
        bullets_html += f"<li>{item}</li>"
    bullets_html += "</ul>"
    set_text(sec5_bullets, bullets_html)
    new_elements.append(sec5_bullets)
    
    sec5_closing = clone_container(10)
    set_text(sec5_closing, '''<p>Si hay una garantía sólida y una operación coherente, podemos estudiar alternativas incluso cuando la banca no ofrece una respuesta viable.</p>''')
    new_elements.append(sec5_closing)
    
    sec6_title = clone_container(2)
    set_text(sec6_title, "ProActivo Finance: financiación privada sin frenos", is_title=True)
    new_elements.append(sec6_title)
    
    sec6_text = clone_container(3)
    set_text(sec6_text, '''<p>En ProActivo Finance entendemos que en Marbella el tiempo y la capacidad de decisión son determinantes. Una buena oportunidad no siempre espera a que un banco complete su circuito interno, y muchas operaciones requieren soluciones financieras más ágiles.</p><p>Nuestro equipo analiza cada caso con visión práctica, confidencialidad y enfoque de negocio. Te ayudamos a estructurar la financiación necesaria para avanzar, proteger tu oportunidad y cerrar la operación con seguridad.</p><p>Si tienes una inversión inmobiliaria en Marbella y necesitas capital privado con garantía hipotecaria, cuéntanos tu caso. Podemos darte una primera valoración rápida y una propuesta adaptada a tu objetivo.</p><p><strong>Solicita información y descubre cómo financiar tu próxima operación inmobiliaria en Marbella.</strong></p>''')
    new_elements.append(sec6_text)
    
    sec7_title = clone_container(6)
    set_text(sec7_title, "Resumen rápido para elegir financiación", is_title=True)
    new_elements.append(sec7_title)
    
    sec7_table = clone_container(7)
    html_table = build_html_table(
        ["Escenario inmobiliario", "Solución recomendada"],
        [
            ["Compra de oportunidad", "Liquidez con garantía hipotecaria y análisis rápido del activo."],
            ["Compra-reforma-venta", "Financiación flexible para compra, mejora y posterior venta."],
            ["Necesidad de rapidez", "Capital puente o a medida para ejecutar sin frenar la operación."]
        ]
    )
    set_text(sec7_table, html_table, is_html=True)
    new_elements.append(sec7_table)
    
    final_template = copy.deepcopy(template)
    final_template['content'][0]['elements'] = new_elements
    if 'page_settings' not in final_template:
         final_template['page_settings'] = {}
    
    with open("/var/www/html/Khumbu/proactivos/Landing mensual/landing-inversion-marbella.json", "w", encoding="utf-8") as f:
        json.dump(final_template, f)

if __name__ == "__main__":
    build_landing_1()
    build_landing_2()
    print("Done")
