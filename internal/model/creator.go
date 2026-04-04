package model

// Creator represents one of the indexed 3D asset sources.
type Creator int

const (
	CreatorAmbientCG        Creator = 1
	CreatorPolyhaven        Creator = 2
	CreatorShareTextures    Creator = 3
	CreatorThreeDTextures   Creator = 4
	CreatorCGBookcase       Creator = 5
	CreatorTextureCan       Creator = 6
	CreatorNoEmotionHDRs    Creator = 7
	CreatorGPUOpenMatLib    Creator = 10
	CreatorRawCatalog       Creator = 11
	CreatorPoliigon         Creator = 14
	CreatorTexturesCom      Creator = 15
	CreatorCGMood           Creator = 16
	CreatorThreeDScans      Creator = 18
	CreatorLocationTextures Creator = 19
	CreatorPbrPx            Creator = 20
	CreatorTwinbru          Creator = 21
	CreatorLightbeans       Creator = 22
)

// allCreators is in display order matching the PHP enum.
var allCreators = []Creator{
	CreatorAmbientCG,
	CreatorPolyhaven,
	CreatorShareTextures,
	CreatorThreeDTextures,
	CreatorCGBookcase,
	CreatorTextureCan,
	CreatorPbrPx,
	CreatorGPUOpenMatLib,
	CreatorNoEmotionHDRs,
	CreatorRawCatalog,
	CreatorPoliigon,
	CreatorTexturesCom,
	CreatorCGMood,
	CreatorThreeDScans,
	CreatorLocationTextures,
	CreatorTwinbru,
	CreatorLightbeans,
}

// regularScrapingTargets excludes NoEmotionHDRs (static list, rarely changes).
var regularScrapingTargets = []Creator{
	CreatorAmbientCG,
	CreatorPolyhaven,
	CreatorShareTextures,
	CreatorThreeDTextures,
	CreatorCGBookcase,
	CreatorTextureCan,
	CreatorGPUOpenMatLib,
	CreatorRawCatalog,
	CreatorPoliigon,
	CreatorTexturesCom,
	CreatorCGMood,
	CreatorThreeDScans,
	CreatorLocationTextures,
	CreatorPbrPx,
	CreatorTwinbru,
	CreatorLightbeans,
}

func AllCreators() []Creator            { return allCreators }
func RegularScrapingTargets() []Creator { return regularScrapingTargets }

func (c Creator) Slug() string {
	switch c {
	case CreatorAmbientCG:
		return "ambientcg"
	case CreatorPolyhaven:
		return "polyhaven"
	case CreatorShareTextures:
		return "sharetextures"
	case CreatorTextureCan:
		return "texturecan"
	case CreatorThreeDTextures:
		return "3d-textures"
	case CreatorCGBookcase:
		return "cgbookcase"
	case CreatorNoEmotionHDRs:
		return "noemotionhdrs"
	case CreatorGPUOpenMatLib:
		return "gpuopen-matlib"
	case CreatorRawCatalog:
		return "rawcatalog"
	case CreatorPoliigon:
		return "poliigon"
	case CreatorTexturesCom:
		return "textures-com"
	case CreatorCGMood:
		return "cgmood"
	case CreatorThreeDScans:
		return "three-d-scans"
	case CreatorLocationTextures:
		return "location-textures"
	case CreatorPbrPx:
		return "pbr-px"
	case CreatorTwinbru:
		return "twinbru"
	case CreatorLightbeans:
		return "lightbeans"
	default:
		return ""
	}
}

func (c Creator) Title() string {
	switch c {
	case CreatorAmbientCG:
		return "ambientCG"
	case CreatorPolyhaven:
		return "Poly Haven"
	case CreatorShareTextures:
		return "Share Textures"
	case CreatorThreeDTextures:
		return "3D Textures"
	case CreatorTextureCan:
		return "Texture Can"
	case CreatorCGBookcase:
		return "CG Bookcase"
	case CreatorNoEmotionHDRs:
		return "NoEmotion HDRs"
	case CreatorGPUOpenMatLib:
		return "AMD GPUOpen MaterialX Library"
	case CreatorRawCatalog:
		return "Raw Catalog"
	case CreatorPoliigon:
		return "Poliigon (Free Section)"
	case CreatorTexturesCom:
		return "Textures.com (Free Section)"
	case CreatorCGMood:
		return "CGMood (Free Section)"
	case CreatorThreeDScans:
		return "Three D Scans"
	case CreatorLocationTextures:
		return "Location Textures"
	case CreatorPbrPx:
		return "PBRPX"
	case CreatorTwinbru:
		return "Twinbru"
	case CreatorLightbeans:
		return "Lightbeans"
	default:
		return ""
	}
}

