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

with open("elementor-15564-2026-09-04.json", "r", encoding="utf-8") as f:
    template = json.load(f)

orig_elements = template['content'][0]['elements']
block_hero_title = copy.deepcopy(orig_elements[0])
block_hero_text = copy.deepcopy(orig_elements[1])
block_section = copy.deepcopy(orig_elements[2])

huge_orig = orig_elements[3]
huge_elements = huge_orig['elements']

block_bullets_title = copy.deepcopy(huge_elements[0])
block_bullets_intro = copy.deepcopy(huge_elements[1])
block_bullets_list = copy.deepcopy(huge_elements[2])
block_bullets_closing = copy.deepcopy(huge_elements[3])

block_grid = copy.deepcopy(huge_elements[4])
block_cta = copy.deepcopy(huge_elements[13])

block_table_title = copy.deepcopy(huge_elements[14])
block_table_html = copy.deepcopy(huge_elements[15])

def set_text(el, text, target='editor'):
    if target == 'title':
        if el['elType'] == 'container':
            el['elements'][0]['settings']['title'] = text
        else:
            el['settings']['title'] = text
    elif target == 'html':
        if el['elType'] == 'container':
            el['elements'][0]['settings']['html'] = text
        else:
            el['settings']['html'] = text
    else: 
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
    s = copy.deepcopy(block_section)
    set_text(s['elements'][0], title, 'title')
    set_text(s['elements'][1], text, 'editor')
    new_id_for_element(s)
    return [s]

def create_table_section(title, headers, rows, closing):
    t_title = copy.deepcopy(block_table_title)
    set_text(t_title, title, 'title')
    
    html = f"""<div class="mi-tabla-ajustada">
    <table>
        <thead>
            <tr>\\n"""
    for h in headers:
        html += f"                <th>{h}</th>\\n"
    html += """            </tr>
        </thead>
        <tbody>\\n"""
    for row in rows:
        html += "            <tr>\\n"
        for i, cell in enumerate(row):
            if i == 0:
                html += f"                <td><strong>{cell}</strong></td>\\n"
            else:
                html += f"                <td>{cell}</td>\\n"
        html += "            </tr>\\n"
        
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

    t_html = copy.deepcopy(block_table_html)
    set_text(t_html, html, 'html')
    
    new_id_for_element(t_title)
    new_id_for_element(t_html)
    
    res = [t_title, t_html]
    if closing:
        t_closing = copy.deepcopy(block_bullets_closing)
        set_text(t_closing, closing, 'editor')
        new_id_for_element(t_closing)
        res.append(t_closing)
        
    return res

def create_bullets_section(title, intro, bullets, closing):
    t_title = copy.deepcopy(block_bullets_title)
    set_text(t_title, title, 'title')
    
    t_intro = copy.deepcopy(block_bullets_intro)
    set_text(t_intro, intro, 'editor')
    
    t_bullets = copy.deepcopy(block_bullets_list)
    bullets_html = "<ul>"
    for b in bullets:
        bullets_html += f"<li>{b}</li>"
    bullets_html += "</ul>"
    set_text(t_bullets, bullets_html, 'editor')
    
    new_id_for_element(t_title)
    new_id_for_element(t_intro)
    new_id_for_element(t_bullets)
    
    res = [t_title]
    if intro: res.append(t_intro)
    res.append(t_bullets)
    if closing:
        t_closing = copy.deepcopy(block_bullets_closing)
        set_text(t_closing, closing, 'editor')
        new_id_for_element(t_closing)
        res.append(t_closing)
    return res

def create_cta(title, text):
    t = copy.deepcopy(block_cta)
    set_text(t['elements'][0], title, 'title')
    set_text(t['elements'][1], text, 'editor')
    new_id_for_element(t)
    return [t]

