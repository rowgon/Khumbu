=== Child Code Snippets Extension ===
Plugin Name: MainWP Code Snippets Extension
Plugin URI: https://mainwp.com
Description: The MainWP Code Snippets Extension is a powerful PHP platform that enables you to execute php code and scripts on your child sites and view the output on your Dashboard. Requires the MainWP Dashboard plugin.
Version: 5.1.1
Author: MainWP
Author URI: https://mainwp.com

== Changelog ==

= 5.1.1 - 6-16-2026 =

* Fixed: Corrected the Code Snippets table installer to always define the auto-increment ID as a primary key.

= 5.1 - 6-8-2026 =

* Security: Improved code snippet database handling with prepared queries, sanitized values, and stricter user scoping.
* Security: Strengthened AJAX input and output handling to improve protection against malformed or unsafe requests.
* Fixed: Corrected formatting of default snippet code.
* Fixed: Ensured default snippet descriptions and data are properly migrated during upgrades.
* Added: Code folding UI and fold keymap support.
* Added: Autocompletion and automatic bracket closing in the code editor.
* Added: Natural Tab indentation support in the code editor.
* Added: PHP pre-save syntax validation to help catch common syntax errors before saving snippets.
* Added: Added a new clients column to the Code Snippets database table to support client-based snippet organization.
* Updated: Reworked the snippet editor layout with a cleaner, more modern interface.
* Updated: Moved snippet settings above the code editor for easier access while editing snippets.
* Updated: Compacted snippet action buttons to reduce visual clutter in the editor area.
* Updated: Replaced the snippet type radio buttons with a pill-style selector for a clearer editing experience.
* Updated: Improved the saved snippets table by combining snippet titles and descriptions into a single, more readable column.
* Updated: Replaced plain text snippet type indicators with colored labels for better visibility in the saved snippets table.
* Updated: Refined multiple text strings for clearer and more consistent wording.
* Updated: Improved snippet options handling, selection normalization, and snippet type validation for better reliability.
* Updated: Improved snippet run UI rendering for a cleaner and more consistent user experience.
* Updated: Replaced the legacy CodeMirror 5 editor with a new CodeMirror 6 bundle.
* Updated: Improved internal utility handling with a safer rand_string method and a more user-friendly time_elapsed_string helper.
* Dev: Refactored core database methods, including install, update_codesnippet, get_codesnippet_by, and remove_codesnippet, for safer and more consistent data handling.
* Dev: Refactored the extension structure with clearer return semantics, improved docblocks, singleton patterns, and updated build files for better maintainability.

= 5.0.4 - 11-28-2024 =

* Fixed: An issue with saving the snippets table state.
* Added: Localization support.
* Updated: Removed excessive navigation menu on the extension page. 

= 5.0.3 - 6-25-2024 =

* Fixed: An issue with sorting snippets table by the last modified date

= 5.0.2 - 6-18-2024 =

* Fixed: An issue with saving new snippets duplicates

= 5.0.1 - 5-29-2024 =

* Updated: Compatibility with the latest DataTables library version
* Updated: CodeMirror library to the latest version

= 5.0 - 2-27-2024 =

* Updated: MainWP 5.0 compatibility

= 4.0.3 - 12-7-2022 =

* Preventative: Multiple security improvements

= 4.0.2 - 9-12-2022 =

Added: Accordion functionality to the sidebar sections
Added: Responsive mode to the snippets table
Updated: CodeMirror library
Updated: Sidebar layout on the execute snippet page

= 4.0.1 - 9-10-2020 =

* Updated: MainWP Dashboard 4.1 compatibility

= 4.0 - 8-27-2019 =

* Updated: extension UI/UX redesign
* Updated: support for the MainWP 4.0

= 1.2 - 7-27-2018 =

* Fixed: incorrect form field label

= 1.1 - 10-6-2017 =

* Fixed: multiple translation issues
* Fixed: multiple codding standard issues
* Fixed: multiple grammar issues
* Added: extension help tab
* Updated: extension general style
* Updated: notification messages
* Updated: CodeMirror library
* Updated: responsive CSS styles
* Removed: unused code
* Removed: unused files

= 1.0 - 2-17-2016 =

* Added: support for WP-CLI
* Added: support for the new API management
* Updated: refactored code to meet WordPress coding standards
* Updated: "Check for updates now" link is not visible if extension is not activated

= 0.0.6 - 4-17-2015 =

* Updated: Quick start guide layout

= 0.0.4 =

* Added: support for the API Manager

= 0.0.4 =

* Added: additional Plugin info
* Added: support for the upcoming extension
* Added: headings for each snippet processing states

= 0.0.3 =

* Fixed: multiple PHP warnings

= 0.0.2 =

* Added: support for the "echo" command

= 0.0.1 =

* First version
