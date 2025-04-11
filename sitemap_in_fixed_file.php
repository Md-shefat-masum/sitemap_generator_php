function updateSitemap($params)
{
    $filePath = public_path('/xmls/' . $params['xml_group']);
    $sitemapUrl = $params['url'];
    $priority = $params['priority'] ?? 0.8;
    $now = date("Y-m-d H:i:s");

    // If file doesn't exist, create a basic sitemapindex structure
    if (!file_exists($filePath)) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>');
        $xml->asXML($filePath);
    }

    // Load XML via DOMDocument for insertBefore
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->load($filePath);

    $mapParent = $dom->documentElement;

    // Create <sitemap> element
    $newUrlNode = $dom->createElement("sitemap");

    $loc = $dom->createElement("loc", $sitemapUrl);
    $lastmod = $dom->createElement("lastmod", $now);
    $changefreq = $dom->createElement("changefreq", $params['changefreq'] ?? "weekly");
    $priorityEl = $dom->createElement("priority", $priority);

    $newUrlNode->appendChild($loc);
    $newUrlNode->appendChild($lastmod);
    $newUrlNode->appendChild($changefreq);
    $newUrlNode->appendChild($priorityEl);

    // Insert before the first child (i.e., prepend)
    if ($mapParent->hasChildNodes()) {
        $mapParent->insertBefore($newUrlNode, $mapParent->firstChild);
    } else {
        $mapParent->appendChild($newUrlNode);
    }

    // Save the updated XML
    $dom->save($filePath);
}
