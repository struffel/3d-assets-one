
# 3Dassets.one - The 3D Asset Search Engine
 
[3Dassets.one](https://3Dassets.one) is a search engine for **free** high quality 3D assets, such as 3D models, PBR materials and HDRIs from numerous different vendors, allowing you to find great free assets without opening a dozen browser tabs.

The website works by regularly checking the individual creator's websites for new releases and listing them in one location for easy searching.

Browse thousands of free assets and filter for asset type, vendor or license and find new sources for high quality models, materials and other resources.

## Planned Features

- Finding and adding more high-quality vendors to the index.
- Improving keyword-based search by using image-to-text systems (AI/ML) to generate keywords based on the thumbnail images. This is necessary because not all sites provide the same quality and style of tagging on their assets.
- Adding a system for detecting inactive asset links, for example because an asset has been removed from the creator's site.

## Contributing
See [contributing.md](contributing.md) for information on how to run 3Dassets.one locally and how to add new creators.

## FAQ

### Is 3Dassets.one making copies of the assets?
**No.** 3Dassets.one does not copy or reupload the assets from all the websites it indexes, it only links to them - as search engines do.
The site only caches a small (256px) copy of each asset's thumbnail image to improve performance and reduce load on the origin servers.

### Can <example.com> get added to the index?
**Maybe.** The criteria for adding sites aren't completely rigid, but these are the general rules for getting listed:

- The assets need to be available without payment. This may change in the future, but for now the site is strictly focussed on free content. Needing to log in with a free account is acceptable.
- Every asset needs to be individually linkable with a unique URL. No asset packs.
- The site should have at least 10+ assets. Anything below that makes an integration barely worth it.
- The assets should have an acceptable level of quality. This definition is obviously vague and subjective, but it *excludes* for example:
    - Large collections of AI-generated assets.
    - Textures with no or very poor processing for seamlessness.
    - PBR materials with badly generated maps.
    - Assets with other severe technical deficiencies, like blur or poorly made photogrammetry.
- The site should provide an acceptable user experience. Requiring sign-up and showing ads is generally acceptable, as long as the ads do not delve into inappropriate or scam-like territory (like showing fake download buttons).

There are counterexamples for many of these rules that can be found in the current index, but fulfilling these criteria makes inclusion much more likely.

### Can I self-host my own instance of 3Dassets.one?
**Generally, yes. But there is no real reason for it.**
Any copy of the site would just start indexing the same sites as the public version again, making it not really useful.
The situation changes of course if you add new sites to the index.
But in that case it may be better to just create a pull request to let other people benefit from the changes as well.

### How does 3Dassets.one get the assets from all these different sites?
**In many different ways**. Due to the lack of an industry standard for communicating asset metadata (OpenAssetIO is still in the earliest stages) 3Dassets.one has individual implementations for every vendor it indexes.
This includes using existing APIs, working with website owners to create an API specifically for 3Dassets.one or working directly with the HTML and its `<meta>` tags to get the required information for the index.