# === LANDING 3: Prestamistas Barcelona ===
def build_landing_3():
    elements = []
    # 1. Hero
    elements.extend(create_hero(
        "Prestamistas Privados en Barcelona",
        '''<p>En <a href="https://proactivofinance.com/"><strong>ProActivo Finance</strong></a> ayudamos a empresas, inversores, promotores y propietarios que buscan <strong>prestamistas privados en Barcelona</strong> para obtener liquidez rápida con una <strong>garantía inmobiliaria</strong> sólida.</p><p>Barcelona es un mercado exigente: operaciones de compraventa, reformas, refinanciaciones, oportunidades inmobiliarias y situaciones urgentes no siempre pueden esperar los plazos de la banca tradicional. Por eso trabajamos con <strong>capital privado</strong>, <strong>análisis flexible</strong> y formalización ante notario.</p><p>Si cuentas con un inmueble en Barcelona, el área metropolitana o zonas de alto valor como Sarrià-Sant Gervasi, Eixample, Pedralbes, Diagonal Mar o Sant Cugat, podemos estudiar una solución ajustada a tu caso sin depender únicamente de nómina, scoring bancario o historial financiero.</p>'''
    ))
    
    # 2. Qué ofrecen
    elements.extend(create_simple_section(
        "Qué ofrecen los prestamistas privados en Barcelona",
        '''<p>Un prestamista privado profesional no funciona como una entidad bancaria tradicional. La operación se analiza principalmente desde el valor del inmueble, la viabilidad jurídica, el importe solicitado y la salida prevista del préstamo.</p><p>En ProActivo Finance trabajamos como alternativa seria para quienes necesitan liquidez con rapidez, pero también con estructura. La operación se plantea con contrato, transparencia y <strong>firma ante notario</strong>, evitando soluciones improvisadas o poco claras.</p><p>Esta vía puede encajar cuando buscas <a href="https://proactivofinance.com/prestamo-capital-privado-en-barcelona-proactivo-finance"><strong>financiación privada en Barcelona</strong></a> para resolver una necesidad empresarial, ejecutar una inversión o cancelar cargas antes de que el problema avance.</p>'''
    ))
    
    huge_container = copy.deepcopy(huge_orig)
    huge_container['elements'] = []
    
    # 3. Cuándo acudir (Bullets)
    huge_container['elements'].extend(create_bullets_section(
        "Cuándo acudir a prestamistas privados en Barcelona",
        '''<p>La financiación privada tiene sentido cuando existe una <strong>garantía inmobiliaria</strong> y el tiempo es un factor importante. No se trata solo de conseguir dinero rápido, sino de estructurar una operación que permita resolver una necesidad concreta sin vender patrimonio de forma precipitada.</p><p>Estudiamos habitualmente operaciones como:</p>''',
        [
            "Empresas que necesitan liquidez para pagos urgentes, proveedores, impuestos o circulante.",
            "Propietarios con inmuebles libres de cargas o con cargas asumibles que quieren obtener capital sin vender.",
            "Inversores que necesitan cerrar una compra, reforma o oportunidad inmobiliaria en Barcelona.",
            "Promotores con proyectos en marcha que requieren financiación puente para avanzar de fase.",
            "Clientes con incidencias bancarias, ASNEF, CIRBE tensionada o rechazo de la banca tradicional."
        ],
        '''<p>Cuando la finalidad está vinculada a compra, reforma o reposicionamiento de activos, también podemos estudiar <a href="https://proactivofinance.com/prestamos-para-inversion-inmobiliaria-en-barcelona/"><strong>préstamos para inversión inmobiliaria en Barcelona</strong></a> si la operación tiene una salida económica clara.</p>'''
    ))
    
    # 4. Ventajas
    huge_container['elements'].extend(create_bullets_section(
        "Ventajas del capital privado frente a la banca en Barcelona",
        '''<p>La principal diferencia está en el enfoque. Mientras el banco suele partir de filtros rígidos, el <strong>capital privado</strong> permite valorar la operación desde el activo y la urgencia real del cliente. Esto es especialmente útil en Barcelona, donde muchas oportunidades se deciden en días.</p>''',
        [
            "Respuesta rápida tras recibir la documentación básica del inmueble.",
            "Análisis flexible incluso si existen cargas, incidencias o una situación financiera compleja.",
            "Garantía inmobiliaria como eje de la operación, no solo scoring bancario.",
            "Condiciones claras antes de avanzar hacia la formalización.",
            "Firma ante notario para dar seguridad jurídica al proceso."
        ],
        ""
    ))
    
    # 5. Qué inmuebles
    huge_container['elements'].extend(create_bullets_section(
        "Qué inmuebles aceptamos como garantía en Barcelona",
        '''<p>Podemos estudiar diferentes activos siempre que tengan valor suficiente, documentación revisable y una situación registral que permita estructurar la financiación.</p>''',
        [
            "Viviendas, pisos, áticos o casas en Barcelona y su área metropolitana.",
            "Locales comerciales, oficinas, naves o activos vinculados a actividad empresarial.",
            "Edificios para rehabilitar, solares urbanos o inmuebles con potencial de desarrollo.",
            "Inmuebles con cargas previas si el préstamo permite regularizar la situación.",
            "Activos destinados a inversión, reforma, alquiler o venta posterior."
        ],
        '''<p>Si el objetivo principal es obtener liquidez sobre un inmueble, los <a href="https://proactivofinance.com/prestamos-con-garantia-hipotecaria-en-barcelona-proactive"><strong>préstamos con garantía hipotecaria en Barcelona</strong></a> pueden ser una alternativa eficaz para transformar patrimonio en capital operativo.</p>'''
    ))
    
    # 6. Tabla Resumen
    huge_container['elements'].extend(create_table_section(
        "Resumen rápido para valorar tu operación",
        ["Situación", "Solución de financiación privada"],
        [
            ["Necesidad urgente de liquidez", "Capital privado respaldado por inmueble para actuar sin esperar semanas."],
            ["Rechazo bancario", "Análisis flexible centrado en garantía, importe y viabilidad."],
            ["Oportunidad inmobiliaria", "Financiación ágil para compra, reforma o inversión con salida prevista."]
        ],
        ""
    ))
    
    # 7. CTA
    huge_container['elements'].extend(create_cta(
        "ProActivo Finance: prestamistas privados para actuar con seguridad",
        '''<p>En ProActivo Finance combinamos rapidez, experiencia y análisis profesional para ofrecer soluciones de financiación privada adaptadas a Barcelona. Nuestro objetivo es que el cliente entienda la operación, sus costes, sus plazos y su salida antes de tomar una decisión.</p><p>Si necesitas liquidez y cuentas con un inmueble como garantía, podemos estudiar tu caso desde ProActivo Finance y darte una primera orientación clara.</p><p>Solicita una valoración y recibe una propuesta ajustada para avanzar con seguridad jurídica, confidencialidad y acompañamiento hasta la <strong>firma ante notario</strong>.</p>'''
    ))
    
    new_id_for_element(huge_container)
    elements.append(huge_container)
    
    final_template = copy.deepcopy(template)
    final_template['content'][0]['elements'] = elements
    if 'page_settings' not in final_template:
         final_template['page_settings'] = {}
    
    with open("landing-prestamistas-barcelona.json", "w", encoding="utf-8") as f:
        json.dump(final_template, f)

