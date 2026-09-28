import json

with open("elementor-15564-2026-09-04.json", "r") as f:
    data = json.load(f)

def print_tree(elements, indent=0):
    for i, el in enumerate(elements):
        typ = el.get("elType", "unknown")
        wid = el.get("widgetType", "")
        sett = el.get("settings", {})
        
        info = f"[{i}] {typ} {wid}"
        if typ == "widget" and wid == "heading":
            info += f" | Title: {sett.get('title', '')[:50]}"
        elif typ == "widget" and wid == "text-editor":
            info += f" | Text: {sett.get('editor', '')[:50]}"
        elif typ == "widget" and wid == "html":
            info += " | HTML"
            
        print("  " * indent + "- " + info)
        if "elements" in el:
            print_tree(el["elements"], indent + 1)

print_tree(data.get("content", [])[0].get("elements", []))
