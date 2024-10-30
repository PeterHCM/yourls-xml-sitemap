<?php
/*
Plugin Name: YOURLS XML Sitemap Generator
Plugin URI: https://github.com/PeterHCM/yourls-xml-sitemap
Description: Generates a sitemap.xml file with all of your short URLs
Version: 1.0
Author: Peter
Reference: https://github.com/SophiaAtkinson/yourls-html-sitemap
*/

// No direct call
if (!defined('YOURLS_ABSPATH')) die();

// Include YOURLS loader
require_once YOURLS_ABSPATH . '/includes/load-yourls.php';

// Hook to add the sitemap action
yourls_add_action('pre_html_head', 'generate_html_sitemap');

// Function to generate the sitemap.html file
function generate_html_sitemap() {
    global $ydb;

    try {
        // Initialize PDO connection
        $pdo = new PDO('mysql:host=' . YOURLS_DB_HOST . ';dbname=' . YOURLS_DB_NAME, YOURLS_DB_USER, YOURLS_DB_PASS);

        // Set PDO to throw exceptions
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Start building the HTML string
        $html .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemalocation="http://www.google.com/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        // Check if the 'private' column exists in the YOURLS database table
        $table_name = YOURLS_DB_TABLE_URL;
        $stmt = $pdo->query("DESCRIBE `$table_name`");
        $private_column_exists = false;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['Field'] === 'private') {
                $private_column_exists = true;
                break;
            }
        }

        if ($private_column_exists) {
            // Retrieve all public short URLs
            $stmt = $pdo->prepare("SELECT `keyword`, `url`, `timestamp` FROM `$table_name` WHERE `private` = '0'");
            $stmt->execute();
        } else {
            // Retrieve all short URLs (assuming all are public)
            $stmt = $pdo->prepare("SELECT `keyword`, `url`, `timestamp` FROM `$table_name`");
            $stmt->execute();
        }

        // Loop through each link and add it to the HTML sitemap
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $shorturl = yourls_link($row['keyword']);
            $longurl = htmlspecialchars($row['url']);
            $timestamp = date('c', strtotime($row['timestamp']));
            $html .= '<url>';
            $html .= '<loc>'.$shorturl.'</loc>';
	    $html .= '<lastmod>'.$timestamp.'</lastmod>';
            $html .= '<changefreq>yearly</changefreq>';
            $html .= '<priority>1</priority>';
            $html .= '</url>';
        }

        // Close the HTML
	$html .= '</urlset>';
        // Save the HTML to sitemap.xml file
	file_put_contents(YOURLS_ABSPATH . '/sitemap.xml', $html);
    } catch (PDOException $e) {
        // Handle PDO exceptions
        yourls_die('Error: ' . $e->getMessage());
    }
}
