# Moodle Plugin "sync_service"

This plugin for Moodle (type: local) adds several functions to the Moodle Web Service API.
These functions allow users and external services to remotely create, move, and manage course modules with a focus on autonomous course design.

Originally developed for [MoodleSync](https://github.com/MoodleSync/sync-app), this fork adds advanced authoring capabilities for pages and labels to support AI-driven course generation.

## Added Functions

The following functions are available via the Moodle Web Service API:

| Function | Description | Note |
| -------- | ----------- | ---- |
| `local_course_add_new_course_module_url` | Add course module URL | |
| `local_course_add_new_course_module_resource` | Add course module Resource | File needs to be uploaded with "/webservice/upload.php". |
| `local_course_add_new_course_module_directory` | Add course module Folder | Files need to be uploaded with "/webservice/upload.php". |
| `local_course_add_new_course_module_page` | **Add course module Page** | Supports HTML content (PARAM_RAW). Uses CMID-First logic for stability. |
| `local_course_add_new_course_module_label` | **Add course module Label** | Supports HTML/Text labels for course structuring. |
| `local_course_add_files_to_directory` | Add files to existing folders | Since version 3.0.0. |
| `local_course_move_module_to_specific_position` | Move a module to a dedicated position | |
| `local_course_add_new_section` | Create and position a new course section | Since version 2.0.0. |

## New in this Fork (v2024061803+)

*   **Authoring Support:** Added support for `mod_page` and `mod_label` which were previously missing from the API.
*   **HTML Support:** Text parameters now use `PARAM_RAW` to allow for CSS-styled content, H5P embeds, and complex layouts.
*   **CMID-First Strategy:** Internal refactoring ensures that complex modules (like Pages) are correctly linked to a Course Module ID (CMID) before instance creation, preventing "Invalid Module ID" errors.

## Usage & Installation

*   **Compatibility:** Tested on Moodle versions 3.11.x, 4.x, and **5.2.x**.
*   **Protocol:** Uses the "REST (returning JSON)" web service protocol.
*   **Installation:** 
    1. Unzip the archive into the directory `local/sync_service`.
    2. Log in as an admin and follow the installation process via the Notifications page.
    3. Ensure the external service "Gemini MCP" or "Course Sync Extension Service" is enabled.
*   **Requirements:** File upload and download must be allowed in the web service settings.
*   **Security:** The setting `restrictedusers` is disabled by default to allow flexible agent interaction.

## License

This plugin is licensed under the same terms as the original Moodle sync_service plugin.

