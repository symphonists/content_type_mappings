# Content Type Mappings

Allows more control over frontend page content type mappings. Each mapping is stored in the Symphony configuration file, and page type is matched against these mappings.

## Installation

1. Enable the extension
2. Add content type mappings via the preferences page
3. If a page uses a type listed in the config, that appropriate content type will be set. Should more than one match be found, the last one encountered will be used.

## Content disposition

To force download of a page (by setting the `Content-Disposition` header), give it a page type that begins with a '.'. The page will be downloaded with a filename = `$page-handle.$type`. For instance, a page with handle `form-data` and a page type of `.csv` will be downloaded as `form-data.csv`.

Depending on the Content Type you map to a page type, it may not be necessary to add this Content-Disposition header in order to cause the page to download.