func (c Creator) Description() string {
	switch c {
	case CreatorThreeDTextures:
		return "Free seamless PBR textures and unique creations in Substance Designer."
	case CreatorAmbientCG:
		return "2000+ Public Domain materials, HDRIs and models for Physically Based Rendering."
	case CreatorPolyhaven:
		return `The Public 3D Asset Library - A combination of the websites "HDRI Haven", "Texture Haven" and "3D Model Haven."`
	case CreatorShareTextures:
		return "ShareTextures.com is creating and sharing PBR textures since 2018."
	case CreatorTextureCan:
		return "Offers free CG textures, free graphics and free patterns for 3D artists."
	case CreatorCGBookcase:
		return "Free PBR textures that come with all the map types needed to create photorealistic materials."
	case CreatorNoEmotionHDRs:
		return "An older website with an impressive collection of free HDRIs."
	case CreatorGPUOpenMatLib:
		return "A collection of high-quality materials and related textures that is available completely for free, hosted by AMD GPUOpen. (Duplicates of materials from Polyhaven are excluded.)"
	case CreatorRawCatalog:
		return "A unique library that includes many ready-to-use resources for creating amazing projects in the field of video games, films, animation and visualization."
	case CreatorPoliigon:
		return `Textures, models and HDRIs for photorealistic 3D rendering. Make better renders, faster. Currently, only the "Free" section is indexed.`
	case CreatorTexturesCom:
		return `Take your CG art to the next level with our highest quality content! Currently, only the "Free" section is indexed.`
	case CreatorCGMood:
		return `CGMood is a fresh, fair 3D marketplace. We are a team of architects and designers with many years of experience in the 3D visualization field. Currently, only the "Free" section is indexed.`
	case CreatorThreeDScans:
		return "A collection of high-quality statues/sculptures scanned in various european museums."
	case CreatorLocationTextures:
		return "Locationtextures.com is an online platform providing high quality royalty-free photo reference packs for games and film industry. We offer free packs and every pack comes with free samples."
	case CreatorPbrPx:
		return "We are a small team from China, passionate about CG production. Through PBRPX, we provide artists with completely free, unrestricted digital assets, allowing them to unleash their creativity."
	case CreatorTwinbru:
		return "Browse our library of more than 13 000 digital fabric twins to download 3D fabric textures or order physical fabric samples."
	case CreatorLightbeans:
		return "We Connect Manufacturers with Architects and Designers - Thousands of digitized products for your projects."
	default:
		return ""
	}
}

func (c Creator) LicenseType() CreatorLicenseType {
	switch c {
	case CreatorAmbientCG, CreatorPolyhaven, CreatorShareTextures, CreatorPbrPx,
		CreatorThreeDTextures, CreatorCGBookcase, CreatorGPUOpenMatLib, CreatorTextureCan:
		return LicensePublicDomain
	case CreatorNoEmotionHDRs:
		return LicenseOpenLicense
	default:
		return LicenseAnyLicense
	}
}

func (c Creator) LicenseURL() string {
	switch c {
	case CreatorAmbientCG:
		return "https://docs.ambientcg.com/license/"
	case CreatorPolyhaven:
		return "https://polyhaven.com/license"
	case CreatorShareTextures:
		return "https://www.sharetextures.com/p/license"
	case CreatorThreeDTextures:
		return "https://3dtextures.me/about/"
	case CreatorCGBookcase:
		return "https://www.cgbookcase.com/textures#:~:text=The%20textures%20are%20published%20under%20the%20CC0%201.0%20license"
	case CreatorTextureCan:
		return "https://www.texturecan.com/terms/"
	case CreatorNoEmotionHDRs:
		return "https://noemotionhdrs.net/#:~:text=NoEmotionHDRs%20by%20Peter%20Sanitra%20is%20licensed%20under%20a%20Creative%20Commons"
	case CreatorRawCatalog:
		return "https://rawcatalog.com/terms/"
	case CreatorPoliigon:
		return "https://help.poliigon.com/en/articles/8749749-asset-use-licensing"
	case CreatorTexturesCom:
		return "https://www.textures.com/faq-license"
	case CreatorCGMood:
		return "https://cgmood.com/faq"
	case CreatorThreeDScans:
		return "https://threedscans.com/info/"
	case CreatorLocationTextures:
		return "https://locationtextures.com/privacy-policy/"
	case CreatorPbrPx:
		return "https://pbrpx.com/privacy-policy/"
	case CreatorTwinbru:
		return "https://textures.twinbru.com/en/products?page=1&page_size=50"
	case CreatorLightbeans:
		return "https://lightbeans.com/en/pages/texture-terms"
	default:
		return ""
	}
}

func (c Creator) BaseURL() string {
	switch c {
	case CreatorThreeDTextures:
		return "https://3dtextures.me"
	case CreatorAmbientCG:
		return "https://ambientCG.com"
	case CreatorPolyhaven:
		return "https://polyhaven.com"
	case CreatorShareTextures:
		return "https://sharetextures.com"
	case CreatorTextureCan:
		return "https://texturecan.com"
	case CreatorCGBookcase:
		return "https://cgbookcase.com"
	case CreatorNoEmotionHDRs:
		return "http://noemotionhdrs.net"
	case CreatorGPUOpenMatLib:
		return "https://matlib.gpuopen.com/"
	case CreatorRawCatalog:
		return "https://rawcatalog.com"
	case CreatorPoliigon:
		return "https://www.poliigon.com/search/free"
	case CreatorTexturesCom:
		return "https://www.textures.com/free"
	case CreatorCGMood:
		return "https://cgmood.com/free"
	case CreatorThreeDScans:
		return "https://threedscans.com/"
	case CreatorLocationTextures:
		return "https://locationtextures.com/panoramas/free-panoramas/"
	case CreatorPbrPx:
		return "https://library.pbrpx.com/"
	case CreatorTwinbru:
		return "https://textures.twinbru.com"
	case CreatorLightbeans:
		return "https://lightbeans.com"
	default:
		return ""
	}
}

func CreatorFromSlug(slug string) (Creator, bool) {
	for _, c := range allCreators {
		if c.Slug() == slug {
			return c, true
		}
	}
	return 0, false
}

func CreatorFromValue(v int) (Creator, bool) {
	for _, c := range allCreators {
		if int(c) == v {
			return c, true
		}
	}
	return 0, false
}
