/**
 *``` php
 updateSitemap([
    "url" => $url,
    "xml_group" => 'sitemap_topics.xml',
    "priority" => 0.8,
    "xml_prefix" => "topics",
 ])
 ```
 */

function updateSitemap($params)
{
    extract($params);

    $currentDate = now();
    $year = $currentDate->year;
    $month = $currentDate->month;
    $xmlFolder = public_path("xmls/$xml_prefix-xml-$year-$month.xml");
    $publicPath = $xmlFolder;

    // Step 2: Check if the file path exists in public_path
    if (!File::exists($publicPath)) {
        // Step 3: Create the folder if it does not exist
        File::ensureDirectoryExists(dirname($xmlFolder));

        // Create the XML structure and add to the file using SimpleXML
        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"></urlset>");
        $urlElement = $xml->addChild('url');
        $urlElement->addChild('loc', $url);
        $urlElement->addChild('lastmod', now()->toDateTimeString());
        $urlElement->addChild('priority', $priority);
        $urlElement->addChild('changefreq', 'weekly');
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $dom->save($xmlFolder);
    } else {
        // Step 4: Prepend XML code into the file
        $xml = simplexml_load_file($publicPath);

        // Check if the URL already exists
        $exists = false;
        foreach ($xml->url as $existingUrl) {
            if ((string)$existingUrl->loc === $url) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            return; // URL already exists, exit the function
        }

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        // $dom->save($publicPath);

        $mapParent = $dom->getElementsByTagName('urlset')->item(0);

        if ($mapParent) {
            // Create the new URL node (you might need to adjust this to match your $newUrl structure)
            $newUrlNode = $dom->createElement('url');

            // Example: Add child elements to the new 'url' node
            $loc = $dom->createElement('loc', $url);
            $lastmod = $dom->createElement('lastmod', now()->toDateTimeString());
            $prio = $dom->createElement('priority', $priority);
            $freq = $dom->createElement('changefreq', 'weekly');

            $newUrlNode->appendChild($loc);
            $newUrlNode->appendChild($lastmod);
            $newUrlNode->appendChild($prio);
            $newUrlNode->appendChild($freq);

            // Prepend the new node to the 'urlset'
            $mapParent->insertBefore($newUrlNode, $mapParent->firstChild);
        }

        // Save the updated XML to the desired path
        $dom->save($publicPath);
    }

    // Step 5: Update the main sitemap.xml
    $sitemapFile = public_path($xml_group);
    if (file_exists($sitemapFile)) {

        $sitemap = simplexml_load_file($sitemapFile);
        $sitemapPath = asset("xmls/$xml_prefix-xml-$year-$month.xml");

        // Check if the sitemap entry already exists
        $exists = false;
        $updated = false;
        foreach ($sitemap->sitemap as $smap) {
            if ((string)$smap->loc === $sitemapPath) {
                $exists = true;
                $updated = true;
                $smap->lastmod = now()->toDateTimeString(); // Update parent lastmod timestamp
                break;
            }
        }

        if ($updated) {
            $sitemap->asXML($sitemapFile);
            $dom = dom_import_simplexml($sitemap)->ownerDocument;
            $dom->formatOutput = true;
            $dom->save($sitemapFile);
        }

        if (!$exists) {
            // Prepend the new sitemap entry
            $newSitemap = $sitemap->addChild('sitemap');
            $newSitemap->addChild('loc', $sitemapPath);
            $newSitemap->addChild('lastmod', now()->format('Y-m-d'));
            $newSitemap->addChild('changefreq', 'weekly');

            $dom = dom_import_simplexml($sitemap)->ownerDocument;
            $dom->formatOutput = true;

            $root = $dom->documentElement;
            $firstChild = $root->firstChild;

            // $newNode = $dom->lastChild;
            // Convert the new SimpleXMLElement into a DOMElement
            $newNode = $dom->importNode(dom_import_simplexml($newSitemap), true);

            $root->insertBefore($newNode, $firstChild);

            // Save the formatted XML
            $xmlString = $dom->saveXML();

            // Reload to apply formatting
            $formattedDom = new DOMDocument();
            $formattedDom->preserveWhiteSpace = false;
            $formattedDom->formatOutput = true;
            $formattedDom->loadXML($xmlString);
            $formattedDom->save($sitemapFile);
        }
    } else {
        // Create parent / group sitemap.xml if it does not exist
        $sitemap = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"></sitemapindex>");

        $newSitemap = $sitemap->addChild('sitemap');
        $newSitemap->addChild('loc', asset("xmls/$xml_prefix-xml-$year-$month.xml"));
        $newSitemap->addChild('lastmod', now()->toDateTimeString());
        $newSitemap->addChild('changefreq', 'weekly');

        $dom = dom_import_simplexml($sitemap)->ownerDocument;
        $dom->formatOutput = true;
        $dom->save($sitemapFile);
    }

    // Step 6: Update the sitemap index
    $sitemapIndexFile = public_path('sitemap.xml');
    if (file_exists($sitemapIndexFile)) {
        $sitemapIndex = simplexml_load_file($sitemapIndexFile);
        $sitemapIndexPath = env("APP_DOMAIN")."/$xml_group";

        // Check if the sitemap entry already exists
        $exists = false;
        $updated = false;
        foreach ($sitemapIndex->sitemap as $smap) {
            if ((string)$smap->loc === $sitemapIndexPath) {
                $exists = true;
                $updated = true;
                $smap->lastmod = now()->toDateTimeString(); // Update parent lastmod timestamp
                break;
            }
        }

        if ($updated) {
            $sitemapIndex->asXML($sitemapIndexFile);
            $dom = dom_import_simplexml($sitemapIndex)->ownerDocument;
            $dom->formatOutput = true;
            $dom->save($sitemapIndexFile);
        }

        if (!$exists) {
            // Prepend the new sitemap entry
            $newSitemap = $sitemapIndex->addChild('sitemap');
            $newSitemap->addChild('loc', $sitemapIndexPath);
            $newSitemap->addChild('lastmod', now()->toDateTimeString());
            $newSitemap->addChild('changefreq', 'weekly');

            $dom = dom_import_simplexml($sitemapIndex)->ownerDocument;
            $dom->formatOutput = true;

            $root = $dom->documentElement;
            // Convert the new SimpleXMLElement into a DOMElement
            $newNode = $dom->importNode(dom_import_simplexml($newSitemap), true);

            $root->appendChild($newNode);

            // Save the formatted XML
            $xmlString = $dom->saveXML();

            // Reload to apply formatting
            $formattedDom = new DOMDocument();
            $formattedDom->preserveWhiteSpace = false;
            $formattedDom->formatOutput = true;
            $formattedDom->loadXML($xmlString);
            $formattedDom->save($sitemapIndexFile);
        }
    }
}
