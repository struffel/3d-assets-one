<?php

use asset\AssetSorting;
use blocks\FooterBlock;
use blocks\HeadBlock;
use blocks\HeaderBlock;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php HeadBlock::render(); ?>

<body>
	<link rel="stylesheet" href="/css/page/about-site.css">
	<?php HeaderBlock::render(); ?>
	<main>
		<h2>What is 3Dassets.one?</h2>
		<p>
			3Dassets.one is a search engine that combines the libraries of many independent CG asset websites like Poly Haven, ambientCG and others and allows you to look for the right texture, HDRI or model without having to open half a dozen browser tabs.
		</p>
		<p>
			The site automatically polls the websites of all creators for new assets and automatically lists them. You can filter the results by creator or using keywords, categories and licenses. When clicking on an asset you will be redirected to the creators website.
			3Dassets.one does not copy, archive or re-upload the assets.
		</p>
		<h2>Can I get my assets listed?</h2>
		<p>
			Yes, you can submit your assets, but there are a few requirements:
		<ul>
			<li><strong>Your collection must already be available on the internet.</strong>
				You don't need to have your own website with a custom domain, a page on Gumroad, Sketchfab or other platforms works as well.</li>
			<li><strong>The assets must be available for free.</strong>
				There are no precise restrictions regarding the exact license, but 3Dassets.one currently does not list paid models.</li>
			<li><strong>Your collection should have at least ~20 assets.</strong></li>
			<li><strong>Every asset must be adressable individually using a unique URL.</strong>
				The URL should ideally point to a dedicated page for just this asset:
				<pre>https://example.com/assets/bricks01</pre>
				But it can also use anchor tags:
				<pre>https://example.com/assets#bricks01</pre>
				This rule means that asset packs (which bundle multiple materials/models/HDRIs in one download) cannot be supported.<br>
			</li>
		</ul>
		If your asset collection fulfills these criterias you can write to <strong>info [at] 3Dassets.one</strong> to discuss an integration.
		</p>
		<h2>API</h2>
		<p>
			Below is a description for <code>v2</code> of the API for 3Dassets.one. Please keep in mind that the API does not come with any uptime/availability guarantees.
			If you are using data from the API in another product I would appreciate a link back to the site, along the lines of:
		<blockquote>
			"Search results powered by <a href="https://3Dassets.one">3Dassets.one</a>."
		</blockquote>
		</p>
		<h3><code>/api/v2/assets</code></h3>
		<p>This endpoint allows searching the link database. Its HTTP parameters are identical to those of the main search page.</p>
		<p>Below is a table with all possible parameters. Values with the <code>[]</code>-suffix can be included multiple times in the query string to filter for multiple values.</p>
		<table>
			<tr>
				<td><code>q</code></td>
				<td>The search string, a list of tags.</td>
			</tr>
			<tr>
				<td><code>id[]</code></td>
				<td>Allows searching for specific asset ids.</td>
			</tr>
			<tr>
				<td><code>creator[]</code></td>
				<td>Allows searching for specific creators using their slug. Check the <code>/creators</code>-endpoint to see a list of possible values.</td>
			</tr>
			<tr>
				<td><code>type[]</code></td>
				<td>Allows searching for specific types using their slug. Check the <code>/types</code>-endpoint to see a list of possible values.</td>
			</tr>
			<tr>
				<td><code>limit</code></td>
				<td>Determines how many assets are returned. Default is 150, maximum is 500 per request.</td>
			</tr>
			<tr>
				<td><code>offset</code></td>
				<td>Allows shifting the results to allow pagination.</td>
			</tr>
			<tr>
				<td><code>sort</code></td>
				<td>Determines the sorting order of the result. Possible values are: <code><?php foreach (AssetSorting::cases() as $s) {
																								echo $s->value . " ";
																							} ?></code></td>
			</tr>
			<tr>
				<td><code>thumbnail-format</code></td>
				<td>Determines the format used in the <code>thumbnailUrl</code> field. Possible values are: <code>128-PNG 256-PNG 128-JPG-FFFFFF 256-JPG-FFFFFF</code></td>
			</tr>

		</table>
		<h3><code>/api/v2/assets_rss</code></h3>
		<p>This endpoint generates a customizable RSS feed of newly indexed assets. Filters from the regular <code>/assets</code> endpoint can be used here as well.</p>
		<h3><code>/api/v2/types</code></h3>
		<p>This endpoint returns all asset types currently featured on 3Dassets.one. It does not accept any parameters.</p>
		<h3><code>/api/v2/creators</code></h3>
		<p>This endpoint returns all creators currently featured on 3Dassets.one. It does not accept any parameters.</p>

		<h2>Creative Commons Attributions</h2>
		<h3>Icons</h3>
		<ul>
			<li>Calendar by Iconika from <a href="https://thenounproject.com/browse/icons/term/calendar/" target="_blank" title="Calendar Icons">Noun Project</a> (CC BY 3.0)</li>
			<li>feather by Monjin Friends from <a href="https://thenounproject.com/browse/icons/term/feather/" target="_blank" title="feather Icons">Noun Project</a> (CC BY 3.0)</li>
			<li>storage by Cuputo from <a href="https://thenounproject.com/browse/icons/term/storage/" target="_blank" title="storage Icons">Noun Project</a> (CC BY 3.0)</li>
			<li>user login by JS from <a href="https://thenounproject.com/browse/icons/term/user-login/" target="_blank" title="user login Icons">Noun Project</a> (CC BY 3.0)</li>
			<li>ads by DinosoftLabs from <a href="https://thenounproject.com/browse/icons/term/ads/" target="_blank" title="ads Icons">Noun Project</a> (CC BY 3.0)</li>
			<li>pop up by kumakamu from <a href="https://thenounproject.com/browse/icons/term/pop-up/" target="_blank" title="pop up Icons">Noun Project</a> (CC BY 3.0)</li>
			<li>bundle by Mark Jayvee Pabilonia from <a href="https://thenounproject.com/browse/icons/term/bundle/" target="_blank" title="bundle Icons">Noun Project</a> (CC BY 3.0)</li>
			<li>network check by Justin Blake from <a href="https://thenounproject.com/browse/icons/term/network-check/" target="_blank" title="network check Icons">Noun Project</a> (CC BY 3.0)</li>
		</ul>



	</main>
	<?php FooterBlock::render(); ?>
</body>

</html>