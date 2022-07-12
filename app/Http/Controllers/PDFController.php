<?php

namespace App\Http\Controllers;

use App\Library\FreshSales\FreshSales;
use App\Library\GoogleAds\GoogleAds;
use Google\Ads\GoogleAds\V11\Enums\AdGroupTypeEnum\AdGroupType;
use Google\Ads\GoogleAds\V11\Enums\ExtensionTypeEnum\ExtensionType;
use Google\Ads\GoogleAds\V11\Enums\FeedItemTargetDeviceEnum\FeedItemTargetDevice;
use Google\Ads\GoogleAds\V11\Resources\ExtensionFeedItem;
use Google\Ads\GoogleAds\V11\Services\GoogleAdsServiceClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class PDFController extends Controller
{
    public function store($account)
    {
        $pdf = $this->createBasePdf();

        $fsClient = new FreshSales();
        $fsAccount = $fsClient->account()->get($account);

        if (!$fsAccount->offsetExists('sales_account')) abort(404);

        $fsAccount = $fsAccount['sales_account'];

        $googleAccountIds = $fsAccount['custom_field']['cf_adwords_ids'];
        $pdf = $this->createAdGroupPages($pdf, $googleAccountIds);

        $pdf->output('AdWords Campaign.pdf', 'D');
    }

    /**
     * Create base PDF
     *
     * @return \Mpdf\Mpdf
     */
    private function createBasePdf()
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $pdf = new Mpdf([
            // TempDir
            'tempDir' => base_path('storage/app/mpdf'),

            // Fonts
            'fontDir' => array_merge($fontDirs, [
                public_path('fonts/'),
            ]),
            'fontdata' => $fontData + [
                'arial' => [
                    'R' => 'pdf/Arial.ttf',
                    'B' => 'pdf/Arial-Bold.ttf',
                    'I' => 'pdf/Arial-Italic.ttf',
                    'BI' => 'pdf/Arial-Bold-Italic.ttf',
                ],
            ],
            'default_font' => 'arial',

            // Margin
            // 'margin_left' => 5,
            // 'margin_right' => 5,
            'margin_top' => 30,
            'margin_bottom' => 50,
        ]);

        $googlePartnerImage = public_path('images/pdf/google-partner.jpg');
        $poweredByImage = public_path('images/pdf/powered-by.jpg');
        $title = 'DRAFT Google AdWords Campaign';
        $pdf->SetHTMLHeader("
        <table style=\"width: 75%; margin: 0 auto;\">
            <tr>
                <td style=\"width: 100px;\">
                    <img src=\"{$googlePartnerImage}\" width=\"100px\">
                </td>
                <td style=\"padding: 0 25px;white-space: nowrap;\">
                    <h2 style=\"\">{$title}</h2>
                </td>
                <td style=\"width: 100px;\">
                    <img src=\"{$poweredByImage}\" width=\"100px\">
                </td>
            </tr>
        </table>
        ");

        $footerImage = public_path('images/pdf/footer.jpg');
        $pdf->SetHTMLFooter("
        <table>
            <tr>
                <td><img src=\"{$footerImage}\"></td>
            </tr>
            <tr>
                <td align=\"right\">
                    Page <strong>{PAGENO}</strong> of <strong>{nbpg}</strong>
                </td>
            </tr>
        </table>
        ");

        $firstPageImage = public_path('images/pdf/first-page.jpg');
        $pdf->writeHtml("
            <h1 style=\"color: #1073b5;\">Welcome to AiiMS</h1>
            <h2><strong>Digital Marketing Strategies!</strong></h2>
            <p style=\"color: #808080; line-height: 1.5;\">
                We’re pleased to present your draft Google AdWords Campaign. This is just the first step in create a campaign your account. We require your approval and/or adjustment to the details of the campaign stated below.
            </p>
            <p style=\"color: #808080; line-height: 1.5;\">
                Once you’ve advised AiiMS of any adjustments or changes to the campaign it is uploaded to the Google&trade; Network. We’ll immediately commence monitor and measuring the campaign performance and make on-going adjustments as necessary including but not limited to: Keyword changes, Optimizing of the Adverts and any applicable settings changes. If you have any questions regarding this Draft, please contact your Client Experience Manager.
            </p>

            <h2><strong>Advert Layouts</strong></h2>
            <p style=\"color: #808080; line-height: 1.5;\">
                Here’s what your Ad could look like, the graphic below illustrates the breakdown of each element of the Google Text Ad. We will design your adverts to stay within these character limits.
            </p>
            <img src=\"{$firstPageImage}\">
        ");
        $pdf->AddPage();

        return $pdf;
    }

    /**
     *
     * @param \Mpdf\Mpdf $pdf
     * @param string $ids
     * @return \Mpdf\Mpdf
     */
    private function createAdGroupPages(Mpdf $pdf, $ids)
    {
        // HACK
        ini_set("pcre.backtrack_limit", "5000000");
        $adsClient = (new GoogleAds())->client()->getGoogleAdsServiceClient();

        $accountIds = Str::of($ids)->replace('-', '')->explode("\n");

        $adGroups = collect();
        $extensions = collect();

        foreach ($accountIds as $accountId) {
            $tempAdGroups = $this->handleAdGroups($accountId, $adsClient);
            $tempAdGroups = $this->handleExpandedText($accountId, $adsClient, $tempAdGroups);
            $tempAdGroups = $this->handleKeywords($accountId, $adsClient, $tempAdGroups);

            $tempExtensions = $this->handleExtensions($accountId, $adsClient);

            $adGroups = $adGroups->merge($tempAdGroups);
            $extensions = $extensions->merge($tempExtensions);
        }

        $i = 1;
        // // Container -- Start
        for ($i = 0; $i < $adGroups->count() / 2; $i++) {
            $offset = $i * 2;
            $toWrite = '<div style="float: left;">';

            if ($adGroups->offsetExists($offset)) {
                $adGroupOne = $adGroups->offsetGet($offset);
                $o1 = $offset + 1;
                $toWrite .= "
                <div style=\"text-align: center; width: 50%; float: left; margin-bottom: 25px; margin-right: 25px;\">
                    <div style=\"border: 1px solid #0072bc; background-color: #0072bc; color: white; padding: 10px;\">
                        <strong>Ad Group {$o1}: {$adGroupOne['name']}</strong>
                    </div>
                    <div style=\"border: 1px solid #0072bc; padding: 10px; font-size: 13px;\">
                        {$adGroupOne['expanded_texts']->join('<br />')}
                    </div>
                    <div style=\"border: solid #0072bc; color: #0072bc; border-width: 0 1px; padding: 10px;\">
                        Keyword List
                    </div>
                    <div style=\"border: 1px solid #0072bc; padding: 10px; font-size: 13px;\">
                        {$adGroupOne['keywords']->join('<br/>')}
                    </div>
                </div>
                ";
            }
            if ($adGroups->offsetExists($offset + 1)) {
                $adGroupTwo = $adGroups->offsetGet($offset + 1);
                $o2 = $offset + 2;
                $toWrite .= "
                <div style=\"text-align: center; width: 50%; float: left; margin-bottom: 25px; margin-left: 25px;\">
                    <div style=\"border: 1px solid #0072bc; background-color: #0072bc; color: white; padding: 10px;\">
                        <strong>Ad Group {$o2}: {$adGroupTwo['name']}</strong>
                    </div>
                    <div style=\"border: 1px solid #0072bc; padding: 10px; font-size: 13px;\">
                        {$adGroupTwo['expanded_texts']->join('<br />')}
                    </div>
                    <div style=\"border: solid #0072bc; color: #0072bc; border-width: 0 1px; padding: 10px;\">
                        Keyword List
                    </div>
                    <div style=\"border: 1px solid #0072bc; padding: 10px; font-size: 13px;\">
                        {$adGroupTwo['keywords']->join('<br/>')}
                    </div>
                </div>
                ";
            }

            $toWrite .= "</div>";

            $pdf->WriteHTML($toWrite);
            $pdf->AddPage();
        }

        // Container -- Start
        $toWrite = '<div style="float: left;">';

        // Sitelink Extensions
        $sitelinks = $extensions['sitelink']->map(function ($values, $url) {
            $text = $values['text']->join('<br/>');
            return "
                <p>
                    {$text}
                    <br/>
                    {$values['line1']}
                    <br/>
                    {$values['line2']}
                    <br/>
                    <span style=\"color: #1a0dab;\">
                        {$url}
                    </span>
                </p>
            ";
        })->join('');
        $toWrite .= "
            <div style=\"text-align: center; width: 50%; float: left; margin-bottom: 25px; margin-right: 25px;\">
                <div style=\"border: 1px solid #0072bc; background-color: #0072bc; color: white; padding: 10px;\">
                    <strong>Sitelink Extensions</strong>
                </div>
                <div style=\"border: 1px solid #0072bc; padding: 10px; font-size: 13px;\">
                    The sitelinks ad extension shows links to specific pages on your website beneath the text of your ads helping customers get to what they are looking for on your site with just one click. Sitelinks appear in ads at the top and bottom of Google search results.
                </div>
                <div style=\"border: 1px solid #0072bc; border-top-width: 0; padding: 10px; font-size: 13px;\">
                    {$sitelinks}
                </div>
            </div>
        ";

        // Callout Extensions
        $callouts = $extensions['callout']->sort()->values()->map(function ($value, $key) {
            $spacing = $key % 2 == 0 ? 'margin-bottom: 0;' : 'margin-top: 0;';
            return "<p style=\"{$spacing}\">{$value}</p>";
        })->join('');

        $toWrite .= "
            <div style=\"text-align: center; width: 50%; float: left; margin-bottom: 25px; margin-left: 25px;\">
                <div style=\"border: 1px solid #0072bc; background-color: #0072bc; color: white; padding: 10px;\">
                    <strong>Callout Extensions</strong>
                </div>
                <div style=\"border: 1px solid #0072bc; padding: 10px; font-size: 13px;\">
                    The call-out ad extension lets you include additional text with your search ads. This lets you provide detailed information about your business, including products and services that you offer. Call-outs appear in ads at the top and bottom of Google search results.
                </div>
                <div style=\"border: 1px solid #0072bc; border-top-width: 0; padding: 10px; font-size: 13px;\">
                    {$callouts}
                </div>
            </div>
        ";

        // Structures Snippets Extensions
        $snippets = $extensions['structured_snippet']->join('');

        $toWrite .= "
            <div style=\"text-align: center; width: 50%; float: left; margin-bottom: 25px; margin-right: 25px;\">
                <div style=\"border: 1px solid #0072bc; background-color: #0072bc; color: white; padding: 10px;\">
                    <strong>Structured Snippets</strong>
                </div>
                <div style=\"border: 1px solid #0072bc; padding: 10px; font-size: 13px;\">
                    Structured snippets are extensions that highlight specific aspects of your products and services. They show underneath your text ad.
                </div>
                <div style=\"border: 1px solid #0072bc; border-top-width: 0; padding: 10px; font-size: 13px; text-align: left;\">
                    {$snippets}
                </div>
            </div>
        ";

        // Container -- End
        $toWrite .= '</div>';

        $pdf->WriteHTML($toWrite);

        return $pdf;

    }

    /**
     * Handle Ad Group Search
     *
     * @param string $accountId
     * @param GoogleAdsServiceClient $client
     * @return Collection
     */
    private function handleAdGroups($accountId, GoogleAdsServiceClient $client)
    {
        $adGroupQuery = "SELECT ad_group.id, ad_group.name, ad_group.type FROM ad_group";
        $adGroupStream = $client->search($accountId, $adGroupQuery);
        $adGroups = collect();

        foreach ($adGroupStream->iterateAllElements() as $adGroupRow) {
            $adGroup = $adGroupRow->getAdGroup();

            if ($adGroup->getType() == AdGroupType::SEARCH_DYNAMIC_ADS) continue;

            $adGroups[$adGroup->getId()] = collect([
                'name' => $adGroup->getName(),
                'keywords' => collect(),
                'expanded_texts' => collect(),
            ]);
        }

        return $adGroups;
    }

    /**
     * Handle Expanded Text Search
     *
     * @param string $accountId
     * @param GoogleAdsServiceClient $client
     * @param Collection $adGroups
     * @return Collection
     */
    private function handleExpandedText($accountId, GoogleAdsServiceClient $client, Collection $adGroups)
    {
        $adGroupAdQuery = "SELECT
                ad_group.id,
                ad_group_ad.ad.final_urls,
                ad_group_ad.ad.expanded_text_ad.description,
                ad_group_ad.ad.expanded_text_ad.description2,
                ad_group_ad.ad.expanded_text_ad.headline_part1,
                ad_group_ad.ad.expanded_text_ad.headline_part2,
                ad_group_ad.ad.expanded_text_ad.headline_part3,
                ad_group_ad.ad.expanded_text_ad.path1,
                ad_group_ad.ad.expanded_text_ad.path2
                FROM ad_group_ad
            ";
        $adGroupAdStream = $client->search($accountId, $adGroupAdQuery);
        foreach ($adGroupAdStream->iterateAllElements() as $adGroupAdRow) {
            $adGroup = $adGroupAdRow->getAdGroup();

            if (!$adGroups->offsetExists($adGroup->getId())) continue;

            $adGroupAd = $adGroupAdRow->getAdGroupAd()->getAd();

            $finalUrls = $adGroupAd->getFinalUrls();

            if ($finalUrls->count() == 0) continue;

            $parsedFinalUrls = parse_url($finalUrls->offsetGet(0));
            $finalUrl = "{$parsedFinalUrls['scheme']}://{$parsedFinalUrls['host']}";
            $expandedTextAd = $adGroupAd->getExpandedTextAd();

            if (!$expandedTextAd) continue;

            $expandedText = Str::of("
                    <p style=\"color: #1a0dab; margin: 0;\">
                        {$expandedTextAd->getHeadlinePart1()} | {$expandedTextAd->getHeadlinePart2()} | {$expandedTextAd->getHeadlinePart3()}
                    </p>
                    <p style=\"color: #006621; margin: 0;\">
                        {$finalUrl}/{$expandedTextAd->getPath1()}/{$expandedTextAd->getPath2()}
                    </p>
                    <p style=\"color: #545454; margin: 0;\">
                        {$expandedTextAd->getDescription()}. {$expandedTextAd->getDescription2()}
                    </p>
                ")
                ->explode("\n")
                ->transform(function ($line) {
                    return trim($line);
                })
                ->join("\n");
            $adGroups[$adGroup->getId()]['expanded_texts']->push($expandedText);
        }

        return $adGroups;
    }

    /**
     * Haandle Keyword Search
     *
     * @param string $accountId
     * @param GoogleAdsServiceClient $client
     * @param Collection $adGroups
     * @return Collection
     */
    private function handleKeywords($accountId, GoogleAdsServiceClient $client, Collection $adGroups)
    {
        $keywordQuery = "SELECT ad_group.id, ad_group_criterion.keyword.text FROM ad_group_criterion WHERE ad_group_criterion.type = KEYWORD";
        $keywordStream = $client->search($accountId, $keywordQuery);

        foreach ($keywordStream->iterateAllElements() as $keywordRow) {
            $adGroup = $keywordRow->getAdGroup();

            if (!$adGroups->offsetExists($adGroup->getId())) continue;

            $keyword = $keywordRow->getAdGroupCriterion()->getKeyword() ?? null;

            if (!$keyword) continue;

            $adGroups[$adGroup->getId()]['keywords']->push($keyword->getText());
        }

        return $adGroups;
    }

    /**
     * Handle Extensions Search
     *
     * @param string $accountId
     * @param GoogleAdsServiceClient $client
     * @return Collection
     */
    private function handleExtensions($accountId, GoogleAdsServiceClient $client)
    {
        $extensions = collect([
            'sitelink' => collect(),
            'callout' => collect(),
            'structured_snippet' => collect(),
        ]);
        $extensionTypes = collect([
            'SITELINK',
            'CALLOUT',
            'STRUCTURED_SNIPPET',
        ]);
        $allowedExtensions = $extensionTypes->join(', ');

        $extensionsQuery = "SELECT
                extension_feed_item.extension_type,
                extension_feed_item.device,
                extension_feed_item.sitelink_feed_item.final_urls,
                extension_feed_item.sitelink_feed_item.link_text,
                extension_feed_item.sitelink_feed_item.line1,
                extension_feed_item.sitelink_feed_item.line2,
                extension_feed_item.callout_feed_item.callout_text,
                extension_feed_item.structured_snippet_feed_item.header,
                extension_feed_item.structured_snippet_feed_item.values
            FROM extension_feed_item
            WHERE extension_feed_item.extension_type IN ({$allowedExtensions})
        ";
        $extensionsStream = $client->search($accountId, $extensionsQuery);

        foreach ($extensionsStream->iterateAllElements() as $extensionRow) {
            $extension = $extensionRow->getExtensionFeedItem();
            switch($extension->getExtensionType()) {
                case ExtensionType::SITELINK:
                    if (!$extension->hasSitelinkFeedItem()) break;

                    $sitelink = $this->handleSitelinkExtension($extension);

                    if (!$sitelink) break;

                    [
                        'url' => $url,
                        'text' => $text,
                        'line1' => $line1,
                        'line2' => $line2,
                    ] = $sitelink;

                    // Initialize url collection
                    if (!$extensions['sitelink']->offsetExists($url)) {
                        $extensions['sitelink'][$url] = collect([
                            'text' => collect(),
                            'line1' => '',
                            'line2' => '',
                        ]);
                    }

                    // Ignore existing text
                    if ($extensions['sitelink'][$url]->contains($text)) break;

                    // Push new text
                    $extensions['sitelink'][$url]['text']->push($text);
                    $extensions['sitelink'][$url]['line1'] = $line1;
                    $extensions['sitelink'][$url]['line2'] = $line2;
                    break;
                case ExtensionType::CALLOUT:
                    if (!$extension->hasCalloutFeedItem()) break;

                    $text = $extension->getCalloutFeedItem()->getCalloutText();

                    if ($extension->getDevice() == FeedItemTargetDevice::MOBILE) {
                        $text .= ' (Mobile)';
                    }

                    $extensions['callout']->push($text);

                    break;
                case ExtensionType::STRUCTURED_SNIPPET:
                    if (!$extension->hasStructuredSnippetFeedItem()) break;

                    $structuredSnippet = $extension->getStructuredSnippetFeedItem();

                    $header = $structuredSnippet->getHeader();
                    $values = collect($structuredSnippet->getValues())->join(', ');

                    if ($extension->getDevice() == FeedItemTargetDevice::MOBILE) {
                        $values .= ' (Mobile)';
                    }

                    $extensions['structured_snippet']->push("<p><strong>{$header}:</strong> {$values}</p>");

                    break;
            }
        }

        return $extensions;
    }

    /**
     * Handle Sitelink Extension
     *
     * @param ExtensionFeedItem $extension
     * @return string[]
     */
    private function handleSitelinkExtension(ExtensionFeedItem $extension)
    {
        $sitelink = $extension->getSitelinkFeedItem();
        $finalUrls = $sitelink->getFinalUrls();

        if (!$finalUrls->offsetExists(0)) return null;

        [
            'scheme' => $scheme,
            'host' => $host,
            'path' => $path,
        ] = parse_url($finalUrls->offsetGet(0));
        $url = "{$scheme}://{$host}{$path}";

        $text = $sitelink->getLinkText();
        $line1 = $sitelink->getLine1();
        $line2 = $sitelink->getLine2();

        if ($extension->getDevice() == FeedItemTargetDevice::MOBILE) {
            $text .= ' (Mobile)';
        }

        return [
            'url' => $url,
            'text' => $text,
            'line1' => $line1,
            'line2' => $line2,
        ];
    }
}
