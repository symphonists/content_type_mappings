# Content Type Mappings

Allows more control over frontend page content type mappings. Each mapping is stored in the Symphony configuration file, and page type is matched against these mappings.

## Installation

1. Enable the extension
2. Add content type mappings via the preferences page
3. If a page uses a type listed in the config, that appropriate content type will be set. Should more than one match be found, the last one encountered will be used.

## Content disposition

To force download of a page (by setting the `Content-Disposition` header), give it a page type that begins with a '.'. The page will be downloaded with a filename = `$page-handle.$type`. For instance, a page with handle `form-data` and a page type of `.csv` will be downloaded as `form-data.csv`.

Depending on the Content Type you map to a page type, it may not be necessary to add this Content-Disposition header in order to cause the page to download.

## Export Mode

Prior to version 1.7 for each export mode that you wanted for a page you were required to create an identical page in Symphony with an separate export mode.
This was quite an overhead if you were trying to create a number of reports which required both html and csv output, more so if you also had to export in xml or json format for other consumption.
With version 1.7 you can handle all of this through the same page, instead of adding a page type `.csv` however add `export.csv` as your page type in addition to any other export formats required.

Through the configuration section there is now an additional parameter to fill, that is the export variable.
This is configurable so you can use a url parameter which fits your website, by default on install / update this is set to `export`. 
It is important to note what you set this value as you will need it within your templates.

### Adjusting your templates to work with export modes

Assuming that you use a master template matching `/` you can add the following code within your page template which supports export modes.

	<xsl:template match="/">
		<xsl:choose>
			<xsl:when test='/data/params/url-export = "csv"'>
				<xsl:apply-templates select='.' mode='csv'/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-imports/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

If you had previously changed the export variable, update the when clause to reflect the correct url parameter.
For each export mode create a where clause reflecting the data which you want to return for that mode.
If no export modes are matched, the default template will be applied using apply imports, if you're not using a master template, call the main template instead.