# === LANDING 4: Herencias Baleares ===
def build_landing_4():
    elements = []
    # 1. Hero
    elements.extend(create_hero(
        "Préstamos para Aceptación de Herencias en Islas Baleares",
        '''<p>En ProActivo Finance ayudamos a herederos, familias y propietarios en las Islas Baleares que necesitan <strong>liquidez inmediata</strong> para aceptar una herencia sin vender activos por debajo de su valor.</p><p>Recibir una herencia en Mallorca, Ibiza, Menorca o Formentera puede convertirse en una oportunidad patrimonial, pero también en un reto financiero cuando existen impuestos, <a href="https://proactivofinance.com/reunificacion-de-deudas-en-islas-baleares/"><strong>deudas pendientes</strong></a>, cargas registrales o gastos notariales que deben resolverse antes de poder disponer de los bienes.</p><p>Nuestro servicio de <strong>préstamos para aceptación de herencias en Islas Baleares</strong> permite obtener <a href="https://proactivofinance.com/financiacion-con-capital-privado-en-islas-baleares/"><strong>financiación privada</strong></a> con <strong>garantía inmobiliaria</strong> para afrontar estos pagos con rapidez, seguridad jurídica y una estructura adaptada a cada caso.</p>'''
    ))
    
    # 2. Qué es
    elements.extend(create_simple_section(
        "¿Qué es un préstamo para aceptación de herencias?",
        '''<p>Un préstamo para aceptación de herencias es una solución de financiación pensada para personas que han recibido bienes en herencia, pero necesitan capital para completar el proceso de adjudicación, liquidar impuestos o desbloquear una situación patrimonial compleja.</p><p>En lugar de esperar una respuesta bancaria lenta o vender una propiedad con urgencia, puedes utilizar un inmueble como garantía para obtener liquidez en pocos días. Esta alternativa resulta especialmente útil cuando la herencia incluye viviendas, locales, terrenos o activos inmobiliarios situados en zonas de alto valor de las Islas Baleares.</p><p>En ProActivo Finance analizamos la operación de forma ágil y realista. Valoramos el activo, revisamos la situación registral y diseñamos una propuesta de <a href="https://proactivofinance.com/"><strong>capital privado</strong></a> orientada a resolver el problema sin añadir más presión al proceso familiar.</p>'''
    ))
    
    huge_container = copy.deepcopy(huge_orig)
    huge_container['elements'] = []
    
    # 3. Situaciones
    huge_container['elements'].extend(create_bullets_section(
        "Situaciones habituales en herencias en Mallorca, Ibiza o Menorca",
        '''<p>Cada herencia tiene sus particularidades. Algunas son sencillas, pero otras requieren actuar rápido para evitar bloqueos, recargos o conflictos entre herederos. En Baleares, además, el valor de los inmuebles hace que una mala decisión financiera pueda tener un impacto patrimonial importante.</p><p>Trabajamos habitualmente con casos como:</p>''',
        [
            "Herederos que necesitan pagar el <strong>Impuesto de Sucesiones</strong> antes de adjudicarse los bienes.",
            "Familias con propiedades heredadas que tienen cargas, hipotecas o deudas pendientes.",
            "Herencias con varios beneficiarios donde uno de ellos necesita liquidez para compensar al resto.",
            "Propietarios que quieren conservar un inmueble familiar y evitar una venta precipitada.",
            "<a href=\"https://proactivofinance.com/prestamos-para-cancelar-embargos-y-subastas-en-islas-baleares\"><strong>Situaciones con embargos, subastas</strong></a> o deudas asociadas a los bienes heredados."
        ],
        '''<p>En todos estos escenarios, el objetivo es el mismo: desbloquear la herencia con una solución rápida, clara y respaldada por garantía real.</p>'''
    ))
    
    # 4. Por qué usar
    huge_container['elements'].extend(create_bullets_section(
        "¿Por qué usar capital privado para aceptar una herencia?",
        '''<p>La banca tradicional no siempre responde bien ante procesos hereditarios. Suele exigir documentación extensa, ingresos recurrentes, análisis de riesgo y plazos que no encajan con la urgencia de pagar impuestos, cancelar deudas o firmar ante notario.</p><p>La financiación privada, en cambio, permite valorar la operación desde otro enfoque. Si existe un inmueble con valor suficiente y la operación tiene sentido, podemos estructurar un préstamo adaptado al momento real del cliente.</p><p>Entre las principales ventajas destacan:</p>''',
        [
            "Estudio rápido de la operación, con respuesta orientativa en 24-48 horas.",
            "Liquidez para pagar impuestos, notaría, registro, deudas o compensaciones entre herederos.",
            "Financiación basada en el valor del inmueble, no solo en el perfil bancario del solicitante.",
            "Posibilidad de trabajar con casos complejos, incluso con cargas o incidencias previas.",
            "Firma ante notario y condiciones claras desde el inicio."
        ],
        '''<p>Esta vía no sustituye al asesoramiento legal o fiscal, pero sí puede aportar la liquidez necesaria para que ese asesoramiento pueda ejecutarse sin que la herencia quede paralizada.</p>'''
    ))
    
    # 5. Qué inmuebles
    huge_container['elements'].extend(create_bullets_section(
        "¿Qué inmuebles pueden servir como garantía en Baleares?",
        '''<p>Las Islas Baleares cuentan con un mercado inmobiliario muy activo, especialmente en zonas como Palma, Calvià, Andratx, Ibiza, Santa Eulalia, Mahón o Ciutadella. Esa fortaleza patrimonial permite plantear operaciones de financiación privada cuando el activo heredado o aportado tiene valor suficiente.</p><p>Podemos estudiar garantías como:</p>''',
        [
            "Viviendas urbanas, apartamentos o casas familiares.",
            "Locales comerciales, oficinas o inmuebles vinculados a actividad empresarial.",
            "Terrenos urbanos o parcelas con potencial de desarrollo.",
            "Fincas rústicas con valor acreditable.",
            "Inmuebles con cargas, siempre que la operación permita regularizarlas."
        ],
        '''<p>Lo importante no es solo el tipo de inmueble, sino la relación entre el valor del activo, el importe solicitado y la viabilidad del plan de devolución.</p>'''
    ))
    
    # 6. Como funciona
    # Instead of create_simple_section (which creates a container outside), we need text-editors inside the huge container.
    # We can reuse create_bullets_section without bullets, or just append titles and text editors directly.
    t_title = copy.deepcopy(block_bullets_title)
    set_text(t_title, "¿Cómo funciona el proceso con ProActivo Finance?", 'title')
    t_text = copy.deepcopy(block_bullets_intro)
    set_text(t_text, '''<p>Nuestro proceso está diseñado para actuar con rapidez, pero sin improvisar. Sabemos que una herencia puede tener implicaciones emocionales, familiares y jurídicas, por eso trabajamos con discreción y claridad desde el primer contacto.</p><p>Primero revisamos la situación: tipo de herencia, bienes incluidos, importe necesario y urgencia del caso. Después analizamos la garantía inmobiliaria disponible y planteamos una propuesta de financiación privada ajustada al objetivo real.</p><p>Si la operación es viable, avanzamos hacia la firma ante notario y la disposición de fondos. Con esa liquidez, el cliente puede pagar impuestos, cancelar cargas, cerrar trámites o conservar el activo heredado sin venderlo deprisa.</p>''', 'editor')
    new_id_for_element(t_title)
    new_id_for_element(t_text)
    huge_container['elements'].extend([t_title, t_text])
    
    # 7. Una solución
    t_title2 = copy.deepcopy(block_bullets_title)
    set_text(t_title2, "Una solución para conservar patrimonio y ganar tiempo", 'title')
    t_text2 = copy.deepcopy(block_bullets_intro)
    set_text(t_text2, '''<p>Muchas decisiones equivocadas en una herencia se toman por falta de liquidez. Vender rápido, aceptar una oferta baja o dejar que una deuda avance puede salir mucho más caro que estructurar una financiación temporal bien planteada.</p><p>En ProActivo Finance ofrecemos una alternativa para quienes necesitan margen de maniobra. Nuestro objetivo es que puedas aceptar la herencia, proteger el patrimonio familiar y decidir con calma qué hacer después con los bienes recibidos.</p><p>Si tienes una <strong>herencia pendiente en Mallorca, Ibiza, Menorca o Formentera</strong> y necesitas capital para desbloquearla, podemos estudiar tu caso sin compromiso.</p><p>Solicita una valoración y recibe una propuesta adaptada a tu situación.</p>''', 'editor')
    new_id_for_element(t_title2)
    new_id_for_element(t_text2)
    huge_container['elements'].extend([t_title2, t_text2])
    
    # 8. Tabla Resumen
    huge_container['elements'].extend(create_table_section(
        "Resumen útil para valorar la operación",
        ["Situación habitual", "Cómo ayuda la financiación privada"],
        [
            ["Pago de impuestos y notaría", "Obtener capital para formalizar la adjudicación sin esperar al banco."],
            ["Herencia con cargas", "Cancelar deudas, cargas o importes pendientes con liquidez rápida."],
            ["Conservar el patrimonio familiar", "Evitar una venta precipitada y ganar margen de decisión."]
        ],
        ""
    ))
    
    new_id_for_element(huge_container)
    elements.append(huge_container)
    
    final_template = copy.deepcopy(template)
    final_template['content'][0]['elements'] = elements
    if 'page_settings' not in final_template:
         final_template['page_settings'] = {}
    
    with open("landing-herencias-baleares.json", "w", encoding="utf-8") as f:
        json.dump(final_template, f)

if __name__ == "__main__":
    build_landing_3()
    build_landing_4()
    print("Done")
